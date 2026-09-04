<?php
/**
 * Plugin Name:       GARRY – Výchozí zabezpečení webu
 * Plugin URI:        https://www.garry.cz
 * Description:       Základní provozní a bezpečnostní modul GARRY Promotion pro WordPress. Nabízí lokální bezpečnostní audit, přehled aktualizací a kompatibility pluginů, doporučení ochranných nástrojů a bezpečné návazné akce; nevyžaduje žádný další GARRY plugin. Původně vytvořen pro standardní instalace webů GARRY Promotion.
 * Version:           2.3.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-default
 * Update URI:        https://www.garry.cz
 * Requires at least: 7.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================================
 * Konstanty
 * ============================================================================ */
define( 'GARRY_DEFAULT_VERSION', '2.3.0' );
define( 'GARRY_DEFAULT_FILE', __FILE__ );
define( 'GARRY_DEFAULT_DIR', plugin_dir_path( __FILE__ ) );
define( 'GARRY_DEFAULT_URL', plugin_dir_url( __FILE__ ) );
define( 'GARRY_DEFAULT_OPTION', 'garry_default_settings' );

/* Minimální požadované verze – zdroj pravdy sdílený s hlavičkou pluginu výše. */
define( 'GARRY_DEFAULT_MIN_WP', '7.0' );
define( 'GARRY_DEFAULT_MIN_PHP', '7.4' );

/* Vlastní capabilities pro GARRY Security (viz docs/GARRY-WP-SECURITY-MASTER-SPEC.md,
 * sekce 14, a auditní SEC-SELF-001: "Zachovat samostatné" pět capabilities).
 * apply_safe od 1.7.0 skutečně řídí bezpečné jednokliknové opravy (mu-plugin,
 * viz class-garry-security-hardening-fixes.php); apply_risky zatím nic
 * nekontroluje – žádná taková akce ještě neexistuje, definovaná dopředu pro
 * budoucí rizikovější remediation, který by vyžadoval samostatné potvrzení. */
define( 'GARRY_SECURITY_CAP_VIEW', 'garry_security_view' );
define( 'GARRY_SECURITY_CAP_RUN', 'garry_security_run_scan' );
define( 'GARRY_SECURITY_CAP_APPLY_SAFE', 'garry_security_apply_safe' );
define( 'GARRY_SECURITY_CAP_APPLY_RISKY', 'garry_security_apply_risky' );
define( 'GARRY_SECURITY_CAP_MANAGE_PROVIDERS', 'garry_security_manage_providers' );

/* ============================================================================
 * Kontrola minimálních požadavků (WordPress / PHP)
 * ============================================================================
 * WordPress od verze 5.5 sám znemožní aktivaci pluginu, jehož hlavičky
 * „Requires at least" / „Requires PHP" prostředí nesplňují. Tato funkce je
 * záložní pojistka pro případy mimo standardní UI (WP-CLI, síťová aktivace,
 * ruční nahrání souborů) – viz garry_default_activate() níže.
 * ============================================================================ */
function garry_default_meets_requirements() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, GARRY_DEFAULT_MIN_PHP, '<' ) ) {
		return false;
	}

	if ( ! empty( $wp_version ) && version_compare( $wp_version, GARRY_DEFAULT_MIN_WP, '<' ) ) {
		return false;
	}

	return true;
}

/* ============================================================================
 * GARRY Security
 * ============================================================================ */
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-db.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-providers.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-ownership.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-version-catalog.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-ecosystem.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-hardening.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-http-health.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-access-hardening.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-exposure.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-exposed-files.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-plugin-hygiene.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-environment.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-robots-llms.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-privacy.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-catalog.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/security-collectors.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-scanner.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-cron.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-backup-gate.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-hardening-fixes.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-admin.php';
require_once GARRY_DEFAULT_DIR . 'includes/security/class-garry-security-actions.php';

/* ============================================================================
 * GARRY Embedded Framework 2.3 (viz docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md)
 * ============================================================================
 * Vlastní PHP namespace nahrazuje starší sdílenou třídu Garry_Promotion_Registry
 * a s ní i dřívější version-handshake pojistku (funkce
 * garry_default_framework_handshake_ok() z verze 1.3.0) – při namespace
 * izolaci už nemůže nastat kolize „čí kopie vyhrála", takže pojistka není
 * potřeba. GARRY Default je jen jedním z rovnocenných účastníků voleb o
 * sdílené claimy (root_menu, group_overview, …) – NENÍ povýšen na povinného
 * poskytovatele (spec, sekce 3). Stránka Zabezpečení běží jako vlastní
 * záložka vedle Přehledu/Info/Logu.
 */
require_once GARRY_DEFAULT_DIR . 'includes/framework-v23/bootstrap.php';
\Garry\Embedded\GarryDefault\V23\bootstrap( __FILE__, array( 'Garry_Security_Admin', 'render_page' ), 'Zabezpečení' );

Garry_Security_Admin::register();

Garry_Security_Cron::register_hooks();
Garry_Security_Actions::register_hooks();

/* ============================================================================
 * Aktivace / deaktivace / úklid
 * ============================================================================ */

/**
 * Nainstaluje/aktualizuje DB schéma GARRY Security, přidá roli administrator
 * všech pět vlastních capabilities (SEC-SELF-001) a naplánuje denní scan.
 * Idempotentní – lze bezpečně volat opakovaně (při aktivaci i při každé
 * budoucí aktualizaci souborů bez explicitní reaktivace, viz
 * garry_default_run_upgrade_routine()).
 */
function garry_default_provision_security() {
	Garry_Security_DB::maybe_upgrade();

	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( array(
			GARRY_SECURITY_CAP_VIEW,
			GARRY_SECURITY_CAP_RUN,
			GARRY_SECURITY_CAP_APPLY_SAFE,
			GARRY_SECURITY_CAP_APPLY_RISKY,
			GARRY_SECURITY_CAP_MANAGE_PROVIDERS,
		) as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	Garry_Security_Cron::schedule();
}

/**
 * Bezpečná aktivace: ověří minimální požadavky, a pokud nejsou splněny,
 * aktivaci odmítne a plugin se hned zase vypne – nic se neuloží ani nezapne.
 * Při splnění požadavků nainstaluje GARRY Security a uloží verzi + čas
 * instalace (autoload vypnutý, aby zbytečně nezatěžovala každý request).
 */
function garry_default_activate() {
	if ( ! garry_default_meets_requirements() ) {
		deactivate_plugins( plugin_basename( GARRY_DEFAULT_FILE ) );
		wp_die(
			esc_html( sprintf(
				/* translators: 1: minimální verze WordPressu, 2: minimální verze PHP */
				__( 'Plugin „GARRY – Default" nelze aktivovat: vyžaduje WordPress %1$s+ a PHP %2$s+. Aktualizujte prosím prostředí a zkuste aktivaci znovu.', 'garry-default' ),
				GARRY_DEFAULT_MIN_WP,
				GARRY_DEFAULT_MIN_PHP
			) ),
			esc_html__( 'Chyba aktivace pluginu', 'garry-default' ),
			array( 'back_link' => true )
		);
	}

	$existing      = get_option( GARRY_DEFAULT_OPTION );
	$installed_at  = ( is_array( $existing ) && isset( $existing['installed_at'] ) ) ? $existing['installed_at'] : time();

	garry_default_provision_security();

	update_option( GARRY_DEFAULT_OPTION, array(
		'version'      => GARRY_DEFAULT_VERSION,
		'installed_at' => $installed_at,
	), '', false );
}
register_activation_hook( __FILE__, 'garry_default_activate' );

/**
 * Bezpečnostní pojistka pro aktualizace bez explicitní reaktivace (soubory
 * přepsané deployem/FTP): pokud je uložená verze nižší než aktuální, znovu
 * proběhne provisioning (DB schéma, capabilities, cron) a verze se zapíše.
 */
function garry_default_run_upgrade_routine() {
	$stored         = get_option( GARRY_DEFAULT_OPTION );
	$stored_version = ( is_array( $stored ) && isset( $stored['version'] ) ) ? $stored['version'] : '0';

	if ( version_compare( $stored_version, GARRY_DEFAULT_VERSION, '>=' ) ) {
		return;
	}

	garry_default_provision_security();

	update_option( GARRY_DEFAULT_OPTION, array(
		'version'      => GARRY_DEFAULT_VERSION,
		'installed_at' => ( is_array( $stored ) && isset( $stored['installed_at'] ) ) ? $stored['installed_at'] : time(),
	), '', false );
}
add_action( 'admin_init', 'garry_default_run_upgrade_routine' );

/**
 * Zruší naplánovaný denní scan ve WP-Cron.
 */
function garry_default_clear_scheduled_events() {
	Garry_Security_Cron::unschedule();
}

/**
 * Deaktivace je nedestruktivní a vratná – žádná uložená data (options ani
 * DB tabulky GARRY Security) se nemažou, jen se zruší naplánovaný scan.
 * Trvalý úklid probíhá až v uninstall.php při odinstalaci.
 */
function garry_default_deactivate() {
	garry_default_clear_scheduled_events();
}
register_deactivation_hook( __FILE__, 'garry_default_deactivate' );
