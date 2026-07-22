<?php
/**
 * GRID Hotel — Divi 5 Child Theme
 * functions.php
 *
 * - načte fonty, CSS a JS designového systému
 * - registruje ACF Options page + acf-json cestu
 * - načte shortcody, které vykreslují jednotlivé sekce z ACF obsahu
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRID_CHILD_VER', '1.10.1' );

/* ------------------------------------------------------------------
 * 1) Styly a skripty
 * ------------------------------------------------------------------ */
function grid_enqueue_assets() {

	// Google Fonts (Saira Condensed / JetBrains Mono / Inter) — plná česká diakritika
	wp_enqueue_style(
		'grid-fonts',
		'https://fonts.googleapis.com/css2?family=Saira+Condensed:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);

	// rodičovský Divi styl
	wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css', array(), GRID_CHILD_VER );

	// child styl (designový systém GRID)
	wp_enqueue_style( 'grid-child-style', get_stylesheet_uri(), array( 'divi-parent-style' ), GRID_CHILD_VER );

	// JS: živý widget (hodiny + teplota), reveal, čekací list, navigace
	wp_enqueue_script( 'grid-app', get_stylesheet_directory_uri() . '/assets/js/grid.js', array(), GRID_CHILD_VER, true );
}
add_action( 'wp_enqueue_scripts', 'grid_enqueue_assets', 20 );

/* ------------------------------------------------------------------
 * 2) ACF — cesta k acf-json (auto-load / auto-save definic polí)
 * ------------------------------------------------------------------ */
add_filter( 'acf/settings/save_json', function ( $path ) {
	return get_stylesheet_directory() . '/acf-json';
} );
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = get_stylesheet_directory() . '/acf-json';
	return $paths;
} );

/* ------------------------------------------------------------------
 * 3) ACF Options page (globální obsah: kontakt, hodiny, widget)
 * ------------------------------------------------------------------ */
add_action( 'acf/init', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( array(
			'page_title' => 'GRID — Nastavení webu',
			'menu_title' => 'GRID Nastavení',
			'menu_slug'  => 'grid-options',
			'capability' => 'edit_posts',
			'redirect'   => false,
			'icon_url'   => 'none', // barevnou ikonu vykreslíme přes admin CSS (viz níže)
			'position'   => 3,
		) );
	}
} );

/* ------------------------------------------------------------------
 * 4) Malý helper: bezpečné čtení ACF s fallbackem
 *    grid_field('slug', 'default', $post_or_option)
 * ------------------------------------------------------------------ */
function grid_field( $name, $default = '', $id = 'option' ) {
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name, $id );
		if ( $v !== null && $v !== '' && $v !== false ) return $v;
	}
	return $default;
}

/* ------------------------------------------------------------------
 * 5) Sekční shortcody (grid_hero, grid_rooms, grid_season, ...)
 * ------------------------------------------------------------------ */
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/errors.php';

/* ------------------------------------------------------------------
 * 6) Obsahová šířka webu (sladěno s návrhem)
 *    1200 = doporučeno | 1280 = kompromis | 1320 = 1:1 návrh
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	$w = (int) grid_field( 'sirka_webu', 1280 );
	echo '<style>:root{--maxw:' . esc_attr( $w ) . 'px}</style>' . "\n";
}, 99 );

/* ------------------------------------------------------------------
 * 7) Favicon webu (pokud není nastavená Ikona webu v Přizpůsobení)
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) return; // WP řeší vlastní ikonou
	$u = get_stylesheet_directory_uri() . '/assets/logo/g-mark.png';
	echo '<link rel="icon" href="' . esc_url( $u ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $u ) . '">' . "\n";
}, 5 );

/* ------------------------------------------------------------------
 * 8) Barevná ikona GRID u položky „GRID Nastavení" (místo autíčka)
 *    Technika převzatá z GARRY frameworku: icon 'none' + CSS background.
 * ------------------------------------------------------------------ */
add_action( 'admin_head', function () {
	$logo = get_stylesheet_directory_uri() . '/assets/logo/g-mark.png';
	?>
	<style>
	#adminmenu .toplevel_page_grid-options .wp-menu-image{
		background-image:url('<?php echo esc_url( $logo ); ?>') !important;
		background-repeat:no-repeat !important;
		background-position:center center !important;
		background-size:22px 22px !important;
		opacity:1 !important;
	}
	#adminmenu .toplevel_page_grid-options .wp-menu-image:before{ content:"" !important; }
	#adminmenu .toplevel_page_grid-options .wp-menu-image img{ display:none !important; }
	</style>
	<?php
} );

/* ------------------------------------------------------------------
 * 9) Klikací odkaz na stránku „Nastavení webu" v menu GRID Nastavení
 *    (CPT typy jsou zavěšené pod grid-options, proto přidáme položku
 *    mířící přímo na ACF options page, ať je vždy dostupná). grid-options-link-fix
 * ------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return;
	add_submenu_page( 'grid-options', 'GRID — Nastavení webu', '⚙ Nastavení webu', 'edit_posts', 'grid-options' );
	// posunout tuto položku na začátek podnabídky
	global $submenu;
	if ( isset( $submenu['grid-options'] ) ) {
		$items = $submenu['grid-options'];
		$self = array(); $rest = array();
		foreach ( $items as $it ) {
			if ( isset( $it[2] ) && $it[2] === 'grid-options' ) $self[] = $it; else $rest[] = $it;
		}
		$submenu['grid-options'] = array_merge( $self, $rest );
	}
}, 999 );

/* ------------------------------------------------------------------
 * 10) Fluent Forms — načíst výchozí styly (jistota viditelnosti)
 * ------------------------------------------------------------------ */
add_filter( 'fluentform/load_default_public_style', '__return_true' );

/* ------------------------------------------------------------------
 * 11) Jazykový přepínač (Polylang) — URL překladů AKTUÁLNÍ stránky.
 *     Hlavička je statická v Divi; grid.js dosadí odkazy z této mapy.
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'pll_get_post' ) ) return;
	$urls = array();
	foreach ( array( 'cs', 'en', 'de' ) as $l ) {
		$u = '';
		if ( is_singular() ) {
			$t = pll_get_post( get_queried_object_id(), $l );
			if ( $t && get_post_status( $t ) === 'publish' ) $u = get_permalink( $t );
		} elseif ( is_tax() && function_exists( 'pll_get_term' ) ) {
			$t = pll_get_term( get_queried_object_id(), $l );
			if ( $t ) { $link = get_term_link( (int) $t ); if ( ! is_wp_error( $link ) ) $u = $link; }
		}
		if ( ! $u && function_exists( 'pll_home_url' ) ) $u = pll_home_url( $l );
		$urls[ $l ] = $u;
	}
	echo '<script>window.gridLangUrls=' . wp_json_encode( $urls ) . ";</script>\n";
} );

/* ------------------------------------------------------------------
 * 12) Shortcody v Theme Builder layoutech (hlavička/patička) —
 *     Divi 5 je v TB obsahu samo nespouští, na stránkách ano.
 * ------------------------------------------------------------------ */
add_filter( 'et_builder_render_layout', 'do_shortcode', 12 ); // Divi 4 cesta
add_filter( 'render_block', function ( $content, $block ) {
	if ( is_admin() ) return $content;
	if ( strpos( (string) ( $block['blockName'] ?? '' ), 'divi/' ) !== 0 ) return $content;
	if ( strpos( $content, '[grid_' ) === false ) return $content;
	return do_shortcode( $content );
}, 20, 2 ); // Divi 5 bloky (Theme Builder)
/* Divi 5 TB renderer shortcody nespouští vůbec → tokeny v hlavičce/patičce
   nahradíme v celém výstupu; náhrady předpočítáme v wp_head (shortcody tam žijí). */
add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	/* FF newsletter v patičce: shortcode spustíme TEĎ (assety se stihnou zařadit),
	   hotové HTML se do TB výstupu vloží tokenem [grid_ff_newsletter] */
	$ff_newsletter = '';
	if ( shortcode_exists( 'fluentform' ) ) {
		$ffmap = (array) get_option( 'grid_ff_forms', array() );
		$lang  = function_exists( 'pll_current_language' ) ? ( pll_current_language() ?: 'cs' ) : 'cs';
		$fid   = $ffmap['newsletter'][ $lang ] ?? ( $ffmap['newsletter']['cs'] ?? 0 );
		if ( $fid ) $ff_newsletter = do_shortcode( '[fluentform id=' . (int) $fid . ']' );
	}
	ob_start( function ( $html ) use ( $ff_newsletter ) {
		$map = array(
			'[grid_paticka_kontakt]' => function_exists( 'grid_sc_footer_kontakt' ) ? grid_sc_footer_kontakt() : '',
			'[grid_socials]'         => function_exists( 'grid_sc_socials' ) ? grid_sc_socials() : '',
			'[grid_ff_newsletter]'   => $ff_newsletter,
		);
		foreach ( $map as $token => $out ) {
			if ( strpos( $html, $token ) !== false ) $html = str_replace( $token, (string) $out, $html );
		}
		return $html;
	} );
}, 1 );
