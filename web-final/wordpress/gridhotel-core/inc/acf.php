<?php
/**
 * GRID Hotel Core — ACF integrace
 * Přidá plugin jako další zdroj acf-json (pole CPT se načtou automaticky).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'acf/settings/load_json', function ( $paths ) {
	$paths[] = GRIDCORE_DIR . 'acf-json';
	return $paths;
} );

/* Uložení změn polí těchto skupin ať míří sem (volitelné, pro vývoj) */
add_filter( 'acf/settings/save_json/key=group_grid_cpt_room', fn() => GRIDCORE_DIR . 'acf-json' );
add_filter( 'acf/settings/save_json/key=group_grid_cpt_experience', fn() => GRIDCORE_DIR . 'acf-json' );
add_filter( 'acf/settings/save_json/key=group_grid_cpt_event', fn() => GRIDCORE_DIR . 'acf-json' );
add_filter( 'acf/settings/save_json/key=group_grid_cpt_gastro', fn() => GRIDCORE_DIR . 'acf-json' );
add_filter( 'acf/settings/save_json/key=group_grid_cpt_testimonial', fn() => GRIDCORE_DIR . 'acf-json' );
/* group_grid_tax_room_cat dřív žádný save_json filtr nemělo (asymetrie proti výše) – doplněno ve 2.0.0. */
add_filter( 'acf/settings/save_json/key=group_grid_tax_room_cat', fn() => GRIDCORE_DIR . 'acf-json' );
/* group_grid_options a group_grid_content přesunuty z child theme (2.0.0), viz inc/options-page.php. */
add_filter( 'acf/settings/save_json/key=group_grid_options', fn() => GRIDCORE_DIR . 'acf-json' );
add_filter( 'acf/settings/save_json/key=group_grid_content', fn() => GRIDCORE_DIR . 'acf-json' );

/**
 * Propojí gastro kartu s konkrétním provozem v GARRY – Denní menu.
 * Core zůstává funkční i bez aktivního menu pluginu: v tom případě je
 * dostupná pouze výchozí volba „automaticky podle názvu provozu".
 */
add_filter( 'acf/load_field/name=menu_venue', function ( $field ) {
	$field['choices'] = array( '' => 'Automaticky podle názvu provozu' );
	if ( function_exists( 'garry_menu_venue_options' ) ) {
		foreach ( (array) garry_menu_venue_options() as $slug => $name ) {
			$field['choices'][ $slug ] = $name . ' (' . $slug . ')';
		}
	}
	return $field;
} );
