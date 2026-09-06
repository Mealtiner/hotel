<?php
/**
 * Plugin Name:       GARRY – Informační bublina 2
 * Plugin URI:        https://www.garry.cz
 * Description:       Vyskakovací informační bublina s vlastním obsahem pro každou stránku ve třech jazycích. Pět šablon (čtverec s textem, obdélníky na šířku i na výšku s fotkou), fotka z knihovny médií. Vzhled se nastavuje v GARRY nastavení, obsah stránek v GRID Nastavení.
 * Version:           2.0.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-informacni-bublina-2
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GBUB_VERSION', '2.0.0' );
define( 'GBUB_FILE', __FILE__ );
define( 'GBUB_DIR', plugin_dir_path( __FILE__ ) );
define( 'GBUB_URL', plugin_dir_url( __FILE__ ) );
define( 'GBUB_VZHLED', 'garry_bublina2_vzhled' );   // design pro celý web
define( 'GBUB_STRANKY', 'garry_bublina2_stranky' ); // obsah po stránkách

/**
 * Granulární capability pro personál — stejný vzor jako ostatní GRID pluginy.
 * Musí být definovaná PŘED bootstrap(), deskriptor frameworku ji čte hned.
 */
define( 'GBUB_STAFF_CAP', 'garry_grid_manage_informacni_bublina' );

require_once __DIR__ . '/includes/vzhled.php';
require_once __DIR__ . '/includes/stranky.php';

require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\Bublina2\V23\bootstrap( __FILE__, 'gbub_admin_vzhled', 'Informační bublina' );

/* ============================================================================
 * Aktivace — capability pro administrátora, výchozí nastavení
 * ============================================================================ */
function gbub_pridej_cap() {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GBUB_STAFF_CAP ) ) $role->add_cap( GBUB_STAFF_CAP );
}
register_activation_hook( __FILE__, function () {
	gbub_pridej_cap();
	add_option( GBUB_VZHLED, gbub_vzhled_defaults() );
	add_option( GBUB_STRANKY, array() );
} );
/* Přepsání souborů pluginu aktivační hook nespustí — bez tohohle by administrátor
   novou capabilitu nikdy nedostal a položka by mu z GRID Nastavení zmizela. */
add_action( 'admin_init', 'gbub_pridej_cap' );

/* ============================================================================
 * Šablony
 * ============================================================================ */

/**
 * Pět šablon bubliny. Na mobilu se obě šířkové varianty překlápějí do výškových
 * (2 → 4, 3 → 5) — vedle sebe by na úzké obrazovce zbylo na text i fotku příliš
 * málo místa.
 */
function gbub_sablony() {
	return array(
		'ctverec' => array(
			'nazev'  => 'Čtverec — jen text',
			'popis'  => 'Bez fotky. Nejmenší varianta, hodí se na krátké oznámení.',
			'foto'   => false,
			'smer'   => 'sloupec',
			'mobil'  => 'ctverec',
		),
		'foto-vlevo' => array(
			'nazev'  => 'Na šířku — vlevo fotka, vpravo text',
			'popis'  => 'Na mobilu se překlopí na „nahoře text, dole fotka".',
			'foto'   => true,
			'smer'   => 'radek',
			'mobil'  => 'foto-dole',
		),
		'foto-vpravo' => array(
			'nazev'  => 'Na šířku — vlevo text, vpravo fotka',
			'popis'  => 'Na mobilu se překlopí na „nahoře fotka, dole text".',
			'foto'   => true,
			'smer'   => 'radek-obracene',
			'mobil'  => 'foto-nahore',
		),
		'foto-dole' => array(
			'nazev'  => 'Na výšku — nahoře text, dole fotka',
			'popis'  => 'Stejné na všech šířkách.',
			'foto'   => true,
			'smer'   => 'sloupec-obraceny',
			'mobil'  => 'foto-dole',
		),
		'foto-nahore' => array(
			'nazev'  => 'Na výšku — nahoře fotka, dole text',
			'popis'  => 'Stejné na všech šířkách.',
			'foto'   => true,
			'smer'   => 'sloupec',
			'mobil'  => 'foto-nahore',
		),
	);
}

function gbub_sablona( $klic ) {
	$vse = gbub_sablony();
	return $vse[ $klic ] ?? $vse['ctverec'];
}

/* ============================================================================
 * Frontend
 * ============================================================================ */

add_action( 'wp_enqueue_scripts', function () {
	wp_register_style( 'garry-bublina2', GBUB_URL . 'assets/bublina.css', array(), GBUB_VERSION );
	wp_register_script( 'garry-bublina2', GBUB_URL . 'assets/bublina.js', array(), GBUB_VERSION, true );
}, 5 );

/**
 * Bublina se vykresluje do patičky s prioritou 5, tedy před tiskem skriptů
 * (wp_print_footer_scripts na prioritě 20) — jinak by bublina.js při spuštění
 * nenašel prvek, na který se má navázat.
 */
add_action( 'wp_footer', 'gbub_render', 5 );

function gbub_render() {
	if ( is_admin() ) return;
	$v = gbub_vzhled();
	if ( empty( $v['aktivni'] ) ) return;

	$z = gbub_zaznam_pro_stranku();
	if ( ! $z ) return;

	$suf    = gbub_suffix();
	/* Když mutace text nemá, spadneme na češtinu — prázdná bublina by byla horší
	   než bublina v jiném jazyce, a redakce si toho na webu hned všimne. */
	$vyber = function ( $zaklad ) use ( $z, $suf ) {
		$h = (string) ( $z[ $zaklad . '_' . $suf ] ?? '' );
		return $h !== '' ? $h : (string) ( $z[ $zaklad . '_cz' ] ?? '' );
	};
	$nadpis   = $vyber( 'nadpis' );
	$text     = $vyber( 'text' );
	$tlacitko = $vyber( 'tlacitko' );
	$odkaz    = (string) ( $z['odkaz'] ?? '' );

	$sablona_klic = (string) ( $z['sablona'] ?? 'ctverec' );
	$sablona      = gbub_sablona( $sablona_klic );
	$obrazek      = (int) ( $z['obrazek'] ?? 0 );
	$ma_foto      = $sablona['foto'] && $obrazek > 0;

	if ( $nadpis === '' && trim( wp_strip_all_tags( $text ) ) === '' && ! $ma_foto ) return;

	wp_enqueue_style( 'garry-bublina2' );
	wp_enqueue_script( 'garry-bublina2' );

	/* Klíč pro zapamatování zavření: mění se s obsahem, takže po úpravě textu
	   se bublina ukáže znovu i tomu, kdo ji už jednou zavřel. */
	$otisk = substr( md5( $nadpis . '|' . $text . '|' . $obrazek . '|' . $sablona_klic ), 0, 10 );

	$data = array(
		'sablona'      => $sablona_klic,
		'sablona-mobil' => $sablona['mobil'],
		'smer'         => $sablona['smer'],
		'pozice'       => $v['pozice'],
		'animace'      => $v['animace'],
		'zpozdeni'     => (int) $v['zpozdeni'],
		'auto-zavrit'  => (int) $v['auto_zavrit'],
		'cetnost'      => $v['cetnost'],
		'cetnost-dny'  => (int) $v['cetnost_dny'],
		'otisk'        => $otisk,
		'prekryv'      => (int) ! empty( $v['preklryt'] ),
		'sirka'        => $v['sirka'],
		'sirka-mobil'  => $v['sirka_mobil'],
		'podil-fotky'  => (int) $v['podil_fotky'],
		'zaobleni'     => (int) $v['zaobleni'],
		'odsazeni'     => (int) $v['odsazeni'],
		'odstup'       => (int) $v['odstup'],
		'odstup-mobil' => (int) $v['odstup_mobil'],
		'barva-pozadi' => $v['barva_pozadi'],
		'barva-nadpisu' => $v['barva_nadpisu'],
		'barva-textu'  => $v['barva_textu'],
		'barva-ramecku' => $v['barva_ramecku'],
		'barva-krizku' => $v['barva_krizku'],
		'barva-krizku-hover' => $v['barva_krizku_hover'],
		'barva-stinu'  => $v['barva_stinu'],
		'barva-prekryvu' => $v['barva_prekryvu'],
		'velikost-nadpisu' => $v['velikost_nadpisu'],
		'velikost-nadpisu-mobil' => $v['velikost_nadpisu_mobil'],
		'velikost-textu' => $v['velikost_textu'],
		'velikost-textu-mobil' => $v['velikost_textu_mobil'],
	);
	$atributy = '';
	foreach ( $data as $k => $h ) {
		$atributy .= ' data-' . esc_attr( $k ) . '="' . esc_attr( (string) $h ) . '"';
	}

	$zavrit = array( 'cz' => 'Zavřít', 'en' => 'Close', 'de' => 'Schließen' )[ $suf ];
	?>
<div class="garry-bublina" id="garry-bublina" role="dialog" aria-modal="false"
     aria-labelledby="<?php echo $nadpis !== '' ? 'garry-bublina-nadpis' : ''; ?>" hidden<?php
     echo $atributy; // phpcs:ignore -- escapováno výše ?>>
  <?php if ( ! empty( $v['preklryt'] ) ) : ?><div class="gbub-prekryv" aria-hidden="true"></div><?php endif; ?>
  <div class="gbub-ram">
    <?php if ( $ma_foto ) : ?>
      <div class="gbub-foto"><?php echo wp_get_attachment_image( $obrazek, 'large', false, array(
        'alt' => esc_attr( $nadpis ), 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div>
    <?php endif; ?>
    <div class="gbub-telo">
      <?php if ( $nadpis !== '' ) : ?>
        <p class="gbub-nadpis" id="garry-bublina-nadpis"><?php echo esc_html( $nadpis ); ?></p>
      <?php endif; ?>
      <?php if ( trim( wp_strip_all_tags( $text ) ) !== '' ) : ?>
        <div class="gbub-text"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
      <?php endif; ?>
      <?php if ( $tlacitko !== '' && $odkaz !== '' ) : ?>
        <a class="gbub-tlacitko" href="<?php echo esc_url( $odkaz ); ?>"><?php echo esc_html( $tlacitko ); ?></a>
      <?php endif; ?>
    </div>
    <button type="button" class="gbub-zavrit" aria-label="<?php echo esc_attr( $zavrit ); ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5L5 19"/></svg>
    </button>
  </div>
</div>
	<?php
}

/* ============================================================================
 * Registrace do GARRY – GRID Core modul registru (nepovinné)
 * ============================================================================ */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) return;
	gridhotel_register_module( array(
		'id'         => 'garry-informacni-bublina-2',
		'name'       => 'Informační bublina',
		'version'    => GBUB_VERSION,
		'admin_slug' => 'garry-informacni-bublina-obsah',
		'capability' => GBUB_STAFF_CAP,
		'shortcodes' => array(),
		'features'   => array( 'page_notice' ),
	) );
} );
