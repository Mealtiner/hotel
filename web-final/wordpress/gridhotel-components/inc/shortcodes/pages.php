<?php
/**
 * GRID Hotel Components — samostatné podstránky: O hotelu, Kariéra, Video,
 * Galerie (GRID-SUITE-02 §4).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* [grid_onas] — O hotelu. */
function gridc_sc_onas() {
	$img = gridhotel_get_option( 'pribeh_obrazek', '' );
	$img = $img ? ( is_array( $img ) ? $img['url'] : $img ) : gridc_fallback_image( 'hotel-okruh-leto.jpg' );
	$vpop = gridhotel_get_option( 'video_popis', 'Sledujte, jak GRID Hotel rostl přímo v srdci Autodromu Brno — od základů až po dnešní ****hotel u Masarykova okruhu.' );
	$highlights = array(
		array( 'Ubytování ****', 'Vyberte si z 64 komfortních pokojů a apartmá s klimatizací, výhledem na trať i terasou.', array( 'ubytovani', 'pokoje' ), 'Pokoje &amp; apartmá' ),
		array( 'Gastronomie', 'Snídaňový bufet, denní menu i večerní à la carte — hotelová restaurace, PADDOCK i GRID Club.', array( 'gastronomie', 'gastro' ), 'Gastronomie' ),
		array( 'Zážitky u okruhu', 'Simulátor Masarykova okruhu, motokáry, škola smyku Polygonu i dárkové poukazy.', array( 'zazitky-u-okruhu', 'zazitky', 'aktivity' ), 'Zážitky' ),
		array( 'Firemní akce &amp; svatby', 'Konference, teambuildingy, večírky i svatby s cateringem na míru a doprovodným programem.', array( 'firemni-akce-svatby', 'firemni' ), 'Firemní akce' ),
		array( 'Doprava &amp; parkování', 'Pár minut z dálnice D1, parkoviště pro osobní auta i autobusy přímo u hotelu.', array( 'doprava' ), 'Jak se k nám dostanete' ),
		array( 'Sezóna 2026', 'MotoGP víkend, vytrvalostní závody i track days — buďte přímo u dění.', array( 'sezona-2026', 'sezona' ), 'Program sezóny' ),
	);
	ob_start(); ?>
	<section class="sec sec-dark carbon grid-component grid-component--onas" style="padding-top:clamp(120px,16vh,180px)">
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
	      <div style="margin-top:26px;display:flex;gap:14px;flex-wrap:wrap"><a class="btn" href="<?php echo esc_url( gridc_rezervace_url() ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/#pokoje' ) ); ?>">Pokoje</a></div>
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
	      <?php foreach ( $highlights as $h ) : $u = gridc_detail_url( $h[2] ); ?>
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
	    <div class="doprava-map" style="margin-top:24px"><?php echo gridc_sc_video_embed(); ?></div>
	  </div>
	</section>
	<section class="sec sec-light final" style="background:var(--paper)">
	  <div class="wrap" style="text-align:center">
	    <span class="kicker" style="justify-content:center;display:inline-flex">Rezervace</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 18px;color:var(--ink)">Přespěte uprostřed Masarykova okruhu</h2>
	    <div class="fc-actions" style="justify-content:center"><a class="btn" href="<?php echo esc_url( gridc_rezervace_url() ); ?>">Rezervovat pobyt</a><a class="btn btn-ghost" href="<?php echo esc_url( gridc_nav_url( '#kontakt' ) ); ?>">Kontaktovat recepci</a></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_onas', 'gridc_sc_onas' );

/* [grid_kariera] — statická stránka "aktuálně nikoho nehledáme". */
function gridc_sc_kariera() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-component grid-component--kariera" style="padding-top:clamp(120px,16vh,180px)">
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
gridc_register_shortcode( 'grid_kariera', 'gridc_sc_kariera' );

/* [grid_kariera_pozice] — dynamický výpis pracovních pozic, přes Core API. */
function gridc_sc_kariera_pozice() {
	$lang = gridc_lang();
	$li   = gridc_lang_index();
	$T    = array(
		'empty_h' => array( 'V tuto chvíli nikoho nehledáme 🙁', 'We are not hiring at the moment 🙁', 'Derzeit suchen wir niemanden 🙁' ),
		'empty_p' => array(
			'Zkuste to prosím později — nebo nám rovnou napište, rádi si vás zařadíme do evidence.',
			'Please check back later — or drop us a line and we will gladly keep your CV on file.',
			'Schauen Sie später wieder vorbei — oder schreiben Sie uns direkt, wir nehmen Sie gern in unsere Kartei auf.',
		),
		'uvazek' => array( 'Úvazek', 'Contract', 'Arbeitsverhältnis' ),
		'misto'  => array( 'Místo', 'Location', 'Ort' ),
		'mzda'   => array( 'Mzda', 'Salary', 'Gehalt' ),
		'apply'  => array( 'Chci se přihlásit →', 'Apply now →', 'Jetzt bewerben →' ),
	);
	$jobs = function_exists( 'gridhotel_get_careers' ) ? gridhotel_get_careers( array( 'locale' => $lang ) ) : array();

	ob_start();
	if ( ! $jobs ) :
		?>
		<div class="job-empty grid-component grid-component--kariera-pozice" style="margin:26px 0;padding:26px;border:1px solid var(--line-c);border-radius:2px;background:var(--card)">
		  <p style="font-family:var(--f-head);text-transform:uppercase;font-size:1.4rem;color:var(--ink);margin-bottom:6px"><?php echo esc_html( $T['empty_h'][ $li ] ); ?></p>
		  <p style="color:var(--muted)"><?php echo esc_html( $T['empty_p'][ $li ] ); ?></p>
		</div>
		<?php
	else :
		?>
		<div class="job-list grid-component grid-component--kariera-pozice">
		<?php foreach ( $jobs as $j ) :
			$meta = array();
			if ( '' !== $j['contract'] ) { $meta[] = '<span><b>' . esc_html( $T['uvazek'][ $li ] ) . ':</b> ' . esc_html( $j['contract'] ) . '</span>'; }
			if ( '' !== $j['location'] ) { $meta[] = '<span><b>' . esc_html( $T['misto'][ $li ] ) . ':</b> ' . esc_html( $j['location'] ) . '</span>'; }
			if ( '' !== $j['salary'] )   { $meta[] = '<span><b>' . esc_html( $T['mzda'][ $li ] ) . ':</b> ' . esc_html( $j['salary'] ) . '</span>'; }
			?>
			<div class="job-card">
			  <h3><?php echo esc_html( $j['title'] ); ?></h3>
			  <?php if ( $meta ) : ?><p class="job-meta"><?php echo implode( ' · ', $meta ); ?></p><?php endif; ?>
			  <?php if ( '' !== $j['description'] ) : ?><div class="job-popis"><?php echo wp_kses_post( wpautop( $j['description'] ) ); ?></div><?php endif; ?>
			  <a class="btn btn-ghost" href="mailto:<?php echo esc_attr( $j['email'] ); ?>?subject=<?php echo rawurlencode( 'Kariéra GRID HOTEL — ' . $j['title'] ); ?>"><?php echo esc_html( $T['apply'][ $li ] ); ?></a>
			</div>
		<?php endforeach; ?>
		</div>
		<?php
	endif;
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_kariera_pozice', 'gridc_sc_kariera_pozice' );

/* [grid_video] — samostatná stránka "časosběr" videa stavby hotelu. */
function gridc_sc_video() {
	$pop = gridhotel_get_option( 'video_popis', 'Časosběrné video ze stavby GRID Hotelu přímo v areálu Masarykova okruhu.' );
	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad grid-component grid-component--video" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:960px">
	    <span class="kicker">Časosběr</span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 12px">Video stavby hotelu</h1>
	    <p style="color:var(--muted);max-width:60ch"><?php echo esc_html( $pop ); ?></p>
	    <div class="doprava-map" style="margin-top:26px"><?php echo gridc_sc_video_embed(); ?></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_video', 'gridc_sc_video' );

/* [grid_galerie] — filtrovatelná fotogalerie, přes Core API (gridhotel_get_gallery_blocks). */
function gridc_sc_galerie( $atts = array() ) {
	$a = shortcode_atts( array( 'kicker' => 'Galerie', 'nadpis' => 'Fotogalerie GRID HOTEL', 'vse' => 'Vše' ), $atts );
	$blocks = function_exists( 'gridhotel_get_gallery_blocks' ) ? gridhotel_get_gallery_blocks() : array();

	ob_start(); ?>
	<section class="sec sec-dark carbon sec-pad grid-component grid-component--galerie" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap">
	    <span class="kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
	    <h1 style="font-size:clamp(2.4rem,6vw,4.4rem);margin:14px 0 20px"><?php echo esc_html( $a['nadpis'] ); ?></h1>
	    <?php if ( empty( $blocks ) ) : ?>
	      <p style="color:var(--muted);font-family:var(--f-mono);font-size:.85rem">// Přidej kategorie a fotky v GRID Nastavení → Obecné nastavení → Galerie.</p>
	    <?php else :
	      $cats = array(); $items = array();
	      foreach ( $blocks as $ci => $block ) {
	        $slug = 'c' . $ci;
	        $cats[ $slug ] = $block['name'];
	        foreach ( $block['images'] as $img ) {
	          $items[] = array( 'cat' => $slug, 'full' => $img['full'], 'thumb' => $img['thumb'], 'alt' => $block['name'] );
	        }
	      }
	      if ( empty( $items ) ) : ?>
	        <p style="color:var(--muted)">Galerie je zatím prázdná — nahraj fotky v GRID Nastavení → Obecné nastavení → Galerie.</p>
	      <?php else : ?>
	        <div class="galerie-filter">
	          <button class="gal-fbtn active" data-filter="all"><?php echo esc_html( $a['vse'] ); ?></button>
	          <?php foreach ( $cats as $slug => $nazev ) : ?><button class="gal-fbtn" data-filter="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nazev ); ?></button><?php endforeach; ?>
	        </div>
	        <div class="galerie-grid">
	          <?php foreach ( $items as $it ) : ?>
	          <a class="gal-item" data-cat="<?php echo esc_attr( $it['cat'] ); ?>" href="<?php echo esc_url( $it['full'] ); ?>" data-lightbox="galerie"><img src="<?php echo esc_url( $it['thumb'] ); ?>" alt="<?php echo esc_attr( $it['alt'] ); ?>" loading="lazy"></a>
	          <?php endforeach; ?>
	        </div>
	      <?php endif;
	    endif; ?>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_galerie', 'gridc_sc_galerie' );
