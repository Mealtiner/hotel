<?php
/**
 * GARRY Embedded Framework 2.3 – diagnostika pro lokální stránku Přehled.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 4.4 a 5.1.
 *
 * Jen čte sdílenou request registry a formátuje ji pro zobrazení.
 * Nikdy nic neopravuje, nevypíná ani nepřebírá cizí data (sekce 8).
 */

namespace Garry\Embedded\KategoriePokoju\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Diagnostics {

	public static function registry() {
		return isset( $GLOBALS[ Protocol::REGISTRY_KEY ] ) && is_array( $GLOBALS[ Protocol::REGISTRY_KEY ] )
			? $GLOBALS[ Protocol::REGISTRY_KEY ]
			: array( 'participants' => array(), 'claims' => array(), 'diagnostics' => array( 'legacy' => array(), 'rejected' => array() ) );
	}

	public static function other_participants( $own_slug ) {
		$registry = self::registry();
		$others   = array();
		foreach ( $registry['participants'] as $slug => $descriptor ) {
			if ( $slug === $own_slug ) {
				continue;
			}
			$others[] = array(
				'slug' => $slug,
				'name' => isset( $descriptor['name'] ) ? $descriptor['name'] : $slug,
			);
		}
		return $others;
	}

	public static function owned_claims( $own_slug ) {
		$registry = self::registry();
		$owned    = array();
		foreach ( $registry['claims'] as $claim => $owner ) {
			if ( $owner === $own_slug ) {
				$owned[] = $claim;
			}
		}
		return $owned;
	}

	public static function has_legacy_framework() {
		$registry = self::registry();
		return ! empty( $registry['diagnostics']['legacy'] );
	}
}
