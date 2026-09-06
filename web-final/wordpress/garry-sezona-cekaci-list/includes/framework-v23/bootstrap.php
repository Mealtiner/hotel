<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-sezona-cekaci-list.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\SezonaCekaciList\V23;

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
		'slug'               => 'garry-sezona-cekaci-list',
		'name'               => 'GARRY – Sezónní nabídka a čekací list',
		'plugin_version'     => '2.7.2',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 250,
		'local_menu_slug'    => 'garry-sezona-cekaci-list',
		'icon'               => 'dashicons-calendar-alt',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Granulární oprávnění na kartu v GRID Nastavení (viz GARRY – GRID
		 * Core, inc/staff-permissions.php) – přidělitelné jednotlivým
		 * uživatelům nezávisle na GARRY Nastavení (to zůstává manage_options).
		 */
		'grid_capability'       => defined( 'GARRY_SEZ_STAFF_CAP' ) ? GARRY_SEZ_STAFF_CAP : null,
		'grid_capability_label' => 'Sezóna & čekací list',
		'doc' => '<p><code>[grid_season_events limit="5" rezim="seznam"]</code> — seznam akcí + čekací list (titulní stránka: 5 nejbližších). <code>[grid_season_events limit="0" rezim="karty"]</code> — všechny budoucí akce jako karty (stránka Sezóna). <code>[grid_voucher_form]</code> — objednávka dárkového poukazu (Zážitky).</p><p>Čekací list i poukazy jedou přes <strong>Fluent Forms</strong> (mapa formulářů v option <code>grid_ff_forms</code>): select akce se plní dynamicky budoucími akcemi, odeslání se zapisuje do logu pluginu a posílá na e-mail z nastavení. Anti-spam lze napojit filtrem <code>garry_sez_verify_request</code>.</p><p>Akce, štítky a log spravuje personál v <strong>GRID Nastavení → Sezóna & čekací list</strong>.</p>',
		'changelog' => array(
			array( 'version' => '2.7.2', 'date' => '2026-09-06', 'notes' => 'Uplynula akce uz nedostava odznak Publikovana (nad limit) — do limitu se pocitaji jen pripravovane akce. Uplynula ma neutralni odznak Probehla a sedy prouzek.' ),
			array( 'version' => '2.7.1', 'date' => '2026-09-06', 'notes' => 'Perioda automatickeho importu jde nastavit: denne, dvakrat denne, tydne, jednou za 14 dni nebo mesicne. Zmena se projevi hned po ulozeni — plan se zrusi a naplanuje znovu, protoze wp_schedule_event() existujici plan neprepise.' ),
			array( 'version' => '2.7.0', 'date' => '2026-09-06', 'notes' => 'Tydenni import zavodu z kalendare Automotodromu. Nove akce se zapisuji jako nepublikovane s priznakem nova akce, hlasi se upozornenim na nastence a v prehledu jsou podbarvene; chybejici preklady maji cervene pole. Akce se v administraci deli na pripravovane a uplynule, pribyla volba maximalniho poctu pripravovanych akci na webu (vychozi 5) a odkaz na detail akce v anglictine pro EN i DE verzi webu.' ),
			array( 'version' => '2.6.0', 'date' => '2026-09-01', 'notes' => 'Fáze 5 GRID Suite refaktoringu — opraveny release blockers z GRID-SUITE-03: skutečný rate limit na obou AJAX handlerech (dřív jen nonce, žádný limit), sjednocená a skutečně vynucená retence 90 dní denním cronem (dřív jen count-cap 300 bez časové platnosti, manifest sliboval 90 dní, žádné se nevynucovalo), registrace WordPress Privacy API exporteru a eraseru (dřív úplně chyběly), kontrola výsledku wp_mail() se záznamem mail_failed do logu místo tichého ignorování. Registrace do GARRY – GRID Core modul registru (feature season_events). GDPR legacy import z grid_event zatím NEIMPLEMENTOVÁN (GRID-SUITE-03 §5) — beze změny vůči 2.5.1.' ),
			array( 'version' => '2.5.1', 'date' => '2026-08-31', 'notes' => 'Granulární capability pro GRID Nastavení (garry_grid_manage_sezona_cekaci_list) místo plošného edit_others_posts; self-healing přidělení administrátorovi i bez reaktivace pluginu; obnovena implementační dokumentace na sdíleném Přehledu.' ),
			array( 'version' => '2.5.0', 'date' => '2026-08-30', 'notes' => 'Framework 2.3 → 2.4 migrace (vlastní PHP namespace místo sdílené třídy Garry_Promotion_Registry), přejmenování na marketingový název, ikona v menu přeznačena na dashicons-calendar-alt kvůli kolizi s jiným pluginem, oprava zdvojeného vykreslení sdílené stránky Přehled a nadměrného zápisu do logu.' ),
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
		'garry_sezona_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
