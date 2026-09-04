<?php
/**
 * GARRY Embedded Framework 2.4 – protokol a validační pravidla.
 */

namespace Garry\Embedded\GridCore\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Protocol {
	const VERSION      = '2.4.0';
	const MAJOR        = 2;
	const REGISTRY_KEY = 'garry_framework_v24';
	const ALLOWED_CLAIMS = array( 'root_menu', 'group_overview', 'group_info', 'group_assets' );
	const ALLOWED_CAPABILITIES = array( 'manage_options', 'edit_others_posts', 'edit_posts' );

	public static function is_valid_slug( $slug ) {
		return is_string( $slug ) && preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug ) === 1;
	}
	public static function is_valid_semver( $version ) {
		return is_string( $version ) && preg_match( '/^\d+\.\d+\.\d+$/', $version ) === 1;
	}
	public static function is_valid_capability( $capability ) {
		return in_array( $capability, self::ALLOWED_CAPABILITIES, true );
	}
	public static function is_valid_claim( $claim ) {
		return in_array( $claim, self::ALLOWED_CLAIMS, true );
	}
	public static function is_valid_descriptor( $descriptor ) {
		if ( ! is_array( $descriptor )
			|| empty( $descriptor['slug'] ) || ! self::is_valid_slug( $descriptor['slug'] )
			|| empty( $descriptor['framework_minimum'] ) || ! self::is_valid_semver( $descriptor['framework_minimum'] )
			|| ! isset( $descriptor['framework_major'] ) || ! is_int( $descriptor['framework_major'] )
			|| ! isset( $descriptor['framework_priority'] ) || ! is_int( $descriptor['framework_priority'] )
			|| empty( $descriptor['local_menu_slug'] ) || ! self::is_valid_slug( $descriptor['local_menu_slug'] )
			|| ! isset( $descriptor['claims'] ) || ! is_array( $descriptor['claims'] ) ) {
			return false;
		}
		foreach ( $descriptor['claims'] as $claim ) {
			if ( ! self::is_valid_claim( $claim ) ) return false;
		}
		foreach ( $descriptor as $value ) {
			if ( is_object( $value ) || $value instanceof \Closure ) return false;
		}
		return true;
	}
}
