<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-kategorie-pokoju.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\KategoriePokoju\V23;

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
		'slug'               => 'garry-kategorie-pokoju',
		'name'               => 'GARRY – Kategorie a srovnání',
		'plugin_version'     => '1.5.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 220,
		'local_menu_slug'    => 'garry-kategorie-pokoju',
		'icon'               => 'dashicons-admin-multisite',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Granulární oprávnění na kartu v GRID Nastavení (viz GARRY – GRID
		 * Core, inc/staff-permissions.php) – přidělitelné jednotlivým
		 * uživatelům nezávisle na GARRY Nastavení (to zůstává manage_options).
		 */
		'grid_capability'       => defined( 'GARRY_POK_STAFF_CAP' ) ? GARRY_POK_STAFF_CAP : null,
		'grid_capability_label' => 'Kategorie pokojů',
		'doc' => '<p><code>[grid_rooms_cards]</code> — karty kategorií pokojů (titulní stránka, Ubytování). <code>[grid_rooms_table]</code> — přehledová tabulka pokojů s odkazy na detail. Srovnávací tabulka na detailu kategorie se vykresluje šablonou <code>taxonomy-grid_room_cat.php</code> (funkce <code>garry_pokoje_compare_html()</code>), názvy typů jsou odkazy na detail.</p><p>Texty, štítky a srovnávací tabulku edituje personál v <strong>GRID Nastavení → Kategorie pokojů</strong>; fotky a galerie v <strong>GRID Nastavení → Pokoje: fotky a galerie</strong>.</p>',
		'changelog' => array(
			array( 'version' => '1.5.0', 'date' => '2026-09-06', 'notes' => 'Novy shortcode [grid_rooms_compare] — srovnávací tabulka mimo detail kategorie, bez zvýrazněného sloupce (atribut zvyraznit ho umí zapnout). Do té doby šla tabulka vykreslit jen ze šablony detailu, takže na stránce Ubytování nešla použít.' ),
			array( 'version' => '1.4.0', 'date' => '2026-09-01', 'notes' => 'Fáze 4 GRID Suite refaktoringu. Registrace do GARRY – GRID Core modul registru (feature room_comparison) — GRID Hotel Components teď najde skutečný poskytovatel karet/tabulky. Nový obecný alias [garry_room_categories view="cards|table"] a veřejné API garry_rooms_get_categories/render_cards/render_table pro standalone weby. Core-vs-standalone provider split (GRID-SUITE-04 §2) zatím NEIMPLEMENTOVÁN — garry_pokoje zůstává jediným zdrojem dat i na GRID webu, žádná regrese, ale dosud beze změny vůči 1.3.1.' ),
			array( 'version' => '1.3.1', 'date' => '2026-08-31', 'notes' => 'Granulární capability pro GRID Nastavení (garry_grid_manage_kategorie_pokoju) místo plošného edit_others_posts; self-healing přidělení administrátorovi i bez reaktivace pluginu; obnovena implementační dokumentace na sdíleném Přehledu.' ),
			array( 'version' => '1.3.0', 'date' => '2026-08-30', 'notes' => 'Framework 2.3 → 2.4 migrace (vlastní PHP namespace místo sdílené třídy Garry_Promotion_Registry), přejmenování na marketingový název, oprava zdvojeného vykreslení sdílené stránky Přehled a nadměrného zápisu do logu.' ),
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
		'garry_kategorie_pokoju_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
