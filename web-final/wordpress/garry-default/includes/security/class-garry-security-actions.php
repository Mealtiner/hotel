<?php
/**
 * GARRY Security – admin-post mutace pro provider workflow (instalace/
 * aktivace doporučených pluginů, vyžádání zálohy). Každá akce vyžaduje
 * jak standardní WP capability (`install_plugins`/`activate_plugins`),
 * tak vlastní `garry_security_manage_providers` (SEC-SELF-001) – druhá
 * dovoluje provoznímu vlastníkovi zúžit okruh adminů, kteří smí přes
 * GARRY spravovat providery, nezávisle na tom, kdo má obecné WP capability.
 * Plus nonce, allowlist na straně security-providers.php a viditelný
 * redirect s výsledkem. Instalace a aktivace jsou záměrně dva oddělené
 * kroky s vlastním potvrzením (viz GARRY-WP-SECURITY-BEHAVIOR-SPEC.md
 * sekce 11.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Actions' ) ) {

	class Garry_Security_Actions {

		public static function register_hooks() {
			add_action( 'admin_post_garry_security_install_plugin', array( __CLASS__, 'handle_install' ) );
			add_action( 'admin_post_garry_security_activate_plugin', array( __CLASS__, 'handle_activate' ) );
			add_action( 'admin_post_garry_security_trigger_backup', array( __CLASS__, 'handle_trigger_backup' ) );
			add_action( 'admin_post_garry_security_apply_fix', array( __CLASS__, 'handle_apply_fix' ) );
			add_action( 'admin_post_garry_security_revert_fix', array( __CLASS__, 'handle_revert_fix' ) );
			add_action( 'admin_post_garry_security_download_htaccess', 'garry_security_handle_download_htaccess' );
			add_action( 'admin_post_garry_security_save_robots_llms', 'garry_security_handle_save_robots_llms' );
			add_action( 'admin_post_garry_security_set_capability_exception', array( __CLASS__, 'handle_set_capability_exception' ) );
			add_action( 'admin_post_garry_security_clear_capability_exception', array( __CLASS__, 'handle_clear_capability_exception' ) );
			add_action( 'admin_post_garry_security_record_restore_test', array( __CLASS__, 'handle_record_restore_test' ) );
			add_action( 'admin_post_garry_security_save_recovery_access', array( __CLASS__, 'handle_save_recovery_access' ) );
		}

		private static function redirect_back( array $extra_args ) {
			wp_safe_redirect( add_query_arg(
				array_merge( array( 'page' => Garry_Security_Admin::PAGE_SLUG ), $extra_args ),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		public static function handle_install() {
			if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_install_plugin' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
			$result = garry_security_install_plugin_from_wp_org( $slug );

			if ( is_wp_error( $result ) ) {
				self::redirect_back( array( 'garry_error' => rawurlencode( $result->get_error_message() ) ) );
			}

			self::redirect_back( array( 'installed' => $slug ) );
		}

		public static function handle_activate() {
			if ( ! current_user_can( 'activate_plugins' ) || ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_activate_plugin' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$slug   = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
			$result = garry_security_activate_provider( $slug );

			if ( is_wp_error( $result ) ) {
				self::redirect_back( array( 'garry_error' => rawurlencode( $result->get_error_message() ) ) );
			}

			self::redirect_back( array( 'activated' => $slug ) );
		}

		public static function handle_trigger_backup() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_trigger_backup' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$result = Garry_Security_Backup_Gate::trigger( get_current_user_id() );

			if ( is_wp_error( $result ) ) {
				self::redirect_back( array( 'garry_error' => rawurlencode( $result->get_error_message() ) ) );
			}

			self::redirect_back( array( 'backup_requested' => 1 ) );
		}

		/**
		 * Zapne bezpečnou opravu (mu-plugin, viz security-hardening-fixes.php)
		 * a rovnou spustí nový scan, aby dashboard hned ukázal skutečný,
		 * aktuální stav – přesně to uživatel žádal jako "ověření, kontrola
		 * stavu, že to tam je".
		 */
		public static function handle_apply_fix() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_APPLY_SAFE ) || ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_apply_fix' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$control_id = isset( $_POST['control_id'] ) ? sanitize_key( wp_unslash( $_POST['control_id'] ) ) : '';
			$control_id = strtoupper( str_replace( '_', '-', $control_id ) );
			$result     = Garry_Security_Hardening_Fixes::apply( $control_id );

			if ( is_wp_error( $result ) ) {
				self::redirect_back( array( 'garry_error' => rawurlencode( $result->get_error_message() ) ) );
			}

			Garry_Security_Scanner::run_scan( 'manual' );
			self::redirect_back( array( 'fix_applied' => $control_id ) );
		}

		public static function handle_revert_fix() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_APPLY_SAFE ) || ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_revert_fix' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$control_id = isset( $_POST['control_id'] ) ? sanitize_key( wp_unslash( $_POST['control_id'] ) ) : '';
			$control_id = strtoupper( str_replace( '_', '-', $control_id ) );
			$result     = Garry_Security_Hardening_Fixes::revert( $control_id );

			if ( is_wp_error( $result ) ) {
				self::redirect_back( array( 'garry_error' => rawurlencode( $result->get_error_message() ) ) );
			}

			Garry_Security_Scanner::run_scan( 'manual' );
			self::redirect_back( array( 'fix_reverted' => $control_id ) );
		}

		/**
		 * "Není potřeba pro tento web" u jedné schopnosti v kapacitní matici
		 * (viz garry_security_capability_exception_set()). Capability se
		 * ověřuje proti allowlistu z garry_security_capability_labels(),
		 * nikdy se z requestu nepřebírá libovolný option klíč.
		 */
		public static function handle_set_capability_exception() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_set_capability_exception' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$capability = isset( $_POST['capability'] ) ? sanitize_key( wp_unslash( $_POST['capability'] ) ) : '';
			if ( ! array_key_exists( $capability, garry_security_capability_labels() ) ) {
				wp_die( esc_html__( 'Neznámá schopnost.', 'garry-default' ) );
			}

			$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
			$until  = isset( $_POST['until'] ) ? sanitize_text_field( wp_unslash( $_POST['until'] ) ) : '';
			if ( '' !== $until && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until ) ) {
				$until = '';
			}

			garry_security_capability_exception_set( $capability, $reason, $until );
			self::redirect_back( array( 'exception_set' => $capability ) );
		}

		public static function handle_clear_capability_exception() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_clear_capability_exception' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}

			$capability = isset( $_POST['capability'] ) ? sanitize_key( wp_unslash( $_POST['capability'] ) ) : '';
			if ( ! array_key_exists( $capability, garry_security_capability_labels() ) ) {
				wp_die( esc_html__( 'Neznámá schopnost.', 'garry-default' ) );
			}

			garry_security_capability_exception_clear( $capability );
			self::redirect_back( array( 'exception_cleared' => $capability ) );
		}

		/** Ruční záznam testu obnovy zálohy (P0 "Obnovitelnost zálohy"). */
		public static function handle_record_restore_test() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_record_restore_test' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}
			$note = isset( $_POST['note'] ) ? sanitize_text_field( wp_unslash( $_POST['note'] ) ) : '';
			Garry_Security_Backup_Gate::record_restore_test( get_current_user_id(), $note );
			self::redirect_back( array( 'restore_test_recorded' => 1 ) );
		}

		/**
		 * Uloží kontakt/umístění záloh/postup pro incident (P2 "Přístup k
		 * obnově"). Vyžaduje GARRY_SECURITY_CAP_MANAGE_PROVIDERS – tahle
		 * informace nesmí být viditelná ani editovatelná rolím mimo
		 * administrátora (viz docblock u Garry_Security_Backup_Gate).
		 */
		public static function handle_save_recovery_access() {
			if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_save_recovery_access' ) ) {
				wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
			}
			Garry_Security_Backup_Gate::set_recovery_access( array(
				'contact'         => isset( $_POST['contact'] ) ? wp_unslash( $_POST['contact'] ) : '',
				'backup_location' => isset( $_POST['backup_location'] ) ? wp_unslash( $_POST['backup_location'] ) : '',
				'runbook_url'     => isset( $_POST['runbook_url'] ) ? wp_unslash( $_POST['runbook_url'] ) : '',
			), get_current_user_id() );
			self::redirect_back( array( 'recovery_access_saved' => 1, 'security_view' => 'technical' ) );
		}
	}
}
