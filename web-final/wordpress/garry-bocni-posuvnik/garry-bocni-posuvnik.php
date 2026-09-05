<?php
/**
 * Plugin Name:       GARRY – Sekční navigace
 * Plugin URI:        https://www.garry.cz
 * Description:       Boční navigace mezi sekcemi jedné stránky s automatickým načtením kotev, vlastním pojmenováním a skrytím položek. Vhodná pro dlouhé landing pages, prezentace a obsahové stránky; původně vytvořena pro GRID Hotel jako navigace ve stylu trati.
 * Version:           1.6.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-bocni-posuvnik
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================================
 * GARRY – Boční posuvník (track progress) — per-stránka konfigurace
 * ============================================================================ */

define( 'GARRY_SCR_VER', '1.6.0' );
define( 'GARRY_SCR_OPT', 'garry_scroller' );

/* Aktuální jazyk (Polylang, fallback locale) */
function garry_scr_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
/* mapa shortcode → [anchor, výchozí název (CS/EN/DE), typ] */
function garry_scr_map( $lang = null ) {
	$lang = $lang ?: garry_scr_lang();
	$names = array(
		'cs' => array( 'Okruh','Vstupy','Příběh','Pokoje','Zážitky','Gastro','Sezóna','Firmy','Reference','Rezervace' ),
		'en' => array( 'Circuit','Ways in','Story','Rooms','Experiences','Dining','Season','Business','Reviews','Booking' ),
		'de' => array( 'Ring','Einstiege','Story','Zimmer','Erlebnisse','Gastro','Saison','Firmen','Referenzen','Buchung' ),
	);
	$n = isset( $names[ $lang ] ) ? $names[ $lang ] : $names['cs'];
	return array(
		'grid_hero'      => array( 'start',     $n[0], 'start' ),
		'grid_vstupy'    => array( 'vstupy',    $n[1], 'mid' ),
		'grid_pribeh'    => array( 'pribeh',    $n[2], 'mid' ),
		'grid_rooms'     => array( 'pokoje',    $n[3], 'mid' ),
		'grid_zazitky'   => array( 'zazitky',   $n[4], 'mid' ),
		'grid_gastro'    => array( 'restaurace',$n[5], 'mid' ),
		'grid_season'    => array( 'sezona',    $n[6], 'mid' ),
		'grid_firemni'   => array( 'firemni',   $n[7], 'mid' ),
		'grid_reference' => array( 'duvera',    $n[8], 'mid' ),
		'grid_final'     => array( 'cil',       $n[9], 'cil' ),
	);
}
/**
 * Názvy sekcí, které se na webu opakují napříč stránkami.
 *
 * Obecná detekce níže najde kotvu sekce, ale ne její název. Tenhle slovník
 * dá známým kotvám lidský popisek ve všech třech jazycích; neznámá kotva
 * dostane zkrášlené jméno kotvy a přejmenovat ji jde v nastavení pluginu
 * u konkrétní stránky.
 */
function garry_scr_labels( $lang = null ) {
	$lang = $lang ?: garry_scr_lang();
	$dict = array(
		'cs' => array( 'start'=>'Okruh','vstupy'=>'Vstupy','pribeh'=>'Příběh','pokoje'=>'Pokoje','kategorie'=>'Kategorie','moznosti'=>'Možnosti','zazemi'=>'Zázemí','zazitky'=>'Zážitky','restaurace'=>'Restaurace','catering'=>'Catering','jidelnicek'=>'Jídelníček','sezona'=>'Sezóna','cekaci-list'=>'Čekací list','poukazy'=>'Poukazy','firemni'=>'Firmy','svatby'=>'Svatby','duvera'=>'Reference','cil'=>'Rezervace','kontakt'=>'Kontakt','mapa'=>'Mapa','galerie'=>'Galerie' ),
		'en' => array( 'start'=>'Circuit','vstupy'=>'Ways in','pribeh'=>'Story','pokoje'=>'Rooms','kategorie'=>'Categories','moznosti'=>'Options','zazemi'=>'Facilities','zazitky'=>'Experiences','restaurace'=>'Restaurant','catering'=>'Catering','jidelnicek'=>'Menu','sezona'=>'Season','cekaci-list'=>'Waiting list','poukazy'=>'Vouchers','firemni'=>'Business','svatby'=>'Weddings','duvera'=>'Reviews','cil'=>'Booking','kontakt'=>'Contact','mapa'=>'Map','galerie'=>'Gallery' ),
		'de' => array( 'start'=>'Ring','vstupy'=>'Einstiege','pribeh'=>'Story','pokoje'=>'Zimmer','kategorie'=>'Kategorien','moznosti'=>'Möglichkeiten','zazemi'=>'Ausstattung','zazitky'=>'Erlebnisse','restaurace'=>'Restaurant','catering'=>'Catering','jidelnicek'=>'Speisekarte','sezona'=>'Saison','cekaci-list'=>'Warteliste','poukazy'=>'Gutscheine','firemni'=>'Firmen','svatby'=>'Hochzeiten','duvera'=>'Referenzen','cil'=>'Buchung','kontakt'=>'Kontakt','mapa'=>'Karte','galerie'=>'Galerie' ),
	);
	return isset( $dict[ $lang ] ) ? $dict[ $lang ] : $dict['cs'];
}

/**
 * Obecná detekce sekcí — funguje na KAŽDÉ stránce, ne jen na úvodní.
 *
 * Mapa shortcodů níž zná jen deset sekcí úvodní stránky, takže na
 * podstránkách našla nanejvýš dvě a posuvník se tam nedal smysluplně
 * zapnout. Po granulární přestavbě do Divi 5 modulů je kotva sekce uložená
 * jako vlastní atribut ve tvaru {"name":"id","value":"pokoje"}; starší
 * obsah má syrové id="pokoje". Bereme oba tvary v pořadí, v jakém jsou
 * v obsahu, takže pořadí bodů odpovídá pořadí sekcí na stránce.
 *
 * Poslední sekce webu je vždy finální výzva s kotvou "cil" — dostane typ
 * cíle, první sekce typ startu, ostatní se číslují T1, T2…
 */
function garry_scr_detect_generic( $content, $lang = null ) {
	$lang   = $lang ?: garry_scr_lang();
	$labels = garry_scr_labels( $lang );
	$hits   = array();

	$patterns = array(
		'~"name"\s*:\s*"id"\s*,\s*"value"\s*:\s*"([a-z0-9_-]+)"~i',
		'~\bid=\\?"([a-z0-9_-]+)\\?"~i',
	);
	foreach ( $patterns as $re ) {
		if ( preg_match_all( $re, $content, $m, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $m[1] as $hit ) {
				$id = strtolower( $hit[0] );
				if ( $id === '' ) continue;
				if ( ! isset( $hits[ $id ] ) || $hit[1] < $hits[ $id ] ) $hits[ $id ] = $hit[1];
			}
		}
	}
	if ( ! $hits ) return array();
	asort( $hits );

	$out = array();
	$i   = 0;
	foreach ( array_keys( $hits ) as $id ) {
		$type = ( $id === 'cil' ) ? 'cil' : ( $i === 0 ? 'start' : 'mid' );
		$out[] = array(
			'sc'    => 'generic:' . $id,
			'id'    => $id,
			'label' => isset( $labels[ $id ] ) ? $labels[ $id ] : ucfirst( str_replace( '-', ' ', $id ) ),
			'type'  => $type,
		);
		$i++;
	}
	return $out;
}

/* Detekce sekcí na stránce podle výskytu shortcodů v obsahu (seřazeno dle pozice) */
function garry_scr_detect( $content, $lang = null ) {
	$map = garry_scr_map( $lang );
	$found = array();
	foreach ( $map as $sc => $def ) {
		$needles = array(
			'[' . $sc,                            // shortcode v obsahu
			'id="' . $def[0] . '"',               // kotva v HTML
			'id=\\"' . $def[0] . '\\"',       // kotva v Divi 5 bloku (escapované uvozovky), starší formát:
			                                       // syrové HTML vložené jako jeden textový řetězec do JSON hodnoty
			'id=\\u0022' . $def[0] . '\\u0022', // totéž přes unicode escapes
			// Atomizovaná Divi 5 sekce ukládá id jako vlastní pár klíč/hodnota
			// v poli custom atributů, ne jako řetězec "id=...": {"...,"name":"id",
			// "value":"vstupy",...}. Bez tohohle vzoru se takhle uložené kotvy
			// vůbec nenašly (proto CZ homepage po přestavbě na atomické moduly
			// ukázala jen 3 z 10 sekcí, zatímco EN/DE verze ve starším formátu
			// obsahu byly v pořádku).
			'"name":"id","value":"' . $def[0] . '"',
		);
		foreach ( $needles as $n ) {
			$pos = strpos( $content, $n );
			if ( $pos !== false ) { $found[ $sc ] = min( $pos, $found[ $sc ] ?? PHP_INT_MAX ); }
		}
	}
	asort( $found );
	$out = array();
	foreach ( $found as $sc => $pos ) { $d = $map[ $sc ]; $out[] = array( 'sc' => $sc, 'id' => $d[0], 'label' => $d[1], 'type' => $d[2] ); }
	return $out;
}
/* Kompletní kanonická sada sekcí úvodní stránky (shoduje se s funkčním [grid_tracknav]
 * v child theme). Použije se jako předvyplnění na titulní stránce, když se v Divi
 * obsahu nepodaří shortcody detekovat (obsah je uložen v Divi struktuře). */
function garry_scr_front_sections( $lang = null ) {
	$out = array();
	foreach ( garry_scr_map( $lang ) as $sc => $d ) $out[] = array( 'sc' => $sc, 'id' => $d[0], 'label' => $d[1], 'type' => $d[2] );
	return $out;
}
/* Jazyk konkrétní stránky (Polylang), fallback aktuální jazyk */
function garry_scr_page_lang( $pid ) {
	if ( function_exists( 'pll_get_post_language' ) ) { $l = pll_get_post_language( $pid ); if ( $l ) return $l; }
	return garry_scr_lang();
}
/* Je stránka titulní stránkou NEBO jejím jazykovým překladem (Polylang)? */
function garry_scr_is_front( $pid ) {
	$front = (int) get_option( 'page_on_front' );
	if ( $front === (int) $pid ) return true;
	if ( $front && function_exists( 'pll_get_post_translations' ) ) {
		$tr = pll_get_post_translations( $front );
		if ( in_array( (int) $pid, array_map( 'intval', (array) $tr ), true ) ) return true;
	}
	return false;
}
/* Sekce pro danou stránku: detekce z obsahu; na titulní stránce (vč. mutací) fallback na kanonickou sadu. */
function garry_scr_sections_for( $pid ) {
	$page = get_post( $pid );
	$lang = garry_scr_page_lang( $pid );
	if ( ! $page ) return array();
	/* Na úvodní stránce vždy kanonická sada — její struktura je pevně daná
	 * a po přestavbě do granulárních modulů by detekce z obsahu našla jen
	 * zbytkové shortcody. */
	if ( garry_scr_is_front( $pid ) ) {
		$front = garry_scr_front_sections( $lang );
		if ( $front ) return $front;
	}
	/* Jinak zkusíme obojí a vezmeme úplnější výsledek: mapa známých sekcí
	 * drží zavedené názvy, ale zná jen deset kotev úvodní stránky, takže na
	 * podstránce najde jen ty, které se shodou okolností jmenují stejně
	 * (na Gastronomii například jen „restaurace" a „cíl", i když má sekce
	 * čtyři). Obecná detekce najde všechny skutečné kotvy. */
	$mapped  = garry_scr_detect( $page->post_content, $lang );
	$generic = garry_scr_detect_generic( $page->post_content, $lang );
	return count( $generic ) > count( $mapped ) ? $generic : $mapped;
}
function garry_scr_all() { $o = get_option( GARRY_SCR_OPT, array() ); return is_array( $o ) ? $o : array(); }
function garry_scr_cfg( $pid ) { $a = garry_scr_all(); return isset( $a[ $pid ] ) ? $a[ $pid ] : array(); }

/* Body posuvníku pro stránku (aplikuje labely/skrytí + přečísluje T1.. ) */
function garry_scr_points( $pid ) {
	$page = get_post( $pid ); if ( ! $page ) return array();
	$sections = garry_scr_sections_for( $pid );
	$cfg = garry_scr_cfg( $pid );
	$labels = isset( $cfg['labels'] ) ? $cfg['labels'] : array();
	$hidden = isset( $cfg['hidden'] ) ? $cfg['hidden'] : array();
	$points = array(); $t = 0;
	foreach ( $sections as $s ) {
		if ( ! empty( $hidden[ $s['id'] ] ) ) continue;
		$label = isset( $labels[ $s['id'] ] ) && $labels[ $s['id'] ] !== '' ? $labels[ $s['id'] ] : $s['label'];
		$cil = array( 'cs' => 'CÍL', 'en' => 'FINISH', 'de' => 'ZIEL' );
		$lang = garry_scr_page_lang( $pid );
		if ( $s['type'] === 'start' ) $num = 'START';
		elseif ( $s['type'] === 'cil' ) $num = isset( $cil[ $lang ] ) ? $cil[ $lang ] : 'CÍL';
		else { $t++; $num = 'T' . $t; }
		$points[] = array( 'target' => $s['id'], 'num' => $num, 'name' => $label );
	}
	return $points;
}
/* Má se na této stránce posuvník zobrazit? (výchozí: titulní stránka vč. jazykových mutací) */
function garry_scr_enabled_for( $pid ) {
	$cfg = garry_scr_cfg( $pid );
	if ( isset( $cfg['enabled'] ) ) return ! empty( $cfg['enabled'] );
	return garry_scr_is_front( $pid ); // default jen homepage (CS/EN/DE)
}

/* Registrace do GARRY menu */
require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\BocniPosuvnik\V23\bootstrap( __FILE__, 'garry_scr_admin_page', 'Boční posuvník' );

/* Uložení konfigurace (admin-post) */
add_action( 'admin_post_garry_scr_save', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'garry_scr_save' ) ) wp_die( 'Nedostatečná oprávnění.' );
	$pid = (int) $_POST['pid'];
	$all = garry_scr_all();
	$labels = array(); $hidden = array();
	$defaults = array();
	foreach ( garry_scr_sections_for( $pid ) as $sec ) $defaults[ $sec['id'] ] = $sec['label'];
	if ( ! empty( $_POST['label'] ) && is_array( $_POST['label'] ) ) foreach ( $_POST['label'] as $id => $v ) {
		$id = sanitize_key( $id ); $v = sanitize_text_field( $v );
		if ( $v === '' || ( isset( $defaults[ $id ] ) && $v === $defaults[ $id ] ) ) continue; // default → neukládat
		$labels[ $id ] = $v;
	}
	if ( ! empty( $_POST['hidden'] ) && is_array( $_POST['hidden'] ) ) foreach ( $_POST['hidden'] as $id => $v ) $hidden[ sanitize_key( $id ) ] = 1;
	$all[ $pid ] = array( 'enabled' => empty( $_POST['enabled'] ) ? 0 : 1, 'labels' => $labels, 'hidden' => $hidden );
	update_option( GARRY_SCR_OPT, $all );
	wp_safe_redirect( add_query_arg( array( 'page' => 'garry-bocni-posuvnik', 'gp' => $pid, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* Admin stránka */
function garry_scr_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	$gp = isset( $_GET['gp'] ) ? (int) $_GET['gp'] : ( $pages ? $pages[0]->ID : 0 );
	?>
	<div class="wrap"><h1>Boční posuvník (trať)</h1>
	<?php if ( isset( $_GET['saved'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Uloženo.</p></div>'; ?>
	<p>Pro každou publikovanou stránku můžeš zapnout posuvník, upravit názvy sekcí nebo některé skrýt (čísla se přečíslují).</p>
	<form method="get" style="margin:12px 0"><input type="hidden" name="page" value="garry-bocni-posuvnik">
	  <label>Stránka: <select name="gp" onchange="this.form.submit()">
	    <?php foreach ( $pages as $p ) printf( '<option value="%d" %s>%s</option>', $p->ID, selected( $gp, $p->ID, false ), esc_html( $p->post_title ) ); ?>
	  </select></label>
	</form>
	<?php if ( $gp ) :
		$cfg = garry_scr_cfg( $gp ); $page = get_post( $gp );
		$sections = garry_scr_sections_for( $gp );
		$is_front = ( (int) get_option( 'page_on_front' ) === (int) $gp );
		$labels = isset( $cfg['labels'] ) ? $cfg['labels'] : array();
		$hidden = isset( $cfg['hidden'] ) ? $cfg['hidden'] : array();
		$enabled = garry_scr_enabled_for( $gp );
	?>
	<div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start">
	  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="flex:1;min-width:340px;max-width:560px">
	    <input type="hidden" name="action" value="garry_scr_save"><input type="hidden" name="pid" value="<?php echo (int) $gp; ?>">
	    <?php wp_nonce_field( 'garry_scr_save' ); ?>
	    <p><label><input type="checkbox" name="enabled" value="1" <?php checked( $enabled, true ); ?> id="scr-enabled"> <strong>Zobrazit posuvník na této stránce</strong></label></p>
	    <?php if ( $is_front ) : ?>
	      <p class="description">Toto je <strong>úvodní stránka</strong> — posuvník je předvyplněn kompletní sadou sekcí (START → CÍL) shodnou s funkčním posuvníkem na webu. Názvy uprav nebo sekce skryj dle potřeby.</p>
	    <?php endif; ?>
	    <?php if ( empty( $sections ) ) : ?>
	      <p class="description">Na této stránce nebyly nalezeny žádné sekce GRID (shortcody). Posuvník potřebuje sekce jako <code>[grid_hero]</code>, <code>[grid_rooms]</code>…</p>
	    <?php else : ?>
	      <table class="widefat" style="max-width:560px"><thead><tr><th>Sekce</th><th>Název v posuvníku</th><th>Skrýt</th></tr></thead><tbody>
	        <?php foreach ( $sections as $s ) :
	          $lbl = isset( $labels[ $s['id'] ] ) ? $labels[ $s['id'] ] : $s['label']; ?>
	          <tr>
	            <td><code>#<?php echo esc_html( $s['id'] ); ?></code></td>
	            <td><input type="text" name="label[<?php echo esc_attr( $s['id'] ); ?>]" value="<?php echo esc_attr( $lbl ); ?>" class="regular-text scr-label" data-id="<?php echo esc_attr( $s['id'] ); ?>" data-type="<?php echo esc_attr( $s['type'] ); ?>"></td>
	            <td style="text-align:center"><input type="checkbox" name="hidden[<?php echo esc_attr( $s['id'] ); ?>]" value="1" <?php checked( ! empty( $hidden[ $s['id'] ] ), true ); ?> class="scr-hide" data-id="<?php echo esc_attr( $s['id'] ); ?>"></td>
	          </tr>
	        <?php endforeach; ?>
	      </tbody></table>
	    <?php endif; ?>
	    <?php submit_button( 'Uložit posuvník' ); ?>
	  </form>
	  <div style="flex:0 0 240px">
	    <p style="font-weight:600;margin:0 0 8px">Živý náhled</p>
	    <div style="background:#0d0f12;border:1px solid #ccd0d4;border-radius:8px;padding:26px 18px;min-height:360px">
	      <div style="font-family:monospace;font-size:10px;letter-spacing:.18em;color:#8b8884;text-transform:uppercase;margin-bottom:14px">Masaryk Circuit</div>
	      <div id="scr-preview" style="position:relative;padding-left:22px"></div>
	    </div>
	  </div>
	</div>
	<script>
	(function(){
	  var prev=document.getElementById('scr-preview'); if(!prev)return;
	  function build(){
	    var rows=[].slice.call(document.querySelectorAll('.scr-label'));
	    var t=0, html='<div style="position:absolute;left:5px;top:4px;bottom:4px;width:2px;background:rgba(150,150,150,.4)"></div>';
	    rows.forEach(function(inp){
	      var id=inp.getAttribute('data-id'), type=inp.getAttribute('data-type');
	      var hide=document.querySelector('.scr-hide[data-id="'+id+'"]');
	      if(hide&&hide.checked) return;
	      var num = type==='start'?'START':(type==='cil'?'CÍL':'T'+(++t));
	      html+='<div style="display:flex;align-items:center;gap:10px;margin:0 0 18px;position:relative">'+
	        '<span style="width:11px;height:11px;border-radius:50%;border:2px solid #7A797B;background:#0d0f12;position:relative;left:-1px;flex:none"></span>'+
	        '<span style="line-height:1.1"><span style="display:block;font-family:monospace;font-size:9px;letter-spacing:.15em;color:#caa75f">'+num+'</span>'+
	        '<span style="display:block;font-family:sans-serif;font-size:12px;text-transform:uppercase;color:#e6e4e2">'+(inp.value||'—')+'</span></span></div>';
	    });
	    prev.innerHTML=html;
	    prev.style.opacity=(document.getElementById('scr-enabled')&&document.getElementById('scr-enabled').checked)?'1':'.3';
	  }
	  document.querySelectorAll('.scr-label,.scr-hide,#scr-enabled').forEach(function(el){el.addEventListener('input',build);el.addEventListener('change',build);});
	  build();
	})();
	</script>
	<?php endif; ?>
	</div>
	<?php
}

/**
 * ============================================================================
 * Fáze 8 GRID Suite refaktoringu (GRID-SUITE-08) — plugin dřív jen vypnul
 * theme [grid_tracknav] a vykreslil vlastní HTML, ale funkční JS/CSS
 * (scroll-spy, aktivní stav, smooth scroll) dodával pořád child theme
 * (grid.js). To porušovalo standalone použití — bez theme by posuvník
 * vůbec nereagoval na scroll/klik a nebyl by ani ovladatelný klávesnicí,
 * protože body neměla href (jen data-target). Teď plugin vlastní kompletní
 * markup (skutečné <a href="#id">, funguje i bez JS), CSS i JS sám.
 * ============================================================================
 */

/* Frontend: plugin řídí posuvník — child [grid_tracknav] vypneme a vykreslíme vlastní */
add_action( 'init', function () { add_shortcode( 'grid_tracknav', '__return_empty_string' ); }, 30 );
add_action( 'init', function () {
	add_shortcode( 'garry_section_navigation', function ( $atts = array() ) {
		$pid = get_queried_object_id();
		return garry_section_nav_render( array( 'pid' => $pid ) );
	} );
}, 5 );

/**
 * Veřejné API (GRID-SUITE-08 §4).
 * @param array $args { pid?: int }
 * @return string
 */
function garry_section_nav_render( array $args = array() ) {
	$pid = isset( $args['pid'] ) ? (int) $args['pid'] : get_queried_object_id();
	if ( ! $pid || ! garry_scr_enabled_for( $pid ) ) {
		return '';
	}
	$points = garry_scr_points( $pid );
	if ( count( $points ) < 2 ) {
		return ''; // spec §4: méně než 2 platné sekce = prázdný string
	}
	garry_scr_mark_rendered();

	$aria = array( 'cs' => 'Postup po stránce', 'en' => 'Page progress', 'de' => 'Seitenfortschritt' );
	$al   = garry_scr_lang();
	ob_start(); ?>
	<nav class="track-progress" aria-label="<?php echo esc_attr( isset( $aria[ $al ] ) ? $aria[ $al ] : $aria['cs'] ); ?>">
	  <div class="tp-inner">
	    <span class="tp-label" aria-hidden="true">Masaryk Circuit</span>
	    <div class="tp-rail" aria-hidden="true"></div><div class="tp-fill" aria-hidden="true"></div>
	    <ul class="tp-points">
	      <?php foreach ( $points as $p ) : ?>
	      <li><a class="tp-point" href="#<?php echo esc_attr( $p['target'] ); ?>" data-target="<?php echo esc_attr( $p['target'] ); ?>"><span class="tp-dot" aria-hidden="true"></span><span class="tp-text"><span class="tp-num" aria-hidden="true"><?php echo esc_html( $p['num'] ); ?></span><span class="tp-name"><?php echo esc_html( $p['name'] ); ?></span></span></a></li>
	      <?php endforeach; ?>
	    </ul>
	  </div>
	</nav>
	<?php return ob_get_clean();
}
/** Parsuje bezpečný `items` atribut shortcode ("id|Label,id2|Label2") — GRID-SUITE-08 §4, žádné PHP/JSON vyhodnocování vstupu. */
function garry_section_nav_parse_items( $source ) {
	$out = array();
	foreach ( explode( ',', (string) $source ) as $chunk ) {
		$parts = explode( '|', trim( $chunk ), 2 );
		$id = sanitize_html_class( trim( $parts[0] ?? '' ) );
		if ( '' === $id ) continue;
		$out[] = array( 'target' => $id, 'name' => sanitize_text_field( trim( $parts[1] ?? $id ) ) );
	}
	return $out;
}

function garry_scr_mark_rendered() {
	$GLOBALS['garry_scr_rendered'] = true;
}

/**
 * Třída na <body>, když se na této stránce boční navigace skutečně vykreslí.
 *
 * Bez ní musel child theme rezervovat pravý prostor přes ručně udržovaný
 * seznam desítek `body.page-id-NNN` selektorů. Ten byl dvojnásobně špatně:
 * musel se ručně doplňovat u každé nové stránky, a hlavně rezervoval místo
 * i tam, kde se lišta nikdy nevykreslila (dnes běží jen na úvodní stránce),
 * takže těm stránkám ubíral až 245 px obsahu.
 *
 * Podmínka musí zrcadlit skutečné vykreslení ve wp_footer níže: zapnuto pro
 * stránku A alespoň dva platné body. body_class se volá dřív než wp_footer,
 * proto se počítá znovu, ne z $GLOBALS.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( is_admin() || ! is_page() ) return $classes;
	$pid = get_queried_object_id();
	if ( $pid && garry_scr_enabled_for( $pid ) && count( garry_scr_points( $pid ) ) >= 2 ) {
		$classes[] = 'has-section-nav';
	}
	return $classes;
} );
add_action( 'wp_footer', function () {
	if ( is_admin() || ! is_page() ) return;
	$pid = get_queried_object_id();
	echo garry_section_nav_render( array( 'pid' => $pid ) );
}, 15 );

/**
 * Assety jen když se posuvník skutečně vykreslil (spec §11). V okamžiku
 * wp_enqueue_scripts (běžný enqueue hook) ještě nevíme, jestli ho wp_footer
 * o pár řádků výš (priorita 15) vůbec vykreslí — proto enqueue přímo tady,
 * z wp_footer callbacku s vyšší prioritou (16, tedy až PO vykreslení výše).
 */
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['garry_scr_rendered'] ) ) return;
	wp_enqueue_style( 'garry-section-nav', plugins_url( 'assets/section-nav.css', __FILE__ ), array(), GARRY_SCR_VER );
	wp_enqueue_script( 'garry-section-nav', plugins_url( 'assets/section-nav.js', __FILE__ ), array(), GARRY_SCR_VER, true );
}, 16 ); // po vykreslení (priorita 15) výše, aby $GLOBALS['garry_scr_rendered'] byl už nastavený

/**
 * Registrace do GARRY – GRID Core modul registru (GRID-SUITE-08 §9).
 */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) {
		return;
	}
	gridhotel_register_module( array(
		'id'         => 'garry-bocni-posuvnik',
		'name'       => 'Sekční navigace',
		'version'    => defined( 'GARRY_SCR_VER' ) ? GARRY_SCR_VER : '1.4.0',
		'admin_slug' => 'garry-bocni-posuvnik',
		'capability' => 'manage_options',
		'shortcodes' => array( 'grid_tracknav', 'garry_section_navigation' ),
		'features'   => array( 'section_navigation' ),
	) );
} );
