<?php
/**
 * GARRY Security – naplánování denního scanu přes WP-Cron.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Cron' ) ) {

	class Garry_Security_Cron {

		const HOOK = 'garry_security_daily_scan';

		public static function register_hooks() {
			add_action( self::HOOK, array( __CLASS__, 'run' ) );
		}

		public static function schedule() {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
			}
		}

		public static function unschedule() {
			$timestamp = wp_next_scheduled( self::HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::HOOK );
			}
			wp_clear_scheduled_hook( self::HOOK );
		}

		public static function run() {
			Garry_Security_Scanner::run_scan( 'cron' );
		}
	}
}
