<?php
/**
 * GRID Hotel — sekční shortcody
 * Každá sekce návrhu = jeden shortcode. Obsah se čte z ACF (Options),
 * s plnými fallbacky, takže web renderuje i bez vyplněného ACF.
 *
 * Dostupné shortcody:
 *  [grid_header] [grid_tracknav] [grid_telemetry]
 *  [grid_hero] [grid_booking] [grid_vstupy] [grid_pribeh]
 *  [grid_rooms] [grid_zazitky] [grid_gastro] [grid_season]
 *  [grid_firemni] [grid_reference] [grid_final] [grid_footer]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* URL helpery pro obrázky child theme */
function grid_img( $file ) { return get_stylesheet_directory_uri() . '/assets/foto/' . $file; }
function grid_logo( $file ) { return get_stylesheet_directory_uri() . '/assets/logo/' . $file; }

/* ACF repeater rows nebo default pole */
function grid_rows( $field, $defaults ) {
	if ( function_exists( 'have_rows' ) && have_rows( $field, 'option' ) ) {
		$out = array();
		while ( have_rows( $field, 'option' ) ) { the_row(); $out[] = get_row( true ); }
		if ( ! empty( $out ) ) return $out;
	}
	return $defaults;
}
function grid_row_val( $row, $key, $fallback = '' ) {
	if ( is_array( $row ) && isset( $row[ $key ] ) && $row[ $key ] !== '' && $row[ $key ] !== null ) return $row[ $key ];
	return $fallback;
}

/* ---- CPT (gridhotel-core) → řádky sekce, jinak fallback ----
   Pokud existují příspěvky daného typu, sekce se plní z nich (přes plugin),
   jinak se použijí návrhové výchozí hodnoty. */
function grid_cpt_posts( $pt ) {
	if ( ! post_type_exists( $pt ) ) return array();
	$q = new WP_Query( array(
		'post_type' => $pt, 'posts_per_page' => -1, 'post_status' => 'publish',
		'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true,
	) );
	return $q->posts;
}
function grid_pf( $id, $name, $fb = '' ) {
	if ( function_exists( 'get_field' ) ) { $v = get_field( $name, $id ); if ( $v !== null && $v !== '' && $v !== false ) return $v; }
	$m = get_post_meta( $id, $name, true );
	return ( $m !== '' && $m !== null ) ? $m : $fb;
}
/* Výchozí obohacená data zážitků (dle původní stránky + Autodrom Brno / Polygon Brno).
 * Použije se na detailu zážitku, když nejsou vyplněná ACF pole. Klíč = číslo zážitku. */
function grid_exp_defaults( $num ) {
	$d = array(
		'4.1' => array(
			'perex' => 'Usedněte do profesionálního dynamického simulátoru s reálnou geometrií Masarykova okruhu — ideální rozjížďka před ostrým výjezdem na trať i zábava pro celou partu.',
			'parametry' => 'Délka=15–30 min|Pro koho=od 12 let|Kde=Autodrom Brno|Obtížnost=pro začátečníky i závodníky',
			'odkaz' => 'https://www.automotodrombrno.cz/', 'odkaz_text' => 'Web Autodromu Brno →' ),
		'4.2' => array(
			'perex' => 'Silné motokáry i obratná pitbike motorka pár metrů od velkého závodního okruhu na speciální dráze. Měření časů a souboj o nejrychlejší kolo.',
			'parametry' => 'Kde=speciální dráha u okruhu|Pro koho=děti i dospělí|Měření časů=ano|Skupiny=ideální pro firmy a oslavy',
			'odkaz' => 'https://www.automotodrombrno.cz/', 'odkaz_text' => 'Web Autodromu Brno →' ),
		'4.3' => array(
			'perex' => 'Škola bezpečné jízdy v moderním tréninkovém centru Polygon Brno — čtyři úrovně školy smyku od základů po pokročilou techniku ovládání vozu.',
			'parametry' => 'Úrovně=Compact · Intensiv · Intensiv+ · Advanced|Kde=Polygon Brno|Vozidlo=vlastní i zapůjčené|Délka=půldenní / denní',
			'odkaz' => 'https://www.polygonbrno.cz/', 'odkaz_text' => 'Rezervovat na Polygonu Brno →' ),
		'4.4' => array(
			'perex' => 'Adrenalinové kurzy Polygonu Brno pro ty, kdo chtějí víc — řízený drift a speciální „gangster" program za volantem na uzavřené ploše.',
			'parametry' => 'Kde=Polygon Brno|Pro koho=držitelé řidičského průkazu|Vozidlo=zapůjčené|Zaměření=drift & ovládání smyku',
			'odkaz' => 'https://www.polygonbrno.cz/', 'odkaz_text' => 'Termíny na Polygonu Brno →' ),
		'4.5' => array(
			'perex' => 'Akreditovaný kurz bezpečné jízdy pro odečet trestných bodů — teorie i praxe na Polygonu Brno. Vhodné i jako firemní školení řidičů na míru.',
			'parametry' => 'Odečet=až 3 body|Akreditace=Ministerstvo dopravy ČR|Kde=Polygon Brno|Forma=teorie + praktická jízda',
			'odkaz' => 'https://www.polygonbrno.cz/', 'odkaz_text' => 'Přihlásit na Polygonu Brno →' ),
		'4.6' => array(
			'perex' => 'Zážitek u okruhu jako dárek — pobyt, simulátor, motokáry nebo kurz Polygonu v libovolné hodnotě. Poukaz pošleme elektronicky i tištěně.',
			'parametry' => 'Forma=elektronicky i tištěně|Platí na=pobyt i zážitky|Objednávka=e-mailem na reservations@gridhotel.cz',
			'odkaz' => '', 'odkaz_text' => '' ),
	);
	return isset( $d[ $num ] ) ? $d[ $num ] : array( 'perex' => '', 'parametry' => '', 'odkaz' => '', 'odkaz_text' => '' );
}

function grid_section_rows( $pt, $mapper, $defaults ) {
	$posts = grid_cpt_posts( $pt );
	if ( ! empty( $posts ) ) {
		$rows = array();
		foreach ( $posts as $i => $p ) { $rows[] = call_user_func( $mapper, $p, $i ); }
		return $rows;
	}
	return $defaults;
}
function grid_map_room( $p, $i ) {
	$imgs = array( 'hotel-exterier.jpg','pokoj-superior.jpg','koupelna.jpg','pokoj-apartma.jpg' );
	$img = get_the_post_thumbnail_url( $p->ID, 'large' ); if ( ! $img ) $img = grid_img( $imgs[ $i % count( $imgs ) ] );
	return array( 'num'=>grid_pf($p->ID,'num'), 'title'=>get_the_title($p), 'img'=>$img, 'desc'=>grid_pf($p->ID,'desc'), 'feat'=>grid_pf($p->ID,'feat') );
}
function grid_map_exp( $p, $i ) {
	$odkaz = grid_pf( $p->ID, 'odkaz' );
	return array( 'num'=>grid_pf($p->ID,'num'), 'title'=>get_the_title($p), 'text'=>grid_pf($p->ID,'text'), 'cta'=>grid_pf($p->ID,'cta'), 'url'=> $odkaz ? $odkaz : get_permalink( $p->ID ) );
}
function grid_map_event( $p, $i ) {
	return array( 'date'=>grid_pf($p->ID,'date'), 'name'=>get_the_title($p), 'desc'=>grid_pf($p->ID,'desc'), 'status'=>grid_pf($p->ID,'status','free') );
}
function grid_map_gastro( $p, $i ) {
	$imgs = array( 'restaurace-paddock.jpg','catering-syry.jpg','catering-dezerty.jpg' );
	$img = get_the_post_thumbnail_url( $p->ID, 'large' ); if ( ! $img ) $img = grid_img( $imgs[ $i % count( $imgs ) ] );
	return array( 'title'=>get_the_title($p), 'img'=>$img, 'hours'=>grid_pf($p->ID,'hours'), 'text'=>grid_pf($p->ID,'text'), 'list'=>grid_pf($p->ID,'list') );
}
function grid_map_review( $p, $i ) {
	return array( 'text'=>grid_pf($p->ID,'text'), 'who'=>grid_pf($p->ID,'who') );
}

/* ---- Kategorie pokojů (taxonomie) → karty na homepage ---- */
function grid_term_field( $term_id, $name, $fb = '' ) {
	if ( function_exists( 'get_field' ) ) { $v = get_field( $name, 'term_' . $term_id ); if ( $v !== null && $v !== '' && $v !== false ) return $v; }
	$m = get_term_meta( $term_id, $name, true );
	return ( $m !== '' && $m !== null ) ? $m : $fb;
}
function grid_gallery_cover( $g ) {
	if ( empty( $g ) || ! is_array( $g ) ) return '';
	$first = $g[0];
	if ( is_array( $first ) ) return ! empty( $first['sizes']['large'] ) ? $first['sizes']['large'] : ( ! empty( $first['url'] ) ? $first['url'] : '' );
	if ( is_numeric( $first ) ) return wp_get_attachment_image_url( $first, 'large' );
	return '';
}
function grid_room_types_rows( $defaults ) {
	if ( ! taxonomy_exists( 'grid_room_cat' ) ) return $defaults;
	$terms = get_terms( array( 'taxonomy' => 'grid_room_cat', 'hide_empty' => false ) );
	if ( empty( $terms ) || is_wp_error( $terms ) ) return $defaults;
	usort( $terms, function ( $a, $b ) {
		return (int) grid_term_field( $a->term_id, 'poradi', 999 ) <=> (int) grid_term_field( $b->term_id, 'poradi', 999 );
	} );
	$imgs = array( 'hotel-exterier.jpg','pokoj-superior.jpg','koupelna.jpg','pokoj-apartma.jpg' );
	$rows = array();
	foreach ( $terms as $i => $t ) {
		$cover = grid_term_field( $t->term_id, 'nahled', '' );
		if ( is_array( $cover ) ) $cover = isset( $cover['url'] ) ? $cover['url'] : '';
		if ( ! $cover ) $cover = grid_gallery_cover( grid_term_field( $t->term_id, 'galerie', null ) );
		if ( ! $cover ) $cover = grid_img( $imgs[ $i % count( $imgs ) ] );
		$link = get_term_link( $t );
		$rows[] = array(
			'num'   => grid_term_field( $t->term_id, 'kod', '' ),
			'title' => $t->name,
			'img'   => $cover,
			'desc'  => grid_term_field( $t->term_id, 'kratky_popis', $t->description ),
			'feat'  => grid_term_field( $t->term_id, 'stitky', '' ),
			'pocet' => grid_term_field( $t->term_id, 'pocet', '' ),
			'kapacita' => grid_term_field( $t->term_id, 'kapacita', '' ),
			'velikost' => grid_term_field( $t->term_id, 'velikost', '' ),
			'postel'   => grid_term_field( $t->term_id, 'postel', '' ),
			'koupelna' => grid_term_field( $t->term_id, 'koupelna', '' ),
			'zarizeni' => grid_term_field( $t->term_id, 'zarizeni', '' ),
			'url'   => is_wp_error( $link ) ? '' : $link,
		);
	}
	return $rows;
}

/* Přehled pokojů + vybavení + „dobré vědět" — pro stránku Ubytování (data i z Bookingu). */
function grid_stay_info( $rooms ) {
	$total = 0; $hasPocet = false;
	foreach ( $rooms as $r ) { $p = (int) grid_row_val( $r, 'pocet', 0 ); if ( $p > 0 ) { $total += $p; $hasPocet = true; } }
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
	<section class="sec sec-light sec-pad" style="padding-top:0">
	  <div class="wrap" style="max-width:1000px">
	    <span class="kicker">Přehled pokojů</span>
	    <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin:14px 0 20px">64 pokojů a apartmá ve čtyřech kategoriích</h2>
	    [grid_rooms_table]

	    <span class="kicker" style="margin-top:44px;display:inline-flex">Vybavení &amp; služby</span>
	    <h2 style="font-size:clamp(1.6rem,3.4vw,2.4rem);margin:12px 0 18px">Co u nás najdete</h2>
	    <ul class="amenity-grid">
	      <?php foreach ( $amenities as $a ) : ?><li><?php echo wp_kses_post( $a ); ?></li><?php endforeach; ?>
	    </ul>

	    <span class="kicker" style="margin-top:44px;display:inline-flex">Dobré vědět</span>
	    <h2 style="font-size:clamp(1.6rem,3.4vw,2.4rem);margin:12px 0 18px">Podmínky pobytu</h2>
	    <dl class="know-list">
	      <?php foreach ( $know as $k ) : ?><div><dt><?php echo esc_html( $k[0] ); ?></dt><dd><?php echo esc_html( $k[1] ); ?></dd></div><?php endforeach; ?>
	    </dl>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* Galerie jednotlivého pokoje (vlastní, jinak zděděná z kategorie) — pro detail pokoje */
function grid_room_gallery( $room_id ) {
	$g = function_exists( 'get_field' ) ? get_field( 'galerie', $room_id ) : null;
	if ( empty( $g ) ) {
		$cats = wp_get_post_terms( $room_id, 'grid_room_cat' );
		if ( ! is_wp_error( $cats ) && ! empty( $cats ) && function_exists( 'get_field' ) ) {
			$g = get_field( 'galerie', 'term_' . $cats[0]->term_id );
		}
	}
	return $g;
}

/* Kotvy v menu/patičce: na homepage nech bare #kotvu (plynulý scroll grid.js),
 * na podstránkách předsaď home_url() (naviguje na homepage + skok).
 * Výjimka #kontakt = patička má id="kontakt" na všech stránkách. */
function grid_nav_url( $url ) {
	if ( ! is_string( $url ) || $url === '' || $url[0] !== '#' ) return $url;
	if ( $url === '#kontakt' ) {
		$k = grid_detail_url( array( 'kontakt' ) );      // Kontakt vždy míří na podstránku kontaktu
		if ( $k ) return $k;
		return is_front_page() ? $url : home_url( '/' ) . $url; // fallback: patička (#kontakt)
	}
	if ( is_front_page() ) return $url;
	return home_url( '/' ) . $url;
}

/* Najde URL detailní podstránky podle seznamu kandidátních slugů (první existující). */
function grid_detail_url( $slugs ) {
	if ( ! function_exists( 'get_page_by_path' ) ) return '';
	foreach ( (array) $slugs as $s ) {
		$p = get_page_by_path( $s );
		if ( $p ) return get_permalink( $p );
	}
	return '';
}
/* Odkaz preferenčně na podstránku (pokud existuje), jinak fallback na kotvu (přes grid_nav_url). */
function grid_link_pref( $slugs, $anchor ) {
	$u = grid_detail_url( $slugs );
	return $u ? $u : grid_nav_url( $anchor );
}

/* Odkazy na sociální sítě z GRID Nastavení → jen ty vyplněné. Použití: patička i jinde. */
function grid_social_links() {
	$map = array(
		'soc_facebook'  => array( 'Facebook', 'FB' ),
		'soc_instagram' => array( 'Instagram', 'IG' ),
		'soc_youtube'   => array( 'YouTube', 'YT' ),
		'soc_linkedin'  => array( 'LinkedIn', 'IN' ),
		'soc_tiktok'    => array( 'TikTok', 'TT' ),
		'soc_x'         => array( 'X / Twitter', 'X' ),
	);
	$out = array();
	foreach ( $map as $field => $meta ) {
		$u = function_exists( 'grid_field' ) ? trim( (string) grid_field( $field, '', 'option' ) ) : '';
		if ( $u ) $out[] = array( 'url' => $u, 'label' => $meta[0], 'short' => $meta[1] );
	}
	return $out;
}

/* [grid_socials] — ikony sociálních sítí z GRID Nastavení (jen vyplněné) */
function grid_sc_socials() {
	$socials = grid_social_links();
	if ( ! $socials ) return '';
	$h = '<div class="socials" aria-label="Social">';
	foreach ( $socials as $so ) $h .= '<a href="' . esc_url( $so['url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $so['label'] ) . '">' . esc_html( $so['short'] ) . '</a>';
	return $h . '</div>';
}
add_shortcode( 'grid_socials', 'grid_sc_socials' );

/* [grid_paticka_kontakt] — adresní blok patičky z GRID Nastavení (lokalizované labely) */
function grid_sc_footer_kontakt() {
	$li = array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ grid_lang() ] ?? 0;
	$L = array(
		'recepce' => array( 'Recepce', 'Reception', 'Rezeption' ),
		'rezervace' => array( 'Rezervace', 'Reservations', 'Reservierung' ),
		'shuttle' => array( 'Shuttle bus', 'Shuttle bus', 'Shuttlebus' ),
	);
	$a1 = grid_field( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh', 'option' );
	$a2 = grid_field( 'adresa_2', '641 00 Brno – Žebětín, ČR', 'option' );
	$telr = grid_field( 'tel_recepce', '+420 775 877 721', 'option' );
	$telrez = grid_field( 'tel_rezervace', '+420 775 877 720', 'option' );
	$tels = grid_field( 'tel_shuttle', '+420 775 778 718', 'option' );
	$email = grid_field( 'email', 'info@gridhotel.cz', 'option' );
	$raw = function ( $t ) { return preg_replace( '/\s+/', '', $t ); };
	return '<span class="data">' . esc_html( $a1 ) . '<br>' . esc_html( $a2 ) . '<br>'
		. esc_html( $L['recepce'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $telr ) ) . '">' . esc_html( $telr ) . '</a><br>'
		. esc_html( $L['rezervace'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $telrez ) ) . '">' . esc_html( $telrez ) . '</a><br>'
		. esc_html( $L['shuttle'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $tels ) ) . '">' . esc_html( $tels ) . '</a><br>'
		. '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></span>';
}
add_shortcode( 'grid_paticka_kontakt', 'grid_sc_footer_kontakt' );

/* [grid_video_embed] — YouTube/Vimeo embed z GRID Nastavení → Video (časosběr) */
function grid_sc_video_embed() {
	$embed = grid_video_embed( grid_field( 'video_url', '', 'option' ) );
	if ( $embed ) return '<iframe src="' . esc_url( $embed ) . '" title="GRID Hotel video" loading="lazy" allowfullscreen></iframe>';
	$li = array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ grid_lang() ] ?? 0;
	$note = array( '// Vlož odkaz na video v GRID Nastavení → Video (časosběr).', '// Add the video link in GRID Settings → Video.', '// Videolink in GRID-Einstellungen → Video eintragen.' );
	return '<div style="padding:60px 24px;text-align:center;color:var(--muted);font-family:var(--f-mono);font-size:.8rem">' . esc_html( $note[ $li ] ) . '</div>';
}
add_shortcode( 'grid_video_embed', 'grid_sc_video_embed' );

/* Cíl VŠECH rezervačních CTA: stránka /rezervace/ (pokud existuje),
 * jinak ACF pole rezervace_url, jinak kotva #booking na homepage. */
function grid_rezervace_url() {
	$u = grid_detail_url( array( 'rezervace' ) );
	if ( $u && function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' ) ) {
		$p = get_page_by_path( 'rezervace' );
		if ( $p ) { $t = pll_get_post( $p->ID, pll_current_language() ); if ( $t ) return get_permalink( $t ); }
	}
	if ( $u ) return $u;
	$opt = function_exists( 'grid_field' ) ? trim( (string) grid_field( 'rezervace_url', '', 'option' ) ) : '';
	if ( $opt ) return $opt;
	return home_url( '/#booking' );
}

/* Jemný CTA „na detailní stránku" pro sekci landing page.
 * Zobrazí se JEN na titulní stránce a JEN pokud daná podstránka existuje. */
function grid_section_more( $slugs, $label = 'Zobrazit více' ) {
	if ( ! is_front_page() ) return '';
	$url = grid_detail_url( $slugs );
	if ( ! $url ) return '';
	return '<a class="sec-more" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">→</span></a>';
}

/* YouTube/Vimeo/přímý odkaz → embed URL (prázdné, pokud nejde rozpoznat). */
function grid_video_embed( $url ) {
	$url = trim( (string) $url );
	if ( ! $url ) return '';
	if ( preg_match( '~youtu\.be/([\w-]+)~', $url, $m ) || preg_match( '~youtube\.com/watch\?v=([\w-]+)~', $url, $m ) ) return 'https://www.youtube.com/embed/' . $m[1];
	if ( preg_match( '~vimeo\.com/(\d+)~', $url, $m ) ) return 'https://player.vimeo.com/video/' . $m[1];
	if ( strpos( $url, 'embed' ) !== false || strpos( $url, 'player.' ) !== false ) return $url;
	return '';
}

/* Aktuální jazyk (Polylang, fallback locale) */
function grid_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
/* ACF option v aktuálním jazyce: {name}_{en|de}, fallback {name}, fallback $default */
function grid_field_lang( $name, $default = '' ) {
	$l = grid_lang();
	if ( $l !== 'cs' ) {
		$v = grid_field( $name . '_' . $l, '', 'option' );
		/* POZOR: nefallbackovat na uložené CZ — vrátit jazykový default */
		return ( $v !== '' && $v !== null ) ? $v : $default;
	}
	return grid_field( $name, $default, 'option' );
}

/* ============================================================
 * HEADER (globální) — logo + navigace + mobilní menu
 * ============================================================ */
function grid_sc_header() {
	$logo = grid_field( 'logo_negativ', '', 'option' );
	$logo = $logo ? ( is_array( $logo ) ? $logo['url'] : $logo ) : grid_logo( 'grid-hotel-negativ.png' );
	$nav  = grid_rows( 'nav', array(
		array( 'label' => 'Pokoje', 'url' => '#pokoje' ),
		array( 'label' => 'Zážitky', 'url' => '#zazitky' ),
		array( 'label' => 'Gastronomie', 'url' => '#restaurace' ),
		array( 'label' => 'Sezóna 2026', 'url' => '#sezona' ),
		array( 'label' => 'Firemní akce & svatby', 'url' => '#firemni' ),
		array( 'label' => 'Kontakt', 'url' => '#kontakt' ),
	) );
	$rez = grid_rezervace_url();

	ob_start(); ?>
	<header id="topbar">
	  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="GRID HOTEL — domů">
	    <img src="<?php echo esc_url( $logo ); ?>" alt="GRID HOTEL logo">
	  </a>
	  <div class="topnav">
	    <nav aria-label="Hlavní menu">
	      <?php foreach ( $nav as $n ) : ?>
	        <a href="<?php echo esc_url( grid_nav_url( grid_row_val( $n, 'url', '#' ) ) ); ?>"><?php echo esc_html( grid_row_val( $n, 'label' ) ); ?></a>
	      <?php endforeach; ?>
	    </nav>
	    <div class="lang" aria-label="Jazyk"><a href="#" class="active">CZ</a><a href="#">EN</a><a href="#">DE</a></div>
	    <a href="<?php echo esc_url( grid_nav_url( $rez ) ); ?>" class="btn">Rezervovat</a>
	  </div>
	  <button class="hamburger" id="hamburger" aria-label="Otevřít menu" aria-expanded="false"><span></span><span></span><span></span></button>
	</header>
	<div class="mobile-menu" id="mobileMenu">
	  <button class="mm-close" id="mmClose" aria-label="Zavřít menu">&times;</button>
	  <?php foreach ( $nav as $n ) : ?>
	    <a href="<?php echo esc_url( grid_nav_url( grid_row_val( $n, 'url', '#' ) ) ); ?>"><?php echo esc_html( grid_row_val( $n, 'label' ) ); ?></a>
	  <?php endforeach; ?>
	  <a href="<?php echo esc_url( grid_nav_url( $rez ) ); ?>" class="btn">Rezervovat</a>
	  <div class="lang"><a href="#" class="active">CZ</a><a href="#">EN</a><a href="#">DE</a></div>
	</div>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_header', 'grid_sc_header' );

/* ============================================================
 * TRACK NAV (pravá „trať" — jen pro one-page domov)
 * ============================================================ */
function grid_sc_tracknav() {
	$pts = array(
		array( 'start', 'START', 'Okruh' ), array( 'vstupy', 'T1', 'Vstupy' ),
		array( 'pribeh', 'T2', 'Příběh' ), array( 'pokoje', 'T3', 'Pokoje' ),
		array( 'zazitky', 'T4', 'Zážitky' ), array( 'restaurace', 'T5', 'Gastro' ),
		array( 'sezona', 'T6', 'Sezóna' ), array( 'firemni', 'T7', 'Firmy' ),
		array( 'duvera', 'T8', 'Reference' ), array( 'cil', 'CÍL', 'Rezervace' ),
	);
	ob_start(); ?>
	<aside class="track-progress" aria-label="Postup po trati">
	  <div class="tp-inner">
	    <span class="tp-label">Masaryk Circuit</span>
	    <div class="tp-rail"></div><div class="tp-fill" id="tpFill"></div>
	    <div class="tp-points" id="tpPoints">
	      <?php foreach ( $pts as $p ) : ?>
	      <button class="tp-point" data-target="<?php echo esc_attr( $p[0] ); ?>"><span class="tp-dot"></span><span class="tp-text"><span class="tp-num"><?php echo esc_html( $p[1] ); ?></span><span class="tp-name"><?php echo esc_html( $p[2] ); ?></span></span></button>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</aside>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_tracknav', 'grid_sc_tracknav' );

/* ============================================================
 * TELEMETRY WIDGET (živý — čas + teplota)
 * ============================================================ */
function grid_telemetry_l10n() {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : substr( (string) get_locale(), 0, 2 );
	$t = array(
		'en' => array( 'status'=>'STATUS', 'cas'=>'LOCAL TIME', 'teplota'=>'TEMP · BRNO', 'povrch'=>'TRACK SURFACE', 'otevreno'=>'OPEN', 'sucho'=>'DRY', 'skryt'=>'Hide widget', 'zobrazit'=>'Show widget', 'aria'=>'Live hotel telemetry' ),
		'de' => array( 'status'=>'STATUS', 'cas'=>'ORTSZEIT', 'teplota'=>'TEMP · BRNO', 'povrch'=>'STRECKENBELAG', 'otevreno'=>'GEÖFFNET', 'sucho'=>'TROCKEN', 'skryt'=>'Widget ausblenden', 'zobrazit'=>'Widget anzeigen', 'aria'=>'Live-Telemetrie des Hotels' ),
	);
	$cs = array( 'status'=>'STATUS', 'cas'=>'MÍSTNÍ ČAS', 'teplota'=>'TEPLOTA · BRNO', 'povrch'=>'POVRCH TRATI', 'otevreno'=>'OTEVŘENO', 'sucho'=>'SUCHO', 'skryt'=>'Skrýt widget', 'zobrazit'=>'Zobrazit widget', 'aria'=>'Živá telemetrie hotelu' );
	return isset( $t[ $lang ] ) ? $t[ $lang ] : $cs;
}
function grid_sc_telemetry() {
	$L = grid_telemetry_l10n();
	$status  = grid_field( 'widget_status', $L['otevreno'], 'option' );
	$surface = grid_field( 'widget_povrch', $L['sucho'], 'option' );
	$lat     = grid_field( 'widget_lat', '49.20', 'option' );
	$lon     = grid_field( 'widget_lon', '16.44', 'option' );
	ob_start(); ?>
	<aside class="telemetry-hud" id="hud" aria-label="<?php echo esc_attr( $L['aria'] ); ?>" data-lat="<?php echo esc_attr( $lat ); ?>" data-lon="<?php echo esc_attr( $lon ); ?>">
	  <div class="hud-head"><span class="hud-title">GRID · Live</span><button class="hud-x" id="hudX" aria-label="<?php echo esc_attr( $L['skryt'] ); ?>">&times;</button></div>
	  <div class="hud-body">
	    <div class="hud-row"><span class="k"><span class="dot"></span><?php echo esc_html( $L['status'] ); ?></span><span class="v"><?php echo esc_html( $status ); ?></span></div>
	    <div class="hud-row"><span class="k"><?php echo esc_html( $L['cas'] ); ?></span><span class="v" id="hudClock">--:--:--</span></div>
	    <div class="hud-row"><span class="k"><?php echo esc_html( $L['teplota'] ); ?></span><span class="v hot" id="hudTemp">24&nbsp;°C</span></div>
	    <div class="hud-row"><span class="k"><?php echo esc_html( $L['povrch'] ); ?></span><span class="v" id="hudSurface"><?php echo esc_html( $surface ); ?></span></div>
	  </div>
	</aside>
	<button class="hud-reopen" id="hudReopen" aria-label="<?php echo esc_attr( $L['zobrazit'] ); ?>">Live</button>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_telemetry', 'grid_sc_telemetry' );

/* ============================================================
 * HERO
 * ============================================================ */
function grid_sc_hero() {
	$li = array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ grid_lang() ] ?? 0;
	$FB = array( // fallbacky = aktuální texty webu (CZ/EN/DE)
		'kicker' => array( 'GRID HOTEL · **** · Masarykův okruh', 'GRID HOTEL · **** · Masaryk Circuit', 'GRID HOTEL · **** · Masaryk-Ring' ),
		'h1' => array( 'Přespi <em>uprostřed</em><br>Masarykova okruhu.', 'Sleep <em>in the middle</em><br>of the Masaryk Circuit.', 'Übernachten Sie <em>mitten</em><br>im Masaryk-Ring.' ),
		'sub' => array(
			'Jediný hotel a restaurace přímo v areálu Autodromu Brno. 60 komfortních pokojů a 4 apartmá s výhledem na trať, paddock i okolní lesy — evropský standard ****.',
			'The only hotel and restaurant right inside the Autodrom Brno grounds. 60 comfortable rooms and 4 suites overlooking the track, the paddock and the surrounding forests — European **** standard.',
			'Das einzige Hotel und Restaurant direkt auf dem Gelände des Autodrom Brno. 60 komfortable Zimmer und 4 Appartements mit Blick auf die Strecke, den Paddock und die umliegenden Wälder — europäischer ****-Standard.' ),
		'btn1' => array( 'Rezervovat pobyt', 'Book your stay', 'Aufenthalt buchen' ),
		'btn2' => array( 'Projet okruh ↓', 'Take a lap ↓', 'Eine Runde drehen ↓' ),
		'cue' => array( 'Scroll · Projeď trať', 'Scroll · Ride the track', 'Scroll · Die Strecke fahren' ),
	);
	$img  = grid_field( 'hero_obrazek', '', 'option' ); $img = $img ? ( is_array($img)?$img['url']:$img ) : grid_img( 'okruh-zapad-slunce.jpg' );
	$kick = grid_field_lang( 'hero_kicker', $FB['kicker'][ $li ] );
	$h1   = grid_field_lang( 'hero_nadpis', $FB['h1'][ $li ] );
	$sub  = grid_field_lang( 'hero_podtitulek', $FB['sub'][ $li ] );
	ob_start(); ?>
	<section class="hero sec sec-dark" id="start">
	  <div class="hero-bg" id="heroBg" style="background-image:url('<?php echo esc_url( $img ); ?>')"></div>
	  <div class="hero-overlay"></div>
	  <div class="hero-track"><svg viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-hidden="true"><path id="trackLine" d="M-20 720 C 200 660, 240 470, 430 470 S 720 600, 820 460 S 900 220, 1040 280" fill="none" stroke="#C20E1A" stroke-width="3" stroke-linecap="round" opacity="0.85"/></svg></div>
	  <div class="hero-content">
	    <div class="hero-meta">49.0227° N · 16.4419° E · Autodrom Brno</div>
	    <span class="kicker"><?php echo wp_kses_post( $kick ); ?></span>
	    <h1><?php echo wp_kses_post( $h1 ); ?></h1>
	    <p class="hero-sub"><?php echo wp_kses_post( $sub ); ?></p>
	    <div class="hero-actions"><a href="<?php echo esc_url( grid_rezervace_url() ); ?>" class="btn"><?php echo esc_html( $FB['btn1'][ $li ] ); ?></a><a href="#pribeh" class="btn btn-ghost"><?php echo esc_html( $FB['btn2'][ $li ] ); ?></a></div>
	  </div>
	  <div class="scroll-cue"><?php echo esc_html( $FB['cue'][ $li ] ); ?></div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_hero', 'grid_sc_hero' );

/* ============================================================
 * BOOKING BAR
 * ============================================================ */
function grid_sc_booking() {
	ob_start(); ?>
	<section class="booking" id="booking" aria-label="Rezervace pobytu">
	  <form class="wrap" onsubmit="return false">
	    <div class="bk-field"><label for="bk-in">Příjezd</label><input type="date" id="bk-in" value="2026-08-07"></div>
	    <div class="bk-field"><label for="bk-out">Odjezd</label><input type="date" id="bk-out" value="2026-08-09"></div>
	    <div class="bk-field"><label for="bk-guests">Hosté</label><select id="bk-guests"><option>1 host</option><option selected>2 hosté</option><option>3 hosté</option><option>4 hosté</option><option>5+ hostů</option></select></div>
	    <div class="bk-field"><label for="bk-pokoj">Typ pokoje</label><select id="bk-pokoj"><option>Standard</option><option selected>Superior</option><option>Superior Plus</option><option>Apartmá</option></select></div>
	    <div class="bk-note">Nejlepší cena<br>přímo u hotelu</div>
	    <div class="bk-submit"><a class="btn" href="<?php echo esc_url( grid_rezervace_url() ); ?>">Zkontrolovat dostupnost</a></div>
	  </form>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_booking', 'grid_sc_booking' );

/* ============================================================
 * T1 VSTUPY (light)
 * ============================================================ */
function grid_sc_vstupy() {
	$items = grid_rows( 'vstupy', array(
		array( 'num'=>'01 / ZÁVODY','title'=>'Race víkend','text'=>'Spi metr od trati a vstávej do zvuku motorů. Pobyty na velké akce sezóny 2026 a čekací list.','cta'=>'Sezóna 2026 →','url'=>'#sezona' ),
		array( 'num'=>'02 / FIRMY &amp; SVATBY','title'=>'Akce na míru','text'=>'Firemní večírky, konference, oslavy i svatební hostiny s adrenalinem a cateringem na dosah.','cta'=>'Poptat akci →','url'=>'#firemni' ),
		array( 'num'=>'03 / ZÁŽITEK','title'=>'Motorsport zážitek','text'=>'Simulátor Masarykova okruhu, motokáry, pitbike i škola bezpečné jízdy. Dárkové poukazy skladem.','cta'=>'Vybrat zážitek →','url'=>'#zazitky' ),
		array( 'num'=>'04 / POBYT','title'=>'Klidný pobyt u Brna','text'=>'Komfort **** hotelu, výhled na trať a lesy, snadné parkování. Ideální základna pro výlety.','cta'=>'Prohlédnout pokoje →','url'=>'#pokoje' ),
	) );
	ob_start(); ?>
	<section id="vstupy" class="sec sec-light sec-pad">
	  <span class="sec-tag">T1</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:40px"><span class="kicker">T1 · Vstupy podle motivace</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px;max-width:18ch">Kudy do zatáčky? Vyber si svou odbočku.</h2></div>
	    <div class="entries reveal d1">
	      <?php foreach ( $items as $it ) : ?>
	      <?php /* div + overlay odkaz: bloky uvnitř <a> rozbíjí wpautop v Divi text modulu */ ?>
	      <div class="entry"><a class="entry-link" href="<?php echo esc_url( grid_row_val($it,'url','#') ); ?>" aria-label="<?php echo esc_attr( grid_row_val($it,'title') ); ?>"></a><span class="e-num"><?php echo wp_kses_post( grid_row_val($it,'num') ); ?></span>
	        <h3><?php echo esc_html( grid_row_val($it,'title') ); ?></h3>
	        <p><?php echo esc_html( grid_row_val($it,'text') ); ?></p>
	        <span class="e-arrow"><?php echo esc_html( grid_row_val($it,'cta') ); ?></span>
	      </div>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_vstupy', 'grid_sc_vstupy' );

/* ============================================================
 * T2 PŘÍBĚH (dark carbon, split)
 * ============================================================ */
function grid_sc_pribeh() {
	$img = grid_field( 'pribeh_obrazek', '', 'option' ); $img = $img ? ( is_array($img)?$img['url']:$img ) : grid_img( 'hotel-okruh-leto.jpg' );
	ob_start(); ?>
	<section id="pribeh" class="sec sec-dark carbon">
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
	      <div style="margin-top:26px"><a class="btn btn-ghost" href="<?php echo esc_url( home_url('/o-nas/') ); ?>">Celý příběh hotelu →</a></div>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_pribeh', 'grid_sc_pribeh' );

/* ============================================================
 * T3 POKOJE (light) — repeater
 * ============================================================ */
function grid_sc_rooms() {
	$rooms = grid_room_types_rows( array(
		array('num'=>'3.1 / STANDARD','title'=>'Standard','img'=>grid_img('hotel-exterier.jpg'),'desc'=>'Komfortní pokoje evropského standardu **** s klidnou orientací do areálu.','feat'=>'1–2 osoby|klimatizace|TV 40" HDMI|trezor · minibar','pocet'=>'30','kapacita'=>'1–2','velikost'=>'24','url'=>home_url('/kategorie-pokoje/standard/')),
		array('num'=>'3.2 / SUPERIOR','title'=>'Superior','img'=>grid_img('pokoj-superior.jpg'),'desc'=>'Orientované výhledem na centrum dění brněnského okruhu i okolní lesy. Denně kávový a čajový set, župan, pantofle a minerální voda.','feat'=>'2 osoby|track view|župan · set','pocet'=>'20','kapacita'=>'2','velikost'=>'24','url'=>home_url('/kategorie-pokoje/superior/')),
		array('num'=>'3.3 / SUPERIOR PLUS','title'=>'Superior Plus','img'=>grid_img('koupelna.jpg'),'desc'=>'Vše ze Superior — navíc terasa s posezením a výhledem na centrum okruhu i okolí.','feat'=>'2–3 osoby|terasa|track view','pocet'=>'10','kapacita'=>'2–3','velikost'=>'24','url'=>home_url('/kategorie-pokoje/superior-plus/')),
		array('num'=>'3.4 / APARTMÁ &amp; APARTMÁ SUPERIOR','title'=>'Apartmá','img'=>grid_img('pokoj-apartma.jpg'),'desc'=>'Nadstandardní ubytování s nejlepším výhledem na město, okruh či paddock. Interiér 47–59 m² plus terasy až 47 m², King Size postele a dvě TV 43".','feat'=>'2–4 osoby|47–59 m²|terasa až 47 m²|King Size','pocet'=>'4','kapacita'=>'2–4','velikost'=>'47–59','url'=>home_url('/kategorie-pokoje/apartma-a-apartma-plus/')),
	) );
	ob_start(); ?>
	<section id="pokoje" class="sec sec-light sec-pad">
	  <span class="sec-tag">T3</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px"><span class="kicker">T3 · Ubytování · 60 pokojů &amp; 4 apartmá</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Kde po jízdě zastavíš</h2><p style="max-width:60ch;margin-top:14px;color:var(--muted)">Vyberte si z 64 vysoce komfortních pokojů a apartmá splňujících veškeré parametry evropského standardu ****. Všechny pokoje mají klimatizaci, Wi-Fi, TV, trezor, možnost plného zatemnění a jsou vhodné i pro handicapované hosty.</p></div>
	    [grid_rooms_cards]
	    <div class="pobyt-chips" aria-label="Součástí pobytu">
	      <span>Snídaňový GRID Buffet</span><span>Wi-Fi zdarma</span><span>Parkoviště zdarma</span><span>Klimatizace</span><span>Recepce 24/7</span><span>Bezbariérový</span>
	    </div>
	    <?php echo grid_section_more( array( 'ubytovani', 'pokoje', 'pokoje-a-apartmany' ), 'Všechny pokoje a apartmá' ); ?>
	  </div>
	</section>
	<?php
	/* Na stránce Ubytování (ne homepage) přidáme přehled pokojů, vybavení a „dobré vědět". */
	if ( ! is_front_page() ) echo grid_stay_info( $rooms );
	return ob_get_clean();
}
add_shortcode( 'grid_rooms', 'grid_sc_rooms' );

/* ============================================================
 * T4 ZÁŽITKY (dark carbon) — repeater
 * ============================================================ */
function grid_exp_rows( $defaults ) {
	if ( ! post_type_exists( 'grid_experience' ) ) return $defaults;
	$base = array( 'post_type'=>'grid_experience', 'posts_per_page'=>6, 'post_status'=>'publish', 'orderby'=>'menu_order title', 'order'=>'ASC', 'no_found_rows'=>true );
	$q = new WP_Query( array_merge( $base, array( 'meta_query'=>array( array( 'key'=>'doporuceno', 'value'=>'1', 'compare'=>'=' ) ) ) ) );
	$posts = $q->posts;
	if ( empty( $posts ) ) { $q = new WP_Query( $base ); $posts = $q->posts; } // fallback: prvních 6 dle pořadí
	if ( empty( $posts ) ) return $defaults;
	$rows = array(); foreach ( $posts as $i => $p ) { $rows[] = grid_map_exp( $p, $i ); }
	return $rows;
}

function grid_sc_zazitky() {
	$items = grid_exp_rows( array(
		array('num'=>'4.1','title'=>'Simulátor okruhu','text'=>'Profesionální dynamický simulátor s reálnou geometrií Masarykova okruhu. Ideální rozjížďka před ostrým výjezdem na trať — pro začátečníky i závodníky.','cta'=>'Vyzkoušet →'),
		array('num'=>'4.2','title'=>'Motokáry &amp; pitbike','text'=>'Usedněte do silné motokáry nebo na obratnou pitbike a zajezděte si pár metrů od velkého okruhu na speciální motokárové dráze. Měření časů a souboj o nejlepší kolo.','cta'=>'Rezervovat →'),
		array('num'=>'4.3','title'=>'Škola smyku — Polygon Brno','text'=>'Moderní tréninkové centrum bezpečné jízdy. Úrovně Compact, Intensiv, Intensiv+, Advanced a Dynamic — od základů po pokročilou techniku ovládání vozu.','cta'=>'Vybrat úroveň →'),
		array('num'=>'4.4','title'=>'Drift &amp; Gangster kurz','text'=>'Zážitkové kurzy Polygonu Brno pro ty, kdo chtějí víc adrenalinu — řízený drift a speciální program za volantem.','cta'=>'Termíny →'),
		array('num'=>'4.5','title'=>'Odpočet trestných bodů','text'=>'Akreditovaný kurz bezpečné jízdy pro odečet trestných bodů. Vhodné i jako firemní školení řidičů na míru.','cta'=>'Více →'),
		array('num'=>'4.6','title'=>'Dárkové poukazy','text'=>'Zážitek u okruhu jako dárek — pobyt, simulátor, motokáry nebo kurz Polygonu v libovolné hodnotě. Pošleme i elektronicky.','cta'=>'Koupit poukaz →'),
	) );
	ob_start(); ?>
	<section id="zazitky" class="sec sec-dark carbon sec-pad">
	  <span class="sec-tag">T4</span>
	  <div class="wrap"><div class="reveal" style="margin-bottom:42px"><span class="kicker">T4 · Zážitky u okruhu</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px;max-width:18ch">Užijte si závodní atmosféru Autodromu Brno</h2><p style="max-width:60ch;margin-top:14px;color:var(--muted)">Při pobytu máte jedinečnou možnost usednout do silné motokáry nebo na obratnou pitbike a zajezdit si pár metrů od velkého okruhu na speciální dráze. Adrenalin začíná hned za dveřmi pokoje.</p></div></div>
	  <div class="exp reveal d1">
	    <?php foreach ( $items as $it ) : ?>
	    <?php $u=grid_row_val($it,'url'); $tag=$u?'a':'div'; ?><<?php echo $tag; ?> class="exp-item"<?php if($u) echo ' href="'.esc_url($u).'"'; ?>><span class="x-num"><?php echo esc_html( grid_row_val($it,'num') ); ?></span><h3><?php echo wp_kses_post( grid_row_val($it,'title') ); ?></h3><p><?php echo esc_html( grid_row_val($it,'text') ); ?></p><span class="x-link"><?php echo esc_html( grid_row_val($it,'cta') ); ?></span></<?php echo $tag; ?>>
	    <?php endforeach; ?>
	  </div>
	  <?php $zmore = grid_section_more( array( 'zazitky-u-okruhu', 'zazitky', 'aktivity' ), 'Všechny zážitky a poukazy' ); if ( $zmore ) echo '<div class="wrap" style="margin-top:30px">' . $zmore . '</div>'; ?>
	</section>
	<?php
	/* Na stránce Zážitky (ne na homepage) rovnou připojíme ceník dárkových poukazů. */
	if ( ! is_front_page() && function_exists( 'grid_sc_poukazy' ) ) echo grid_sc_poukazy();
	return ob_get_clean();
}
add_shortcode( 'grid_zazitky', 'grid_sc_zazitky' );

/* ============================================================
 * T5 GASTRONOMIE (light) — repeater
 * ============================================================ */
function grid_sc_gastro() {
	$items = grid_section_rows( 'grid_gastro', 'grid_map_gastro', array(
		array('title'=>'Hotelová restaurace','img'=>grid_img('restaurace-paddock.jpg'),'hours'=>'Snídaně · Oběd · Večeře','text'=>'Začněte den bohatou snídaní formou studeného i teplého bufetu v moderně zařízené hotelové restauraci. Přes den denní menu, večer à la carte s výhledem na trať.','list'=>"Snídaně=7:00–10:00|Obědy (denní menu)=12:00–15:00|Večeře (à la carte)=18:00–21:30"),
		array('title'=>'PADDOCK Restaurant','img'=>grid_img('catering-dezerty.jpg'),'hours'=>'Přímo v areálu okruhu','text'=>'Přímo v areálu Masarykova okruhu, komfortní posezení až pro 80 hostů. Samoobslužný bufet z národní i mezinárodní kuchyně, salátový bar a dezerty. Venkovní terasa s částečným výhledem do paddocku — ideální i pro společenské události.','list'=>"Kapacita=až 80 hostů|Bufet &amp; salátový bar=|Celodenní stravování="),
		array('title'=>'GRID Club','img'=>grid_img('catering-syry.jpg'),'hours'=>'Otevřeno 12:00–24:00','text'=>'Stylové prostory nedaleko recepce — ideální na pracovní i obchodní schůzky i k relaxaci. Široká nabídka nápojů a lehkého občerstvení a pohodlné posezení na letní terase s výhledem do centra okruhu.','list'=>"Koktejlový bar=|Terasa s výhledem=|Afterparty &amp; race víkendy="),
	) );
	/* Vynutit pořadí ráno→poledne→večer: Hotelová restaurace → PADDOCK → GRID Club (i pro data z CPT). */
	$gorder = function( $t ) {
		$t = mb_strtolower( (string) grid_row_val( $t, 'title' ) );
		if ( strpos( $t, 'paddock' ) !== false ) return 2;
		if ( strpos( $t, 'club' )    !== false ) return 3;
		return ( strpos( $t, 'restaur' ) !== false ) ? 1 : 4; // hotelová restaurace první
	};
	usort( $items, function( $a, $b ) use ( $gorder ) { return $gorder( $a ) - $gorder( $b ); } );
	ob_start(); ?>
	<section id="restaurace" class="sec sec-light sec-pad">
	  <span class="sec-tag">T5</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px"><span class="kicker">T5 · Gastronomie · Restaurace &amp; bar</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Chuť dne od snídaně po poslední drink</h2><p style="max-width:64ch;margin-top:14px;color:var(--muted)">Od snídaňového bufetu přes denní menu až po večerní à la carte — a k tomu stylový GRID Club a PADDOCK Restaurant přímo u trati. Postaráme se i o catering na vaši svatbu či oslavu.</p></div>
	    <div class="gastro">
	      <?php $d=1; foreach ( $items as $it ) : $img=grid_row_val($it,'img'); if(is_array($img)) $img=$img['url']; $list=grid_row_val($it,'list'); $rows = is_array($list)?$list:array_filter(array_map('trim',explode('|',(string)$list))); ?>
	      <div class="gcard reveal d<?php echo $d++; ?>">
	        <div class="g-img"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr( grid_row_val($it,'title') ); ?>"></div>
	        <div class="g-body">
	          <h3><?php echo esc_html( grid_row_val($it,'title') ); ?></h3>
	          <div class="g-hours"><?php echo esc_html( grid_row_val($it,'hours') ); ?></div>
	          <p><?php echo esc_html( grid_row_val($it,'text') ); ?></p>
	          <ul class="g-list"><?php foreach ( $rows as $li ) : $parts = is_array($li)?array_values($li):explode('=',$li); $lbl=isset($parts[0])?$parts[0]:''; $val=isset($parts[1])?$parts[1]:''; ?><li><?php echo wp_kses_post($lbl); ?><?php if($val!=='') echo ' <b>'.wp_kses_post($val).'</b>'; ?></li><?php endforeach; ?></ul>
	        </div>
	      </div>
	      <?php endforeach; ?>
	    </div>
	    <?php echo grid_section_more( array( 'gastronomie', 'gastro' ), 'Celá nabídka gastronomie' );
	    if ( is_front_page() && grid_detail_url( array( 'gastronomie', 'gastro' ) ) ) : ?>
	      <a class="sec-more" href="<?php echo esc_url( grid_detail_url( array( 'gastronomie', 'gastro' ) ) ); ?>#jidelnicek" style="margin-left:26px">Aktuální týdenní menu <span aria-hidden="true">→</span></a>
	    <?php endif; ?>
	  </div>
	</section>
	<?php
	/* Na stránce Gastronomie (ne homepage): catering jako samostatná služba + týdenní menu. */
	if ( ! is_front_page() && function_exists( 'grid_gastro_extra' ) ) echo grid_gastro_extra();
	return ob_get_clean();
}
add_shortcode( 'grid_gastro', 'grid_sc_gastro' );

/* Catering (samostatná služba) + týdenní jídelníček — jen na stránce Gastronomie. */
function grid_gastro_extra() {
	$rez = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );
	$firemni = grid_detail_url( array( 'firemni-akce-svatby', 'firemni' ) );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad" id="catering">
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url( grid_img('catering-syry.jpg') ); ?>" alt="Catering GRID HOTEL"></div>
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
	        <a class="btn btn-ghost" href="<?php echo esc_url( grid_nav_url( '#kontakt' ) ); ?>">Poptat catering</a>
	      </div>
	    </div>
	  </div>
	</section>
	<section class="sec sec-light sec-pad" id="jidelnicek">
	  <div class="wrap">
	    <span class="kicker">Restaurace · Týdenní menu</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px">Jídelníček tohoto týdne</h2>
	    [grid_menu_tydne]
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* Týdenní jídelníček — z GRID Nastavení (menu_dny); když prázdné, ukáže dummy týden. */
function grid_menu_tydne() {
	$platnost = grid_field( 'menu_platnost', '', 'option' );
	$poznamka = grid_field( 'menu_poznamka', '', 'option' );
	$dny = array();
	if ( function_exists( 'have_rows' ) && have_rows( 'menu_dny', 'option' ) ) {
		while ( have_rows( 'menu_dny', 'option' ) ) { the_row();
			$den = get_sub_field( 'den' );
			$row = array( 'den' => $den, 'polevky' => array(), 'hlavni' => array(), 'dezerty' => array(), 'napoje' => array() );
			foreach ( array( 'polevky', 'hlavni', 'dezerty', 'napoje' ) as $grp ) {
				if ( have_rows( $grp ) ) while ( have_rows( $grp ) ) { the_row();
					$row[ $grp ][] = array( 'nazev' => get_sub_field( 'nazev' ), 'cena' => get_sub_field( 'cena' ), 'popis' => get_sub_field( 'popis' ), 'alergeny' => get_sub_field( 'alergeny' ) );
				}
			}
			$dny[] = $row;
		}
	}
	if ( empty( $dny ) ) { // DUMMY obsah
		$platnost = $platnost ?: 'Ukázkový týden';
		$poznamka = $poznamka ?: '// Ukázková data — vyplňte v GRID Nastavení → Týdenní menu.';
		$mk = function ( $n, $c, $p = '', $a = '' ) { return array( 'nazev' => $n, 'cena' => $c, 'popis' => $p, 'alergeny' => $a ); };
		$dny = array(
			array( 'den' => 'Pondělí', 'polevky' => array( $mk('Hovězí vývar s nudlemi','45 Kč','','1,3,9') ), 'hlavni' => array( $mk('Svíčková na smetaně, houskový knedlík','185 Kč','','1,3,7'), $mk('Grilovaný losos, bylinkové brambory','245 Kč','','4,7') ), 'dezerty' => array( $mk('Domácí štrúdl','65 Kč') ), 'napoje' => array() ),
			array( 'den' => 'Úterý', 'polevky' => array( $mk('Krémová dýňová polévka','49 Kč','','7') ), 'hlavni' => array( $mk('Kuřecí řízek, bramborová kaše','179 Kč','','1,3,7'), $mk('Rizoto s houbami a parmezánem','165 Kč','','7') ), 'dezerty' => array( $mk('Panna cotta','69 Kč') ), 'napoje' => array() ),
			array( 'den' => 'Středa', 'polevky' => array( $mk('Zeleninový krém','45 Kč') ), 'hlavni' => array( $mk('Vepřová pečeně, zelí, knedlík','175 Kč','','1'), $mk('Těstoviny s kuřecím masem a smetanou','169 Kč','','1,3,7') ), 'dezerty' => array( $mk('Čokoládový fondant','79 Kč') ), 'napoje' => array() ),
			array( 'den' => 'Čtvrtek', 'polevky' => array( $mk('Gulášová polévka','49 Kč','','1') ), 'hlavni' => array( $mk('Hovězí guláš, houskový knedlík','189 Kč','','1'), $mk('Pečené kuřecí stehno, rýže','175 Kč') ), 'dezerty' => array( $mk('Tiramisu','75 Kč') ), 'napoje' => array() ),
			array( 'den' => 'Pátek', 'polevky' => array( $mk('Kulajda','49 Kč','','3,7') ), 'hlavni' => array( $mk('Smažený sýr, hranolky, tatarka','169 Kč','','1,3,7'), $mk('Steak z vepřové krkovice, grilovaná zelenina','215 Kč') ), 'dezerty' => array( $mk('Zmrzlinový pohár','69 Kč') ), 'napoje' => array() ),
		);
	}
	ob_start(); ?>
	<section class="sec sec-light sec-pad" id="jidelnicek">
	  <div class="wrap">
	    <span class="kicker">Restaurace · Týdenní menu</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 8px">Jídelníček tohoto týdne</h2>
	    <?php if ( $platnost ) : ?><p style="color:var(--muted);font-family:var(--f-mono);font-size:.82rem;margin-bottom:24px"><?php echo esc_html( $platnost ); ?></p><?php endif; ?>
	    <div class="menu-week">
	      <?php foreach ( $dny as $d ) : ?>
	      <div class="menu-day">
	        <h3><?php echo esc_html( $d['den'] ); ?></h3>
	        <?php
	        $grp_lbl = array( 'polevky' => 'Polévky', 'hlavni' => 'Hlavní chody', 'dezerty' => 'Dezerty', 'napoje' => 'Nápoje' );
	        foreach ( $grp_lbl as $k => $lbl ) : if ( empty( $d[ $k ] ) ) continue; ?>
	        <div class="menu-grp"><span class="menu-grp-l"><?php echo esc_html( $lbl ); ?></span>
	          <?php foreach ( $d[ $k ] as $item ) : ?>
	          <div class="menu-item">
	            <span class="menu-n"><?php echo esc_html( $item['nazev'] ); ?><?php if ( ! empty( $item['alergeny'] ) ) : ?> <em class="menu-a">(<?php echo esc_html( $item['alergeny'] ); ?>)</em><?php endif; ?><?php if ( ! empty( $item['popis'] ) ) : ?><small><?php echo esc_html( $item['popis'] ); ?></small><?php endif; ?></span>
	            <?php if ( ! empty( $item['cena'] ) ) : ?><span class="menu-c"><?php echo esc_html( $item['cena'] ); ?></span><?php endif; ?>
	          </div>
	          <?php endforeach; ?>
	        </div>
	        <?php endforeach; ?>
	      </div>
	      <?php endforeach; ?>
	    </div>
	    <?php if ( $poznamka ) : ?><p style="color:var(--muted);font-size:.86rem;margin-top:20px"><?php echo esc_html( $poznamka ); ?></p><?php endif; ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* ============================================================
 * T6 SEZÓNA 2026 / ČEKACÍ LIST (dark carbon) — repeater akcí
 * ============================================================ */
function grid_sc_season() {
	ob_start(); ?>
	<section id="sezona" class="sec sec-dark carbon sec-pad">
	  <span class="sec-tag">T6</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:46px"><span class="kicker">T6 · Sezóna 2026 · Čekací list</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Velké akce se plní rychle. Buďte na roštu první.</h2><p style="max-width:62ch;margin-top:14px;color:var(--muted)">O závodních víkendech je hotel uprostřed okruhu nejžádanějším místem v Brně. Vyberte akci, zkontrolujte dostupnost pokojů a rezervujte — nebo se zapište na čekací list. Jakmile se uvolní místnost pro vámi vybraný termín, ozveme se jako prvním.</p></div>
	    <?php echo is_front_page() ? '[grid_season_events limit="5"]' : '[grid_season_events limit="0" karty="1"]'; ?>
	    <?php echo grid_section_more( array( 'sezona-2026', 'sezona' ), 'Celý program sezóny 2026' ); ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_season', 'grid_sc_season' );

/* ============================================================
 * T7 FIREMNÍ AKCE & SVATBY (light, split + formulář)
 * ============================================================ */
function grid_sc_firemni() {
	$img = grid_field( 'firemni_obrazek', '', 'option' ); $img = $img ? ( is_array($img)?$img['url']:$img ) : grid_img( 'hotel-exterier.jpg' );
	ob_start(); ?>
	<section id="firemni" class="sec sec-light">
	  <span class="sec-tag" style="z-index:1">T7</span>
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url($img); ?>" alt="Exteriér GRID Hotelu — firemní akce a svatby"></div>
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
	      <div class="form-grid" style="margin-top:22px">
	        <div><label for="f-firma">Firma / jméno</label><input id="f-firma" type="text"></div>
	        <div><label for="f-email">E-mail</label><input id="f-email" type="email"></div>
	        <div><label for="f-pocet">Počet osob</label><input id="f-pocet" type="number" min="1"></div>
	        <div><label for="f-termin">Termín</label><input id="f-termin" type="date"></div>
	        <div class="full"><label for="f-typ">Typ akce</label><select id="f-typ"><option>Firemní večírek</option><option>Konference / teambuilding</option><option>Svatební hostina</option><option>Oslava narozenin</option><option>Vánoční večírek</option><option>Jiné</option></select></div>
	        <div class="full"><button type="submit" class="btn" onclick="return false">Odeslat nezávaznou poptávku</button></div>
	      </div>
	      <?php echo grid_section_more( array( 'firemni-akce-svatby', 'firemni-akce', 'firemni' ), 'Firemní akce & svatby — detail' ); ?>
	    </div>
	  </div>
	</section>
	<?php
	/* Na stránce Firemní akce & svatby (ne homepage) přidáme bohatší obsah. */
	if ( ! is_front_page() && function_exists( 'grid_firemni_extra' ) ) echo grid_firemni_extra();
	return ob_get_clean();
}
add_shortcode( 'grid_firemni', 'grid_sc_firemni' );

/* Rozšíření stránky Firemní akce & svatby (typy akcí, co nabízíme, prostory) — mimo homepage. */
function grid_firemni_extra() {
	$types = array(
		array( 'Konference &amp; školení', 'Zázemí pro jednání i celodenní program s občerstvením a ubytováním na místě.' ),
		array( 'Teambuilding', 'Spojte poradu s adrenalinem — simulátor okruhu, motokáry i motoškola pár kroků od hotelu.' ),
		array( 'Firemní večírek', 'Vánoční večírek, oslava výsledků nebo jen tak — bar, catering a klub GRID.' ),
		array( 'Svatební hostina', 'Svatba v neokoukaném prostředí Masarykova okruhu, catering a hosté ubytovaní pod jednou střechou.' ),
		array( 'Oslavy narozenin', 'Rodinné i velké oslavy s menu na míru a možností přespání.' ),
		array( 'Rauty &amp; prezentace', 'Uvedení produktu, tisková akce nebo raut v areálu okruhu i na hotelu.' ),
	);
	$nabidka = array(
		'Restaurace s kapacitou cca 50 míst',
		'Stylový bar — nealko, alko i míchané nápoje',
		'Catering na míru dle vašich představ',
		'Ubytování v 64 moderních pokojích a apartmá',
		'Bezproblémové parkování přímo před hotelem',
		'Doprovodný program: simulátor okruhu, motokáry, motoškola',
		'Kompletní organizace — od menu po realizaci',
	);
	$rez = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad">
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
	    <ul class="amenity-grid">
	      <?php foreach ( $nabidka as $n ) echo '<li>' . esc_html( $n ) . '</li>'; ?>
	    </ul>
	    <div class="fc-actions" style="margin-top:30px">
	      <a class="btn" href="<?php echo esc_url( grid_nav_url( '#kontakt' ) ); ?>">Nezávazná poptávka</a>
	      <a class="btn btn-ghost" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}

/* ============================================================
 * T8 REFERENCE (dark carbon) — repeater
 * ============================================================ */
function grid_sc_reference() {
	$items = grid_section_rows( 'grid_testimonial', 'grid_map_review', array(
		array('text'=>'Probudit se a vidět z okna cílovou rovinku Masarykova okruhu je nepopsatelné. Servis i snídaně na úrovni.','who'=>'Petr H. · MotoGP víkend'),
		array('text'=>'Uspořádali jsme tu firemní akci pro 120 lidí. Catering, bar i motokáry — vše na jednom místě bez transferů.','who'=>'Lucie K. · HR manažerka'),
		array('text'=>'Apartmá s terasou a výhledem na trať. Klid, prémiová úroveň a pár minut do Brna. Vrátíme se.','who'=>'Tomáš &amp; Eva R. · Víkendový pobyt'),
	) );
	ob_start(); ?>
	<section id="duvera" class="sec sec-dark carbon sec-pad">
	  <span class="sec-tag">T8</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:42px"><span class="kicker">T8 · Co říkají hosté</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Recenze z pole position</h2></div>
	    <div class="reviews">
	      <?php $d=1; foreach ( $items as $it ) : ?>
	      <div class="review reveal d<?php echo $d++; ?>"><span class="stars">★★★★★</span><p>„<?php echo esc_html( grid_row_val($it,'text') ); ?>“</p><span class="r-who"><?php echo wp_kses_post( grid_row_val($it,'who') ); ?></span></div>
	      <?php endforeach; ?>
	    </div>
	    <div class="partners reveal"><span class="p-label">Partner</span><span class="p-name">Autodrom Brno</span><span class="p-name">Masarykův okruh</span><span class="p-name">Polygon Brno</span></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_reference', 'grid_sc_reference' );

/* ============================================================
 * CÍL / FINAL CTA (dark)
 * ============================================================ */
function grid_sc_final() {
	ob_start(); ?>
	<section id="cil" class="sec sec-dark final">
	  <div class="wrap">
	    <span class="kicker" style="justify-content:center;display:inline-flex">CÍL · Cílová rovinka</span>
	    <h2 style="margin-top:18px">Dojeď do cíle.<br>Rezervuj svůj okruh.</h2>
	    <p>Ať jedeš za závody, byznysem, oslavou nebo klidem — tvůj pobyt uprostřed Masarykova okruhu začíná jedním kliknutím.</p>
	    <div class="fc-actions"><a href="<?php echo esc_url( grid_rezervace_url() ); ?>" class="btn">Rezervovat pobyt</a><a href="<?php echo esc_url( grid_nav_url('#firemni') ); ?>" class="btn btn-ghost">Poptat akci nebo svatbu</a><a href="<?php echo esc_url( grid_nav_url('#zazitky') ); ?>" class="btn btn-ghost">Koupit dárkový poukaz</a></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_final', 'grid_sc_final' );

/* ============================================================
 * FOOTER (globální) — kontakt z ACF
 * ============================================================ */
function grid_sc_footer() {
	$logo   = grid_logo( 'grid-hotel-negativ.png' );
	$a1     = grid_field( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh', 'option' );
	$a2     = grid_field( 'adresa_2', '641 00 Brno – Žebětín, ČR', 'option' );
	$telr   = grid_field( 'tel_recepce', '+420 775 877 721', 'option' );
	$telrez = grid_field( 'tel_rezervace', '+420 775 877 720', 'option' );
	$tels   = grid_field( 'tel_shuttle', '+420 775 778 718', 'option' );
	$email  = grid_field( 'email', 'info@gridhotel.cz', 'option' );
	$ico    = grid_field( 'ico', '04996364', 'option' );
	$dic    = grid_field( 'dic', 'CZ04996364', 'option' );
	$spis   = grid_field( 'spis_znacka', 'Sp. zn. C 92997, KS v Brně', 'option' );
	$u_pod = grid_field( 'url_podminky', home_url( '/podminky/' ), 'option' );
	$u_och = grid_field( 'url_ochrana', home_url( '/ochrana-osobnich-udaju/' ), 'option' );
	$u_coo = grid_field( 'url_cookies', home_url( '/cookies/' ), 'option' );
	$u_dop = grid_field( 'url_doprava', home_url( '/doprava/' ), 'option' );
	$u_dot = grid_field( 'url_dotaznik', home_url( '/dotaznik-spokojenosti/' ), 'option' );
	$u_kar = grid_field( 'url_kariera', home_url( '/kariera/' ), 'option' );
	$telrRaw = preg_replace('/\s+/','',$telr); $telrezRaw = preg_replace('/\s+/','',$telrez); $telsRaw = preg_replace('/\s+/','',$tels);
	ob_start(); ?>
	<footer id="kontakt">
	  <div class="wrap">
	    <div class="foot-top">
	      <div class="foot-brand">
	        <img src="<?php echo esc_url($logo); ?>" alt="GRID HOTEL logo">
	        <p>Hotel a restaurace **** přímo v areálu Autodromu Brno. Přespi uprostřed Masarykova okruhu.</p>
	        [grid_paticka_kontakt]
	        [grid_socials]
	      </div>
	      <div class="foot-col"><h4>Hotel</h4><ul><li><a href="<?php echo esc_url( grid_link_pref( array('o-nas'), '#pribeh' ) ); ?>">O hotelu</a></li><li><a href="<?php echo esc_url( grid_link_pref( array('ubytovani','pokoje','pokoje-a-apartmany'), '#pokoje' ) ); ?>">Pokoje &amp; apartmá</a></li><li><a href="<?php echo esc_url( grid_link_pref( array('gastronomie','gastro'), '#restaurace' ) ); ?>">Gastronomie</a></li><li><a href="<?php echo esc_url( grid_link_pref( array('zazitky-u-okruhu','zazitky','aktivity'), '#zazitky' ) ); ?>">Zážitky &amp; dárkové poukazy</a></li><li><a href="<?php echo esc_url( grid_link_pref( array('sezona-2026','sezona'), '#sezona' ) ); ?>">Sezóna 2026</a></li><li><a href="<?php echo esc_url( grid_link_pref( array('firemni-akce-svatby','firemni'), '#firemni' ) ); ?>">Firemní akce &amp; svatby</a></li></ul></div>
	      <div class="foot-col"><h4>Informace</h4><ul><li><a href="<?php echo esc_url($u_dop); ?>">Jak se k nám dostanete</a></li><li><a href="<?php echo esc_url($u_dop); ?>">Parkování &amp; shuttle bus</a></li><li><a href="<?php echo esc_url($u_kar); ?>">Kariéra</a></li><li><a href="<?php echo esc_url($u_dot); ?>">Dotazník spokojenosti</a></li><li><a href="<?php echo esc_url($u_pod); ?>">Všeobecné obchodní podmínky</a></li><li><a href="<?php echo esc_url($u_och); ?>">Ochrana osobních údajů</a></li></ul></div>
	      <div class="foot-col"><h4>Event alert &amp; Newsletter</h4><p style="color:var(--grey);font-size:.86rem">Nezmeškej termíny sezóny 2026 a speciální balíčky.</p><form class="newsletter" onsubmit="return false"><input type="email" placeholder="Tvůj e-mail" aria-label="E-mail pro newsletter"><button type="submit" onclick="return false">Odebírat</button></form><p style="color:var(--grey-dim);font-size:.78rem;margin-top:18px">GRH s.r.o.<br>IČ: <?php echo esc_html($ico); ?> · DIČ: <?php echo esc_html($dic); ?><br><?php echo esc_html($spis); ?></p></div>
	    </div>
	    <div class="foot-bottom"><div class="legal"><a href="<?php echo esc_url($u_och); ?>">Ochrana osobních údajů</a><a href="<?php echo esc_url($u_coo); ?>">Cookies</a><a href="<?php echo esc_url($u_pod); ?>">Obchodní podmínky</a><span>© <?php echo esc_html( date('Y') ); ?> GRID HOTEL</span></div><a class="build-tag" href="https://www.garry.cz" target="_blank" rel="noopener">Web &amp; design — GARRY Promotion</a></div>
	  </div>
	</footer>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_footer', 'grid_sc_footer' );

/* ============================================================
 * Srovnávací tabulka kategorií pokojů (z popisů typů)
 * ============================================================ */
function grid_room_compare_table( $current = '' ) {
	/* Plugin „Kategorie pokojů" má přednost (CZ/EN/DE, editovatelné řádky) */
	if ( function_exists( 'garry_pokoje_compare_html' ) ) return garry_pokoje_compare_html( $current );
	$cols = array( 'Standard', 'Superior', 'Superior Plus', 'Apartmá a Apartmá Superior' );
	$rows = array(
		array( 'Postele', 'TWIN / DOUBLE', 'TWIN / DOUBLE', 'TWIN / DOUBLE', 'King Size' ),
		array( 'Výhled', 'areál / klid', 'okruh + lesy', 'okruh + terasa', 'město · okruh · paddock' ),
		array( 'Terasa', '–', '–', '✓', '✓ (až 47 m²)' ),
		array( 'Velikost', 'standardní', 'standardní', 'standardní', '47–59 m²' ),
		array( 'Obývací pokoj', '–', '–', '–', '✓' ),
		array( 'TV', '40&quot; HDMI', '40&quot; HDMI', '40&quot; HDMI', '2× 43&quot; HDMI' ),
		array( 'Telefon s předvolbou', '✓', '✓', '✓', '2×' ),
		array( 'Klimatizace', '✓', '✓', '✓', '✓' ),
		array( 'Wi-Fi · pracovní stůl', '✓', '✓', '✓', '✓' ),
		array( 'Trezor · minibar · zatemnění', '✓', '✓', '✓', '✓' ),
		array( 'Sprchový kout', '✓', '✓', '✓', '✓' ),
		array( 'Vana', '–', '–', '–', '✓' ),
		array( 'Kosmetika · vysoušeč vlasů', '✓', '✓', '✓', '✓' ),
		array( 'Kávový/čajový set · župan · pantofle · minerálka', 'za poplatek', 'zdarma denně', 'zdarma denně', 'zdarma denně' ),
		array( 'Bezbariérový', '✓ (vybrané)', '–', '–', '–' ),
	);
	$curIdx = array_search( $current, $cols, true ); // 0..3 nebo false
	ob_start(); ?>
	<div class="rd-tablewrap"><table class="rd-table">
	  <thead><tr><th>Vlastnost</th>
	    <?php foreach ( $cols as $i => $c ) : ?><th class="<?php echo ( $i === $curIdx ) ? 'is-current' : ''; ?>"><?php echo esc_html( $c ); ?></th><?php endforeach; ?>
	  </tr></thead>
	  <tbody>
	    <?php foreach ( $rows as $r ) : ?>
	    <tr><td><?php echo esc_html( $r[0] ); ?></td>
	      <?php for ( $i = 1; $i <= 4; $i++ ) : ?><td class="<?php echo ( ($i-1) === $curIdx ) ? 'is-current' : ''; ?>"><?php echo wp_kses_post( $r[$i] ); ?></td><?php endfor; ?>
	    </tr>
	    <?php endforeach; ?>
	  </tbody>
	</table></div>
	<?php return ob_get_clean();
}

/* ============================================================
 * Fluent Forms napojení — vrátí vyrenderovaný formulář dle ID/shortcode v nastavení
 * ============================================================ */
function grid_form_has( $opt ) { return (bool) grid_field( $opt, '', 'option' ); }
function grid_form_render( $opt ) {
	$v = trim( (string) grid_field( $opt, '', 'option' ) );
	if ( $v === '' ) return '';
	if ( preg_match( '/(\\d+)/', $v, $m ) ) return do_shortcode( '[fluentform id="' . $m[1] . '"]' );
	return do_shortcode( $v );
}

/* Slepý (statický, nefunkční) formulář — vizuální ukázka bez napojení na Fluent Forms.
 * Použití: [grid_form_dotaznik], grid_kontakt. Nic neodesílá (onsubmit=return false). */
function grid_blind_form( $type ) {
	$note = '<p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:12px">// Ukázkový formulář — připraven k napojení, zatím neodesílá.</p>';
	ob_start();
	if ( $type === 'kontakt' ) : ?>
		<form class="form-grid" onsubmit="return false" aria-label="Kontaktní formulář (ukázka)">
		  <div><label for="k-jmeno">Jméno</label><input id="k-jmeno" type="text" placeholder="Jméno"></div>
		  <div><label for="k-prijmeni">Příjmení</label><input id="k-prijmeni" type="text" placeholder="Příjmení"></div>
		  <div><label for="k-email">E-mail</label><input id="k-email" type="email" placeholder="vas@email.cz"></div>
		  <div><label for="k-tel">Telefon</label><input id="k-tel" type="tel" placeholder="+420 …"></div>
		  <div class="full"><label for="k-zprava">Vaše zpráva</label><textarea id="k-zprava" rows="4" placeholder="Dotaz k pobytu, termínu, rezervaci…"></textarea></div>
		  <div class="full"><button type="submit" class="btn" onclick="return false">Odeslat dotaz</button></div>
		</form>
		<?php echo $note;
	elseif ( $type === 'dotaznik' ) :
		/* Škála 1–5 (1 = velmi dobře … 5 = velmi špatně) — dle gridhotel.cz */
		$scale = function( $name ) {
			$o = '<div class="df-scale-row" role="radiogroup">';
			for ( $i = 1; $i <= 5; $i++ ) {
				$o .= '<label class="df-opt"><input type="radio" name="' . esc_attr( $name ) . '" value="' . $i . '"><span>' . $i . '</span></label>';
			}
			return $o . '</div>';
		}; ?>
		<form class="dotaznik-form" onsubmit="return false" aria-label="Dotazník spokojenosti (ukázka)">
		  <p class="df-scale-legend">Hodnocení: <strong>1 = velmi dobře</strong> … <strong>5 = velmi špatně</strong></p>
		  <fieldset class="df-q"><legend>1/ Jak hodnotíte celkový dojem z hotelu?</legend><?php echo $scale( 'q1' ); ?></fieldset>
		  <fieldset class="df-q"><legend>2/ Jak hodnotíte přístup personálu?</legend><?php echo $scale( 'q2' ); ?></fieldset>
		  <fieldset class="df-q"><legend>3/ Jak hodnotíte naše snídaně?</legend><?php echo $scale( 'q3' ); ?></fieldset>
		  <fieldset class="df-q"><legend>4/ Jak hodnotíte společenské prostory hotelu?</legend>
		    <div class="df-sub"><span class="df-sublabel">Komfort</span><?php echo $scale( 'q4a' ); ?></div>
		    <div class="df-sub"><span class="df-sublabel">Čistota</span><?php echo $scale( 'q4b' ); ?></div>
		  </fieldset>
		  <fieldset class="df-q"><legend>5/ Jak hodnotíte naše pokoje?</legend>
		    <div class="df-sub"><span class="df-sublabel">Vybavení</span><?php echo $scale( 'q5a' ); ?></div>
		    <div class="df-sub"><span class="df-sublabel">Čistota</span><?php echo $scale( 'q5b' ); ?></div>
		  </fieldset>
		  <div class="df-q"><label for="d-pozn">6/ Rádi uvítáme Vaše připomínky a poznámky:</label><textarea id="d-pozn" rows="4"></textarea></div>
		  <p class="df-optional">Nepovinné údaje</p>
		  <div class="form-grid">
		    <div><label for="d-jmeno">Vaše jméno a příjmení</label><input id="d-jmeno" type="text"></div>
		    <div><label for="d-email">Váš e-mail</label><input id="d-email" type="email" placeholder="vas@email.cz"></div>
		  </div>
		  <button type="submit" class="btn" style="margin-top:20px" onclick="return false">Odeslat</button>
		  <p class="df-thanks">Děkujeme za Váš čas!</p>
		</form>
		<?php echo $note;
	endif;
	return ob_get_clean();
}

/* ============================================================
 * [grid_poukazy] — Dárkové poukazy (ceník 1:1 z gridhotel.cz)
 * ============================================================ */
function grid_sc_poukazy() {
	$email = grid_field( 'email_rezervace', 'reservations@gridhotel.cz', 'option' );
	if ( ! $email ) $email = 'reservations@gridhotel.cz';
	$one = array(
		array( '3 750', 'Pokoj Superior', 'Výhled do zázemí Masarykova okruhu, snídaně v ceně.' ),
		array( '4 250', 'Pokoj Superior Plus', 'Terasa a výhled do zázemí okruhu, snídaně v ceně.' ),
		array( '6 625', 'Apartmán', 'Terasa a výhled na trať, snídaně v ceně.' ),
	);
	$two = array(
		array( '4 500', 'Pokoj Superior', 'Výhled do zázemí Masarykova okruhu, snídaně v ceně.' ),
		array( '5 000', 'Pokoj Superior Plus', 'Terasa a výhled do zázemí okruhu, snídaně v ceně.' ),
		array( '6 875', 'Apartmán', 'Terasa a výhled na trať, snídaně v ceně.' ),
	);
	$cards = function( $rows ) {
		$h = '<div class="vou-grid">';
		foreach ( $rows as $r ) {
			$h .= '<div class="vou-card"><span class="vou-price">' . esc_html( $r[0] ) . '&nbsp;Kč</span>'
			   . '<h4>' . esc_html( $r[1] ) . '</h4><p>' . esc_html( $r[2] ) . '</p></div>';
		}
		return $h . '</div>';
	};
	ob_start(); ?>
	<section id="poukazy" class="sec sec-dark carbon sec-pad">
	  <div class="wrap" style="max-width:1000px">
	    <span class="kicker">Dárkové poukazy · Sezóna 2026</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 16px">Darujte pobyt u Masarykova okruhu</h2>
	    <p style="color:var(--muted)">Vážení fanoušci motorsportu, máme pro vás možnost zakoupení dárkového poukazu na sezónu 2026. Obdarujte své blízké na Vánoce, narozeniny, výročí, promoce, jako svatební dar nebo jen tak.</p>
	    <p style="color:var(--muted)">Vyberte si jeden ze dvou vzorů poukazu, do e-mailu uveďte, který vzor jste zvolili a jaký si přejete „Váš text" (např. „Tatínkovi"). Obratem pošleme zálohovou fakturu k platbě bankovním převodem; po přijetí platby vám elektronicky zašleme dárkový poukaz.</p>
	    <p style="color:var(--muted);font-family:var(--f-mono);font-size:.8rem">// Poukazy jsou standardně na 1 noc — dle přání upravíme na požadovaný počet nocí.</p>

	    <h3 style="margin:30px 0 12px;color:var(--gold)">Pro jednu osobu</h3>
	    <?php echo $cards( $one ); ?>
	    <h3 style="margin:30px 0 12px;color:var(--gold)">Pro dvě osoby</h3>
	    <?php echo $cards( $two ); ?>

	    <div class="vou-order">
	      [grid_voucher_form]
	      <p class="vou-note"><strong>Pozor:</strong> dárkový poukaz nelze kombinovat s jinými slevami a není platný v termínu konání vybraných akcí (např. HISTOCUP či Podzimní cena).</p>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_poukazy', 'grid_sc_poukazy' );

/* ============================================================
 * [grid_doprava] — Jak se k nám dostanete (Google mapa + navigace)
 * ============================================================ */
function grid_sc_doprava() {
	$adresa = grid_field( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh', 'option' ) . ', ' . grid_field( 'adresa_2', '641 00 Brno – Žebětín', 'option' );
	$dest   = rawurlencode( 'Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	$mapsrc = 'https://www.google.com/maps?q=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' ) . '&output=embed&hl=cs';
	$navig  = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Kontakt · Příjezd</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 10px">Jak se k nám dostanete?</h1>
	    <p style="max-width:64ch;color:var(--muted)">GRID HOTEL najdete přímo v areálu Autodromu Brno na adrese <strong style="color:var(--fg)"><?php echo esc_html( $adresa ); ?></strong>. Při příjezdu se řiďte dopravním značením <strong style="color:var(--fg)">„Grand Prix"</strong> a <strong style="color:var(--fg)">„Paddock"</strong>.</p>
	    <div class="doprava-map"><iframe src="<?php echo esc_url( $mapsrc ); ?>" title="Mapa — GRID HOTEL" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
	    <div class="doprava-actions"><a class="btn" href="<?php echo esc_url( $navig ); ?>" target="_blank" rel="noopener">Navigovat →</a><a class="btn btn-ghost" href="tel:+420775778718">Shuttle bus: +420 775 778 718</a></div>
	  </div>
	</section>

	<section class="sec sec-light sec-pad">
	  <div class="wrap">
	    <div class="dp-cols">
	      <div class="dp-card">
	        <h3>Automobilem</h3>
	        <div class="dp-route"><b>Z dálnice D1 od Prahy</b><span>Použijte exit 178 – Ostrovačice, dále podle dopravního značení Grand Prix a Paddock.</span></div>
	        <div class="dp-route"><b>Po dálnici D1 od Brna</b><span>Použijte exit 182 Kývalka, poté sledujte směrovky „Grand Prix" či „Paddock". Ve směru od Olomouce, Bratislavy či Vídně použijte rovněž exit 182 Kývalka.</span></div>
	        <div class="dp-route"><b>Z Brna mimo dálnici D1</b><span>Silnicí III. třídy 3842 od Brna-Žebětína. Značeno dopravním značením Autodrom a Grand Prix.</span></div>
	        <div class="dp-route"><b>Z Prahy mimo dálnici D1</b><span>Silnicí I/23 na Kývalku, dále po silnici II/602 – sledujte směrovky „Grand Prix" a „Paddock".</span></div>
	      </div>
	      <div class="dp-card">
	        <h3>Hromadnou dopravou</h3>
	        <div class="dp-route"><b>Autobus 402</b><span>Stálá linka ze Starého Lískovce (z Hlavního nádraží použijte tramvaj 8 směr Starý Lískovec).</span></div>
	        <div class="dp-route"><b>Autobus 400 · zdarma</b><span>Během mezinárodních podniků jezdí speciální linka z Mendlova náměstí zdarma (z Hlavního nádraží tramvaj 1 směr Bystrc). Jízdní řád je zveřejněn před konáním akce.</span></div>
	        <h3 style="margin-top:26px">Parkování &amp; taxi</h3>
	        <div class="dp-route"><b>Parkování</b><span>Plán parkovacích ploch bude zveřejněn před konáním akce.</span></div>
	        <div class="dp-route"><b>Taxi</b><span>Naše recepce vám ráda přivolá taxi.</span></div>
	      </div>
	    </div>
	  </div>
	</section>

	<section class="sec sec-dark carbon sec-pad">
	  <div class="wrap">
	    <span class="kicker">Letecky</span>
	    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 6px">Nejbližší letiště</h2>
	    <div class="dp-air-grid">
	      <div class="dp-airport">
	        <div class="dp-head"><h4>Letiště Brno</h4><span class="dp-badge">25 km</span></div>
	        <p>Použijte dálnici D1 a exit 182 Kývalka, poté sledujte směrovky „Grand Prix" či „Paddock".</p>
	        <span class="dp-vign">Dálniční známka: CZ</span>
	      </div>
	      <div class="dp-airport">
	        <div class="dp-head"><h4>Praha – Ruzyně</h4><span class="dp-badge">210 km</span></div>
	        <p>Pražský okruh, dále dálnice D1 a exit 178 Ostrovačice, poté směrovky „Grand Prix" či „Paddock".</p>
	        <span class="dp-vign">Dálniční známka: CZ</span>
	      </div>
	      <div class="dp-airport">
	        <div class="dp-head"><h4>Vídeň – Schwechat</h4><span class="dp-badge">160–200 km</span></div>
	        <p><strong style="color:var(--fg)">Trasa 1:</strong> A4 (E85) směr Bratislava → D2 do Brna → D1, exit 182 Kývalka (200 km).</p>
	        <p><strong style="color:var(--fg)">Trasa 2:</strong> A4 → A23 → S1 → A5 → silnice 7 směr Brno → I/52 (R52) → D1 směr Praha, exit 182 Kývalka (160 km).</p>
	        <span class="dp-vign">Dálniční známky: AT, SK, CZ</span>
	      </div>
	      <div class="dp-airport">
	        <div class="dp-head"><h4>Bratislava</h4><span class="dp-badge">150 km</span></div>
	        <p>Dálnicí D2 do Brna a poté dálnicí D1 směr Praha, exit 182 Kývalka.</p>
	        <span class="dp-vign">Dálniční známky: SK, CZ</span>
	      </div>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_doprava', 'grid_sc_doprava' );

/* [grid_form_dotaznik] — stránka s dotazníkem spokojenosti (Fluent Form) */
function grid_sc_form_dotaznik() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker">Zpětná vazba</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 10px">Dotazník spokojenosti</h1>
	    <p style="color:var(--muted);margin-bottom:26px">Budeme rádi za vaše hodnocení pobytu v GRID HOTELU — pomůže nám zlepšovat služby.</p>
	    <?php echo grid_blind_form( 'dotaznik' ); ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_form_dotaznik', 'grid_sc_form_dotaznik' );

/* [grid_legal]OBSAH[/grid_legal] — stylovaný obal pro právní texty (vlož text 1:1) */
function grid_sc_legal( $atts, $content = '' ) {
	$a = shortcode_atts( array( 'nadpis' => '', 'kicker' => 'Právní informace' ), $atts );
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
	    <?php if ( $a['nadpis'] ) : ?><h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 20px"><?php echo esc_html( $a['nadpis'] ); ?></h1><?php endif; ?>
	    <div class="legal-body"><?php echo do_shortcode( wpautop( $content ) ); ?></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_legal', 'grid_sc_legal' );

/* [grid_rezervace] — dočasný zástupný prvek pro rezervační systém (nasadí se na ostrém webu) */
function grid_sc_rezervace() {
	$bullets = array( 'Ubytování přímo v centru dění', 'Výhodnější ceny ubytování', 'Možnost uplatnění promo kódů', 'Voda na pokoji zdarma', 'Parkování před hotelem zdarma', 'Recepce k dispozici 24/7' );
	$mock = array(
		array( 'name' => 'Pokoj Superior',      'cap' => 2, 'm2' => '23', 'img' => grid_img('pokoj-superior.jpg'), 'p1' => '3 758', 'p2' => '3 394' ),
		array( 'name' => 'Pokoj Superior plus', 'cap' => 2, 'm2' => '23', 'img' => grid_img('koupelna.jpg'),       'p1' => '4 122', 'p2' => '3 637' ),
		array( 'name' => 'Apartmán',            'cap' => 2, 'm2' => '47', 'img' => grid_img('pokoj-apartma.jpg'),   'p1' => '6 667', 'p2' => '6 304' ),
		array( 'name' => 'Apartmán LUX',        'cap' => 2, 'm2' => '58', 'img' => grid_img('pokoj-apartma.jpg'),   'p1' => '7 516', 'p2' => '7 152' ),
		array( 'name' => 'Pokoj Standard',      'cap' => 2, 'm2' => '23', 'img' => grid_img('hotel-exterier.jpg'),  'unavail' => true ),
	);
	ob_start(); ?>
	<section class="sec sec-light sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Rezervace</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 10px">Rezervace pobytu</h1>
	    <p style="max-width:70ch;color:var(--muted);margin-bottom:28px">Takto bude vypadat online rezervace přímo na webu. <strong style="color:var(--ink)">Níže je ukázka — ostrý rezervační systém (Bookolo) se vloží při nasazení na ostrém webu.</strong></p>

	    <div class="bkmock" aria-hidden="true">
	      <div class="bkmock-wm"><span>UKÁZKA</span><small>rezervační systém se nasadí na ostrém webu</small></div>

	      <div class="bkmock-topbar">
	        <span class="bkmock-tab">Pokoje</span>
	        <span class="bkmock-topright">CS ▾ &nbsp; CZK ▾ &nbsp; Více možností ▾</span>
	      </div>
	      <div class="bkmock-head">
	        <h3>Rezervace ubytování</h3>
	        <div class="bkmock-promo"><input type="text" placeholder="Zadejte promo kód" disabled><span class="bkmock-btn red">Potvrdit</span></div>
	      </div>
	      <div class="bkmock-datebar">
	        <div class="bkmock-date red"><span class="l">Příjezd</span></div>
	        <div class="bkmock-date red"><span class="l">Odjezd</span></div>
	        <div class="bkmock-dbox"><b>4</b> Červenec 2026</div>
	        <div class="bkmock-dbox"><b>5</b> Červenec 2026</div>
	        <span class="bkmock-pill">+ Další pokoj</span>
	        <span class="bkmock-pill">Dospělí − 2 +</span>
	        <span class="bkmock-pill">Děti − 0 +</span>
	      </div>

	      <div class="bkmock-layout">
	        <div class="bkmock-rooms">
	          <?php foreach ( $mock as $m ) : $un = ! empty( $m['unavail'] ); ?>
	          <div class="bkmock-card">
	            <div class="bkmock-cimg" style="background-image:url('<?php echo esc_url( $m['img'] ); ?>')"><?php if ( $un ) : ?><div class="bkmock-unavail"><span>Pokoj není dostupný ve vybraném termínu</span><em>Zobrazit ceny a dostupnost</em></div><?php endif; ?></div>
	            <div class="bkmock-cbody">
	              <h4><?php echo esc_html( $m['name'] ); ?></h4>
	              <div class="bkmock-cmeta">Max <?php echo (int) $m['cap']; ?> &nbsp;·&nbsp; Min <?php echo esc_html( $m['m2'] ); ?> m²</div>
	              <ul><?php foreach ( $bullets as $b ) echo '<li>' . esc_html( $b ) . '</li>'; ?></ul>
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
	      <a class="btn btn-ghost" href="<?php echo esc_url( grid_detail_url( array( 'kontakt' ) ) ?: home_url( '/#kontakt' ) ); ?>">Kontaktovat recepci</a>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_rezervace', 'grid_sc_rezervace' );

/* ============================================================
 * [grid_podminky] — Ubytovací a reklamační řád (text 1:1 z gridhotel.cz)
 * ============================================================ */
/* Auto-generováno ze zdroje gridhotel.cz/cz/podminky/ — text 1:1 */
function grid_podminky_rows() {
	return array(
		array('h2','Ubytovací řád GRID hotelu'),
		array('h3','Podmínky a způsob ubytování'),
		array('p','1. GRID HOTEL ****, (dále jen „ubytovatel“) je oprávněn ubytovat jen hosta, který se řádně přihlásí. Za tímto účelem předloží k nahlédnutí zaměstnanci na recepci ihned po příchodu svůj občanský průkaz nebo jiný platný průkaz totožnosti, cestovní pas nebo jiný cestovní doklad ve smyslu zákona o pobytu cizinců na území ČR.'),
		array('p','2. Každý host, který není státním občanem ČR (cizinec), je povinný ve smyslu zákona o pobytu cizinců na území ČR v platném znění vyplnit a odevzdat na recepci úřední doklad o hlášení pobytu, všechny požadované údaje je host povinen uvést pravdivě a úplně.'),
		array('p','3. Na základě objednaného a ubytovatelem písemně potvrzeného ubytování se host v den příjezdu může ubytovat v době od 14:00 h. do 24:00 h. Do této doby ubytovatel pro hosta pokoj rezervuje, pokud v objednávce nebyl jiný požadavek a ubytovatel jej potvrdil.'),
		array('p','4. Host, který se ubytuje před 06:00 h, resp. trvá na ubytování před 10:00 h., je povinen zaplatit plnou cenu i za předcházející noc, pokud se dopředu nedohodl ubytovatel s hostem jinak.'),
		array('p','5. Host ubytovaný v GRID HOTELU odhlásí svůj pobyt do 10.00 h. Do této doby pokoj uvolní, pokud nebylo individuálně a dopředu s ubytovatelem dohodnuto jinak. Pokud host neuvolní pokoj do stanoveného času, může mu ubytovatel účtovat pobyt za celý následující den, pokud nebylo předem dohodnuto jinak. Pokoj se považuje za uvolněný potom, co host vynese z pokoje všechny své věci, odevzdá klíč pověřenému zaměstnanci ubytovacího zařízení a oznámí, že se odhlašuje z pobytu. Ubytovatel si vyhrazuje právo na kontrolu inventáře pokoje (nábytek, spotřebiče, zapomenuté věci) a úhrady a spotřeby hosta, a to do 1 hodiny od uvolnění pokoje. Hotel nezodpovídá za movité věci vnesené hostem do pokoje poté, co host uvolní pokoj či poté co skončí ubytovací vztah mezi hotelem a hostem. Pokud host neuvolní pokoj, vyhrazuje si hotel právo zamezit hostu přístup do pokoje a pro případ neuhrazené platby za pobyt či jiných pohledávek za klientem si hotel vyhrazuje možnost využít zadržovací právo k movitým věcem vneseným hostem do pokoje.'),
		array('p','6. V případě, že host požádá o prodloužení ubytování, může mu ubytovatel nabídnout i jiný pokoj v jiné cenové relaci, než byl ten původní. V tomto případě host nemá nárok na ubytování v pokoji, ve kterém byl původně ubytovaný, a ani na ubytování v jiném pokoji, pokud to z kapacitních nebo provozních důvodů není možné.'),
		array('p','7. Ubytovatel si vyhrazuje právo ve výjimečných případech nabídnout hostovi jiné ubytování, než bylo původně dohodnuté, pokud se podstatně neliší od potvrzené objednávky.'),
		array('p','8. Ubytovatel poskytuje svým hostům služby v rozsahu, v jakém byly vzájemně dohodnuty a v rozsahu, v jakém to určuje příslušný platný právní předpis. Host je povinen uhradit platbu za ubytování a poskytnuté služby v souladu s platným ceníkem ubytovatele nejpozději v den skončení pobytu. Tímto jsou platební podmínky nedotknutelné na základě smluv o ubytování. Ceník služeb za přechodné ubytování a další služby je k nahlédnutí na recepci hotelu.'),
		array('p','9. Host je povinen přizpůsobit pobyt v hotelu svému aktuálnímu zdravotnímu stavu a fyzickým i psychickým schopnostem.'),
		array('p','10. Hotel si vyhrazuje právo hosta neubytovat, pokud oděv či chování hosta neodpovídá dobrým mravům, host je zjevně pod vlivem alkoholu či psychotropních látek nebo je host či jeho oděv či zavazadla nadměrně znečištěn.'),
		array('h3','Platba za poskytnuté ubytování a storno poplatky'),
		array('p','11. Za ubytování a poskytnuté služby je host povinen uhradit cenu v souladu s platným ceníkem, nejpozději však v den ukončení pobytu na základě předložení účtu, spolu s vyúčtováním poskytnutých záloh ze strany hosta.'),
		array('p','12. Ubytovatel si vyhrazuje právo požadovat od hosta při rezervaci zálohu 50 % až 100 % z ceny ubytování či za tímto účelem vyžadovat sdělení údajů platební karty hosta. Rezervace se pro ubytovací zařízení stává závaznou až po obdržení úhrady zálohy na účet ubytovatele, není-li dohodnuto jinak.'),
		array('p','13. V případě zkrácení pobytu nebo jiné změny hostem, má ubytovatel právo hostovi vyúčtovat plnou výši (100 %) dohodnuté ceny za celou délku pobytu.'),
		array('p','14. Ubytovatel je oprávněný účtovat storno poplatky, a na jejich zaplacení použít i složenou zálohu, v případě, že host zruší svou rezervaci pobytu písemně, elektronicky nebo telefonicky dle následujících podmínek:'),
		array('p','– V případě nevratné rezervace není možné tuto rezervaci bezplatně zrušit a host nemá nárok na vrácení zaplacené zálohy. Ubytovatel si u této rezervace za účelem garance ubytování vyhrazuje právo požadovat sdělení údajů platební karty hosta pro provedení zálohové platby v plné výši ceny ubytování.'),
		array('p','– V případě zrušení větší (5 a více pokojů) rezervace v době více než 30 dnů před prvním dnem pobytu hostů je toto zrušení zdarma.'),
		array('p','– V případě zrušení větší rezervace v době mezi 30. dnem a 72 hodinami před prvním dnem pobytu hostů činí storno poplatek 50 % z celkové ceny rezervace.'),
		array('p','– V případě zrušení větší rezervace méně jak 72 hodin před prvním dnem pobytu hostů činí storno poplatek 100 % z celkové ceny rezervace.'),
		array('p','– V případě zrušení menší (4 a méně pokojů) rezervace do 72 hodin před jejím začátkem je toto zrušení zdarma.'),
		array('p','– V případě zrušení menší rezervace méně jak 72 hodin před jejím začátkem činí storno poplatek 100 % z celkové ceny rezervace.'),
		array('p','Nebo pokud se dopředu nedohodl ubytovatel s hostem jinak.'),
		array('h3','Zodpovědnost ubytovatele a hosta'),
		array('p','15. Ubytovatel zodpovídá za škodu způsobenou na věcech vnesených a odložených hostem v ubytovací části zařízení podle obecně závazných předpisů.'),
		array('p','16. Ubytovatel poskytuje hostům bezpečnostní trezory na pokoji, do kterých doporučuje uložit cennosti. Uložení věcí v trezoru v pokoji není možné považovat za převzetí věcí ubytovatelem do úschovy.'),
		array('p','17. Za škody způsobené na zařízení, resp. inventáři ubytovacího zařízení zodpovídá host podle příslušných platných právních předpisů. V případě poškození nebo zničení majetku ubytovatele má ubytovatel právo na náhradu škody. Je v zájmu hosta informovat se na hodnotu inventáře v případě znehodnocení nebo poškození zařízení v pokoji. Host jako zákonný zástupce zodpovídá za škody způsobené neplnoletými osobami, za které je zodpovědný, jako i za škody způsobené osobami nebo zvířaty, které se nacházejí v prostorách ubytovacího zařízení, a pobyt jim tam umožnil host.'),
		array('p','18. V případě škody na majetku ubytovatele způsobené hostem je host povinen uhradit náhradu způsobené škody nejpozději v den skončení pobytu hosta nebo na základě faktury vystavené do 14 dní ode dne skončení pobytu hosta, splatné do 10 dní od doručení hostovi za předpokladu, že ubytovatel rozhodne o takovém způsobu úhrady škody. Hotel je oprávněn provést blokaci a stržení takto vyúčtovaných částek na platební kartě hosta.'),
		array('p','19. Praní prádla hostů. Ubytovatel si vyhrazuje odmítnout ošetřit prádlo, jež je nadměrně znečištěno nebo je poškozeno. Čistírna nenese odpovědnost za narušené vybarvení (ekologické barvy), knoflíky či ozdobné spony poškozené během čistícího procesu. Náhrady škody či ztráty vzniklé vinou čistírny může dosáhnout maximálně pětinásobku ceny za čištění nebo praní uvedeného prádla.'),
		array('p','20. Ubytovatel nezodpovídá za odcizení, případně za poškození motorových vozidel ponechaných na parkovišti ubytovatele. Ubytovatel doporučuje hostům, aby se přesvědčili o řádném uzamčení a zabezpečení auta. Také doporučuje nenechávat v autě volně položené osobní věci. Ubytovatel nenese odpovědnost za škody způsobené hostem na parkovišti třetím osobám. Ubytovatel si vyhrazuje právo požadovat a vyúčtovat škodu, jež vznikne na majetku zařízení vozidlem hosta.'),
		array('p','21. Host je povinen chovat se tak, aby předcházel škodám na zdraví, na majetku, na přírodě a životním prostředí. Před odchodem z pokoje host je povinen uzavřít okna, vodovodní kohoutky, vypnout elektrické přístroje a uzamknout pokoj.'),
		array('p','22. Ubytovatel nezodpovídá za jakékoliv škody způsobené mimo vyhrazený areál hotelu.'),
		array('h3','Stravování a prodej alkoholických nápojů'),
		array('p','23. V prostorách ubytovacího zařízení je povolena konzumace alkoholu osobám starším 18 let, a to výhradně v rámci nápojového lístku nebo vinné karty ubytovatele.'),
		array('p','24. Host není oprávněn vnášet do pokojů alkoholické nápoje nebo jakékoliv jiné potraviny zakoupené jinde než v prostorách ubytovatele.'),
		array('p','25. Host je povinen seznámit personál ubytovacího zařízení s jakýmikoliv závažnými zdravotními omezeními, příp. stravovacími omezeními a tyto omezení nahlásí na recepci.'),
		array('p','26. Personál je oprávněn odmítnout podat alkoholický nápoj osobám mladším 18 let a osobám zjevně již pod vlivem alkoholu.'),
		array('p','27. Ubytovatel poskytuje snídaně, obědy a večeře v restauraci GRID BUFFET v časovém rozmezí určeném dle provozu.'),
		array('p','28. Při příchodu na snídaně je host povinen mít na zápěstí připnutý identifikační pásek. Personál je oprávněn provádět kontrolu jeho nošení. V případě přetržení nebo jiného poškození tohoto pásku host výměnou za zničený kus obdrží od hotelové recepce pásek nový.'),
		array('p','29. Součástí všech pokojů hotelu jsou minibary, jež může dle svého uvážení host využít. Ceny a služby jsou určeny v ceníku určeném pro minibar. Minibary jsou pokojovou službou denně doplňovány. Každá spotřebovaná nebo doplněná položka, jež je součástí sortimentu minibaru, je zaznamenána na kontrolním lístku pokojovou službou.'),
		array('p','30. Kontrolní lístek minibaru vyplněný nebo i prázdný v případě, že host z minibaru nekonzumoval, musí host podepsaný odevzdat na hotelové recepci při odjezdu. Jinak nemůže být účet hosta uzavřen. Host svým podpisem na kontrolním lístku potvrzuje množství konzumace. Ubytovatel není povinen kontrolovat při odjezdu hosta stav a počet položek v minibaru.'),
		array('p','31. V případě nesrovnalostí v konzumaci minibaru bude hostu naúčtována dlužná částka k zaplacení. Na dodatečné reklamace k výši konzumace nebude brán zřetel. Hotel je oprávněn provést blokaci a stržení takto vyúčtovaných částek na platební kartě hosta.'),
		array('h3','Všeobecně platné ustanovení'),
		array('p','32. Pro přijímání návštěv ubytovaných hostů jsou vyhrazeny prostory přízemí hotelu, případně jiné společenské prostory hotelu. V pokoji, kde je host ubytovaný, smí přijímat návštěvy jen se souhlasem zodpovědného zaměstnance nebo vedení hotelu, po zaevidování v čase od 08:00 h. do 22:00 h. Zaměstnanec hotelu není oprávněn podávat jakékoliv informace o ubytovaných hostech třetím osobám (s výjimkou příslušníků policie po jejich legitimování se a prokázání opodstatněnosti požadovat tyto údaje) ani povolit návštěvu třetí osoby hosta bez jeho souhlasu.'),
		array('p','33. V pokoji a společenských prostorách nesmí host bez souhlasu zodpovědného pracovníka nebo vedení přemisťovat interiérové zařízení, provádět jakékoliv změny a úpravy na zařízení, vykonávat zásahy do elektrické sítě nebo jiné instalace.'),
		array('p','34. V pokoji není hostům dovoleno používat vlastní elektrické spotřebiče. Toto nařízení se netýká elektrických spotřebičů osobní hygieny (holicí strojek, masážní strojek, fén atd.)'),
		array('p','35. Hostům není dovoleno vnášet do pokojů věci pro úschovu, kterým nejsou vyčleněna místa, např. sportovní potřeby, kočárky, kola, vozíky apod. Na úschovu těchto věcí se host informuje na recepci. Za poškození majetku ubytovatele způsobené i přes tento zákaz bude hostovi účtována náhrada škody v plné výši. V případě porušení tohoto zákazu je ubytovatel oprávněn účtovat hostovi smluvní pokutu ve výši 1 000 Kč za každé porušení. V případě, že bude způsobená škoda vyšší, ubytovatel si vyhrazuje právo účtovat škodu v plné výši.'),
		array('p','36. Kouření je ve všech vnitřních prostorách hotelu striktně zakázáno! Povoleno je jen ve vyhrazených venkovních prostorách ubytovatele. V pokojích, platí přísný zákaz kouření. V případě porušení tohoto zákazu je ubytovatel oprávněn účtovat hostovi smluvní pokutu ve výši 5 000 Kč za každé porušení. V případě, že bude způsobená škoda vyšší, ubytovatel si vyhrazuje právo účtovat škodu v plné výši.'),
		array('p','37. V hotelu je přísný zákaz užívání jakýchkoliv omamných a psychotropních látek. Ubytovatel je oprávněn informovat Policii ČR a okamžitě zrušit ubytování hosta, jenž tento zákaz porušil, bez náhrady.'),
		array('p','38. Psi a jiná zvířata se mohou pohybovat v prostorách ubytovacího zařízení jen se souhlasem zodpovědného zaměstnance nebo na základě předcházející dohody hosta za předpokladu, že majitel prokáže jejich zdravotní způsobilost. Cena za ubytování zvířete se účtuje podle platného ceníku. Na ubytování psů a jiných zvířat se vztahují následující opatření:'),
		array('p','Psům a jiným zvířatům je zakázaný vstup a pobyt v těch prostorách, ve kterých jsou skladované a připravované potraviny nebo se podávají jídla a nápoje.'),
		array('p','Vstup do ubytovací části mají pouze malá plemena psů.'),
		array('p','Ve všech veřejných prostorách musí být každý pes na vodítku a mít náhubek.'),
		array('p','Psi a jiná zvířata se nesmí nechat odpočívat/ležet na lůžku nebo jiném zařízení, které slouží k odpočinku hosta.'),
		array('p','Na krmení psů a jiných zvířat nesmí být použitý inventář, který slouží na přípravu nebo podávání jídla hostům.'),
		array('p','V případě jakéhokoliv poškození zařízení zvířetem je host povinen zaplatit škodu v plné výši. Za zvíře zodpovídá v plném rozsahu majitel zvířete a host, který zvířeti pobyt v pokoji umožnil.'),
		array('p','Za výše uvedené porušení pravidel a opatření, vyjma přímého poškození majetku, které je účtováno hostu v plné výši, bude hostovi účtována za dodatečný úklid pokoje či prostor znečištěný zvířetem částka až ve výši 5 000 Kč. Ubytovatel si vyhrazuje právo vyúčtovat případně i přímé náklady za čištění, jež budou převyšovat výše uvedenou částku, a to v plné výši. Ubytovatel si také vyhrazuje právo k úhradě zaplacení nových lůžkovin, jež byly použity pro odpočinek zvířat. Tyto lůžkoviny budou hostovi vyúčtovány v plné výši.'),
		array('p','Úklid, kontrola pokoje a opravy na pokojích, kde je host ubytován i se zvířetem, musí být umožněny tak, aby nedošlo k ohrožení personálu či jiných hostů. Kontrola musí být umožněna, alespoň jednou denně pro případné zjištění škod či nadměrného znečištění. Personál není povinen provést úklid nebo opravy na pokoji v případě, že se cítí být ohrožen psem nebo jiným zvířetem na pokoji.'),
		array('p','39. Před odchodem je host povinen odevzdat kartu od pokoje při odhlašování z pobytu.'),
		array('p','40. Za ztrátu či znehodnocení karty účtuje ubytovatel částku 100 Kč za kus.'),
		array('p','41. Odpadky jsou hosti povinni dávat výlučné do určených nádob na vyhrazených místech.'),
		array('p','42. Ubytovatel doporučuje z bezpečnostních důvodů neponechávat děti do 12 roků bez dozoru dospělých ani v pokoji ani v ostatních společenských prostorách.'),
		array('p','43. V čase od 22:00 h. do 07:00 h. je host povinen dodržovat noční klid. Se souhlasem provozovatele (vedoucího, resp. zástupce) se mohou organizovat v prostorách zařízení společenské akce i po 22:00 h., a to v prostorách k tomu určených.'),
		array('p','44. Host v prostorách ubytovacího zařízení nesmí nosit střelnou zbraň, střelivo či jiné zbraně, nebo je jakýmkoli způsobem přechovávat ve stavu umožňujícím jejich okamžité použití.'),
		array('p','45. Stížnosti hostů a případné návrhy na zlepšení činnosti přijímá vedení hotelu. Dotazník je k dispozici na hotelových pokojích nebo na recepci.'),
		array('p','46. Spory, které vzniknou z této smlouvy, budou řešeny prostřednictvím soudů v České republice. Ve sporech o náhradu škody, ve kterých žalovanou osobou bude osoba mající bydliště v některém z členských států EU, je daná příslušnost soudu místa, kde ke škodě došlo, podle čl. 5, bod 3 Nařízení rady (ES) č. 44/2001 ze dne 22. 12. 2000 o příslušnosti a uznávání a výkonu soudních rozhodnutí v občanských a obchodních věcech.'),
		array('p','47. Host je povinen dodržovat ustanovení tohoto ubytovacího řádu. V případě, že host nebude dodržovat ubytovací řád, má ubytovatel právo odstoupit od poskytování ubytovacích služeb a odstoupit od ubytovací smlouvy před uplynutím dohodnutého času. Ubytovatel má v takovém případě právo na plnou úhradu ceny za ubytování. Host musí následně bezodkladně opustit hotel. Host je povinen obeznámit se s provozními a bezpečnostními pravidly ubytovatele, včetně všech jeho zařízení a důsledně je dodržovat.'),
		array('p','48. Host svým podpisem registrační karty odsouhlasil, že se obeznámil s provozním řádem ubytovatele. Ubytování hostů se řídí českým právním řádem, na základě českého práva a tímto ubytovacím řádem. Ubytováním host přijímá ubytovací řád jako smluvní podmínky ubytování a je povinen dodržovat jeho ustanovení. Host je povinen se s tímto ubytovacím řádem seznámit, na jeho neznalost nebude brán zřetel.'),
		array('p','49. Host poskytující ubytovateli při vzniku ubytovací služby své osobní údaje ze svých dokladů souhlasí se zpracováním a uchováním svých osobních údajů ve společnosti GRH s.r.o. ve smyslu zák. č. 101/2000 Sb. v platném znění'),
		array('h3','Ochrana spotřebitele'),
		array('p','Poskytujeme Vám tímto veškeré informace dle ustanovení § 1811 a § 1820 zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník“).'),
		array('p','Ubytovatel poskytuje ubytovaným hostům následující informace:'),
		array('p','a) Totožnost a kontaktní údaje ubytovatele: GRH s.r.o., Ostrovačická 936/65, 641 00 Brno, DIČ CZ04996364, společnost zapsaná v obchodním rejstříku vedeném u Krajského soudu v Brně, oddíl C, vložka 92997;'),
		array('p','b) hlavní předmět podnikání ubytovatele: poskytování ubytovacích služeb;'),
		array('p','c) označení služby: ubytovatel obstarává pro ubytované hosty ubytování a služby související s ubytováním na základě podmínek uvedených v potvrzení rezervace,'),
		array('p','d) náklady na prostředky komunikace na dálku: náklady na prostředky komunikace na dálku určují subjekty poskytující služby prostředků komunikace na dálku a tyto náklady se neliší od základní sazby;'),
		array('p','e) údaj o existenci, způsobu a podmínkách mimosoudního vyřizování stížností spotřebitelů včetně údaje, zda se lze obrátit na orgán dohledu; ubytovaný host má právo podat návrh na mimosoudní řešení takového sporu určenému subjektu mimosoudního řešení spotřebitelských sporů, kterým je:'),
		array('p','Česká obchodní inspekce'),
		array('p','Ústřední inspektorát – oddělení ADR Štěpánská 15, 120 00 Praha 2'),
		array('p','Email: adr@coi.cz / Web: adr.coi.cz.'),
		array('p','Česká obchodní inspekce je dozorovým orgánem vykonávajícím dohled nad ochranou spotřebitele, postupující podle zákona č. 64/1986 Sb., o České obchodní inspekci, ve znění pozdějších předpisů, a dalších právních předpisů. Internetová stránka České obchodní inspekce je www.coi.cz ;'),
		array('p','f) v souladu s ustanovením § 1837 písmeno j) občanského zákoníku ubytovaným hostům jako spotřebitelům nevzniká právo na odstoupení od smlouvy o ubytování, pokud ubytovatel poskytuje plnění v určeném termínu;'),
		array('p','g) označení členského státu nebo členských států Evropské unie, jejichž právními předpisy se bude řídit vztah mezi ubytovaným hostem a ubytovatelem založený na základě potvrzení rezervace: Česká republika;'),
		array('p','h) údaj o jazyku, ve kterém bude ubytovaný host s ubytovatelem jednat po dobu pobytu a ve kterém poskytne ubytovaným hostům smluvní podmínky a další údaje: český jazyk.'),
		array('p','Ubytovací řád je platný od 1. 6. 2017 a byl aktualizovaný 14. 11. 2023'),
		array('h2','Reklamační řád GRID HOTELU'),
		array('p','GRID HOTEL je provozovaný společností GRH s.r.o., se sídlem Ostrovačická 936/65, 641 00 Brno – Žebětín, IČ: 04996364, DIČ: CZ04996364, zapsaná v OR Krajského soudu v Brně, oddíl C, vl. 92997, zastoupena jednatelem Ing. Karlem Hubáčkem (dále jen „hotel“).'),
		array('h3','1. Předmět'),
		array('p','1.1. Tento reklamační řád upravuje v souladu s platnými právními předpisy, zejména zákonem č. 89/2012 Sb., občanský zákoník, ve znění pozdějších předpisů (dále jen „občanský zákoník“), a zákonem č. 634/1992 Sb., o ochraně spotřebitele, ve znění pozdějších předpisů (dále jen „zákon o ochraně spotřebitele“), rozsah, podmínky a způsob uplatňování práv zákazníka z vadného plnění vyplývajícího z odpovědnosti hotelu za vady pobytu, poskytnuté jednotlivé služby nebo prodaného zboží a jejich vyřizování (dále také jen „reklamace“). Reklamační řád je k dispozici také na webových stránkách společnosti hotelu – www.gridhotel.cz'),
		array('h3','2. Uplatňování reklamací'),
		array('p','2.1. V případě vadně poskytnutých služeb nebo služeb, které byly prokazatelně objednány a potvrzeny, avšak neposkytnuty, vzniká zákazníkovi právo reklamace. Práva z vadného plnění zákazník uplatňuje v kterékoliv provozovně společnosti v sídle nebo u zprostředkovatele služeb hotelu, kde reklamované služby či zboží zakoupil, případně v místě poskytované služby u pověřeného zástupce hotelu.'),
		array('p','2.2. Zákazník je povinen vytknout vadu poskytovaných služeb včas, bez zbytečného odkladu, pokud možno na místě poskytnutí služby. Nevytkne-li zákazník vadu poskytovaných služeb bez zbytečného odkladu, nemůže mu být reklamace uznána. Právo z odpovědnosti za vady jednotlivé služby zakoupené na základě smlouvy o poskytnutí jednotlivé služby je zákazník povinen vytknout bez zbytečného odkladu po jejím zjištění, nejpozději však do 6 měsíců od okamžiku, kdy mu byla služba poskytnuta. Neprodlené vytknutí vady (uplatnění reklamace) na místě samém umožní odstranění vady okamžitě, zatímco s odstupem času se ztěžuje průkaznost i objektivnost posouzení a tím i možnost řádného vyřízení reklamace.'),
		array('p','2.3. Práva z odpovědnosti za vady prodaného zboží zaniknou, nebyla-li uplatněna do 24 měsíců ode dne převzetí.'),
		array('p','2.4. Zákazník je při uplatňování reklamace povinen uvést jméno, příjmení, adresu, co je obsahem reklamace, svou reklamaci zdůvodnit a podle možností i předmět reklamace průkazně skutkově doložit; současně je doporučeno předložit doklad o poskytnuté službě, stejnopis objednávky, fakturu, potvrzení o platbě apod., čímž se usnadní vyřizování reklamace. V případě zakoupeného zboží je zákazník povinen jej při reklamaci předložit.'),
		array('p','2.5. Reklamaci může zákazník uplatnit jakoukoliv formou s uvedením data, předmětu reklamace a požadovaného způsobu vyřízení reklamace. V případě ústního podání reklamace je hotelem pověřený zástupce povinen sepsat se zákazníkem reklamační protokol, resp. vydat písemné potvrzení o přijetí reklamace. V protokolu uvede osobní údaje zákazníka, kdy zákazník reklamaci uplatnil, co je obsahem reklamace, jaký způsob vyřízení reklamace zákazník vyžaduje a dále datum a požadovaný způsob vyřízení reklamace. Protokol, resp. potvrzení o přijetí reklamace podepíše sepisující zástupce hotelu i zákazník, který podpisem vyslovuje souhlas s jeho obsahem.'),
		array('p','2.6. Jestliže zákazník zároveň předá hotelu nebo zprostředkovateli služeb hotelu písemnosti, popř. jiné podklady týkající se reklamace, popř. reklamované zboží musí být tato skutečnost v protokolu výslovně uvedena.'),
		array('h3','3. Vyřizování reklamací'),
		array('p','3.1. Hotel je povinen zákazníkovi vydat písemné potvrzení o tom, kdy zákazník reklamaci uplatnil, co je obsahem reklamace, jaký způsob vyřízení reklamace zákazník požaduje a dále potvrzení o datu a způsobu vyřízení reklamace a v případě reklamovaného zboží, včetně potvrzení o provedení opravy a době jejího trvání, případně písemné odůvodnění zamítnutí reklamace.'),
		array('p','3.2. Uplatní-li zákazník právo z vadného plnění související se službami, které mu jsou poskytovány, nebo které mu již byly poskytnuty, vedoucí provozovny poskytující předmětné služby nebo jiný hotelem pověřený zástupce je povinen po potřebném prozkoumání skutkových a právních okolností rozhodnout o reklamaci ihned, ve složitých případech do tří pracovních dnů. Do této doby se nezapočítává doba potřebná k odbornému posouzení vady. Reklamace musí být vyřízena bez zbytečného odkladu, nejpozději do 30 dnů od uplatnění reklamace zákazníkem, pokud se zákazníkem není dohodnuta lhůta delší.'),
		array('p','3.3. V případě písemných reklamačních podání platí pro jejich obsah přiměřeně ustanovení odstavce 3.1. reklamačního řádu.'),
		array('h3','4. Součinnost zákazníka při vyřizování reklamací'),
		array('p','4.1. Zákazník je povinen poskytnout potřebnou součinnost k vyřízení reklamace, zejména podat informace, předložit doklady prokazující skutkový stav, předložit reklamované zboží, specifikovat své požadavky co do důvodu a výše apod. Vyžaduje-li to povaha věci, musí zákazník umožnit pověřenému zástupci hotelu, jakož i zástupcům dodavatele služby přístup do prostoru, který mu byl poskytnut k ubytování apod., aby se mohli přesvědčit o oprávněnosti reklamace.'),
		array('p','4.2. V případech, kdy zákazník čerpá služby bez přítomnosti zástupce hotelu a poskytnutá služba má vady, doporučuje hotel, aby zákazník dbal též o včasné a řádné uplatnění nároků vůči dodavatelům služeb.'),
		array('h3','5. Způsoby vyřízení reklamace'),
		array('p','5.1. V případech, kdy je reklamace posouzena jako zcela nebo z části důvodná, spočívá vyřízení reklamace v bezplatném odstranění vady služby nebo reklamovaného zboží, nebo v případech, kdy je to možné i k poskytnutí náhradní služby či výměny zboží. V závislosti na rozsahu a trvání vady má zákazník právo na přiměřenou slevu z ceny. Tím není dotčeno právo zákazníka domáhat se v zákonem stanovených případech odstoupení od smlouvy. V případech, kdy je reklamace posouzena jako nedůvodná, je zákazník písemně informován o důvodech zamítnutí reklamace.'),
		array('p','5.2. Nastanou-li okolnosti, jejichž vznik, průběh a příp. následek není závislý na vůli, činnosti a postupu hotelu (vis maior) nebo okolnosti, které jsou na straně zákazníka, na jejichž základě zákazník zcela nebo zčásti nevyužije objednané, zaplacené a hotelem zabezpečené služby, nevzniká zákazníkovi nárok na vrácení zaplacené ceny nebo na slevu z ceny.'),
		array('h3','6. Ostatní ustanovení'),
		array('p','6.1. V ostatním platí ustanovení obecně závazných právních předpisů, zejména občanského zákoníku a zákona o ochraně spotřebitele.'),
		array('p','6.2. V souladu s ustanovením § 14 zákona č. 634/1992 Sb., o ochraně spotřebitele, ve znění pozdějších předpisů má zákazník možnost řešit případné spory vyplývající ze smluv uzavřených s hotelem prostřednictvím subjektu mimosoudního řešení spotřebitelských sporů, kterým je Česká obchodní inspekce, se sídlem Štěpánská 567/15, Praha 2, PSČ 120 00, internetová adresa www.coi.cz.'),
		array('h3','7. Závěrečná ustanovení'),
		array('p','7.1. Tento Reklamační řád vstupuje v platnost a účinnost dnem 1. 6. 2017 a byl aktualizovaný dne 14. 11. 2023.'),
		array('p','7.2. Tento reklamační řád bude vyvěšen na vhodném a veřejně přístupném místě v hotelu a také na internetových stránkách hotelu www.gridhotel.cz.'),
		array('p','Ing. Karel Hubáček, jednatel společnosti'),
		array('p','V Brně dne 14. 11. 2023'),
	);
}
function grid_sc_podminky() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker">Právní informace</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 8px">Ubytovací a reklamační řád</h1>
	    <p style="color:var(--muted);margin:0 0 24px">Všeobecné obchodní podmínky GRID HOTELU. Ubytováním host přijímá ubytovací řád jako smluvní podmínky ubytování.</p>
	    <div class="legal-body">
	      <?php foreach ( grid_podminky_rows() as $r ) {
	        list( $t, $txt ) = $r;
	        if ( $t === 'h2' )      echo '<h2 style="font-size:clamp(1.5rem,3.4vw,2.1rem);margin:34px 0 10px">' . esc_html( $txt ) . '</h2>';
	        elseif ( $t === 'h3' )  echo '<h3 style="font-size:clamp(1.1rem,2.4vw,1.35rem);margin:24px 0 8px;color:var(--gold,#CAA75F)">' . esc_html( $txt ) . '</h3>';
	        else                    echo '<p>' . esc_html( $txt ) . '</p>';
	      } ?>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_podminky', 'grid_sc_podminky' );


/* ============================================================
 * [grid_kontakt] — plné kontaktní informace + mapa + formulář
 * ============================================================ */
function grid_sc_kontakt() {
	$a1 = grid_field( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh', 'option' );
	$a2 = grid_field( 'adresa_2', '641 00 Brno – Žebětín, ČR', 'option' );
	$telr = grid_field( 'tel_recepce', '+420 775 877 721', 'option' );
	$telrez = grid_field( 'tel_rezervace', '+420 775 877 720', 'option' );
	$tels = grid_field( 'tel_shuttle', '+420 775 778 718', 'option' );
	$email = grid_field( 'email', 'info@gridhotel.cz', 'option' );
	$ico = grid_field( 'ico', '04996364', 'option' );
	$dic = grid_field( 'dic', 'CZ04996364', 'option' );
	$spis = grid_field( 'spis_znacka', 'Sp. zn. C 92997, KS v Brně', 'option' );
	$tel = function( $t ) { return preg_replace( '/\s+/', '', $t ); };
	$dest = rawurlencode( 'Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	$mapsrc = 'https://www.google.com/maps?q=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' ) . '&output=embed&hl=cs';
	$navig  = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( 'GRID HOTEL, Ostrovačická 936/65, 641 00 Brno-Žebětín' );
	ob_start(); ?>
	<section class="sec sec-light sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker">Kontakt</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Jsme přímo v areálu Autodromu Brno</h1>
	    <p style="max-width:60ch;color:var(--muted)">Napište nám kvůli rezervaci, firemní akci, svatbě, cateringu, dárkovému poukazu nebo dopravě.</p>
	    <div class="kontakt-grid">
	      <div class="k-info">
	        <div class="k-block"><h3>Adresa</h3><p><?php echo esc_html( $a1 ); ?><br><?php echo esc_html( $a2 ); ?></p><a class="btn btn-ghost" href="<?php echo esc_url( $navig ); ?>" target="_blank" rel="noopener">Navigovat →</a></div>
	        <div class="k-block"><h3>Rezervace</h3><p><a href="tel:<?php echo esc_attr( $tel($telrez) ); ?>"><?php echo esc_html( $telrez ); ?></a><br><a href="mailto:reservations@gridhotel.cz">reservations@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Recepce</h3><p><a href="tel:<?php echo esc_attr( $tel($telr) ); ?>"><?php echo esc_html( $telr ); ?></a><br><a href="mailto:reception@gridhotel.cz">reception@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Shuttle bus</h3><p><a href="tel:<?php echo esc_attr( $tel($tels) ); ?>"><?php echo esc_html( $tels ); ?></a></p></div>
	        <div class="k-block"><h3>Vedení</h3><p>Zuzana Ulmanová<br>výkonná ředitelka<br><a href="mailto:ulmanova@gridhotel.cz">ulmanova@gridhotel.cz</a></p></div>
	        <div class="k-block"><h3>Provozovatel</h3><p>GRH s.r.o.<br>IČ: <?php echo esc_html( $ico ); ?> · DIČ: <?php echo esc_html( $dic ); ?><br><?php echo esc_html( $spis ); ?></p></div>
	        <div class="k-block k-full"><h3>Fakturační údaje</h3><p class="data">Fio banka a.s.<br>CZK: 2203313575/2010 · IBAN CZ43 2010 0000 0022 0331 3575<br>EUR: 2003368942/2010 · IBAN CZ10 2010 0000 0020 0336 8942<br>BIC/SWIFT: FIOBCZPPXXX</p></div>
	      </div>
	      <div class="k-form">
	        <h3>Napište nám</h3>
	        <?php echo grid_blind_form( 'kontakt' ); ?>
	      </div>
	    </div>
	    <div class="doprava-map" style="margin-top:34px"><iframe src="<?php echo esc_url( $mapsrc ); ?>" title="Mapa — GRID HOTEL" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_kontakt', 'grid_sc_kontakt' );

/* ============================================================
 * [grid_gdpr] — Prohlášení o ochraně osobních údajů (plný text 1:1)
 * ============================================================ */
function grid_sc_gdpr() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:840px">
	    <span class="kicker">GDPR</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 20px">Prohlášení ke zpracování osobních údajů</h1>
	    <div class="legal-body">
	      <p>Společnost GRH s. r. o, se sídlem Ostrovačická 936/65, Žebětín, 621 00 Brno, zapsaná v obchodním rejstříku Krajského soudu v Brně, oddíl C, vložka 92997, IČ: 04996364, jakožto správce osobních údajů (dále jen Společnost) v souvislosti s poskytováním svých služeb a nabízením svých produktů zpracovává osobní údaje Subjektů osobních údajů (dále jen Klienti).</p>
	      <p>Smyslem Prohlášení ke zpracování osobních údajů je poskytnout informace zejména o tom, jaké osobní údaje Společnost shromažďuje, jak s nimi nakládá, z jakých zdrojů je získává, k jakým účelům je využívá, komu je smí poskytnout, kde může získat informace o osobních údajích, které zpracovává, a jaká jsou individuální práva Klienta v oblasti ochrany osobních údajů.</p>
	      <h2>I. Obecné informace</h2>
	      <p>Osobní údaje Klienta zpracovává Společnost v minimálním rozsahu nezbytném pro účely nabízení svých služeb a produktů.</p>
	      <p>Při zpracování osobních údajů Společnost ctí a respektuje nejvyšší standardy ochrany osobních údajů a dodržuje zejména následující zásady:</p>
	      <p>(a) osobní údaje jsou vždy zpracovávány pro jasně a srozumitelně stanovený účel, stanovenými prostředky, stanoveným způsobem, a pouze po dobu, která je nezbytná vzhledem k účelům jejich zpracování; zpracovávány jsou pouze přesné osobní údaje, jejichž zpracování odpovídá stanovenému účelu a je nezbytné pro naplnění tohoto účelu;</p>
	      <p>(b) osobní údaje jsou zpracovávány způsobem, který zajišťuje nejvyšší možnou bezpečnost těchto údajů a který zabraňuje jakémukoliv neoprávněnému nebo nahodilému přístupu, k jejich změně, zničení či ztrátě, neoprávněným přenosům, k jejich jinému neoprávněnému zpracování, jakož i k jinému zneužití;</p>
	      <p>(c) Společnost vždy srozumitelně informuje o zpracování osobních údajů a o nárocích na přesné a úplné informace, o okolnostech jejich zpracování, jakož i o dalších souvisejících právech;</p>
	      <p>(d) Společnost dodržuje odpovídající technická a organizační opatření, aby byla zajištěna úroveň zabezpečení odpovídající všem možným rizikům; veškeré osoby, které přicházejí do styku s osobními údaji, mají povinnost dodržovat mlčenlivost o informacích získaných v souvislosti se zpracováváním těchto údajů.</p>
	      <h2>II. Informace o zpracování osobních údajů</h2>
	      <h3>a) Informace o Správci</h3>
	      <p>GRH s. r. o, se sídlem Ostrovačická 936/65, Žebětín, 621 00 Brno, zapsaná v obchodním rejstříku Krajského soudu v Brně, oddíl C, vložka 92997, IČ: 04996364, e-mail: info@gridhotel.cz, telefon: +420 775 877 817</p>
	      <h3>b) Účely zpracovávání a právní základ pro zpracování</h3>
	      <p>Na základě dobrovolného souhlasu zpracovává Společnost osobní údaje za účelem řádného plnění smlouvy a výkonu práv z ní plynoucích, nabízení produktů a služeb Společnosti: jde zejména o šíření informací, nabízení produktů a služeb Společnosti a to různými kanály, např. poštou, elektronickými prostředky (včetně elektronické pošty a zpráv zaslaných na mobilní zařízení prostřednictvím telefonního čísla) či telefonickým hovorem, prostřednictvím webových stránek.</p>
	      <h3>c) Rozsah zpracovávaných osobních údajů</h3>
	      <p>Společnost zpracovává osobní údaje v rozsahu nezbytném pro naplnění výše uvedeného cíle. Zpracovává tyto identifikační údaje: Jméno a příjmení, narození, telefonní číslo, e mailovou adresu, adresu trvalého bydliště/sídla, číslo občanského průkazu a číslo řidičského průkazu dále údaje ze vzájemné komunikace smluvních stran a dále údaje vzniknuvší v důsledku plnění smlouvy.</p>
	      <h3>č) Způsob zpracování osobních údajů</h3>
	      <p>Zpracování osobních údajů zahrnuje manuální i automatizované zpracování v informačních systémech Společnosti. Údaje zpracovávají pouze zaměstnanci Společnosti.</p>
	      <h3>d) Příjemci osobních údajů</h3>
	      <p>Osobní údaje jsou zpřístupněny pouze zaměstnancům Společnosti v souvislosti s plněním jejich pracovních povinností, při kterých je nutné nakládat s osobními údaji, pouze však v rozsahu, který je v tom kterém případě nezbytný a při dodržení veškerých bezpečnostních opatření.</p>
	      <h3>e) Předávání osobních údajů do zahraničí</h3>
	      <p>Osobní údaje jsou zpracovávány pouze na území České republiky.</p>
	      <h3>f) Doba zpracování osobních údajů</h3>
	      <p>Osobní údaje zpracovává Společnost pouze po dobu, která je nezbytná vzhledem k účelu jejich zpracování. Průběžně posuzuje, jestli nadále trvá potřeba zpracovávat určité osobní údaje potřebné pro určitý účel. Pokud zjistí, že již nejsou potřebné pro účel, pro který byly zpracovávány, údaje zlikviduje.</p>
	      <h3>g) Právo odvolat souhlas</h3>
	      <p>Souhlas se zpracováním osobních údajů není Subjekt osobních údajů povinen udělit a zároveň je oprávněn tento souhlas kdykoliv odvolat.</p>
	      <h3>h) Právo na přístup k osobním údajům</h3>
	      <p>Klient má právo získat od Společnosti potvrzení, zda osobní údaje, které se ho týkají, jsou či nejsou zpracovány, a pokud tomu tak je, má právo získat přístup k těmto osobním údajům a dalším informacím dle čl. 15 nařízení GDPR.</p>
	      <h3>Ch) Právo na opravu</h3>
	      <p>Dle čl. 16 nařízení GDPR má Klient právo na to, aby Společnost bez zbytečného podkladu opravila nepřesné osobní údaje, které se ho týkají a doplnil osobní údaje neúplné.</p>
	      <h3>i) Právo na výmaz</h3>
	      <p>Klient má právo na to, aby Společnost bez zbytečného odkladu vymazal osobní údaje, které se daného subjektů týkají, a to za podmínek dle čl. 17 nařízení GDPR, zejména tedy pokud již nejsou potřebné pro účely, pro které byly shromážděny. Toto právo rovněž naplňuje tzv. právo být zapomenut.</p>
	      <h3>j) Právo na omezení zpracování</h3>
	      <p>Klient má právo na to, aby Společnost omezila zpracování jeho osobních údajů, a to v kterémkoliv z případů uvedených v čl. 18 nařízení GDPR.</p>
	      <h3>k) Právo na přenositelnost údajů</h3>
	      <p>Klient má ve smyslu a za podmínek uvedených v čl. 20 nařízení GDPR právo od Společnosti získat osobní údaje, které se ho týkají, a to ve strukturovaném běžně používaném a strojově čitelném formátu, a právo předat tyto údaje jinému správci, aniž by tomuto původní AMD bránil.</p>
	      <h3>l) Právo podat stížnost</h3>
	      <p>Klient je oprávněn v souvislosti se zpracováním svých osobních údajů podat stížnost ve smyslu čl. 77 nařízení GDPR u dozorového úřadu. Dozor nad dodržováním povinností při zpracováním osobních údajů vykonává Úřad pro ochranu osobních údajů se sídlem Pplk. Sochora 27, 170 00 Praha. Více informací o právech subjektů údajů je k dispozici na internetových stránkách Úřadu pro ochranu osobních údajů (<a href="https://www.uoou.cz/6-prava-subjektu-udaj/d-27276" target="_blank" rel="noopener">https://www.uoou.cz/6-prava-subjektu-udaj/d-27276</a>).</p>
	      <p><em>Toto Prohlášení je platné a účinné ke dni 25. 5. 2018. Aktuální znění Prohlášení je uveřejněno na webových stránkách www.gridhotel.cz</em></p>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_gdpr', 'grid_sc_gdpr' );

/* ============================================================
 * [grid_onas] — O hotelu
 * ============================================================ */
function grid_sc_onas() {
	$img = grid_field( 'pribeh_obrazek', '', 'option' ); $img = $img ? ( is_array($img)?$img['url']:$img ) : grid_img( 'hotel-okruh-leto.jpg' );
	$vurl  = grid_field( 'video_url', '', 'option' );
	$vpop  = grid_field( 'video_popis', 'Sledujte, jak GRID Hotel rostl přímo v srdci Autodromu Brno — od základů až po dnešní ****hotel u Masarykova okruhu.', 'option' );
	$embed = grid_video_embed( $vurl );
	$highlights = array(
		array( 'Ubytování ****',        'Vyberte si z 64 komfortních pokojů a apartmá s klimatizací, výhledem na trať i terasou.', array( 'ubytovani','pokoje' ), 'Pokoje &amp; apartmá' ),
		array( 'Gastronomie',           'Snídaňový bufet, denní menu i večerní à la carte — hotelová restaurace, PADDOCK i GRID Club.', array( 'gastronomie','gastro' ), 'Gastronomie' ),
		array( 'Zážitky u okruhu',      'Simulátor Masarykova okruhu, motokáry, škola smyku Polygonu i dárkové poukazy.', array( 'zazitky-u-okruhu','zazitky','aktivity' ), 'Zážitky' ),
		array( 'Firemní akce &amp; svatby', 'Konference, teambuildingy, večírky i svatby s cateringem na míru a doprovodným programem.', array( 'firemni-akce-svatby','firemni' ), 'Firemní akce' ),
		array( 'Doprava &amp; parkování', 'Pár minut z dálnice D1, parkoviště pro osobní auta i autobusy přímo u hotelu.', array( 'doprava' ), 'Jak se k nám dostanete' ),
		array( 'Sezóna 2026',           'MotoGP víkend, vytrvalostní závody i track days — buďte přímo u dění.', array( 'sezona-2026','sezona' ), 'Program sezóny' ),
	);
	ob_start(); ?>
	<section class="sec sec-dark carbon" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="split">
	    <div class="sp-media"><img src="<?php echo esc_url( $img ); ?>" alt="GRID HOTEL — Masarykův okruh"></div>
	    <div class="sp-content">
	      <span class="kicker">O hotelu</span>
	      <h1 style="font-size:clamp(2.4rem,5vw,4.2rem);margin:16px 0">Hotel přímo<br>v areálu okruhu</h1>
	      <p class="lead">GRID HOTEL nabízí ubytování přímo v areálu Masarykova okruhu v Brně — jako jediný hotel uvnitř Autodromu Brno.</p>
	      <p>GRID Hotel je certifikovaným **** hotelem. Start do nového dne Vám zpříjemní svojí nabídkou hotelové restaurace. GRID Club, hotelové lobby či nedaleká PADDOCK Restaurace s letní terasou Vám naopak v sezoně budou k dispozici po celý zbytek dne.</p>
	      <p>Samozřejmostí jsou Wi-Fi, parkoviště pro osobní auta i autobusy, individuálně nastavitelná klimatizace, hotelové služby, vyžití v rámci Autodromu, catering a organizační podpora při realizaci firemních a velkých akcí.</p>
	      <div class="stat-row">
	        <div class="stat"><span class="data">64</span><span>pokojů &amp; apartmá</span></div>
	        <div class="stat"><span class="data">****</span><span>evropský standard</span></div>
	        <div class="stat"><span class="data">0 m</span><span>od trati</span></div>
	      </div>
	      <div style="margin-top:26px;display:flex;gap:14px;flex-wrap:wrap"><a class="btn" href="<?php echo esc_url( grid_rezervace_url() ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( home_url('/#pokoje') ); ?>">Pokoje</a></div>
	    </div>
	  </div>
	</section>

	<section class="sec sec-light sec-pad">
	  <div class="wrap" style="max-width:900px">
	    <span class="kicker">Proč GRID</span>
	    <h2 style="font-size:clamp(2rem,4.5vw,3.4rem);margin:14px 0 18px">Adrenalin okruhu a klid lesů na jednom místě</h2>
	    <p style="max-width:70ch;color:var(--muted);font-size:1.06rem">Nejsme hotel <em>u</em> trati — stojíme <strong>přímo v areálu</strong> slavného Masarykova okruhu, pár kroků od paddocku a startovní roviny, obklopení brněnskými lesy. Ráno se probudíte do dění závodního víkendu, večer usnete v naprostém klidu.</p>
	    <p style="max-width:70ch;color:var(--muted);font-size:1.06rem">Během roku jsme domovskou základnou fanoušků motorsportu při <strong>MotoGP</strong>, vytrvalostních závodech i track days, stejně jako zázemím pro firemní akce, svatby a klidnou dovolenou. K tomu vlastní gastronomie, zážitky u okruhu a kompletní organizační podpora.</p>
	  </div>
	  <div class="wrap" style="margin-top:34px">
	    <div class="onas-grid">
	      <?php foreach ( $highlights as $h ) :
	        $u = grid_detail_url( $h[2] ); ?>
	        <div class="onas-card">
	          <h3><?php echo wp_kses_post( $h[0] ); ?></h3>
	          <p><?php echo wp_kses_post( $h[1] ); ?></p>
	          <?php if ( $u ) : ?><a class="sec-more" href="<?php echo esc_url( $u ); ?>"><?php echo wp_kses_post( $h[3] ); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
	        </div>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</section>

	<section class="sec sec-dark carbon sec-pad">
	  <div class="wrap" style="max-width:960px">
	    <span class="kicker">Časosběr</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 12px">Jak hotel vznikal</h2>
	    <p style="color:var(--muted);max-width:64ch"><?php echo esc_html( $vpop ); ?></p>
	    <div class="doprava-map" style="margin-top:24px">[grid_video_embed]</div>
	  </div>
	</section>

	<section class="sec sec-light final" style="background:var(--paper)">
	  <div class="wrap" style="text-align:center">
	    <span class="kicker" style="justify-content:center;display:inline-flex">Rezervace</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 18px;color:var(--ink)">Přespěte uprostřed Masarykova okruhu</h2>
	    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( grid_rezervace_url() ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( grid_nav_url('#kontakt') ); ?>">Kontaktovat recepci</a></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_onas', 'grid_sc_onas' );

/* ============================================================
 * [grid_kariera] — Kariéra
 * ============================================================ */
function grid_sc_kariera() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:760px">
	    <span class="kicker">Kariéra</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Restaurace a hotel GRID</h1>
	    <p style="color:var(--muted);font-size:1.1rem">Na Masarykově okruhu v Brně (areál Autodromu) hledáme nové kolegy do týmu.</p>
	    <div style="margin:26px 0;padding:26px;border:1px solid var(--line-c);border-radius:2px;background:var(--card)">
	      <p style="font-family:var(--f-head);text-transform:uppercase;font-size:1.4rem;color:var(--ink);margin-bottom:6px">V tuto chvíli nikoho nehledáme 🙁</p>
	      <p style="color:var(--muted)">Zkuste to prosím později — nebo nám rovnou napište, rádi si vás zařadíme do evidence.</p>
	    </div>
	    <a class="btn" href="mailto:info@gridhotel.cz?subject=Kariéra%20GRID%20HOTEL">Napsat nám životopis</a>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_kariera', 'grid_sc_kariera' );

/* ============================================================
 * [grid_video] — Časosběr / video stavby hotelu
 * ============================================================ */
function grid_sc_video() {
	$url = trim( (string) grid_field( 'video_url', '', 'option' ) );
	$pop = grid_field( 'video_popis', 'Časosběrné video ze stavby GRID Hotelu přímo v areálu Masarykova okruhu.', 'option' );
	$embed = grid_video_embed( $url );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:960px">
	    <span class="kicker">Časosběr</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Video stavby hotelu</h1>
	    <p style="color:var(--muted);max-width:60ch"><?php echo esc_html( $pop ); ?></p>
	    <div class="doprava-map" style="margin-top:26px">[grid_video_embed]</div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_video', 'grid_sc_video' );

/* ============================================================
 * [grid_galerie] — Fotogalerie s filtrem podle kategorií
 * ============================================================ */
function grid_sc_galerie( $atts = array() ) {
	$a = shortcode_atts( array( 'kicker' => 'Galerie', 'nadpis' => 'Fotogalerie GRID HOTEL', 'vse' => 'Vše' ), $atts );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 20px"><?php echo esc_html( $a['nadpis'] ); ?></h1>
	    <?php if ( function_exists( 'have_rows' ) && have_rows( 'galerie_bloky', 'option' ) ) :
		$cats = array(); $items = array(); $ci = 0;
		while ( have_rows( 'galerie_bloky', 'option' ) ) { the_row();
			$nazev = get_sub_field( 'nazev' ); $fotky = get_sub_field( 'fotky' );
			if ( ! $nazev ) $nazev = 'Galerie';
			$slug = 'c' . $ci; $cats[ $slug ] = $nazev;
			if ( is_array( $fotky ) ) foreach ( $fotky as $img ) {
				$full = is_array( $img ) ? ( ! empty( $img['url'] ) ? $img['url'] : '' ) : ( is_numeric( $img ) ? wp_get_attachment_image_url( $img, 'full' ) : '' );
				$thumb = is_array( $img ) ? ( ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $full ) : ( is_numeric( $img ) ? wp_get_attachment_image_url( $img, 'large' ) : '' );
				if ( $full ) $items[] = array( 'cat' => $slug, 'full' => $full, 'thumb' => $thumb, 'alt' => $nazev );
			}
			$ci++;
		}
		if ( ! empty( $items ) ) : ?>
		<div class="galerie-filter">
		  <button class="gal-fbtn active" data-filter="all"><?php echo esc_html( $a['vse'] ); ?></button>
		  <?php foreach ( $cats as $slug => $nazev ) : ?><button class="gal-fbtn" data-filter="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nazev ); ?></button><?php endforeach; ?>
		</div>
		<div class="galerie-grid">
		  <?php foreach ( $items as $it ) : ?>
		  <a class="gal-item" data-cat="<?php echo esc_attr( $it['cat'] ); ?>" href="<?php echo esc_url( $it['full'] ); ?>" data-lightbox="galerie"><img src="<?php echo esc_url( $it['thumb'] ); ?>" alt="<?php echo esc_attr( $it['alt'] ); ?>" loading="lazy"></a>
		  <?php endforeach; ?>
		</div>
		<?php else : ?><p style="color:var(--muted)">Galerie je zatím prázdná — nahraj fotky v GRID Nastavení → Galerie.</p><?php endif;
	  else : ?>
		<p style="color:var(--muted);font-family:var(--f-mono);font-size:.85rem">// Přidej kategorie a fotky v GRID Nastavení → Galerie.</p>
	  <?php endif; ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}
add_shortcode( 'grid_galerie', 'grid_sc_galerie' );
