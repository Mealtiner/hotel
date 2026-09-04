<?php
/**
 * GARRY Security – veřejně dostupné citlivé soubory a výpis adresářů
 * (verze 2.2, P1 "Rizikové soubory a adresáře").
 *
 * Stejný bezpečný self-request vzor jako jinde v pluginu – jen GET na
 * předem známé, pevné cesty na vlastním home_url(), nikdy na URL z
 * requestu. Žádné hádání/procházení (crawling), jen kontrola hrstky
 * dobře známých rizikových cest. Falešně pozitivní výsledek (např. web
 * má vlastní stránku na /.env, nebo hosting vrací 200 pro cokoli) je
 * možný – proto se u nálezu vždy uvádí přesná URL k ručnímu ověření.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Cesty, které by na produkčním WordPress webu neměly být veřejně čitelné.
 * `must_not_contain` je volitelný textový signál, který v odpovědi
 * vylučuje typické "404 stránka" HTML výstupy generované samotným
 * WordPressem (aby se nehlásil false-positive jen proto, že web vrací
 * 200 i pro neexistující cesty – běžné u některých frameworků/CDN).
 */
function garry_security_exposed_paths() {
	return array(
		'.env'           => array( 'label' => '.env', 'severity' => 'critical' ),
		'.git/config'    => array( 'label' => '.git/config', 'severity' => 'critical' ),
		'wp-config.php.bak' => array( 'label' => 'wp-config.php.bak', 'severity' => 'critical' ),
		'wp-config.bak'  => array( 'label' => 'wp-config.bak', 'severity' => 'critical' ),
		'backup.sql'     => array( 'label' => 'backup.sql', 'severity' => 'high' ),
		'database.sql'   => array( 'label' => 'database.sql', 'severity' => 'high' ),
		'debug.log'      => array( 'label' => 'debug.log', 'severity' => 'medium' ),
		'wp-content/debug.log' => array( 'label' => 'wp-content/debug.log', 'severity' => 'medium' ),
	);
}

/**
 * EXP-001: přímý HTTP přístup k hrstce dobře známých citlivých cest.
 * Vyhodnocuje se jen HTTP status a přítomnost typického obsahu (ne
 * plná analýza) – 200 s neprázdným tělem u těchto konkrétních cest je
 * silný signál, protože žádná z nich nemá na běžném WordPress webu
 * legitimní důvod existovat jako veřejně čitelný soubor.
 */
function garry_security_check_exposed_files() {
	$found = array();
	$errors = 0;

	foreach ( garry_security_exposed_paths() as $path => $info ) {
		$response = wp_safe_remote_get( home_url( '/' . $path ), array( 'timeout' => 4, 'redirection' => 0 ) );
		if ( is_wp_error( $response ) ) {
			$errors++;
			continue;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 200 === $code && strlen( trim( $body ) ) > 0 ) {
			$found[] = array( 'path' => $path, 'label' => $info['label'], 'severity' => $info['severity'] );
		}
	}

	if ( ! empty( $found ) ) {
		$worst = 'medium';
		foreach ( $found as $f ) {
			if ( 'critical' === $f['severity'] ) { $worst = 'critical'; break; }
			if ( 'high' === $f['severity'] && 'critical' !== $worst ) { $worst = 'high'; }
		}
		return array(
			'state'    => 'critical' === $worst ? 'critical' : 'warning',
			'severity' => $worst,
			'message'  => sprintf(
				'Veřejně dostupné citlivé cesty: %s. Ověřte ručně (může jít i o falešný poplach, pokud hosting vrací 200 pro neexistující URL) a pokud jde o skutečný soubor, okamžitě ho smažte nebo zablokujte.',
				implode( ', ', wp_list_pluck( $found, 'label' ) )
			),
			'evidence' => array( 'found' => $found ),
		);
	}

	if ( $errors === count( garry_security_exposed_paths() ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se ověřit žádnou z kontrolovaných cest (chyba požadavků).',
			'evidence' => array(),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Žádná z kontrolovaných citlivých cest (.env, .git/config, zálohy, debug.log…) není veřejně dostupná.',
		'evidence' => array(),
	);
}

/**
 * EXP-002: výpis obsahu adresáře wp-content/uploads/ – typický signál, že
 * server nemá vypnuté "Options +Indexes" a chybí index.php/index.html.
 * Hledá se typický autoindex vzor ("Index of /"), ne jen HTTP 200 (ten
 * vrátí i legitimní prázdná odpověď).
 */
function garry_security_check_directory_listing() {
	$upload_dir = wp_get_upload_dir();
	$url        = trailingslashit( $upload_dir['baseurl'] );

	$response = wp_safe_remote_get( $url, array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se ověřit výpis adresáře uploads (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( 200 === $code && ( false !== stripos( $body, 'Index of /' ) || false !== stripos( $body, '<title>Index of' ) ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( 'Adresář %s vypisuje svůj obsah veřejně (autoindex) – doporučeno vypnout Options +Indexes nebo doplnit prázdný index.php.', $url ),
			'evidence' => array( 'url' => $url, 'status' => $code ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Adresář uploads nevypisuje svůj obsah veřejně.',
		'evidence' => array( 'url' => $url, 'status' => $code ),
	);
}

function garry_security_collect_exposed_files() {
	return array(
		'EXP-001' => garry_security_check_exposed_files(),
		'EXP-002' => garry_security_check_directory_listing(),
	);
}

function garry_security_exposed_files_catalog_entries() {
	return array(
		'EXP-001' => array( 'title' => 'Veřejně dostupné citlivé soubory (.env, .git, zálohy…)', 'category' => 'Vystavené soubory' ),
		'EXP-002' => array( 'title' => 'Výpis obsahu adresáře uploads', 'category' => 'Vystavené soubory' ),
	);
}
