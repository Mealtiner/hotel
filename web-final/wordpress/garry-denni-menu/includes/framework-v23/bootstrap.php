<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-denni-menu.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\DenniMenu\V23;

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
		'slug'               => 'garry-denni-menu',
		'name'               => 'GARRY – Týdenní menu',
		'plugin_version'     => '1.8.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 300,
		'local_menu_slug'    => 'garry-denni-menu',
		'icon'               => 'dashicons-food',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Granulární oprávnění na kartu v GRID Nastavení (viz GARRY – GRID
		 * Core, inc/staff-permissions.php) – přidělitelné jednotlivým
		 * uživatelům nezávisle na GARRY Nastavení (to zůstává manage_options).
		 */
		'grid_capability'       => defined( 'GARRY_DENNI_MENU_STAFF_CAP' ) ? GARRY_DENNI_MENU_STAFF_CAP : null,
		'grid_capability_label' => 'Jídelní lístek',
		'doc' => '<p><code>[grid_menu_tydne provoz="..." typ="cely|denni|tydenni|stala|vecerni"]</code> — vypíše jídelníček daného provozu (atribut <code>provoz</code> je povinný u všech, i prvního). <code>[grid_napojovy_listek provoz="..." stitek="..."]</code> — nápojový lístek daného provozu, nepovinně jen jeden štítek. Oba fungují i jako Divi 4 widgety (Divi 5: jen shortcode). Prázdný jídelníček/lístek se na webu nezobrazí.</p><p>Provozy, jejich nabídky i obsah edituje personál v <strong>GRID Nastavení → Jídelní lístek</strong>; přidávání/mazání provozů jen administrátor v GARRY nastavení → Denní menu → Nastavení.</p>',
		/**
		 * Historie verzí – standing požadavek: každá úprava pluginu se
		 * loguje i v adminu (Info tab), ne jen v README.md. Nejnovější
		 * nahoře.
		 */
		'changelog' => array(
			array( 'version' => '1.8.0', 'date' => '2026-09-01', 'notes' => 'Nový nativní WordPress/Divi 5 blok "GARRY — Jídelníček" (garry/denni-menu) — atomický ekvivalent shortcode [grid_menu_tydne] pro editaci v blokovém/Divi 5 builderu, s vlastním UI (dropdown provoz + typ nabídky) a živým náhledem přes ServerSideRender. Standardní WP Block API (register_block_type + block.json), žádný build krok, žádná závislost na interních Divi balíčcích — blok se v inserteru objeví vedle vlastních Divi modulů, protože Divi 5 canvas je od základu WordPress Block Editor. render_callback volá přímo garry_menu_render(), žádná duplicitní logika.' ),
			array( 'version' => '1.7.0', 'date' => '2026-09-01', 'notes' => 'Nová nabídka typu "Večerní menu" — souběžně se stálou nabídkou, vlastní kategorie (výchozí: předkrmy/polévky/hlavní chody/přílohy/omáčky/dezerty), vlastní zapínání na provoz v Nastavení, vlastní [grid_menu_tydne typ="vecerni"]. Naplněno defaultní obsahem pro Hotelovou restauraci (stálá nabídka + večerní menu) podle dodaných PDF lístků, německý překlad doplněn tam, kde chyběl.' ),
			array( 'version' => '1.6.1', 'date' => '2026-09-01', 'notes' => 'Kritická oprava nahlášená z živého webu: přidání/smazání/hromadné přejmenování provozů v Nastavení tiše neuložilo žádnou změnu (zelená hláška o úspěchu se zobrazila, ale nic se nezapsalo). Příčina: garry_menu_sanitize() je zaregistrovaná přes register_setting() pro editor obsahu JEDNOHO provozu (options.php, vyžaduje venue_slug) — WordPress ale aplikuje sanitize_option_garry_menu filtr na KAŽDÉ update_option() volání nad touto option, ne jen na zápis z options.php. Strukturální formulář (přidat/smazat/přejmenovat provoz) venue_slug nemá, takže sanitizace zápis tiše zahodila zpět na starou hodnotu. garry_menu_handle_save_venues() teď kolem svého update_option() volání dočasně odpojí tenhle filtr.' ),
			array( 'version' => '1.6.0', 'date' => '2026-09-01', 'notes' => 'Fáze 3 GRID Suite refaktoringu. Registrace do GARRY – GRID Core modul registru (feature weekly_menu) — GRID Hotel Components teď najde skutečný poskytovatel místo neutrálního fallbacku. Nový obecný alias [garry_weekly_menu] pro standalone weby (stejný renderer jako [grid_menu_tydne]). Nové veřejné API garry_menu_get_current()/garry_menu_is_available().' ),
				array( 'version' => '1.5.0', 'date' => '2026-08-31', 'notes' => 'Podpora více provozů (restaurace, bar…) s vlastní kombinací nabídek; nový nápojový lístek (štítky + položky); shortcody vyžadují atribut provoz; Divi 4 widgety pro menu i nápojový lístek; jednorázová migrace starých dat na první provoz.' ),
			array( 'version' => '1.4.2', 'date' => '2026-08-31', 'notes' => 'Granulární capability pro GRID Nastavení (garry_grid_manage_denni_menu) místo plošného edit_others_posts; self-healing přidělení administrátorovi i bez reaktivace pluginu.' ),
			array( 'version' => '1.4.1', 'date' => '2026-08-30', 'notes' => 'Framework 2.4: vlastní PHP namespace, sdílené Přehled/Info/Log, oprava zdvojeného vykreslení sdílené stránky Přehled.' ),
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
		'garry_denni_menu_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
