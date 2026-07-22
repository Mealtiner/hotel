<?php
/**
 * Detail kategorie pokoje (typ pokoje) — CZ/EN/DE.
 * Texty čte z pluginu „GARRY – Kategorie pokojů" podle jazyka termu,
 * fotky (náhled + galerie) z ACF polí ZÁKLADNÍHO (českého) termu.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$term = get_queried_object();
get_header();

/* jazyk stránky + lokalizované UI texty šablony */
$lang = function_exists( 'pll_current_language' ) ? ( pll_current_language() ?: 'cs' ) : 'cs';
$li   = $lang === 'en' ? 1 : ( $lang === 'de' ? 2 : 0 );
$T = array(
	'back'     => array( '← Zpět na pokoje', '← Back to rooms', '← Zurück zu den Zimmern' ),
	'velikost' => array( 'velikost', 'size', 'Größe' ),
	'osob'     => array( 'osob', 'guests', 'Personen' ),
	'pokoju'   => array( 'pokojů v hotelu', 'rooms in the hotel', 'Zimmer im Hotel' ),
	'smoke'    => array( 'Kouření ve všech vnitřních prostorách zakázáno.', 'Smoking is prohibited in all indoor areas.', 'Rauchen ist in allen Innenräumen verboten.' ),
	'rezervovat' => array( 'Rezervovat pobyt', 'Book your stay', 'Aufenthalt buchen' ),
	'dostupnost' => array( 'Zkontrolovat dostupnost', 'Check availability', 'Verfügbarkeit prüfen' ),
	'vybaveni' => array( 'Vybavení pokoje', 'Room amenities', 'Zimmerausstattung' ),
	'koupelna' => array( 'V soukromé koupelně', 'In your private bathroom', 'Im eigenen Badezimmer' ),
	'srovnani' => array( 'Srovnání kategorií', 'Category comparison', 'Kategorienvergleich' ),
	'tabulka'  => array( 'Srovnávací tabulka pokojů', 'Room comparison table', 'Zimmer-Vergleichstabelle' ),
	'zvyraz'   => array( '// Zvýrazněný sloupec = ', '// Highlighted column = ', '// Hervorgehobene Spalte = ' ),
	'galerie'  => array( 'Fotogalerie', 'Photo gallery', 'Fotogalerie' ),
	'gal_suf'  => array( ' — galerie', ' — gallery', ' — Galerie' ),
	'rez_kick' => array( 'Rezervace', 'Booking', 'Buchung' ),
	'u_okruhu' => array( ' u Masarykova okruhu', ' at the Masaryk Circuit', ' am Masaryk-Ring' ),
	'ostatni'  => array( 'Ostatní pokoje', 'Other rooms', 'Weitere Zimmer' ),
);
$t = function ( $k ) use ( $T, $li ) { return $T[ $k ][ $li ]; };

/* data pokoje z pluginu (klíč = slug bez -en/-de) */
$key  = function_exists( 'garry_pok_key_from_term' ) ? garry_pok_key_from_term( $term ) : $term->slug;
$room = function_exists( 'garry_pokoje_room' ) ? garry_pokoje_room( $key ) : null;
$suf  = array( 'cz', 'en', 'de' )[ $li ];
$rf   = function ( $base ) use ( $room, $suf ) {
	if ( ! $room ) return '';
	$v = $room[ $base . '_' . $suf ] ?? '';
	return $v !== '' ? $v : ( $room[ $base . '_cz' ] ?? '' );
};

/* základní (CZ) term kvůli fotkám a fallbacku meta */
$base_term = $term;
if ( $key !== $term->slug ) {
	$bt = get_term_by( 'slug', $key, 'grid_room_cat' );
	if ( $bt && ! is_wp_error( $bt ) ) $base_term = $bt;
}

$nazev  = $rf( 'nazev' ) ?: $term->name;
$kod    = ( $suf !== 'cz' && $room && ! empty( $room[ 'kod_' . $suf ] ) ) ? $room[ 'kod_' . $suf ] : ( $room['kod_cz'] ?? grid_term_field( $base_term->term_id, 'kod', 'Ubytování' ) );
$popis  = $rf( 'popis' ) ?: grid_term_field( $base_term->term_id, 'popis', '' );
$velikost = $room['velikost'] ?? grid_term_field( $base_term->term_id, 'velikost', '' );
$kapacita = $room['kapacita'] ?? grid_term_field( $base_term->term_id, 'kapacita', '' );
$pocet    = $room['pocet'] ?? grid_term_field( $base_term->term_id, 'pocet', '' );
$postel   = $rf( 'postel' ) ?: grid_term_field( $base_term->term_id, 'postel', '' );
$koupelna = array_filter( array_map( 'trim', explode( '|', (string) ( $rf( 'koupelna' ) ?: grid_term_field( $base_term->term_id, 'koupelna', '' ) ) ) ) );
$zarizeni = array_filter( array_map( 'trim', explode( '|', (string) ( $rf( 'zarizeni' ) ?: grid_term_field( $base_term->term_id, 'zarizeni', '' ) ) ) ) );

/* štítky (chips) z pluginu podle jazyka, fallback term meta */
$chips = array();
if ( $room && function_exists( 'garry_pok_labels_map' ) ) {
	$lmap = garry_pok_labels_map();
	foreach ( (array) ( $room['stitky'] ?? array() ) as $lk ) {
		if ( empty( $lmap[ $lk ] ) ) continue;
		$lv = ( $suf !== 'cz' && $lmap[ $lk ][ $suf ] !== '' ) ? $lmap[ $lk ][ $suf ] : $lmap[ $lk ]['cz'];
		if ( $lv !== '' ) $chips[] = $lv;
	}
}
if ( ! $chips ) $chips = array_filter( array_map( 'trim', explode( '|', (string) grid_term_field( $base_term->term_id, 'stitky', '' ) ) ) );

$rez = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );
$home_pokoje = function_exists( 'pll_home_url' ) ? pll_home_url( $lang ) . '#pokoje' : home_url( '/#pokoje' );

/* obrázky: náhled + galerie ze ZÁKLADNÍHO termu */
$imgs = array();
$nah = grid_term_field( $base_term->term_id, 'nahled', '' );
if ( is_array( $nah ) ) $nah = isset( $nah['url'] ) ? $nah['url'] : '';
if ( $nah ) $imgs[] = $nah;
$gal = grid_term_field( $base_term->term_id, 'galerie', null );
if ( is_array( $gal ) ) {
	foreach ( $gal as $g ) {
		$u = is_array( $g ) ? ( ! empty( $g['sizes']['large'] ) ? $g['sizes']['large'] : ( ! empty( $g['url'] ) ? $g['url'] : '' ) ) : ( is_numeric( $g ) ? wp_get_attachment_image_url( $g, 'large' ) : '' );
		if ( $u ) $imgs[] = $u;
	}
}
if ( ! $imgs && $room && ! empty( $room['img'] ) ) $imgs[] = $room['img'];
$hero_img = ! empty( $imgs ) ? $imgs[0] : '';
?>

<section class="sec sec-dark carbon sec-pad roomdetail" style="padding-top:clamp(120px,16vh,180px)">
  <div class="wrap">
    <a class="rd-back" href="<?php echo esc_url( $home_pokoje ); ?>"><?php echo esc_html( $t( 'back' ) ); ?></a>
    <span class="kicker"><?php echo esc_html( $kod ); ?></span>
    <h1 style="font-size:clamp(2.4rem,6vw,4.6rem);margin:14px 0 18px"><?php echo esc_html( $nazev ); ?></h1>
    <div class="rd-grid">
      <div class="rd-main">
        <?php if ( $velikost !== '' || $kapacita !== '' || $postel !== '' || $pocet !== '' ) : ?>
        <div class="rd-facts">
          <?php if ( $velikost !== '' ) : ?><span><b><?php echo esc_html( $velikost ); ?>&nbsp;m²</b> <?php echo esc_html( $t( 'velikost' ) ); ?></span><?php endif; ?>
          <?php if ( $kapacita !== '' ) : ?><span><b><?php echo esc_html( $kapacita ); ?></b> <?php echo esc_html( $t( 'osob' ) ); ?></span><?php endif; ?>
          <?php if ( $postel !== '' ) : ?><span><?php echo esc_html( $postel ); ?></span><?php endif; ?>
          <?php if ( $pocet !== '' ) : ?><span><b><?php echo esc_html( $pocet ); ?></b> <?php echo esc_html( $t( 'pokoju' ) ); ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ( $chips ) : ?><div class="r-feat rd-chips"><?php foreach ( $chips as $c ) : ?><span><?php echo esc_html( $c ); ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="rd-popis"><?php echo $popis ? wp_kses_post( $popis ) : '<p>' . esc_html( $term->description ) . '</p>'; ?></div>
        <?php if ( $koupelna ) : ?><p class="rd-smoke"><?php echo esc_html( $t( 'smoke' ) ); ?></p><?php endif; ?>
        <div class="rd-actions"><a class="btn" href="<?php echo esc_url( $rez ); ?>"><?php echo esc_html( $t( 'rezervovat' ) ); ?></a> <a class="btn btn-ghost" href="<?php echo esc_url( $rez ); ?>"><?php echo esc_html( $t( 'dostupnost' ) ); ?></a></div>
      </div>
      <aside class="rd-aside">
        <?php if ( $hero_img ) : ?><img class="rd-photo" src="<?php echo esc_url( $hero_img ); ?>" alt="<?php echo esc_attr( $nazev ); ?>" loading="lazy"><?php endif; ?>
        <?php if ( $zarizeni ) : ?><div class="rd-col"><h3><?php echo esc_html( $t( 'vybaveni' ) ); ?></h3><ul class="check-list"><?php foreach ( $zarizeni as $c ) echo '<li>' . esc_html( $c ) . '</li>'; ?></ul></div><?php endif; ?>
        <?php if ( $koupelna ) : ?><div class="rd-col"><h3><?php echo esc_html( $t( 'koupelna' ) ); ?></h3><ul class="check-list"><?php foreach ( $koupelna as $c ) echo '<li>' . esc_html( $c ) . '</li>'; ?></ul></div><?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<section class="sec sec-light sec-pad">
  <div class="wrap">
    <span class="kicker"><?php echo esc_html( $t( 'srovnani' ) ); ?></span>
    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px"><?php echo esc_html( $t( 'tabulka' ) ); ?></h2>
    <?php echo grid_room_compare_table( $key ); ?>
    <p style="font-family:var(--f-mono);font-size:.72rem;color:var(--muted);margin-top:16px"><?php echo esc_html( $t( 'zvyraz' ) . $nazev ); ?></p>
  </div>
</section>

<?php if ( ! empty( $imgs ) ) : ?>
<section class="sec sec-dark carbon sec-pad">
  <div class="wrap">
    <span class="kicker"><?php echo esc_html( $t( 'galerie' ) ); ?></span>
    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px"><?php echo esc_html( $nazev . $t( 'gal_suf' ) ); ?></h2>
    <div class="roomgallery">
      <?php foreach ( $imgs as $u ) : ?>
      <a href="<?php echo esc_url( $u ); ?>" class="rg-item" data-lightbox="room"><img src="<?php echo esc_url( $u ); ?>" alt="<?php echo esc_attr( $nazev ); ?>" loading="lazy"></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec sec-light final" style="background:var(--paper)">
  <div class="wrap" style="text-align:center">
    <span class="kicker" style="justify-content:center;display:inline-flex"><?php echo esc_html( $t( 'rez_kick' ) ); ?></span>
    <h2 style="font-size:clamp(2rem,5vw,4rem);margin:14px 0 18px;color:var(--ink)"><?php echo esc_html( $nazev . $t( 'u_okruhu' ) ); ?></h2>
    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( $rez ); ?>"><?php echo esc_html( $t( 'rezervovat' ) ); ?></a><a class="btn btn-ghost" href="<?php echo esc_url( $home_pokoje ); ?>"><?php echo esc_html( $t( 'ostatni' ) ); ?></a></div>
  </div>
</section>

<?php get_footer();
