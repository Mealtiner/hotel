<?php
/**
 * Plugin Name:       GARRY – Denní menu
 * Plugin URI:        https://www.garry.cz
 * Description:       Správa jídelníčku a nápojového lístku pro víc provozů (restaurace, bar…): denní menu Po–Ne, celotýdenní nabídka, stálá nabídka, večerní menu a nápojový lístek, každý provoz nezávisle, ve 3 jazycích (CZ/EN/DE).
 * Version:           1.8.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-denni-menu
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================================
 * GARRY – Denní menu (víc provozů, CZ/EN/DE) — datový model
 * ============================================================================
 * Option 'garry_menu' je od schema 2 strukturovaná jako { schema, venues:
 * { <slug>: { name, enabled, week, days, stala, typy, napoje } }, venue_order }.
 * 'days' zahrnuje i pseudo-den 'tyden' (celotýdenní nabídka), přesně jako
 * dřív – jen se řídí přes enabled['tydenni'], jestli se vůbec nabízí.
 * ============================================================================ */

define( 'GARRY_MENU_VER', '1.8.0' );
define( 'GARRY_MENU_OPT', 'garry_menu' );
define( 'GARRY_MENU_SCHEMA', 2 );

/* Dny: klíč => [CZ, EN, DE] */
function garry_menu_days() {
	return array(
		'po'    => array( 'Pondělí',             'Monday',        'Montag' ),
		'ut'    => array( 'Úterý',               'Tuesday',       'Dienstag' ),
		'st'    => array( 'Středa',              'Wednesday',     'Mittwoch' ),
		'ct'    => array( 'Čtvrtek',             'Thursday',      'Donnerstag' ),
		'pa'    => array( 'Pátek',               'Friday',        'Freitag' ),
		'so'    => array( 'Sobota',              'Saturday',      'Samstag' ),
		'ne'    => array( 'Neděle',              'Sunday',        'Sonntag' ),
		'tyden' => array( 'Celotýdenní nabídka', 'All-week menu', 'Wochenkarte' ),
	);
}
/* Typy chodů: klíč => [CZ, EN, DE] */
function garry_menu_types() {
	return array(
		'polevka' => array( 'Polévka',     'Soup',        'Suppe' ),
		'hlavni'  => array( 'Hlavní chod', 'Main course', 'Hauptgericht' ),
		'dezert'  => array( 'Dezert',      'Dessert',     'Dessert' ),
	);
}
/* Kategorie stálé nabídky: klíč => [CZ, EN, DE] (výchozí; vlastní lze přidat v adminu) */
function garry_menu_stala_default_typy() {
	return array(
		array( 'key' => 'predkrm', 'cz' => 'Předkrmy',    'en' => 'Starters',     'de' => 'Vorspeisen' ),
		array( 'key' => 'polevka', 'cz' => 'Polévky',     'en' => 'Soups',        'de' => 'Suppen' ),
		array( 'key' => 'hlavni',  'cz' => 'Hlavní chody','en' => 'Main courses', 'de' => 'Hauptgerichte' ),
		array( 'key' => 'priloha', 'cz' => 'Přílohy',     'en' => 'Side dishes',  'de' => 'Beilagen' ),
		array( 'key' => 'dezert',  'cz' => 'Dezerty',     'en' => 'Desserts',     'de' => 'Desserts' ),
	);
}
/* Kategorie večerního menu: klíč => [CZ, EN, DE] (výchozí; vlastní lze přidat v adminu — stejný vzor jako stálá nabídka). */
function garry_menu_vecerni_default_typy() {
	return array(
		array( 'key' => 'predkrm', 'cz' => 'Předkrmy',    'en' => 'Starters',     'de' => 'Vorspeisen' ),
		array( 'key' => 'polevka', 'cz' => 'Polévky',     'en' => 'Soups',        'de' => 'Suppen' ),
		array( 'key' => 'hlavni',  'cz' => 'Hlavní chody','en' => 'Main courses', 'de' => 'Hauptgerichte' ),
		array( 'key' => 'priloha', 'cz' => 'Přílohy',     'en' => 'Side dishes',  'de' => 'Beilagen' ),
		array( 'key' => 'omacka',  'cz' => 'Omáčky',      'en' => 'Sauces',       'de' => 'Soßen' ),
		array( 'key' => 'dezert',  'cz' => 'Dezerty',     'en' => 'Desserts',     'de' => 'Desserts' ),
	);
}
/* Výchozí štítky nápojového lístku – standardní členění, vlastní lze přidat v adminu (stejný vzor jako stálá nabídka). */
function garry_menu_napoje_default_tags() {
	return array(
		array( 'key' => 'bila-vina',     'cz' => 'Bílá vína',            'en' => 'White wines',              'de' => 'Weißweine' ),
		array( 'key' => 'cervena-vina',  'cz' => 'Červená vína',         'en' => 'Red wines',                 'de' => 'Rotweine' ),
		array( 'key' => 'ruzova-vina',   'cz' => 'Růžová vína',          'en' => 'Rosé wines',                 'de' => 'Roséweine' ),
		array( 'key' => 'sekty',         'cz' => 'Sekty a šampaňské',    'en' => 'Sparkling wine & champagne', 'de' => 'Sekt & Champagner' ),
		array( 'key' => 'piva',          'cz' => 'Piva',                  'en' => 'Beers',                      'de' => 'Biere' ),
		array( 'key' => 'whisky',        'cz' => 'Whisky',                'en' => 'Whisky',                     'de' => 'Whisky' ),
		array( 'key' => 'rum',           'cz' => 'Rum',                   'en' => 'Rum',                        'de' => 'Rum' ),
		array( 'key' => 'gin',           'cz' => 'Gin',                   'en' => 'Gin',                        'de' => 'Gin' ),
		array( 'key' => 'vodka',         'cz' => 'Vodka',                 'en' => 'Vodka',                      'de' => 'Wodka' ),
		array( 'key' => 'koktejly',      'cz' => 'Koktejly',              'en' => 'Cocktails',                  'de' => 'Cocktails' ),
		array( 'key' => 'nealko',        'cz' => 'Nealkoholické nápoje',  'en' => 'Non-alcoholic drinks',       'de' => 'Alkoholfreie Getränke' ),
		array( 'key' => 'horke-napoje',  'cz' => 'Horké nápoje a káva',   'en' => 'Hot drinks & coffee',        'de' => 'Heißgetränke & Kaffee' ),
	);
}
function garry_menu_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_menu_lang_idx() {
	$l = garry_menu_lang();
	return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 );
}
/* Týden z hodnoty: datum YYYY-MM-DD (kalendářní výběr — libovolný den v týdnu)
 * nebo legacy ISO 2026-W31 → [DateTime pondělí, DateTime neděle] */
function garry_menu_week_range( $week ) {
	$week = (string) $week;
	try {
		if ( preg_match( '/^(\d{4})-W(\d{2})$/', $week, $m ) ) {
			$po = new DateTime(); $po->setISODate( (int) $m[1], (int) $m[2] );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $week ) ) {
			$po = new DateTime( $week );
			$po->modify( 'monday this week' );
		} else {
			return null;
		}
		$ne = clone $po; $ne->modify( '+6 days' );
		return array( $po, $ne );
	} catch ( Exception $e ) { return null; }
}

function garry_menu_empty_venue( $name ) {
	return array(
		'name'         => (string) $name,
		'enabled'      => array( 'denni' => true, 'tydenni' => true, 'stala' => true, 'vecerni' => true, 'napoje' => false ),
		'week'         => '',
		'days'         => array(),
		'stala'        => array(),
		'typy'         => garry_menu_stala_default_typy(),
		'vecerni'      => array(),
		'vecerni_typy' => garry_menu_vecerni_default_typy(),
		'napoje'       => array( 'tags' => garry_menu_napoje_default_tags(), 'items' => array() ),
	);
}

/**
 * Čistě čtecí přístup k celé struktuře – normalizuje chybějící dílčí klíče
 * uvnitř existujících provozů (např. po přidání nápojového lístku do starší
 * instalace), ale sám žádný provoz "od oka" nevymýšlí. Prázdný seznam
 * provozů (čerstvá instalace před migrací, nebo administrátor smazal
 * úplně všechny) se řeší v adminu tlačítkem "Přidat provoz", ne tichým
 * naordinováním jednoho výchozího.
 */
function garry_menu_get_all() {
	$o = get_option( GARRY_MENU_OPT, array() );
	if ( ! is_array( $o ) ) $o = array();
	$o = wp_parse_args( $o, array( 'schema' => 0, 'venues' => array(), 'venue_order' => array() ) );
	if ( ! is_array( $o['venues'] ) ) $o['venues'] = array();

	foreach ( $o['venues'] as $slug => $venue ) {
		if ( ! is_array( $venue ) ) { unset( $o['venues'][ $slug ] ); continue; }
		$defaults = garry_menu_empty_venue( $venue['name'] ?? $slug );
		$venue = wp_parse_args( $venue, $defaults );
		if ( empty( $venue['typy'] ) || ! is_array( $venue['typy'] ) ) $venue['typy'] = garry_menu_stala_default_typy();
		if ( ! is_array( $venue['vecerni'] ) ) $venue['vecerni'] = array();
		if ( empty( $venue['vecerni_typy'] ) || ! is_array( $venue['vecerni_typy'] ) ) $venue['vecerni_typy'] = garry_menu_vecerni_default_typy();
		if ( ! is_array( $venue['napoje'] ) ) $venue['napoje'] = $defaults['napoje'];
		$venue['napoje'] = wp_parse_args( $venue['napoje'], $defaults['napoje'] );
		if ( empty( $venue['napoje']['tags'] ) || ! is_array( $venue['napoje']['tags'] ) ) $venue['napoje']['tags'] = garry_menu_napoje_default_tags();
		if ( ! is_array( $venue['napoje']['items'] ) ) $venue['napoje']['items'] = array();
		if ( ! is_array( $venue['enabled'] ) ) $venue['enabled'] = $defaults['enabled'];
		$venue['enabled'] = wp_parse_args( $venue['enabled'], $defaults['enabled'] );
		$o['venues'][ $slug ] = $venue;
	}

	$order = array_values( array_filter( (array) $o['venue_order'], function ( $s ) use ( $o ) { return isset( $o['venues'][ $s ] ); } ) );
	foreach ( array_keys( $o['venues'] ) as $slug ) {
		if ( ! in_array( $slug, $order, true ) ) $order[] = $slug;
	}
	$o['venue_order'] = $order;

	return $o;
}

function garry_menu_get_venue( $slug ) {
	$all = garry_menu_get_all();
	return isset( $all['venues'][ $slug ] ) ? $all['venues'][ $slug ] : null;
}

/**
 * Ordered [slug => název] pro dynamické taby v adminu (GARRY Nastavení i
 * GRID Nastavení) i pro select "Provoz" v Divi modulech.
 */
function garry_menu_venue_options() {
	$all = garry_menu_get_all();
	$out = array();
	foreach ( $all['venue_order'] as $slug ) {
		$out[ $slug ] = $all['venues'][ $slug ]['name'];
	}
	return $out;
}

/**
 * Vlastní taby pro LocalAdmin.php (viz includes/framework-v23/LocalAdmin.php
 * a FrameworkBridge.php – čtou tuhle funkci přes $config['own_tabs']).
 * Provozy + pevná položka "Nastavení" (správa provozů) na konci vlastní
 * části, PŘED sdílenými Přehled/Info/Log.
 */
function garry_menu_own_tabs() {
	$tabs = garry_menu_venue_options();
	$tabs['settings'] = 'Nastavení';
	return $tabs;
}

/**
 * Migrace ze staré ploché struktury (jeden jídelníček) na multi-venue
 * (schema 2). Idempotentní – běží jen dokud 'schema' < GARRY_MENU_SCHEMA.
 * Stejný vzor jako garry_default_run_upgrade_routine() v garry-default.php.
 *
 * Krok 2 (přepis post_content) řeší situaci, kdy administrátor v obsahu
 * webu použil holý shortcode [grid_menu_tydne] bez atributů – ten od téhle
 * verze vyžaduje `provoz`, takže bez přepisu by na webu přestal cokoli
 * vykreslovat. Nahradí se JEN přesný literální řetězec bez atributů, takže
 * shortcody, které atribut už mají, zůstanou nedotčené.
 */
function garry_menu_run_upgrade_routine() {
	$raw = get_option( GARRY_MENU_OPT, array() );
	$schema = is_array( $raw ) && isset( $raw['schema'] ) ? (int) $raw['schema'] : 0;
	if ( $schema >= GARRY_MENU_SCHEMA ) {
		return;
	}

	if ( is_array( $raw ) && ( ! empty( $raw['week'] ) || ! empty( $raw['days'] ) || ! empty( $raw['stala'] ) ) ) {
		// Stará plochá data existují – zabalit jako první provoz.
		$venue = garry_menu_empty_venue( 'Hotelová restaurace' );
		$venue['week']  = isset( $raw['week'] ) ? $raw['week'] : '';
		$venue['days']  = isset( $raw['days'] ) && is_array( $raw['days'] ) ? $raw['days'] : array();
		$venue['stala'] = isset( $raw['stala'] ) && is_array( $raw['stala'] ) ? $raw['stala'] : array();
		if ( ! empty( $raw['typy'] ) && is_array( $raw['typy'] ) ) $venue['typy'] = $raw['typy'];
		$new = array(
			'schema'      => GARRY_MENU_SCHEMA,
			'venues'      => array( 'hotelova-restaurace' => $venue ),
			'venue_order' => array( 'hotelova-restaurace' ),
		);
	} elseif ( is_array( $raw ) && ! empty( $raw['venues'] ) ) {
		// Už multi-venue tvar, jen chybí/je stará hodnota schema.
		$new = wp_parse_args( $raw, array( 'venues' => array(), 'venue_order' => array() ) );
		$new['schema'] = GARRY_MENU_SCHEMA;
	} else {
		// Čerstvá instalace bez dat – jeden prázdný startovní provoz.
		$new = array(
			'schema'      => GARRY_MENU_SCHEMA,
			'venues'      => array( 'hotelova-restaurace' => garry_menu_empty_venue( 'Hotelová restaurace' ) ),
			'venue_order' => array( 'hotelova-restaurace' ),
		);
	}
	update_option( GARRY_MENU_OPT, $new, false );

	if ( isset( $new['venues']['hotelova-restaurace'] ) ) {
		global $wpdb;
		$affected = $wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
			'[grid_menu_tydne]',
			'[grid_menu_tydne provoz="hotelova-restaurace"]',
			'%[grid_menu_tydne]%'
		) );
		if ( $affected ) {
			update_option( 'garry_menu_migration_notice', (int) $affected, false );
		}
	}
}
add_action( 'admin_init', 'garry_menu_run_upgrade_routine', 5 );

add_action( 'admin_notices', function () {
	$count = get_option( 'garry_menu_migration_notice' );
	if ( ! $count || ! current_user_can( 'manage_options' ) ) return;
	printf(
		'<div class="notice notice-info is-dismissible"><p>GARRY – Denní menu: přechod na více provozů. Na %1$d stránkách byl shortcode <code>[grid_menu_tydne]</code> automaticky doplněn o <code>provoz="hotelova-restaurace"</code>, aby jídelníček na webu dál fungoval beze změny.</p></div>',
		(int) $count
	);
	delete_option( 'garry_menu_migration_notice' );
} );

/**
 * Lokální náhrada bývalých Garry_Promotion_Registry::STAFF_CAPABILITY a
 * grid_visible() – Framework 2.3 nesmí definovat sdílenou třídu (viz
 * docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md), takže tahle funkčnost (personál
 * hotelu smí spravovat jídelníček v „GRID Nastavení") teď žije jen tady,
 * beze změny chování. Čte STEJNOU option 'garry_grid_visibility' ve
 * stejném formátu, takže dřívější uložené nastavení zůstává v platnosti.
 *
 * Od verze s granulárním oprávněním na kartu (viz GARRY – GRID Core) už
 * tahle capability není plošné edit_others_posts, ale vlastní
 * garry_grid_manage_denni_menu – definováno PŘED voláním bootstrap() níže,
 * protože descriptor frameworku ho čte v okamžiku volání (viz
 * includes/framework-v23/bootstrap.php). Je to jedna capability pro CELÝ
 * plugin (všechny provozy najednou) – oprávnění na jednotlivý provoz zvlášť
 * je záměrně mimo rozsah téhle verze.
 */
define( 'GARRY_DENNI_MENU_STAFF_CAP', 'garry_grid_manage_denni_menu' );

require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\DenniMenu\V23\bootstrap( __FILE__, 'garry_menu_route_admin', 'Jídelní lístek' );

/**
 * Administrátor má ke kartě přístup vždy (SEC-SELF-001 vzor z garry-default)
 * – capabilitu při aktivaci přidá roli Administrator. Přidělení komukoli
 * dalšímu řeší jen obrazovka „Oprávnění personálu" v GARRY – GRID Core.
 */
register_activation_hook( __FILE__, function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_DENNI_MENU_STAFF_CAP ) ) {
		$role->add_cap( GARRY_DENNI_MENU_STAFF_CAP );
	}
} );

/**
 * Self-healing doplněk: register_activation_hook() se spustí jen při
 * skutečném přechodu neaktivní → aktivní, ne při pouhém přepsání souborů
 * pluginu beze změny stavu aktivace – bez tohohle by administrátor novou
 * capabilitu nikdy nedostal a „Jídelní lístek" by mu z GRID Nastavení
 * i z GARRY Nastavení zmizel (viz current_user_can() výše).
 */
add_action( 'admin_init', function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_DENNI_MENU_STAFF_CAP ) ) {
		$role->add_cap( GARRY_DENNI_MENU_STAFF_CAP );
	}
} );

function garry_denni_menu_grid_visible() {
	$v = get_option( 'garry_grid_visibility', array() );
	if ( ! is_array( $v ) || ! array_key_exists( 'garry-denni-menu', $v ) ) {
		return true;
	}
	return ! empty( $v['garry-denni-menu'] );
}

function garry_denni_menu_save_grid_visibility() {
	if ( ! isset( $_POST['garry_denni_menu_grid_visibility_nonce'] )
		|| ! wp_verify_nonce( $_POST['garry_denni_menu_grid_visibility_nonce'], 'garry_denni_menu_grid_visibility' )
		|| ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$v = get_option( 'garry_grid_visibility', array() );
	if ( ! is_array( $v ) ) $v = array();
	$v['garry-denni-menu'] = empty( $_POST['garry_denni_menu_grid_visible'] ) ? 0 : 1;
	update_option( 'garry_grid_visibility', $v, false );
	echo '<div class="notice notice-success is-dismissible"><p>Viditelnost v GRID Nastavení uložena.</p></div>';
}

add_action( 'admin_init', function () { register_setting( 'garry_menu_group', GARRY_MENU_OPT, 'garry_menu_sanitize' ); } );
/* personál (Editor) smí ukládat přes options.php */
add_filter( 'option_page_capability_garry_menu_group', function () { return GARRY_DENNI_MENU_STAFF_CAP; } );

/* Druhý vstup: „Denní menu" i jako podpoložka menu „GRID Nastavení" (ACF options
 * page 'grid-options' — tam, kde personál spravuje ostatní obsah hotelu). */
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return; // GRID Nastavení neexistuje
	if ( ! garry_denni_menu_grid_visible() ) return;
	add_submenu_page(
		'grid-options',
		'Jídelní lístek',
		'Jídelní lístek',
		GARRY_DENNI_MENU_STAFF_CAP,
		'garry-denni-menu-grid',
		'garry_menu_grid_admin_page'
	);
}, 100 );

/**
 * Sanitizace jednoho provozu (obsah – dny/tyden/stálá/nápoje), беz jeho
 * name/enabled (ty spravuje jen "Nastavení" tab přes garry_menu_handle_save_venues()).
 */
function garry_menu_sanitize_venue_content( array $in, array $existing ) {
	$out = $existing;
	$types = array_keys( garry_menu_types() );

	$w = (string) ( $in['week'] ?? '' );
	$out['week'] = preg_match( '/^(\d{4}-\d{2}-\d{2}|\d{4}-W\d{2})$/', $w ) ? $w : '';

	$days_out = array();
	foreach ( array_keys( garry_menu_days() ) as $day ) {
		$rows = $in['days'][ $day ] ?? null;
		if ( ! is_array( $rows ) ) continue;
		$clean = array();
		$n = max( count( $rows['typ'] ?? array() ), count( $rows['cz'] ?? array() ) );
		for ( $i = 0; $i < $n; $i++ ) {
			$typ = sanitize_key( $rows['typ'][ $i ] ?? 'hlavni' );
			if ( ! in_array( $typ, $types, true ) ) $typ = 'hlavni';
			$row = array(
				'typ'  => $typ,
				'cz'   => sanitize_text_field( $rows['cz'][ $i ] ?? '' ),
				'en'   => sanitize_text_field( $rows['en'][ $i ] ?? '' ),
				'de'   => sanitize_text_field( $rows['de'][ $i ] ?? '' ),
				'cena' => sanitize_text_field( $rows['cena'][ $i ] ?? '' ),
			);
			if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue;
			$clean[] = $row;
		}
		if ( $clean ) $days_out[ $day ] = $clean;
	}
	$out['days'] = $days_out;

	/* kategorie stálé nabídky (editovatelné, možno přidat vlastní) */
	$ty = $in['typy'] ?? array();
	$n = count( $ty['key'] ?? array() );
	$typy_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $ty['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $ty['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$typy_out[] = array(
			'key' => $key, 'cz' => $cz,
			'en'  => sanitize_text_field( $ty['en'][ $i ] ?? '' ),
			'de'  => sanitize_text_field( $ty['de'][ $i ] ?? '' ),
		);
	}
	if ( ! $typy_out ) $typy_out = garry_menu_stala_default_typy();
	$out['typy'] = $typy_out;
	$typ_keys = wp_list_pluck( $typy_out, 'key' );

	/* položky stálé nabídky */
	$st = $in['stala'] ?? array();
	$n = count( $st['cz'] ?? array() );
	$stala_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'typ'  => in_array( $st['typ'][ $i ] ?? '', $typ_keys, true ) ? $st['typ'][ $i ] : ( $typ_keys[0] ?? 'hlavni' ),
			'cz'   => sanitize_text_field( $st['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $st['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $st['de'][ $i ] ?? '' ),
			'cena' => sanitize_text_field( $st['cena'][ $i ] ?? '' ),
		);
		if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue;
		$stala_out[] = $row;
	}
	$out['stala'] = $stala_out;

	/* kategorie večerního menu (editovatelné, možno přidat vlastní) — stejný vzor jako stálá nabídka */
	$vty = $in['vecerni_typy'] ?? array();
	$n = count( $vty['key'] ?? array() );
	$vtypy_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $vty['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $vty['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$vtypy_out[] = array(
			'key' => $key, 'cz' => $cz,
			'en'  => sanitize_text_field( $vty['en'][ $i ] ?? '' ),
			'de'  => sanitize_text_field( $vty['de'][ $i ] ?? '' ),
		);
	}
	if ( ! $vtypy_out ) $vtypy_out = garry_menu_vecerni_default_typy();
	$out['vecerni_typy'] = $vtypy_out;
	$vtyp_keys = wp_list_pluck( $vtypy_out, 'key' );

	/* položky večerního menu */
	$ve = $in['vecerni'] ?? array();
	$n = count( $ve['cz'] ?? array() );
	$vecerni_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'typ'  => in_array( $ve['typ'][ $i ] ?? '', $vtyp_keys, true ) ? $ve['typ'][ $i ] : ( $vtyp_keys[0] ?? 'hlavni' ),
			'cz'   => sanitize_text_field( $ve['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $ve['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $ve['de'][ $i ] ?? '' ),
			'cena' => sanitize_text_field( $ve['cena'][ $i ] ?? '' ),
		);
		if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue;
		$vecerni_out[] = $row;
	}
	$out['vecerni'] = $vecerni_out;

	/* nápojový lístek – stejný vzor jako stálá nabídka (štítky + položky) */
	$nt = $in['napoje_tags'] ?? array();
	$n = count( $nt['key'] ?? array() );
	$tags_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $nt['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $nt['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$tags_out[] = array(
			'key' => $key, 'cz' => $cz,
			'en'  => sanitize_text_field( $nt['en'][ $i ] ?? '' ),
			'de'  => sanitize_text_field( $nt['de'][ $i ] ?? '' ),
		);
	}
	if ( ! $tags_out ) $tags_out = garry_menu_napoje_default_tags();
	$tag_keys = wp_list_pluck( $tags_out, 'key' );

	$ni = $in['napoje_items'] ?? array();
	$n = count( $ni['cz'] ?? array() );
	$items_out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'tag'  => in_array( $ni['tag'][ $i ] ?? '', $tag_keys, true ) ? $ni['tag'][ $i ] : ( $tag_keys[0] ?? '' ),
			'cz'   => sanitize_text_field( $ni['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $ni['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $ni['de'][ $i ] ?? '' ),
			'cena' => sanitize_text_field( $ni['cena'][ $i ] ?? '' ),
		);
		if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue;
		$items_out[] = $row;
	}
	$out['napoje'] = array( 'tags' => $tags_out, 'items' => $items_out );

	return $out;
}

/**
 * Sanitizace pro register_setting() – čte AKTUÁLNĚ uloženou strukturu a
 * do ní vmerguje jen provoz, jehož formulář byl odeslán (skrytý input
 * venue_slug). Ostatní provozy zůstávají netknuté – bez tohohle by uložení
 * jednoho provozu smazalo data všech ostatních (klasická past WP Settings
 * API u víc-sekčních formulářů).
 */
function garry_menu_sanitize( $in ) {
	$current = garry_menu_get_all();
	$venue_slug = isset( $in['venue_slug'] ) ? sanitize_key( $in['venue_slug'] ) : '';
	if ( '' === $venue_slug || ! isset( $current['venues'][ $venue_slug ] ) ) {
		return $current;
	}
	$current['venues'][ $venue_slug ] = garry_menu_sanitize_venue_content( $in, $current['venues'][ $venue_slug ] );
	$current['schema'] = GARRY_MENU_SCHEMA;
	return $current;
}

/**
 * Správa provozů (přidat/přejmenovat/smazat, zapnout/vypnout typy nabídky) –
 * samostatná admin-post akce, mimo Settings API, protože mění STRUKTURU
 * (které provozy existují), ne obsah jednoho z nich; obsah ostatních
 * provozů zůstává nedotčený.
 */
add_action( 'admin_post_garry_menu_save_venues', 'garry_menu_handle_save_venues' );
function garry_menu_handle_save_venues() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'garry_menu_save_venues' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-denni-menu' ) );
	}

	$all = garry_menu_get_all();

	$action = isset( $_POST['venue_action'] ) ? sanitize_key( $_POST['venue_action'] ) : '';

	if ( 'add' === $action ) {
		$name = sanitize_text_field( wp_unslash( $_POST['new_venue_name'] ?? '' ) );
		if ( '' !== $name ) {
			$slug = sanitize_title( $name );
			$base = $slug; $i = 2;
			while ( '' === $slug || isset( $all['venues'][ $slug ] ) ) { $slug = $base . '-' . $i; $i++; }
			$all['venues'][ $slug ] = garry_menu_empty_venue( $name );
			$all['venue_order'][] = $slug;
		}
	} elseif ( 'delete' === $action ) {
		$slug = sanitize_key( $_POST['venue_slug'] ?? '' );
		if ( isset( $all['venues'][ $slug ] ) && count( $all['venues'] ) > 1 ) {
			unset( $all['venues'][ $slug ] );
			$all['venue_order'] = array_values( array_diff( $all['venue_order'], array( $slug ) ) );
		}
	} else {
		/* Hromadné uložení: názvy, pořadí a zapnuté typy nabídky pro všechny provozy najednou. */
		$names   = isset( $_POST['venue_name'] ) && is_array( $_POST['venue_name'] ) ? wp_unslash( $_POST['venue_name'] ) : array();
		$order   = isset( $_POST['venue_order'] ) && is_array( $_POST['venue_order'] ) ? wp_unslash( $_POST['venue_order'] ) : array();
		$enabled = isset( $_POST['venue_enabled'] ) && is_array( $_POST['venue_enabled'] ) ? $_POST['venue_enabled'] : array();

		foreach ( $all['venues'] as $slug => $venue ) {
			if ( isset( $names[ $slug ] ) ) {
				$name = sanitize_text_field( $names[ $slug ] );
				if ( '' !== $name ) $all['venues'][ $slug ]['name'] = $name;
			}
			foreach ( array( 'denni', 'tydenni', 'stala', 'vecerni', 'napoje' ) as $flag ) {
				$all['venues'][ $slug ]['enabled'][ $flag ] = ! empty( $enabled[ $slug ][ $flag ] );
			}
		}
		$clean_order = array_values( array_filter( array_map( 'sanitize_key', $order ), function ( $s ) use ( $all ) { return isset( $all['venues'][ $s ] ); } ) );
		foreach ( array_keys( $all['venues'] ) as $slug ) {
			if ( ! in_array( $slug, $clean_order, true ) ) $clean_order[] = $slug;
		}
		$all['venue_order'] = $clean_order;
	}

	$all['schema'] = GARRY_MENU_SCHEMA;
	/* garry_menu_sanitize() je zaregistrovaná přes register_setting() pro editor obsahu
	   JEDNOHO provozu (options.php, vyžaduje venue_slug) – WordPress ale sanitize_option_*
	   filtr aplikuje na KAŽDÉ update_option() volání nad touto option, ne jen na to z
	   options.php. Bez odpojení by tenhle přímý zápis (přidání/smazání/hromadné přejmenování
	   provozů, které venue_slug nemá) sanitizace tiše zahodila zpět na starou hodnotu – zápis
	   by "uspěl" (žádná chyba), ale nic by se neuložilo. Skutečná chyba nahlášená z živého webu. */
	remove_filter( 'sanitize_option_' . GARRY_MENU_OPT, 'garry_menu_sanitize' );
	update_option( GARRY_MENU_OPT, $all, false );
	add_filter( 'sanitize_option_' . GARRY_MENU_OPT, 'garry_menu_sanitize' );

	wp_safe_redirect( add_query_arg(
		array( 'page' => 'garry-denni-menu', 'garry_v23_tab' => 'settings', 'venues_saved' => 1 ),
		admin_url( 'admin.php' )
	) );
	exit;
}

/**
 * Router volaný LocalAdmin.php (GARRY Nastavení) pro každý vlastní tab –
 * $tab je buď slug provozu, nebo 'settings'. Viz garry_menu_own_tabs()
 * a includes/framework-v23/LocalAdmin.php.
 */
function garry_menu_route_admin( $tab ) {
	if ( ! current_user_can( GARRY_DENNI_MENU_STAFF_CAP ) ) return;
	if ( 'settings' === $tab ) {
		garry_menu_render_settings_tab();
		return;
	}
	garry_menu_render_venue_editor( $tab );
}

/** "Nastavení" – správa provozů (jen manage_options, ne personál v GRID Nastavení). */
function garry_menu_render_settings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		echo '<div class="wrap"><p>Správa provozů vyžaduje oprávnění administrátora.</p></div>';
		return;
	}
	$all = garry_menu_get_all();
	?>
	<div class="wrap">
		<h1>Nastavení — provozy</h1>
		<p class="description">Každý provoz (restaurace, bar…) má vlastní záložku nahoře a vlastní kombinaci nabídek. Odškrtnutím typu nabídky se jeho záložka u provozu skryje – data zůstávají uložená, jen se nenabízí k editaci ani se nevykreslí na webu.</p>

		<?php if ( isset( $_GET['venues_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Nastavení provozů uloženo.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="garry_menu_save_venues">
			<?php wp_nonce_field( 'garry_menu_save_venues' ); ?>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr><th>Provoz</th><th>Denní menu</th><th>Týdenní nabídka</th><th>Stálá nabídka</th><th>Večerní menu</th><th>Nápojový lístek</th></tr>
				</thead>
				<tbody>
					<?php foreach ( $all['venue_order'] as $slug ) : $v = $all['venues'][ $slug ]; ?>
						<tr>
							<td>
								<input type="hidden" name="venue_order[]" value="<?php echo esc_attr( $slug ); ?>">
								<input type="text" name="venue_name[<?php echo esc_attr( $slug ); ?>]" value="<?php echo esc_attr( $v['name'] ); ?>" style="width:100%">
								<p class="description"><code><?php echo esc_html( $slug ); ?></code></p>
							</td>
							<?php foreach ( array( 'denni' => 'Denní menu', 'tydenni' => 'Týdenní nabídka', 'stala' => 'Stálá nabídka', 'vecerni' => 'Večerní menu', 'napoje' => 'Nápojový lístek' ) as $flag => $label ) : ?>
								<td style="text-align:center">
									<label><input type="checkbox" name="venue_enabled[<?php echo esc_attr( $slug ); ?>][<?php echo esc_attr( $flag ); ?>]" value="1" <?php checked( ! empty( $v['enabled'][ $flag ] ) ); ?>></label>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'Uložit provozy' ); ?>
		</form>

		<h2 style="margin-top:28px">Přidat provoz</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:10px;align-items:center">
			<input type="hidden" name="action" value="garry_menu_save_venues">
			<input type="hidden" name="venue_action" value="add">
			<?php wp_nonce_field( 'garry_menu_save_venues' ); ?>
			<input type="text" name="new_venue_name" placeholder="např. Paddock restaurant" class="regular-text">
			<?php submit_button( 'Přidat', 'secondary', 'submit', false ); ?>
		</form>

		<?php if ( count( $all['venues'] ) > 1 ) : ?>
			<h2 style="margin-top:28px">Smazat provoz</h2>
			<p class="description">Nevratné – smaže veškerý obsah (jídelníček i nápojový lístek) daného provozu.</p>
			<?php foreach ( $all['venue_order'] as $slug ) : $v = $all['venues'][ $slug ]; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:4px 8px 4px 0" onsubmit="return confirm('Opravdu trvale smazat provoz „<?php echo esc_js( $v['name'] ); ?>" a veškerý jeho obsah?');">
					<input type="hidden" name="action" value="garry_menu_save_venues">
					<input type="hidden" name="venue_action" value="delete">
					<input type="hidden" name="venue_slug" value="<?php echo esc_attr( $slug ); ?>">
					<?php wp_nonce_field( 'garry_menu_save_venues' ); ?>
					<?php submit_button( 'Smazat „' . $v['name'] . '"', 'delete small', 'submit', false ); ?>
				</form>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Editor obsahu jednoho provozu (GARRY Nastavení) – Po–Ne / Celotýdenní /
 * Stálá nabídka / Nápojový lístek, jen za typy zapnuté v "Nastavení".
 * Stejná funkce se volá i z GRID Nastavení (garry_menu_grid_admin_page()).
 */
function garry_menu_render_venue_editor( $venue_slug ) {
	$all = garry_menu_get_all();
	if ( ! isset( $all['venues'][ $venue_slug ] ) ) {
		echo '<div class="wrap"><p>Tenhle provoz neexistuje. Vyberte ho v záložkách nahoře, nebo ho vytvořte v Nastavení.</p></div>';
		return;
	}
	$s = $all['venues'][ $venue_slug ];
	$en = $s['enabled'];
	$DAYS = garry_menu_days(); $TYPES = garry_menu_types();
	$range = garry_menu_week_range( $s['week'] );

	$week_days = array_filter( $DAYS, function ( $k ) { return 'tyden' !== $k; }, ARRAY_FILTER_USE_KEY );

	if ( current_user_can( 'manage_options' ) ) {
		garry_denni_menu_save_grid_visibility();
	}
	?>
	<div class="wrap"><h1><?php echo esc_html( $s['name'] ); ?> — jídelníček</h1>
	<p>Vyplňte jídla a nápoje pro tenhle provoz (CZ/EN/DE). Nevyplněný den/kategorie se na webu nezobrazí.</p>
	<?php if ( current_user_can( 'manage_options' ) ) : ?>
	<form method="post" style="margin:10px 0 18px">
		<?php wp_nonce_field( 'garry_denni_menu_grid_visibility', 'garry_denni_menu_grid_visibility_nonce' ); ?>
		<label><input type="checkbox" name="garry_denni_menu_grid_visible" value="1" <?php checked( garry_denni_menu_grid_visible() ); ?>>
		Zobrazit tuto stránku personálu v <strong>GRID Nastavení</strong></label>
		<?php submit_button( 'Uložit viditelnost', 'secondary', 'submit', false ); ?>
	</form>
	<?php endif; ?>
	<form method="post" action="options.php" id="garry-menu-form">
	<?php settings_fields( 'garry_menu_group' ); ?>
	<input type="hidden" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[venue_slug]" value="<?php echo esc_attr( $venue_slug ); ?>">
	<?php
	$week_val = $s['week'];
	if ( $range && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $week_val ) ) $week_val = $range[0]->format( 'Y-m-d' );
	$has_any_tab = $en['denni'] || $en['tydenni'] || $en['stala'] || $en['vecerni'] || $en['napoje'];
	if ( ! $has_any_tab ) :
	?>
		<p class="description">Tenhle provoz nemá zapnutou žádnou nabídku. Zapněte aspoň jednu v <a href="<?php echo esc_url( add_query_arg( 'garry_v23_tab', 'settings' ) ); ?>">Nastavení</a>.</p>
	<?php else : ?>
	<?php if ( $en['denni'] || $en['tydenni'] ) : ?>
	<p style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
	  <label><strong>Platí pro týden:</strong>
	    <input type="date" id="gm-week" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[week]" value="<?php echo esc_attr( $week_val ); ?>">
	  </label>
	  <span class="description" id="gm-week-range"><?php if ( $range ) printf( 'týden %s – %s',
		esc_html( $range[0]->format( 'j. n. Y' ) ), esc_html( $range[1]->format( 'j. n. Y' ) ) );
		else echo 'vyberte v kalendáři libovolný den — týden Po–Ne se dopočítá sám'; ?></span>
	</p>
	<?php endif; ?>
	<h2 class="nav-tab-wrapper" id="gm-tabs" style="margin-bottom:0">
	  <?php
	  $first = true;
	  if ( $en['denni'] ) : foreach ( $week_days as $key => $names ) :
		$filled = ! empty( $s['days'][ $key ] ); ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="<?php echo esc_attr( $key ); ?>">
	      <?php echo esc_html( $names[0] ); ?><?php if ( $filled ) echo ' <span style="color:#2ecc71">●</span>'; ?>
	    </a>
	  <?php $first = false; endforeach; endif;
	  if ( $en['tydenni'] ) :
		$filled = ! empty( $s['days']['tyden'] ); ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="tyden">Celotýdenní nabídka<?php if ( $filled ) echo ' <span style="color:#2ecc71">●</span>'; ?></a>
	  <?php $first = false; endif;
	  if ( $en['stala'] ) : ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="stala">Stálá nabídka<?php if ( ! empty( $s['stala'] ) ) echo ' <span style="color:#2ecc71">●</span>'; ?></a>
	  <?php $first = false; endif;
	  if ( $en['vecerni'] ) : ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="vecerni">Večerní menu<?php if ( ! empty( $s['vecerni'] ) ) echo ' <span style="color:#2ecc71">●</span>'; ?></a>
	  <?php $first = false; endif;
	  if ( $en['napoje'] ) : ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="napoje">Nápojový lístek<?php if ( ! empty( $s['napoje']['items'] ) ) echo ' <span style="color:#2ecc71">●</span>'; ?></a>
	  <?php endif; ?>
	</h2>
	<?php
	$first = true;
	$render_day_panel = function ( $key, $names ) use ( $s, $TYPES, &$first, $venue_slug ) {
		$rows = $s['days'][ $key ] ?? array();
		if ( ! $rows && 'tyden' !== $key ) $rows = array(
			array( 'typ' => 'polevka', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'hlavni',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'hlavni',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'dezert',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
		);
		if ( ! $rows ) $rows = array( array( 'typ' => 'hlavni', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ) );
		?>
		<div class="gm-day" data-day="<?php echo esc_attr( $key ); ?>" style="<?php echo $first ? '' : 'display:none'; ?>;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
		  <table class="widefat striped gm-table" data-day="<?php echo esc_attr( $key ); ?>">
		    <thead><tr>
		      <th style="width:130px">Chod</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th>
		    </tr></thead>
		    <tbody>
		    <?php foreach ( $rows as $r ) : ?>
		      <tr>
		        <td><select name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][typ][]">
		          <?php foreach ( $TYPES as $tk => $tn ) printf( '<option value="%s" %s>%s</option>', esc_attr( $tk ), selected( $r['typ'], $tk, false ), esc_html( $tn[0] ) ); ?>
		        </select></td>
		        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
		        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
		        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
		        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="145 Kč"></td>
		        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-del" title="Smazat řádek" style="color:#b32d2e">×</button></td>
		      </tr>
		    <?php endforeach; ?>
		    </tbody>
		  </table>
		  <p><button type="button" class="button gm-add" data-day="<?php echo esc_attr( $key ); ?>">+ Přidat řádek</button></p>
		</div>
		<?php
		$first = false;
	};
	if ( $en['denni'] ) foreach ( $week_days as $key => $names ) $render_day_panel( $key, $names );
	if ( $en['tydenni'] ) $render_day_panel( 'tyden', $DAYS['tyden'] );
	?>
	<?php if ( $en['stala'] ) : ?>
	<div class="gm-day" data-day="stala" style="<?php echo $first ? '' : 'display:none'; ?>;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
	  <p class="description">Stálá nabídka (jídelní lístek) — na webu se vypisuje ve 4 sloupcích, položky seskupené podle kategorií. Nevyplněný jazyk = použije se čeština, prázdná cena se nezobrazí.</p>
	  <h3 style="margin:14px 0 6px">Kategorie</h3>
	  <p class="description">Pořadí kategorií zde určuje pořadí na webu. Vlastní kategorii přidáte tlačítkem — klíč se doplní sám z českého názvu.</p>
	  <table class="widefat striped" id="gm-typy" style="max-width:860px">
	    <thead><tr><th style="width:130px">Klíč</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php foreach ( $s['typy'] as $ty ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[typy][key][]" value="<?php echo esc_attr( $ty['key'] ); ?>"></td>
	        <?php foreach ( array( 'cz', 'en', 'de' ) as $l ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[typy][<?php echo $l; ?>][]" value="<?php echo esc_attr( $ty[ $l ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-typ-del" title="Smazat kategorii" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-typ-add">+ Přidat kategorii</button></p>
	  <h3 style="margin:18px 0 6px">Položky</h3>
	  <table class="widefat striped" id="gm-stala">
	    <thead><tr><th style="width:160px">Kategorie</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php $stala_rows = $s['stala'] ?: array( array( 'typ' => $s['typy'][0]['key'] ?? 'hlavni', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ) );
	    foreach ( $stala_rows as $r ) : ?>
	      <tr>
	        <td><select style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][typ][]">
	          <?php foreach ( $s['typy'] as $ty ) printf( '<option value="%s" %s>%s</option>', esc_attr( $ty['key'] ), selected( $r['typ'], $ty['key'], false ), esc_html( $ty['cz'] ) ); ?>
	        </select></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="145 Kč"></td>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-stala-del" title="Smazat položku" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-stala-add">+ Přidat položku</button></p>
	</div>
	<?php $first = false; endif; ?>

	<?php if ( $en['vecerni'] ) : ?>
	<div class="gm-day" data-day="vecerni" style="<?php echo $first ? '' : 'display:none'; ?>;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
	  <p class="description">Večerní menu — na webu se vypisuje ve 4 sloupcích, položky seskupené podle kategorií. Nevyplněný jazyk = použije se čeština, prázdná cena se nezobrazí.</p>
	  <h3 style="margin:14px 0 6px">Kategorie</h3>
	  <p class="description">Pořadí kategorií zde určuje pořadí na webu. Vlastní kategorii přidáte tlačítkem — klíč se doplní sám z českého názvu.</p>
	  <table class="widefat striped" id="gm-vecerni-typy" style="max-width:860px">
	    <thead><tr><th style="width:130px">Klíč</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php foreach ( $s['vecerni_typy'] as $ty ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni_typy][key][]" value="<?php echo esc_attr( $ty['key'] ); ?>"></td>
	        <?php foreach ( array( 'cz', 'en', 'de' ) as $l ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni_typy][<?php echo $l; ?>][]" value="<?php echo esc_attr( $ty[ $l ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-vecerni-typ-del" title="Smazat kategorii" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-vecerni-typ-add">+ Přidat kategorii</button></p>
	  <h3 style="margin:18px 0 6px">Položky</h3>
	  <table class="widefat striped" id="gm-vecerni">
	    <thead><tr><th style="width:160px">Kategorie</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php $vecerni_rows = $s['vecerni'] ?: array( array( 'typ' => $s['vecerni_typy'][0]['key'] ?? 'hlavni', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ) );
	    foreach ( $vecerni_rows as $r ) : ?>
	      <tr>
	        <td><select style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni][typ][]">
	          <?php foreach ( $s['vecerni_typy'] as $ty ) printf( '<option value="%s" %s>%s</option>', esc_attr( $ty['key'] ), selected( $r['typ'], $ty['key'], false ), esc_html( $ty['cz'] ) ); ?>
	        </select></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[vecerni][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="145 Kč"></td>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-vecerni-del" title="Smazat položku" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-vecerni-add">+ Přidat položku</button></p>
	</div>
	<?php $first = false; endif; ?>

	<?php if ( $en['napoje'] ) : ?>
	<div class="gm-day" data-day="napoje" style="<?php echo $first ? '' : 'display:none'; ?>;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
	  <p class="description">Nápojový lístek — na webu se vypisuje ve 4 sloupcích podle štítků. Prázdný štítek (bez položek) se nezobrazí.</p>
	  <h3 style="margin:14px 0 6px">Štítky</h3>
	  <p class="description">Pořadí štítků zde určuje pořadí na webu. Vlastní štítek přidáte tlačítkem — klíč se doplní sám z českého názvu.</p>
	  <table class="widefat striped" id="gm-napoje-typy" style="max-width:860px">
	    <thead><tr><th style="width:130px">Klíč</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php foreach ( $s['napoje']['tags'] as $ty ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_tags][key][]" value="<?php echo esc_attr( $ty['key'] ); ?>"></td>
	        <?php foreach ( array( 'cz', 'en', 'de' ) as $l ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_tags][<?php echo $l; ?>][]" value="<?php echo esc_attr( $ty[ $l ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-napoje-typ-del" title="Smazat štítek" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-napoje-typ-add">+ Přidat štítek</button></p>
	  <h3 style="margin:18px 0 6px">Položky</h3>
	  <table class="widefat striped" id="gm-napoje">
	    <thead><tr><th style="width:160px">Štítek</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php $napoje_rows = $s['napoje']['items'] ?: array( array( 'tag' => $s['napoje']['tags'][0]['key'] ?? '', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ) );
	    foreach ( $napoje_rows as $r ) : ?>
	      <tr>
	        <td><select style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_items][tag][]">
	          <?php foreach ( $s['napoje']['tags'] as $ty ) printf( '<option value="%s" %s>%s</option>', esc_attr( $ty['key'] ), selected( $r['tag'], $ty['key'], false ), esc_html( $ty['cz'] ) ); ?>
	        </select></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_items][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_items][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_items][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[napoje_items][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="85 Kč"></td>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-napoje-del" title="Smazat položku" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-napoje-add">+ Přidat položku</button></p>
	</div>
	<?php endif; ?>

	<?php submit_button( 'Uložit menu' ); ?>
	<h2 style="margin-top:8px" id="gm-preview-h">Náhled na webu (česky)</h2>
	<p class="description" id="gm-preview-sub">Živý náhled — zobrazují se jen vyplněné položky.</p>
	<div id="gm-preview" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;max-width:1400px;background:#F4F2F0;border:1px solid #c3c4c7;border-radius:8px;padding:16px"></div>
	<?php endif; // has_any_tab ?>
	</form></div>
	<?php if ( $has_any_tab ) : ?>
	<script>
	(function(){
	  var wk=document.getElementById('gm-week'), wr=document.getElementById('gm-week-range');
	  if(wk&&wr){ wk.addEventListener('change', function(){
	    if(!wk.value) return;
	    var d=new Date(wk.value+'T12:00:00');
	    var day=(d.getDay()+6)%7;
	    var po=new Date(d); po.setDate(d.getDate()-day);
	    var ne=new Date(po); ne.setDate(po.getDate()+6);
	    var f=function(x){ return x.getDate()+'. '+(x.getMonth()+1)+'.'; };
	    wr.textContent='týden '+f(po)+' – '+f(ne)+' '+ne.getFullYear();
	  }); }
	  var tabs=document.querySelectorAll('#gm-tabs .nav-tab');
	  var gmPreviewH=document.getElementById('gm-preview-h'), gmPreviewSub=document.getElementById('gm-preview-sub');
	  function activeKind(){
	    var active=document.querySelector('#gm-tabs .nav-tab-active');
	    return active ? active.getAttribute('data-day') : '';
	  }
	  function renderActivePreview(){
	    var kind=activeKind();
	    if(gmPreviewH) gmPreviewH.textContent = kind==='stala' ? 'Náhled stálé nabídky (česky)' : (kind==='vecerni' ? 'Náhled večerního menu (česky)' : (kind==='napoje' ? 'Náhled nápojového lístku (česky)' : 'Náhled na webu (česky)'));
	    if(kind==='stala') previewGrouped('#gm-typy', '#gm-stala', '[stala]');
	    else if(kind==='vecerni') previewGrouped('#gm-vecerni-typy', '#gm-vecerni', '[vecerni]');
	    else if(kind==='napoje') previewGrouped('#gm-napoje-typy', '#gm-napoje', '[napoje_items]');
	    else preview();
	  }
	  tabs.forEach(function(t){ t.addEventListener('click', function(e){
	    e.preventDefault();
	    tabs.forEach(function(x){ x.classList.remove('nav-tab-active'); });
	    t.classList.add('nav-tab-active');
	    document.querySelectorAll('.gm-day').forEach(function(d){ d.style.display = d.getAttribute('data-day')===t.getAttribute('data-day') ? '' : 'none'; });
	    renderActivePreview();
	  }); });
	  document.querySelectorAll('.gm-add').forEach(function(btn){ btn.addEventListener('click', function(){
	    var tbl=document.querySelector('.gm-table[data-day="'+btn.getAttribute('data-day')+'"] tbody');
	    var tr=tbl.rows[tbl.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; });
	    tr.querySelector('select').value='hlavni';
	    tbl.appendChild(tr);
	  }); });
	  function wireAdd(id, tbodySel){
	    var btn=document.getElementById(id); if(!btn) return;
	    btn.addEventListener('click', function(){
	      var tb=document.querySelector(tbodySel); var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	      tr.querySelectorAll('input').forEach(function(i){ i.value=''; }); tb.appendChild(tr); renderActivePreview();
	    });
	  }
	  wireAdd('gm-typ-add', '#gm-typy tbody');
	  wireAdd('gm-stala-add', '#gm-stala tbody');
	  wireAdd('gm-vecerni-typ-add', '#gm-vecerni-typy tbody');
	  wireAdd('gm-vecerni-add', '#gm-vecerni tbody');
	  wireAdd('gm-napoje-typ-add', '#gm-napoje-typy tbody');
	  wireAdd('gm-napoje-add', '#gm-napoje tbody');
	  document.addEventListener('click', function(e){
	    if(e.target.classList.contains('gm-up')||e.target.classList.contains('gm-down')){
	      var mtr=e.target.closest('tr'), mtb=mtr.parentNode;
	      if(e.target.classList.contains('gm-up') && mtr.previousElementSibling) mtb.insertBefore(mtr, mtr.previousElementSibling);
	      if(e.target.classList.contains('gm-down') && mtr.nextElementSibling) mtb.insertBefore(mtr.nextElementSibling, mtr);
	      renderActivePreview();
	      return;
	    }
	    if(e.target.classList.contains('gm-typ-del')||e.target.classList.contains('gm-stala-del')||e.target.classList.contains('gm-vecerni-typ-del')||e.target.classList.contains('gm-vecerni-del')||e.target.classList.contains('gm-napoje-typ-del')||e.target.classList.contains('gm-napoje-del')){
	      var tb2=e.target.closest('tbody');
	      if(tb2.rows.length>1) e.target.closest('tr').remove();
	      else tb2.querySelectorAll('input').forEach(function(i){ i.value=''; });
	      renderActivePreview();
	      return;
	    }
	    if(!e.target.classList.contains('gm-del')) return;
	    var tb=e.target.closest('tbody');
	    if(tb.rows.length>1) e.target.closest('tr').remove();
	    else tb.querySelectorAll('input').forEach(function(i){ i.value=''; });
	    renderActivePreview();
	  });

	  /* ---- živý náhled matice dnů (česky) ---- */
	  var DAYS=<?php echo wp_json_encode( array_map( function ( $n ) { return $n[0]; }, garry_menu_days() ) ); ?>;
	  var TYPES=<?php echo wp_json_encode( array_map( function ( $n ) { return $n[0]; }, garry_menu_types() ) ); ?>;
	  function preview(){
	    var box=document.getElementById('gm-preview'); if(!box) return;
	    var h='';
	    Object.keys(DAYS).forEach(function(day){
	      var tbl=document.querySelector('.gm-table[data-day="'+day+'"] tbody'); if(!tbl) return;
	      var groups={};
	      [].forEach.call(tbl.rows, function(tr){
	        var typ=tr.querySelector('select').value;
	        var cz=tr.querySelector('input[name*="[cz]"]').value.trim();
	        var cena=tr.querySelector('input[name*="[cena]"]').value.trim();
	        if(!cz) return;
	        (groups[typ]=groups[typ]||[]).push({n:cz,c:cena});
	      });
	      if(!Object.keys(groups).length) return;
	      var card='<div style="background:#fff;border:1px solid rgba(20,22,25,.12);border-radius:6px;padding:12px 14px">'+
	        '<div style="font-weight:700;color:#16181B;border-bottom:2px solid #C20E1A;padding-bottom:6px;margin-bottom:8px">'+DAYS[day]+'</div>';
	      Object.keys(TYPES).forEach(function(tk){
	        if(!groups[tk]) return;
	        card+='<div style="font-family:monospace;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#8F8E90;margin:8px 0 3px">'+TYPES[tk]+'</div>';
	        groups[tk].forEach(function(it){
	          card+='<div style="display:flex;justify-content:space-between;gap:8px;font-size:12.5px;color:#16181B;padding:2px 0">'+
	            '<span>'+it.n+'</span>'+(it.c?'<b style="white-space:nowrap">'+it.c+'</b>':'')+'</div>';
	        });
	      });
	      h+=card+'</div>';
	    });
	    box.innerHTML=h||'<em style="color:#8F8E90">Zatím nic vyplněno — karta se na webu skryje.</em>';
	  }
	  /* ---- živý náhled seskupený podle štítků (stálá nabídka i nápojový lístek) ---- */
	  function previewGrouped(typySel, itemsSel, itemFieldMarker){
	    var box=document.getElementById('gm-preview'); if(!box) return;
	    var typy=[];
	    document.querySelectorAll(typySel+' tbody tr').forEach(function(tr){
	      var inputs=tr.querySelectorAll('input');
	      var key=inputs[0].value.trim(), cz=inputs[1].value.trim();
	      if(key||cz) typy.push({key:key, cz:cz||key});
	    });
	    var groups={};
	    document.querySelectorAll(itemsSel+' tbody tr').forEach(function(tr){
	      var sel=tr.querySelector('select');
	      var key=sel?sel.value:'';
	      var inputs=tr.querySelectorAll('input[type="text"]');
	      var cz=inputs[0]?inputs[0].value.trim():'';
	      var cena=inputs[3]?inputs[3].value.trim():'';
	      if(!cz) return;
	      (groups[key]=groups[key]||[]).push({n:cz,c:cena});
	    });
	    var h='';
	    typy.forEach(function(ty){
	      if(!groups[ty.key] || !groups[ty.key].length) return;
	      h+='<div style="background:#fff;border:1px solid rgba(20,22,25,.12);border-radius:6px;padding:12px 14px">'+
	        '<div style="font-weight:700;color:#16181B;border-bottom:2px solid #C20E1A;padding-bottom:6px;margin-bottom:8px">'+ty.cz+'</div>';
	      groups[ty.key].forEach(function(it){
	        h+='<div style="display:flex;justify-content:space-between;gap:8px;font-size:12.5px;color:#16181B;padding:2px 0">'+
	          '<span>'+it.n+'</span>'+(it.c?'<b style="white-space:nowrap">'+it.c+'</b>':'')+'</div>';
	      });
	      h+='</div>';
	    });
	    box.innerHTML=h||'<em style="color:#8F8E90">Zatím nic vyplněno — na webu se sekce nezobrazí.</em>';
	  }
	  document.addEventListener('input', function(e){
	    if(!e.target.closest('.gm-day')) return;
	    renderActivePreview();
	  });
	  document.addEventListener('change', function(e){
	    if(!e.target.closest('.gm-day')) return;
	    renderActivePreview();
	  });
	  renderActivePreview();
	})();
	</script>
	<?php endif; ?>
	<?php
}

/**
 * Vstupní bod z „GRID Nastavení" (mimo Framework 2.4 tab bar – ta stránka
 * jde přímo, beze záložek Přehled/Info/Log, viz add_submenu_page() výše).
 * Personál si tu sám přepíná provoz přes vlastní jednoduchý pruh záložek;
 * "Nastavení" (přidání/smazání provozu) tady záměrně není – to zůstává jen
 * administrátorům v GARRY Nastavení.
 */
function garry_menu_grid_admin_page() {
	if ( ! current_user_can( GARRY_DENNI_MENU_STAFF_CAP ) ) return;
	$all = garry_menu_get_all();
	if ( empty( $all['venue_order'] ) ) {
		echo '<div class="wrap"><h1>Jídelní lístek</h1><p>Zatím není nastavený žádný provoz. Přidá ho administrátor v GARRY Nastavení → Denní menu → Nastavení.</p></div>';
		return;
	}
	$current = isset( $_GET['gm_venue'] ) ? sanitize_key( $_GET['gm_venue'] ) : '';
	if ( ! isset( $all['venues'][ $current ] ) ) $current = $all['venue_order'][0];

	if ( count( $all['venue_order'] ) > 1 ) {
		echo '<h2 class="nav-tab-wrapper" style="margin-bottom:0">';
		foreach ( $all['venue_order'] as $slug ) {
			$url = add_query_arg( array( 'page' => 'garry-denni-menu-grid', 'gm_venue' => $slug ), admin_url( 'admin.php' ) );
			$class = 'nav-tab' . ( $current === $slug ? ' nav-tab-active' : '' );
			printf( '<a href="%s" class="%s">%s</a>', esc_url( $url ), esc_attr( $class ), esc_html( $all['venues'][ $slug ]['name'] ) );
		}
		echo '</h2>';
	}
	garry_menu_render_venue_editor( $current );
}

/* ---------- Frontend: [grid_menu_tydne provoz="..." typ="cely|denni|tydenni|stala|vecerni"] ---------- */
function garry_menu_render( $atts = array() ) {
	$atts = shortcode_atts( array( 'provoz' => '', 'typ' => 'cely' ), $atts, 'grid_menu_tydne' );
	$venue_slug = sanitize_key( $atts['provoz'] );
	$typ = sanitize_key( $atts['typ'] );

	$s = '' !== $venue_slug ? garry_menu_get_venue( $venue_slug ) : null;
	if ( ! $s ) {
		if ( current_user_can( 'manage_options' ) ) {
			$known = implode( ', ', array_keys( garry_menu_venue_options() ) );
			return '<!-- GARRY – Denní menu: neplatný nebo chybějící atribut provoz="' . esc_html( $venue_slug ) . '". Dostupné provozy: ' . esc_html( $known ) . ' -->';
		}
		return '';
	}

	$DAYS = garry_menu_days(); $TYPES = garry_menu_types();
	$li = garry_menu_lang_idx();
	$en = $s['enabled'];

	$show_denni  = ( 'cely' === $typ && $en['denni'] ) || 'denni' === $typ;
	$show_tyden  = ( 'cely' === $typ && $en['tydenni'] ) || 'tydenni' === $typ;
	$show_stala  = ( 'cely' === $typ && $en['stala'] ) || 'stala' === $typ;
	$show_vecerni = ( 'cely' === $typ && $en['vecerni'] ) || 'vecerni' === $typ;

	$filled = array();
	if ( $show_denni ) {
		foreach ( $DAYS as $key => $names ) {
			if ( 'tyden' === $key ) continue;
			if ( ! empty( $s['days'][ $key ] ) ) $filled[ $key ] = $s['days'][ $key ];
		}
	}
	if ( $show_tyden && ! empty( $s['days']['tyden'] ) ) {
		$filled['tyden'] = $s['days']['tyden'];
	}

	if ( ! $filled && ( ! $show_stala || empty( $s['stala'] ) ) && ( ! $show_vecerni || empty( $s['vecerni'] ) ) ) {
		return '';
	}

	$weekline = '';
	$range = garry_menu_week_range( $s['week'] );
	if ( $range && ( $show_denni || $show_tyden ) ) {
		$fmt = array( 'Platí pro týden %s – %s', 'Valid for the week of %s – %s', 'Gültig für die Woche %s – %s' );
		$weekline = sprintf( $fmt[ $li ], $range[0]->format( 'j. n.' ), $range[1]->format( 'j. n. Y' ) );
	}
	ob_start();
	if ( $filled ) {
	if ( $weekline ) echo '<p style="color:var(--muted);font-family:var(--f-mono);font-size:.82rem;margin-bottom:24px">' . esc_html( $weekline ) . '</p>';
	echo '<div class="menu-week menu-week--8">';
	foreach ( $filled as $key => $rows ) {
		echo '<div class="menu-day"><h3>' . esc_html( $DAYS[ $key ][ $li ] ) . '</h3>';
		foreach ( $TYPES as $tk => $tn ) {
			$items = array_values( array_filter( $rows, function ( $r ) use ( $tk ) { return ( $r['typ'] ?? '' ) === $tk; } ) );
			if ( ! $items ) continue;
			echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $tn[ $li ] ) . '</span>';
			foreach ( $items as $r ) {
				$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
				if ( $name === '' ) continue;
				echo '<div class="menu-item"><span class="menu-n">' . esc_html( $name ) . '</span>';
				if ( ! empty( $r['cena'] ) ) echo '<span class="menu-c">' . esc_html( $r['cena'] ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
	}

	if ( $show_stala && ! empty( $s['stala'] ) ) {
		$groups = array();
		foreach ( $s['stala'] as $r ) {
			$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
			if ( $name === '' ) continue;
			$groups[ $r['typ'] ][] = array( $name, (string) ( $r['cena'] ?? '' ) );
		}
		if ( $groups ) {
			$KICK = array( 'Stálá nabídka', 'À la carte menu', 'Ständiges Angebot' );
			echo '<h3 class="menu-stala-h">' . esc_html( $KICK[ $li ] ) . '</h3>';
			echo '<div class="menu-stala">';
			foreach ( $s['typy'] as $ty ) {
				if ( empty( $groups[ $ty['key'] ] ) ) continue;
				$lbl = $ty[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $ty['cz'];
				echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $lbl ) . '</span>';
				foreach ( $groups[ $ty['key'] ] as $it ) {
					echo '<div class="menu-item"><span class="menu-n">' . esc_html( $it[0] ) . '</span>';
					if ( $it[1] !== '' ) echo '<span class="menu-c">' . esc_html( $it[1] ) . '</span>';
					echo '</div>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}

	if ( $show_vecerni && ! empty( $s['vecerni'] ) ) {
		$groups = array();
		foreach ( $s['vecerni'] as $r ) {
			$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
			if ( $name === '' ) continue;
			$groups[ $r['typ'] ][] = array( $name, (string) ( $r['cena'] ?? '' ) );
		}
		if ( $groups ) {
			$KICK = array( 'Večerní menu', 'Evening menu', 'Abendkarte' );
			echo '<h3 class="menu-stala-h">' . esc_html( $KICK[ $li ] ) . '</h3>';
			echo '<div class="menu-stala">';
			foreach ( $s['vecerni_typy'] as $ty ) {
				if ( empty( $groups[ $ty['key'] ] ) ) continue;
				$lbl = $ty[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $ty['cz'];
				echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $lbl ) . '</span>';
				foreach ( $groups[ $ty['key'] ] as $it ) {
					echo '<div class="menu-item"><span class="menu-n">' . esc_html( $it[0] ) . '</span>';
					if ( $it[1] !== '' ) echo '<span class="menu-c">' . esc_html( $it[1] ) . '</span>';
					echo '</div>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_menu_tydne', 'garry_menu_render' ); }, 5 );
/**
 * Obecný alias beze „grid_" prefixu pro standalone weby mimo GRID Hotel
 * (GRID-SUITE-05 §5) — stejný renderer, stejné atributy, žádná duplicitní
 * logika.
 */
add_action( 'init', function () { add_shortcode( 'garry_weekly_menu', 'garry_menu_render' ); }, 5 );

/**
 * Nativní WordPress/Divi 5 blok „garry/denni-menu" — atomický ekvivalent
 * shortcode [grid_menu_tydne] pro editaci v blokovém/Divi 5 builderu
 * (ten je od verze 5 postavený na standardním WP Block Editoru, viz
 * <!-- wp:divi/... --> markup v obsahu stránek). Čistě standardní WP Block
 * API (register_block_type + block.json), žádná závislost na interních
 * Divi balíčcích — blok se v inserteru objeví vedle vlastních Divi modulů.
 * Render_callback volá PŘÍMO garry_menu_render() (stejná funkce jako
 * shortcode), žádná duplicitní logika.
 */
add_action( 'init', function () {
	wp_register_script(
		'garry-denni-menu-block-edit',
		plugins_url( 'blocks/denni-menu/edit.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
		GARRY_MENU_VER,
		true
	);
	wp_add_inline_script(
		'garry-denni-menu-block-edit',
		'window.GarryDenniMenuBlockData = ' . wp_json_encode( array(
			'venues' => array_map(
				function ( $slug, $name ) { return array( 'label' => $name, 'value' => $slug ); },
				array_keys( garry_menu_venue_options() ), array_values( garry_menu_venue_options() )
			),
			'types'  => array(
				array( 'label' => __( 'Celý lístek (podle nastavení provozu)', 'garry-denni-menu' ), 'value' => 'cely' ),
				array( 'label' => __( 'Jen denní menu', 'garry-denni-menu' ), 'value' => 'denni' ),
				array( 'label' => __( 'Jen týdenní nabídku', 'garry-denni-menu' ), 'value' => 'tydenni' ),
				array( 'label' => __( 'Jen stálou nabídku', 'garry-denni-menu' ), 'value' => 'stala' ),
				array( 'label' => __( 'Jen večerní menu', 'garry-denni-menu' ), 'value' => 'vecerni' ),
			),
		) ) . ';',
		'before'
	);
	register_block_type( __DIR__ . '/blocks/denni-menu', array( 'render_callback' => 'garry_menu_render' ) );
}, 6 );

/**
 * Veřejné API (GRID-SUITE-05 §5), nad stejnými daty jako shortcode výše —
 * pro Components/GARRY pluginy, které chtějí menu použít bez shortcode
 * stringu.
 *
 * @param array $args { provoz?: string slug provozu; typ?: string }
 * @return array Data provozu (garry_menu_empty_venue() tvar) nebo prázdné pole, pokud provoz neexistuje.
 */
function garry_menu_get_current( array $args = array() ) {
	$venue_slug = isset( $args['provoz'] ) ? sanitize_key( $args['provoz'] ) : '';
	$s = '' !== $venue_slug ? garry_menu_get_venue( $venue_slug ) : null;
	return $s ?: array();
}

/** @return bool True, pokud provoz existuje a má aspoň jeden zapnutý typ nabídky. Bez $menu_id kontroluje, zda existuje aspoň jeden provoz vůbec. */
function garry_menu_is_available( $menu_id = null ) {
	$all = garry_menu_get_all();
	if ( null === $menu_id || '' === $menu_id ) {
		return ! empty( $all['venue_order'] );
	}
	$slug = sanitize_key( $menu_id );
	if ( empty( $all['venues'][ $slug ] ) ) {
		return false;
	}
	return ! empty( array_filter( $all['venues'][ $slug ]['enabled'] ) );
}

/**
 * Registrace do GARRY – GRID Core modul registru (GRID-SUITE-00 „Integrační
 * API GRID") — bez tohohle Components (fáze 2) nikdy nenajde skutečný
 * poskytovatel a vždy použije jen svůj neutrální fallback. Volá se jen
 * pokud gridhotel_register_module skutečně existuje (Core nemusí být
 * aktivní vůbec — plugin funguje i tak, viz standalone testy).
 */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) {
		return;
	}
	gridhotel_register_module( array(
		'id'              => 'garry-denni-menu',
		'name'            => 'Týdenní menu',
		'version'         => defined( 'GARRY_MENU_VER' ) ? GARRY_MENU_VER : '1.5.0',
		'admin_slug'      => 'garry-denni-menu-grid',
		'capability'      => GARRY_DENNI_MENU_STAFF_CAP,
		'shortcodes'      => array( 'grid_menu_tydne', 'garry_weekly_menu', 'grid_napojovy_listek' ),
		'features'        => array( 'weekly_menu' ),
		'render_callback' => 'garry_menu_render',
	) );
} );

/* ---------- Frontend: [grid_napojovy_listek provoz="..." stitek="..."] ---------- */
function garry_napoje_render( $atts = array() ) {
	$atts = shortcode_atts( array( 'provoz' => '', 'stitek' => '' ), $atts, 'grid_napojovy_listek' );
	$venue_slug = sanitize_key( $atts['provoz'] );
	$only_tag = sanitize_key( $atts['stitek'] );

	$s = '' !== $venue_slug ? garry_menu_get_venue( $venue_slug ) : null;
	if ( ! $s ) {
		if ( current_user_can( 'manage_options' ) ) {
			$known = implode( ', ', array_keys( garry_menu_venue_options() ) );
			return '<!-- GARRY – Denní menu: neplatný nebo chybějící atribut provoz="' . esc_html( $venue_slug ) . '". Dostupné provozy: ' . esc_html( $known ) . ' -->';
		}
		return '';
	}
	if ( empty( $s['napoje']['items'] ) ) {
		return '';
	}

	$li = garry_menu_lang_idx();
	$groups = array();
	foreach ( $s['napoje']['items'] as $r ) {
		if ( '' !== $only_tag && $r['tag'] !== $only_tag ) continue;
		$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
		if ( $name === '' ) continue;
		$groups[ $r['tag'] ][] = array( $name, (string) ( $r['cena'] ?? '' ) );
	}
	if ( ! $groups ) return '';

	ob_start();
	echo '<div class="menu-stala menu-napoje">';
	foreach ( $s['napoje']['tags'] as $ty ) {
		if ( empty( $groups[ $ty['key'] ] ) ) continue;
		$lbl = $ty[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $ty['cz'];
		echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $lbl ) . '</span>';
		foreach ( $groups[ $ty['key'] ] as $it ) {
			echo '<div class="menu-item"><span class="menu-n">' . esc_html( $it[0] ) . '</span>';
			if ( $it[1] !== '' ) echo '<span class="menu-c">' . esc_html( $it[1] ) . '</span>';
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_napojovy_listek', 'garry_napoje_render' ); }, 5 );


/* ============================================================================
 * Detekce legacy Divi 4 Builderu (sdíleno mezi GARRY pluginy – stejná
 * funkce jako v garry-galerie/garry-tabulka-nemovitosti/garry-mapa-katastr/
 * garry-video-prohlidka, zkopírováno beze změny).
 * ============================================================================ */
if ( ! function_exists( 'garry_divi4_legacy_builder' ) ) {
	/**
	 * Zjistí, zda běží legacy Divi 4 Builder.
	 *
	 * Vrací false na Divi 5 (i s aktivní zpětnou kompatibilitou) – registrace
	 * legacy modulů by tam nutila načítat celý Divi 4 framework a přepínala
	 * stránky do kompatibilního režimu, což rozbíjí vykreslování webu.
	 *
	 * @return bool True pouze na Divi 4 s dostupným ET_Builder_Module.
	 */
	function garry_divi4_legacy_builder() {
		if ( ! class_exists( 'ET_Builder_Module' ) ) {
			return false;
		}
		if ( class_exists( '\\ET\\Builder\\Framework\\DependencyManagement\\DependencyTree' )
			|| class_exists( '\\ET\\Builder\\Framework\\Utility\\Conditions' ) ) {
			return false;
		}
		$ver = defined( 'ET_BUILDER_PRODUCT_VERSION' ) ? ET_BUILDER_PRODUCT_VERSION
			: ( function_exists( 'et_get_theme_version' ) ? et_get_theme_version() : '' );
		if ( $ver && version_compare( $ver, '5.0-alpha', '>=' ) ) {
			return false;
		}
		return true;
	}
}

/* ============================================================================
 * Divi Builder moduly (widgety) – stejné vykreslení jako shortcody.
 *
 * POUZE DIVI 4, stejné zdůvodnění jako garry-galerie. Na Divi 5 zůstává
 * jen shortcode [grid_menu_tydne]/[grid_napojovy_listek] (Text/Kód modul) –
 * nativní D5 modul by šel postavit jen s ověřením proti skutečné D5
 * instalaci, což GARRY nemá jak udělat naslepo.
 * ============================================================================ */
add_action( 'et_builder_ready', function () {
	if ( ! garry_divi4_legacy_builder() ) {
		return;
	}

	$venue_field = function () {
		$options = garry_menu_venue_options();
		return array(
			'label'       => esc_html__( 'Provoz', 'garry-denni-menu' ),
			'type'        => 'select',
			'option_category' => 'basic_option',
			'options'     => $options,
			'default'     => '',
			'toggle_slug' => 'main_content',
			'description' => esc_html__( 'Který provoz se má vypsat. Spravuje se v GARRY nastavení → Denní menu.', 'garry-denni-menu' ),
		);
	};

	class GTV_DenniMenu_Module extends ET_Builder_Module {

		public $slug       = 'gtv_denni_menu';
		public $vb_support = 'off';

		public function init() {
			$this->name             = esc_html__( 'GARRY – Denní menu', 'garry-denni-menu' );
			$this->main_css_element = '%%order_class%%';
		}

		public function get_fields() {
			return array(
				'provoz' => array(
					'label'            => esc_html__( 'Provoz', 'garry-denni-menu' ),
					'type'             => 'select',
					'option_category'  => 'basic_option',
					'options'          => garry_menu_venue_options(),
					'default'          => '',
					'toggle_slug'      => 'main_content',
				),
				'typ' => array(
					'label'           => esc_html__( 'Co vypsat', 'garry-denni-menu' ),
					'type'            => 'select',
					'option_category' => 'basic_option',
					'options'         => array(
						'cely'    => esc_html__( 'Celý lístek (podle nastavení provozu)', 'garry-denni-menu' ),
						'denni'   => esc_html__( 'Jen denní menu', 'garry-denni-menu' ),
						'tydenni' => esc_html__( 'Jen týdenní nabídku', 'garry-denni-menu' ),
						'stala'   => esc_html__( 'Jen stálou nabídku', 'garry-denni-menu' ),
						'vecerni' => esc_html__( 'Jen večerní menu', 'garry-denni-menu' ),
					),
					'default'         => 'cely',
					'toggle_slug'     => 'main_content',
				),
			);
		}

		public function render( $attrs, $content = null, $render_slug = '' ) {
			return garry_menu_render( array(
				'provoz' => $this->props['provoz'] ?? '',
				'typ'    => $this->props['typ'] ?? 'cely',
			) );
		}
	}

	class GTV_NapojovyListek_Module extends ET_Builder_Module {

		public $slug       = 'gtv_napojovy_listek';
		public $vb_support = 'off';

		public function init() {
			$this->name             = esc_html__( 'GARRY – Nápojový lístek', 'garry-denni-menu' );
			$this->main_css_element = '%%order_class%%';
		}

		public function get_fields() {
			return array(
				'provoz' => array(
					'label'           => esc_html__( 'Provoz', 'garry-denni-menu' ),
					'type'            => 'select',
					'option_category' => 'basic_option',
					'options'         => garry_menu_venue_options(),
					'default'         => '',
					'toggle_slug'     => 'main_content',
				),
				'stitek' => array(
					'label'           => esc_html__( 'Jen tenhle štítek (nepovinné)', 'garry-denni-menu' ),
					'type'            => 'text',
					'option_category' => 'basic_option',
					'toggle_slug'     => 'main_content',
					'description'     => esc_html__( 'Prázdné = celý nápojový lístek. Klíč štítku je vidět v GARRY nastavení → Denní menu → provoz → Nápojový lístek.', 'garry-denni-menu' ),
				),
			);
		}

		public function render( $attrs, $content = null, $render_slug = '' ) {
			return garry_napoje_render( array(
				'provoz' => $this->props['provoz'] ?? '',
				'stitek' => $this->props['stitek'] ?? '',
			) );
		}
	}

	try {
		new GTV_DenniMenu_Module();
		new GTV_NapojovyListek_Module();
	} catch ( \Throwable $e ) {
		// Pojistka: registrace modulu nikdy nesmí shodit web.
	}
} );
