<?php
/**
 * GRID Hotel Core — jeden adaptér pro čtení globálních hotelových hodnot
 * (verze 2.0.0). Frontend/Components/GARRY pluginy mají číst VÝHRADNĚ přes
 * `gridhotel_get_option()`, nikdy přímo `get_field(..., 'option')` — díky
 * tomu je jedno, jestli web běží s ACF PRO options page, nebo jen s
 * fallbackem přes Settings API (viz inc/options-page.php); obě cesty vrací
 * stejný tvar hodnoty.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_SETTINGS_FALLBACK_OPTION', 'gridcore_settings_fallback' );

/**
 * @param string      $key     Název ACF pole bez jazykové přípony, např. 'hero_nadpis'.
 * @param mixed       $default Vrátí se, pokud hodnota (ani v žádném fallbacku) neexistuje/je prázdná.
 * @param string|null $locale  'cs'|'en'|'de'; null = aktuální jazyk (Polylang, je-li aktivní) nebo výchozí jazyk webu.
 * @return mixed
 */
function gridhotel_get_option( $key, $default = null, $locale = null ) {
	$key = (string) $key;

	if ( null === $locale ) {
		$locale = function_exists( 'pll_current_language' ) ? pll_current_language() : gridcore_site_default_locale();
	}

	$candidates       = array();
	$requested_suffix = gridcore_locale_field_suffix( $locale );
	if ( $requested_suffix ) {
		$candidates[] = $key . '_' . $requested_suffix;
	}

	$default_locale = gridcore_site_default_locale();
	$default_suffix = gridcore_locale_field_suffix( $default_locale );
	if ( $default_suffix && $default_suffix !== $requested_suffix ) {
		$candidates[] = $key . '_' . $default_suffix;
	}

	$candidates[] = $key; // čeština = pole bez jazykové přípony

	foreach ( array_unique( $candidates ) as $field_name ) {
		$value = gridcore_options_backend_get( $field_name );
		if ( null !== $value && '' !== $value ) {
			return apply_filters( 'gridhotel_get_option', $value, $key, $locale );
		}
	}

	return apply_filters( 'gridhotel_get_option', $default, $key, $locale );
}

/**
 * @param string $locale 'cs'|'en-US'|… – normalizuje na 'en'/'de', nebo '' (= čeština, bez přípony).
 */
function gridcore_locale_field_suffix( $locale ) {
	$locale = strtolower( substr( (string) $locale, 0, 2 ) );
	return in_array( $locale, array( 'en', 'de' ), true ) ? $locale : '';
}

/** @return string Výchozí jazyk webu (Polylang, je-li aktivní), jinak 'cs'. */
function gridcore_site_default_locale() {
	if ( function_exists( 'pll_default_language' ) ) {
		$locale = pll_default_language();
		if ( $locale ) {
			return $locale;
		}
	}
	return 'cs';
}

/**
 * Jeden bod pravdy pro to, ODKUD hodnota reálně přijde — ACF options page,
 * je-li k dispozici, jinak fallback option (viz gridcore_render_settings_fallback_form()
 * v inc/options-page.php). Vrací null, pokud pole vůbec neexistuje (odlišeno
 * od '' = existuje, je prázdné).
 */
function gridcore_options_backend_get( $field_name ) {
	if ( function_exists( 'get_field' ) && function_exists( 'acf_add_options_page' ) ) {
		$value = get_field( $field_name, 'option' );
		return ( false === $value ) ? null : $value;
	}
	$settings = get_option( GRIDCORE_SETTINGS_FALLBACK_OPTION, array() );
	return isset( $settings[ $field_name ] ) ? $settings[ $field_name ] : null;
}
