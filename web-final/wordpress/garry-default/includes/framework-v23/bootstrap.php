<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-default.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\GarryDefault\V23;

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
		'slug'               => 'garry-default',
		'name'               => 'GARRY – Výchozí zabezpečení webu',
		'plugin_version'     => '2.3.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 300,
		'local_menu_slug'    => 'garry-default',
		'icon'               => 'dashicons-shield',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		/**
		 * Historie verzí – standing požadavek: každá úprava pluginu se
		 * loguje i v adminu (Info tab), ne jen v README.md. Plná historie
		 * je v README.md, tady jen posledních pár verzí pro rychlý přehled.
		 * POZOR: 'plugin_version' výše byl dlouho zaseknutý na '1.4.0' i po
		 * mnoha reálných vydáních – tenhle descriptor se při běžných
		 * verzních bumpech nesynchronizoval automaticky, jen hlavičkа
		 * pluginu a manifest. Napříště hlídat i tohle pole.
		 */
		'changelog' => array(
			array( 'version' => '2.3.0', 'date' => '2026-09-01', 'notes' => 'Nový katalog nejnovějších verzí GARRY pluginů (includes/security/security-version-catalog.php) — ručně vedený, protože GARRY pluginy nemají skutečný update server a WordPress nativní kontrola aktualizací je proto pro celou rodinu vždy prázdná. Nová kontrola GARRY-013 porovnává nasazenou verzi každého aktivního GARRY pluginu proti katalogu a hlásí zastaralé; panel "GARRY ekosystém" nově ukazuje počet zastaralých modulů a u každého i nejnovější dostupnou verzi.' ),
			array( 'version' => '2.2.0', 'date' => '2026-08-31', 'notes' => 'Kapacitní matice: "V pořádku" jen s reálným důkazem, jinak "Zajištěno — čeká na ověření". Nové kontroly: vystavené citlivé soubory, neaktivní pluginy, SPF/DMARC, rozšířené bezpečnostní hlavičky.' ),
			array( 'version' => '2.1.0', 'date' => '2026-08-31', 'notes' => 'Sjednocený stavový slovník, rozšířená kapacitní matice s výjimkami "Nevyžadováno", GARRY ekosystém jako modulová tabulka.' ),
			array( 'version' => '2.0.0', 'date' => '2026-08-30', 'notes' => '"Control plane" redesign: Akce nyní, přecejchování závažností, DELEGATED → "Ověřeno přes…", DNS/htaccess/LiteSpeed karty.' ),
			array( 'version' => '1.8.0', 'date' => '2026-08-31', 'notes' => 'Ověření konfigurace Simple Cloudflare Turnstile, napojení na nativní WordPress Privacy API, karta PHP limitů.' ),
			array( 'version' => '1.7.0', 'date' => '2026-08-31', 'notes' => 'Jednokliknové bezpečné opravy přes vlastní mu-plugin, karta prostředí a serveru, záloha .htaccess, správa robots.txt/llms.txt.' ),
			array( 'version' => '1.6.0', 'date' => '2026-08-30', 'notes' => 'Fáze D: nápověda z historie záloh UpdraftPlus, bezpečné remediation snippety, hlášky ohledné na aktivní Turnstile.' ),
			array( 'version' => '1.5.0', 'date' => '2026-08-30', 'notes' => 'Fáze C: kontroly GARRY-004..012 nad manifesty ekosystému (admin assety, anti-spam, retence, shoda verze).' ),
			array( 'version' => '1.4.0', 'date' => '2026-08-30', 'notes' => 'Framework 2.3 migrace (vlastní PHP namespace místo sdílené třídy Garry_Promotion_Registry) + kontroly GARRY-001..003 kolize ekosystému.' ),
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
		'garry_default_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
