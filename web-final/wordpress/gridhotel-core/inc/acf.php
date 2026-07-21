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
