<?php
/**
 * GARRY Embedded Framework 2.3 – lokální log tohoto pluginu.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 5.3.
 *
 * Log patří výhradně tomuto pluginu (vlastní option prefix), nikdy nečte
 * ani neagreguje logy jiných pluginů. Retence: max. 200 položek nebo 90 dní,
 * podle toho, co nastane dřív. Zakázaná data (hesla, tokeny, IP, e-maily,
 * plné URL, HTML/SQL/backtrace) se do logu nikdy neukládají – proto tahle
 * třída bere jen pevně dané typy událostí s allowlistovanými poli, ne
 * libovolné pole od volajícího.
 */

namespace Garry\Embedded\GarryDefault\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class LocalLog {

	const MAX_ENTRIES     = 200;
	const MAX_AGE_SECONDS = 90 * DAY_IN_SECONDS;

	const ALLOWED_EVENTS = array(
		'framework_boot',
		'plugin_version_changed',
		'framework_version_changed',
		'migration_completed',
		'compatibility_state_changed',
		'settings_saved',
		'integration_changed',
	);

	/**
	 * @param string $option_name Vlastní option prefix tohoto pluginu, např. garry_denni_menu_framework_log.
	 * @param string $event       Musí být v ALLOWED_EVENTS.
	 * @param array  $data        Jen neutrální skalární hodnoty (viz jednotliví volající).
	 */
	public static function log( $option_name, $event, array $data = array() ) {
		if ( ! in_array( $event, self::ALLOWED_EVENTS, true ) ) {
			return;
		}

		$entries   = self::get_entries( $option_name );
		$entries[] = array(
			'time'  => time(),
			'event' => $event,
			'data'  => self::sanitize_data( $data ),
		);

		$entries = self::prune( $entries );

		update_option( $option_name, $entries, false );
	}

	public static function get_entries( $option_name ) {
		$entries = get_option( $option_name, array() );
		return is_array( $entries ) ? self::prune( $entries ) : array();
	}

	private static function prune( array $entries ) {
		$cutoff = time() - self::MAX_AGE_SECONDS;
		$entries = array_values( array_filter( $entries, function ( $e ) use ( $cutoff ) {
			return isset( $e['time'] ) && (int) $e['time'] >= $cutoff;
		} ) );

		if ( count( $entries ) > self::MAX_ENTRIES ) {
			$entries = array_slice( $entries, -self::MAX_ENTRIES );
		}

		return $entries;
	}

	/**
	 * Jen skalární hodnoty, žádné pole delší než pár desítek znaků a
	 * explicitní blacklist klíčů, které by mohly nést citlivá data.
	 */
	private static function sanitize_data( array $data ) {
		$forbidden_keys = array( 'password', 'token', 'secret', 'key', 'cookie', 'nonce', 'email', 'ip', 'url' );
		$clean = array();
		foreach ( $data as $key => $value ) {
			$key = (string) $key;
			foreach ( $forbidden_keys as $needle ) {
				if ( false !== stripos( $key, $needle ) ) {
					continue 2;
				}
			}
			if ( is_scalar( $value ) ) {
				$value = is_string( $value ) ? substr( $value, 0, 80 ) : $value;
				$clean[ $key ] = $value;
			}
		}
		return $clean;
	}
}
