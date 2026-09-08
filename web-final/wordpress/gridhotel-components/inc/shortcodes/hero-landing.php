<?php
/**
 * GRID Hotel Components — hero a CTA shortcody (GRID-SUITE-02 §4 „hero a CTA").
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [grid_hero] — homepage hero. Křivku vykresluje výhradně plugin Hero křivka
 * (garry_hero_curve_render(), fáze 7) — Components tady žádnou vlastní SVG
 * kopii nemá a nic nepředstírá, když plugin není aktivní/je vypnutý
 * (garry_hero_curve_render() tehdy vrátí prázdný string).
 */
function gridc_sc_hero() {
	$li = gridc_lang_index();
	$FB = array(
		'kicker' => array( 'GRID HOTEL · **** · Masarykův okruh', 'GRID HOTEL · **** · Masaryk Circuit', 'GRID HOTEL · **** · Masaryk-Ring' ),
		'h1'     => array( 'Přespi <em>uprostřed</em><br>Masarykova okruhu.', 'Sleep <em>in the middle</em><br>of the Masaryk Circuit.', 'Übernachten Sie <em>mitten</em><br>im Masaryk-Ring.' ),
		'sub'    => array(
			'Jediný hotel a restaurace přímo v areálu Autodromu Brno. 60 komfortních pokojů a 4 apartmá s výhledem na trať, paddock i okolní lesy — evropský standard ****.',
			'The only hotel and restaurant right inside the Autodrom Brno grounds. 60 comfortable rooms and 4 suites overlooking the track, the paddock and the surrounding forests — European **** standard.',
			'Das einzige Hotel und Restaurant direkt auf dem Gelände des Autodrom Brno. 60 komfortable Zimmer und 4 Appartements mit Blick auf die Strecke, den Paddock und die umliegenden Wälder — europäischer ****-Standard.',
		),
		'btn1' => array( 'Rezervovat pobyt', 'Book your stay', 'Aufenthalt buchen' ),
		'btn2' => array( 'Projet okruh ↓', 'Take a lap ↓', 'Eine Runde drehen ↓' ),
		'cue'  => array( 'Scroll · Projeď trať', 'Scroll · Ride the track', 'Scroll · Die Strecke fahren' ),
	);
	$img  = gridhotel_get_option( 'hero_obrazek', '' );
	$img  = $img ? ( is_array( $img ) ? $img['url'] : $img ) : gridc_fallback_image( 'okruh-zapad-slunce.jpg' );
	$kick = gridhotel_get_option( 'hero_kicker', $FB['kicker'][ $li ] );
	$h1   = gridhotel_get_option( 'hero_nadpis', $FB['h1'][ $li ] );
	$sub  = gridhotel_get_option( 'hero_podtitulek', $FB['sub'][ $li ] );
	ob_start(); ?>
	<section class="hero sec sec-dark grid-component grid-component--hero" id="start">
	  <div class="hero-bg" id="heroBg" style="background-image:url('<?php echo esc_url( $img ); ?>')"></div>
	  <div class="hero-overlay"></div>
	  <?php if ( function_exists( 'garry_hero_curve_render' ) ) : ?>
	  <div class="hero-track"><?php echo garry_hero_curve_render(); ?></div>
	  <?php endif; ?>
	  <div class="hero-content">
	    <div class="hero-meta">49.0227° N · 16.4419° E · Autodrom Brno</div>
	    <span class="kicker"><?php echo wp_kses_post( $kick ); ?></span>
	    <h1><?php echo wp_kses_post( $h1 ); ?></h1>
	    <p class="hero-sub"><?php echo wp_kses_post( $sub ); ?></p>
	    <div class="hero-actions"><a href="<?php echo esc_url( gridc_rezervace_url() ); ?>" class="btn"><?php echo esc_html( $FB['btn1'][ $li ] ); ?></a><a href="#pribeh" class="btn btn-ghost"><?php echo esc_html( $FB['btn2'][ $li ] ); ?></a></div>
	  </div>
	  <div class="scroll-cue"><?php echo esc_html( $FB['cue'][ $li ] ); ?></div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_hero', 'gridc_sc_hero' );

/**
 * [grid_vyber_pokoje] — pole „Typ pokoje" v rezervační liště.
 *
 * Nabídka se bere z kategorií pokojů (taxonomie grid_room_cat) v jazyce
 * stránky a řadí se podle pole „pořadí". Dřív byly možnosti napsané natvrdo
 * v obsahu stránky, takže nesouhlasily se skutečnými typy pokojů (chyběl
 * Superior s terasou i Apartmá Superior a naopak přebýval neexistující
 * „Superior Plus"). Přidání nebo přejmenování kategorie se teď propíše samo.
 */
function gridc_sc_vyber_pokoje( $atts = array() ) {
	$li = gridc_lang_index();
	$T  = array(
		'label'    => array( 'Typ pokoje', 'Room type', 'Zimmertyp' ),
		'libovolny'=> array( 'Libovolný', 'Any', 'Beliebig' ),
	);
	$atts = shortcode_atts( array( 'id' => 'bk-pokoj' ), $atts, 'grid_vyber_pokoje' );

	/* Stejný zdroj jako pole „Typ pokoje" v čekacím listu — viz inc/forms.php.
	   Bez Core (nebo když taxonomie ještě nemá termy) se pole nevykresluje
	   vůbec: prázdný výběr by uživatele mátl víc než jeho absence. */
	$kategorie = function_exists( 'gridc_typy_pokoju' ) ? gridc_typy_pokoju() : array();
	if ( ! $kategorie ) {
		return '';
	}

	ob_start(); ?>
	<div class="bk-field"><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php echo esc_html( $T['label'][ $li ] ); ?></label><select id="<?php echo esc_attr( $atts['id'] ); ?>" name="typ-pokoje"><option value=""><?php echo esc_html( $T['libovolny'][ $li ] ); ?></option><?php foreach ( $kategorie as $k ) : ?><option value="<?php echo esc_attr( $k['slug'] ); ?>"><?php echo esc_html( $k['name'] ); ?></option><?php endforeach; ?></select></div>
	<?php
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_vyber_pokoje', 'gridc_sc_vyber_pokoje' );

/* [grid_booking] — statický (nefunkční) date/guests/room-type booking bar. */
function gridc_sc_booking() {
	ob_start(); ?>
	<section class="booking grid-component grid-component--booking" id="booking" aria-label="Rezervace pobytu">
	  <form class="wrap" onsubmit="return false">
	    <div class="bk-field"><label for="bk-in">Příjezd</label><input type="date" id="bk-in" value="2026-08-07"></div>
	    <div class="bk-field"><label for="bk-out">Odjezd</label><input type="date" id="bk-out" value="2026-08-09"></div>
	    <div class="bk-field"><label for="bk-guests">Hosté</label><select id="bk-guests"><option>1 host</option><option selected>2 hosté</option><option>3 hosté</option><option>4 hosté</option><option>5+ hostů</option></select></div>
	    <?php echo gridc_sc_vyber_pokoje(); ?>
	    <div class="bk-note">Nejlepší cena<br>přímo u hotelu</div>
	    <div class="bk-submit"><a class="btn" href="<?php echo esc_url( gridc_rezervace_url() ); ?>">Zkontrolovat dostupnost</a></div>
	  </form>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_booking', 'gridc_sc_booking' );

/* [grid_final] — cílová CTA sekce. */
function gridc_sc_final() {
	ob_start(); ?>
	<section id="cil" class="sec sec-dark final grid-component grid-component--final">
	  <div class="wrap">
	    <span class="kicker" style="justify-content:center;display:inline-flex">CÍL · Cílová rovinka</span>
	    <h2 style="margin-top:18px">Dojeď do cíle.<br>Rezervuj svůj okruh.</h2>
	    <p>Ať jedeš za závody, byznysem, oslavou nebo klidem — tvůj pobyt uprostřed Masarykova okruhu začíná jedním kliknutím.</p>
	    <div class="fc-actions"><a href="<?php echo esc_url( gridc_rezervace_url() ); ?>" class="btn">Rezervovat pobyt</a><a href="<?php echo esc_url( gridc_nav_url( '#firemni' ) ); ?>" class="btn btn-ghost">Poptat akci nebo svatbu</a><a href="<?php echo esc_url( gridc_nav_url( '#zazitky' ) ); ?>" class="btn btn-ghost">Koupit dárkový poukaz</a></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_final', 'gridc_sc_final' );

/* [grid_rezervace] — dočasný zástupný prvek pro rezervační systém (Bookolo mock, watermark "UKÁZKA"). */
function gridc_sc_rezervace() {
	$bullets = array( 'Ubytování přímo v centru dění', 'Výhodnější ceny ubytování', 'Možnost uplatnění promo kódů', 'Voda na pokoji zdarma', 'Parkování před hotelem zdarma', 'Recepce k dispozici 24/7' );
	$mock    = array(
		array( 'name' => 'Pokoj Superior', 'cap' => 2, 'm2' => '23', 'img' => gridc_fallback_image( 'pokoj-superior.jpg' ), 'p1' => '3 758', 'p2' => '3 394' ),
		array( 'name' => 'Pokoj Superior plus', 'cap' => 2, 'm2' => '23', 'img' => gridc_fallback_image( 'koupelna.jpg' ), 'p1' => '4 122', 'p2' => '3 637' ),
		array( 'name' => 'Apartmán', 'cap' => 2, 'm2' => '47', 'img' => gridc_fallback_image( 'pokoj-apartma.jpg' ), 'p1' => '6 667', 'p2' => '6 304' ),
		array( 'name' => 'Apartmán LUX', 'cap' => 2, 'm2' => '58', 'img' => gridc_fallback_image( 'pokoj-apartma.jpg' ), 'p1' => '7 516', 'p2' => '7 152' ),
		array( 'name' => 'Pokoj Standard', 'cap' => 2, 'm2' => '23', 'img' => gridc_fallback_image( 'hotel-exterier.jpg' ), 'unavail' => true ),
	);
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--rezervace" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Rezervace</span>
	    <h1 style="font-size:clamp(2.2rem,6vw,4.4rem);margin:14px 0 10px">Rezervace pobytu</h1>
	    <p style="max-width:70ch;color:var(--muted);margin-bottom:28px">Takto bude vypadat online rezervace přímo na webu. <strong style="color:var(--ink)">Níže je ukázka — ostrý rezervační systém (Bookolo) se vloží při nasazení na ostrém webu.</strong></p>
	    <div class="bkmock" aria-hidden="true">
	      <?php /* Vodoznak je dekorace nad ukázkovým widgetem — pro čtečky skrytý,
	         informaci nese text sekce (WCAG 1.4.3 se na skrytý obsah nevztahuje). */ ?>
	      <div class="bkmock-wm" aria-hidden="true"><span>UKÁZKA</span><small>rezervační systém se nasadí na ostrém webu</small></div>
	      <div class="bkmock-topbar"><span class="bkmock-tab">Pokoje</span><span class="bkmock-topright">CS ▾ &nbsp; CZK ▾ &nbsp; Více možností ▾</span></div>
	      <div class="bkmock-head"><h2>Rezervace ubytování</h2><div class="bkmock-promo"><input type="text" placeholder="Zadejte promo kód" disabled><span class="bkmock-btn red">Potvrdit</span></div></div>
	      <div class="bkmock-datebar">
	        <div class="bkmock-date red"><span class="l">Příjezd</span></div>
	        <div class="bkmock-date red"><span class="l">Odjezd</span></div>
	        <div class="bkmock-dbox"><b>4</b> Červenec 2026</div>
	        <div class="bkmock-dbox"><b>5</b> Červenec 2026</div>
	        <span class="bkmock-pill">+ Další pokoj</span><span class="bkmock-pill">Dospělí − 2 +</span><span class="bkmock-pill">Děti − 0 +</span>
	      </div>
	      <div class="bkmock-layout">
	        <div class="bkmock-rooms">
	          <?php foreach ( $mock as $m ) : $un = ! empty( $m['unavail'] ); ?>
	          <div class="bkmock-card">
	            <div class="bkmock-cimg" style="background-image:url('<?php echo esc_url( $m['img'] ); ?>')"><?php if ( $un ) : ?><div class="bkmock-unavail"><span>Pokoj není dostupný ve vybraném termínu</span><em>Zobrazit ceny a dostupnost</em></div><?php endif; ?></div>
	            <div class="bkmock-cbody">
	              <h3><?php echo esc_html( $m['name'] ); ?></h3>
	              <div class="bkmock-cmeta">Max <?php echo (int) $m['cap']; ?> &nbsp;·&nbsp; Min <?php echo esc_html( $m['m2'] ); ?> m²</div>
	              <ul><?php foreach ( $bullets as $b ) { echo '<li>' . esc_html( $b ) . '</li>'; } ?></ul>
	              <span class="bkmock-detail">Detail pokoje</span>
	            </div>
	          </div>
	          <?php if ( ! $un ) : ?>
	          <div class="bkmock-rates">
	            <div class="bkmock-rate"><div class="bkmock-rinfo"><b>Ubytování se snídaní s možností zrušení</b><span>S možností vrácení peněz · <u>Zobrazit detail nabídky</u></span></div><span class="bkmock-price"><?php echo esc_html( $m['p1'] ); ?> CZK</span><span class="bkmock-btn red">Rezervovat</span></div>
	            <div class="bkmock-rate"><div class="bkmock-rinfo"><b>Ubytování se snídaní bez možnosti zrušení</b><span>Bez možnosti vrácení peněz · <u>Zobrazit detail nabídky</u></span></div><span class="bkmock-price"><?php echo esc_html( $m['p2'] ); ?> CZK</span><span class="bkmock-btn red">Rezervovat</span></div>
	          </div>
	          <?php endif; ?>
	          <?php endforeach; ?>
	        </div>
	        <aside class="bkmock-side">
	          <div class="bkmock-sbox"><b>Jste flexibilní s termíny?</b><span class="bkmock-pill">📅 Ceny a dostupnosti</span></div>
	          <div class="bkmock-sbox"><b>Shrnutí rezervace</b><div class="bkmock-srange">4. 7. 2026 → 5. 7. 2026</div><span class="bkmock-smuted">Není vybraný žádný pokoj</span></div>
	          <div class="bkmock-sbox bkmock-guar"><span>❤ Bezpečná online platba</span><span>❤ Oficiální web</span><span>❤ Garance nejnižších cen</span></div>
	        </aside>
	      </div>
	      <div class="bkmock-foot"><span>Powered by <b>BOOKOLO</b></span><span>FAQ &nbsp;·&nbsp; Podmínky rezervace &nbsp;·&nbsp; Ochrana osobních údajů</span></div>
	    </div>
	    <div class="fc-actions" style="margin-top:30px">
	      <a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>#booking">Zpět na výběr termínu</a>
	      <a class="btn btn-ghost" href="<?php echo esc_url( gridc_detail_url( array( 'kontakt' ) ) ?: home_url( '/#kontakt' ) ); ?>">Kontaktovat recepci</a>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_rezervace', 'gridc_sc_rezervace' );
