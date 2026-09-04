<?php
/**
 * GRID Hotel Components — assety (verze 1.0.0, GRID-SUITE-02 §11).
 *
 * CSS zůstává vlastnictvím theme (GRID-SUITE-09 §15 — Components dodává JEN
 * markup + interaktivní chování, vizuál řeší theme přes stejné třídy jako
 * dosud, žádná duplicitní/konfliktní stylová vrstva teď nevzniká). JS ale
 * Components vlastní tam, kde je přímo svázané s JEJÍ vlastní markupem
 * (mobilní menu v [grid_header], filtr/lightbox v [grid_galerie]) — zbytek
 * (scroll-reveal, hero track line, telemetry, tracknav) zůstává mimo rozsah
 * fáze 2, viz vlastnické tabulky GRID-SUITE-09 §15 (dekorativní/theme, nebo
 * vlastní jiný plugin).
 *
 * Enqueue až když se komponenta reálně vykreslila (ne staticky na každé
 * stránce) — přes `has_shortcode()` na aktuálním obsahu + přímý příznak z
 * rendereru pro sekce mimo `the_content` (Theme Builder header/footer).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$GLOBALS['gridc_needs_mobile_menu'] = false;
$GLOBALS['gridc_needs_gallery_js']  = false;

add_action( 'wp_footer', function () {
	$needs_menu    = ! empty( $GLOBALS['gridc_needs_mobile_menu'] );
	$needs_gallery = ! empty( $GLOBALS['gridc_needs_gallery_js'] );

	if ( is_singular() && ! $needs_menu ) {
		global $post;
		if ( $post && has_shortcode( (string) $post->post_content, 'grid_header' ) ) {
			$needs_menu = true;
		}
	}
	if ( is_singular() && ! $needs_gallery ) {
		global $post;
		if ( $post && has_shortcode( (string) $post->post_content, 'grid_galerie' ) ) {
			$needs_gallery = true;
		}
	}

	if ( ! $needs_menu && ! $needs_gallery ) {
		return;
	}

	wp_enqueue_script(
		'gridhotel-components',
		trailingslashit( GRIDHOTEL_COMPONENTS_URL ) . 'assets/components.js',
		array(),
		GRIDHOTEL_COMPONENTS_VER,
		true
	);
	wp_localize_script( 'gridhotel-components', 'gridComponentsConfig', array(
		'mobileMenu' => $needs_menu,
		'gallery'    => $needs_gallery,
	) );
}, 20 );

/**
 * Nastaveno přímo z gridc_register_shortcode() (inc/shortcode-registry.php)
 * při renderu [grid_header]/[grid_galerie] — funguje i pro sekce mimo
 * the_content (Theme Builder header/footer), kde has_shortcode() nad
 * post_content nestačí.
 */
function gridc_flag_mobile_menu_needed() {
	$GLOBALS['gridc_needs_mobile_menu'] = true;
}
function gridc_flag_gallery_js_needed() {
	$GLOBALS['gridc_needs_gallery_js'] = true;
}
