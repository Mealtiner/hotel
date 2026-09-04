<?php
/**
 * GARRY Embedded Framework 2.3 – deterministická volba vlastníka claims.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 4.2–4.3.
 *
 * Čistá/bezstavová funkce: ze sdílené (pouze datové) request registry
 * spočítá, kdo je vlastníkem každého sdíleného claimu. Nemění nic mimo
 * návratovou hodnotu – zápis do $GLOBALS dělá volající (FrameworkBridge).
 */

namespace Garry\Embedded\KategoriePokoju\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Election {

	/**
	 * @param array $participants slug => descriptor
	 * @return array claim => slug|null
	 */
	public static function run( array $participants ) {
		$claims = array();
		foreach ( Protocol::ALLOWED_CLAIMS as $claim ) {
			$claims[ $claim ] = self::elect_for_claim( $claim, $participants );
		}

		// group_overview patří vlastníkovi root_menu, pokud ho sám deklaruje
		// (sekce 4.3, poslední odstavec).
		if ( null !== $claims['root_menu'] && null !== $claims['group_overview'] ) {
			$root_owner = $participants[ $claims['root_menu'] ];
			if ( in_array( 'group_overview', (array) $root_owner['claims'], true ) ) {
				$claims['group_overview'] = $claims['root_menu'];
			}
		}

		return $claims;
	}

	private static function elect_for_claim( $claim, array $participants ) {
		$candidates = array();

		foreach ( $participants as $slug => $descriptor ) {
			if ( ! Protocol::is_valid_descriptor( $descriptor ) ) {
				continue; // krok 2: odmítni neplatný descriptor.
			}
			if ( (int) $descriptor['framework_major'] !== Protocol::MAJOR ) {
				continue; // krok 1: jen framework_major 2.
			}
			if ( ! in_array( $claim, (array) $descriptor['claims'], true ) ) {
				continue;
			}
			if ( version_compare( Protocol::VERSION, $descriptor['framework_minimum'], '<' ) ) {
				continue; // krok 3: framework_minimum nejvýše aktuální protokol.
			}
			$candidates[ $slug ] = $descriptor;
		}

		if ( empty( $candidates ) ) {
			return null;
		}

		$slugs = array_keys( $candidates );
		usort( $slugs, function ( $a, $b ) use ( $candidates ) {
			$pa = (int) $candidates[ $a ]['framework_priority'];
			$pb = (int) $candidates[ $b ]['framework_priority'];
			if ( $pa === $pb ) {
				return strcmp( $a, $b ); // krok 5: abecední slug jako tie-breaker.
			}
			return $pa - $pb; // krok 4: vzestupně podle priority.
		} );

		return $slugs[0];
	}
}
