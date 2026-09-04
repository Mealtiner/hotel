<?php
/**
 * GARRY Security – UpdraftPlus backup gate (Fáze 2).
 *
 * Podle GARRY-WP-SECURITY-BEHAVIOR-SPEC.md sekce 8: tlačítko spustí Updraft
 * full backup přes feature-detected akci `updraft_backupnow_backup_all`.
 * Volání znamená START, nikoli úspěch, takže se stav po vyžádání ponechává
 * jako `requested`. Fáze D doplnila best-effort čtení vlastní historie
 * záloh UpdraftPlus (`get_own_history_hint()`) – jen nejobecnější tvar
 * option `updraft_backup_history` (klíče = časy sad), nikdy se nedomýšlí
 * vnitřní strukturu jednotlivé sady (db/pluginy/…), protože ta není
 * dokumentované veřejné API a mezi verzemi se může lišit. I s touhle
 * nápovědou platí, že GARRY nikdy netvrdí „záloha dokončena" bez důkazu –
 * odkazuje na vlastní historii zálohování UpdraftPlus, kde si
 * administrátor dokončení a obsah ověří sám.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Backup_Gate' ) ) {

	class Garry_Security_Backup_Gate {

		const OPTION_KEY    = 'garry_security_backup_request';
		const PROVIDER_SLUG = 'updraftplus';

		/**
		 * Vyžádá zálohu přes UpdraftPlus. Vrátí true při úspěšném vyvolání,
		 * nebo WP_Error pokud UpdraftPlus není aktivní/nepodporuje akci,
		 * nebo pokud už jiná záloha běží.
		 */
		public static function trigger( $requested_by_user_id ) {
			if ( ! garry_security_provider_is_active( self::PROVIDER_SLUG ) ) {
				return new WP_Error( 'garry_updraft_inactive', 'UpdraftPlus není aktivní.' );
			}

			if ( ! has_action( 'updraft_backupnow_backup_all' ) ) {
				return new WP_Error( 'garry_updraft_unsupported', 'Nainstalovaná verze UpdraftPlus nepodporuje očekávanou akci pro vyžádání zálohy.' );
			}

			$last = self::get_last_request();
			if ( $last && 'requested' === $last['status'] && ( time() - $last['requested_at'] ) < 30 * MINUTE_IN_SECONDS ) {
				return new WP_Error( 'garry_backup_in_progress', 'Záloha už byla vyžádána nedávno – počkejte na dokončení, než vyžádáte další.' );
			}

			/**
			 * Zdroj: docs/GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md, sekce 4
			 * „Tlačítko před rizikovou změnou".
			 */
			do_action( 'updraft_backupnow_backup_all', array( 'nocloud' => 0 ) );

			update_option( self::OPTION_KEY, array(
				'status'        => 'requested',
				'requested_at'  => time(),
				'requested_by'  => (int) $requested_by_user_id,
			), false );

			return true;
		}

		public static function get_last_request() {
			$stored = get_option( self::OPTION_KEY );
			return is_array( $stored ) ? $stored : null;
		}

		/**
		 * Best-effort čtení UpdraftPlus vlastní historie záloh (Fáze D, bod 1).
		 * Čte jen `updraft_backup_history` – option přímo vlastněná UpdraftPlus,
		 * ne interní dokumentované API. Věří se jí jen v tom nejobecnějším
		 * tvaru (pole klíčované unixovým časem každé sady zálohy), nikdy se
		 * nedomýšlí vnitřní struktura jednotlivé položky (db/pluginy/…), takže
		 * ani změna té struktury mezi verzemi tenhle kód nerozbije. Vrací
		 * `null`, pokud option chybí nebo nemá očekávaný tvar – nikdy
		 * nefabrikuje "dokončeno", pokud si tím není jisté (viz komentář nad
		 * třídou a docs/GARRY-WP-SECURITY-BEHAVIOR-SPEC.md, zásada "žádná
		 * fabrikovaná jistota").
		 */
		public static function get_own_history_hint() {
			if ( ! garry_security_provider_is_active( self::PROVIDER_SLUG ) ) {
				return null;
			}
			$history = get_option( 'updraft_backup_history' );
			if ( ! is_array( $history ) || empty( $history ) ) {
				return null;
			}
			$timestamps = array_filter( array_keys( $history ), 'is_numeric' );
			if ( empty( $timestamps ) ) {
				return null;
			}
			$last = max( $timestamps );
			return array(
				'last_backup_set_time' => (int) $last,
				'sets_recorded'        => count( $timestamps ),
			);
		}

		/**
		 * Best-effort čtení konfigurace UpdraftPlus (verze 2.2, P0
		 * "Obnovitelnost zálohy" – typ, vzdálené úložiště, retence). Stejná
		 * zásada jako get_own_history_hint(): jen obecný tvar hodnot přímo
		 * z options UpdraftPlus, nikdy se nedomýšlí jejich vnitřní detail.
		 * Chybějící/neočekávaná hodnota se prostě vynechá, nikdy se
		 * nefabrikuje.
		 */
		public static function get_backup_configuration_hint() {
			if ( ! garry_security_provider_is_active( self::PROVIDER_SLUG ) ) {
				return null;
			}

			$service = get_option( 'updraft_service' );
			$services = array();
			if ( is_array( $service ) ) {
				$services = array_values( array_filter( $service, 'is_string' ) );
			} elseif ( is_string( $service ) && '' !== $service ) {
				$services = array( $service );
			}
			$services = array_values( array_diff( $services, array( '', 'none' ) ) );

			$retain_files = get_option( 'updraft_retain' );
			$retain_db    = get_option( 'updraft_retain_db' );

			return array(
				'remote_services' => $services,
				'retain_files'    => is_numeric( $retain_files ) ? (int) $retain_files : null,
				'retain_db'       => is_numeric( $retain_db ) ? (int) $retain_db : null,
			);
		}

		/**
		 * Ruční záznam testu obnovy (P0 "Obnovitelnost zálohy" – GARRY sám
		 * žádnou obnovu neprovádí ani neověřuje, jen eviduje, že ji
		 * administrátor provedl a kdy – přesně jako "Zapsat test" v návrhu
		 * struktury). Bez tohohle záznamu zůstává obnovitelnost neověřená,
		 * ať je záloha sebečerstvější.
		 */
		const RESTORE_TEST_OPTION = 'garry_security_backup_restore_test';

		public static function record_restore_test( $user_id, $note = '' ) {
			update_option( self::RESTORE_TEST_OPTION, array(
				'recorded_at' => time(),
				'recorded_by' => (int) $user_id,
				'note'        => sanitize_text_field( $note ),
			), false );
		}

		public static function get_restore_test() {
			$stored = get_option( self::RESTORE_TEST_OPTION );
			return ( is_array( $stored ) && ! empty( $stored['recorded_at'] ) ) ? $stored : null;
		}

		/**
		 * "Přístup k obnově" (P2) – kontakt/umístění záloh/postup pro incident.
		 * Čistě informační pole, nikdy nesmí být viditelné rolím mimo
		 * manage_options (viz capability gating u volajícího v administraci) –
		 * proto tahle třída sama žádnou capabilitu neověřuje, spoléhá na
		 * volajícího stejně jako zbytek pluginu.
		 */
		const RECOVERY_ACCESS_OPTION = 'garry_security_recovery_access';

		public static function get_recovery_access() {
			$stored = get_option( self::RECOVERY_ACCESS_OPTION );
			$defaults = array( 'contact' => '', 'backup_location' => '', 'runbook_url' => '', 'verified_at' => null, 'verified_by' => null );
			return is_array( $stored ) ? array_merge( $defaults, $stored ) : $defaults;
		}

		public static function set_recovery_access( array $data, $user_id ) {
			update_option( self::RECOVERY_ACCESS_OPTION, array(
				'contact'         => sanitize_text_field( $data['contact'] ?? '' ),
				'backup_location' => sanitize_text_field( $data['backup_location'] ?? '' ),
				'runbook_url'     => esc_url_raw( $data['runbook_url'] ?? '' ),
				'verified_at'     => time(),
				'verified_by'     => (int) $user_id,
			), false );
		}
	}
}
