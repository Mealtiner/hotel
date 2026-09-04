<?php
/**
 * GARRY Security – databázové schéma (scans / findings / events).
 *
 * Podle GARRY-WP-SECURITY-FUNCTION-MAP.md, sekce 5 „Datový model".
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_DB' ) ) {

	class Garry_Security_DB {

		const DB_VERSION  = 1;
		const OPTION_KEY  = 'garry_security_db_version';

		/**
		 * Vrátí plný (prefixovaný) název tabulky. Prefix se nikdy nehardcoduje.
		 */
		public static function table( $name ) {
			global $wpdb;
			return $wpdb->prefix . 'garry_security_' . $name;
		}

		/**
		 * Vytvoří/aktualizuje schéma přes dbDelta (idempotentní, bezpečné pro opakované volání).
		 */
		public static function install() {
			global $wpdb;

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$charset_collate = $wpdb->get_charset_collate();
			$scans_table     = self::table( 'scans' );
			$findings_table  = self::table( 'findings' );
			$events_table    = self::table( 'events' );

			$sql_scans = "CREATE TABLE {$scans_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				scan_uuid VARCHAR(36) NOT NULL,
				scan_type VARCHAR(32) NOT NULL DEFAULT 'full',
				trigger_source VARCHAR(32) NOT NULL DEFAULT 'cron',
				status VARCHAR(20) NOT NULL DEFAULT 'queued',
				started_at DATETIME NOT NULL,
				finished_at DATETIME NULL,
				heartbeat_at DATETIME NULL,
				plugin_version VARCHAR(20) NOT NULL DEFAULT '',
				schema_version SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				pass_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				warning_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				critical_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				unknown_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				conflict_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				error_message TEXT NULL,
				PRIMARY KEY  (id),
				KEY scan_uuid (scan_uuid),
				KEY status (status)
			) {$charset_collate};";

			$sql_findings = "CREATE TABLE {$findings_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				control_id VARCHAR(32) NOT NULL,
				scope VARCHAR(64) NOT NULL DEFAULT '',
				severity VARCHAR(20) NOT NULL DEFAULT 'info',
				state VARCHAR(20) NOT NULL DEFAULT 'unknown',
				owner VARCHAR(32) NOT NULL DEFAULT 'garry',
				provider VARCHAR(64) NULL,
				evidence_hash CHAR(64) NULL,
				evidence LONGTEXT NULL,
				message TEXT NULL,
				first_seen DATETIME NOT NULL,
				last_seen DATETIME NOT NULL,
				changed_at DATETIME NOT NULL,
				resolved_at DATETIME NULL,
				suppressed_until DATETIME NULL,
				suppressed_reason TEXT NULL,
				suppressed_by BIGINT UNSIGNED NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY control_scope (control_id, scope),
				KEY state (state),
				KEY severity (severity)
			) {$charset_collate};";

			$sql_events = "CREATE TABLE {$events_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_time DATETIME NOT NULL,
				event_type VARCHAR(32) NOT NULL,
				control_id VARCHAR(32) NOT NULL,
				old_state VARCHAR(20) NULL,
				new_state VARCHAR(20) NULL,
				actor VARCHAR(64) NOT NULL DEFAULT 'system',
				source VARCHAR(32) NOT NULL DEFAULT 'scan',
				correlation_id VARCHAR(36) NULL,
				context LONGTEXT NULL,
				PRIMARY KEY  (id),
				KEY control_id (control_id),
				KEY event_time (event_time)
			) {$charset_collate};";

			dbDelta( $sql_scans );
			dbDelta( $sql_findings );
			dbDelta( $sql_events );

			update_option( self::OPTION_KEY, self::DB_VERSION, false );
		}

		/**
		 * Nainstaluje/aktualizuje schéma jen pokud je uložená verze nižší
		 * než aktuální – bezpečné volat na každém requestu v administraci.
		 */
		public static function maybe_upgrade() {
			$installed = (int) get_option( self::OPTION_KEY, 0 );
			if ( $installed < self::DB_VERSION ) {
				self::install();
			}
		}

		/**
		 * Trvalé odstranění vlastních tabulek při odinstalaci pluginu.
		 * Nikdy se nemažou data cizích pluginů/providerů.
		 */
		public static function uninstall() {
			global $wpdb;
			foreach ( array( 'scans', 'findings', 'events' ) as $name ) {
				$table = self::table( $name );
				$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // Název je interní, nezávislý na uživatelském vstupu.
			}
			delete_option( self::OPTION_KEY );
		}
	}
}
