<?php
/**
 * GRID Hotel Components — jednotná registrace shortcodů (verze 1.0.0).
 *
 * Všech 30 `add_shortcode()` volání v inc/shortcodes/*.php jde přes
 * `gridc_register_shortcode()` místo přímo, aby:
 *  - vždy proběhl filtr `gridhotel_components_shortcode_output` (spec §5) —
 *    tag + obsah k dispozici, ale bez možnosti obejít sanitizaci uvnitř
 *    callbacku (filtr dostane už HOTOVÝ, escapovaný HTML string, ne vstup);
 *  - výstup byl vždy string (nikdy přímý echo/null);
 *  - se dala na jednom místě navázat detekce potřeby assetů (inc/assets.php)
 *    bez zásahu do každého z 30 callbacků zvlášť.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function gridc_register_shortcode( $tag, $callback ) {
	add_shortcode( $tag, function ( $atts = array(), $content = '', $shortcode_tag = '' ) use ( $tag, $callback ) {
		$atts   = is_array( $atts ) ? $atts : array();
		$output = call_user_func( $callback, $atts, $content, $tag );
		if ( ! is_string( $output ) ) {
			$output = '';
		}

		if ( function_exists( 'gridc_flag_mobile_menu_needed' ) && 'grid_header' === $tag ) {
			gridc_flag_mobile_menu_needed();
		}
		if ( function_exists( 'gridc_flag_gallery_js_needed' ) && 'grid_galerie' === $tag ) {
			gridc_flag_gallery_js_needed();
		}
		if ( function_exists( 'gridc_flag_gastro_menu_needed' ) && 'grid_gastro' === $tag ) {
			gridc_flag_gastro_menu_needed();
		}

		return apply_filters( 'gridhotel_components_shortcode_output', $output, $tag, $content );
	} );
}
