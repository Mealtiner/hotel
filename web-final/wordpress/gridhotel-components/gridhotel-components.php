<?php
/**
 * Plugin Name:       GARRY – GRID Components
 * Plugin URI:        https://www.garry.cz
 * Description:       Funkční shortcody webu GRID Hotel (30 z 32 sekcí přesunutých z child theme) — hero, pokoje, gastro, zážitky, sezóna, kontakt, právní texty a další. Vyžaduje GARRY – GRID Core. Součást ekosystému GARRY Promotion.
 * Version:           1.0.4
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       gridhotel-components
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDHOTEL_COMPONENTS_VER', '1.0.4' );
define( 'GRIDHOTEL_COMPONENTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRIDHOTEL_COMPONENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'GRIDHOTEL_COMPONENTS_FILE', __FILE__ );
define( 'GRIDHOTEL_COMPONENTS_MIN_CORE_API', '1.0.0' );

require_once GRIDHOTEL_COMPONENTS_DIR . 'inc/admin-page.php';

require_once GRIDHOTEL_COMPONENTS_DIR . 'includes/framework-v23/bootstrap.php';
\Garry\Embedded\GridComponents\V23\bootstrap( __FILE__, 'gridc_render_admin_page', 'Komponenty' );

/**
 * Shortcody se registrují až na plugins_loaded, ne na top-level provedení
 * tohoto souboru — pořadí načítání pluginů WordPress NEZARUČUJE (nezávisí
 * na abecedě názvu složky), takže v okamžiku, kdy WP includuje TENHLE
 * soubor, gridhotel-core ještě klidně nemusí být načtené. Na plugins_loaded
 * už mají VŠECHNY aktivní pluginy své funkce definované, ať byly načtené v
 * jakémkoli pořadí — bezpečná kontrola závislosti (GRID-SUITE-02 §2: "musí
 * selhat řízeně... nikdy nezpůsobit fatal error na frontendu").
 */
add_action( 'plugins_loaded', function () {
	if ( ! function_exists( 'gridhotel_get_option' ) || ! function_exists( 'gridhotel_core_api_version' ) ) {
		add_action( 'admin_notices', function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p><strong>GARRY – GRID Components:</strong> vyžaduje aktivní plugin <strong>GARRY – GRID Core</strong> (≥ 2.0.0). Bez něj se shortcody webu GRID Hotel neregistrují.</p></div>';
		} );
		return; // Řízené selhání — žádný shortcode, žádný fatal na frontendu.
	}

	require_once GRIDHOTEL_COMPONENTS_DIR . 'inc/helpers.php';
	require_once GRIDHOTEL_COMPONENTS_DIR . 'inc/module-renderer.php';
	require_once GRIDHOTEL_COMPONENTS_DIR . 'inc/shortcode-registry.php';
	require_once GRIDHOTEL_COMPONENTS_DIR . 'inc/assets.php';

	foreach ( glob( GRIDHOTEL_COMPONENTS_DIR . 'inc/shortcodes/*.php' ) as $file ) {
		require_once $file;
	}
}, 20 );
