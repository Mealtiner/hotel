<?php
/**
 * GARRY Security – napojení na nativní WordPress GDPR/Privacy API.
 *
 * Odpověď na otázku „jak propojit s compliance pluginy": nebuduje se
 * vlastní integrace pro každý konkrétní compliance plugin (Complianz,
 * WP GDPR Compliance…) – místo toho se GARRY zapojí do WordPressem
 * NATIVNĚ dodaných rozhraní pro export/výmaz osobních údajů
 * (Nástroje → Exportovat osobní údaje / Vymazat osobní údaje):
 *
 *  - wp_add_privacy_policy_content() – navrhne text do stránky Zásady
 *    ochrany osobních údajů,
 *  - filtr wp_privacy_personal_data_exporters – zaregistruje GARRY jako
 *    zdroj dat pro "právo na přístup",
 *  - filtr wp_privacy_personal_data_erasers – zaregistruje GARRY jako
 *    zdroj pro "právo na výmaz".
 *
 * Tahle API jsou jádrová funkce WordPressu od verze 4.9.6 a právě přes
 * ně čtou/vyvolávají requesty i compliance pluginy – ne přes vlastní
 * proprietární rozhraní konkrétního pluginu, které by GARRY musel znát
 * a udržovat pro každého zvlášť (a nemá jak ho ověřit proti živé
 * instalaci, stejná zásada jako jinde v tomhle souboru). Zapojením do
 * jádrového API GARRY funguje se všemi compliance pluginy, které tyhle
 * standardní háčky respektují, bez jakékoli vlastní znalosti o nich.
 *
 * Jediný skutečný zdroj osobních údajů v GARRY Security je pole
 * `evidence` u nálezů USR-003/USR-004 (číselná ID uživatelských účtů u
 * detekovaného rozdílu API/DB nebo neočekávané capability) – přesně
 * podle deklarace v garry-plugin-manifest.json.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function garry_security_privacy_policy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}
	$content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Následující text popisuje zpracování osobních údajů pluginem GARRY – Výchozí zabezpečení webu. Doplňte/upravte podle skutečného nastavení webu.', 'garry-default' ) . '</p>'
		. '<p>' . esc_html__( 'GARRY Security provádí pravidelný read-only bezpečnostní audit tohoto webu (kontrola aktualizací, HTTPS, administrátorských účtů a dalších nastavení). U kontrol USR-003 (shoda seznamu administrátorů mezi WordPress API a přímou databází) a USR-004 (neočekávaná kritická oprávnění) může evidence nálezu obsahovat číselné ID dotčeného uživatelského účtu WordPress.', 'garry-default' ) . '</p>'
		. '<p>' . esc_html__( 'Tato evidence neobsahuje jména, e-maily, hesla ani jiné osobní údaje mimo číselné ID účtu a slouží výhradně k detekci neoprávněných/skrytých administrátorských účtů. Odráží aktuální stav a při každém dalším skenu se přepočítá.', 'garry-default' ) . '</p>'
		. '<p>' . esc_html__( 'Retence: nálezy se uchovávají, dokud je odpovídající bezpečnostní stav aktuální; historie událostí 365 dní.', 'garry-default' ) . '</p>';

	wp_add_privacy_policy_content(
		'GARRY – Výchozí zabezpečení webu',
		wp_kses_post( wpautop( $content, false ) )
	);
}
add_action( 'admin_init', 'garry_security_privacy_policy_content' );

/** Rekurzivně zjistí, jestli se dané ID objevuje v dekódované evidence (jako klíč i jako hodnota). */
function garry_security_evidence_contains_user_id( $data, $user_id ) {
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			if ( (string) $key === (string) $user_id ) {
				return true;
			}
			if ( garry_security_evidence_contains_user_id( $value, $user_id ) ) {
				return true;
			}
		}
		return false;
	}
	return (string) $data === (string) $user_id;
}

/** Rekurzivně nahradí dané ID (jako klíč i hodnotu) placeholderem '[erased]'. Vrací true, pokud něco změnila. */
function garry_security_evidence_redact_user_id( &$data, $user_id ) {
	$changed = false;
	if ( ! is_array( $data ) ) {
		return false;
	}
	$result = array();
	foreach ( $data as $key => $value ) {
		$new_key = ( (string) $key === (string) $user_id ) ? '[erased]' : $key;
		if ( (string) $key === (string) $user_id ) {
			$changed = true;
		}
		if ( is_array( $value ) ) {
			$changed = garry_security_evidence_redact_user_id( $value, $user_id ) || $changed;
			$result[ $new_key ] = $value;
		} elseif ( (string) $value === (string) $user_id ) {
			$result[ $new_key ] = '[erased]';
			$changed             = true;
		} else {
			$result[ $new_key ] = $value;
		}
	}
	$data = $result;
	return $changed;
}

/**
 * Exportér pro Nástroje → Exportovat osobní údaje. Read-only – jen
 * nahlásí, ve kterých kontrolách se ID dotčeného uživatele objevuje.
 */
function garry_security_privacy_exporter( $email_address, $page = 1 ) {
	$export_items = array();
	$user         = get_user_by( 'email', $email_address );

	if ( $user ) {
		global $wpdb;
		$table = Garry_Security_DB::table( 'findings' );
		$rows  = $wpdb->get_results( "SELECT control_id, evidence, message, last_seen FROM {$table} WHERE evidence IS NOT NULL" );

		foreach ( $rows as $row ) {
			$decoded = json_decode( $row->evidence, true );
			if ( ! is_array( $decoded ) || ! garry_security_evidence_contains_user_id( $decoded, $user->ID ) ) {
				continue;
			}
			$export_items[] = array(
				'group_id'    => 'garry-security-findings',
				'group_label' => __( 'GARRY Security – nálezy bezpečnostního auditu', 'garry-default' ),
				'item_id'     => 'garry-security-finding-' . $row->control_id,
				'data'        => array(
					array( 'name' => __( 'Kontrola', 'garry-default' ), 'value' => $row->control_id ),
					array( 'name' => __( 'Zpráva', 'garry-default' ), 'value' => $row->message ),
					array( 'name' => __( 'Naposledy viděno', 'garry-default' ), 'value' => $row->last_seen ),
				),
			);
		}
	}

	return array( 'data' => $export_items, 'done' => true );
}

/**
 * Mazač pro Nástroje → Vymazat osobní údaje. Záměrně NEtvrdí "smazáno" v
 * plném smyslu: nálezy USR-003/USR-004 odrážejí AKTUÁLNÍ živý stav
 * (shoda administrátorů, capabilities) a při každém dalším scanu se
 * přepočítají – pokud podmínka, která ID do evidence dostala, na webu
 * pořád platí, po dalším scanu se tam ID objeví znovu bez ohledu na
 * jednorázový výmaz teď. Proto se evidence sice redaguje (nahrazení
 * '[erased]'), ale vrací se items_retained => true s vysvětlením, ne
 * fabrikované "trvale vymazáno".
 */
function garry_security_privacy_eraser( $email_address, $page = 1 ) {
	$messages = array();
	$user     = get_user_by( 'email', $email_address );
	$redacted_any = false;

	if ( $user ) {
		global $wpdb;
		$table = Garry_Security_DB::table( 'findings' );
		$rows  = $wpdb->get_results( "SELECT id, evidence FROM {$table} WHERE evidence IS NOT NULL" );

		foreach ( $rows as $row ) {
			$decoded = json_decode( $row->evidence, true );
			if ( ! is_array( $decoded ) ) {
				continue;
			}
			if ( garry_security_evidence_redact_user_id( $decoded, $user->ID ) ) {
				$wpdb->update(
					$table,
					array( 'evidence' => wp_json_encode( $decoded ) ),
					array( 'id' => $row->id ),
					array( '%s' ),
					array( '%d' )
				);
				$redacted_any = true;
			}
		}

		if ( $redacted_any ) {
			$messages[] = __( 'GARRY Security: ID účtu bylo v aktuální evidenci nálezů nahrazeno. Nálezy USR-003/USR-004 ale odrážejí živý stav webu (shoda administrátorů, capabilities) a při dalším plánovaném scanu se přepočítají – pokud podmínka na webu stále platí (např. účet stále existuje s danou capabilitou), ID se v evidenci znovu objeví. Trvalé odstranění vyžaduje vyřešit podkladovou příčinu nálezu, ne jen smazat evidenci.', 'garry-default' );
		}
	}

	return array(
		'items_removed'  => false,
		'items_retained' => $redacted_any,
		'messages'       => $messages,
		'done'           => true,
	);
}

add_filter( 'wp_privacy_personal_data_exporters', function ( $exporters ) {
	$exporters['garry-default'] = array(
		'exporter_friendly_name' => __( 'GARRY – Výchozí zabezpečení webu', 'garry-default' ),
		'callback'                => 'garry_security_privacy_exporter',
	);
	return $exporters;
} );

add_filter( 'wp_privacy_personal_data_erasers', function ( $erasers ) {
	$erasers['garry-default'] = array(
		'eraser_friendly_name' => __( 'GARRY – Výchozí zabezpečení webu', 'garry-default' ),
		'callback'              => 'garry_security_privacy_eraser',
	);
	return $erasers;
} );

/**
 * Ryze informativní detekce známého compliance/consent pluginu (Complianz
 * – GDPR/CCPA Cookie Consent, nejběžnější volba v českém prostředí).
 * Nevydává právní verdikt, jen říká, jestli je nějaký takový plugin na
 * webu aktivní – GARRY nemá jak posoudit, jestli je web skutečně v
 * souladu s GDPR, to je vždy na administrátorovi/DPO.
 */
function garry_security_check_compliance_plugin() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$known = array(
		'complianz-gdpr/complianz-gpdr.php'       => 'Complianz – GDPR/CCPA Cookie Consent',
		'complianz-gdpr-premium/complianz-gdpr-premium.php' => 'Complianz Premium',
		'cookiebot/cookiebot.php'                  => 'Cookiebot',
		'cookie-law-info/cookie-law-info.php'      => 'CookieYes (Cookie Law Info)',
	);

	foreach ( $known as $file => $label ) {
		if ( is_plugin_active( $file ) ) {
			return array(
				'state'    => 'delegated',
				'severity' => 'info',
				'message'  => sprintf( 'Nalezen aktivní compliance/consent plugin: %s. GARRY je napojen na nativní WordPress Privacy API (export/výmaz osobních údajů), které tyhle nástroje typicky respektují.', $label ),
				'evidence' => array( 'plugin' => $label ),
			);
		}
	}

	return array(
		'state'    => 'unknown',
		'severity' => 'info',
		'message'  => 'Nebyl detekován žádný ze známých compliance/consent pluginů. GARRY je napojen na nativní WordPress Privacy API bez ohledu na to – soulad s GDPR/CCPA jako celek je vždy na administrátorovi webu.',
		'evidence' => array(),
	);
}
