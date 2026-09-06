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
$GLOBALS['gridc_needs_gastro_menu'] = false;

add_action( 'wp_footer', function () {
	$needs_menu    = ! empty( $GLOBALS['gridc_needs_mobile_menu'] );
	$needs_gallery = ! empty( $GLOBALS['gridc_needs_gallery_js'] );
	$needs_gastro  = ! empty( $GLOBALS['gridc_needs_gastro_menu'] );

	if ( is_singular() && ! $needs_menu ) {
		global $post;
		if ( $post && has_shortcode( (string) $post->post_content, 'grid_header' ) ) {
			$needs_menu = true;
		}
	}
	/* Hlavička s mobilním menu je v šabloně Theme Builderu, ne v obsahu stránky,
	   takže ji has_shortcode() nikdy nenajde. Token v ní navíc rozbaluje až
	   výstupní buffer PO wp_footer, takže ani příznak z rendereru sem nedorazí
	   včas. Na frontendu je hlavička vždycky, proto skript zařazujeme natvrdo —
	   je to pár kilobajtů a bez něj hamburger nefunguje. */
	if ( ! is_admin() ) {
		$needs_menu = true;
	}
	if ( is_singular() && ! $needs_gallery ) {
		global $post;
		if ( $post && has_shortcode( (string) $post->post_content, 'grid_galerie' ) ) {
			$needs_gallery = true;
		}
	}

	if ( ! $needs_menu && ! $needs_gallery && ! $needs_gastro ) {
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
		'gastroMenu' => $needs_gastro,
	) );
}, 20 );

/**
 * Skript pro mobilní menu se musí zařadit ve `wp_enqueue_scripts`, ne až
 * v `wp_footer`. WordPress tiskne patičkové skripty rovněž na `wp_footer`
 * s prioritou 20 — enqueue přidaný později se do stránky vůbec nedostane
 * a hamburger pak nemá obsluhu. Hlavička s menu je na frontendu vždycky,
 * takže tady žádnou podmínku nepotřebujeme; samotný skript si přítomnost
 * prvků hlídá sám.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() ) return;
	wp_enqueue_script(
		'gridhotel-components',
		trailingslashit( GRIDHOTEL_COMPONENTS_URL ) . 'assets/components.js',
		array(),
		GRIDHOTEL_COMPONENTS_VER,
		true
	);
	wp_localize_script( 'gridhotel-components', 'gridComponentsConfig', array(
		'mobileMenu' => true,
		'gallery'    => true,
		'gastroMenu' => true,
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
function gridc_flag_gastro_menu_needed() {
	$GLOBALS['gridc_needs_gastro_menu'] = true;
}
