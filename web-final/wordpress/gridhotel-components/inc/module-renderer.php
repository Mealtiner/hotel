<?php
/**
 * GRID Hotel Components — ModuleRenderer (verze 1.0.0, GRID-SUITE-02 §6).
 *
 * Oprava kritického bugu vnořených shortcodů: theme dřív skládal sekce
 * vložením textových tagů jako [grid_rooms_cards]/[grid_menu_tydne] do
 * vlastního HTML výstupu a spoléhal na to, že WordPress ten string projde
 * podruhé (do_shortcode() se sám o sobě nerekurzuje) — fungovalo to jen
 * náhodou, jen v content, ne v Theme Builder headeru/footeru (theme si tam
 * musel ručně nahrazovat 2 konkrétní tokeny, viz funguje.php).
 *
 * gridc_render_module() řeší to jednou, obecně, pro všech 5 skládaných
 * sekcí, v přesně tomto pořadí:
 *   1. dokumentovaná veřejná render funkce modulu (descriptor['render_callback']);
 *   2. shortcode_exists() + do_shortcode() nad JEDNÍM explicitně sestaveným
 *      tagem se známými atributy (nikdy nad libovolným HTML);
 *   3. neutrální fallback Components.
 * Guard proti rekurzi: max. jedno vnoření v rámci jednoho callbacku.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param string $feature        Feature ID, např. 'room_comparison', 'weekly_menu', 'season_events'.
 * @param string $shortcode_tag  Očekávaný shortcode tag modulu, např. 'grid_rooms_cards'.
 * @param array  $atts           Atributy pro krok 1/2.
 * @param string $fallback_html  Vrátí se, pokud žádný modul feature/tag neposkytuje.
 * @return string
 */
function gridc_render_module( $feature, $shortcode_tag, array $atts = array(), $fallback_html = '' ) {
	static $depth = 0;
	if ( $depth > 0 ) {
		return $fallback_html; // ochrana proti rekurzi — max. jedno vnoření (spec §6)
	}
	if ( ! function_exists( 'gridhotel_get_modules' ) ) {
		return $fallback_html; // Core nedostupné/starší verze — degradace, ne fatal
	}

	$depth++;
	try {
		foreach ( gridhotel_get_modules() as $descriptor ) {
			if ( empty( $descriptor['frontend_enabled'] ) ) {
				continue;
			}
			if ( empty( $descriptor['features'] ) || ! in_array( $feature, (array) $descriptor['features'], true ) ) {
				continue;
			}
			if ( empty( $descriptor['shortcodes'] ) || ! in_array( $shortcode_tag, (array) $descriptor['shortcodes'], true ) ) {
				continue;
			}

			if ( ! empty( $descriptor['render_callback'] ) && is_callable( $descriptor['render_callback'] ) ) {
				$out = call_user_func( $descriptor['render_callback'], $atts );
				if ( is_string( $out ) && '' !== trim( $out ) ) {
					return $out;
				}
			}

			if ( shortcode_exists( $shortcode_tag ) ) {
				$out = do_shortcode( gridc_build_single_shortcode_tag( $shortcode_tag, $atts ) );
				if ( is_string( $out ) && '' !== trim( $out ) ) {
					return $out;
				}
			}
		}
	} finally {
		$depth--;
	}

	return $fallback_html;
}

/** Sestaví JEDEN shortcode tag z whitelisovaného jména atributu + escapované hodnoty — nikdy libovolné HTML. */
function gridc_build_single_shortcode_tag( $tag, array $atts ) {
	$out = '[' . preg_replace( '/[^a-z0-9_]/', '', $tag );
	foreach ( $atts as $k => $v ) {
		$k = preg_replace( '/[^a-z0-9_]/', '', (string) $k );
		if ( '' === $k ) {
			continue;
		}
		$out .= ' ' . $k . '="' . esc_attr( (string) $v ) . '"';
	}
	return $out . ']';
}

/**
 * True, pokud existuje aktivní, frontend_enabled modul s danou feature —
 * použij pro podmíněné vykreslení nadpisu sekce apod. bez nutnosti sekci
 * skutečně renderovat.
 */
function gridc_feature_available( $feature ) {
	return function_exists( 'gridhotel_has_feature' ) && gridhotel_has_feature( $feature );
}
