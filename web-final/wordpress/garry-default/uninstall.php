<?php
/**
 * Úklid při odinstalaci pluginu GARRY – Default.
 *
 * WordPress volá tento soubor pouze při skutečné odinstalaci (tlačítko
 * „Odstranit" po deaktivaci), a to jen pokud je definována konstanta
 * WP_UNINSTALL_PLUGIN – přímé spuštění souboru je tak vyloučené.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

require_once __DIR__ . '/includes/security/class-garry-security-db.php';
require_once __DIR__ . '/includes/security/class-garry-security-hardening-fixes.php';

function garry_default_uninstall_cleanup() {
	delete_option( 'garry_default_settings' );
	delete_transient( 'garry_default_cache' );

	// GARRY Security: vlastní tabulky, cron, lock, backup request a capabilities.
	Garry_Security_DB::uninstall();

	wp_clear_scheduled_hook( 'garry_security_daily_scan' );
	delete_transient( 'garry_security_scan_lock' );
	delete_option( 'garry_security_backup_request' );

	// Bezpečné opravy (Fáze D): smaže vlastní mu-plugin soubor i jeho option
	// – wp-config.php a .htaccess se nikdy neupravují, takže tu není co vracet.
	Garry_Security_Hardening_Fixes::uninstall_cleanup();

	// robots.txt / llms.txt nastavení – jen options, žádný fyzický soubor
	// (llms.txt se vykresluje virtuálně, nikdy se nezapisuje na disk).
	delete_option( 'garry_security_robots_settings' );
	delete_option( 'garry_security_llms_settings' );

	// Verze 2.1: výjimky "Není potřeba pro tento web" u kapacitní matice
	// a usermeta se sledováním posledního přihlášení administrátorů.
	delete_option( 'garry_security_capability_exceptions' );
	if ( function_exists( 'delete_metadata' ) ) {
		delete_metadata( 'user', 0, 'garry_security_last_login', '', true );
	}

	// Verze 2.2: ruční záznam testu obnovy a kontakt pro obnovu po incidentu.
	delete_option( 'garry_security_backup_restore_test' );
	delete_option( 'garry_security_recovery_access' );

	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( array(
			'garry_security_view',
			'garry_security_run_scan',
			'garry_security_apply_safe',
			'garry_security_apply_risky',
			'garry_security_manage_providers',
		) as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		garry_default_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	garry_default_uninstall_cleanup();
}
