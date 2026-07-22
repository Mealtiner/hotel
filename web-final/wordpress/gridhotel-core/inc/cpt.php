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
		),
		'grid_gastro' => array(
			'singular' => 'Gastro provoz', 'plural' => 'Gastronomie', 'slug' => 'gastro',
			'icon' => 'dashicons-food', 'thumb' => true,
		),
		'grid_testimonial' => array(
			'singular' => 'Reference', 'plural' => 'Reference', 'slug' => 'reference',
			'icon' => 'dashicons-format-quote', 'thumb' => false,
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
			'show_in_menu'       => gridcore_parent_menu(),
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
			'manage_options',
			'edit-tags.php?taxonomy=grid_room_cat&post_type=grid_room'
		);
	}
}, 30 );

/* Řazení v adminu podle menu_order (page-attributes) */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) return;
	$pt = $q->get( 'post_type' );
	if ( in_array( $pt, array( 'grid_room','grid_experience','grid_event','grid_gastro','grid_testimonial' ), true ) ) {
		$q->set( 'orderby', 'menu_order title' );
		$q->set( 'order', 'ASC' );
	}
} );

/* Sloupec „Pořadí" v adminním výpisu (rychlá orientace) */
foreach ( array('grid_room','grid_experience','grid_event','grid_gastro','grid_testimonial') as $pt ) {
	add_filter( "manage_{$pt}_posts_columns", function ( $cols ) {
		$cols['menu_order'] = 'Pořadí';
		return $cols;
	} );
	add_action( "manage_{$pt}_posts_custom_column", function ( $col, $post_id ) {
		if ( $col === 'menu_order' ) echo (int) get_post_field( 'menu_order', $post_id );
	}, 10, 2 );
}
