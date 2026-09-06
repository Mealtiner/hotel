<?php
/**
 * GRID Hotel Components — globální shortcody: hlavní menu, socials, patička
 * kontaktu, video embed, header, footer. Ported z child theme inc/shortcodes.php
 * (GRID-SUITE-02 §4 „globální obsah a kontakty").
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * [grid_menu_hlavni] — WP nav menu 'grid-hlavni', Polylang-aware.
 *
 * Musí vracet PLOCHÉ <a> tagy vedle sebe, ne wp_nav_menu()'s <ul><li>
 * strukturu (tu wp_nav_menu() vždy obalí, container=>false odstraní jen
 * vnější <div>/<nav>, ne <ul><li> samotné) — .topnav nav{display:flex} v
 * style.css čeká přímé <a> potomky uvnitř <nav>, jinak menu spadne pod
 * sebe (na <ul> ani <li> žádné flex pravidlo necílí). Stejný formát jako
 * theme fallback grid_render_hlavni_menu() v functions.php.
 */
function gridc_render_hlavni_menu( $atts = array() ) {
	$locations = get_nav_menu_locations();
	$menu_id   = $locations['grid-hlavni'] ?? 0;
	$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	if ( ! $items ) {
		return '';
	}
	$out = array();
	foreach ( $items as $it ) {
		$out[] = '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
	}
	return implode( ' ', $out );
}
gridc_register_shortcode( 'grid_menu_hlavni', 'gridc_render_hlavni_menu' );

/* [grid_socials] — ikony sociálních sítí z Core (jen vyplněné). */
function gridc_sc_socials() {
	$socials = gridc_social_links();
	if ( ! $socials ) {
		return '';
	}
	$h = '<div class="socials grid-component grid-component--socials" aria-label="Social">';
	foreach ( $socials as $so ) {
		$h .= '<a href="' . esc_url( $so['url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $so['label'] ) . '">' . esc_html( $so['short'] ) . '</a>';
	}
	return $h . '</div>';
}
gridc_register_shortcode( 'grid_socials', 'gridc_sc_socials' );

/* [grid_paticka_kontakt] — adresní blok patičky, lokalizované labely. */
function gridc_sc_footer_kontakt() {
	$li = gridc_lang_index();
	$L  = array(
		'recepce'   => array( 'Recepce', 'Reception', 'Rezeption' ),
		'rezervace' => array( 'Rezervace', 'Reservations', 'Reservierung' ),
		'shuttle'   => array( 'Shuttle bus', 'Shuttle bus', 'Shuttlebus' ),
	);
	$a1     = gridhotel_get_option( 'adresa_1', 'Ostrovačická 936/65, Masarykův okruh' );
	$a2     = gridhotel_get_option( 'adresa_2', '641 00 Brno – Žebětín, ČR' );
	$telr   = gridhotel_get_option( 'tel_recepce', '+420 775 877 721' );
	$telrez = gridhotel_get_option( 'tel_rezervace', '+420 775 877 720' );
	$tels   = gridhotel_get_option( 'tel_shuttle', '+420 775 778 718' );
	$email  = gridhotel_get_option( 'email', 'info@gridhotel.cz' );
	$raw    = function ( $t ) { return preg_replace( '/\s+/', '', $t ); };
	return '<span class="data grid-component grid-component--footer-kontakt">' . esc_html( $a1 ) . '<br>' . esc_html( $a2 ) . '<br>'
		. esc_html( $L['recepce'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $telr ) ) . '">' . esc_html( $telr ) . '</a><br>'
		. esc_html( $L['rezervace'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $telrez ) ) . '">' . esc_html( $telrez ) . '</a><br>'
		. esc_html( $L['shuttle'][ $li ] ) . ': <a href="tel:' . esc_attr( $raw( $tels ) ) . '">' . esc_html( $tels ) . '</a><br>'
		. '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></span>';
}
gridc_register_shortcode( 'grid_paticka_kontakt', 'gridc_sc_footer_kontakt' );

/* [grid_video_embed] — YouTube/Vimeo embed z Core → Video (časosběr). */
function gridc_sc_video_embed() {
	$embed = gridc_video_embed( gridhotel_get_option( 'video_url', '' ) );
	if ( $embed ) {
		return '<iframe class="grid-component grid-component--video-embed" src="' . esc_url( $embed ) . '" title="GRID Hotel video" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>';
	}
	$li   = gridc_lang_index();
	$note = array(
		'// Vlož odkaz na video v GRID Nastavení → Obecné nastavení → Video.',
		'// Add the video link in GRID Settings → General → Video.',
		'// Videolink in GRID-Einstellungen → Allgemein → Video eintragen.',
	);
	return '<div class="grid-component grid-component--video-embed" style="padding:60px 24px;text-align:center;color:var(--muted);font-family:var(--f-mono);font-size:.8rem">' . esc_html( $note[ $li ] ) . '</div>';
}
gridc_register_shortcode( 'grid_video_embed', 'gridc_sc_video_embed' );

/**
 * [grid_lang_switch] — přepínač jazyka, odkazující na PŘEKLAD AKTUÁLNÍ stránky
 * (přes gridc_lang_switch_urls() / Polylang), ne na jazykovou domovskou
 * stránku. Vytažené jako vlastní shortcode, aby ho šlo použít i mimo
 * [grid_header] — konkrétně v Divi Theme Builder hlavičce (post ID 11),
 * kde byl přepínač donedávna nadupaný jako statický HTML (3× zkopírovaný
 * blok .grid-lang-cs/en/de, JS podle jazyka stránky jen schovával ty
 * ostatní) s pevnými odkazy na "/", "/en/", "/de/" — proto vždy skočil na
 * jazykovou domovskou stránku místo na přeložený ekvivalent aktuální
 * stránky, i když tahle (správná, Polylang-aware) logika už v pluginu
 * existovala, jen nebyla v hlavičce vůbec použitá.
 */
function gridc_sc_lang_switch() {
	$cur_lang  = gridc_lang();
	$lang_urls = gridc_lang_switch_urls();
	$aria      = array( 'cs' => 'Jazyk', 'en' => 'Language', 'de' => 'Sprache' );
	$aria_label = isset( $aria[ $cur_lang ] ) ? $aria[ $cur_lang ] : 'Jazyk';
	// Polylang slug pro češtinu je "cs", ale zobrazovaný štítek byl vždy "CZ"
	// (statický baseline markup) — mapujeme zvlášť, není to prosté strtoupper().
	$label = array( 'cs' => 'CZ', 'en' => 'EN', 'de' => 'DE' );
	$lbl   = function ( $l ) use ( $label ) { return isset( $label[ $l ] ) ? $label[ $l ] : strtoupper( $l ); };

	ob_start();
	if ( empty( $lang_urls ) ) {
		// Bez Polylang / bez dostupných URL zůstává neutrální statický popisek — žádné mrtvé "#" odkazy.
		echo '<div class="lang" aria-label="' . esc_attr( $aria_label ) . '"><span class="active">' . esc_html( $lbl( $cur_lang ) ) . '</span></div>';
		return ob_get_clean();
	}
	echo '<div class="lang" aria-label="' . esc_attr( $aria_label ) . '">';
	foreach ( $lang_urls as $l => $u ) {
		printf(
			'<a href="%s"%s>%s</a>',
			esc_url( $u ),
			$l === $cur_lang ? ' class="active" aria-current="true"' : '',
			esc_html( $lbl( $l ) )
		);
	}
	echo '</div>';
	return ob_get_clean();
}
gridc_register_shortcode( 'grid_lang_switch', 'gridc_sc_lang_switch' );

/* [grid_header] — sticky header: logo, nav, jazyk, mobilní menu. */
function gridc_sc_header() {
	$logo = gridhotel_get_option( 'logo_negativ', '' );
	$logo = $logo ? ( is_array( $logo ) ? $logo['url'] : $logo ) : gridc_fallback_logo( 'grid-hotel-negativ.png' );
	$nav  = array(
		array( 'label' => 'Pokoje', 'url' => '#pokoje' ),
		array( 'label' => 'Zážitky', 'url' => '#zazitky' ),
		array( 'label' => 'Gastronomie', 'url' => '#restaurace' ),
		array( 'label' => 'Sezóna', 'url' => '#sezona' ),
		array( 'label' => 'Firemní akce & svatby', 'url' => '#firemni' ),
		array( 'label' => 'Kontakt', 'url' => '#kontakt' ),
	);
	$rez = gridc_rezervace_url();
	$render_lang_switch = function () {
		echo gridc_sc_lang_switch();
	};

	ob_start(); ?>
	<header id="topbar" class="grid-component grid-component--header">
	  <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="brand" aria-label="GRID HOTEL — domů">
	    <img src="<?php echo esc_url( $logo ); ?>" alt="GRID HOTEL logo">
	  </a>
	  <div class="topnav">
	    <nav aria-label="Hlavní menu">
	      <?php foreach ( $nav as $n ) : ?>
	        <a href="<?php echo esc_url( gridc_nav_url( $n['url'] ) ); ?>"><?php echo esc_html( $n['label'] ); ?></a>
	      <?php endforeach; ?>
	    </nav>
	    <?php $render_lang_switch(); ?>
	    <a href="<?php echo esc_url( gridc_nav_url( $rez ) ); ?>" class="btn">Rezervovat</a>
	  </div>
	  <button class="hamburger" id="hamburger" aria-label="Otevřít menu" aria-expanded="false"><span></span><span></span><span></span></button>
	</header>
	<div class="mobile-menu" id="mobileMenu">
	  <button class="mm-close" id="mmClose" aria-label="Zavřít menu">&times;</button>
	  <?php foreach ( $nav as $n ) : ?>
	    <a href="<?php echo esc_url( gridc_nav_url( $n['url'] ) ); ?>"><?php echo esc_html( $n['label'] ); ?></a>
	  <?php endforeach; ?>
	  <a href="<?php echo esc_url( gridc_nav_url( $rez ) ); ?>" class="btn">Rezervovat</a>
	  <?php $render_lang_switch(); ?>
	</div>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_header', 'gridc_sc_header' );

/* [grid_footer] — footer + kontakt/socials (přes ModuleRenderer, ne text token). */
function gridc_sc_footer() {
	$logo   = gridc_fallback_logo( 'grid-hotel-negativ.png' );
	$ico    = gridhotel_get_option( 'ico', '04996364' );
	$dic    = gridhotel_get_option( 'dic', 'CZ04996364' );
	$spis   = gridhotel_get_option( 'spis_znacka', 'Sp. zn. C 92997, KS v Brně' );
	$u_pod  = gridhotel_get_option( 'url_podminky', home_url( '/podminky/' ) );
	$u_och  = gridhotel_get_option( 'url_ochrana', home_url( '/ochrana-osobnich-udaju/' ) );
	$u_coo  = gridhotel_get_option( 'url_cookies', home_url( '/cookies/' ) );
	$u_dop  = gridhotel_get_option( 'url_doprava', home_url( '/doprava/' ) );
	$u_dot  = gridhotel_get_option( 'url_dotaznik', home_url( '/dotaznik-spokojenosti/' ) );
	$u_kar  = gridhotel_get_option( 'url_kariera', home_url( '/kariera/' ) );

	ob_start(); ?>
	<footer id="kontakt" class="grid-component grid-component--footer">
	  <div class="wrap">
	    <div class="foot-top">
	      <div class="foot-brand">
	        <img src="<?php echo esc_url( $logo ); ?>" alt="GRID HOTEL logo">
	        <p>Hotel a restaurace **** přímo v areálu Autodromu Brno. Přespi uprostřed Masarykova okruhu.</p>
	        <?php echo gridc_sc_footer_kontakt(); ?>
	        <?php echo gridc_sc_socials(); ?>
	      </div>
	      <div class="foot-col"><h4>Hotel</h4><ul>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'o-nas' ), '#pribeh' ) ); ?>">O hotelu</a></li>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'ubytovani', 'pokoje', 'pokoje-a-apartmany' ), '#pokoje' ) ); ?>">Pokoje &amp; apartmá</a></li>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'gastronomie', 'gastro' ), '#restaurace' ) ); ?>">Gastronomie</a></li>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'zazitky-u-okruhu', 'zazitky', 'aktivity' ), '#zazitky' ) ); ?>">Zážitky &amp; dárkové poukazy</a></li>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'sezona-2026', 'sezona' ), '#sezona' ) ); ?>">Sezóna</a></li>
	        <li><a href="<?php echo esc_url( gridc_link_pref( array( 'firemni-akce-svatby', 'firemni' ), '#firemni' ) ); ?>">Firemní akce &amp; svatby</a></li>
	      </ul></div>
	      <div class="foot-col"><h4>Informace</h4><ul>
	        <li><a href="<?php echo esc_url( $u_dop ); ?>">Jak se k nám dostanete</a></li>
	        <li><a href="<?php echo esc_url( $u_dop ); ?>">Parkování &amp; shuttle bus</a></li>
	        <li><a href="<?php echo esc_url( $u_kar ); ?>">Kariéra</a></li>
	        <li><a href="<?php echo esc_url( $u_dot ); ?>">Dotazník spokojenosti</a></li>
	        <li><a href="<?php echo esc_url( $u_pod ); ?>">Všeobecné obchodní podmínky</a></li>
	        <li><a href="<?php echo esc_url( $u_och ); ?>">Ochrana osobních údajů</a></li>
	      </ul></div>
	      <div class="foot-col">
	        <h4>Event alert &amp; Newsletter</h4>
	        <p style="color:var(--grey);font-size:.86rem">Nezmeškej termíny sezóny a speciální balíčky.</p>
	        <?php echo gridc_render_newsletter_form(); ?>
	        <p style="color:var(--grey-dim);font-size:.78rem;margin-top:18px">GRH s.r.o.<br>IČ: <?php echo esc_html( $ico ); ?> · DIČ: <?php echo esc_html( $dic ); ?><br><?php echo esc_html( $spis ); ?></p>
	      </div>
	    </div>
	    <div class="foot-bottom">
	      <div class="legal">
	        <a href="<?php echo esc_url( $u_och ); ?>">Ochrana osobních údajů</a>
	        <a href="<?php echo esc_url( $u_coo ); ?>">Cookies</a>
	        <a href="<?php echo esc_url( $u_pod ); ?>">Obchodní podmínky</a>
	        <span>© <?php echo esc_html( date( 'Y' ) ); ?> GRID HOTEL</span>
	      </div>
	      <a class="build-tag" href="https://www.garry.cz" target="_blank" rel="noopener">Web &amp; design — GARRY Promotion</a>
	    </div>
	  </div>
	</footer>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_footer', 'gridc_sc_footer' );

/**
 * Newsletter formulář — dřív hardcoded 3jazyčný Fluent Forms token
 * `[grid_ff_newsletter]` s ruční theme náhradou (template_redirect string-replace
 * hack, protože Divi 5 Theme Builder nevolá do_shortcode() na header/footer).
 * Tady se řeší čistě: gridhotel_ff_status()/gridhotel_ff_get_form_id() z Core,
 * žádný string-replace hack potřeba, protože Components generuje HTML přímo
 * (ne přes vnořený token).
 */
function gridc_render_newsletter_form() {
	if ( ! function_exists( 'gridhotel_ff_status' ) ) {
		return '';
	}
	$lang   = gridc_lang();
	$status = gridhotel_ff_status( 'newsletter', $lang );
	if ( 'ok' === $status ) {
		$id = gridhotel_ff_get_form_id( 'newsletter', $lang );
		return do_shortcode( '[fluentform id="' . (int) $id . '"]' );
	}
	// Fallback: vizuální placeholder bez skutečného odeslání (nikdy nepředstírat úspěch — GRID-SUITE-02 §10).
	return '<form class="newsletter" onsubmit="return false"><input type="email" placeholder="Tvůj e-mail" aria-label="E-mail pro newsletter"><button type="submit" onclick="return false">Odebírat</button></form>';
}
