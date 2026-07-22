<?php
/**
 * Archiv zážitků (/zazitky/) — stylované karty s prokliky na detail.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
$rez = function_exists( 'grid_rezervace_url' ) ? grid_rezervace_url() : home_url( '/#booking' );
?>
<section class="sec sec-dark carbon sec-pad" style="padding-top:clamp(120px,16vh,180px)">
  <div class="wrap">
    <span class="kicker">Zážitky u okruhu</span>
    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Motorsport zážitky pár metrů od hotelu</h1>
    <p style="max-width:64ch;color:var(--muted)">Užijte si závodní atmosféru Autodromu Brno. Při pobytu můžete usednout do silné motokáry nebo na obratnou pitbike, projet si simulátor Masarykova okruhu nebo absolvovat kurzy bezpečné jízdy v Polygonu Brno.</p>
  </div>
  <div class="exp reveal in" style="margin-top:34px">
    <?php
    $q = new WP_Query( array( 'post_type' => 'grid_experience', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
    if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post();
        $id = get_the_ID();
        $num = grid_pf( $id, 'num' ); $txt = grid_pf( $id, 'text' ); $cta = grid_pf( $id, 'cta', 'Zjistit více →' );
        $odkaz = grid_pf( $id, 'odkaz' ); $url = $odkaz ? $odkaz : get_permalink( $id );
    ?>
      <a class="exp-item" href="<?php echo esc_url( $url ); ?>">
        <span class="x-num"><?php echo esc_html( $num ); ?></span>
        <h3><?php echo wp_kses_post( get_the_title() ); ?></h3>
        <p><?php echo esc_html( $txt ); ?></p>
        <span class="x-link"><?php echo esc_html( $cta ); ?></span>
      </a>
    <?php endwhile; wp_reset_postdata(); else : ?>
      <div class="wrap"><p style="color:var(--muted)">Zatím tu nejsou žádné zážitky. Přidejte je v <strong>GRID: Zážitky</strong>.</p></div>
    <?php endif; ?>
  </div>
</section>

<section class="sec sec-light final" style="background:var(--paper)">
  <div class="wrap" style="text-align:center">
    <span class="kicker" style="justify-content:center;display:inline-flex">Rezervace</span>
    <h2 style="font-size:clamp(2rem,5vw,4rem);margin:14px 0 18px;color:var(--ink)">Zážitek jako dárek i doplněk pobytu</h2>
    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( $rez ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/#kontakt' ) ); ?>">Kontaktovat recepci</a></div>
  </div>
</section>
<?php get_footer();
