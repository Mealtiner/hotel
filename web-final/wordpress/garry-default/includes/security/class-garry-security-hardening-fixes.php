<?php
/**
 * GARRY Security – bezpečné jednokliknové opravy přes vlastní mu-plugin
 * (Fáze D, bod 6, revidováno podle zpětné vazby: uživatel chtěl tlačítko,
 * které opravu skutečně provede).
 *
 * Záměrně NEPÍŠE do wp-config.php ani .htaccess. Důvod: wp-config.php se
 * načítá při KAŽDÉM požadavku ještě před zbytkem WordPressu – chyba v
 * zápisu (špatný marker, konstanta definovaná podruhé, práva k zápisu)
 * může celý web shodit do White Screen of Death, opravitelného jen přes
 * FTP/SSH. Místo toho GARRY spravuje vlastní soubor
 * wp-content/mu-plugins/garry-security-hardening.php – mu-plugin se načte
 * automaticky bez aktivace, ale je to izolovaný, GARRY vlastněný soubor:
 * chyba v něm nikdy nesundá zbytek webu a soubor lze kdykoli bezpečně
 * smazat přes FTP bez zásahu do wp-config.php. Soubor se při každé změně
 * kompletně přegeneruje (ne upravuje), takže tu není žádná fragilní
 * hledání/vkládání do existujícího obsahu jako by hrozilo u wp-config.php.
 *
 * Omezení: WP_DEBUG samo o sobě (CORE-005) mu-pluginem spolehlivě
 * neopravíte – WordPress ho vyhodnocuje dřív, než se mu-pluginy načtou.
 * Proto tahle sada řeší jen CORE-006 (display_errors – ini_set() funguje
 * i odsud), CORE-007 (DISALLOW_FILE_EDIT) a WEB-002 (FORCE_SSL_ADMIN) –
 * obě konstanty se vyhodnocují až během admin bootstrapu, tedy po načtení
 * mu-pluginů.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Hardening_Fixes' ) ) {

	class Garry_Security_Hardening_Fixes {

		const OPTION_KEY  = 'garry_security_applied_fixes';
		const MU_FILE_NAME = 'garry-security-hardening.php';

		/**
		 * Katalog bezpečných oprav. `what` = co nastavení dělá, `why` = proč
		 * je to důležité, `mu_snippet` = přesný kód vložený do mu-pluginu.
		 */
		public static function catalog() {
			return array(
				'CORE-006' => array(
					'title'      => 'Vypnout zobrazování PHP chyb',
					'what'       => 'PHP nastavení display_errors řídí, jestli se chybové hlášky (varování, notice, fatal chyby) vypisují přímo do stránky, kterou vidí návštěvník. Oprava ho pro tento web nastaví na vypnuto pomocí ini_set() spuštěného co nejdřív při načtení WordPressu.',
					'why'        => 'Chybové výpisy běžně obsahují absolutní cesty k souborům na serveru, názvy použitých pluginů a jejich verze, a někdy i fragmenty SQL dotazů. Útočníkovi to usnadňuje mapování webu a hledání known-vulnerable verzí komponent. Produkční web by chyby měl logovat (do souboru), ne je zobrazovat veřejně.',
					'mu_snippet' => "@ini_set( 'display_errors', '0' );",
				),
				'CORE-007' => array(
					'title'      => 'Vypnout vestavěný editor pluginů a šablon',
					'what'       => 'WordPress má ve výchozím stavu v administraci (Pluginy → Editor souborů pluginů, Vzhled → Editor souborů šablony) vestavěný textový editor, kterým lze přímo v prohlížeči upravovat PHP soubory pluginů a šablon. Oprava tuto funkci vypne definováním konstanty DISALLOW_FILE_EDIT.',
					'why'        => 'Pokud útočník získá přístup k administrátorskému účtu (odcizené heslo, session, slabé 2FA), tenhle editor mu dá přímou cestu, jak spustit libovolný PHP kód na serveru – jen přepíše soubor pluginu a uloží. Vypnutí editoru tohle konkrétní zneužití zavře, i když samo o sobě nenahrazuje silná hesla a 2FA.',
					'mu_snippet' => "if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) define( 'DISALLOW_FILE_EDIT', true );",
				),
				'WEB-002' => array(
					'title'      => 'Vynutit HTTPS v administraci',
					'what'       => 'Nastavení vynutí, aby WordPress administrace (wp-admin) i přihlašovací formulář běžely výhradně přes HTTPS, a nešifrovaný přístup přesměruje. Oprava definuje konstantu FORCE_SSL_ADMIN.',
					'why'        => 'Bez vynuceného HTTPS se přihlašovací jméno, heslo i session cookie administrátora mohou přenášet nešifrovaně – na nezabezpečené Wi-Fi nebo kdekoli mezi klientem a serverem je pak lze odposlechnout a přihlásit se jako administrátor. Vyžaduje funkční platný SSL certifikát na doméně administrace.',
					'mu_snippet' => "if ( ! defined( 'FORCE_SSL_ADMIN' ) ) define( 'FORCE_SSL_ADMIN', true );",
				),
			);
		}

		public static function applied() {
			$stored = get_option( self::OPTION_KEY, array() );
			return is_array( $stored ) ? array_values( array_intersect( $stored, array_keys( self::catalog() ) ) ) : array();
		}

		public static function is_applied( $control_id ) {
			return in_array( $control_id, self::applied(), true );
		}

		private static function mu_plugin_path() {
			return trailingslashit( WPMU_PLUGIN_DIR ) . self::MU_FILE_NAME;
		}

		/**
		 * Zapne opravu pro daný control_id. Vrací true, nebo WP_Error s
		 * lidsky čitelným důvodem selhání (např. složka mu-plugins není
		 * zapisovatelná – to se může stát a GARRY to musí umět nahlásit,
		 * ne tiše předstírat úspěch).
		 */
		public static function apply( $control_id ) {
			if ( ! isset( self::catalog()[ $control_id ] ) ) {
				return new WP_Error( 'garry_unknown_fix', 'Neznámá oprava.' );
			}
			$applied = self::applied();
			if ( ! in_array( $control_id, $applied, true ) ) {
				$applied[] = $control_id;
			}
			return self::write_mu_plugin( $applied );
		}

		/** Vypne dříve aktivovanou opravu (odebere ji z mu-pluginu). */
		public static function revert( $control_id ) {
			$applied = array_values( array_diff( self::applied(), array( $control_id ) ) );
			return self::write_mu_plugin( $applied );
		}

		/**
		 * Kompletně přegeneruje mu-plugin soubor podle aktuálního seznamu
		 * zapnutých oprav (nikdy needituje existující obsah – vyhýbá se tak
		 * jakémukoli riziku spojenému s hledáním/vkládáním do cizího
		 * souboru, jak je popsáno v docblocku nad třídou).
		 */
		private static function write_mu_plugin( array $applied ) {
			update_option( self::OPTION_KEY, $applied, false );

			$path = self::mu_plugin_path();

			if ( empty( $applied ) ) {
				if ( file_exists( $path ) && is_writable( $path ) ) {
					@unlink( $path );
				}
				return true;
			}

			if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
				if ( ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
					return new WP_Error( 'garry_mu_dir_failed', 'Nepodařilo se vytvořit složku wp-content/mu-plugins/ – zkontrolujte oprávnění k zápisu.' );
				}
			}

			$catalog = self::catalog();
			$lines   = array(
				'<?php',
				'/**',
				' * Vygenerováno pluginem GARRY – Výchozí zabezpečení webu.',
				' * NEUPRAVUJTE ručně – při další změně v GARRY nastavení -> Zabezpečení',
				' * se tento soubor kompletně přepíše. Vypnutí konkrétní opravy tam',
				' * soubor buď přegeneruje bez ní, nebo (nezbývá-li nic) smaže.',
				' */',
				"if ( ! defined( 'ABSPATH' ) ) exit;",
				'',
			);
			foreach ( $applied as $id ) {
				if ( ! isset( $catalog[ $id ] ) ) {
					continue;
				}
				$lines[] = '// ' . $id . ': ' . $catalog[ $id ]['title'];
				$lines[] = $catalog[ $id ]['mu_snippet'];
				$lines[] = '';
			}

			$result = @file_put_contents( $path, implode( "\n", $lines ), LOCK_EX );
			if ( false === $result ) {
				return new WP_Error( 'garry_mu_write_failed', 'Zápis souboru wp-content/mu-plugins/' . self::MU_FILE_NAME . ' selhal – zkontrolujte oprávnění k zápisu.' );
			}
			return true;
		}

		/** Pro uninstall.php – smaže mu-plugin soubor i jeho option beze stopy. */
		public static function uninstall_cleanup() {
			$path = self::mu_plugin_path();
			if ( file_exists( $path ) ) {
				@unlink( $path );
			}
			delete_option( self::OPTION_KEY );
		}
	}
}
