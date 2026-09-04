<?php
/**
 * Plugin Name:       GARRY – Lokální počasí a provozní stav
 * Plugin URI:        https://www.garry.cz
 * Description:       Plovoucí widget s lokálním počasím, ručním provozním stavem a volitelným odhadem podmínek z dat Open-Meteo podle nastavené GPS polohy. Vhodný pro sportoviště, areály a venkovní provozy; původně vytvořen pro GRID Hotel.
 * Version:           1.5.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-situace-na-trati
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;



/* ============================================================================
 * GARRY – Situace na trati (widget) — živá data z Open-Meteo dle GPS
 * ============================================================================ */

define( 'GARRY_SIT_VER', '1.5.0' );
define( 'GARRY_SIT_OPT', 'garry_sit_settings' );
/**
 * Fáze 6 GRID Suite refaktoringu — dřív tenhle plugin neměl žádnou vlastní
 * capability ani GRID integraci (viz GRID-SUITE-06 §7/§10). Stejný
 * SEC-SELF-001 vzor jako u ostatních GARRY pluginů: admin má přístup vždy,
 * komukoli dalšímu se přidělí přes „Oprávnění personálu" v GARRY – GRID Core.
 */
define( 'GARRY_SIT_STAFF_CAP', 'garry_grid_manage_situace_na_trati' );

/* Dostupné údaje (klíč => [popisek v adminu, popisek na webu, výchozí zapnuto]) */
function garry_sit_fields() {
	return array(
		'status'    => array( 'Status (ruční text)',              'STATUS',       1 ),
		'cas'       => array( 'Místní čas',                       'MÍSTNÍ ČAS',   1 ),
		'teplota'   => array( 'Teplota',                          'TEPLOTA',      1 ),
		'pocitova'  => array( 'Pocitová teplota',                 'POCITOVÁ',     0 ),
		'vlhkost'   => array( 'Vlhkost',                          'VLHKOST',      0 ),
		'oblacnost' => array( 'Oblačnost',                        'OBLAČNOST',    0 ),
		'srazky'    => array( 'Srážky',                           'SRÁŽKY',       0 ),
		'vitr'      => array( 'Vítr',                             'VÍTR',         0 ),
		'popis'     => array( 'Slovní popis počasí',              'POČASÍ',       1 ),
		'povrch'    => array( 'Povrch trati (automatický odhad)', 'POVRCH TRATI', 1 ),
	);
}
/* Aktuální jazyk (Polylang, fallback locale) + slovníky frontendu */
function garry_sit_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_sit_l10n() {
	$t = array(
		'cs' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'MÍSTNÍ ČAS','teplota'=>'TEPLOTA','pocitova'=>'POCITOVÁ','vlhkost'=>'VLHKOST','oblacnost'=>'OBLAČNOST','srazky'=>'SRÁŽKY','vitr'=>'VÍTR','popis'=>'POČASÍ','povrch'=>'POVRCH TRATI' ),
			'status' => array(), 'aria'=>'Živá situace na trati', 'skryt'=>'Skrýt widget', 'zobrazit'=>'Zobrazit widget', 'locale'=>'cs-CZ',
			'povrch' => array( 'mokra'=>'Mokrá', 'vlhka'=>'Vlhká (odhad)', 'sucha'=>'Suchá', 'sucha_odhad'=>'Suchá (odhad)' ),
			'codes'  => array( 0=>'Jasno',1=>'Skoro jasno',2=>'Polojasno',3=>'Zataženo',45=>'Mlha',48=>'Námraza',51=>'Mrholení',53=>'Mrholení',55=>'Mrholení',56=>'Mrznoucí mrholení',57=>'Mrznoucí mrholení',61=>'Slabý déšť',63=>'Déšť',65=>'Silný déšť',66=>'Mrznoucí déšť',67=>'Mrznoucí déšť',71=>'Slabé sněžení',73=>'Sněžení',75=>'Silné sněžení',77=>'Sněhové krupky',80=>'Přeháňky',81=>'Přeháňky',82=>'Silné přeháňky',85=>'Sněhové přeháňky',86=>'Sněhové přeháňky',95=>'Bouřka',96=>'Bouřka s kroupami',99=>'Bouřka s kroupami' ),
		),
		'en' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'LOCAL TIME','teplota'=>'TEMP','pocitova'=>'FEELS','vlhkost'=>'HUMIDITY','oblacnost'=>'CLOUDS','srazky'=>'RAIN','vitr'=>'WIND','popis'=>'WEATHER','povrch'=>'SURFACE' ),
			'status' => array( 'OTEVŘENO'=>'OPEN', 'ZAVŘENO'=>'CLOSED' ), 'aria'=>'Live track conditions', 'skryt'=>'Hide widget', 'zobrazit'=>'Show widget', 'locale'=>'en-GB',
			'povrch' => array( 'mokra'=>'Wet', 'vlhka'=>'Damp (est.)', 'sucha'=>'Dry', 'sucha_odhad'=>'Dry (est.)' ),
			'codes'  => array( 0=>'Clear',1=>'Mostly clear',2=>'Partly cloudy',3=>'Overcast',45=>'Fog',48=>'Rime fog',51=>'Drizzle',53=>'Drizzle',55=>'Drizzle',56=>'Frz. drizzle',57=>'Frz. drizzle',61=>'Light rain',63=>'Rain',65=>'Heavy rain',66=>'Frz. rain',67=>'Frz. rain',71=>'Light snow',73=>'Snow',75=>'Heavy snow',77=>'Snow grains',80=>'Showers',81=>'Showers',82=>'Heavy showers',85=>'Snow showers',86=>'Snow showers',95=>'Thunderstorm',96=>'T-storm + hail',99=>'T-storm + hail' ),
		),
		'de' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'ORTSZEIT','teplota'=>'TEMPERATUR','pocitova'=>'GEFÜHLT','vlhkost'=>'FEUCHTE','oblacnost'=>'WOLKEN','srazky'=>'REGEN','vitr'=>'WIND','popis'=>'WETTER','povrch'=>'FAHRBAHN' ),
			'status' => array( 'OTEVŘENO'=>'GEÖFFNET', 'ZAVŘENO'=>'GESCHLOSSEN' ), 'aria'=>'Live-Streckenzustand', 'skryt'=>'Widget ausblenden', 'zobrazit'=>'Widget anzeigen', 'locale'=>'de-DE',
			'povrch' => array( 'mokra'=>'Nass', 'vlhka'=>'Feucht (ca.)', 'sucha'=>'Trocken', 'sucha_odhad'=>'Trocken (ca.)' ),
			'codes'  => array( 0=>'Klar',1=>'Meist klar',2=>'Teils bewölkt',3=>'Bedeckt',45=>'Nebel',48=>'Raureif',51=>'Niesel',53=>'Niesel',55=>'Niesel',56=>'Gefr. Niesel',57=>'Gefr. Niesel',61=>'Leichter Regen',63=>'Regen',65=>'Starker Regen',66=>'Gefr. Regen',67=>'Gefr. Regen',71=>'Leichter Schnee',73=>'Schneefall',75=>'Starker Schnee',77=>'Schneegriesel',80=>'Schauer',81=>'Schauer',82=>'Starke Schauer',85=>'Schneeschauer',86=>'Schneeschauer',95=>'Gewitter',96=>'Gewitter+Hagel',99=>'Gewitter+Hagel' ),
		),
	);
	$l = garry_sit_lang();
	return isset( $t[ $l ] ) ? $t[ $l ] : $t['cs'];
}
function garry_sit_defaults() {
	$d = array( 'enabled' => 1, 'pos' => 85, 'lat' => '49.2043', 'lon' => '16.4471', 'refresh' => 10, 'status_text' => 'OTEVŘENO' );
	foreach ( garry_sit_fields() as $k => $f ) $d[ 'f_' . $k ] = $f[2];
	return $d;
}
function garry_sit_get() {
	$o = get_option( GARRY_SIT_OPT, array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), garry_sit_defaults() );
}

require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\SituaceNaTrati\V23\bootstrap( __FILE__, 'garry_sit_admin_page', 'Situace na trati' );

register_activation_hook( __FILE__, function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_SIT_STAFF_CAP ) ) {
		$role->add_cap( GARRY_SIT_STAFF_CAP );
	}
} );
add_action( 'admin_init', function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_SIT_STAFF_CAP ) ) {
		$role->add_cap( GARRY_SIT_STAFF_CAP );
	}
} );

/**
 * Registrace do GARRY – GRID Core modul registru (GRID-SUITE-06 §10).
 * Feature track_status zatím žádný jiný plugin nekonzumuje (GRID Hotel
 * Components [grid_telemetry] záměrně nepřevzala — viz manifest Components,
 * widget už dnes vlastní tenhle plugin end-to-end), ale registrace patří do
 * Core diagnostiky/Moduly stránky konzistentně se zbytkem sady.
 */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) {
		return;
	}
	gridhotel_register_module( array(
		'id'                  => 'garry-situace-na-trati',
		'name'                => 'Situace na trati',
		'version'             => defined( 'GARRY_SIT_VER' ) ? GARRY_SIT_VER : '1.5.0',
		'admin_slug'          => 'garry-situace-na-trati',
		'capability'          => GARRY_SIT_STAFF_CAP,
		'shortcodes'          => array( 'grid_telemetry' ),
		'features'            => array( 'track_status' ),
		'hidden_in_grid_menu' => true, // zatím bez vlastní GRID Nastavení karty (GRID-SUITE-06 §7 zbývá) — widget se ovládá v GARRY Nastavení
	) );
} );

add_action( 'admin_init', function () { register_setting( 'garry_sit_group', GARRY_SIT_OPT, 'garry_sit_sanitize' ); } );
function garry_sit_sanitize( $in ) {
	$d = garry_sit_defaults(); $o = array();
	$o['enabled'] = empty( $in['enabled'] ) ? 0 : 1;
	foreach ( garry_sit_fields() as $k => $f ) $o[ 'f_' . $k ] = empty( $in[ 'f_' . $k ] ) ? 0 : 1;
	$o['pos'] = max( 0, min( 100, (int) ( $in['pos'] ?? 85 ) ) );
	$o['refresh'] = max( 0, min( 120, (int) ( $in['refresh'] ?? 10 ) ) );
	$o['lat'] = sanitize_text_field( $in['lat'] ?? $d['lat'] );
	$o['lon'] = sanitize_text_field( $in['lon'] ?? $d['lon'] );
	$o['status_text'] = sanitize_text_field( $in['status_text'] ?? $d['status_text'] );
	return $o;
}

function garry_sit_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$s = garry_sit_get(); $F = garry_sit_fields();
	$name = function ( $k ) { return esc_attr( GARRY_SIT_OPT ) . '[' . esc_attr( $k ) . ']'; };
	?>
	<div class="wrap"><h1>Situace na trati — živý widget</h1>
	<div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start">
	  <form method="post" action="options.php" style="flex:1;min-width:340px;max-width:560px">
	    <?php settings_fields( 'garry_sit_group' ); ?>
	    <table class="form-table"><tbody>
	      <tr><th>Zobrazit widget</th><td><label><input type="checkbox" name="<?php echo $name('enabled'); ?>" value="1" <?php checked($s['enabled'],1); ?> data-sit="enabled"> Zapnout na webu</label></td></tr>
	      <tr><th>Zobrazené údaje</th><td>
	        <?php foreach ( $F as $k => $f ) printf(
	          '<label style="display:block;margin:5px 0"><input type="checkbox" name="%s" value="1" %s data-sit="f_%s"> %s</label>',
	          $name('f_'.$k), checked($s['f_'.$k],1,false), esc_attr($k), esc_html($f[0]) ); ?>
	      </td></tr>
	      <tr><th>Text „Status"</th><td><input type="text" name="<?php echo $name('status_text'); ?>" value="<?php echo esc_attr($s['status_text']); ?>" data-sit="status_text" class="regular-text"><p class="description">Ruční provozní stav (jediné needitované automaticky).</p></td></tr>
	      <tr><th>Výšková pozice</th><td><input type="range" min="0" max="100" name="<?php echo $name('pos'); ?>" value="<?php echo esc_attr($s['pos']); ?>" data-sit="pos" style="width:100%"><p class="description">0 % nahoře … 100 % dole. <b id="sit-pos-val"><?php echo (int)$s['pos']; ?> %</b></p></td></tr>
	      <tr><th colspan="2"><h2 style="margin:8px 0">Zdroj dat</h2></th></tr>
	      <tr><th>Poskytovatel</th><td><strong>Open-Meteo</strong> — zdarma, bez klíče, automaticky dle GPS. <a href="https://open-meteo.com/" target="_blank" rel="noopener">open-meteo.com</a></td></tr>
	      <tr><th>GPS okruhu</th><td>
	        <input type="text" name="<?php echo $name('lat'); ?>" value="<?php echo esc_attr($s['lat']); ?>" placeholder="49.2043" style="width:120px"> ,
	        <input type="text" name="<?php echo $name('lon'); ?>" value="<?php echo esc_attr($s['lon']); ?>" placeholder="16.4471" style="width:120px">
	        <p class="description">Výchozí = Masarykův okruh (49.2043 N, 16.4471 E).</p>
	      </td></tr>
	      <tr><th>Obnovovat po</th><td><input type="number" name="<?php echo $name('refresh'); ?>" value="<?php echo esc_attr($s['refresh']); ?>" min="0" max="120" style="width:80px"> min <span class="description">(0 = jen při načtení)</span></td></tr>
	    </tbody></table>
	    <?php submit_button( 'Uložit' ); ?>
	    <p class="description">Povrch trati se počítá automaticky: aktuální srážky → „Mokrá", déšť v posledních hodinách → „Vlhká (odhad)", jinak dle teploty a oblačnosti → „Suchá". Jde o odhad z počasí, ne o oficiální stav trati.</p>
	  </form>
	  <div style="flex:0 0 260px">
	    <p style="font-weight:600;margin:0 0 8px">Náhled</p>
	    <div style="position:relative;height:420px;border:1px solid #ccd0d4;border-radius:8px;background:#0d0f12;overflow:hidden">
	      <aside id="sit-preview" style="position:absolute;left:14px;top:85%;transform:translateY(-50%);width:214px;background:rgba(20,22,26,.92);border:1px solid rgba(255,255,255,.16);border-radius:3px;color:#B9B7B9;font-family:monospace;font-size:11.5px">
	        <div style="display:flex;justify-content:space-between;padding:8px 11px;border-bottom:1px solid rgba(255,255,255,.1)"><span style="color:#caa75f;letter-spacing:.16em">GRID · LIVE</span><span>×</span></div>
	        <div id="sit-prev-body" style="padding:8px 11px"></div>
	      </aside>
	    </div>
	    <p class="description" style="margin-top:8px">Náhled hodnot je ilustrativní; na webu se tahají živě.</p>
	  </div>
	</div></div>
	<script>
	(function(){
	  var F=<?php echo wp_json_encode( array_map( function($f){ return $f[1]; }, $F ) ); ?>;
	  var demo={status:'OTEVŘENO',cas:'14:22:07',teplota:'18 °C',pocitova:'17 °C',vlhkost:'62 %',oblacnost:'40 %',srazky:'0.0 mm',vitr:'12 km/h',popis:'Polojasno',povrch:'Suchá'};
	  var body=document.getElementById('sit-prev-body'), box=document.getElementById('sit-preview');
	  function q(s){return document.querySelector('[data-sit="'+s+'"]');}
	  function upd(){
	    if(!body)return; var h='';
	    Object.keys(F).forEach(function(k){
	      var cb=q('f_'+k); if(cb&&cb.checked){
	        var val=(k==='status')?(q('status_text').value||'OTEVŘENO'):demo[k];
	        h+='<div style="display:flex;justify-content:space-between;padding:3px 0"><span>'+F[k]+'</span><b style="color:'+(k==='teplota'?'#FF5A50':'#fff')+'">'+val+'</b></div>';
	      }
	    });
	    body.innerHTML=h;
	    var pos=q('pos').value; box.style.top=pos+'%'; var pv=document.getElementById('sit-pos-val'); if(pv)pv.textContent=pos+' %';
	    box.style.opacity=q('enabled').checked?'1':'.3';
	  }
	  document.querySelectorAll('[data-sit]').forEach(function(el){el.addEventListener('input',upd);el.addEventListener('change',upd);});
	  upd();
	})();
	</script>
	<?php
}

/* Frontend: plugin řídí widget (child [grid_telemetry] vypnut) */
add_action( 'init', function () { add_shortcode( 'grid_telemetry', '__return_empty_string' ); }, 30 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$s = garry_sit_get(); if ( empty( $s['enabled'] ) ) return;
	$F = garry_sit_fields(); $pos = (int) $s['pos'];
	$L = garry_sit_l10n();
	ob_start();
	foreach ( $F as $k => $f ) {
		if ( empty( $s[ 'f_' . $k ] ) ) continue;
		if ( $k === 'status' ) { $st = $s['status_text']; $val = esc_html( isset( $L['status'][ $st ] ) ? $L['status'][ $st ] : $st ); }
		elseif ( $k === 'cas' )     $val = '--:--:--';
		elseif ( $k === 'teplota' ) $val = '–&nbsp;°C';
		else                        $val = '…';
		$cls = ( $k === 'teplota' ) ? 'v hot' : 'v';
		$dot = ( $k === 'status' ) ? '<span class="dot"></span>' : '';
		$lbl = isset( $L['labels'][ $k ] ) ? $L['labels'][ $k ] : $f[1];
		echo '<div class="hud-row"><span class="k">' . $dot . esc_html( $lbl ) . '</span><span class="' . $cls . '" data-field="' . esc_attr( $k ) . '">' . $val . '</span></div>';
	}
	$rows = ob_get_clean();
	?>
	<aside class="telemetry-hud" id="hud" aria-label="<?php echo esc_attr( $L['aria'] ); ?>" data-lat="<?php echo esc_attr($s['lat']); ?>" data-lon="<?php echo esc_attr($s['lon']); ?>" data-refresh="<?php echo (int)$s['refresh']; ?>" style="top:<?php echo $pos; ?>%;bottom:auto;transform:translateY(-50%)">
	  <div class="hud-head"><span class="hud-title">GRID · Live</span><button class="hud-x" id="hudX" aria-label="<?php echo esc_attr( $L['skryt'] ); ?>">&times;</button></div>
	  <div class="hud-body"><?php echo $rows; ?></div>
	</aside>
	<button class="hud-reopen" id="hudReopen" aria-label="<?php echo esc_attr( $L['zobrazit'] ); ?>">Live</button>
	<script>
	(function(){
	  var GSIT=<?php echo wp_json_encode( array( 'locale' => $L['locale'], 'codes' => $L['codes'], 'povrch' => $L['povrch'] ) ); ?>;
	  var el=document.getElementById('hud'); if(!el)return;
	  var reopen=document.getElementById('hudReopen');
	  /* Přesun mimo Divi kontejnery (transform ruší position:fixed) — řídí plugin sám,
	     nezávisle na pořadí načtení skriptů šablony. */
	  if(el.parentNode!==document.body) document.body.appendChild(el);
	  if(reopen && reopen.parentNode!==document.body) document.body.appendChild(reopen);
	  /* Zavření křížkem + znovuotevření boční kartou „Live" — stav se pamatuje přes
	     localStorage, ať zůstane schovaný i po přechodu na další stránku (dřív se
	     při každém novém načtení stránky tiše zapomněl a widget se vždy vrátil). */
	  var HUD_KEY='garry_sit_hud_hidden';
	  function hudStore(v){ try{ if(v) localStorage.setItem(HUD_KEY,'1'); else localStorage.removeItem(HUD_KEY); }catch(e){} }
	  function hudRecall(){ try{ return localStorage.getItem(HUD_KEY)==='1'; }catch(e){ return false; } }
	  if(hudRecall()){ el.classList.add('hidden'); if(reopen) reopen.classList.add('show'); }
	  var xbtn=document.getElementById('hudX');
	  if(xbtn) xbtn.addEventListener('click', function(){ el.classList.add('hidden'); if(reopen) reopen.classList.add('show'); hudStore(true); });
	  if(reopen) reopen.addEventListener('click', function(){ el.classList.remove('hidden'); reopen.classList.remove('show'); hudStore(false); });
	  var lat=el.getAttribute('data-lat')||'49.2043', lon=el.getAttribute('data-lon')||'16.4471', refresh=parseInt(el.getAttribute('data-refresh')||'10',10);
	  function setF(f,v){ var s=el.querySelector('[data-field="'+f+'"]'); if(s)s.innerHTML=v; }
	  if(el.querySelector('[data-field="cas"]')){ var tick=function(){ setF('cas', new Date().toLocaleTimeString(GSIT.locale,{hour:'2-digit',minute:'2-digit',second:'2-digit'})); }; tick(); setInterval(tick,1000); }
	  var codes=GSIT.codes;
	  function loadW(){
	    var u='https://api.open-meteo.com/v1/forecast?latitude='+encodeURIComponent(lat)+'&longitude='+encodeURIComponent(lon)+'&current=temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,rain,showers,weather_code,cloud_cover,wind_speed_10m&hourly=precipitation&past_hours=3&forecast_hours=1&timezone=Europe%2FPrague';
	    fetch(u).then(function(r){return r.json();}).then(function(j){
	      var c=j.current||{};
	      if(typeof c.temperature_2m==='number') setF('teplota', Math.round(c.temperature_2m)+'&nbsp;°C');
	      if(typeof c.apparent_temperature==='number') setF('pocitova', Math.round(c.apparent_temperature)+'&nbsp;°C');
	      if(typeof c.relative_humidity_2m==='number') setF('vlhkost', Math.round(c.relative_humidity_2m)+'&nbsp;%');
	      if(typeof c.cloud_cover==='number') setF('oblacnost', Math.round(c.cloud_cover)+'&nbsp;%');
	      if(typeof c.precipitation==='number') setF('srazky', (c.precipitation||0).toFixed(1)+'&nbsp;mm');
	      if(typeof c.wind_speed_10m==='number') setF('vitr', Math.round(c.wind_speed_10m)+'&nbsp;km/h');
	      if(typeof c.weather_code!=='undefined') setF('popis', codes[c.weather_code]||'—');
	      var recent=0; if(j.hourly&&j.hourly.precipitation){ recent=j.hourly.precipitation.reduce(function(a,b){return a+(b||0);},0); }
	      var now=(c.precipitation||0)+(c.rain||0)+(c.showers||0), povrch;
	      if(now>0) povrch=GSIT.povrch.mokra;
	      else if(recent>0.1) povrch=GSIT.povrch.vlhka;
	      else if((c.temperature_2m||0)>=15 && (c.cloud_cover||100)<60) povrch=GSIT.povrch.sucha;
	      else povrch=GSIT.povrch.sucha_odhad;
	      setF('povrch', povrch);
	    }).catch(function(){});
	  }
	  loadW(); if(refresh>0) setInterval(loadW, refresh*60000);
	})();
	</script>
	<?php
}, 20 );
