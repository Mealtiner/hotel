<?php
/**
 * GARRY Security – katalog kontrol (metadata pro zobrazení v administraci).
 *
 * Podle GARRY-WP-SECURITY-FUNCTION-MAP.md. Statická část pokrývá Fázi 1
 * (read-only kontroly). Fáze 2 přidává kontroly PROV- a OWN- dynamicky
 * ze security-providers.php a security-ownership.php, aby se popisky
 * nemusely ručně duplikovat na dvou místech.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function garry_security_catalog() {
	$static = array(
		'ENV-001'   => array( 'title' => 'Detekce prostředí (production/staging/development)', 'category' => 'Bootstrap' ),
		'ENV-003'   => array( 'title' => 'Single Site / Multisite', 'category' => 'Bootstrap' ),
		'ENV-004'   => array( 'title' => 'Podporovaná verze PHP', 'category' => 'Bootstrap' ),
		'ENV-005'   => array( 'title' => 'Verze a charset databáze', 'category' => 'Bootstrap' ),
		'ENV-006'   => array( 'title' => 'PHP limity (memory, upload, execution time)', 'category' => 'Bootstrap' ),
		'ENV-007'   => array( 'title' => 'Obsah .htaccess (rizikové direktivy a zpevnění)', 'category' => 'Bootstrap' ),
		'ENV-008'   => array( 'title' => 'DNS záznamy a Cloudflare proxy', 'category' => 'Bootstrap' ),
		'ENV-009'   => array( 'title' => 'E-mailová důvěryhodnost (SPF/DMARC)', 'category' => 'Bootstrap' ),
		'CORE-001'  => array( 'title' => 'Aktualizace jádra WordPress', 'category' => 'Jádro a aktualizace' ),
		'CORE-005'  => array( 'title' => 'WP_DEBUG na produkci', 'category' => 'Jádro a aktualizace' ),
		'CORE-006'  => array( 'title' => 'PHP display_errors', 'category' => 'Jádro a aktualizace' ),
		'CORE-007'  => array( 'title' => 'Vestavěný editor pluginů a šablon', 'category' => 'Jádro a aktualizace' ),
		'CORE-008'  => array( 'title' => 'Politika automatických aktualizací jádra', 'category' => 'Jádro a aktualizace' ),
		'UPD-001'   => array( 'title' => 'Aktualizace pluginů', 'category' => 'Jádro a aktualizace' ),
		'UPD-002'   => array( 'title' => 'Aktualizace šablon', 'category' => 'Jádro a aktualizace' ),
		'USR-001'   => array( 'title' => 'Počet administrátorů (WP API)', 'category' => 'Uživatelé a oprávnění' ),
		'USR-002'   => array( 'title' => 'Počet administrátorů (přímá DB kontrola)', 'category' => 'Uživatelé a oprávnění' ),
		'USR-003'   => array( 'title' => 'Shoda API a DB seznamu administrátorů', 'category' => 'Uživatelé a oprávnění' ),
		'USR-004'   => array( 'title' => 'Kritické capabilities bez odpovídající DB role', 'category' => 'Uživatelé a oprávnění' ),
		'WEB-001'   => array( 'title' => 'Frontend HTTPS', 'category' => 'HTTPS a povrch' ),
		'WEB-002'   => array( 'title' => 'Administrace HTTPS', 'category' => 'HTTPS a povrch' ),
		'SCAN-CRON' => array( 'title' => 'Naplánovaný denní scan (WP-Cron)', 'category' => 'Scheduler' ),
		'PROV-COMPLIANCE-PLUGIN' => array( 'title' => 'Detekce compliance/consent pluginu (GDPR/CCPA)', 'category' => 'Provider registry' ),
	);

	return array_merge(
		$static,
		garry_security_provider_catalog_entries(),
		garry_security_ownership_catalog_entries(),
		garry_security_ecosystem_catalog_entries(),
		garry_security_hardening_catalog_entries(),
		garry_security_http_health_catalog_entries(),
		garry_security_access_hardening_catalog_entries(),
		garry_security_exposure_catalog_entries(),
		garry_security_exposed_files_catalog_entries(),
		garry_security_plugin_hygiene_catalog_entries()
	);
}

/**
 * Sjednocený stavový slovník napříč celou administrací (návrh struktury
 * a bezpečnostních přehledů, sekce "Stavový model"): technický stav +
 * závažnost se převádí na jeden ze šesti lidsky čitelných štítků, aby
 * administrátor nemusel znát interní anglická jména stavů. `delegated`
 * se řeší zvlášť u volajícího (potřebuje evidence.owner pro jméno
 * poskytovatele), tady vrací jen neutrální "Ověřeno".
 */
function garry_security_state_label( $state, $severity = 'info' ) {
	switch ( $state ) {
		case 'critical':
			return 'Kritické';
		case 'conflict':
			return 'Konflikt';
		case 'warning':
			return in_array( $severity, array( 'high', 'critical' ), true ) ? 'Vyžaduje akci' : 'Zkontrolovat';
		case 'unknown':
			return 'Zkontrolovat';
		case 'pass':
			return 'V pořádku';
		case 'not_applicable':
			return 'Neplatí';
		case 'delegated':
			return 'Ověřeno';
		default:
			return $state;
	}
}

/**
 * Lidsky čitelný název prostředí pro dashboard kartu „GARRY".
 */
function garry_security_detect_environment_label() {
	if ( ! function_exists( 'wp_get_environment_type' ) ) {
		return 'Neznámé';
	}

	$map = array(
		'production'  => 'Produkce',
		'staging'     => 'Staging',
		'development' => 'Vývoj',
		'local'       => 'Lokální',
	);

	$type = wp_get_environment_type();

	return isset( $map[ $type ] ) ? $map[ $type ] : $type;
}
