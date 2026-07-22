<?php
/**
 * GRID Hotel Core — Custom Post Types
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Rodičovské menu pro CPT: podpoložky pod „GRID Nastavení" (ACF options 'grid-options').
 * Když ACF PRO / options page není, spadne na samostatnou top-level položku.
 */
function gridcore_parent_menu() {
	return function_exists( 'acf_add_options_page' ) ? 'grid-options' : true;
}

function gridcore_register_cpts() {

	$types = array(
		'grid_room' => array(
			'singular' => 'Pokoj', 'plural' => 'Pokoje', 'slug' => 'pokoje',
			'icon' => 'dashicons-bank', 'thumb' => true,
		),
		'grid_experience' => array(
			'singular' => 'Zážitek', 'plural' => 'Zážitky', 'slug' => 'zazitky',
			'icon' => 'dashicons-superhero', 'thumb' => false,
			// BEZ archivu: /zazitky/ patří stránce (Divi layout), archiv CPT by ji přebil.
			'archive' => false,
		),
		'grid_event' => array(
			'singular' => 'Akce sezóny', 'plural' => 'Sezóna 2026', 'slug' => 'akce',
			'icon' => 'dashicons-calendar-alt', 'thumb' => false,
			// SKRYTO z menu: akce spravuje plugin „Sezóna & čekací list", CPT je jen datová legacy.
			// BEZ archivu: /akce/ nikde v navigaci nevede, reálný obsah je na /sezona-2026/ (SEO: prázdný duplicitní archiv pryč).
			'menu' => false, 'archive' => false,
		),
		'grid_gastro' => array(
			'singular' => 'Gastro provoz', 'plural' => 'Gastronomie', 'slug' => 'gastro',
			'icon' => 'dashicons-food', 'thumb' => true,
			// BEZ archivu: /gastro/ nikde v navigaci nevede, reálný obsah je na /gastronomie/ (SEO: prázdný duplicitní archiv pryč).
			'archive' => false,
		),
		'grid_job' => array(
			'singular' => 'Pracovní pozice', 'plural' => 'Kariéra', 'slug' => 'kariera-pozice',
			'icon' => 'dashicons-groups', 'thumb' => false,
			// vlastní podpoložka v GRID Nastavení (viz níže), z hlavního menu skryto
			'menu' => false,
		),
		'grid_testimonial' => array(
			'singular' => 'Reference', 'plural' => 'Reference', 'slug' => 'reference',
			'icon' => 'dashicons-format-quote', 'thumb' => false,
			// BEZ archivu: /reference/ nikde v navigaci nevede (reference odloženy, viz backlog) — SEO: prázdný archiv pryč.
			'archive' => false,
		),
	);

	foreach ( $types as $key => $t ) {
		$supports = array( 'title', 'page-attributes' );
		if ( ! empty( $t['thumb'] ) ) $supports[] = 'thumbnail';

		register_post_type( $key, array(
			'labels' => array(
				'name'          => $t['plural'],
				'singular_name' => $t['singular'],
				'add_new_item'  => 'Přidat: ' . $t['singular'],
				'edit_item'     => 'Upravit: ' . $t['singular'],
				'menu_name'     => 'GRID: ' . $t['plural'],
				'all_items'     => $t['plural'],
			),
			'public'             => true,
			'show_ui'            => true,
			// Zobrazit jako podpoložky pod „GRID Nastavení" (ACF options page 'grid-options').
			// Fallback na samostatnou top-level položku, když options page není (ACF Free).
			'show_in_menu'       => array_key_exists( 'menu', $t ) ? $t['menu'] : gridcore_parent_menu(),
			'show_in_rest'       => true,
			'has_archive'        => array_key_exists( 'archive', $t ) ? $t['archive'] : true,
			'hierarchical'       => false,
			'menu_icon'          => $t['icon'],
			'supports'           => $supports,
			'rewrite'            => array( 'slug' => $t['slug'], 'with_front' => false ),
			'capability_type'    => 'post',
		) );
	}
}
add_action( 'init', 'gridcore_register_cpts' );

/* Zážitky a pracovní pozice jsou překládané Polylangem (CZ/EN/DE) */
add_filter( 'pll_get_post_types', function ( $types, $is_settings ) {
	$types['grid_experience'] = 'grid_experience';
	$types['grid_job']        = 'grid_job';
	return $types;
}, 10, 2 );

/* ------------------------------------------------------------------
 * Taxonomie: Kategorie pokojů (4 typy) — spravováno v „GRID Nastavení"
 * Jednotlivý pokoj = příspěvek grid_room zařazený do jedné kategorie.
 * ------------------------------------------------------------------ */
function gridcore_register_room_tax() {
	register_taxonomy( 'grid_room_cat', 'grid_room', array(
		'labels' => array(
			'name'          => 'Kategorie pokojů',
			'singular_name' => 'Kategorie pokoje',
			'menu_name'     => 'Pokoje — fotky a galerie',
			'add_new_item'  => 'Přidat kategorii',
			'edit_item'     => 'Upravit kategorii',
			'all_items'     => 'Kategorie pokojů',
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'show_in_menu'      => false,          // schováno z menu Pokoje — spravuje se v Nastavení
		'meta_box_cb'       => false,          // výběr řešíme ACF radiem (jedna ze 4)
		'rewrite'           => array( 'slug' => 'kategorie-pokoje', 'with_front' => false ),
	) );
}
add_action( 'init', 'gridcore_register_room_tax' );

/* Odkaz na správu kategorií pod „GRID Nastavení" */
add_action( 'admin_menu', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		add_submenu_page(
			'grid-options',
			'Pokoje — fotky a galerie',
			'— Pokoje: fotky a galerie',
			'edit_others_posts',
			'edit-tags.php?taxonomy=grid_room_cat&post_type=grid_room'
		);
		add_submenu_page(
			'grid-options',
			'Kariéra — pracovní pozice',
			'Kariéra',
			'edit_others_posts',
			'edit.php?post_type=grid_job'
		);
	}
}, 30 );

/* ------------------------------------------------------------------
 * Kariéra: ACF pole pracovní pozice (název pozice = titulek příspěvku)
 * Výpis na stránce Kariéra zajišťuje shortcode [grid_kariera].
 * ------------------------------------------------------------------ */
add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;
	acf_add_local_field_group( array(
		'key'    => 'group_grid_job',
		'title'  => 'Pracovní pozice',
		'fields' => array(
			array( 'key' => 'field_job_uvazek', 'name' => 'uvazek', 'label' => 'Úvazek',
				'type' => 'text', 'instructions' => 'Např. „Plný úvazek (HPP)", „Zkrácený úvazek", „DPP / brigáda".' ),
			array( 'key' => 'field_job_misto', 'name' => 'misto', 'label' => 'Místo výkonu',
				'type' => 'text', 'default_value' => 'GRID Hotel, Masarykův okruh, Brno' ),
			array( 'key' => 'field_job_mzda', 'name' => 'mzda', 'label' => 'Mzda (nepovinné)',
				'type' => 'text', 'instructions' => 'Např. „35 000–42 000 Kč" — prázdné se nezobrazí.' ),
			array( 'key' => 'field_job_popis', 'name' => 'popis', 'label' => 'Popis pozice',
				'type' => 'textarea', 'rows' => 6, 'new_lines' => 'br',
				'instructions' => 'Náplň práce, požadavky, co nabízíme. Odstavce oddělujte prázdným řádkem.' ),
			array( 'key' => 'field_job_email', 'name' => 'email', 'label' => 'E-mail pro přihlášení',
				'type' => 'email', 'default_value' => 'info@gridhotel.cz' ),
		),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'grid_job' ) ) ),
		'position' => 'acf_after_title', // pole pozice hned pod titulkem, NAD rámečkem Yoast SEO
	) );
} );

/* Řazení v adminu podle menu_order (page-attributes) */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	$pt = $q->get( 'post_type' );
	if ( in_array( $pt, array( 'grid_room','grid_experience','grid_event','grid_gastro','grid_testimonial','grid_job' ), true ) ) {
		$q->set( 'orderby', 'menu_order title' );
		$q->set( 'order', 'ASC' );
	}
} );

/* Sloupec „Pořadí" v adminním výpisu (rychlá orientace) */
foreach ( array('grid_room','grid_experience','grid_event','grid_gastro','grid_testimonial','grid_job') as $pt ) {
	add_filter( "manage_{$pt}_posts_columns", function ( $cols ) {
		$cols['menu_order'] = 'Pořadí';
		return $cols;
	} );
	add_action( "manage_{$pt}_posts_custom_column", function ( $col, $post_id ) {
		if ( $col === 'menu_order' ) echo (int) get_post_field( 'menu_order', $post_id );
	}, 10, 2 );
}
