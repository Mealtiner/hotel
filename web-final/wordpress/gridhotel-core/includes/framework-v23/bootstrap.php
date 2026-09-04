<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin gridhotel-core
 * ("GARRY – GRID Core"). Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 *
 * Nahrazuje dřívější vestavěnou třídu Garry_Promotion_Registry (framework
 * 2.1.0) v inc/garry.php – slug/folder zůstává `gridhotel-core` (jen
 * branding v Plugin Name se mění), aby WordPress bral tohle jako update
 * existujícího pluginu, ne jako nový.
 */

namespace Garry\Embedded\GridCore\V23;

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
		'slug'               => 'gridhotel-core',
		'name'               => 'GARRY – GRID Core',
		'plugin_version'     => '2.0.2',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 300,
		'local_menu_slug'    => 'gridhotel-core',
		'icon'               => 'dashicons-admin-settings',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Nepovinný klíč pro granulární oprávnění na kartu v GRID Nastavení
		 * (viz "Oprávnění personálu" níže) – čte ho jen tenhle plugin, ne
		 * framework samotný, ale žije v deskriptoru, protože ten je i tak
		 * jediný sdílený registr napříč aktivními GARRY pluginy.
		 */
		'grid_capability'    => null,
		'grid_capability_label' => null,
		'doc' => '<p>Globální obsah webu (hero texty, kontakty, sociální sítě, video) — od verze 2.0.0 vlastní stránku <strong>GRID Nastavení</strong> (<code>grid-options</code>) sám tenhle plugin (dřív child theme), funguje i bez ACF PRO (Settings API fallback). Do Theme Builderu se vkládá tokeny: <code>[grid_paticka_kontakt]</code> (kontakt v patičce), <code>[grid_socials]</code> (ikony sítí), <code>[grid_menu_hlavni]</code> (hlavní menu z WP menu — editace ve Vzhled → Menu), <code>[grid_ff_newsletter]</code> (newsletter formulář Fluent Forms dle jazyka). Mapa Fluent Forms formulářů má od 2.0.0 vlastní administraci pod GRID Nastavení → Formuláře a integrace (dřív jen option <code>grid_ff_forms</code> bez UI). Přístupová práva k jednotlivým typům obsahu: GRID Nastavení → Přístupy.</p>',
		'changelog' => array(
			array( 'version' => '2.0.2', 'date' => '2026-09-01', 'notes' => 'Opraveno hlášené 404 po kliknutí na Moduly/Přístupy/Formuláře a integrace/Diagnostika — tyhle 4 technické administrační stránky se přesunuly z GRID Nastavení (grid-options) do GARRY Nastavení (garry-nastaveni), kam patří jako správa webu, ne obsah pro personál hotelu. Pravděpodobná příčina 404: theme dosud souběžně registruje vlastní grid-options ACF stránku se stejným menu_slug, což bránilo WP správně přiřadit nové vlastní podstránky (staré podpoložky mířící na edit.php/edit-tags.php byly tímhle souběhem nedotčené). Dashboard "Nastavení webu" dostal odkazy na všechny 4 přesunuté stránky.' ),
			array( 'version' => '2.0.1', 'date' => '2026-09-01', 'notes' => 'Datové API doplněno o gridhotel_get_testimonials()/gridhotel_get_careers() — potřebuje GRID Hotel Components (fáze 2), aby i reference a kariéru četla přes Core API místo přímého CPT dotazu, stejně jako pokoje/zážitky/gastro.' ),
			array( 'version' => '2.0.0', 'date' => '2026-09-01', 'notes' => 'Fáze 1 GRID Suite refaktoringu. Skutečné doménové capabilities (grid_manage_*) na CPT/taxonomii místo plošného capability_type=post. Core teď sám vlastní grid-options (přesunuto z child theme, stejné field names, žádná migrace dat) s ACF-optional fallbackem. Nové: registr GRID modulů (gridhotel_register_module a spol.), datové API (gridhotel_get_rooms/room_categories/experiences/gastro_locations), stránky Přístupy/Formuláře a integrace/Diagnostika. Verzovaný idempotentní upgrader (gridhotel_core_db_version) nahrazuje ad-hoc admin_init self-heal. Opraven sdílený/kolizní option názvu Framework logu (dřív garry_denni_menu_framework_log, teď vlastní gridhotel_core_framework_log). grid_event se dál neseeduje (vlastnictví přechází na plugin Sezóna & čekací list).' ),
			array( 'version' => '1.6.0', 'date' => '2026-08-31', 'notes' => 'Přechod z vestavěné Garry_Promotion_Registry (framework 2.1.0) na Framework 2.4 – vlastní namespace, sdílené Přehled/Info/Log. Nová granulární oprávnění na kartu (Pokoje: fotky a galerie, Kariéra) přidělitelná jednotlivým uživatelům přes "Oprávnění personálu", ne jen plošné edit_others_posts. Obnovena "Implementační dokumentace" na sdíleném Přehledu.' ),
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
		'gridhotel_core_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
