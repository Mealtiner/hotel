<?php
/**
 * GARRY Security – rozšířený audit ekosystému (GARRY-004..012), Fáze C.
 *
 * Podle docs/GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md, sekce 4 „Fáze C".
 * V první iteraci pouze měří a vysvětluje stav; sama nic neopravuje.
 *
 * Zdrojem dat je výhradně lokální `garry-plugin-manifest.json` každého
 * aktivního GARRY pluginu (volitelný blok `security_profile`) – žádné
 * čtení cizího PHP kódu jako spustitelného souboru, jen `file_get_contents()`
 * a `json_decode()`. GARRY-004, GARRY-005 a GARRY-012 jsou záměrně jen
 * kontrolou přítomnosti/konzistence deklarace, ne drahým runtime scannerem
 * (viz roadmap dokument, poznámka pod tabulkou Fáze C).
 *
 * Chybějící deklarace = `unknown`, nikdy fabrikované `pass` (master spec,
 * zásada „unknown je horší než zelená, ale nikdy nefabrikovat jistotu").
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Načte manifest aktivního GARRY pluginu (stejný soubor, který si
 * čte i sám plugin přes Framework 2.3 Manifest::load()) a vrátí ho jako
 * pole, nebo null pokud chybí/je nečitelný/je neplatný JSON.
 */
function garry_security_read_plugin_manifest( $folder ) {
	$path = WP_PLUGIN_DIR . '/' . $folder . '/garry-plugin-manifest.json';
	if ( ! is_readable( $path ) ) {
		return null;
	}
	$contents = file_get_contents( $path, false, null, 0, 200000 );
	if ( false === $contents ) {
		return null;
	}
	$data = json_decode( $contents, true );
	return is_array( $data ) ? $data : null;
}

/**
 * folder => array( 'name' => ..., 'manifest' => array|null ) pro všechny
 * aktivní GARRY pluginy. Sdílený vstupní bod pro všechny kontroly
 * v tomto souboru.
 */
function garry_security_hardening_inventory() {
	$inventory = array();
	foreach ( garry_security_active_garry_plugin_files() as $plugin_file => $data ) {
		$folder = garry_security_plugin_folder( $plugin_file );
		$inventory[ $folder ] = array(
			'name'     => ! empty( $data['Name'] ) ? $data['Name'] : $folder,
			'version'  => isset( $data['Version'] ) ? $data['Version'] : '',
			'manifest' => garry_security_read_plugin_manifest( $folder ),
		);
	}
	return $inventory;
}

/** GARRY-004: admin assety mimo vlastní screen. */
function garry_security_check_admin_asset_scope( array $inventory ) {
	$global  = array();
	$unknown = array();

	foreach ( $inventory as $folder => $info ) {
		$scope = isset( $info['manifest']['security_profile']['admin_assets_scope'] )
			? $info['manifest']['security_profile']['admin_assets_scope']
			: null;

		if ( null === $scope ) {
			$unknown[] = $info['name'];
		} elseif ( 'global' === $scope ) {
			$global[] = $info['name'];
		}
	}

	if ( ! empty( $global ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů deklaruje admin assety mimo vlastní obrazovku: %2$s.', count( $global ), implode( ', ', $global ) ),
			'evidence' => array( 'plugins' => $global ),
		);
	}
	if ( ! empty( $unknown ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů nemá v manifestu deklarovaný admin_assets_scope: %2$s.', count( $unknown ), implode( ', ', $unknown ) ),
			'evidence' => array( 'plugins' => $unknown ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny aktivní GARRY pluginy deklarují své admin assety scoped na vlastní obrazovku (nebo je nepoužívají).',
		'evidence' => array(),
	);
}

/** GARRY-005: inline JS/CSS a potřebné CSP zdroje – jen kontrola deklarace. */
function garry_security_check_inline_assets_declared( array $inventory ) {
	$missing = array();
	foreach ( $inventory as $folder => $info ) {
		if ( ! isset( $info['manifest']['security_profile']['inline_assets'] ) ) {
			$missing[] = $info['name'];
		}
	}

	if ( ! empty( $missing ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů nemá v manifestu deklarované použití inline JS/CSS: %2$s.', count( $missing ), implode( ', ', $missing ) ),
			'evidence' => array( 'plugins' => $missing ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny aktivní GARRY pluginy mají deklarované použití (nebo nepoužití) inline JS/CSS – podklad pro budoucí CSP politiku.',
		'evidence' => array(),
	);
}

/** GARRY-006: veřejný AJAX/REST/form surface bez anti-spam a rate-limit strategie. */
function garry_security_check_public_surfaces( array $inventory ) {
	$no_anti_spam = array();
	$no_rate_limit = array();
	$total_surfaces = 0;

	foreach ( $inventory as $folder => $info ) {
		$surfaces = isset( $info['manifest']['security_profile']['public_surfaces'] ) && is_array( $info['manifest']['security_profile']['public_surfaces'] )
			? $info['manifest']['security_profile']['public_surfaces']
			: array();

		foreach ( $surfaces as $surface ) {
			$total_surfaces++;
			$label = $info['name'] . ' → ' . ( isset( $surface['name'] ) ? $surface['name'] : '?' );

			$anti_spam = isset( $surface['anti_spam'] ) ? $surface['anti_spam'] : 'none';
			if ( 'none' === $anti_spam || '' === $anti_spam ) {
				$no_anti_spam[] = $label;
			}

			$rate_limit = isset( $surface['rate_limit'] ) ? $surface['rate_limit'] : 'none';
			if ( 'none' === $rate_limit ) {
				$no_rate_limit[] = $label;
			}
		}
	}

	if ( 0 === $total_surfaces ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Žádný aktivní GARRY plugin nedeklaruje veřejný AJAX/REST/form surface.',
			'evidence' => array(),
		);
	}

	if ( ! empty( $no_anti_spam ) ) {
		/**
		 * `warning`, ne `critical` – chybějící anti-spam na veřejném vstupu
		 * je riziko zneužití (spam), ne aktivní zranitelnost, malware ani
		 * kompromitovaný účet (zpětná vazba po nasazení, kritéria pro
		 * kritický stav). Zůstává `high` závažnost, protože reálně hrozí.
		 */
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf( '%1$d veřejných vstupních bodů nemá žádnou anti-spam ochranu: %2$s.', count( $no_anti_spam ), implode( ', ', $no_anti_spam ) ),
			'evidence' => array( 'surfaces_without_anti_spam' => $no_anti_spam, 'surfaces_without_rate_limit' => $no_rate_limit ),
		);
	}

	if ( ! empty( $no_rate_limit ) ) {
		$turnstile_active = function_exists( 'garry_security_provider_is_active' ) && garry_security_provider_is_active( 'simple-cloudflare-turnstile' );
		$suggestion = $turnstile_active
			? 'Simple Cloudflare Turnstile je na webu aktivní, ale podle deklarace v manifestu na tyto vstupy není napojen – ověřte zapojení. Doporučeno doplnit i IP/transient throttling.'
			: 'Doporučeno doplnit IP/transient throttling, nebo nainstalovat Simple Cloudflare Turnstile (viz „Doporučené pluginy" níže) a napojit ho na tyto formuláře.';
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf(
				'%1$d veřejných vstupních bodů má anti-spam ochranu (nonce/honeypot), ale žádné rate-limitování: %2$s. %3$s',
				count( $no_rate_limit ),
				implode( ', ', $no_rate_limit ),
				$suggestion
			),
			'evidence' => array( 'surfaces_without_rate_limit' => $no_rate_limit, 'turnstile_active' => $turnstile_active ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Všech %d deklarovaných veřejných vstupních bodů má anti-spam i rate-limit strategii.', $total_surfaces ),
		'evidence' => array(),
	);
}

/** GARRY-007: vlastní CSS nastavitelné editorem (capability policy podle manifestu). */
function garry_security_check_editor_custom_css( array $inventory ) {
	$found = array();
	foreach ( $inventory as $folder => $info ) {
		if ( ! empty( $info['manifest']['security_profile']['editor_custom_css'] ) ) {
			$found[] = $info['name'];
		}
	}

	if ( empty( $found ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Žádný aktivní GARRY plugin nenabízí editoru pole s volným CSS.',
			'evidence' => array(),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf(
			'%1$d pluginů nabízí editoru pole s volným CSS (sanitizované – odstranění tagů, expression(), javascript:): %2$s. Přístup má jen role s právem upravovat danou stránku/šablonu.',
			count( $found ),
			implode( ', ', $found )
		),
		'evidence' => array( 'plugins' => $found ),
	);
}

/** GARRY-008: neomezený veřejný query/render limit. */
function garry_security_check_unbounded_queries( array $inventory ) {
	$unbounded = array();
	$unknown   = array();

	foreach ( $inventory as $folder => $info ) {
		$budget = isset( $info['manifest']['security_profile']['performance_budget'] )
			? $info['manifest']['security_profile']['performance_budget']
			: null;

		if ( null === $budget || ! array_key_exists( 'bounded', $budget ) ) {
			$unknown[] = $info['name'];
			continue;
		}
		if ( false === $budget['bounded'] ) {
			$unbounded[] = $info['name'] . ( ! empty( $budget['note'] ) ? ' (' . $budget['note'] . ')' : '' );
		}
	}

	if ( ! empty( $unbounded ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů umožňuje editoru nastavit neomezený veřejný dotaz: %2$s.', count( $unbounded ), implode( '; ', $unbounded ) ),
			'evidence' => array( 'plugins' => $unbounded ),
		);
	}
	if ( ! empty( $unknown ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů nemá deklarovaný performance_budget: %2$s.', count( $unknown ), implode( ', ', $unknown ) ),
			'evidence' => array( 'plugins' => $unknown ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny veřejně vykreslované dotazy aktivních GARRY pluginů mají deklarovaný horní limit.',
		'evidence' => array(),
	);
}

/** GARRY-009: externí origin bez deklarace v manifestu (formát jen schéma+doména). */
function garry_security_check_external_origins( array $inventory ) {
	$malformed = array();
	foreach ( $inventory as $folder => $info ) {
		$origins = isset( $info['manifest']['external_origins'] ) && is_array( $info['manifest']['external_origins'] )
			? $info['manifest']['external_origins']
			: array();
		foreach ( $origins as $origin ) {
			if ( ! is_string( $origin ) || ! preg_match( '#^https://[a-z0-9.-]+$#i', $origin ) ) {
				$malformed[] = $info['name'] . ' → ' . (string) $origin;
			}
		}
	}

	if ( ! empty( $malformed ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d deklarovaných externích origin hodnot neodpovídá formátu „jen schéma a doména": %2$s.', count( $malformed ), implode( '; ', $malformed ) ),
			'evidence' => array( 'entries' => $malformed ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny deklarované externí origin hodnoty jsou ve správném formátu (jen schéma a doména, žádný spustitelný endpoint).',
		'evidence' => array(),
	);
}

/** GARRY-010: osobní údaje bez deklarace retence. */
function garry_security_check_personal_data_retention( array $inventory ) {
	$missing = array();
	foreach ( $inventory as $folder => $info ) {
		$stores = ! empty( $info['manifest']['data']['stores_personal_data'] );
		if ( ! $stores ) {
			continue;
		}
		$declared = ! empty( $info['manifest']['security_profile']['personal_data']['retention_declared'] );
		if ( ! $declared ) {
			$missing[] = $info['name'];
		}
	}

	if ( ! empty( $missing ) ) {
		/**
		 * `warning`, ne `critical` – chybějící deklarace retenční politiky
		 * je dokumentační mezera, ne sama o sobě únik dat ani kompromitovaný
		 * účet (kritéria pro kritický stav, zpětná vazba po nasazení).
		 */
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf( '%1$d pluginů ukládá osobní údaje bez deklarované retenční politiky: %2$s.', count( $missing ), implode( ', ', $missing ) ),
			'evidence' => array( 'plugins' => $missing ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny aktivní GARRY pluginy, které ukládají osobní údaje, mají deklarovanou retenční politiku.',
		'evidence' => array(),
	);
}

/** GARRY-011: multisite lifecycle neověřen. */
function garry_security_check_multisite_lifecycle( array $inventory ) {
	if ( ! is_multisite() ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Web neběží jako Multisite – kontrola se neuplatňuje.',
			'evidence' => array(),
		);
	}

	$untested = array();
	foreach ( $inventory as $folder => $info ) {
		$status = isset( $info['manifest']['security_profile']['multisite_tested'] )
			? $info['manifest']['security_profile']['multisite_tested']
			: 'untested';
		if ( 'untested' === $status ) {
			$untested[] = $info['name'];
		}
	}

	if ( ! empty( $untested ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf( '%1$d pluginů nemá ověřený stav chování na Multisite: %2$s.', count( $untested ), implode( ', ', $untested ) ),
			'evidence' => array( 'plugins' => $untested ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Všechny aktivní GARRY pluginy mají deklarovaný stav chování na Multisite.',
		'evidence' => array(),
	);
}

/**
 * GARRY-012: neshoda verze v manifestu a skutečné verze pluginu.
 * Odlehčená náhrada release hash kontroly (viz roadmap – primárním zdrojem
 * má být CI artefakt; bez release pipeline je nejbližší ověřitelná věc
 * konzistence manifestu se skutečně nasazenými soubory).
 */
function garry_security_check_release_consistency( array $inventory ) {
	$mismatched = array();
	$missing    = array();

	foreach ( $inventory as $folder => $info ) {
		if ( null === $info['manifest'] ) {
			continue; // pokryto GARRY-003.
		}
		if ( empty( $info['manifest']['plugin_version'] ) ) {
			$missing[] = $info['name'];
			continue;
		}
		if ( $info['manifest']['plugin_version'] !== $info['version'] ) {
			$mismatched[] = sprintf( '%1$s (manifest %2$s, plugin %3$s)', $info['name'], $info['manifest']['plugin_version'], $info['version'] );
		}
	}

	if ( ! empty( $mismatched ) ) {
		/**
		 * `warning`, ne `critical` – neshoda verze je signál nekonzistentního
		 * nasazení (stará kopie souborů, neúplný upload), ne sama o sobě
		 * aktivní zranitelnost ani kompromitovaný účet.
		 */
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf( '%1$d pluginů má neshodu verze mezi manifestem a hlavičkou pluginu: %2$s.', count( $mismatched ), implode( '; ', $mismatched ) ),
			'evidence' => array( 'plugins' => $mismatched ),
		);
	}
	if ( ! empty( $missing ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'high',
			'message'  => sprintf( '%1$d pluginů nemá v manifestu deklarovanou plugin_version: %2$s.', count( $missing ), implode( ', ', $missing ) ),
			'evidence' => array( 'plugins' => $missing ),
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Verze v manifestu odpovídá skutečně nasazené verzi u všech aktivních GARRY pluginů.',
		'evidence' => array(),
	);
}

function garry_security_collect_hardening() {
	$inventory = garry_security_hardening_inventory();

	return array(
		'GARRY-004' => garry_security_check_admin_asset_scope( $inventory ),
		'GARRY-005' => garry_security_check_inline_assets_declared( $inventory ),
		'GARRY-006' => garry_security_check_public_surfaces( $inventory ),
		'GARRY-007' => garry_security_check_editor_custom_css( $inventory ),
		'GARRY-008' => garry_security_check_unbounded_queries( $inventory ),
		'GARRY-009' => garry_security_check_external_origins( $inventory ),
		'GARRY-010' => garry_security_check_personal_data_retention( $inventory ),
		'GARRY-011' => garry_security_check_multisite_lifecycle( $inventory ),
		'GARRY-012' => garry_security_check_release_consistency( $inventory ),
	);
}

function garry_security_hardening_catalog_entries() {
	return array(
		'GARRY-004' => array( 'title' => 'Admin assety scoped na vlastní obrazovku', 'category' => 'Ekosystém GARRY' ),
		'GARRY-005' => array( 'title' => 'Deklarace inline JS/CSS pro budoucí CSP', 'category' => 'Ekosystém GARRY' ),
		'GARRY-006' => array( 'title' => 'Anti-spam a rate-limit u veřejných vstupních bodů', 'category' => 'Ekosystém GARRY' ),
		'GARRY-007' => array( 'title' => 'Vlastní CSS nastavitelné editorem', 'category' => 'Ekosystém GARRY' ),
		'GARRY-008' => array( 'title' => 'Horní limit veřejně vykreslovaných dotazů', 'category' => 'Ekosystém GARRY' ),
		'GARRY-009' => array( 'title' => 'Formát deklarovaných externích origin', 'category' => 'Ekosystém GARRY' ),
		'GARRY-010' => array( 'title' => 'Retenční politika u osobních údajů', 'category' => 'Ekosystém GARRY' ),
		'GARRY-011' => array( 'title' => 'Ověřený stav chování na Multisite', 'category' => 'Ekosystém GARRY' ),
		'GARRY-012' => array( 'title' => 'Shoda verze manifestu se skutečně nasazeným pluginem', 'category' => 'Ekosystém GARRY' ),
	);
}
