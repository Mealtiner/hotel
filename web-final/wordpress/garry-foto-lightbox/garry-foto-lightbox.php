<?php
/**
 * Plugin Name:       GARRY – Foto lightbox
 * Plugin URI:        https://www.garry.cz
 * Description:       Lightbox pro fotogalerie s nastavitelným pozadím (plná barva i přechody), logem webu, popiskem nad snímkem, doprovodnými informacemi pod ním a vodorovným ukazatelem pořadí ve stylu trackovače na trati. Shortcode, Elementor widget i Divi modul.
 * Version:           1.0.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-foto-lightbox
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GFLB_VERSION', '1.0.0' );
define( 'GFLB_FILE', __FILE__ );
define( 'GFLB_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFLB_URL', plugin_dir_url( __FILE__ ) );
define( 'GFLB_OPTION', 'garry_foto_lightbox' );

/* ============================================================================
 * Administrace
 * ============================================================================ */
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/admin-stranka.php';

/* ============================================================================
 * Sdílený GARRY rámec (vlastní namespacovaná kopie, žádná závislost na jiném
 * pluginu — viz GARRY-CORE-FRAMEWORK-2.4-REFERENCE.md).
 * ============================================================================ */
require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\FotoLightbox\V23\bootstrap( __FILE__, 'gflb_admin_page', 'Foto lightbox' );

/* ============================================================================
 * Nastavení
 * ============================================================================ */

/**
 * Výchozí nastavení.
 *
 * Výchozí pozadí je lineární přechod z pravého horního rohu (červená) do
 * levého dolního (černá) — zadání pro GRID Hotel. Plugin ale nesmí nést
 * branding konkrétního webu natvrdo v kódu, proto jsou barvy nastavitelné
 * a logo se bere z loga webu (custom_logo), ne z obrázku v pluginu.
 */
function gflb_defaults() {
	return array(
		/* --- pozadí --- */
		'pozadi_typ'        => 'linear',   // solid | linear | radial | conic | rohy
		'pozadi_barva1'     => '#C20E1A',
		'pozadi_barva2'     => '#08090B',
		'pozadi_barva3'     => '#3A0A0E',
		'pozadi_uhel'       => 225,        // stupně, 225 = z pravého horního do levého dolního
		'pozadi_zlom'       => 34,         // % pozice prostřední barvy
		'pozadi_stred'      => 'top right',
		'pozadi_roh_ph'     => '#C20E1A',  // pravý horní
		'pozadi_roh_lh'     => '#3A0A0E',  // levý horní
		'pozadi_roh_pd'     => '#1A0C10',  // pravý dolní
		'pozadi_roh_ld'     => '#08090B',  // levý dolní
		'pozadi_kryti'      => 96,         // %
		'pozadi_rozostreni' => 0,          // px, backdrop-filter

		/* --- logo --- */
		'logo_zobrazit' => 1,
		'logo_zdroj'    => 'web',          // web | priloha | url
		'logo_priloha'  => 0,
		'logo_url'      => '',
		'logo_pozice'   => 'vlevo-nahore', // vlevo-nahore | vpravo-nahore | vlevo-dole | vpravo-dole
		'logo_vyska'    => 44,             // px
		'logo_kryti'    => 100,            // %

		/* --- text nad snímkem --- */
		'nadpis_zobrazit'  => 1,
		'nadpis_sablona'   => '{web} — {galerie} — {index}/{celkem}',
		'nadpis_zarovnani' => 'center',
		'nadpis_barva'     => '#F4F2F0',
		'nadpis_velikost'  => 13,          // px

		/* --- informace pod snímkem --- */
		'popisek_zobrazit' => 1,
		'popisek_zdroj'    => 'caption',   // caption | description | alt | title
		'popisek_barva'    => '#B9B7B9',
		'popisek_velikost' => 14,

		/* --- ukazatel pořadí --- */
		'ukazatel_zobrazit'    => 1,
		'ukazatel_cisla'       => 1,
		'ukazatel_barva_cary'  => 'rgba(255,255,255,.28)',
		'ukazatel_barva_vypln' => '#C20E1A',
		'ukazatel_barva_bod'   => 'rgba(255,255,255,.55)',
		'ukazatel_barva_aktiv' => '#FF5A50',
		'ukazatel_barva_cislo' => 'rgba(255,255,255,.55)',
		'ukazatel_max_bodu'    => 24,      // nad tímto počtem se body skryjí a zůstane jen čára

		/* --- šipky --- */
		'sipky_zobrazit' => 1,
		'sipky_styl'     => 'kruh',        // sipka | kruh | ctverec
		'sipky_barva'    => '#F4F2F0',
		'sipky_hover'    => '#FF5A50',
		'sipky_velikost' => 44,            // px

		/* --- zavírání --- */
		'zavrit_barva' => '#F4F2F0',
		'zavrit_hover' => '#FF5A50',

		/* --- chování --- */
		'selektory'    => ".roomgallery a, .wp-block-gallery a, .gallery a, [data-lightbox]",
		'smycka'       => 1,
		'klavesnice'   => 1,
		'gesta'        => 1,
		'kolecko'      => 0,
		'predlozit'    => 1,   // přednačíst sousední snímky
		'hash'         => 0,   // zapisovat pořadí do adresy (#foto-3)
		'autoplay'     => 0,
		'autoplay_ms'  => 5000,
		'aktivni'      => 1,   // hlavní vypínač
	);
}

function gflb_get() {
	$ulozene = get_option( GFLB_OPTION, array() );
	if ( ! is_array( $ulozene ) ) $ulozene = array();
	return array_merge( gflb_defaults(), $ulozene );
}

register_activation_hook( __FILE__, function () {
	add_option( GFLB_OPTION, gflb_defaults() );
} );

/* ============================================================================
 * Sestavení CSS hodnoty pozadí
 * ============================================================================ */

/**
 * Vrátí hodnotu pro CSS vlastnost background podle nastaveného typu.
 *
 * „rohy" skládá čtyři radiální přechody nad sebe — jde o nejjednodušší způsob,
 * jak dostat mesh přechod bez knihovny a bez canvasu, a funguje ve všech
 * prohlížečích, kde funguje samotný radial-gradient.
 */
function gflb_pozadi_css( array $n ) {
	$b1 = $n['pozadi_barva1'];
	$b2 = $n['pozadi_barva2'];
	$b3 = $n['pozadi_barva3'];
	/* Prostřední zastávka je záměrně blízko začátku (34 %): u přechodu přes celou
	   úhlopříčku by 50 % znamenalo, že první barva zabere polovinu plochy. Takhle
	   zůstane sytá barva u rohu a zbytek plátna je klidný. */
	$zlom = max( 5, min( 95, (int) $n['pozadi_zlom'] ) );
	$stops = $b3 !== '' ? "$b1 0%, $b3 {$zlom}%, $b2 100%" : "$b1 0%, $b2 100%";

	switch ( $n['pozadi_typ'] ) {
		case 'solid':
			return $b1;
		case 'radial':
			return "radial-gradient(circle at {$n['pozadi_stred']}, $stops)";
		case 'conic':
			return "conic-gradient(from " . (int) $n['pozadi_uhel'] . "deg at {$n['pozadi_stred']}, $stops)";
		case 'rohy':
			return sprintf(
				'radial-gradient(circle at 100%% 0%%, %s 0%%, transparent 62%%),'
				. 'radial-gradient(circle at 0%% 0%%, %s 0%%, transparent 62%%),'
				. 'radial-gradient(circle at 100%% 100%%, %s 0%%, transparent 62%%),'
				. 'radial-gradient(circle at 0%% 100%%, %s 0%%, transparent 62%%), %s',
				$n['pozadi_roh_ph'], $n['pozadi_roh_lh'],
				$n['pozadi_roh_pd'], $n['pozadi_roh_ld'], $n['pozadi_roh_ld']
			);
		case 'linear':
		default:
			return 'linear-gradient(' . (int) $n['pozadi_uhel'] . "deg, $stops)";
	}
}

/** Adresa loga podle nastaveného zdroje; prázdný řetězec = logo se nevykreslí. */
function gflb_logo_url( array $n ) {
	if ( empty( $n['logo_zobrazit'] ) ) return '';
	switch ( $n['logo_zdroj'] ) {
		case 'priloha':
			$u = $n['logo_priloha'] ? wp_get_attachment_image_url( (int) $n['logo_priloha'], 'medium' ) : '';
			return $u ?: '';
		case 'url':
			return esc_url_raw( (string) $n['logo_url'] );
		case 'web':
		default:
			$id = (int) get_theme_mod( 'custom_logo' );
			if ( ! $id ) return '';
			return wp_get_attachment_image_url( $id, 'medium' ) ?: '';
	}
}

/* ============================================================================
 * Frontend — assety a kontejner
 * ============================================================================ */

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'garry-foto-lightbox', GFLB_URL . 'assets/lightbox.css', array(), GFLB_VERSION );
	wp_register_script( 'garry-foto-lightbox', GFLB_URL . 'assets/lightbox.js', array(), GFLB_VERSION, true );

	/* Zařadit se musí tady, ne až ve wp_footer — tam už WordPress frontu
	   vytiskl a styl ani skript by se do stránky nedostaly. */
	if ( ! is_admin() && gflb_je_aktivni() ) {
		wp_enqueue_style( 'garry-foto-lightbox' );
		wp_enqueue_script( 'garry-foto-lightbox' );
	}
}, 5 );

/** Přepínač pro jednotlivou stránku — nastaví ho shortcode [garry_lightbox]. */
function &gflb_prepis() {
	static $prepis = array();
	return $prepis;
}

/**
 * Lightbox se na stránce vykreslí, pokud je zapnutý v nastavení. Vypnout ho
 * jen pro jednu stránku jde shortcodem [garry_lightbox aktivni="0"].
 */
function gflb_je_aktivni() {
	$n = gflb_nastaveni_stranky();
	return ! empty( $n['aktivni'] );
}

/** Nastavení pro aktuální stránku = globální nastavení + případný přepis shortcodem. */
function gflb_nastaveni_stranky() {
	$prepis = gflb_prepis();
	return array_merge( gflb_get(), $prepis );
}

/* Priorita 5, tedy PŘED wp_print_footer_scripts (priorita 20) — jinak by se
   lightbox.js vytiskl dřív než kontejner a při spuštění by ho nenašel. */
add_action( 'wp_footer', function () {
	if ( is_admin() || ! gflb_je_aktivni() ) return;
	gflb_render_kontejner();
}, 5 );

/**
 * Jediný kontejner na stránku. Veškeré nastavení jde do data-atributů, žádný
 * inline <style> ani <script> — hodnoty přepíše JS do CSS custom properties
 * až při otevření. Manifest proto může zůstat u inline_assets false/false.
 */
function gflb_render_kontejner() {
	static $vykresleno = false;
	if ( $vykresleno ) return;
	$vykresleno = true;

	$n = gflb_nastaveni_stranky();
	if ( ! wp_style_is( 'garry-foto-lightbox', 'enqueued' ) ) wp_enqueue_style( 'garry-foto-lightbox' );
	if ( ! wp_script_is( 'garry-foto-lightbox', 'enqueued' ) ) wp_enqueue_script( 'garry-foto-lightbox' );

	$logo = gflb_logo_url( $n );
	$data = array(
		'selektory'      => (string) $n['selektory'],
		'pozadi'         => gflb_pozadi_css( $n ),
		'kryti'          => (int) $n['pozadi_kryti'] / 100,
		'rozostreni'     => (int) $n['pozadi_rozostreni'],
		'logo'           => $logo,
		'logo-pozice'    => (string) $n['logo_pozice'],
		'logo-vyska'     => (int) $n['logo_vyska'],
		'logo-kryti'     => (int) $n['logo_kryti'] / 100,
		'nadpis'         => (int) ! empty( $n['nadpis_zobrazit'] ),
		'sablona'        => (string) $n['nadpis_sablona'],
		'nadpis-zarovnani' => (string) $n['nadpis_zarovnani'],
		'nadpis-barva'   => (string) $n['nadpis_barva'],
		'nadpis-velikost' => (int) $n['nadpis_velikost'],
		'web'            => get_bloginfo( 'name' ),
		'popisek'        => (int) ! empty( $n['popisek_zobrazit'] ),
		'popisek-zdroj'  => (string) $n['popisek_zdroj'],
		'popisek-barva'  => (string) $n['popisek_barva'],
		'popisek-velikost' => (int) $n['popisek_velikost'],
		'ukazatel'       => (int) ! empty( $n['ukazatel_zobrazit'] ),
		'ukazatel-cisla' => (int) ! empty( $n['ukazatel_cisla'] ),
		'u-cara'         => (string) $n['ukazatel_barva_cary'],
		'u-vypln'        => (string) $n['ukazatel_barva_vypln'],
		'u-bod'          => (string) $n['ukazatel_barva_bod'],
		'u-aktiv'        => (string) $n['ukazatel_barva_aktiv'],
		'u-cislo'        => (string) $n['ukazatel_barva_cislo'],
		'u-max'          => (int) $n['ukazatel_max_bodu'],
		'sipky'          => (int) ! empty( $n['sipky_zobrazit'] ),
		'sipky-styl'     => (string) $n['sipky_styl'],
		'sipky-barva'    => (string) $n['sipky_barva'],
		'sipky-hover'    => (string) $n['sipky_hover'],
		'sipky-velikost' => (int) $n['sipky_velikost'],
		'zavrit-barva'   => (string) $n['zavrit_barva'],
		'zavrit-hover'   => (string) $n['zavrit_hover'],
		'smycka'         => (int) ! empty( $n['smycka'] ),
		'klavesnice'     => (int) ! empty( $n['klavesnice'] ),
		'gesta'          => (int) ! empty( $n['gesta'] ),
		'kolecko'        => (int) ! empty( $n['kolecko'] ),
		'predlozit'      => (int) ! empty( $n['predlozit'] ),
		'hash'           => (int) ! empty( $n['hash'] ),
		'autoplay'       => (int) ! empty( $n['autoplay'] ),
		'autoplay-ms'    => max( 1000, (int) $n['autoplay_ms'] ),
	);

	$atributy = '';
	foreach ( $data as $klic => $hodnota ) {
		$atributy .= ' data-' . esc_attr( $klic ) . '="' . esc_attr( (string) $hodnota ) . '"';
	}

	$t = array(
		'zavrit'  => esc_attr__( 'Zavřít', 'garry-foto-lightbox' ),
		'predchozi' => esc_attr__( 'Předchozí snímek', 'garry-foto-lightbox' ),
		'dalsi'   => esc_attr__( 'Další snímek', 'garry-foto-lightbox' ),
		'galerie' => esc_attr__( 'Fotogalerie', 'garry-foto-lightbox' ),
	);
	?>
<div class="garry-lightbox" id="garry-lightbox" role="dialog" aria-modal="true"
     aria-label="<?php echo $t['galerie']; ?>" hidden<?php echo $atributy; // phpcs:ignore -- escapováno výše ?>>
  <div class="glb-pozadi" aria-hidden="true"></div>
  <?php if ( $logo ) : ?>
  <img class="glb-logo" src="<?php echo esc_url( $logo ); ?>" alt="" aria-hidden="true">
  <?php endif; ?>
  <button type="button" class="glb-zavrit" aria-label="<?php echo $t['zavrit']; ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5L5 19"/></svg>
  </button>
  <p class="glb-nadpis" id="glb-nadpis"></p>
  <div class="glb-telo">
    <button type="button" class="glb-sipka glb-sipka--vlevo" aria-label="<?php echo $t['predchozi']; ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 4l-8 8 8 8"/></svg>
    </button>
    <figure class="glb-scena">
      <img class="glb-foto" src="" alt="">
      <figcaption class="glb-popisek"></figcaption>
    </figure>
    <button type="button" class="glb-sipka glb-sipka--vpravo" aria-label="<?php echo $t['dalsi']; ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 4l8 8-8 8"/></svg>
    </button>
  </div>
  <nav class="glb-ukazatel" aria-label="<?php echo $t['galerie']; ?>">
    <span class="glb-cara" aria-hidden="true"></span>
    <span class="glb-vypln" aria-hidden="true"></span>
    <ol class="glb-body"></ol>
  </nav>
</div>
	<?php
}

/* ============================================================================
 * Shortcody
 * ============================================================================ */

/**
 * [garry_lightbox] — přenastaví lightbox pro jednu stránku. Nic nevykresluje.
 *
 * Přijímá stejné klíče jako nastavení, jen s podtržítky, např.
 * [garry_lightbox pozadi_typ="radial" pozadi_barva1="#0af" sipky_zobrazit="0"]
 * Vypnutí na jedné stránce: [garry_lightbox aktivni="0"].
 */
function gflb_sc_lightbox( $atts = array() ) {
	$povolene = array_keys( gflb_defaults() );
	$atts     = shortcode_atts( array_fill_keys( $povolene, null ), (array) $atts, 'garry_lightbox' );
	$prepis   = &gflb_prepis();
	foreach ( $atts as $klic => $hodnota ) {
		if ( $hodnota !== null && $hodnota !== '' ) $prepis[ $klic ] = $hodnota;
	}
	if ( isset( $atts['aktivni'] ) && $atts['aktivni'] !== null ) {
		$prepis['aktivni'] = (int) $atts['aktivni'];
	}
	return '';
}
add_action( 'init', function () { add_shortcode( 'garry_lightbox', 'gflb_sc_lightbox' ); }, 5 );

/**
 * [garry_lightbox_galerie ids="12,13,14" sloupce="3" velikost="large"]
 *
 * Vlastní mřížka náhledů napojená na lightbox. Na rozdíl od cizích galerií tady
 * plugin zná i popisek a popis z knihovny médií, takže volba „doprovodné
 * informace pod fotkou" funguje v plném rozsahu.
 */
function gflb_sc_galerie( $atts = array() ) {
	$a = shortcode_atts( array(
		'ids'      => '',
		'sloupce'  => 3,
		'velikost' => 'large',
		'nahled'   => 'medium_large',
		'mezera'   => 12,
		'skupina'  => '',
	), (array) $atts, 'garry_lightbox_galerie' );

	$ids = array_filter( array_map( 'intval', preg_split( '/[\s,]+/', (string) $a['ids'] ) ) );
	if ( ! $ids ) return '';

	gflb_render_kontejner();

	$skupina = $a['skupina'] !== '' ? sanitize_key( $a['skupina'] ) : 'glb-' . substr( md5( implode( ',', $ids ) ), 0, 8 );
	$sloupce = max( 1, min( 8, (int) $a['sloupce'] ) );
	$mezera  = max( 0, (int) $a['mezera'] );

	ob_start();
	printf(
		'<div class="glb-galerie" data-glb-skupina="%s" style="--glb-sloupce:%d;--glb-mezera:%dpx">',
		esc_attr( $skupina ), $sloupce, $mezera
	);
	foreach ( $ids as $id ) {
		$plna = wp_get_attachment_image_url( $id, $a['velikost'] );
		if ( ! $plna ) continue;
		$priloha = get_post( $id );
		printf(
			'<a href="%s" class="glb-dlazdice" data-lightbox="%s" data-popisek="%s" data-popis="%s" data-titulek="%s">%s</a>',
			esc_url( $plna ),
			esc_attr( $skupina ),
			esc_attr( $priloha ? wp_get_attachment_caption( $id ) : '' ),
			esc_attr( $priloha ? $priloha->post_content : '' ),
			esc_attr( $priloha ? $priloha->post_title : '' ),
			wp_get_attachment_image( $id, $a['nahled'], false, array( 'loading' => 'lazy', 'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ) ) )
		);
	}
	echo '</div>';
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'garry_lightbox_galerie', 'gflb_sc_galerie' ); }, 5 );

/* ============================================================================
 * Registrace do GARRY – GRID Core modul registru (je-li k dispozici).
 * Nepovinné: bez GRID Core plugin funguje beze změny.
 * ============================================================================ */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) return;
	gridhotel_register_module( array(
		'id'         => 'garry-foto-lightbox',
		'name'       => 'Foto lightbox',
		'version'    => GFLB_VERSION,
		'admin_slug' => 'garry-foto-lightbox',
		'capability' => 'manage_options',
		'shortcodes' => array( 'garry_lightbox', 'garry_lightbox_galerie' ),
		'features'   => array( 'lightbox' ),
	) );
} );

/* ============================================================================
 * Elementor — widget se registruje jen když Elementor běží.
 * ============================================================================ */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	require_once GFLB_DIR . 'includes/class-widget-lightbox.php';
	$widgets_manager->register( new \GFLB_Widget_Lightbox() );
} );
add_action( 'elementor/preview/enqueue_scripts', function () {
	wp_enqueue_style( 'garry-foto-lightbox' );
	wp_enqueue_script( 'garry-foto-lightbox' );
} );

/* ============================================================================
 * Divi Builder modul.
 *
 * POUZE DIVI 4. Na Divi 5 by registrace legacy modulu vynutila načtení celého
 * Divi 4 frameworku a přepnula stránky do kompatibilního režimu, což rozbíjí
 * vykreslování — stejné zdůvodnění a stejná strážní funkce jako v ostatních
 * GARRY pluginech. Na Divi 5 zůstává shortcode v modulu Text/Kód, který je
 * plnohodnotná cesta: lightbox se stejně aktivuje sám nad odkazy na obrázky.
 * ============================================================================ */
if ( ! function_exists( 'garry_divi4_legacy_builder' ) ) {
	function garry_divi4_legacy_builder() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) return false;
		if ( class_exists( '\\ET\\Builder\\Framework\\DependencyManagement\\DependencyTree' )
			|| class_exists( '\\ET\\Builder\\Framework\\Utility\\Conditions' ) ) return false;
		$ver = defined( 'ET_BUILDER_PRODUCT_VERSION' ) ? ET_BUILDER_PRODUCT_VERSION
			: ( function_exists( 'et_get_theme_version' ) ? et_get_theme_version() : '' );
		if ( $ver && version_compare( $ver, '5.0-alpha', '>=' ) ) return false;
		return true;
	}
}
add_action( 'et_builder_ready', function () {
	if ( ! garry_divi4_legacy_builder() ) return;
	require_once GFLB_DIR . 'includes/divi4-modul.php';
} );
