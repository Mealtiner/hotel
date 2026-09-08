<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-turisticke-cile.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\TuristickeCile\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/Protocol.php';
require_once __DIR__ . '/Manifest.php';
require_once __DIR__ . '/Election.php';
require_once __DIR__ . '/LocalLog.php';
require_once __DIR__ . '/Diagnostics.php';
require_once __DIR__ . '/LocalAdmin.php';
require_once __DIR__ . '/GroupAdmin.php';
require_once __DIR__ . '/FrameworkBridge.php';

/**
 * @param string        $plugin_file    __FILE__ hlavního souboru pluginu.
 * @param callable|null $own_callback   Existující vykreslovací funkce vlastní admin stránky pluginu (beze změny), nebo null.
 * @param string        $own_tab_label  Popisek záložky pro $own_callback (ignorováno, pokud je $own_callback null).
 */
function bootstrap( $plugin_file, $own_callback = null, $own_tab_label = 'Nastavení' ) {
	$descriptor = array(
		'slug'               => 'garry-turisticke-cile',
		'name'               => 'GARRY – Turistické cíle',
		'plugin_version'     => '1.2.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 230,
		'local_menu_slug'    => 'garry-turisticke-cile',
		'icon'               => 'dashicons-location-alt',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Granulární oprávnění na kartu v GRID Nastavení (viz GARRY – GRID
		 * Core, inc/staff-permissions.php) – přidělitelné jednotlivým
		 * uživatelům nezávisle na GARRY Nastavení (to zůstává manage_options).
		 */
		'grid_capability'       => defined( 'GARRY_TC_STAFF_CAP' ) ? GARRY_TC_STAFF_CAP : null,
		'grid_capability_label' => 'Turistické cíle',
		'doc' => '<p><code>[grid_turisticke_cile]</code> — filtrovatelné karty míst v okolí (vzdálenost vlevo nahoře, ikona štítku vpravo nahoře, mřížka 3 / 2 / 1 sloupec podle šířky). Atribut <code>stitky="0"</code> skryje panel filtru. Obsah se spravuje na této stránce nebo v GRID Nastavení → Turistické cíle: záložka Štítky drží skupiny pro filtr, záložka Cíle jednotlivá místa (název a popis CZ/EN/DE, vzdálenost, odkaz, přepínač zobrazení). Pořadí karet určuje pořadí v seznamu — přetažením nebo šipkami.</p>',
		'changelog' => array(
			array( 'version' => '1.2.0', 'date' => '2026-09-08', 'notes' => 'Název místa v kartě je h3 místo h4. Karty stojí ve stejné rovině jako vedoucí karta nad mřížkou, nejsou jí podřízené, takže h4 osnovu zbytečně zanořovalo. Úroveň navíc rozhodovala o písmu: motiv vynucuje nadpisové písmo jen pro h1–h3, takže názvy míst tiše padaly na písmo běžného textu a jako jediné karty na webu se lišily.' ),
			array( 'version' => '1.1.0', 'date' => '2026-09-08', 'notes' => 'Administrace rozdělena na dvě záložky (Cíle / Štítky), každý cíl je sbalitelný a pořadí se určuje pozicí v seznamu — přetažením myší nebo šipkami z klávesnice, místo ručního číslování. Stejná stránka je nově i v GRID Nastavení. Opraveny zbytky po pluginu Kategorie pokojů, ze kterého byl deskriptor zkopírovaný: plugin psal do jeho logovací option (garry_kategorie_pokoju_framework_log), dědil jeho oprávnění (GARRY_POK_STAFF_CAP) a v nápovědě i v historii verzí měl jeho obsah. Ikona v mapě glyfů dostala vlastní kód (f507) — dřív přepisovala dashicons-admin-multisite a zobrazovala se jako jeho glyf.' ),
			array( 'version' => '1.0.0', 'date' => '2026-09-08', 'notes' => 'První verze. Data převzata z dosavadní pevné sekce „Co máte na dosah“ — 18 míst ve třech jazycích a 4 štítky. Doprava (letiště, vlak, autobus) v pluginu není, ta patří na stránku Jak se k nám dostanete.' ),
		),
	);

	/**
	 * Konzistence tříd (2.4): pokud namespace nesedí napříč soubory frameworku
	 * (např. částečně nasazená/poškozená kopie), třídy v očekávaném namespace
	 * neexistují. Bez téhle kontroly by new FrameworkBridge() skončilo PHP
	 * fatal chybou. Místo toho se sdílené GARRY menu pro tento plugin bezpečně
	 * vypne, zobrazí se admin oznámení, a pokud plugin dodal vlastní
	 * $own_callback, zůstane dosažitelný přes nouzové top-level menu bez
	 * jakékoli závislosti na Frameworku.
	 */
	$required = array( 'Protocol', 'Election', 'Manifest', 'LocalLog', 'Diagnostics', 'LocalAdmin', 'GroupAdmin', 'FrameworkBridge' );
	$missing  = array();
	foreach ( $required as $class ) {
		if ( ! class_exists( __NAMESPACE__ . '\\' . $class, false ) ) {
			$missing[] = $class;
		}
	}
	if ( ! empty( $missing ) ) {
		add_action( 'admin_notices', function () use ( $missing, $descriptor ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf(
					'GARRY Embedded Framework (%1$s): nekonzistentní instalace – chybí třída/y %2$s v očekávaném namespace. Sdílené GARRY menu je pro tento plugin dočasně vypnuté, aby nedošlo k chybě.',
					$descriptor['name'],
					implode( ', ', $missing )
				) )
			);
		} );
		if ( $own_callback ) {
			add_action( 'admin_menu', function () use ( $descriptor, $own_callback ) {
				add_menu_page(
					$descriptor['name'],
					$descriptor['name'],
					'manage_options',
					$descriptor['local_menu_slug'],
					$own_callback,
					$descriptor['icon'],
					81
				);
			} );
		}
		return;
	}

	$bridge = new FrameworkBridge(
		$descriptor,
		plugin_dir_path( $plugin_file ),
		plugin_dir_url( $plugin_file ),
		'manage_options',
		'garry_turisticke_cile_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
