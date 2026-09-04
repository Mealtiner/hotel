<?php
/**
 * GRID Hotel Components — gastro a týdenní nabídka (GRID-SUITE-02 §4).
 * Týdenní menu je skládané přes ModuleRenderer (feature weekly_menu, tag
 * grid_menu_tydne) — vlastní ho plugin Týdenní menu, ne Components.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function gridc_gastro_rows_from_core( array $defaults ) {
	if ( ! function_exists( 'gridhotel_get_gastro_locations' ) ) {
		return $defaults;
	}
	$items = gridhotel_get_gastro_locations();
	if ( empty( $items ) ) {
		return $defaults;
	}
	$imgs = array( 'restaurace-paddock.jpg', 'catering-dezerty.jpg', 'catering-syry.jpg' );
	$rows = array();
	foreach ( $items as $i => $it ) {
		$img = $it['id'] ? get_the_post_thumbnail_url( $it['id'], 'large' ) : '';
		if ( ! $img ) {
			$img = gridc_fallback_image( $imgs[ $i % count( $imgs ) ] );
		}
		$rows[] = array( 'title' => $it['title'], 'img' => $img, 'hours' => $it['hours'], 'text' => $it['text'], 'list' => implode( '|', $it['list'] ) );
	}
	return $rows;
}

/* [grid_gastro] — T5 sekce (homepage) / plná stránka Gastronomie (podstránka). */
function gridc_sc_gastro() {
	$defaults = array(
		array( 'title' => 'Hotelová restaurace', 'img' => gridc_fallback_image( 'restaurace-paddock.jpg' ), 'hours' => 'Snídaně · Oběd · Večeře', 'text' => 'Začněte den bohatou snídaní formou studeného i teplého bufetu v moderně zařízené hotelové restauraci. Přes den denní menu, večer à la carte s výhledem na trať.', 'list' => 'Snídaně=7:00–10:00|Obědy (denní menu)=12:00–15:00|Večeře (à la carte)=18:00–21:30' ),
		array( 'title' => 'PADDOCK Restaurant', 'img' => gridc_fallback_image( 'catering-dezerty.jpg' ), 'hours' => 'Přímo v areálu okruhu', 'text' => 'Přímo v areálu Masarykova okruhu, komfortní posezení až pro 80 hostů. Samoobslužný bufet z národní i mezinárodní kuchyně, salátový bar a dezerty. Venkovní terasa s částečným výhledem do paddocku — ideální i pro společenské události.', 'list' => 'Kapacita=až 80 hostů|Bufet &amp; salátový bar=|Celodenní stravování=' ),
		array( 'title' => 'GRID Club', 'img' => gridc_fallback_image( 'catering-syry.jpg' ), 'hours' => 'Otevřeno 12:00–24:00', 'text' => 'Stylové prostory nedaleko recepce — ideální na pracovní i obchodní schůzky i k relaxaci. Široká nabídka nápojů a lehkého občerstvení a pohodlné posezení na letní terase s výhledem do centra okruhu.', 'list' => 'Koktejlový bar=|Terasa s výhledem=|Afterparty &amp; race víkendy=' ),
	);
	$items = gridc_gastro_rows_from_core( $defaults );

	$gorder = function ( $t ) {
		$t = mb_strtolower( (string) gridc_row_val( $t, 'title' ) );
		if ( false !== strpos( $t, 'paddock' ) ) { return 2; }
		if ( false !== strpos( $t, 'club' ) ) { return 3; }
		return ( false !== strpos( $t, 'restaur' ) ) ? 1 : 4;
	};
	usort( $items, function ( $a, $b ) use ( $gorder ) { return $gorder( $a ) - $gorder( $b ); } );

	ob_start(); ?>
	<section id="restaurace" class="sec sec-light sec-pad grid-component grid-component--gastro">
	  <span class="sec-tag">T5</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px"><span class="kicker">T5 · Gastronomie · Restaurace &amp; bar</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Chuť dne od snídaně po poslední drink</h2><p style="max-width:64ch;margin-top:14px;color:var(--muted)">Od snídaňového bufetu přes denní menu až po večerní à la carte — a k tomu stylový GRID Club a PADDOCK Restaurant přímo u trati. Postaráme se i o catering na vaši svatbu či oslavu.</p></div>
	    <div class="gastro">
	      <?php $d = 1; foreach ( $items as $it ) :
	        $img  = gridc_row_val( $it, 'img' );
	        $rows = gridc_pipe_list( gridc_row_val( $it, 'list' ) );
	        ?>
	      <div class="gcard reveal d<?php echo (int) $d++; ?>">
	        <div class="g-img"><img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( gridc_row_val( $it, 'title' ) ); ?>" loading="lazy"></div>
	        <div class="g-body">
	          <h3><?php echo esc_html( gridc_row_val( $it, 'title' ) ); ?></h3>
	          <div class="g-hours"><?php echo esc_html( gridc_row_val( $it, 'hours' ) ); ?></div>
	          <p><?php echo esc_html( gridc_row_val( $it, 'text' ) ); ?></p>
	          <ul class="g-list"><?php foreach ( $rows as $li ) : $parts = explode( '=', $li ); $lbl = $parts[0] ?? ''; $val = $parts[1] ?? ''; ?><li><?php echo wp_kses_post( $lbl ); ?><?php if ( '' !== $val ) { echo ' <b>' . wp_kses_post( $val ) . '</b>'; } ?></li><?php endforeach; ?></ul>
	        </div>
	      </div>
	      <?php endforeach; ?>
	    </div>
	    <?php
	    echo gridc_section_more( array( 'gastronomie', 'gastro' ), 'Celá nabídka gastronomie' );
	    if ( is_front_page() && gridc_detail_url( array( 'gastronomie', 'gastro' ) ) ) : ?>
	      <a class="sec-more" href="<?php echo esc_url( gridc_detail_url( array( 'gastronomie', 'gastro' ) ) ); ?>#jidelnicek" style="margin-left:26px">Aktuální týdenní menu <span aria-hidden="true">→</span></a>
	    <?php endif; ?>
	  </div>
	</section>
	<?php
	if ( ! is_front_page() ) {
		echo gridc_render_gastro_extra();
	}
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_gastro', 'gridc_sc_gastro' );

/** Catering (samostatná služba) + týdenní jídelníček — jen na podstránce Gastronomie. */
function gridc_render_gastro_extra() {
	$firemni = gridc_detail_url( array( 'firemni-akce-svatby', 'firemni' ) );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad grid-component grid-component--catering" id="catering">
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url( gridc_fallback_image( 'catering-syry.jpg' ) ); ?>" alt="Catering GRID HOTEL"></div>
	    <div class="sp-content">
	      <span class="kicker">Služba · Catering</span>
	      <h2>Catering na míru<br>na hotelu i u trati</h2>
	      <p class="lead">Plánujete svatbu, oslavu nebo firemní akci? Postaráme se o kompletní gastro zázemí v prostorách hotelu i přímo v areálu Masarykova okruhu.</p>
	      <p>Sestavíme menu podle vašich představ, zajistíme obsluhu, nápoje i celý průběh akce — od rautu po slavnostní večeři. K tomu stylový bar a doprovodný program u okruhu.</p>
	      <ul class="check-list" style="margin:18px 0">
	        <li>Menu a raut na míru</li><li>Prostory hotelu i areál okruhu</li><li>Obsluha a kompletní organizace</li><li>Bar a nápojový lístek</li><li>Ubytování hostů v 64 pokojích</li>
	      </ul>
	      <div class="rd-actions">
	        <?php if ( $firemni ) : ?><a class="btn" href="<?php echo esc_url( $firemni ); ?>">Firemní akce &amp; svatby</a><?php endif; ?>
	        <a class="btn btn-ghost" href="<?php echo esc_url( gridc_nav_url( '#kontakt' ) ); ?>">Poptat catering</a>
	      </div>
	    </div>
	  </div>
	</section>
	<section class="sec sec-light sec-pad grid-component grid-component--weekly-menu" id="jidelnicek">
	  <div class="wrap">
	    <span class="kicker">Restaurace · Týdenní menu</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px">Jídelníček tohoto týdne</h2>
	    <?php echo gridc_render_module( 'weekly_menu', 'grid_menu_tydne', array(), gridc_render_weekly_menu_fallback() ); ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/** Fallback dle GRID-SUITE-02 §9 tabulky — "skrytá sekce / CTA dle nastavení", nikdy prázdná rozbitá sekce. */
function gridc_render_weekly_menu_fallback() {
	return '<p class="description" style="color:var(--muted)">Aktuální týdenní menu se právě připravuje. Pro dnešní nabídku se prosím zeptejte na recepci nebo <a href="' . esc_url( gridc_nav_url( '#kontakt' ) ) . '">nás kontaktujte</a>.</p>';
}
