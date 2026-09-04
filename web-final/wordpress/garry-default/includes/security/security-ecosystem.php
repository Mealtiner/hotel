<?php
/**
 * GARRY Security – kompatibilita ekosystému GARRY pluginů (GARRY-001..003).
 *
 * Podle docs/GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md, Fáze A/C, a
 * docs/GARRY-WP-PLUGIN-ECOSYSTEM-STANDARD.md. Auditní zjištění: sdílený
 * `Garry_Promotion_Registry` framework je v každém GARRY pluginu vložený
 * jako vlastní kopie chráněná jen `class_exists()`. Který plugin se načte
 * první, ten "vyhraje" a definuje třídu pro všechny ostatní – pokud mají
 * různou verzi a novější plugin volá metodu/konstantu, kterou starší verze
 * nemá, skončí to PHP fatal chybou. Tento soubor to jen READ-ONLY detekuje
 * a hlásí; nic nepřepisuje ani neopravuje (to by vyžadovalo zásah do
 * cizích pluginů, což je mimo rozsah GARRY Default).
 *
 * Čtení cizích pluginových souborů je čistě textové (`file_get_contents`)
 * – nikdy `include`/`require`, aby GARRY Security nikdy neprovedl cizí kód.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Složka pluginu z `plugin_file` klíče vráceného `get_plugins()`
 * (formát „slozka/soubor.php", u pluginů bez podsložky jen „soubor.php").
 */
function garry_security_plugin_folder( $plugin_file ) {
	$folder = strstr( $plugin_file, '/', true );
	return false === $folder ? $plugin_file : $folder;
}

/**
 * Aktivní pluginy s prefixem `garry-` nebo `cit-` (viz ekosystémový
 * standard, sekce 1: „Platí pro každý plugin s předponou garry- a cit-").
 * Vrací plugin_file => plugin data z get_plugins().
 */
function garry_security_active_garry_plugin_files() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$matches = array();
	foreach ( get_plugins() as $plugin_file => $data ) {
		if ( ! is_plugin_active( $plugin_file ) ) {
			continue;
		}
		$folder = garry_security_plugin_folder( $plugin_file );
		if ( 0 === strpos( $folder, 'garry-' ) || 0 === strpos( $folder, 'cit-' ) ) {
			$matches[ $plugin_file ] = $data;
		}
	}
	return $matches;
}

/**
 * GARRY-001: najde všechny soubory aktivních GARRY pluginů, které
 * definují `class Garry_Promotion_Registry`, a přečte jejich deklarovanou
 * FRAMEWORK_VERSION prostým textovým regexem (nikdy se nenačítá jako kód).
 * Vrací folder => verze|null.
 */
function garry_security_scan_framework_copies() {
	$found = array();

	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		$folder     = garry_security_plugin_folder( $plugin_file );
		$candidates = array(
			WP_PLUGIN_DIR . '/' . $plugin_file,
			WP_PLUGIN_DIR . '/' . $folder . '/includes/garry-framework.php',
		);

		foreach ( $candidates as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$contents = file_get_contents( $path, false, null, 0, 200000 );
			if ( false === $contents || false === strpos( $contents, 'class Garry_Promotion_Registry' ) ) {
				continue;
			}
			$version = null;
			if ( preg_match( "/FRAMEWORK_VERSION\s*=\s*'([^']+)'/", $contents, $m ) ) {
				$version = $m[1];
			}
			$found[ $folder ] = $version;
			break;
		}
	}

	return $found;
}

/** GARRY-002: aktivní GARRY pluginy bez deklarovaného Update URI. */
function garry_security_scan_missing_update_uri() {
	$missing = array();
	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		if ( empty( $data['UpdateURI'] ) ) {
			$missing[] = ! empty( $data['Name'] ) ? $data['Name'] : $plugin_file;
		}
	}
	return $missing;
}

/** GARRY-003: aktivní GARRY pluginy bez lokálního garry-plugin-manifest.json. */
function garry_security_scan_missing_manifest() {
	$missing = array();
	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		$folder        = garry_security_plugin_folder( $plugin_file );
		$manifest_path = WP_PLUGIN_DIR . '/' . $folder . '/garry-plugin-manifest.json';
		if ( ! is_readable( $manifest_path ) ) {
			$missing[] = ! empty( $data['Name'] ) ? $data['Name'] : $plugin_file;
		}
	}
	return $missing;
}

function garry_security_collect_ecosystem() {
	$results = array();

	$framework_copies = garry_security_scan_framework_copies();
	$copy_count        = count( $framework_copies );
	$distinct_versions = array_unique( array_filter( array_values( $framework_copies ) ) );

	if ( $copy_count <= 1 ) {
		$results['GARRY-001'] = array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Sdílený GARRY framework je na tomto webu jen v jedné kopii – žádné riziko kolize podle pořadí načtení.',
			'evidence' => array( 'copies' => $framework_copies ),
		);
	} elseif ( count( $distinct_versions ) > 1 ) {
		/**
		 * `warning`, ne `critical` – riziko PHP fatal chyby je otázka
		 * dostupnosti/stability, ne aktivní zranitelnost, únik dat ani
		 * kompromitovaný účet (kritéria pro kritický stav, zpětná vazba po
		 * nasazení). Framework 2.3/2.4 (namespace izolace) tohle riziko
		 * navíc architektonicky eliminuje u všech migrovaných pluginů.
		 */
		$results['GARRY-001'] = array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf(
				'Nalezeno %1$d kopií sdíleného frameworku s různými verzemi (%2$s) u aktivních pluginů – hrozí PHP fatal chyba podle toho, která kopie se načte první.',
				$copy_count,
				implode( ', ', $distinct_versions )
			),
			'evidence' => array( 'copies' => $framework_copies ),
		);
	} else {
		$results['GARRY-001'] = array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf(
				'Nalezeno %d kopií sdíleného frameworku se stejnou verzí – dnes funkční, ale architektonicky by vlastníkem měl být jen GARRY Default (viz ekosystémový standard, sekce 2.1).',
				$copy_count
			),
			'evidence' => array( 'copies' => $framework_copies ),
		);
	}

	$missing_update_uri = garry_security_scan_missing_update_uri();
	$results['GARRY-002'] = empty( $missing_update_uri )
		? array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Všechny aktivní GARRY pluginy mají deklarovaný Update URI.',
			'evidence' => array(),
		)
		: array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d aktivních GARRY pluginů nemá deklarovaný Update URI: %2$s.', count( $missing_update_uri ), implode( ', ', $missing_update_uri ) ),
			'evidence' => array( 'plugins' => $missing_update_uri ),
		);

	$missing_manifest = garry_security_scan_missing_manifest();
	$results['GARRY-003'] = empty( $missing_manifest )
		? array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Všechny aktivní GARRY pluginy mají lokální garry-plugin-manifest.json.',
			'evidence' => array(),
		)
		: array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d aktivních GARRY pluginů nemá garry-plugin-manifest.json: %2$s.', count( $missing_manifest ), implode( ', ', $missing_manifest ) ),
			'evidence' => array( 'plugins' => $missing_manifest ),
		);

	/**
	 * GARRY-013: aktivní GARRY pluginy zastaralé vůči ručně vedenému katalogu
	 * nejnovějších verzí (security-version-catalog.php) – jediný dostupný
	 * zdroj pravdy, protože GARRY pluginy nemají skutečný update server a
	 * WordPress nativní `get_plugin_updates()` je proto pro celou rodinu
	 * vždy prázdné (viz komentář v katalogu).
	 */
	$outdated = array();
	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		$folder = garry_security_plugin_folder( $plugin_file );
		$status = garry_security_version_status( $folder, isset( $data['Version'] ) ? $data['Version'] : '' );
		if ( $status['is_outdated'] ) {
			$outdated[ $folder ] = array(
				'name'      => ! empty( $data['Name'] ) ? $data['Name'] : $folder,
				'installed' => $data['Version'],
				'latest'    => $status['latest'],
			);
		}
	}
	$results['GARRY-013'] = empty( $outdated )
		? array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Všechny aktivní GARRY pluginy odpovídají nejnovější verzi podle katalogu GARRY Security.',
			'evidence' => array(),
		)
		: array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf(
				'%1$d aktivních GARRY pluginů je zastaralých: %2$s.',
				count( $outdated ),
				implode( ', ', array_map( function ( $o ) { return $o['name'] . ' (' . $o['installed'] . ' → ' . $o['latest'] . ')'; }, $outdated ) )
			),
			'evidence' => array( 'outdated' => $outdated ),
		);

	return $results;
}

/**
 * Souhrnný panel + tabulka modulů pro "GARRY ekosystém" v Technických
 * detailech (návrh struktury, sekce "GARRY ekosystém"). Zdrojem je výhradně
 * lokální garry-plugin-manifest.json každého aktivního GARRY pluginu –
 * stejná data, jen jinak agregovaná, jako GARRY-001/002/003/012 výše.
 * "Poslední aktivita" je čestně jen čas poslední změny souboru manifestu,
 * ne runtime telemetrie, kterou GARRY nemá (viz caption pod tabulkou v
 * administraci).
 */
function garry_security_collect_ecosystem_modules() {
	$own_manifest   = garry_security_read_plugin_manifest( 'garry-default' );
	$own_protocol   = isset( $own_manifest['framework_protocol'] ) ? $own_manifest['framework_protocol'] : null;

	if ( ! function_exists( 'get_plugin_updates' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}
	$pending_update_files = array_keys( get_plugin_updates() );

	$modules = array();
	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		$folder   = garry_security_plugin_folder( $plugin_file );
		$manifest = garry_security_read_plugin_manifest( $folder );

		$manifest_path = WP_PLUGIN_DIR . '/' . $folder . '/garry-plugin-manifest.json';
		$last_change   = is_readable( $manifest_path ) ? filemtime( $manifest_path ) : false;

		$protocol = $manifest['framework_protocol'] ?? null;
		if ( null === $manifest ) {
			$compat_state = 'unknown';
		} elseif ( null === $own_protocol || null === $protocol ) {
			$compat_state = 'unknown';
		} elseif ( $protocol === $own_protocol ) {
			$compat_state = 'compatible';
		} else {
			$compat_state = 'incompatible';
		}

		$admin_slug = $manifest['admin']['local_menu_slug'] ?? null;

		$version_status = garry_security_version_status( $folder, isset( $data['Version'] ) ? $data['Version'] : '' );

		$modules[ $folder ] = array(
			'name'           => ! empty( $data['Name'] ) ? $data['Name'] : $folder,
			'version'        => isset( $data['Version'] ) ? $data['Version'] : '',
			'latest_known'   => $version_status['latest'],
			'is_outdated'    => $version_status['is_outdated'],
			'framework'      => $protocol ?: '—',
			'framework_min'  => $manifest['framework_minimum'] ?? '—',
			'compat_state'   => $compat_state,
			'pending_update' => in_array( $plugin_file, $pending_update_files, true ),
			'last_change'    => $last_change ?: null,
			'admin_url'      => $admin_slug ? admin_url( 'admin.php?page=' . rawurlencode( $admin_slug ) ) : '',
		);
	}

	$compatible_count = 0;
	$manifest_ok_count = 0;
	$pending_count     = 0;
	$outdated_count    = 0;
	foreach ( $modules as $module ) {
		if ( 'compatible' === $module['compat_state'] ) {
			$compatible_count++;
		}
		if ( '—' !== $module['framework'] ) {
			$manifest_ok_count++;
		}
		if ( $module['pending_update'] ) {
			$pending_count++;
		}
		if ( $module['is_outdated'] ) {
			$outdated_count++;
		}
	}

	return array(
		'modules' => $modules,
		'summary' => array(
			'active_count'      => count( $modules ),
			'compatible_count'  => $compatible_count,
			'manifest_ok_count' => $manifest_ok_count,
			'pending_updates'   => $pending_count,
			'outdated_count'    => $outdated_count,
		),
	);
}

function garry_security_ecosystem_catalog_entries() {
	return array(
		'GARRY-001' => array( 'title' => 'Kolize sdíleného GARRY frameworku (duplicitní registry)', 'category' => 'Ekosystém GARRY' ),
		'GARRY-002' => array( 'title' => 'Update URI u aktivních GARRY pluginů', 'category' => 'Ekosystém GARRY' ),
		'GARRY-003' => array( 'title' => 'Lokální manifest u aktivních GARRY pluginů', 'category' => 'Ekosystém GARRY' ),
		'GARRY-013' => array( 'title' => 'Nasazená verze odpovídá katalogu nejnovějších verzí GARRY', 'category' => 'Ekosystém GARRY' ),
	);
}
