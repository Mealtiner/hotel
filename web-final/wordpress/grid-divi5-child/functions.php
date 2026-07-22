<?php
/**
 * GRID Hotel — Divi 5 Child Theme
 * functions.php
 *
 * - načte fonty, CSS a JS designového systému
 * - registruje ACF Options page + acf-json cestu
 * - načte shortcody, které vykreslují jednotlivé sekce z ACF obsahu
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRID_CHILD_VER', '2.18.0' );

/* ------------------------------------------------------------------
 * 1) Styly a skripty
 * ------------------------------------------------------------------ */
function grid_enqueue_assets() {

	// Google Fonts (Saira Condensed / JetBrains Mono / Inter) — plná česká diakritika
	wp_enqueue_style(
		'grid-fonts',
		'https://fonts.googleapis.com/css2?family=Saira+Condensed:wght@500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@300;400;500;600;700&display=swap',
		array(),
		null
	);

	// rodičovský Divi styl
	wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css', array(), GRID_CHILD_VER );

	// child styl (designový systém GRID)
	wp_enqueue_style( 'grid-child-style', get_stylesheet_uri(), array( 'divi-parent-style' ), GRID_CHILD_VER );

	// JS: živý widget (hodiny + teplota), reveal, čekací list, navigace
	wp_enqueue_script( 'grid-app', get_stylesheet_directory_uri() . '/assets/js/grid.js', array(), GRID_CHILD_VER, true );
}
add_action( 'wp_enqueue_scripts', 'grid_enqueue_assets', 5 ); // PŘED Divi (priorita 10) — jinak Divi nepozná, že child styl už je zaregistrovaný, a načte ho podruhé pod handle 'divi-style-child'

/* ------------------------------------------------------------------
 * 2) ACF — cesta k acf-json (auto-load / auto-save definic polí)
 * ------------------------------------------------------------------ */
add_filter( 'acf/settings/save_json', function ( $path ) {
	return get_stylesheet_directory() . '/acf-json';
} );
add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = get_stylesheet_directory() . '/acf-json';
	return $paths;
} );

/* ------------------------------------------------------------------
 * 3) ACF Options page (globální obsah: kontakt, hodiny, widget)
 * ------------------------------------------------------------------ */
add_action( 'acf/init', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( array(
			'page_title' => 'GRID — Nastavení webu',
			'menu_title' => 'GRID Nastavení',
			'menu_slug'  => 'grid-options',
			'capability' => 'manage_options', // globální nastavení (Hero/Kontakt/Socials/Video) jen pro admina — Contributor/Editor mají WP core capability 'edit_posts' i bez vztahu k webu
			'redirect'   => false,
			'icon_url'   => 'none', // barevnou ikonu vykreslíme přes admin CSS (viz níže)
			'position'   => 3,
		) );
	}
} );

/* ------------------------------------------------------------------
 * 4) Malý helper: bezpečné čtení ACF s fallbackem
 *    grid_field('slug', 'default', $post_or_option)
 * ------------------------------------------------------------------ */
function grid_field( $name, $default = '', $id = 'option' ) {
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name, $id );
		if ( $v !== null && $v !== '' && $v !== false ) return $v;
	}
	return $default;
}

/* ------------------------------------------------------------------
 * 5) Sekční shortcody (grid_hero, grid_rooms, grid_season, ...)
 * ------------------------------------------------------------------ */
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/errors.php';

/* ------------------------------------------------------------------
 * 6) Obsahová šířka webu (sladěno s návrhem)
 *    1200 = doporučeno | 1280 = kompromis | 1320 = 1:1 návrh
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	$w = (int) grid_field( 'sirka_webu', 1280 );
	echo '<style>:root{--maxw:' . esc_attr( $w ) . 'px}</style>' . "\n";
}, 99 );

/* ------------------------------------------------------------------
 * 7) Favicon webu (pokud není nastavená Ikona webu v Přizpůsobení)
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) return; // WP řeší vlastní ikonou
	$u = get_stylesheet_directory_uri() . '/assets/logo/g-mark.png';
	echo '<link rel="icon" href="' . esc_url( $u ) . '">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $u ) . '">' . "\n";
}, 5 );

/* ------------------------------------------------------------------
 * 8) Barevná ikona GRID u položky „GRID Nastavení" (místo autíčka)
 *    Technika převzatá z GARRY frameworku: icon 'none' + CSS background.
 * ------------------------------------------------------------------ */
add_action( 'admin_head', function () {
	$logo = get_stylesheet_directory_uri() . '/assets/logo/g-mark.png';
	?>
	<style>
	#adminmenu .toplevel_page_grid-options .wp-menu-image{
		background-image:url('<?php echo esc_url( $logo ); ?>') !important;
		background-repeat:no-repeat !important;
		background-position:center center !important;
		background-size:22px 22px !important;
		opacity:1 !important;
	}
	#adminmenu .toplevel_page_grid-options .wp-menu-image:before{ content:"" !important; }
	#adminmenu .toplevel_page_grid-options .wp-menu-image img{ display:none !important; }
	</style>
	<?php
} );

/* ------------------------------------------------------------------
 * 9) Klikací odkaz na stránku „Nastavení webu" v menu GRID Nastavení
 *    (CPT typy jsou zavěšené pod grid-options, proto přidáme položku
 *    mířící přímo na ACF options page, ať je vždy dostupná). grid-options-link-fix
 * ------------------------------------------------------------------ */
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return;
	add_submenu_page( 'grid-options', 'GRID — Nastavení webu', '⚙ Nastavení webu', 'edit_posts', 'grid-options' );
	// posunout tuto položku na začátek podnabídky
	global $submenu;
	if ( isset( $submenu['grid-options'] ) ) {
		$items = $submenu['grid-options'];
		$self = array(); $rest = array();
		foreach ( $items as $it ) {
			if ( isset( $it[2] ) && $it[2] === 'grid-options' ) $self[] = $it; else $rest[] = $it;
		}
		$submenu['grid-options'] = array_merge( $self, $rest );
	}
}, 999 );

/* ------------------------------------------------------------------
 * 10) Fluent Forms — načíst výchozí styly (jistota viditelnosti)
 * ------------------------------------------------------------------ */
add_filter( 'fluentform/load_default_public_style', '__return_true' );

/* ------------------------------------------------------------------
 * 11) Jazykový přepínač (Polylang) — URL překladů AKTUÁLNÍ stránky.
 *     Hlavička je statická v Divi; grid.js dosadí odkazy z této mapy.
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'pll_get_post' ) ) return;
	$urls = array();
	foreach ( array( 'cs', 'en', 'de' ) as $l ) {
		$u = '';
		if ( is_singular() ) {
			$t = pll_get_post( get_queried_object_id(), $l );
			if ( $t && get_post_status( $t ) === 'publish' ) $u = get_permalink( $t );
		} elseif ( is_tax() && function_exists( 'pll_get_term' ) ) {
			$t = pll_get_term( get_queried_object_id(), $l );
			if ( $t ) { $link = get_term_link( (int) $t ); if ( ! is_wp_error( $link ) ) $u = $link; }
		}
		if ( ! $u && function_exists( 'pll_home_url' ) ) $u = pll_home_url( $l );
		$urls[ $l ] = $u;
	}
	echo '<script>window.gridLangUrls=' . wp_json_encode( $urls ) . ";</script>\n";
} );

/* ------------------------------------------------------------------
 * 11b) Hlavní menu jako WP menu — editace ve Vzhled → Menu,
 *      jazykové mutace přiřazuje Polylang (lokace „grid-hlavni").
 *      V TB hlavičce je token [grid_menu_hlavni] → plain <a> odkazy.
 * ------------------------------------------------------------------ */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'grid-hlavni' => 'Hlavní menu (horní lišta)' ) );
} );
function grid_render_hlavni_menu() {
	$locations = get_nav_menu_locations(); // Polylang vrací menu pro aktuální jazyk
	$menu_id   = $locations['grid-hlavni'] ?? 0;
	$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	if ( ! $items ) return '';
	$out = array();
	foreach ( $items as $it ) {
		$out[] = '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
	}
	return implode( ' ', $out );
}
add_shortcode( 'grid_menu_hlavni', 'grid_render_hlavni_menu' );

/* ------------------------------------------------------------------
 * 12) Shortcody v Theme Builder layoutech (hlavička/patička)
 * ------------------------------------------------------------------
 * KRITICKÁ OPRAVA (2026-07-23): dřív se tu dělalo ob_start()+str_replace()
 * nad CELOU HTTP odpovědí na template_redirect. V Divi 5 Visual/Theme
 * Builderu (?et_fb=1) je celá odpověď zároveň JS bootstrap (<script
 * id="divi-settings-js-extra">…serializovaný JSON…</script>) — vložení raw
 * HTML (uvozovky, <script> tagy z Fluent Forms) do něj řetězec syntakticky
 * rozbilo a React skončil v ErrorBoundary ("Oops! An Error Has Occurred").
 * Builder tím padal na VŠECH stránkách, protože header/footer TB layout je
 * součástí bootstrapu úplně každé stránky bez ohledu na její vlastní obsah.
 *
 * Řešení: render_block filtr níže spouští do_shortcode() na úrovni JEDNOHO
 * Divi bloku přes WordPress block-rendering API — to funguje stejně dobře
 * pro běžné stránky i pro Theme Builder hlavičku/patičku (ověřeno: tokeny
 * [grid_paticka_kontakt], [grid_socials] i [grid_menu_hlavni] se touto
 * cestou vykreslují správně, žádný output buffer nad celou stránkou nebyl
 * potřeba). [grid_ff_newsletter] proto níže registrujeme jako plnohodnotný
 * shortcode se stejnou cestou vykreslení.
 */
add_filter( 'et_builder_render_layout', 'do_shortcode', 12 ); // Divi 4 cesta
add_filter( 'render_block', function ( $content, $block ) {
	if ( is_admin() ) return $content;
	if ( strpos( (string) ( $block['blockName'] ?? '' ), 'divi/' ) !== 0 ) return $content;
	if ( strpos( $content, '[grid_' ) === false ) return $content;
	return do_shortcode( $content );
}, 20, 2 ); // Divi 5 bloky (běžné stránky i Theme Builder header/footer)

/* [grid_ff_newsletter lang="cs|en|de"] — newsletter formulář Fluent Forms.
 * Atribut lang je NUTNÝ: v šabloně patičky je token 3× (jednou v každém
 * .grid-lang-cs/en/de bloku) a každý výskyt potřebuje SVOU jazykovou mutaci
 * formuláře — jinak by měly 3 kopie stejného formuláře identické HTML id. */
add_shortcode( 'grid_ff_newsletter', function ( $atts ) {
	if ( ! shortcode_exists( 'fluentform' ) ) return '';
	$atts = shortcode_atts( array( 'lang' => '' ), $atts );
	$lang = in_array( $atts['lang'], array( 'cs', 'en', 'de' ), true ) ? $atts['lang'] : grid_lang();
	$ffmap = (array) get_option( 'grid_ff_forms', array() );
	$fid = (int) ( $ffmap['newsletter'][ $lang ] ?? ( $ffmap['newsletter']['cs'] ?? 0 ) );
	if ( ! $fid ) return '';
	return do_shortcode( '[fluentform id=' . $fid . ']' );
} );

/* ------------------------------------------------------------------
 * 13) Bezpečnostní hardening (audit 2026-07-22)
 * ------------------------------------------------------------------ */

/* XML-RPC nepoužíváme (žádná mobilní appka, žádný Jetpack) — vypnuto celé.
 * Filtr 'xmlrpc_enabled' sám o sobě NEstačí (blokuje jen pingback metody, ne
 * např. system.multicall zneužívané k hromadnému brute-force loginu) —
 * proto smažeme VŠECHNY registrované metody, endpoint pak na cokoliv vrátí fault. */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

/* Web nepoužívá komentáře ani pingbacky — výchozí hodnoty pro nový obsah na "zavřeno"
 * (u existujícího obsahu se nic nemění, ten je uzavřený per post_type/support už dnes). */
add_action( 'admin_init', function () {
	if ( get_option( 'default_comment_status' ) !== 'closed' ) update_option( 'default_comment_status', 'closed' );
	if ( get_option( 'default_ping_status' ) !== 'closed' ) update_option( 'default_ping_status', 'closed' );
} );

/* REST /wp/v2/users neautentizovaně odhaluje display_name (u nás byl nastaven na e-mail
 * administrátora — snadný cíl phishingu/credential stuffingu). Anonymním požadavkům na
 * uživatelské endpointy vrátíme 401; přihlášeným (adminovi) REST dál funguje normálně. */
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( is_wp_error( $result ) || is_user_logged_in() ) return $result;
	$route = $GLOBALS['wp']->query_vars['rest_route'] ?? ( $_SERVER['REQUEST_URI'] ?? '' );
	if ( is_string( $route ) && preg_match( '~/wp/v2/users(?:/|$|\?)~', $route ) ) {
		return new WP_Error( 'rest_forbidden', 'Uživatelský REST endpoint je dostupný jen přihlášeným.', array( 'status' => 401 ) );
	}
	return $result;
} );
/* Autorské archivy (/?author=N, /author/slug/) přesměrovat na homepage — na webu se nepoužívají
 * a jinak umožňují dohledat uživatelské jméno/slug enumerací ?author=1,2,3… */
add_action( 'template_redirect', function () {
	if ( is_author() && ! is_user_logged_in() ) wp_safe_redirect( home_url( '/' ), 301 );
} );

/* readme.html / license.txt / *.php-old / *.bak / *.orig — standardní WP fingerprinting
 * a případné zapomenuté zálohy nikdy neservírovat veřejně. */
add_action( 'template_redirect', function () {
	$uri = strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '?' );
	if ( preg_match( '~/(readme\.html|readme\.txt|license\.txt)$~i', $uri )
		|| preg_match( '~\.(php-old|bak|orig)$~i', $uri ) ) {
		status_header( 404 );
		nocache_headers();
		include get_theme_file_path( '404.php' );
		exit;
	}
}, 0 );

/* Bezpečné HTTP hlavičky, které nezávisí na konfiguraci webového serveru (funguje i když
 * produkční nginx/Apache config zatím není hotová). CSP vědomě NEnasazujeme — Divi/Google
 * Fonts/inline styly by vyžadovaly rozsáhlé ladění, riziko rozbití webu je vyšší než přínos
 * v této fázi; doporučeno řešit až na produkci s vyhrazeným testem. */
add_action( 'send_headers', function () {
	if ( is_admin() ) return;
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
} );
/* Skrýt PHP verzi v odpovědi (expose_php řeší až produkční php.ini, toto je doplňkové). */
add_action( 'init', function () { if ( function_exists( 'header_remove' ) ) header_remove( 'X-Powered-By' ); } );
