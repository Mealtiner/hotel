<?php
/**
 * GARRY Security – scan engine (job queue, findings a events).
 *
 * Implementuje pravidla z GARRY-WP-SECURITY-BEHAVIOR-SPEC.md sekce 7.5:
 * stejný výsledek jen aktualizuje last_seen, změna stavu vytvoří event,
 * vyřešený nález dostane resolved_at + recovery event.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Garry_Security_Scanner' ) ) {

	class Garry_Security_Scanner {

		const LOCK_KEY = 'garry_security_scan_lock';
		const LOCK_TTL = 300; // pojistka proti zaseknutému locku (5 minut).

		/** Stavy, které se považují za „vyřešeno" / nerizikové. */
		const RESOLVED_STATES = array( 'pass', 'not_applicable', 'delegated' );

		/**
		 * Spustí jeden scan: zamkne proti souběhu, projede všechny collectory,
		 * zapíše findings/events a uzavře záznam ve scans tabulce. Lock nese
		 * scan UUID, vlastníka a expiraci (SEC-SELF-005 z auditního plánu),
		 * takže je dohledatelné, čí běh lock drží a ke kterému scanu patří.
		 *
		 * @return int|false ID scanu, nebo false pokud už jiný scan běží.
		 */
		public static function run_scan( $trigger ) {
			if ( false !== get_transient( self::LOCK_KEY ) ) {
				return false;
			}

			$scan_uuid = wp_generate_uuid4();
			set_transient( self::LOCK_KEY, array(
				'scan_uuid' => $scan_uuid,
				'owner'     => get_current_user_id() ?: 'cron',
				'locked_at' => time(),
				'expires_at' => time() + self::LOCK_TTL,
			), self::LOCK_TTL );

			global $wpdb;
			$scans_table = Garry_Security_DB::table( 'scans' );
			$now         = current_time( 'mysql', true );

			$wpdb->insert(
				$scans_table,
				array(
					'scan_uuid'      => $scan_uuid,
					'scan_type'      => 'full',
					'trigger_source' => sanitize_key( $trigger ),
					'status'         => 'running',
					'started_at'     => $now,
					'heartbeat_at'   => $now,
					'plugin_version' => defined( 'GARRY_DEFAULT_VERSION' ) ? GARRY_DEFAULT_VERSION : '',
					'schema_version' => Garry_Security_DB::DB_VERSION,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
			);
			$scan_id = (int) $wpdb->insert_id;

			$counts = array(
				'pass' => 0, 'warning' => 0, 'critical' => 0, 'unknown' => 0,
				'conflict' => 0, 'delegated' => 0, 'not_applicable' => 0,
			);
			$status = 'completed';
			$error  = '';

			try {
				$results = garry_security_run_all_collectors();
				foreach ( $results as $control_id => $result ) {
					self::upsert_finding( $control_id, $result );
					$state = isset( $result['state'] ) ? $result['state'] : 'unknown';
					if ( ! isset( $counts[ $state ] ) ) {
						$state = 'unknown';
					}
					$counts[ $state ]++;
				}
			} catch ( Exception $e ) {
				$status = 'failed';
				$error  = $e->getMessage();
			}

			$wpdb->update(
				$scans_table,
				array(
					'status'         => $status,
					'finished_at'    => current_time( 'mysql', true ),
					'pass_count'     => $counts['pass'],
					'warning_count'  => $counts['warning'],
					'critical_count' => $counts['critical'],
					'unknown_count'  => $counts['unknown'],
					'conflict_count' => $counts['conflict'],
					'error_message'  => '' !== $error ? $error : null,
				),
				array( 'id' => $scan_id ),
				array( '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);

			delete_transient( self::LOCK_KEY );

			return $scan_id;
		}

		private static function upsert_finding( $control_id, array $result ) {
			global $wpdb;
			$table = Garry_Security_DB::table( 'findings' );

			$state         = isset( $result['state'] ) ? sanitize_key( $result['state'] ) : 'unknown';
			$severity      = isset( $result['severity'] ) ? sanitize_key( $result['severity'] ) : 'info';
			$message       = isset( $result['message'] ) ? wp_strip_all_tags( $result['message'] ) : '';
			$evidence      = ( isset( $result['evidence'] ) && is_array( $result['evidence'] ) ) ? $result['evidence'] : array();
			$evidence_json = wp_json_encode( $evidence );
			$evidence_hash = hash( 'sha256', $control_id . '|' . $state . '|' . $evidence_json );
			$now           = current_time( 'mysql', true );

			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, state, evidence_hash FROM {$table} WHERE control_id = %s AND scope = ''", $control_id )
			);

			if ( ! $existing ) {
				$wpdb->insert(
					$table,
					array(
						'control_id'    => $control_id,
						'scope'         => '',
						'severity'      => $severity,
						'state'         => $state,
						'owner'         => 'garry',
						'evidence_hash' => $evidence_hash,
						'evidence'      => $evidence_json,
						'message'       => $message,
						'first_seen'    => $now,
						'last_seen'     => $now,
						'changed_at'    => $now,
						'resolved_at'   => in_array( $state, self::RESOLVED_STATES, true ) ? $now : null,
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
				self::insert_event( $control_id, null, $state, 'created' );
				return;
			}

			$changed = ( $existing->state !== $state || $existing->evidence_hash !== $evidence_hash );

			if ( ! $changed ) {
				$wpdb->update( $table, array( 'last_seen' => $now ), array( 'id' => $existing->id ), array( '%s' ), array( '%d' ) );
				return;
			}

			$resolved_now = in_array( $state, self::RESOLVED_STATES, true );
			$was_active   = ! in_array( $existing->state, self::RESOLVED_STATES, true );

			$wpdb->update(
				$table,
				array(
					'severity'      => $severity,
					'state'         => $state,
					'evidence_hash' => $evidence_hash,
					'evidence'      => $evidence_json,
					'message'       => $message,
					'last_seen'     => $now,
					'changed_at'    => $now,
					'resolved_at'   => $resolved_now ? $now : null,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			self::insert_event( $control_id, $existing->state, $state, ( $resolved_now && $was_active ) ? 'resolved' : 'changed' );
		}

		private static function insert_event( $control_id, $old_state, $new_state, $event_type ) {
			global $wpdb;
			$wpdb->insert(
				Garry_Security_DB::table( 'events' ),
				array(
					'event_time' => current_time( 'mysql', true ),
					'event_type' => $event_type,
					'control_id' => $control_id,
					'old_state'  => $old_state,
					'new_state'  => $new_state,
					'actor'      => 'system',
					'source'     => 'scan',
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		/**
		 * Počty findings podle stavu, pro dashboard kartu „Stav zabezpečení".
		 */
		public static function get_summary_counts() {
			global $wpdb;
			$table  = Garry_Security_DB::table( 'findings' );
			$rows   = $wpdb->get_results( "SELECT state, COUNT(*) AS total FROM {$table} GROUP BY state", ARRAY_A );
			$counts = array();
			foreach ( $rows as $row ) {
				$counts[ $row['state'] ] = (int) $row['total'];
			}
			return $counts;
		}

		/**
		 * Všechna aktuální findings, kritické/warning/conflict nahoře.
		 */
		public static function get_findings() {
			global $wpdb;
			$table = Garry_Security_DB::table( 'findings' );
			return $wpdb->get_results(
				"SELECT * FROM {$table} ORDER BY FIELD(state,'critical','conflict','warning','unknown','delegated','not_applicable','pass'), control_id ASC"
			);
		}

		public static function get_last_scan() {
			global $wpdb;
			$table = Garry_Security_DB::table( 'scans' );
			return $wpdb->get_row( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 1" );
		}

		/**
		 * Posledních N událostí (vznik/změna/vyřešení nálezu) pro kartu
		 * "Poslední bezpečnostní události" na Přehledu.
		 */
		public static function get_recent_events( $limit = 5 ) {
			global $wpdb;
			$table = Garry_Security_DB::table( 'events' );
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", (int) $limit )
			);
		}
	}
}
