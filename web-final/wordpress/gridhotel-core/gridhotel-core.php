<?php
/**
 * Plugin Name:       GARRY – GRID Core
 * Plugin URI:        https://www.garry.cz
 * Description:       Datová vrstva webu GRID Hotel — custom post types (pokoje, zážitky, akce sezóny, gastro, kariéra, reference) + ACF pole + jednoklikové naplnění obsahem. Součást ekosystému GARRY Promotion (zobrazuje se v přehledu „GARRY nastavení"). Nezávislé na šabloně.
 * Version:           2.4.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       gridhotel-core
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_VER', '2.4.0' );
define( 'GRIDCORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRIDCORE_URL', plugin_dir_url( __FILE__ ) );
define( 'GRIDCORE_FILE', __FILE__ );

/**
 * Granulární oprávnění na kartu v GRID Nastavení (od verze 1.6.0, beze
 * změny). Dvě vlastní capability pro submenu položky, které GRID Core sám
 * registruje pod grid-options – "Pokoje: fotky a galerie" a "Kariéra". Admin
 * je má vždy (provisioning je od 2.0.0 v inc/upgrader.php, ne tady napevno);
 * komukoli dalšímu je přiděluje jen obrazovka "Oprávnění personálu"
 * (inc/staff-permissions.php), ne role jako celek. Odlišné od NOVÉ rodiny
 * GRIDCORE_DCAP_* (inc/capabilities.php) – viz komentář tam.
 */
define( 'GRIDCORE_CAP_ROOMS_GALLERY', 'garry_grid_manage_pokoje_galerie' );
define( 'GRIDCORE_CAP_CAREERS', 'garry_grid_manage_kariera' );

require_once GRIDCORE_DIR . 'inc/capabilities.php';
require_once GRIDCORE_DIR . 'inc/cpt.php';
require_once GRIDCORE_DIR . 'inc/acf.php';
require_once GRIDCORE_DIR . 'inc/options.php';
require_once GRIDCORE_DIR . 'inc/options-page.php';
require_once GRIDCORE_DIR . 'inc/data-api.php';
require_once GRIDCORE_DIR . 'inc/module-registry.php';
require_once GRIDCORE_DIR . 'inc/access-page.php';
require_once GRIDCORE_DIR . 'inc/forms-admin.php';
require_once GRIDCORE_DIR . 'inc/seo.php';
require_once GRIDCORE_DIR . 'inc/diagnostics.php';
require_once GRIDCORE_DIR . 'inc/seed.php';
require_once GRIDCORE_DIR . 'inc/admin-page.php';
require_once GRIDCORE_DIR . 'inc/staff-permissions.php';
require_once GRIDCORE_DIR . 'inc/upgrader.php';

require_once GRIDCORE_DIR . 'includes/framework-v23/bootstrap.php';
\Garry\Embedded\GridCore\V23\bootstrap( __FILE__, 'gridcore_garry_web_settings_page', 'Nastavení webu' );

/**
 * Aktivace: zaregistrovat CPT, přegenerovat pravidla přepisu URL a rovnou
 * spustit upgrader (inc/upgrader.php) – ten idempotentně přidělí všechny
 * capability (legacy i nové doménové) a provede zbytek schema migrace.
 */
register_activation_hook( __FILE__, function () {
	gridcore_register_cpts();
	gridcore_register_room_tax(); // taxonomie grid_room_cat — bez tohoto by flush níže proběhl
	                               // ještě před zaregistrováním jejích rewrite pravidel (chyběla
	                               // by URL /kategorie-pokoje/... do dalšího ručního flushe)
	flush_rewrite_rules();
	gridcore_run_upgrade();
} );
register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

/**
 * admin_init self-heal už NENÍ potřeba tady napevno pro tyhle 2 capability –
 * od 2.0.0 to řeší obecný, schema-verzovaný gridcore_run_upgrade() na
 * plugins_loaded (inc/upgrader.php, krok 1), který běží při každém requestu
 * dokud schema verze nedosáhne cíle, a jde spustit opakovaně bez rizika
 * (idempotentní). Nahrazuje dřívější přímý admin_init hook beze změny
 * záruky – administrátor dostane chybějící capability i po pouhém přepsání
 * souborů pluginu beze změny stavu aktivace.
 */

/* Admin upozornění: doporuč ACF PRO a nabídni naplnění obsahem */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) return;
	if ( ! class_exists( 'ACF' ) ) {
		echo '<div class="notice notice-warning"><p><strong>GARRY – GRID Core:</strong> pro editaci polí je potřeba <strong>Advanced Custom Fields PRO</strong>.</p></div>';
	}
} );
