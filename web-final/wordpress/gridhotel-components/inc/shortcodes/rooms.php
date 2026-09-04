<?php
/**
 * GRID Hotel Components — pokoje, karty a tabulky (GRID-SUITE-02 §4).
 *
 * [grid_rooms_cards]/[grid_rooms_table] jsou skládané přes ModuleRenderer:
 * primárně je vykresluje plugin „Kategorie pokojů" (feature room_comparison),
 * fallback je jednoduchý seznam přímo z Core (gridhotel_get_room_categories) —
 * přesně dle GRID-SUITE-02 §9 tabulky poskytovatelů/fallbacků.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Core kategorie → tvar shodný s theme repeater rows (num/title/img/desc/feat/…), pro fallback i pro T3 sekci. */
function gridc_room_rows_from_core( array $fallback_defaults ) {
	if ( ! function_exists( 'gridhotel_get_room_categories' ) ) {
		return $fallback_defaults;
	}
	$cats = gridhotel_get_room_categories();
	if ( empty( $cats ) ) {
		return $fallback_defaults;
	}
	$imgs = array( 'hotel-exterier.jpg', 'pokoj-superior.jpg', 'koupelna.jpg', 'pokoj-apartma.jpg' );
	$rows = array();
	foreach ( $cats as $i => $c ) {
		$img = $c['image'] ?: gridc_fallback_image( $imgs[ $i % count( $imgs ) ] );
		$rows[] = array(
			'num'      => $c['code'],
			'title'    => $c['name'],
			'img'      => $img,
			'desc'     => $c['short_description'] ?: $c['description'],
			'feat'     => implode( '|', $c['tags'] ),
			'pocet'    => $c['count'],
			'kapacita' => $c['capacity'],
			'velikost' => $c['size'],
			'postel'   => $c['bed'],
			'koupelna' => implode( '|', $c['bathroom'] ),
			'zarizeni' => implode( '|', $c['amenities'] ),
			'url'      => $c['url'],
		);
	}
	return $rows;
}

/** Neutrální fallback karet, když ani Kategorie pokojů, ani Core kategorie nic nedají — jednoduchý seznam, ne rozbitý layout. */
function gridc_render_room_cards_fallback( array $rows ) {
	ob_start(); ?>
	<div class="rooms-grid grid-component grid-component--rooms-cards-fallback">
	  <?php foreach ( $rows as $r ) : $feat = gridc_pipe_list( gridc_row_val( $r, 'feat' ) ); ?>
	  <article class="room-card">
	    <?php if ( gridc_row_val( $r, 'img' ) ) : ?><img src="<?php echo esc_url( $r['img'] ); ?>" alt="<?php echo esc_attr( gridc_row_val( $r, 'title' ) ); ?>" loading="lazy"><?php endif; ?>
	    <h3><?php echo esc_html( gridc_row_val( $r, 'title' ) ); ?></h3>
	    <p><?php echo esc_html( gridc_row_val( $r, 'desc' ) ); ?></p>
	    <?php if ( $feat ) : ?><ul><?php foreach ( $feat as $f ) { echo '<li>' . esc_html( $f ) . '</li>'; } ?></ul><?php endif; ?>
	    <?php if ( gridc_row_val( $r, 'url' ) ) : ?><a class="sec-more" href="<?php echo esc_url( $r['url'] ); ?>">Detail <span aria-hidden="true">→</span></a><?php endif; ?>
	  </article>
	  <?php endforeach; ?>
	</div>
	<?php return ob_get_clean();
}

/**
 * DŮLEŽITÉ: [grid_rooms_cards] a [grid_rooms_table] tady NEJSOU registrované
 * přes add_shortcode() — ty tagy vlastní plugin „Kategorie pokojů" (spec
 * GRID-SUITE-04 §7). Kdyby je Components registrovala taky, o to, čí verze
 * "vyhraje", by rozhodovalo jen abecední pořadí načtení pluginů
 * (garry-kategorie-pokoju < gridhotel-components), takže by fallback tady
 * mohl nečekaně přebít skutečnou implementaci. Použití je proto VÝHRADNĚ
 * přes gridc_render_module() níže — přímé volání funkce, ne druhá shortcode
 * registrace téhož tagu.
 */
function gridc_render_room_table_fallback( array $rows ) {
	ob_start(); ?>
	<div class="rd-tablewrap grid-component grid-component--rooms-table-fallback"><table class="rd-table">
	  <caption class="screen-reader-text">Srovnání kategorií pokojů</caption>
	  <thead><tr><th scope="col">Kategorie</th><th scope="col">Kapacita</th><th scope="col">Velikost</th><th scope="col">Postel</th></tr></thead>
	  <tbody>
	    <?php foreach ( $rows as $r ) : ?>
	    <tr>
	      <th scope="row"><?php echo esc_html( gridc_row_val( $r, 'title' ) ); ?></th>
	      <td><?php echo esc_html( gridc_row_val( $r, 'kapacita', '—' ) ); ?></td>
	      <td><?php echo esc_html( gridc_row_val( $r, 'velikost', '—' ) ); ?></td>
	      <td><?php echo esc_html( gridc_row_val( $r, 'postel', '—' ) ); ?></td>
	    </tr>
	    <?php endforeach; ?>
	  </tbody>
	</table></div>
	<?php return ob_get_clean();
}
/** Přehled pokojů + vybavení + „dobré vědět" — na stránce Ubytování (ne homepage). */
function gridc_render_stay_info( array $rooms ) {
	$amenities = array(
		'Parkování zdarma (auta i autobusy)', 'Wi-Fi zdarma', 'Restaurace &amp; bar', 'Recepce 24/7',
		'Bezbariérový přístup', 'Nekuřácký hotel', 'Individuální klimatizace', 'Snídaňový bufet',
		'Pokojová služba', 'Denní úklid', 'Zvířata za poplatek', 'Doprava na letiště (za poplatek)',
	);
	$know = array(
		array( 'Check-in', '14:00 – 24:00' ),
		array( 'Check-out', 'do 10:00' ),
		array( 'Děti', 'Vítány; přistýlky dle typu pokoje a kapacity' ),
		array( 'Zvířata', 'Povolena za poplatek (na vyžádání)' ),
		array( 'Platby', 'Platební karty (Visa, Mastercard, Maestro) i v hotovosti' ),
		array( 'Kouření', 'Ve všech vnitřních prostorách zakázáno' ),
		array( 'Storno', 'Dle podmínek konkrétní rezervace (viz Obchodní podmínky)' ),
		array( 'Jazyky personálu', 'Čeština, angličtina' ),
	);
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--stay-info" style="padding-top:0">
	  <div class="wrap" style="max-width:1000px">
	    <span class="kicker">Přehled pokojů</span>
	    <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin:14px 0 20px">64 pokojů a apartmá ve čtyřech kategoriích</h2>
	    <?php echo gridc_render_module( 'room_comparison', 'grid_rooms_table', array(), gridc_render_room_table_fallback( $rooms ) ); ?>

	    <span class="kicker" style="margin-top:44px;display:inline-flex">Vybavení &amp; služby</span>
	    <h2 style="font-size:clamp(1.6rem,3.4vw,2.4rem);margin:12px 0 18px">Co u nás najdete</h2>
	    <ul class="amenity-grid"><?php foreach ( $amenities as $a ) : ?><li><?php echo wp_kses_post( $a ); ?></li><?php endforeach; ?></ul>

	    <span class="kicker" style="margin-top:44px;display:inline-flex">Dobré vědět</span>
	    <h2 style="font-size:clamp(1.6rem,3.4vw,2.4rem);margin:12px 0 18px">Podmínky pobytu</h2>
	    <dl class="know-list"><?php foreach ( $know as $k ) : ?><div><dt><?php echo esc_html( $k[0] ); ?></dt><dd><?php echo esc_html( $k[1] ); ?></dd></div><?php endforeach; ?></dl>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* [grid_rooms] — T3 sekce (homepage) / plná stránka Ubytování (podstránka). */
function gridc_sc_rooms() {
	$defaults = array(
		array( 'num' => '3.1 / STANDARD', 'title' => 'Standard', 'img' => gridc_fallback_image( 'hotel-exterier.jpg' ), 'desc' => 'Komfortní pokoje evropského standardu **** s klidnou orientací do areálu.', 'feat' => '1–2 osoby|klimatizace|TV 40" HDMI|trezor · minibar', 'pocet' => '30', 'kapacita' => '1–2', 'velikost' => '24', 'url' => home_url( '/kategorie-pokoje/standard/' ) ),
		array( 'num' => '3.2 / SUPERIOR', 'title' => 'Superior', 'img' => gridc_fallback_image( 'pokoj-superior.jpg' ), 'desc' => 'Orientované výhledem na centrum dění brněnského okruhu i okolní lesy. Denně kávový a čajový set, župan, pantofle a minerální voda.', 'feat' => '2 osoby|track view|župan · set', 'pocet' => '20', 'kapacita' => '2', 'velikost' => '24', 'url' => home_url( '/kategorie-pokoje/superior/' ) ),
		array( 'num' => '3.3 / SUPERIOR PLUS', 'title' => 'Superior Plus', 'img' => gridc_fallback_image( 'koupelna.jpg' ), 'desc' => 'Vše ze Superior — navíc terasa s posezením a výhledem na centrum okruhu i okolí.', 'feat' => '2–3 osoby|terasa|track view', 'pocet' => '10', 'kapacita' => '2–3', 'velikost' => '24', 'url' => home_url( '/kategorie-pokoje/superior-plus/' ) ),
		array( 'num' => '3.4 / APARTMÁ & APARTMÁ SUPERIOR', 'title' => 'Apartmá', 'img' => gridc_fallback_image( 'pokoj-apartma.jpg' ), 'desc' => 'Nadstandardní ubytování s nejlepším výhledem na město, okruh či paddock. Interiér 47–59 m² plus terasy až 47 m², King Size postele a dvě TV 43".', 'feat' => '2–4 osoby|47–59 m²|terasa až 47 m²|King Size', 'pocet' => '4', 'kapacita' => '2–4', 'velikost' => '47–59', 'url' => home_url( '/kategorie-pokoje/apartma-a-apartma-plus/' ) ),
	);
	$rooms = gridc_room_rows_from_core( $defaults );

	ob_start(); ?>
	<section id="pokoje" class="sec sec-light sec-pad grid-component grid-component--rooms">
	  <span class="sec-tag">T3</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px">
	      <span class="kicker">T3 · Ubytování · 60 pokojů &amp; 4 apartmá</span>
	      <h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Kde po jízdě zastavíš</h2>
	      <p style="max-width:60ch;margin-top:14px;color:var(--muted)">Vyberte si z 64 vysoce komfortních pokojů a apartmá splňujících veškeré parametry evropského standardu ****. Všechny pokoje mají klimatizaci, Wi-Fi, TV, trezor, možnost plného zatemnění a jsou vhodné i pro handicapované hosty.</p>
	    </div>
	    <?php echo gridc_render_module( 'room_comparison', 'grid_rooms_cards', array(), gridc_render_room_cards_fallback( $rooms ) ); ?>
	    <div class="pobyt-chips" aria-label="Součástí pobytu">
	      <span>Snídaňový GRID Buffet</span><span>Wi-Fi zdarma</span><span>Parkoviště zdarma</span><span>Klimatizace</span><span>Recepce 24/7</span><span>Bezbariérový</span>
	    </div>
	    <?php echo gridc_section_more( array( 'ubytovani', 'pokoje', 'pokoje-a-apartmany' ), 'Všechny pokoje a apartmá' ); ?>
	  </div>
	</section>
	<?php
	if ( ! is_front_page() ) {
		echo gridc_render_stay_info( $rooms );
	}
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_rooms', 'gridc_sc_rooms' );
