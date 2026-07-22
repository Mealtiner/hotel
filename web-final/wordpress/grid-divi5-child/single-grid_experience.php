<?php
/**
 * Detail zážitku (single grid_experience) — perex, parametry, plný popis,
 * náhledová foto, externí CTA (Autodrom / Polygon), galerie a další zážitky.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$rez = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );

while ( have_posts() ) : the_post();
	$id    = get_the_ID();
	$num   = grid_pf( $id, 'num', 'Zážitek' );
	$def   = function_exists( 'grid_exp_defaults' ) ? grid_exp_defaults( $num ) : array( 'perex'=>'', 'parametry'=>'', 'odkaz'=>'', 'odkaz_text'=>'' );
	$txt   = grid_pf( $id, 'text', '' );
	$perex = grid_pf( $id, 'perex', $def['perex'] );
	$popis = grid_pf( $id, 'popis', '' );
	$paramRaw = grid_pf( $id, 'parametry', $def['parametry'] );
	$odkaz = grid_pf( $id, 'odkaz' ); if ( ! $odkaz ) $odkaz = $def['odkaz'];
	$odkaz_text = grid_pf( $id, 'odkaz_text', $def['odkaz_text'] ? $def['odkaz_text'] : 'Web partnera →' );
	$params = array();
	foreach ( array_filter( array_map( 'trim', explode( '|', (string) $paramRaw ) ) ) as $p ) {
		$kv = array_map( 'trim', explode( '=', $p, 2 ) );
		if ( $kv[0] !== '' ) $params[] = array( $kv[0], isset( $kv[1] ) ? $kv[1] : '' );
	}
	/* galerie */
	$imgs = array();
	$gal  = function_exists( 'get_field' ) ? get_field( 'galerie', $id ) : null;
	if ( is_array( $gal ) ) foreach ( $gal as $g ) {
		$u = is_array( $g ) ? ( ! empty( $g['sizes']['large'] ) ? $g['sizes']['large'] : ( ! empty( $g['url'] ) ? $g['url'] : '' ) ) : ( is_numeric( $g ) ? wp_get_attachment_image_url( $g, 'large' ) : '' );
		if ( $u ) $imgs[] = $u;
	}
	$hero_img = ! empty( $imgs ) ? $imgs[0] : '';
	?>
	<section class="sec sec-dark carbon sec-pad roomdetail" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <a class="rd-back" href="<?php echo esc_url( get_post_type_archive_link( 'grid_experience' ) ); ?>">← Zpět na zážitky</a>
	    <span class="kicker"><?php echo esc_html( $num ); ?></span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 18px"><?php the_title(); ?></h1>
	    <div class="rd-grid<?php echo $hero_img ? '' : ' rd-grid--single'; ?>">
	      <div class="rd-main">
	        <?php if ( $perex ) : ?><p class="lead" style="color:var(--fg);font-size:1.12rem;max-width:60ch;margin-bottom:18px"><?php echo esc_html( $perex ); ?></p><?php endif; ?>
	        <?php if ( $params ) : ?>
	        <div class="rd-facts">
	          <?php foreach ( $params as $pp ) : ?><span><?php if ( $pp[1] !== '' ) : ?><b><?php echo esc_html( $pp[1] ); ?></b> <?php echo esc_html( $pp[0] ); ?><?php else : echo esc_html( $pp[0] ); endif; ?></span><?php endforeach; ?>
	        </div>
	        <?php endif; ?>
	        <div class="rd-popis"><?php echo $popis ? wp_kses_post( $popis ) : '<p>' . esc_html( $txt ) . '</p>'; ?></div>
	        <div class="rd-actions">
	          <a class="btn" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a>
	          <?php if ( $odkaz ) : ?><a class="btn btn-ghost" href="<?php echo esc_url( $odkaz ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $odkaz_text ); ?></a><?php endif; ?>
	          <a class="btn btn-ghost" href="<?php echo esc_url( grid_nav_url( '#kontakt' ) ); ?>">Kontakt</a>
	        </div>
	      </div>
	      <?php if ( $hero_img ) : ?>
	      <aside class="rd-aside">
	        <img class="rd-photo" src="<?php echo esc_url( $hero_img ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
	      </aside>
	      <?php endif; ?>
	    </div>
	  </div>
	</section>

	<?php if ( count( $imgs ) > 1 ) : ?>
	<section class="sec sec-light sec-pad">
	  <div class="wrap">
	    <span class="kicker">Fotogalerie</span>
	    <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin:14px 0 22px"><?php the_title(); ?></h2>
	    <div class="roomgallery">
	      <?php foreach ( $imgs as $u ) : ?><a href="<?php echo esc_url( $u ); ?>" class="rg-item" data-lightbox="exp"><img src="<?php echo esc_url( $u ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy"></a><?php endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php endif; ?>

	<?php
	$others = new WP_Query( array( 'post_type' => 'grid_experience', 'posts_per_page' => 3, 'post__not_in' => array( $id ), 'orderby' => 'menu_order title', 'order' => 'ASC', 'post_status' => 'publish' ) );
	if ( $others->have_posts() ) : ?>
	<section class="sec sec-dark carbon sec-pad">
	  <div class="wrap">
	    <span class="kicker">Další zážitky u okruhu</span>
	    <h2 style="font-size:clamp(1.8rem,4vw,3rem);margin:14px 0 22px">Co dál vyzkoušet</h2>
	    <div class="exp reveal in" style="grid-template-columns:repeat(3,1fr)">
	      <?php while ( $others->have_posts() ) : $others->the_post(); $oid = get_the_ID(); ?>
	        <a class="exp-item" href="<?php echo esc_url( get_permalink( $oid ) ); ?>">
	          <span class="x-num"><?php echo esc_html( grid_pf( $oid, 'num' ) ); ?></span>
	          <h3><?php echo wp_kses_post( get_the_title() ); ?></h3>
	          <p><?php echo esc_html( grid_pf( $oid, 'text' ) ); ?></p>
	          <span class="x-link">Zjistit více →</span>
	        </a>
	      <?php endwhile; wp_reset_postdata(); ?>
	    </div>
	  </div>
	</section>
	<?php endif; ?>

	<section class="sec sec-light final" style="background:var(--paper)">
	  <div class="wrap" style="text-align:center">
	    <span class="kicker" style="justify-content:center;display:inline-flex">Rezervace</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 18px;color:var(--ink)">Spojte pobyt se zážitkem u okruhu</h2>
	    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( grid_nav_url( '#kontakt' ) ); ?>">Kontaktovat recepci</a></div>
	  </div>
	</section>
	<?php
endwhile;
get_footer();
