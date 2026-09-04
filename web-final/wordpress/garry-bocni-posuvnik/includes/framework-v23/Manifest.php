<?php
/**
 * GARRY Embedded Framework 2.3 – lokální manifest.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 6.
 *
 * Čte VÝHRADNĚ garry-plugin-manifest.json z vlastního adresáře tohoto
 * pluginu. Nikdy nestahuje nic ze sítě, nikdy neinterpretuje obsah jako kód.
 */

namespace Garry\Embedded\BocniPosuvnik\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Manifest {

	private static $cache = null;

	public static function load( $plugin_dir ) {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$path = trailingslashit( $plugin_dir ) . 'garry-plugin-manifest.json';
		self::$cache = array();

		if ( ! is_readable( $path ) ) {
			return self::$cache;
		}

		$raw = file_get_contents( $path, false, null, 0, 100000 );
		if ( false === $raw ) {
			return self::$cache;
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || ! self::is_valid( $data ) ) {
			return self::$cache;
		}

		self::$cache = $data;
		return self::$cache;
	}

	/**
	 * Povinná validace podle sekce 6 – „Povinná validace manifestu".
	 */
	private static function is_valid( array $data ) {
		if ( empty( $data['slug'] ) || ! Protocol::is_valid_slug( $data['slug'] ) ) {
			return false;
		}
		if ( empty( $data['plugin_version'] ) || ! Protocol::is_valid_semver( $data['plugin_version'] ) ) {
			return false;
		}
		if ( ! empty( $data['admin']['capability'] ) && ! Protocol::is_valid_capability( $data['admin']['capability'] ) ) {
			return false;
		}
		if ( ! empty( $data['data']['settings_option_prefix'] ) ) {
			$prefix = $data['data']['settings_option_prefix'];
			if ( ! is_string( $prefix ) || 0 !== strpos( $prefix, 'garry_' ) ) {
				return false;
			}
		}
		return true;
	}
}
