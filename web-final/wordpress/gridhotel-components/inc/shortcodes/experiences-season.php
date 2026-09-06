<?php
/**
 * GRID Hotel Components — zážitky a sezónní nabídka (GRID-SUITE-02 §4).
 * Sezónní akce a poukazy jsou skládané přes ModuleRenderer (feature
 * season_events) — vlastní je plugin Sezóna & čekací list, ne Components.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* [grid_zazitky] — T4 sekce: doporučené zážitky, jinak prvních 6 dle pořadí. */
function gridc_sc_zazitky() {
	$defaults = array(
		array( 'num' => '4.1', 'title' => 'Simulátor okruhu', 'text' => 'Profesionální dynamický simulátor s reálnou geometrií Masarykova okruhu. Ideální rozjížďka před ostrým výjezdem na trať — pro začátečníky i závodníky.', 'cta' => 'Vyzkoušet →' ),
		array( 'num' => '4.2', 'title' => 'Motokáry &amp; pitbike', 'text' => 'Usedněte do silné motokáry nebo na obratnou pitbike a zajezděte si pár metrů od velkého okruhu na speciální motokárové dráze. Měření časů a souboj o nejlepší kolo.', 'cta' => 'Rezervovat →' ),
		array( 'num' => '4.3', 'title' => 'Škola smyku — Polygon Brno', 'text' => 'Moderní tréninkové centrum bezpečné jízdy. Úrovně Compact, Intensiv, Intensiv+, Advanced a Dynamic — od základů po pokročilou techniku ovládání vozu.', 'cta' => 'Vybrat úroveň →' ),
		array( 'num' => '4.4', 'title' => 'Drift &amp; Gangster kurz', 'text' => 'Zážitkové kurzy Polygonu Brno pro ty, kdo chtějí víc adrenalinu — řízený drift a speciální program za volantem.', 'cta' => 'Termíny →' ),
		array( 'num' => '4.5', 'title' => 'Odpočet trestných bodů', 'text' => 'Akreditovaný kurz bezpečné jízdy pro odečet trestných bodů. Vhodné i jako firemní školení řidičů na míru.', 'cta' => 'Více →' ),
		array( 'num' => '4.6', 'title' => 'Dárkové poukazy', 'text' => 'Zážitek u okruhu jako dárek — pobyt, simulátor, motokáry nebo kurz Polygonu v libovolné hodnotě. Pošleme i elektronicky.', 'cta' => 'Koupit poukaz →' ),
	);

	$items = $defaults;
	if ( function_exists( 'gridhotel_get_experiences' ) ) {
		$all = gridhotel_get_experiences( array( 'limit' => -1 ) );
		if ( ! empty( $all ) ) {
			$featured = array_values( array_filter( $all, fn( $e ) => ! empty( $e['featured'] ) ) );
			$source   = ! empty( $featured ) ? $featured : $all;
			$source   = array_slice( $source, 0, 6 );
			$items    = array();
			foreach ( $source as $e ) {
				$items[] = array( 'num' => $e['number'], 'title' => $e['title'], 'text' => $e['text'], 'cta' => $e['cta'], 'url' => $e['link'] ?: $e['url'] );
			}
		}
	}

	ob_start(); ?>
	<section id="zazitky" class="sec <?php echo is_front_page() ? 'sec-dark carbon' : 'sec-light'; ?> sec-pad grid-component grid-component--zazitky">
	  <span class="sec-tag">T4</span>
	  <div class="wrap"><div class="reveal" style="margin-bottom:42px"><span class="kicker">T4 · Zážitky u okruhu</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px;max-width:18ch">Užijte si závodní atmosféru Autodromu Brno</h2><p style="max-width:60ch;margin-top:14px;color:var(--muted)">Při pobytu máte jedinečnou možnost usednout do silné motokáry nebo na obratnou pitbike a zajezdit si pár metrů od velkého okruhu na speciální dráze. Adrenalin začíná hned za dveřmi pokoje.</p></div></div>
	  <div class="exp reveal d1">
	    <?php foreach ( $items as $it ) :
	      $u   = gridc_row_val( $it, 'url' );
	      $tag = $u ? 'a' : 'div';
	      ?>
	    <<?php echo esc_html( $tag ); ?> class="exp-item"<?php if ( $u ) { echo ' href="' . esc_url( $u ) . '"'; } ?>><span class="x-num"><?php echo esc_html( gridc_row_val( $it, 'num' ) ); ?></span><h3><?php echo wp_kses_post( gridc_row_val( $it, 'title' ) ); ?></h3><p><?php echo esc_html( gridc_row_val( $it, 'text' ) ); ?></p><span class="x-link"><?php echo esc_html( gridc_row_val( $it, 'cta' ) ); ?></span></<?php echo esc_html( $tag ); ?>>
	    <?php endforeach; ?>
	  </div>
	  <?php $zmore = gridc_section_more( array( 'zazitky-u-okruhu', 'zazitky', 'aktivity' ), 'Všechny zážitky a poukazy' ); if ( $zmore ) { echo '<div class="wrap" style="margin-top:30px">' . $zmore . '</div>'; } ?>
	</section>
	<?php
	if ( ! is_front_page() ) {
		echo gridc_sc_poukazy();
	}
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_zazitky', 'gridc_sc_zazitky' );

/* [grid_season] — T6 sekce; homepage = teaser (5 akcí), podstránka = karty + čekací list. */
function gridc_sc_season() {
	ob_start();
	if ( is_front_page() ) : ?>
	<section id="sezona" class="sec sec-dark carbon sec-pad grid-component grid-component--season">
	  <span class="sec-tag">T6</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:46px"><span class="kicker">T6 · Sezóna · Čekací list</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Velké akce se plní rychle. Buďte na roštu první.</h2><p style="max-width:62ch;margin-top:14px;color:var(--muted)">O závodních víkendech je hotel uprostřed okruhu nejžádanějším místem v Brně. Vyberte akci, zkontrolujte dostupnost pokojů a rezervujte — nebo se zapište na čekací list. Jakmile se uvolní místnost pro vámi vybraný termín, ozveme se jako prvním.</p></div>
	    <?php echo gridc_render_module( 'season_events', 'grid_season_events', array( 'limit' => 5 ), gridc_render_season_fallback() ); ?>
	    <?php echo gridc_section_more( array( 'sezona-2026', 'sezona' ), 'Celý program sezóny' ); ?>
	  </div>
	</section>
	<?php else : ?>
	<section id="sezona" class="sec sec-light sec-pad grid-component grid-component--season">
	  <span class="sec-tag">T6</span>
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:46px"><span class="kicker">T6 · Sezóna · Čekací list</span><h2 style="font-size:clamp(2rem,4vw,3.6rem);margin-top:16px">Velké akce se plní rychle. Buďte na roštu první.</h2><p style="max-width:62ch;margin-top:14px;color:var(--muted)">O závodních víkendech je hotel uprostřed okruhu nejžádanějším místem v Brně. Vyberte akci a přečtěte si detail — rezervace a čekací list jsou hned pod kartami.</p></div>
	    <?php echo gridc_render_module( 'season_events', 'grid_season_events', array( 'limit' => 0, 'rezim' => 'karty' ), gridc_render_season_fallback() ); ?>
	  </div>
	</section>
	<section id="cekaci-list" class="sec sec-dark carbon sec-pad grid-component grid-component--season-waitlist">
	  <div class="wrap">
	    <div class="reveal" style="margin-bottom:40px"><span class="kicker">Rezervace &amp; čekací list</span><h2 style="font-size:clamp(2rem,4vw,3.4rem);margin-top:16px">Rezervujte pokoj na vybranou akci</h2></div>
	    <?php echo gridc_render_module( 'season_events', 'grid_season_events', array( 'limit' => 0, 'rezim' => 'seznam' ), gridc_render_season_fallback() ); ?>
	  </div>
	</section>
	<?php endif;
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_season', 'gridc_sc_season' );

/** Fallback dle GRID-SUITE-02 §9 — "prázdný stav bez formuláře", ne rozbitá sekce. */
function gridc_render_season_fallback() {
	return '<p class="description" style="color:var(--muted)">Program sezóny se právě aktualizuje. Sledujte nás nebo <a href="' . esc_url( gridc_nav_url( '#kontakt' ) ) . '">nás kontaktujte</a> pro aktuální termíny.</p>';
}

/* [grid_poukazy] — dárkové poukazy, ceník 1:1 z gridhotel.cz. */
function gridc_sc_poukazy() {
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
	$cards = function ( $rows ) {
		$h = '<div class="vou-grid">';
		foreach ( $rows as $r ) {
			$h .= '<div class="vou-card"><span class="vou-price">' . esc_html( $r[0] ) . '&nbsp;Kč</span><h4>' . esc_html( $r[1] ) . '</h4><p>' . esc_html( $r[2] ) . '</p></div>';
		}
		return $h . '</div>';
	};
	ob_start(); ?>
	<section id="poukazy" class="sec sec-dark carbon sec-pad grid-component grid-component--poukazy">
	  <div class="wrap" style="max-width:1000px">
	    <span class="kicker">Dárkové poukazy · Sezóna</span>
	    <h2 style="font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 16px">Darujte pobyt u Masarykova okruhu</h2>
	    <p style="color:var(--muted)">Vážení fanoušci motorsportu, máme pro vás možnost zakoupení dárkového poukazu na sezónu. Obdarujte své blízké na Vánoce, narozeniny, výročí, promoce, jako svatební dar nebo jen tak.</p>
	    <p style="color:var(--muted)">Vyberte si jeden ze dvou vzorů poukazu, do e-mailu uveďte, který vzor jste zvolili a jaký si přejete „Váš text" (např. „Tatínkovi"). Obratem pošleme zálohovou fakturu k platbě bankovním převodem; po přijetí platby vám elektronicky zašleme dárkový poukaz.</p>
	    <p style="color:var(--muted);font-family:var(--f-mono);font-size:.8rem">// Poukazy jsou standardně na 1 noc — dle přání upravíme na požadovaný počet nocí.</p>
	    <h3 style="margin:30px 0 12px;color:var(--gold)">Pro jednu osobu</h3>
	    <?php echo $cards( $one ); ?>
	    <h3 style="margin:30px 0 12px;color:var(--gold)">Pro dvě osoby</h3>
	    <?php echo $cards( $two ); ?>
	    <div class="vou-order">
	      <?php echo gridc_render_module( 'season_events', 'grid_voucher_form', array(), gridc_render_voucher_fallback() ); ?>
	      <p class="vou-note"><strong>Pozor:</strong> dárkový poukaz nelze kombinovat s jinými slevami a není platný v termínu konání vybraných akcí (např. HISTOCUP či Podzimní cena).</p>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_poukazy', 'gridc_sc_poukazy' );

/** Fallback formuláře poukazu — CTA e-mail, nikdy nepředstírat funkční formulář (GRID-SUITE-02 §10). */
function gridc_render_voucher_fallback() {
	$email = gridhotel_get_option( 'email', 'reservations@gridhotel.cz' );
	return '<p class="description" style="color:var(--muted)">Objednávku dárkového poukazu vyřídíme rádi e-mailem: <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p>';
}
