<?php
/**
 * GRID Hotel — chybové stránky (jednotný design, CZ/EN/DE).
 * 404 řeší WordPress přes 404.php (volá grid_render_error(404)).
 * 403/500/503 jsou samostatné statické soubory v /errors/ (napojení přes .htaccess),
 * protože při serverové chybě nemusí WordPress vůbec běžet.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Texty chyb v CZ/EN/DE. */
function grid_error_content( $code ) {
	$m = array(
		403 => array(
			'cs' => array( 'k' => 'Chyba 403', 'h' => 'Uzavřený box.',      's' => 'Na tuto stránku nemáte přístup. Vraťte se prosím do veřejné části areálu.' ),
			'en' => array( 'k' => 'Error 403', 'h' => 'Restricted paddock.', 's' => 'You don’t have access to this page. Please head back to the public area.' ),
			'de' => array( 'k' => 'Fehler 403','h' => 'Gesperrte Box.',      's' => 'Sie haben keinen Zugriff auf diese Seite. Bitte kehren Sie zum öffentlichen Bereich zurück.' ),
		),
		404 => array(
			'cs' => array( 'k' => 'Chyba 404', 'h' => 'Špatná zatáčka.', 's' => 'Tahle stránka sjela z trati. Vraťte se do boxu a zkuste to znovu.' ),
			'en' => array( 'k' => 'Error 404', 'h' => 'Wrong turn.',     's' => 'This page has left the track. Head back to the pit and try again.' ),
			'de' => array( 'k' => 'Fehler 404','h' => 'Falsche Kurve.',  's' => 'Diese Seite hat die Strecke verlassen. Zurück in die Box und erneut versuchen.' ),
		),
		500 => array(
			'cs' => array( 'k' => 'Chyba 500', 'h' => 'Technická porucha.', 's' => 'Naši mechanici už na tom pracují. Zkuste to prosím za chvíli.' ),
			'en' => array( 'k' => 'Error 500', 'h' => 'Technical failure.', 's' => 'Our crew is already on it. Please try again shortly.' ),
			'de' => array( 'k' => 'Fehler 500','h' => 'Technischer Defekt.','s' => 'Unsere Crew ist schon dran. Bitte versuchen Sie es gleich noch einmal.' ),
		),
		503 => array(
			'cs' => array( 'k' => 'Chyba 503', 'h' => 'Zastávka v boxech.', 's' => 'Web je na chvíli v servisu. Za okamžik jsme zpátky na trati.' ),
			'en' => array( 'k' => 'Error 503', 'h' => 'Pit stop.',          's' => 'The site is briefly under maintenance. We’ll be back on track shortly.' ),
			'de' => array( 'k' => 'Fehler 503','h' => 'Boxenstopp.',        's' => 'Die Seite ist kurz in Wartung. Wir sind gleich zurück auf der Strecke.' ),
		),
	);
	return isset( $m[ $code ] ) ? $m[ $code ] : $m[404];
}

/** Aktuální jazyk (Polylang / WPML / fallback cs). */
function grid_error_lang() {
	$lang = 'cs';
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language( 'slug' ); if ( $l ) $lang = $l; }
	elseif ( defined( 'ICL_LANGUAGE_CODE' ) ) { $lang = ICL_LANGUAGE_CODE; }
	return in_array( $lang, array( 'cs','en','de' ), true ) ? $lang : 'cs';
}

/** Sdílené CSS chybové stránky (pro WP 404). */
function grid_error_css() {
	return '<style>
	.grid-error{min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:clamp(30px,6vw,80px);position:relative;color:var(--offwhite)}
	.grid-error .ge-logo{position:absolute;top:clamp(20px,4vw,40px);left:50%;transform:translateX(-50%)}
	.grid-error .ge-logo img{height:40px;width:auto}
	.ge-inner{position:relative;z-index:2;max-width:44ch}
	.ge-num{font-family:var(--f-head);font-weight:800;line-height:.78;font-size:clamp(7rem,26vw,19rem);color:transparent;-webkit-text-stroke:2px rgba(255,90,80,.55);letter-spacing:.02em;margin-bottom:6px}
	.ge-kicker{font-family:var(--f-mono);font-size:.72rem;letter-spacing:.32em;text-transform:uppercase;color:var(--red-hi,#FF5A50)}
	.ge-head{font-family:var(--f-head);text-transform:uppercase;font-size:clamp(2rem,5vw,3.6rem);margin:14px 0 12px;color:#fff}
	.ge-sub{color:var(--grey);font-size:1.05rem;margin-bottom:30px}
	</style>';
}

/**
 * Vykreslí kompletní chybovou stránku (self-contained doc) — pro 404.php.
 */
function grid_render_error( $code ) {
	$c    = grid_error_content( $code );
	$lang = grid_error_lang();
	$t    = isset( $c[ $lang ] ) ? $c[ $lang ] : $c['cs'];
	$btn  = array( 'cs' => 'Zpět na start', 'en' => 'Back to start', 'de' => 'Zurück zum Start' );
	$logo = get_stylesheet_directory_uri() . '/assets/logo/grid-hotel-negativ.png';
	if ( ! headers_sent() ) status_header( (int) $code );
	?><!DOCTYPE html><html <?php language_attributes(); ?>><head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $t['k'] . ' — ' . $t['h'] . ' | GRID HOTEL' ); ?></title>
	<meta name="robots" content="noindex,follow">
	<?php wp_head(); echo grid_error_css(); ?>
	</head><body <?php body_class( 'grid-error-body' ); ?>>
	<div class="grid-error carbon">
	  <a class="ge-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo esc_url( $logo ); ?>" alt="GRID HOTEL"></a>
	  <div class="ge-inner">
	    <div class="ge-num"><?php echo (int) $code; ?></div>
	    <span class="ge-kicker"><?php echo esc_html( $t['k'] ); ?></span>
	    <h1 class="ge-head"><?php echo esc_html( $t['h'] ); ?></h1>
	    <p class="ge-sub"><?php echo esc_html( $t['s'] ); ?></p>
	    <a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $btn[ $lang ] ?? $btn['cs'] ); ?></a>
	  </div>
	</div>
	<?php wp_footer(); ?>
	</body></html><?php
}
