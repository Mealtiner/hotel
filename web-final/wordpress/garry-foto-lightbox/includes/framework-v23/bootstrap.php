<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin garry-foto-lightbox.
 * Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Jediný integrační bod volaný z hlavního souboru pluginu. Vlastní PHP
 * namespace vylučuje jakoukoli kolizi s jiným GARRY pluginem – žádná
 * globální třída, žádný class_exists() dohad o tom, čí kopie "vyhrála".
 */

namespace Garry\Embedded\FotoLightbox\V23;

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
		'slug'               => 'garry-foto-lightbox',
		'name'               => 'GARRY – Foto lightbox',
		'plugin_version'     => '1.0.0',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 360,
		'local_menu_slug'    => 'garry-foto-lightbox',
		'icon'               => 'dashicons-format-image',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		'doc' => '<p>Lightbox se na frontendu aktivuje sám nad odkazy na obrázky ve vybraných kontejnerech (galerie WordPressu, galerie GARRY, vlastní selektory). Shortcode <code>[garry_lightbox]</code> slouží k přenastavení jedné stránky, <code>[garry_lightbox_galerie]</code> vykreslí vlastní mřížku náhledů. Nastavení je zde v GARRY nastavení (administrátor).</p>',
		'changelog' => array(
			array( 'version' => '1.0.0', 'date' => '2026-09-06', 'notes' => 'První verze. Lightbox s nastavitelným pozadím (plná barva, lineární/radiální/kónický přechod, čtyři rohy), logem webu, popiskem nad snímkem s tokeny, doprovodnými informacemi pod snímkem, vodorovným ukazatelem pořadí ve stylu trackovače na trati a nastavitelnými šipkami. Shortcody, Elementor widget a Divi 4 modul (na Divi 5 zůstává shortcode).' ),
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
