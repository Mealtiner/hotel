<?php
/**
 * Plugin Name:       GRID Hotel Core
 * Plugin URI:        https://www.garry.cz
 * Description:       Datová vrstva webu GRID Hotel — custom post types (pokoje, zážitky, akce sezóny, gastro, reference) + ACF pole + jednoklikové naplnění obsahem. Součást ekosystému GARRY Promotion (zobrazuje se v přehledu „GARRY nastavení"). Nezávislé na šabloně.
 * Version:           1.5.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       gridhotel-core
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_VER', '1.5.0' );
define( 'GRIDCORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRIDCORE_URL', plugin_dir_url( __FILE__ ) );

require_once GRIDCORE_DIR . 'inc/cpt.php';
require_once GRIDCORE_DIR . 'inc/acf.php';
require_once GRIDCORE_DIR . 'inc/seed.php';
require_once GRIDCORE_DIR . 'inc/garry.php';

/* Aktivace: zaregistrovat CPT a přegenerovat pravidla přepisu URL */
register_activation_hook( __FILE__, function () {
	gridcore_register_cpts();
	gridcore_register_room_tax(); // taxonomie grid_room_cat — bez tohoto by flush níže proběhl
	                               // ještě před zaregistrováním jejích rewrite pravidel (chyběla
	                               // by URL /kategorie-pokoje/... do dalšího ručního flushe)
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

/* Admin upozornění: doporuč ACF PRO a nabídni naplnění obsahem */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! class_exists( 'ACF' ) ) {
		echo '<div class="notice notice-warning"><p><strong>GRID Hotel Core:</strong> pro editaci polí je potřeba <strong>Advanced Custom Fields PRO</strong>.</p></div>';
	}
} );
