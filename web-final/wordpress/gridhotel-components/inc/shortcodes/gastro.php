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
		$rows[] = array(
			'title'          => $it['title'],
			'img'            => $img,
			'hours'          => $it['hours'],
			'text'           => $it['text'],
			'list'           => implode( '|', $it['list'] ),
			'menu_venue'     => $it['menu_venue'],
			'menu_link_mode' => $it['menu_link_mode'],
			'menu_link_url'  => $it['menu_link_url'],
		);
	}
	return $rows;
}

/**
 * [grid_gastro_foto provoz="hotelova-restaurace" foto="restaurace-paddock.jpg"]
 *
 * Fotka provozu na kartě v sekci T5. Bere se z náhledového obrázku CPT „Gastro
 * provoz" — personál ji tedy mění v administraci u provozu, ne v layoutu stránky.
 * Když náhledový obrázek není nastavený, použije se záložní soubor z motivu.
 */
function gridc_sc_gastro_foto( $atts = array() ) {
	$a = shortcode_atts( array( 'provoz' => '', 'id' => 0, 'foto' => '', 'alt' => '' ), $atts );

	$id = (int) $a['id'];
	if ( ! $id && $a['provoz'] ) {
		$p = get_page_by_path( sanitize_title( $a['provoz'] ), OBJECT, 'grid_gastro' );
		if ( $p ) $id = (int) $p->ID;
	}

	$src = $id ? get_the_post_thumbnail_url( $id, 'large' ) : '';
	if ( ! $src ) $src = gridc_fallback_image( $a['foto'] ? $a['foto'] : 'restaurace-paddock.jpg' );

	$alt = $a['alt'] ? $a['alt'] : ( $id ? get_the_title( $id ) : '' );

	return '<div class="g-img"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt )
		. '" loading="lazy"></div>';
}
gridc_register_shortcode( 'grid_gastro_foto', 'gridc_sc_gastro_foto' );

/** Identifikátor panelu jídelníčku podle provozu. */
function gridc_gastro_menu_slot( $item ) {
	$title = mb_strtolower( (string) gridc_row_val( $item, 'title' ) );
	if ( false !== strpos( $title, 'paddock' ) ) return 'paddock';
	if ( false !== strpos( $title, 'club' ) || false !== strpos( $title, 'bar' ) ) return 'bar';
	return 'hotel';
}

/** Propojení provozu Gastro s provozem v pluginu GARRY Denní menu. */
function gridc_gastro_menu_venue( $item ) {
	$venue = sanitize_key( (string) gridc_row_val( $item, 'menu_venue' ) );
	if ( $venue ) return $venue;
	$slot = gridc_gastro_menu_slot( $item );
	$fallbacks = array(
		'hotel'   => array( 'hotelova-restaurace', 'restaurace' ),
		'paddock' => array( 'paddock-restaurant', 'paddock' ),
		'bar'     => array( 'hotel-bar', 'grid-club', 'bar' ),
	);
	$options = function_exists( 'garry_menu_venue_options' ) ? (array) garry_menu_venue_options() : array();
	foreach ( $fallbacks[ $slot ] as $candidate ) {
		if ( empty( $options ) || isset( $options[ $candidate ] ) ) return $candidate;
	}
	return '';
}

/** Odkaz z karty: externí/PDF má přednost, jinak kotva filtrovaného panelu. */
function gridc_gastro_menu_href( $item ) {
	if ( 'external' === gridc_row_val( $item, 'menu_link_mode' ) && gridc_row_val( $item, 'menu_link_url' ) ) {
		return gridc_row_val( $item, 'menu_link_url' );
	}
	$detail = gridc_detail_url( array( 'gastronomie', 'gastro' ) );
	return $detail ? $detail . '#jidelnicek-' . gridc_gastro_menu_slot( $item ) : '#jidelnicek-' . gridc_gastro_menu_slot( $item );
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
	          <h2><?php echo esc_html( gridc_row_val( $it, 'title' ) ); ?></h2>
	          <div class="g-hours"><?php echo esc_html( gridc_row_val( $it, 'hours' ) ); ?></div>
	          <p><?php echo esc_html( gridc_row_val( $it, 'text' ) ); ?></p>
		          <ul class="g-list"><?php foreach ( $rows as $li ) : $parts = explode( '=', $li ); $lbl = $parts[0] ?? ''; $val = $parts[1] ?? ''; ?><li><?php echo wp_kses_post( $lbl ); ?><?php if ( '' !== $val ) { echo ' <b>' . wp_kses_post( $val ) . '</b>'; } ?></li><?php endforeach; ?></ul>
		          <a class="sec-more gcard-menu-link" href="<?php echo esc_url( gridc_gastro_menu_href( $it ) ); ?>">Zobrazit jídelní lístek <span aria-hidden="true">→</span></a>
		        </div>
	      </div>
	      <?php endforeach; ?>
	    </div>
	    <?php
	    echo gridc_section_more( array( 'gastronomie', 'gastro' ), 'Detail provozů' );
	    if ( is_front_page() && gridc_detail_url( array( 'gastronomie', 'gastro' ) ) ) : ?>
	      <a class="sec-more" href="<?php echo esc_url( gridc_detail_url( array( 'gastronomie', 'gastro' ) ) ); ?>#jidelnicek" style="margin-left:26px">Aktuální týdenní menu <span aria-hidden="true">→</span></a>
	    <?php endif; ?>
	  </div>
	</section>
	<?php
	if ( ! is_front_page() ) {
		echo gridc_render_gastro_extra( $items );
	}
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_gastro', 'gridc_sc_gastro' );

/** Catering (samostatná služba) + týdenní jídelníček — jen na podstránce Gastronomie. */
function gridc_render_gastro_extra( array $items = array() ) {
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
	<?php echo gridc_render_gastro_menu_sections( $items ); ?>
	<?php return ob_get_clean();
}

/** Tři menu panely, data vždy z pluginu GARRY Denní menu přes jeho shortcody. */
function gridc_render_gastro_menu_sections( array $items ) {
	$venues = array( 'hotel' => '', 'paddock' => '', 'bar' => '' );
	foreach ( $items as $item ) {
		$venues[ gridc_gastro_menu_slot( $item ) ] = gridc_gastro_menu_venue( $item );
	}
	$panels = array(
		'hotel'   => array( 'label' => 'Restaurace · Týdenní menu', 'title' => 'Jídelníček tohoto týdne', 'type' => 'menu' ),
		'paddock' => array( 'label' => 'PADDOCK RESTAURANT · TÝDENNÍ MENU', 'title' => 'Jídelníček tohoto týdne', 'type' => 'menu' ),
		'bar'     => array( 'label' => 'HOTEL BAR · NÁPOJOVÉ MENU', 'title' => 'Nápojový lístek', 'type' => 'drinks' ),
	);
	if ( function_exists( 'gridc_flag_gastro_menu_needed' ) ) gridc_flag_gastro_menu_needed();
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--weekly-menu" id="jidelnicek">
	  <div class="wrap">
	    <div class="gastro-menu-filter" role="group" aria-label="Filtrovat jídelní lístek">
	      <button class="gal-fbtn active" type="button" data-gastro-menu-target="all" aria-pressed="true">Ukázat vše</button>
	      <?php foreach ( $panels as $slot => $panel ) : ?><button class="gal-fbtn" type="button" data-gastro-menu-target="<?php echo esc_attr( $slot ); ?>" aria-pressed="false"><?php echo esc_html( $panel['label'] ); ?></button><?php endforeach; ?>
	    </div>
	    <?php foreach ( $panels as $slot => $panel ) : $venue = $venues[ $slot ]; ?>
	      <section class="gastro-menu-panel" id="jidelnicek-<?php echo esc_attr( $slot ); ?>" data-gastro-menu-panel="<?php echo esc_attr( $slot ); ?>" aria-labelledby="jidelnicek-<?php echo esc_attr( $slot ); ?>-title">
	        <span class="kicker"><?php echo esc_html( $panel['label'] ); ?></span>
	        <h2 id="jidelnicek-<?php echo esc_attr( $slot ); ?>-title" style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px"><?php echo esc_html( $panel['title'] ); ?></h2>
	        <?php
	        if ( 'drinks' === $panel['type'] && shortcode_exists( 'grid_napojovy_listek' ) ) {
	          $content = do_shortcode( '[grid_napojovy_listek provoz="' . esc_attr( $venue ) . '"]' );
	        } else {
	          $content = gridc_render_module( 'weekly_menu', 'grid_menu_tydne', array( 'provoz' => $venue, 'typ' => 'cely' ), '' );
	        }
	        echo $content ? $content : gridc_render_weekly_menu_fallback( $panel['title'] );
	        ?>
	      </section>
	    <?php endforeach; ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/** Fallback dle GRID-SUITE-02 §9 tabulky — "skrytá sekce / CTA dle nastavení", nikdy prázdná rozbitá sekce. */
function gridc_render_weekly_menu_fallback( $title = 'Aktuální nabídka' ) {
	return '<p class="description" style="color:var(--muted)">' . esc_html( $title ) . ' se právě připravuje. Pro aktuální nabídku se prosím zeptejte na recepci nebo <a href="' . esc_url( gridc_nav_url( '#kontakt' ) ) . '">nás kontaktujte</a>.</p>';
}
