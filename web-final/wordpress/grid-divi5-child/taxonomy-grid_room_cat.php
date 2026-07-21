<?php
/**
 * Detail kategorie pokoje (typ pokoje).
 * Plný popis + srovnávací tabulka + fotogalerie. Čte ACF pole termu.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$term = get_queried_object();
get_header();

$kod    = grid_term_field( $term->term_id, 'kod', 'Ubytování' );
$popis  = grid_term_field( $term->term_id, 'popis', '' );
$stitky = grid_term_field( $term->term_id, 'stitky', '' );
$chips  = array_filter( array_map( 'trim', explode( '|', (string) $stitky ) ) );
$velikost = grid_term_field( $term->term_id, 'velikost', '' );
$kapacita = grid_term_field( $term->term_id, 'kapacita', '' );
$pocet    = grid_term_field( $term->term_id, 'pocet', '' );
$postel   = grid_term_field( $term->term_id, 'postel', '' );
$koupelna = array_filter( array_map( 'trim', explode( '|', (string) grid_term_field( $term->term_id, 'koupelna', '' ) ) ) );
$zarizeni = array_filter( array_map( 'trim', explode( '|', (string) grid_term_field( $term->term_id, 'zarizeni', '' ) ) ) );
$rez    = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );

/* obrázky: náhled + galerie */
$imgs = array();
$nah = grid_term_field( $term->term_id, 'nahled', '' );
if ( is_array( $nah ) ) $nah = isset( $nah['url'] ) ? $nah['url'] : '';
if ( $nah ) $imgs[] = $nah;
$gal = grid_term_field( $term->term_id, 'galerie', null );
if ( is_array( $gal ) ) {
	foreach ( $gal as $g ) {
		$u = is_array( $g ) ? ( ! empty( $g['sizes']['large'] ) ? $g['sizes']['large'] : ( ! empty( $g['url'] ) ? $g['url'] : '' ) ) : ( is_numeric( $g ) ? wp_get_attachment_image_url( $g, 'large' ) : '' );
		if ( $u ) $imgs[] = $u;
	}
}
$hero_img = ! empty( $imgs ) ? $imgs[0] : '';
?>

<section class="sec sec-dark carbon sec-pad roomdetail" style="padding-top:clamp(120px,16vh,180px)">
  <div class="wrap">
    <a class="rd-back" href="<?php echo esc_url( home_url( '/#pokoje' ) ); ?>">← Zpět na pokoje</a>
    <span class="kicker"><?php echo esc_html( $kod ); ?></span>
    <h1 style="font-size:clamp(2.4rem,6vw,4.6rem);margin:14px 0 18px"><?php echo esc_html( $term->name ); ?></h1>
    <div class="rd-grid">
      <div class="rd-main">
        <?php if ( $velikost !== '' || $kapacita !== '' || $postel !== '' || $pocet !== '' ) : ?>
        <div class="rd-facts">
          <?php if ( $velikost !== '' ) : ?><span><b><?php echo esc_html( $velikost ); ?>&nbsp;m²</b> velikost</span><?php endif; ?>
          <?php if ( $kapacita !== '' ) : ?><span><b><?php echo esc_html( $kapacita ); ?></b> osob</span><?php endif; ?>
          <?php if ( $postel !== '' ) : ?><span><?php echo esc_html( $postel ); ?></span><?php endif; ?>
          <?php if ( $pocet !== '' ) : ?><span><b><?php echo esc_html( $pocet ); ?></b> pokojů v hotelu</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ( $chips ) : ?><div class="r-feat rd-chips"><?php foreach ( $chips as $c ) : ?><span><?php echo esc_html( $c ); ?></span><?php endforeach; ?></div><?php endif; ?>
        <div class="rd-popis"><?php echo $popis ? wp_kses_post( $popis ) : '<p>' . esc_html( $term->description ) . '</p>'; ?></div>
        <?php if ( $koupelna ) : ?><p class="rd-smoke">Kouření ve všech vnitřních prostorách zakázáno.</p><?php endif; ?>
        <div class="rd-actions"><a class="btn" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a> <a class="btn btn-ghost" href="<?php echo esc_url( grid_rezervace_url() ); ?>">Zkontrolovat dostupnost</a></div>
      </div>
      <aside class="rd-aside">
        <?php if ( $hero_img ) : ?><img class="rd-photo" src="<?php echo esc_url( $hero_img ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy"><?php endif; ?>
        <?php if ( $zarizeni ) : ?><h3>Vybavení pokoje</h3><ul class="check-list"><?php foreach ( $zarizeni as $c ) echo '<li>' . esc_html( $c ) . '</li>'; ?></ul><?php endif; ?>
        <?php if ( $koupelna ) : ?><h3>V soukromé koupelně</h3><ul class="check-list"><?php foreach ( $koupelna as $c ) echo '<li>' . esc_html( $c ) . '</li>'; ?></ul><?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<section class="sec sec-light sec-pad">
  <div class="wrap">
    <span class="kicker">Srovnání kategorií</span>
    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px">Srovnávací tabulka pokojů</h2>
    <?php echo grid_room_compare_table( $term->name ); ?>
    <p style="font-family:var(--f-mono);font-size:.72rem;color:var(--muted);margin-top:16px">// Zvýrazněný sloupec = <?php echo esc_html( $term->name ); ?></p>
  </div>
</section>

<?php if ( ! empty( $imgs ) ) : ?>
<section class="sec sec-dark carbon sec-pad">
  <div class="wrap">
    <span class="kicker">Fotogalerie</span>
    <h2 style="font-size:clamp(2rem,4vw,3.4rem);margin:14px 0 24px"><?php echo esc_html( $term->name ); ?> — galerie</h2>
    <div class="roomgallery">
      <?php foreach ( $imgs as $u ) : ?>
      <a href="<?php echo esc_url( $u ); ?>" class="rg-item" data-lightbox="room"><img src="<?php echo esc_url( $u ); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy"></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="sec sec-light final" style="background:var(--paper)">
  <div class="wrap" style="text-align:center">
    <span class="kicker" style="justify-content:center;display:inline-flex">Rezervace</span>
    <h2 style="font-size:clamp(2rem,5vw,4rem);margin:14px 0 18px;color:var(--ink)"><?php echo esc_html( $term->name ); ?> u Masarykova okruhu</h2>
    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/#pokoje' ) ); ?>">Ostatní pokoje</a></div>
  </div>
</section>

<?php get_footer();
