<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-typografie.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\Typografie\V23;

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
		'slug'               => 'garry-typografie',
		'name'               => 'GARRY – Typografie',
		'plugin_version'     => '1.2.2',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 50,
		'local_menu_slug'    => 'garry-typografie',
		'icon'               => 'dashicons-editor-textcolor',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		'changelog' => array(
			array( 'version' => '1.2.2', 'date' => '2026-09-01', 'notes' => 'Odstraněny zbylé viditelné stopy po CIT v admin UI: úvodní text na stránce nastavení, tlačítko "Načíst doporučené nastavení pro CIT" (i data barev, co posílalo do prohlížeče), a ukázkový náhledový obsah (nadpisy/menu/tabulka) přepsán z textů kliniky na texty GRID Hotelu. Nadpisům a základnímu textu doplněny reálné barvy z aktuálního webu (--fg/--muted podle .sec-light/.sec-dark v style.css) přímo do výchozích hodnot – dřív byly barvy záměrně prázdné, teď je klient chtěl rovnou předvyplněné podle živého webu.' ),
			array( 'version' => '1.2.1', 'date' => '2026-09-01', 'notes' => 'Oprava: automatická migrace pluginu (spouští se při první návštěvě admin stránky) chybně považovala i zcela čerstvou instalaci bez uloženého nastavení za starší instalaci CIT vyžadující dosazení fontu Nagel – u nové instalace tak sama přepsala výchozí custom_font_enabled zpět na zapnuto a vyplnila pole vlastního fontu soubory Nagel, i když administrátor žádný vlastní font nezvolil. Migrace teď běží jen tam, kde už dřív existoval uložený záznam nastavení. Zároveň vyčištěny obecné výchozí hodnoty pluginu (custom_font_family/custom_font_faces) od natvrdo vepsaného fontu Nagel, aby se nový klient v adminu nesetkal s názvem fontu předchozího klienta.' ),
			array( 'version' => '1.2.0', 'date' => '2026-09-01', 'notes' => 'Nová GRID Hotel varianta: pro každou vlastnost prvku (font, tučnost, velikost, řádkování, rozpal, transformace textu) jde nastavit samostatná hodnota pro tmavé pozadí (ne jen barva jako dřív) – prázdné pole = přebírá se světlá hodnota. Nově generuje i pojmenované třídy (.garry-typo-{prvek}, prefix nastavitelný) pro ruční přiřazení modulu v Divi builderu. Výchozí hodnoty pro GRID Hotel předvyplněny podle reálného živého webu (fonty/velikosti/řádkování), barvy záměrně ponechány prázdné.' ),
			array( 'version' => '1.1.4', 'date' => '2026-08-31', 'notes' => 'Doplněna historie verzí do Info tabu (zpětná rekonstrukce z git historie a session poznámek).' ),
			array( 'version' => '1.1.3', 'date' => '2026-08-30', 'notes' => 'Framework 2.3 → 2.4 migrace (vlastní PHP namespace místo sdílené třídy Garry_Promotion_Registry), přejmenování na marketingový název, oprava zdvojeného vykreslení sdílené stránky Přehled a nadměrného zápisu do logu. Tenhle plugin dnes v testovací kombinaci všech 22 pluginů vyhrává volbu o sdílené root menu (nejnižší abecední slug) – Přehled/Info tedy typicky vykresluje jeho kopie frameworku.' ),
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
		'garry_typografie_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
