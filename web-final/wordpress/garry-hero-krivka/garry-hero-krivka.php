<?php
/**
 * Plugin Name:       GARRY – Animovaná hero křivka
 * Plugin URI:        https://www.garry.cz
 * Description:       Přidá do úvodní hero sekce animovanou dekorativní křivku s nastavením zobrazení, tloušťky a rychlosti animace. Je vhodná pro značkové landing pages; původně vytvořena pro GRID Hotel jako motiv trati.
 * Version:           1.3.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-hero-krivka
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================================
 * GARRY – Hero křivka trati (animovaná červená křivka v hero sekci)
 * ============================================================================ */

define( 'GARRY_HK_VER', '1.3.0' );
define( 'GARRY_HK_OPT', 'garry_hk_settings' );
define( 'GARRY_HK_DEFAULT_COLOR', '#C20E1A' ); // GRID červená (shodná s --red v style.css)

function garry_hk_defaults() {
	return array( 'enabled' => 1, 'thickness' => 3, 'speed' => 2.6, 'color' => GARRY_HK_DEFAULT_COLOR );
}
function garry_hk_get() {
	$o = get_option( GARRY_HK_OPT, array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), garry_hk_defaults() );
}

require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\HeroKrivka\V23\bootstrap( __FILE__, 'garry_hk_admin_page', 'Hero křivka' );

add_action( 'admin_init', function () { register_setting( 'garry_hk_group', GARRY_HK_OPT, 'garry_hk_sanitize' ); } );
function garry_hk_sanitize( $in ) {
	$o = array();
	$o['enabled']   = empty( $in['enabled'] ) ? 0 : 1;
	$o['thickness'] = max( 1, min( 12, (float) ( $in['thickness'] ?? 3 ) ) );
	$o['speed']     = max( 0, min( 15, (float) ( $in['speed'] ?? 2.6 ) ) );
	$color          = isset( $in['color'] ) ? sanitize_hex_color( $in['color'] ) : '';
	$o['color']     = $color ? $color : GARRY_HK_DEFAULT_COLOR;
	return $o;
}

function garry_hk_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$s = garry_hk_get();
	$name = function ( $k ) { return esc_attr( GARRY_HK_OPT ) . '[' . esc_attr( $k ) . ']'; };
	?>
	<div class="wrap"><h1>Hero křivka trati</h1>
	<p>Animovaná červená křivka na pozadí úvodní hero sekce, která se po načtení stránky postupně „projede" zleva doprava.</p>
	<div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start">
	  <form method="post" action="options.php" style="flex:1;min-width:320px;max-width:520px">
	    <?php settings_fields( 'garry_hk_group' ); ?>
	    <table class="form-table"><tbody>
	      <tr><th>Zobrazit křivku</th><td><label><input type="checkbox" name="<?php echo $name('enabled'); ?>" value="1" <?php checked($s['enabled'],1); ?> data-hk="enabled"> Zapnout na úvodní stránce</label></td></tr>
	      <tr><th>Barva křivky</th><td><input type="color" name="<?php echo $name('color'); ?>" value="<?php echo esc_attr($s['color']); ?>" data-hk="color"> <span class="description">Výchozí je GRID červená (<?php echo esc_html( GARRY_HK_DEFAULT_COLOR ); ?>).</span></td></tr>
	      <tr><th>Tloušťka křivky</th><td><input type="range" min="1" max="12" step="0.5" name="<?php echo $name('thickness'); ?>" value="<?php echo esc_attr($s['thickness']); ?>" data-hk="thickness" style="width:70%"> <b id="hk-th"><?php echo esc_html($s['thickness']); ?></b> px</td></tr>
	      <tr><th>Rychlost průjezdu</th><td><input type="range" min="0" max="15" step="0.1" name="<?php echo $name('speed'); ?>" value="<?php echo esc_attr($s['speed']); ?>" data-hk="speed" style="width:70%"> <b id="hk-sp"><?php echo esc_html($s['speed']); ?></b> s<p class="description">Doba postupného vykreslení. 0 = ihned. Doporučeno 2–5 s, ať to není jen půlsekunda.</p></td></tr>
	    </tbody></table>
	    <?php submit_button( 'Uložit' ); ?>
	  </form>
	  <div style="flex:0 0 360px">
	    <p style="font-weight:600;margin:0 0 8px">Živý náhled <button type="button" class="button" id="hk-replay">Přehrát znovu</button></p>
	    <div style="background:#0d0f12;border:1px solid #ccd0d4;border-radius:8px;overflow:hidden">
	      <svg id="hk-svg" viewBox="0 0 1000 560" preserveAspectRatio="none" style="width:100%;height:230px;display:block">
	        <path id="hk-path" d="M-20 420 C 200 380, 240 250, 430 250 S 720 340, 820 250 S 900 120, 1040 160" fill="none" stroke="<?php echo esc_attr($s['color']); ?>" stroke-width="3" stroke-linecap="round"/>
	      </svg>
	    </div>
	    <p class="description" style="margin-top:8px">Náhled odpovídá nastavení; na webu je křivka přes celou hero fotku.</p>
	  </div>
	</div></div>
	<script>
	(function(){
	  var q=function(s){return document.querySelector('[data-hk="'+s+'"]');};
	  var path=document.getElementById('hk-path'), en=q('enabled'), th=q('thickness'), sp=q('speed'), co=q('color');
	  var thV=document.getElementById('hk-th'), spV=document.getElementById('hk-sp');
	  function play(){
	    if(!path)return; var len=path.getTotalLength();
	    path.style.strokeWidth=th.value;
	    path.style.stroke=co.value;
	    path.style.opacity=en.checked?'1':'.15';
	    thV.textContent=th.value; spV.textContent=sp.value;
	    path.style.transition='none'; path.style.strokeDasharray=len; path.style.strokeDashoffset=len;
	    // force reflow
	    path.getBoundingClientRect();
	    var dur=parseFloat(sp.value)||0;
	    path.style.transition='stroke-dashoffset '+dur+'s cubic-bezier(.16,1,.3,1)';
	    requestAnimationFrame(function(){requestAnimationFrame(function(){ path.style.strokeDashoffset=0; });});
	  }
	  [en,th,sp,co].forEach(function(el){ el.addEventListener('input',play); el.addEventListener('change',play); });
	  var rb=document.getElementById('hk-replay'); if(rb) rb.addEventListener('click',play);
	  play();
	})();
	</script>
	<?php
}

/**
 * ============================================================================
 * Fáze 7 GRID Suite refaktoringu (GRID-SUITE-07) — plugin dřív jen publikoval
 * window.gridHeroCurve a skutečné vykreslení/animaci dělal child theme
 * (grid.js, element #trackLine uvnitř theme hero markupu). To porušovalo
 * standalone použití (bez theme by se nic nezobrazilo). Teď plugin vlastní
 * celý render + animaci sám.
 * ============================================================================
 */

/**
 * @param array $atts Zatím bez veřejných atributů (tvar je interní whitelisted
 *        preset, ne libovolný vstup — spec §3 zakazuje libovolný SVG
 *        markup/atributy). Barva je nastavitelná, ale jen z nastavení pluginu
 *        (hex, sanitizováno přes sanitize_hex_color()), ne z shortcode atts.
 * @return array {enabled, thickness, speed, color}
 */
function garry_hero_curve_get_config( array $atts = array() ) {
	$s = garry_hk_get();
	return array(
		'enabled'   => ! empty( $s['enabled'] ),
		'thickness' => (float) $s['thickness'],
		'speed'     => (float) $s['speed'],
		'color'     => $s['color'],
	);
}

/**
 * Vykreslí dekorativní SVG křivku. Whitelisted preset path (stejný tvar jako
 * dřív v theme) — žádný vstup uživatele se do SVG markupu nedostane.
 * Podporuje víc instancí na stránce (unikátní ID), je dekorativní
 * (aria-hidden, focusable="false") a nic nevykreslí, když je enabled=0.
 *
 * @param array $atts
 * @return string
 */
function garry_hero_curve_render( array $atts = array() ) {
	static $instance = 0;
	$cfg = garry_hero_curve_get_config( $atts );
	if ( ! $cfg['enabled'] ) {
		return '';
	}
	$instance++;
	$id = 'garry-hero-curve-' . $instance;
	garry_hero_curve_mark_rendered();
	// Whitelisted preset — jediný dnes podporovaný tvar (stejný jako dřív v theme).
	$path_d = 'M-20 720 C 200 660, 240 470, 430 470 S 720 600, 820 460 S 900 220, 1040 280';

	ob_start(); ?>
	<div class="garry-hero-curve" id="<?php echo esc_attr( $id ); ?>" data-thickness="<?php echo esc_attr( $cfg['thickness'] ); ?>" data-speed="<?php echo esc_attr( $cfg['speed'] ); ?>">
	  <svg class="garry-hero-curve-svg" viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-hidden="true" focusable="false">
	    <path class="garry-hero-curve-path" d="<?php echo esc_attr( $path_d ); ?>" fill="none" stroke="<?php echo esc_attr( $cfg['color'] ); ?>" style="stroke:<?php echo esc_attr( $cfg['color'] ); ?>" stroke-width="<?php echo esc_attr( $cfg['thickness'] ); ?>" stroke-linecap="round"/>
	  </svg>
	</div>
	<?php return ob_get_clean();
}
add_action( 'init', function () {
	add_shortcode( 'garry_hero_curve', function ( $atts = array() ) {
		return garry_hero_curve_render( (array) $atts );
	} );
}, 5 );

/** Enqueue jen když se komponenta skutečně vykreslila (statická proměnná výše nefunguje napříč requesty, proto přímý flag). */
function garry_hero_curve_mark_rendered() {
	$GLOBALS['garry_hero_curve_rendered'] = true;
}
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['garry_hero_curve_rendered'] ) ) {
		return;
	}
	wp_enqueue_script(
		'garry-hero-curve',
		plugins_url( 'assets/hero-curve.js', __FILE__ ),
		array(),
		GARRY_HK_VER,
		true
	);
}, 5 );

/**
 * Dočasný, needitovatelný compatibility export pro starý theme kód, který by
 * ještě mohl číst window.gridHeroCurve přímo (spec §5 — deprecated, ne
 * primární bootstrap; skutečné vykreslení dělá assets/hero-curve.js výše).
 * Theme's #trackLine element už v Components šabloně [grid_hero] neexistuje
 * (nahrazen tímhle vlastním rendererem), takže starý theme JS je od téhle
 * verze fakticky neškodně neaktivní (žádný element k animaci) — bezpečné k
 * odstranění z theme v poslední fázi refaktoringu (GRID-SUITE-09).
 */
add_action( 'wp_head', function () {
	if ( is_admin() ) return;
	$cfg = garry_hero_curve_get_config();
	echo '<script>window.gridHeroCurve=' . wp_json_encode( $cfg ) . ';</script>' . "\n";
}, 5 );

/**
 * Registrace do GARRY – GRID Core modul registru (GRID-SUITE-07 §8).
 */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) {
		return;
	}
	gridhotel_register_module( array(
		'id'         => 'garry-hero-krivka',
		'name'       => 'Hero křivka',
		'version'    => defined( 'GARRY_HK_VER' ) ? GARRY_HK_VER : '1.2.0',
		'admin_slug' => 'garry-hero-krivka',
		'capability' => 'manage_options',
		'shortcodes' => array( 'garry_hero_curve' ),
		'features'   => array( 'hero_curve' ),
	) );
} );
