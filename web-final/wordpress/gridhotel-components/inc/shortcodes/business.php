<?php
/**
 * GRID Hotel Components — vstupy, příběh, firemní akce, reference
 * (GRID-SUITE-02 §4 „reference, kariéra a servisní prvky" + rozcestník).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* [grid_vstupy] — T1 rozcestník; vstupy jsou ACF repeater group_grid_content (Core), zatím bez get_field wrapperu v Core API — čte se přímo pokud ACF aktivní, jinak defaults. */
function gridc_sc_vstupy() {
	$defaults = array(
		array( 'num' => '01 / ZÁVODY', 'title' => 'Race víkend', 'text' => 'Spi metr od trati a vstávej do zvuku motorů. Pobyty na velké akce sezóny 2026 a čekací list.', 'cta' => 'Sezóna 2026 →', 'url' => '#sezona' ),
		array( 'num' => '02 / FIRMY &amp; SVATBY', 'title' => 'Akce na míru', 'text' => 'Firemní večírky, konference, oslavy i svatební hostiny s adrenalinem a cateringem na dosah.', 'cta' => 'Poptat akci →', 'url' => '#firemni' ),
		array( 'num' => '03 / ZÁŽITEK', 'title' => 'Motorsport zážitek', 'text' => 'Simulátor Masarykova okruhu, motokáry, pitbike i škola bezpečné jízdy. Dárkové poukazy skladem.', 'cta' => 'Vybrat zážitek →', 'url' => '#zazitky' ),
		array( 'num' => '04 / POBYT', 'title' => 'Klidný pobyt u Brna', 'text' => 'Komfort **** hotelu, výhled na trať a lesy, snadné parkování. Ideální základna pro výlety.', 'cta' => 'Prohlédnout pokoje →', 'url' => '#pokoje' ),
	);
	$items = $defaults;
	if ( function_exists( 'have_rows' ) && have_rows( 'vstupy', 'option' ) ) {
		$rows = array();
		while ( have_rows( 'vstupy', 'option' ) ) {
			the_row();
			$rows[] = array( 'num' => get_sub_field( 'num' ), 'title' => get_sub_field( 'title' ), 'text' => get_sub_field( 'text' ), 'cta' => get_sub_field( 'cta' ), 'url' => get_sub_field( 'url' ) );
		}
		if ( ! empty( $rows ) ) {
			$items = $rows;
		}
	}
	ob_start(); ?>
	<section id="vstupy" class="sec sec-light sec-pad grid-component grid-component--vstupy">
	  <span class="sec-tag">T1</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:40px"><span class="kicker">T1 · Vstupy podle motivace</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px;max-width:18ch">Kudy do zatáčky? Vyber si svou odbočku.</h2></div>
	    <div class="entries reveal d1">
	      <?php foreach ( $items as $it ) : ?>
	      <div class="entry"><a class="entry-link" href="<?php echo esc_url( gridc_row_val( $it, 'url', '#' ) ); ?>" aria-label="<?php echo esc_attr( gridc_row_val( $it, 'title' ) ); ?>"></a><span class="e-num"><?php echo wp_kses_post( gridc_row_val( $it, 'num' ) ); ?></span>
	        <h3><?php echo esc_html( gridc_row_val( $it, 'title' ) ); ?></h3>
	        <p><?php echo esc_html( gridc_row_val( $it, 'text' ) ); ?></p>
	        <span class="e-arrow"><?php echo esc_html( gridc_row_val( $it, 'cta' ) ); ?></span>
	      </div>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_vstupy', 'gridc_sc_vstupy' );

/* [grid_pribeh] — T2 příběh místa. */
function gridc_sc_pribeh() {
	$img = gridhotel_get_option( 'pribeh_obrazek', '' );
	$img = $img ? ( is_array( $img ) ? $img['url'] : $img ) : gridc_fallback_image( 'hotel-okruh-leto.jpg' );
	ob_start(); ?>
	<section id="pribeh" class="sec sec-dark carbon grid-component grid-component--pribeh">
	  <span class="sec-tag">T2</span>
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url( $img ); ?>" alt="GRID Hotel s výhledem na trať Masarykova okruhu"></div>
	    <div class="sp-content reveal">
	      <span class="kicker">T2 · Příběh místa</span>
	      <h2>Jediný hotel<br>uvnitř trati.</h2>
	      <p class="lead">GRID HOTEL nestojí u trati. Stojí přímo <strong>v areálu Autodromu Brno</strong> na adrese Ostrovačická 65 — pár metrů od slavného Masarykova okruhu.</p>
	      <p>Z oken pokojů a z terasy vidíte na centrum dění okruhu i okolní lesy. Probudíte se do zvuku motorů, na snídani vyrazíte s výhledem na trať a večer si dáte v restauraci nebo v GRID Clubu. Je to jediné místo v Česku, kde spíte uprostřed závodní legendy.</p>
	      <div class="stat-row">
	        <div class="stat"><span class="data">64</span><span>pokojů &amp; apartmá</span></div>
	        <div class="stat"><span class="data">5,4 km</span><span>délka okruhu</span></div>
	        <div class="stat"><span class="data">0 m</span><span>od trati</span></div>
	      </div>
	      <div style="margin-top:26px"><a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/o-nas/' ) ); ?>">Celý příběh hotelu →</a></div>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_pribeh', 'gridc_sc_pribeh' );

/* [grid_firemni] — T7 firemní akce & svatby. */
function gridc_sc_firemni() {
	$img = gridhotel_get_option( 'firemni_obrazek', '' );
	$img = $img ? ( is_array( $img ) ? $img['url'] : $img ) : gridc_fallback_image( 'hotel-exterier.jpg' );
	ob_start(); ?>
	<section id="firemni" class="sec sec-light grid-component grid-component--firemni">
	  <span class="sec-tag" style="z-index:1">T7</span>
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url( $img ); ?>" alt="Exteriér GRID Hotelu — firemní akce a svatby"></div>
	    <div class="sp-content reveal">
	      <span class="kicker">T7 · Firemní akce &amp; svatby</span>
	      <h2>Večírky, svatby,<br>konference.</h2>
	      <p class="lead">Hledáte příjemné prostory pro firemní akci, vánoční večírek, oslavu narozenin nebo svatební hostinu? Spojte jednání i oslavu s adrenalinem — vše pod jednou střechou.</p>
	      <div class="b2b-grid">
	        <div class="b2b-card"><span class="data">50</span><span>míst v restauraci</span></div>
	        <div class="b2b-card"><span class="data">64</span><span>pokojů pro hosty</span></div>
	        <div class="b2b-card"><span class="data">bar</span><span>míchané nápoje</span></div>
	        <div class="b2b-card"><span class="data">P</span><span>parkování před hotelem</span></div>
	      </div>
	      <p>Catering na míru dle vašich představ, stylový bar se širokou nabídkou nápojů a bezproblémové parkování přímo před hotelem. Jako bonus doprovodný program: simulátor Masarykova okruhu, motokáry i motoškola. Postaráme se o menu i celý průběh akce — na hotelu i přímo v areálu okruhu.</p>
	      <?php echo gridc_render_inquiry_form_placeholder(); ?>
	      <?php echo gridc_section_more( array( 'firemni-akce-svatby', 'firemni-akce', 'firemni' ), 'Firemní akce & svatby — detail' ); ?>
	    </div>
	  </div>
	</section>
	<?php
	if ( ! is_front_page() ) {
		echo gridc_render_firemni_extra();
	}
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_firemni', 'gridc_sc_firemni' );

/**
 * Poptávkový formulář firemních akcí — v baseline (theme) je to `onclick="return false"`
 * mockup bez skutečného odeslání. GRID-SUITE-02 §10 zakazuje předstírat funkční
 * formulář; tady proto zůstává jasně označen jako ukázka (stejně jako grid_blind_form
 * v forms-contact.php), dokud nemá reálný backend (Fluent Forms mapping pro tenhle
 * účel dnes neexistuje ani v Core → mimo rozsah fáze 2, žádná regrese oproti baseline).
 */
function gridc_render_inquiry_form_placeholder() {
	ob_start(); ?>
	<div class="form-grid" style="margin-top:22px">
	  <div><label for="f-firma">Firma / jméno</label><input id="f-firma" type="text"></div>
	  <div><label for="f-email">E-mail</label><input id="f-email" type="email"></div>
	  <div><label for="f-pocet">Počet osob</label><input id="f-pocet" type="number" min="1"></div>
	  <div><label for="f-termin">Termín</label><input id="f-termin" type="date"></div>
	  <div class="full"><label for="f-typ">Typ akce</label><select id="f-typ"><option>Firemní večírek</option><option>Konference / teambuilding</option><option>Svatební hostina</option><option>Oslava narozenin</option><option>Vánoční večírek</option><option>Jiné</option></select></div>
	  <div class="full"><button type="submit" class="btn" onclick="return false" title="Poptávkový formulář zatím není napojen — napište prosím e-mailem nebo použijte kontaktní formulář.">Odeslat nezávaznou poptávku</button></div>
	</div>
	<p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:12px">// Ukázkový formulář — připraven k napojení, zatím neodesílá.</p>
	<?php return ob_get_clean();
}

/** Rozšíření stránky Firemní akce & svatby — mimo homepage. */
function gridc_render_firemni_extra() {
	$types = array(
		array( 'Konference &amp; školení', 'Zázemí pro jednání i celodenní program s občerstvením a ubytováním na místě.' ),
		array( 'Teambuilding', 'Spojte poradu s adrenalinem — simulátor okruhu, motokáry i motoškola pár kroků od hotelu.' ),
		array( 'Firemní večírek', 'Vánoční večírek, oslava výsledků nebo jen tak — bar, catering a klub GRID.' ),
		array( 'Svatební hostina', 'Svatba v neokoukaném prostředí Masarykova okruhu, catering a hosté ubytovaní pod jednou střechou.' ),
		array( 'Oslavy narozenin', 'Rodinné i velké oslavy s menu na míru a možností přespání.' ),
		array( 'Rauty &amp; prezentace', 'Uvedení produktu, tisková akce nebo raut v areálu okruhu i na hotelu.' ),
	);
	$nabidka = array(
		'Restaurace s kapacitou cca 50 míst', 'Stylový bar — nealko, alko i míchané nápoje', 'Catering na míru dle vašich představ',
		'Ubytování v 64 moderních pokojích a apartmá', 'Bezproblémové parkování přímo před hotelem',
		'Doprovodný program: simulátor okruhu, motokáry, motoškola', 'Kompletní organizace — od menu po realizaci',
	);
	$rez = gridc_rezervace_url();
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad grid-component grid-component--firemni-extra">
	  <div class="wrap">
	    <span class="kicker">Pro jakou akci</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 26px">Jedno místo pro byznys i oslavu</h2>
	    <div class="onas-grid">
	      <?php foreach ( $types as $t ) : ?>
	        <div class="onas-card" style="background:rgba(255,255,255,.04);border-color:rgba(255,255,255,.12)">
	          <h3 style="color:#fff"><?php echo wp_kses_post( $t[0] ); ?></h3>
	          <p><?php echo esc_html( $t[1] ); ?></p>
	        </div>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<section class="sec sec-light sec-pad">
	  <div class="wrap" style="max-width:900px">
	    <span class="kicker">Co u nás najdete</span>
	    <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin:14px 0 20px">Vše pod jednou střechou</h2>
	    <ul class="amenity-grid"><?php foreach ( $nabidka as $n ) { echo '<li>' . esc_html( $n ) . '</li>'; } ?></ul>
	    <div class="fc-actions" style="margin-top:30px">
	      <a class="btn" href="<?php echo esc_url( gridc_nav_url( '#kontakt' ) ); ?>">Nezávazná poptávka</a>
	      <a class="btn btn-ghost" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* [grid_reference] — T8 reference, přes Core API (gridhotel_get_testimonials). */
function gridc_sc_reference() {
	$defaults = array(
		array( 'text' => 'Probudit se a vidět z okna cílovou rovinku Masarykova okruhu je nepopsatelné. Servis i snídaně na úrovni.', 'who' => 'Petr H. · MotoGP víkend' ),
		array( 'text' => 'Uspořádali jsme tu firemní akci pro 120 lidí. Catering, bar i motokáry — vše na jednom místě bez transferů.', 'who' => 'Lucie K. · HR manažerka' ),
		array( 'text' => 'Apartmá s terasou a výhledem na trať. Klid, prémiová úroveň a pár minut do Brna. Vrátíme se.', 'who' => 'Tomáš &amp; Eva R. · Víkendový pobyt' ),
	);
	$items = $defaults;
	if ( function_exists( 'gridhotel_get_testimonials' ) ) {
		$core = gridhotel_get_testimonials();
		if ( ! empty( $core ) ) {
			$items = array_map( fn( $t ) => array( 'text' => $t['text'], 'who' => $t['who'] ), $core );
		}
	}
	ob_start(); ?>
	<section id="duvera" class="sec sec-dark carbon sec-pad grid-component grid-component--reference">
	  <span class="sec-tag">T8</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px"><span class="kicker">T8 · Co říkají hosté</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Recenze z pole position</h2></div>
	    <div class="reviews">
	      <?php $d = 1; foreach ( $items as $it ) : ?>
	      <div class="review reveal d<?php echo (int) $d++; ?>"><span class="stars">★★★★★</span><p>„<?php echo esc_html( gridc_row_val( $it, 'text' ) ); ?>“</p><span class="r-who"><?php echo wp_kses_post( gridc_row_val( $it, 'who' ) ); ?></span></div>
	      <?php endforeach; ?>
	    </div>
	    <div class="partners reveal"><span class="p-label">Partner</span><span class="p-name">Autodrom Brno</span><span class="p-name">Masarykův okruh</span><span class="p-name">Polygon Brno</span></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_reference', 'gridc_sc_reference' );
