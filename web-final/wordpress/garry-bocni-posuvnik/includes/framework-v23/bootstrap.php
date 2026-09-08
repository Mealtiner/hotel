<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-bocni-posuvnik.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\BocniPosuvnik\V23;

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
		'slug'               => 'garry-bocni-posuvnik',
		'name'               => 'GARRY – Sekční navigace',
		'plugin_version'     => '1.7.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 200,
		'local_menu_slug'    => 'garry-bocni-posuvnik',
		'icon'               => 'dashicons-editor-ol',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		'doc' => '<p>Boční posuvník (pravá svislá navigace sekcí) se vykresluje na frontendu automaticky — bez shortcodu. Nastavení je pouze zde v GARRY nastavení (administrátor).</p>',
		'changelog' => array(
			array( 'version' => '1.9.1', 'date' => '2026-09-08', 'notes' => 'Oprava regrese z 1.9.0: výplň se posunula až k prvnímu bodu, takže čára nezačínala u horní hrany lišty. Počátek výplně se zase měří od lišty samotné.' ),
			array( 'version' => '1.9.0', 'date' => '2026-09-08', 'notes' => 'Červená čára postupu se řídí body lišty, ne procentem sjetí stránky — dřív ukazovala jinam než rozsvícený bod (na titulní straně až o dva body vedle). Aktivní bod i výška čáry teď vycházejí z jednoho výpočtu, takže se nemůžou rozejít. Po načtení stránky je čára prázdná a roste teprve s rolováním. Poloha sekcí se měří vůči dokumentu, ne přes offsetTop — Divi sekce mají různé umístěné předky a výpočet ujížděl o celou sekci.' ),
			array( 'version' => '1.8.0', 'date' => '2026-09-08', 'notes' => 'Popisek START patří jen titulní straně; na podstránkách začínají body od T1, aby odpovídaly vodoznakům sekcí. Zároveň srovnány verze v hlavičce, konstantě a manifestu, které se rozešly.' ),
			array( 'version' => '1.7.0', 'date' => '2026-09-06', 'notes' => 'Doplněny popisky sekcí prehled-pokoju, srovnani-pokoju, vybaveni a dobre-vedet ve všech třech jazycích — nové sekce stránky Ubytování se do bočního posuvníku hlásily bez názvu. Zároveň srovnán plugin_version v deskriptoru frameworku, který zůstal na 1.4.2, zatímco hlavička souboru i manifest byly na 1.6.0.' ),
			array( 'version' => '1.4.2', 'date' => '2026-09-02', 'notes' => 'Oprava vizuální regrese nahlášené z živého webu: v1.4.0 přidalo vlastní portable CSS/JS (assets/section-nav.css, .garry-section-nav třída) vedle theme třídy .track-progress na stejném <nav> elementu. Protože section-nav.css se z WordPressu načítá AŽ PO theme style.css a jeho selektory (.garry-section-nav .tp-X) mají stejnou nebo vyšší specificitu než odpovídající theme pravidla (často jen holé .tp-X), plugin styl ticho přebil zamýšlený GRID vzhled: postupová čára ztratila správnou pozici (.tp-rail/.tp-fill top:0 místo 48px, translatilo se do překryvu s nadpisem "Masaryk Circuit"), tečky byly mimo střed čáry (jiná šířka/layout mechanismus – flex mísený s absolutní pozicí) a hlavně aktivní tečka/podnadpis se vůbec nezbarvily červeně/zlatě, protože JS nastavoval jen aria-current atribut, ne .active třídu, na které je theme barevná logika založená. Řešení: <nav> teď nese jen .track-progress (theme třída sedí s živým referenčním webem https://gridhotel.garryjob.cz/ 1:1, section-nav.css tím zůstává neaktivní), JS selektor přepnut z .garry-section-nav na .track-progress a setActive() teď kromě aria-current přepíná i .active třídu.' ),
			array( 'version' => '1.4.1', 'date' => '2026-09-01', 'notes' => 'Oprava: garry_scr_detect() hledala kotvy sekcí jako doslovný řetězec id="X" (i v escapovaných variantách), což fungovalo jen pro starší formát obsahu (syrové HTML vložené jako jeden textový řetězec do JSON hodnoty Divi bloku). Po přestavbě homepage na atomizované Divi 5 moduly (id uložené jako strukturovaný pár "name":"id","value":"X") se tak na CZ verzi homepage detekovaly jen 3 z 10 sekcí (zbylé 2 – Pokoje/Sezóna – jen náhodou přes shodu začátku jiného shortcode tagu), zatímco EN/DE verze ve starším formátu obsahu byly v pořádku. Doplněn nový vzor detekce pro strukturovaný JSON formát.' ),
			array( 'version' => '1.4.0', 'date' => '2026-09-01', 'notes' => 'Fáze 8 GRID Suite refaktoringu — zásadní oprava: plugin dřív vykresloval jen HTML (tlačítka bez href), funkční JS/CSS (scroll-spy, aktivní stav, smooth scroll) dodával child theme. Markup teď používá skutečné <a href="#id"> (funguje i bez JS/klávesnicí), vlastní CSS (assets/section-nav.css, CSS custom properties, žádné natvrdo GRID barvy) i JS (assets/section-nav.js — IntersectionObserver, prefers-reduced-motion, aria-current, jeden sdílený listener). Nový obecný shortcode [garry_section_navigation] a veřejné API garry_section_nav_render()/parse_items(). Registrace do GARRY – GRID Core modul registru (feature section_navigation).' ),
			array( 'version' => '1.3.1', 'date' => '2026-08-31', 'notes' => 'Obnovena implementační dokumentace na sdíleném Přehledu (dřívější funkce vypadlá při přechodu na Framework 2.4).' ),
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
		'garry_bocni_posuvnik_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
