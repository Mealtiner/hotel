<?php
/**
 * GRID Hotel — Divi 5 Child Theme
 * functions.php
 *
 * Fáze 9 GRID Suite refaktoringu (GRID-SUITE-09) — theme je teď čistě
 * prezentační vrstva: design tokeny, CSS, Divi layout/template úpravy,
 * obrázky. Vlastnictví dat/business logiky/hlavních shortcodů přešlo na
 * pluginy (gridhotel-core, gridhotel-components, GARRY Hero křivka,
 * GARRY Sekční navigace, GARRY Situace na trati) — viz GRID-SUITE-00
 * master plán, "Mapování vlastnictví runtime".
 *
 * Co theme OD 3.0.0 už NEVLASTNÍ (a proto to tady není):
 *  - GRID Nastavení (grid-options) + ACF options page — vlastní
 *    gridhotel-core ≥ 2.0.0 (inc/options-page.php), stejné field names,
 *    žádná migrace dat, jen změna KDO stránku registruje.
 *  - Hlavních 30 z 32 grid_* shortcode callbacků — vlastní
 *    gridhotel-components ≥ 1.0.0. [grid_tracknav]/[grid_telemetry]
 *    vlastní GARRY Sekční navigace / GARRY Situace na trati.
 *  - Hero křivka JS/SVG (window.gridHeroCurve byl jen konfigurace,
 *    skutečné vykreslení dělá GARRY Hero křivka ≥ 1.2.0).
 *  - Scroll-spy/aktivní stav sekční navigace (GARRY Sekční navigace ≥ 1.4.0).
 *  - Telemetry/počasí polling (GARRY Situace na trati vlastní od začátku,
 *    theme dřív běžel DUPLICITNÍ vlastní fetch — odstraněno, viz assets/js/grid.js).
 *
 * Tenhle theme proto VYŽADUJE aktivní gridhotel-core + gridhotel-components,
 * aby web vykreslil obsah (žádná vlastní fallback data) — to je u
 * projektově-specifického theme akceptovatelné (na rozdíl od přenositelných
 * GARRY pluginů, které standalone fungovat MUSÍ).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRID_CHILD_VER', '3.38.0' );

/* ------------------------------------------------------------------
 * 1) Styly a skripty
 * ------------------------------------------------------------------ */
function grid_enqueue_assets() {

	/* Písma (Saira Condensed / JetBrains Mono / Inter) — plná česká diakritika.
	   Hostujeme je lokálně: načtení z fonts.googleapis.com posílá IP adresu
	   návštěvníka Googlu ještě před souhlasem s cookies (GDPR/ePrivacy).
	   Soubory a @font-face generuje tools/obsah/pisma-lokalne.py. */
	wp_enqueue_style(
		'grid-fonts',
		get_stylesheet_directory_uri() . '/assets/css/pisma.css',
		array(),
		GRID_CHILD_VER
	);

	// rodičovský Divi styl
	wp_enqueue_style( 'divi-parent-style', get_template_directory_uri() . '/style.css', array(), GRID_CHILD_VER );

	// child styl (designový systém GRID)
	wp_enqueue_style( 'grid-child-style', get_stylesheet_uri(), array( 'divi-parent-style' ), GRID_CHILD_VER );

	// JS: živý widget (hodiny + teplota), reveal, čekací list, navigace
	wp_enqueue_script( 'grid-app', get_stylesheet_directory_uri() . '/assets/js/grid.js', array(), GRID_CHILD_VER, true );

	/* Vlastní rozbalovací seznam — rezervační lišta a čekací list. Skript si
	   sám najde, jestli je na stránce co obsloužit, takže není potřeba hlídat
	   šablonu; kalendář (flatpickr) se naopak načítá jen na titulní straně. */
	wp_enqueue_script( 'grid-vyber', get_stylesheet_directory_uri() . '/assets/js/vyber.js', array(), GRID_CHILD_VER, true );

	/* Karty dárkových poukazů předvyplňují objednávkový formulář; skript si sám
	   ověří, že je na stránce karta i formulář, jinak neudělá nic. */
	wp_enqueue_script( 'grid-poukazy', get_stylesheet_directory_uri() . '/assets/js/poukazy.js', array( 'grid-vyber' ), GRID_CHILD_VER, true );

}
add_action( 'wp_enqueue_scripts', 'grid_enqueue_assets', 5 ); // PŘED Divi (priorita 10) — jinak Divi nepozná, že child styl už je zaregistrovaný, a načte ho podruhé pod handle 'divi-style-child'

/* ------------------------------------------------------------------
 * Kalendář ve formulářích — přesunuto do GRID Hotel Components 1.13.0
 * (inc/forms.php). Motiv si jen veze knihovnu flatpickr pro rezervační
 * lištu a i18n si vyžádá z pluginu.
 *
 * Původní poznámka:
 * Kalendář ve formulářích česky / anglicky / německy
 * ------------------------------------------------------------------
 * Fluent Forms skládá názvy měsíců a dnů pro flatpickr ze svojí vlastní
 * textové domény (__('Monday', 'fluentform')). Český překlad pluginu
 * neexistuje a německý tyhle řetězce nemá, takže kalendář vycházel ve všech
 * jazykových verzích anglicky — přestože WordPress ta jména správně zná
 * (Pondělí / Září, Montag / September).
 *
 * Bereme je proto z $wp_locale přes oficiální filtr pluginu. Locale přepíná
 * Polylang podle jazyka stránky, takže se picker přizpůsobí sám. Do formulářů
 * v administraci se nesahá — tohle je jen zdroj názvů.
 * ------------------------------------------------------------------ */
/* ------------------------------------------------------------------
 * Menu v patičce
 * ------------------------------------------------------------------
 * Sloupce „Hotel" a „Informace" byly napsané natvrdo v šabloně patičky.
 * Jsou z nich běžná menu WordPressu, takže se položky přidávají a přesouvají
 * ve Vzhled → Menu — pro každý jazyk zvlášť (Polylang přiřazuje k jedné
 * pozici jiné menu podle jazyka). Když k pozici žádné menu přiřazené není,
 * patička vypíše původní pevný seznam, takže nikdy nezůstane prázdná.
 * ------------------------------------------------------------------ */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'grid-paticka-hotel'     => 'Patička — sloupec Hotel',
		'grid-paticka-informace' => 'Patička — sloupec Informace',
	) );
} );




/* Kalendář v rezervační liště (titulní strana). Knihovnu si motiv veze sám —
   dosud ji na stránku tahaly jen formuláře, takže by lišta tiše přestala
   fungovat, kdyby na stránce žádný formulář nebyl. Handle je shodný s tím,
   který používá Fluent Forms, takže se soubor nenačte dvakrát. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_front_page() ) {
		return;
	}
	$adr = get_stylesheet_directory_uri() . '/assets/vendor/flatpickr/';
	wp_enqueue_style( 'flatpickr', $adr . 'flatpickr.min.css', array(), '4.6.9' );
	wp_enqueue_script( 'flatpickr', $adr . 'flatpickr.min.js', array(), '4.6.9', true );
	wp_enqueue_script( 'grid-rezervacni-lista', get_stylesheet_directory_uri() . '/assets/js/rezervacni-lista.js', array( 'flatpickr' ), GRID_CHILD_VER, true );

	$formaty = array( 'cs' => 'j. n. Y', 'en' => 'j M Y', 'de' => 'j. n. Y' );
	$jazyk   = substr( (string) determine_locale(), 0, 2 );
	wp_localize_script( 'grid-rezervacni-lista', 'gridKalendar', array(
		'i18n'   => function_exists( 'gridc_kalendar_i18n' ) ? gridc_kalendar_i18n() : array(),
		'format' => $formaty[ $jazyk ] ?? $formaty['cs'],
	) );
}, 6 );

/* Divi si samo tahá Open Sans z Google (inline CSS + soubory z fonts.gstatic.com).
   Web ho nikde nepoužívá — všechna písma určuje child motiv — a volání odchází
   ještě před souhlasem s cookies. Divi nabízí oficiální vypínač přes překlad
   řetězce „on/off“, kterým se načítání celé rodiny přeskočí. */
/* Divi 5 navíc registruje variabilní Open Sans z fonts.googleapis.com a WordPress
   k němu přidává resource hints. Na webu se nepoužije žádný jeho řez, tak ho
   ve frontendu odhlásíme; ve Visual Builderu ho necháváme být. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() || isset( $_GET['et_fb'] ) ) {
		return;
	}
	/* Jen odhlásit z výpisu — deregistrovat handle by shodilo případný styl,
	   který by ho měl uvedený jako závislost. */
	/* Divi má pro variabilní Open Sans víc handlů podle toho, jak se stránka
	   staví. Na šablonách mimo builder (kategorie pokojů) to je
	   et-builder-googlefonts-variable, který v seznamu chyběl — Open Sans
	   se tam proto pořád stahoval z fonts.googleapis.com. */
	foreach ( array( 'et-builder-googlefonts-cached-variable', 'et-builder-googlefonts-variable', 'et-builder-googlefonts-cached', 'et-builder-googlefonts', 'et-divi-open-sans' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}, 100 );

/* Druhý průchod: Divi 5 zařazuje písma až v et_builder_print_font() na
   wp_footer (priorita 10), tedy dávno po wp_enqueue_scripts. Na stránkách
   mimo builder (kategorie pokojů) se tak Open Sans dostal do fronty, i když
   jsme ho výš odhlásili. Styly zařazené v patičce vypisuje
   wp_print_footer_scripts na prioritě 20, takže priorita 11 je stihne. */
add_action( 'wp_footer', function () {
	if ( is_admin() || isset( $_GET['et_fb'] ) ) {
		return;
	}
	foreach ( array( 'et-builder-googlefonts-cached-variable', 'et-builder-googlefonts-variable', 'et-builder-googlefonts-cached', 'et-builder-googlefonts', 'et-divi-open-sans' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}, 11 );

add_action( 'init', function () {
	remove_action( 'wp_enqueue_scripts', 'et_builder_preconnect_google_fonts', 9 );
} );

add_filter( 'wp_resource_hints', function ( $adresy, $vztah ) {
	if ( ! in_array( $vztah, array( 'dns-prefetch', 'preconnect' ), true ) ) {
		return $adresy;
	}
	return array_values( array_filter( $adresy, function ( $a ) {
		$url = is_array( $a ) ? ( $a['href'] ?? '' ) : $a;
		return false === strpos( (string) $url, 'fonts.googleapis.com' )
			&& false === strpos( (string) $url, 'fonts.gstatic.com' );
	} ) );
}, 10, 2 );

add_filter( 'gettext_with_context', function ( $preklad, $text, $kontext, $domena ) {
	if ( 'Divi' === $domena && 'Open Sans font: on or off' === $kontext ) {
		return 'off';
	}
	return $preklad;
}, 10, 4 );


/**
 * 2)+3) GRID Nastavení / ACF options page — PŘESUNUTO do gridhotel-core
 * ≥ 2.0.0 (inc/options-page.php), stejné field names/keys, žádná migrace
 * dat. Theme už 'grid-options' ani acf-json cestu neregistruje. Zbytky
 * acf-json/ (group_grid_options.json, group_grid_content.json,
 * legacy group_grid_menu.json) byly z theme smazané — jsou teď jen v
 * gridhotel-core (první dva) nebo úplně superseded (group_grid_menu, GARRY
 * Týdenní menu má vlastní nezávislý datový model).
 */

/* ------------------------------------------------------------------
 * 4) Malý fallback helper pro čtení ACF, jen pro sekce theme (šablony
 *    single/archive/taxonomy níže), které GRID-SUITE-02 ještě nepřevzala.
 *    Nové čtení má jít přes gridhotel_get_option() (Core), tenhle helper
 *    zůstává jako záložní cesta, kdyby Core nebyl aktivní.
 * ------------------------------------------------------------------ */
function grid_field( $name, $default = '', $id = 'option' ) {
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $name, $id );
		if ( $v !== null && $v !== '' && $v !== false ) return $v;
	}
	return $default;
}

/**
 * 5) Zbylé shortcody/helpery — POUZE to, co ještě potřebují šablony
 * single-grid_experience.php/archive-grid_experience.php/taxonomy-grid_room_cat.php
 * (grid_pf, grid_exp_defaults, grid_rezervace_url, grid_room_compare_table…).
 * Všech 30 hlavních grid_* shortcodů (grid_hero, grid_rooms, grid_season…)
 * PŘEVZALA gridhotel-components ≥ 1.0.0 a byly odsud odstraněny — viz
 * inc/shortcodes.php (nově jen ~150 řádků helperů, dřív ~1791 řádků).
 */
require_once get_stylesheet_directory() . '/inc/shortcodes.php';
require_once get_stylesheet_directory() . '/inc/errors.php';

/* ------------------------------------------------------------------
 * 6) Obsahová šířka webu (sladěno s návrhem)
 *    1200 = doporučeno | 1280 = kompromis | 1320 = 1:1 návrh
 *    Čte přes gridhotel_get_option() (Core), pokud je aktivní — přímé
 *    get_field() zůstává jen jako fallback bez Core (GRID-SUITE-02 §8:
 *    frontend nemá číst ACF přímo, pokud existuje Core API).
 * ------------------------------------------------------------------ */
add_action( 'wp_head', function () {
	$w = function_exists( 'gridhotel_get_option' ) ? (int) gridhotel_get_option( 'sirka_webu', 1280 ) : (int) grid_field( 'sirka_webu', 1280 );
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

/**
 * 11) Jazykový přepínač (Polylang) — PŘESUNUTO do gridhotel-components ≥ 1.0.1
 * (gridc_lang_switch_urls(), počítá se server-side rovnou v PHP). Dřívější
 * mechanismus (window.gridLangUrls injektovaný v wp_head, dosazovaný teprve
 * JS v grid.js) byl skutečná chyba, ne jen jiné umístění — bez JS jazykový
 * přepínač vůbec nefungoval. Odstraněno odsud i z assets/js/grid.js.
 */

/**
 * 11b) Hlavní menu jako WP menu (lokace „grid-hlavni") — registrace lokace
 * zůstává v theme (standardní WP konvence, theme vlastní strukturu menu).
 * Samotný shortcode [grid_menu_hlavni] ale teď primárně vykresluje
 * gridhotel-components ≥ 1.0.0 (gridc_render_hlavni_menu()) — funkce tady
 * zůstává jen jako interní volání pro Divi TB kompatibilní vrstvu níže
 * (bod 12), ne jako konkurenční shortcode registrace.
 */
add_action( 'after_setup_theme', function () {
	register_nav_menus( array( 'grid-hlavni' => 'Hlavní menu (horní lišta)' ) );
} );
function grid_render_hlavni_menu( $atts = array() ) {
	$locations = get_nav_menu_locations();
	$menu_id   = $locations['grid-hlavni'] ?? 0;
	$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
	if ( ! $items ) {
		return '';
	}

	$deti = array();
	foreach ( $items as $it ) {
		$rodic = (int) $it->menu_item_parent;
		if ( $rodic ) {
			$deti[ $rodic ][] = $it;
		}
	}

	$rozbalit = array( 'cs' => 'Rozbalit podmenu', 'en' => 'Expand submenu', 'de' => 'Untermenü öffnen' );
	$jazyk    = function_exists( 'grid_lang' ) ? grid_lang() : 'cs';
	$popisek  = $rozbalit[ $jazyk ] ?? $rozbalit['cs'];

	$out = array();
	foreach ( $items as $it ) {
		if ( (int) $it->menu_item_parent ) {
			continue; // podpoložky se vykreslují u svého rodiče
		}
		$moje = $deti[ (int) $it->ID ] ?? array();
		if ( ! $moje ) {
			$out[] = '<a href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>';
			continue;
		}
		$pod = '';
		foreach ( $moje as $d ) {
			$pod .= '<a href="' . esc_url( $d->url ) . '">' . esc_html( $d->title ) . '</a>';
		}
		$out[] = '<div class="ma-item">'
			. '<a class="ma-link" href="' . esc_url( $it->url ) . '">' . esc_html( $it->title ) . '</a>'
			. '<button type="button" class="ma-toggle" aria-expanded="false" aria-label="' . esc_attr( $popisek ) . '">'
			. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6"/></svg>'
			. '</button>'
			. '<div class="ma-sub">' . $pod . '</div>'
			. '</div>';
	}
	return implode( ' ', $out );
}
if ( ! shortcode_exists( 'grid_menu_hlavni' ) ) { add_shortcode( 'grid_menu_hlavni', 'grid_render_hlavni_menu' ); } // GRID-SUITE-09 §5: záložní síť, gridhotel-components registruje tenhle tag jako první

/* ------------------------------------------------------------------
 * 12) Shortcody v Theme Builder layoutech (hlavička/patička) —
 *     Divi 5 je v TB obsahu samo nespouští, na stránkách ano.
 * ------------------------------------------------------------------ */
add_filter( 'et_builder_render_layout', 'do_shortcode', 12 ); // Divi 4 cesta
add_filter( 'render_block', function ( $content, $block ) {
	if ( is_admin() ) return $content;
	if ( strpos( (string) ( $block['blockName'] ?? '' ), 'divi/' ) !== 0 ) return $content;
	if ( strpos( $content, '[grid_' ) === false ) return $content;
	return do_shortcode( $content );
}, 20, 2 ); // Divi 5 bloky (Theme Builder)
/* Divi 5 TB renderer shortcody nespouští vůbec → tokeny v hlavičce/patičce
   nahradíme v celém výstupu; náhrady předpočítáme v wp_head (shortcody tam žijí). */
add_action( 'template_redirect', function () {
	if ( is_admin() ) return;
	/* FF newsletter v patičce: spustíme shortcode TEĎ (assety se stihnou zařadit) — ZVLÁŠŤ pro
	   každý jazyk. Token [grid_ff_newsletter] se v šabloně vyskytuje 3× (jednou v každém
	   .grid-lang-cs/en/de bloku patičky, v tomto pořadí) — každý výskyt musí dostat SVOU
	   jazykovou mutaci formuláře, jinak by 3 kopie stejného formuláře měly identické HTML id
	   (neplatné duplicitní ID), než je grid.js později odstraní podle aktivního jazyka. */
	$ff_newsletter_by_lang = array();
	if ( shortcode_exists( 'fluentform' ) ) {
		$ffmap = (array) get_option( 'grid_ff_forms', array() );
		foreach ( array( 'cs', 'en', 'de' ) as $l ) {
			$fid = (int) ( $ffmap['newsletter'][ $l ] ?? 0 );
			if ( $fid ) $ff_newsletter_by_lang[ $l ] = do_shortcode( '[fluentform id=' . $fid . ']' );
		}
	}
	ob_start( function ( $html ) use ( $ff_newsletter_by_lang ) {
		$map = array(
			'[grid_paticka_kontakt]' => function_exists( 'grid_sc_footer_kontakt' ) ? grid_sc_footer_kontakt() : '',
			'[grid_socials]'         => function_exists( 'grid_sc_socials' ) ? grid_sc_socials() : '',
			'[grid_menu_hlavni]'     => grid_render_hlavni_menu(),
		);
		foreach ( $map as $token => $out ) {
			if ( strpos( $html, $token ) !== false ) $html = str_replace( $token, (string) $out, $html );
		}
		/* Token může v TB obsahu nést atribut lang="cs" (přímé určení mutace) — regex proto
		   musí matchovat i s atributem, ne jen holé [grid_ff_newsletter]. Když je lang uveden
		   a známe pro něj formulář, použije se přesně on; jinak (holý token / neznámý jazyk)
		   padáme na první dostupnou mutaci jako bezpečný fallback. */
		if ( $ff_newsletter_by_lang && preg_match( '~\[grid_ff_newsletter(?:\s+[^\]]*)?\]~', $html ) ) {
			$html = preg_replace_callback( '~\[grid_ff_newsletter(?:\s+lang=["\']([a-z]{2})["\'])?(?:\s+[^\]]*)?\]~', function ( $m ) use ( $ff_newsletter_by_lang ) {
				$lang = $m[1] ?? '';
				if ( $lang && isset( $ff_newsletter_by_lang[ $lang ] ) ) return $ff_newsletter_by_lang[ $lang ];
				return reset( $ff_newsletter_by_lang ) ?: '';
			}, $html );
		}
		return $html;
	} );
}, 1 );

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

/* Trvalá přesměrování zrušených nebo přejmenovaných URL. Stará adresa může
 * být v tištěné komunikaci, v e-mailech nebo v záložkách návštěvníků —
 * 301 ji drží funkční a předá i případnou SEO hodnotu.
 *
 * /vseobecne-obchodni-podminky/ (stránka 340) byla obsahově totožná
 * s /ubytovaci-a-reklamacni-rad/ (293): stejný text i stejný nadpis H1,
 * jen bez anglické a německé verze a bez správných tříd rozvržení.
 * Nic na ni neodkazovalo, patička míří na 293. Odpublikováno 2026-09.
 */
add_action( 'template_redirect', function () {
	$redirects = array(
		'/vseobecne-obchodni-podminky/' => '/ubytovaci-a-reklamacni-rad/',
	);
	$uri = untrailingslashit( strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), '?' ) ) . '/';
	if ( isset( $redirects[ $uri ] ) ) {
		wp_safe_redirect( home_url( $redirects[ $uri ] ), 301 );
		exit;
	}
}, 0 );

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

/* Přesměrování starých URL kategorií pokojů (301).
 * Kategorie se v září 2026 přejmenovaly podle Booking.com: „Superior Plus“ se stal
 * „Superior s terasou“ a sloučené „Apartmá a Apartmá Superior“ se rozdělilo na dvě
 * samostatné kategorie. Mapu starý→nový slug zapisuje skript při migraci dat, aby
 * odkazy z Bookingu, Googlu a starých rezervačních e-mailů nekončily na 404. */
add_action( 'template_redirect', function () {
	if ( is_admin() || ! is_404() ) return;
	$mapa = get_option( 'grid_presmerovani_pokoju', array() );
	if ( ! is_array( $mapa ) || ! $mapa ) return;

	$cesta = trim( (string) parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
	$casti = explode( '/', $cesta );
	$slug  = end( $casti );
	if ( $slug === '' || empty( $mapa[ $slug ] ) ) return;

	$novy = get_term_by( 'slug', $mapa[ $slug ], 'grid_room_cat' );
	if ( ! $novy || is_wp_error( $novy ) ) return;
	$url = get_term_link( $novy );
	if ( is_wp_error( $url ) ) return;

	wp_safe_redirect( $url, 301 );
	exit;
}, 1 );

/* ============================================================
   PŘÍSTUPNOST (WCAG 2.2 AA) — audit 7. 9. 2026
   ============================================================ */

/**
 * Viewport bez zákazu zvětšení (WCAG 1.4.4 Změna velikosti textu).
 *
 * Divi vypisuje `maximum-scale=1.0, user-scalable=0`, čímž slabozrakým
 * uživatelům zakazuje přiblížení prsty. Nahrazujeme vlastní hlavičkou.
 */
add_action( 'init', function () {
	remove_action( 'wp_head', 'et_add_viewport_meta' );
} );
add_action( 'wp_head', function () {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
}, 1 );

/**
 * Odkaz „Přeskočit na obsah" (WCAG 2.4.1 Přeskočení bloků).
 *
 * Bez něj musí uživatel klávesnice projít celé menu (14 položek) na každé
 * stránce. Cíl #obsah přidává grid.js na první sekci obsahu — hlavičku
 * skládá Divi Theme Builder, takže do ní nejde vložit <main> ze šablony.
 */
add_action( 'wp_body_open', function () {
	$texty = array( 'cs' => 'Přeskočit na obsah', 'en' => 'Skip to content', 'de' => 'Zum Inhalt springen' );
	$lang  = function_exists( 'pll_current_language' ) ? ( pll_current_language() ?: 'cs' ) : 'cs';
	$text  = isset( $texty[ $lang ] ) ? $texty[ $lang ] : $texty['cs'];
	$popis = array( 'cs' => 'Přeskočit navigaci', 'en' => 'Skip navigation', 'de' => 'Navigation überspringen' );
	printf(
		'<nav class="skip-nav" aria-label="%s"><a class="skip-link" href="#obsah">%s</a></nav>' . "\n",
		esc_attr( isset( $popis[ $lang ] ) ? $popis[ $lang ] : $popis['cs'] ),
		esc_html( $text )
	);
}, 1 );

/**
 * Hlavní oblast a jedinečné landmarky (WCAG 1.3.1, 2.4.1).
 *
 * Divi Theme Builder obaluje hlavičku i patičku do dalšího <header>/<footer>,
 * takže dokument má dva bannery a dvě contentinfo oblasti a obsah stránky
 * neleží v žádném landmarku. Vnější obaly zneplatníme a obsah zabalíme
 * do <main id="obsah">.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || is_feed() || is_robots() || ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) ) {
		return;
	}
	ob_start( 'grid_a11y_landmarky' );
} );

/**
 * Přepis obalů Theme Builderu na výstupu.
 *
 * Divi skládá hlavičku i patičku do <header>/<footer>, uvnitř kterých leží
 * naše vlastní <header id="topbar"> a <footer id="kontakt"> — dokument tak má
 * dva bannery a dvě contentinfo oblasti a obsah stránky neleží v žádné
 * oblasti. Vnější obaly proto měníme na neutrální <div role="presentation">
 * a #main-content balíme do <main id="obsah">, na který míří odkaz
 * „Přeskočit na obsah". Řetězce odpovídají výstupu Divi 5.11; když se změní,
 * filtr jen nic nenahradí a stránka zůstane funkční.
 */
function grid_a11y_landmarky( $html ) {
	if ( false === strpos( $html, 'et-l--header' ) ) {
		return $html;
	}
	/* Obal hlavičky i patičky zůstává <header>/<footer> — jen se z nich sundá
	   role orientačního bodu, aby nevznikly dva bannery (vlastní lišta uvnitř
	   je taky <header>). Dřív se tu tagy měnily na <div>: obal tím přišel
	   o position:fixed a z-index navázané na typ elementu a celá horní lišta
	   zmizela pod hero sekcí. */
	$nahrady = array(
		'<header class="et-l et-l--header">'  => '<header class="et-l et-l--header" role="presentation">',
		'<div id="main-content">'             => '<main id="obsah" tabindex="-1"><div id="main-content">',
		"<footer class=\"et-l et-l--footer\">" => "</main>\n\t<footer class=\"et-l et-l--footer\" role=\"presentation\">",
	);
	foreach ( $nahrady as $co => $cim ) {
		$pozice = strpos( $html, $co );
		if ( false !== $pozice ) {
			$html = substr_replace( $html, $cim, $pozice, strlen( $co ) );
		}
	}
	return $html;
}
