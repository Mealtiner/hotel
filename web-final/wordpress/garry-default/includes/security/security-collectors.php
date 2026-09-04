<?php
/**
 * GARRY Security – read-only collectors (Fáze 1: audit foundation).
 *
 * Každá collector funkce vrací pole `control_id => array( state, severity,
 * message, evidence )`. Žádný collector nic nemění – jde výhradně o audit
 * (viz GARRY-WP-SECURITY-BEHAVIOR-SPEC.md, zásada „Audit před změnou").
 * Evidence smí obsahovat jen neutrální strukturovaná data, nikdy hesla,
 * tokeny nebo jiné citlivé údaje.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bezpečná deserializace usermeta capabilities – žádné objekty, jen pole.
 */
function garry_security_safe_unserialize( $value ) {
	if ( ! is_string( $value ) || '' === $value ) {
		return array();
	}
	$data = @unserialize( $value, array( 'allowed_classes' => false ) );
	return is_array( $data ) ? $data : array();
}

/**
 * ENV-001, ENV-003, ENV-004, ENV-005 – prostředí, PHP a DB.
 */
function garry_security_collect_environment() {
	global $wpdb;
	$results = array();

	$results['ENV-001'] = array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Detekované prostředí: %s.', garry_security_detect_environment_label() ),
		'evidence' => array(
			'environment_type' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'unknown',
		),
	);

	$results['ENV-003'] = array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => is_multisite() ? 'Web běží jako Multisite.' : 'Web běží jako Single Site.',
		'evidence' => array( 'multisite' => is_multisite() ),
	);

	$php_ok = version_compare( PHP_VERSION, '7.4', '>=' );
	$results['ENV-004'] = array(
		'state'    => $php_ok ? 'pass' : 'warning',
		'severity' => $php_ok ? 'info' : 'high',
		'message'  => sprintf( 'PHP verze %s.', PHP_VERSION ),
		'evidence' => array( 'php_version' => PHP_VERSION ),
	);

	$results['ENV-005'] = array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Databáze: verze %s, charset %s.', $wpdb->db_version(), $wpdb->charset ),
		'evidence' => array( 'db_version' => $wpdb->db_version(), 'charset' => $wpdb->charset ),
	);

	$results['ENV-006'] = garry_security_check_php_limits();
	$results['ENV-007'] = garry_security_check_htaccess_content();
	$results['ENV-008'] = garry_security_check_dns_records();
	$results['ENV-009'] = garry_security_check_email_trust_records();

	return $results;
}

/**
 * CORE-001, CORE-005, CORE-006, CORE-007, UPD-001, UPD-002 – jádro a aktualizace.
 */
function garry_security_collect_updates() {
	if ( ! function_exists( 'get_core_updates' ) ) {
		require_once ABSPATH . 'wp-admin/includes/update.php';
	}

	$results = array();

	$core_needs_update = false;
	$core_updates       = get_core_updates();
	if ( is_array( $core_updates ) ) {
		foreach ( $core_updates as $update ) {
			if ( isset( $update->response ) && 'upgrade' === $update->response ) {
				$core_needs_update = true;
				break;
			}
		}
	}
	$results['CORE-001'] = array(
		'state'    => $core_needs_update ? 'warning' : 'pass',
		'severity' => $core_needs_update ? 'high' : 'info',
		'message'  => $core_needs_update ? 'Je dostupná aktualizace jádra WordPress.' : 'WordPress jádro je aktuální.',
		'evidence' => array( 'current_version' => get_bloginfo( 'version' ) ),
	);

	$plugin_updates = get_plugin_updates();
	$plugin_count   = is_array( $plugin_updates ) ? count( $plugin_updates ) : 0;
	$plugin_names   = array();
	if ( $plugin_count > 0 ) {
		foreach ( $plugin_updates as $data ) {
			$plugin_names[] = ! empty( $data->Name ) ? $data->Name : ( ! empty( $data->update->slug ) ? $data->update->slug : '?' );
		}
	}
	/**
	 * GARRY nemá spolehlivý zdroj, KTERÉ konkrétní aktualizace opravují
	 * bezpečnostní chybu (to by vyžadovalo placenou vulnerability feed jako
	 * Patchstack/WPScan) – proto se v evidence i zprávě neoznačuje jednotlivá
	 * položka jako "bezpečnostní", jen se čestně jmenují a doporučí Patchstack
	 * pro tuhle konkrétní schopnost (viz vulnerability_intelligence v
	 * kapacitní matici).
	 */
	$results['UPD-001'] = array(
		'state'    => $plugin_count > 0 ? 'warning' : 'pass',
		'severity' => $plugin_count > 0 ? 'high' : 'info',
		'message'  => $plugin_count > 0
			? sprintf(
				'Čeká %1$d aktualizací pluginů: %2$s. GARRY nemá zdroj, které z nich řeší bezpečnostní chybu (viz Patchstack v Pokrytí bezpečnostních schopností) – doporučeno aktualizovat všechny.',
				$plugin_count,
				implode( ', ', array_slice( $plugin_names, 0, 8 ) ) . ( $plugin_count > 8 ? '…' : '' )
			)
			: 'Všechny pluginy jsou aktuální.',
		'evidence' => array( 'count' => $plugin_count, 'plugins' => $plugin_names ),
	);

	$theme_updates = get_theme_updates();
	$theme_count   = is_array( $theme_updates ) ? count( $theme_updates ) : 0;
	$results['UPD-002'] = array(
		'state'    => $theme_count > 0 ? 'warning' : 'pass',
		'severity' => $theme_count > 0 ? 'high' : 'info',
		'message'  => $theme_count > 0 ? sprintf( 'Čeká %d aktualizací šablon.', $theme_count ) : 'Všechny šablony jsou aktuální.',
		'evidence' => array( 'count' => $theme_count ),
	);

	$debug_on = defined( 'WP_DEBUG' ) && WP_DEBUG;
	$results['CORE-005'] = array(
		'state'    => $debug_on ? 'warning' : 'pass',
		'severity' => $debug_on ? 'medium' : 'info',
		'message'  => $debug_on ? 'WP_DEBUG je na tomto webu zapnuté.' : 'WP_DEBUG je vypnuté.',
		'evidence' => array( 'wp_debug' => $debug_on ),
	);

	$display_errors = filter_var( ini_get( 'display_errors' ), FILTER_VALIDATE_BOOLEAN );
	$results['CORE-006'] = array(
		'state'    => $display_errors ? 'warning' : 'pass',
		'severity' => $display_errors ? 'high' : 'info',
		'message'  => $display_errors ? 'PHP display_errors je zapnuté – chybové výpisy se mohou zobrazovat veřejně.' : 'PHP display_errors je vypnuté.',
		'evidence' => array( 'display_errors' => $display_errors ),
	);

	$file_edit_disabled = defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT;
	$results['CORE-007'] = array(
		'state'    => $file_edit_disabled ? 'pass' : 'warning',
		'severity' => $file_edit_disabled ? 'info' : 'medium',
		'message'  => $file_edit_disabled ? 'Vestavěný editor pluginů a šablon je vypnutý.' : 'Vestavěný editor pluginů a šablon je aktivní (doporučeno zapnout DISALLOW_FILE_EDIT).',
		'evidence' => array( 'disallow_file_edit' => $file_edit_disabled ),
	);

	$results['CORE-008'] = garry_security_check_core_auto_update_policy();

	return $results;
}

/**
 * CORE-008 (Fáze D, bod 4 – "core update adapter"): jen sleduje deklarovanou
 * politiku automatických aktualizací jádra, nenahrazuje WP upgrader a
 * nenutí auto-updates. Explicitní vypnutí i bezpečnostních/minor aktualizací
 * (WP_AUTO_UPDATE_CORE === false) je jediný stav, který hlásí jako warning –
 * WordPress ho ve výchozím stavu nechává zapnutý přesně kvůli bezpečnostním
 * opravám.
 */
function garry_security_check_core_auto_update_policy() {
	if ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'Automatické aktualizace jádra (i bezpečnostní) jsou explicitně vypnuté konstantou WP_AUTO_UPDATE_CORE.',
			'evidence' => array( 'wp_auto_update_core' => false ),
		);
	}

	$policy = defined( 'WP_AUTO_UPDATE_CORE' ) ? WP_AUTO_UPDATE_CORE : 'minor (výchozí chování WordPressu)';

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Politika automatických aktualizací jádra: %s.', is_bool( $policy ) ? ( $policy ? 'všechny' : 'žádné' ) : (string) $policy ),
		'evidence' => array( 'wp_auto_update_core' => $policy ),
	);
}

/**
 * USR-001..004 – vícezdrojová detekce administrátorů (WP API vs. přímá DB
 * kontrola vs. runtime capabilities), viz GARRY-WP-SECURITY-MASTER-SPEC.md
 * sekce 9. Přímý DB dotaz záměrně obchází WP_User_Query, protože tu lze
 * ovlivnit hookem `pre_user_query`.
 */
function garry_security_collect_users() {
	global $wpdb;
	$results = array();

	// USR-001: administrátoři přes standardní WP API.
	$api_ids = array_map( 'intval', get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) );
	sort( $api_ids );

	$results['USR-001'] = array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Administrátorů přes WP API: %d.', count( $api_ids ) ),
		'evidence' => array( 'count' => count( $api_ids ) ),
	);

	// USR-002/003: přímý DB dotaz na users + usermeta capabilities.
	$cap_key = $wpdb->prefix . 'capabilities';
	$rows    = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT u.ID, um.meta_value FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s",
			$cap_key
		)
	);

	$db_admin_ids   = array();
	$db_user_caps   = array();
	foreach ( $rows as $row ) {
		$caps = garry_security_safe_unserialize( $row->meta_value );
		$db_user_caps[ (int) $row->ID ] = $caps;
		if ( ! empty( $caps['administrator'] ) ) {
			$db_admin_ids[] = (int) $row->ID;
		}
	}
	sort( $db_admin_ids );

	$results['USR-002'] = array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Administrátorů přímo z DB: %d.', count( $db_admin_ids ) ),
		'evidence' => array( 'count' => count( $db_admin_ids ) ),
	);

	$missing_in_api = array_values( array_diff( $db_admin_ids, $api_ids ) );
	$missing_in_db  = array_values( array_diff( $api_ids, $db_admin_ids ) );

	if ( empty( $missing_in_api ) && empty( $missing_in_db ) ) {
		$results['USR-003'] = array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Seznam administrátorů z WP API a přímé DB kontroly se shoduje.',
			'evidence' => array( 'api_count' => count( $api_ids ), 'db_count' => count( $db_admin_ids ) ),
		);
	} else {
		$results['USR-003'] = array(
			'state'    => 'critical',
			'severity' => 'critical',
			'message'  => 'Rozdíl mezi WP API a přímou DB kontrolou administrátorů – možné skrytí účtu přes hook pre_user_query.',
			'evidence' => array(
				'missing_in_api' => $missing_in_api,
				'missing_in_db'  => $missing_in_db,
			),
		);
	}

	// USR-004: runtime kritické capabilities bez odpovídající DB role.
	$critical_caps = array(
		'manage_options', 'activate_plugins', 'install_plugins', 'update_plugins',
		'edit_users', 'create_users', 'promote_users', 'delete_users', 'unfiltered_html',
	);

	$flagged = array();
	foreach ( $db_user_caps as $uid => $caps ) {
		if ( ! empty( $caps['administrator'] ) ) {
			continue; // administrátor v DB - runtime capability zde není překvapivá.
		}
		foreach ( $critical_caps as $cap ) {
			if ( user_can( $uid, $cap ) ) {
				$flagged[ $uid ][] = $cap;
			}
		}
	}

	if ( empty( $flagged ) ) {
		$results['USR-004'] = array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Žádný neadministrátorský účet nemá runtime kritické capability.',
			'evidence' => array(),
		);
	} else {
		$results['USR-004'] = array(
			'state'    => 'critical',
			'severity' => 'critical',
			'message'  => sprintf( 'Nalezeno %d účtů s kritickou runtime capability bez odpovídající DB role administrátora.', count( $flagged ) ),
			'evidence' => array( 'flagged' => $flagged ),
		);
	}

	return $results;
}

/**
 * WEB-001, WEB-002 – HTTPS na frontendu a v administraci.
 */
function garry_security_collect_web() {
	$results = array();

	$home           = home_url();
	$is_https_home  = ( 0 === strpos( $home, 'https://' ) );
	$force_ssl_admin = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN;
	$is_https_admin  = is_ssl() || $force_ssl_admin;

	$results['WEB-001'] = array(
		'state'    => $is_https_home ? 'pass' : 'critical',
		'severity' => $is_https_home ? 'info' : 'high',
		'message'  => $is_https_home ? 'Frontend používá HTTPS.' : 'Frontend neběží na HTTPS.',
		'evidence' => array( 'home_url' => $home ),
	);

	$results['WEB-002'] = array(
		'state'    => $is_https_admin ? 'pass' : 'warning',
		'severity' => $is_https_admin ? 'info' : 'high',
		'message'  => $is_https_admin ? 'Administrace používá/vynucuje HTTPS.' : 'Administrace nemusí běžet na HTTPS (FORCE_SSL_ADMIN není zapnuté).',
		'evidence' => array( 'force_ssl_admin' => $force_ssl_admin ),
	);

	return $results;
}

/**
 * SCAN-CRON – kontrola, že je denní scan skutečně naplánovaný ve WP-Cron.
 */
function garry_security_collect_scheduler() {
	$results   = array();
	$timestamp = wp_next_scheduled( 'garry_security_daily_scan' );

	if ( ! $timestamp ) {
		/**
		 * Záměrně jen `warning`, ne `critical` – nejde o díru na webu, ale
		 * o spolehlivost samotných kontrol (pokud sken neběží, GARRY jen
		 * přestane sledovat aktuální stav, nezpůsobí to samo o sobě žádné
		 * riziko). Kritické zůstává jen pro skutečnou zranitelnost, malware,
		 * veřejně dostupná citlivá data, vypnutý firewall nebo kompromitovaný
		 * účet (zpětná vazba po nasazení).
		 */
		$results['SCAN-CRON'] = array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'Denní bezpečnostní scan není naplánovaný ve WP-Cron – kontroly se nemusí pravidelně aktualizovat.',
			'evidence' => array(),
		);
	} else {
		$overdue = ( $timestamp < time() - 2 * DAY_IN_SECONDS );
		$results['SCAN-CRON'] = array(
			'state'    => $overdue ? 'warning' : 'pass',
			'severity' => $overdue ? 'medium' : 'info',
			'message'  => $overdue ? 'Naplánovaný scan je opožděný (overdue).' : 'Denní scan je naplánovaný.',
			'evidence' => array( 'next_scheduled' => $timestamp ),
		);
	}

	return $results;
}

/**
 * Spustí všechny collectory (read-only audit + provider discovery a
 * ownership/conflict engine + kompatibilita a hardening ekosystému GARRY
 * pluginů) a vrátí sloučený výsledek. `garry_security_collect_providers()`
 * (PROV-*) je v security-providers.php, `garry_security_collect_ownership()`
 * (OWN-*) v security-ownership.php, `garry_security_collect_ecosystem()`
 * (GARRY-001..003) v security-ecosystem.php, `garry_security_collect_hardening()`
 * (GARRY-004..012, Fáze C) v security-hardening.php – všechny musí být
 * require-nuté před tímto.
 */
function garry_security_run_all_collectors() {
	$results = array();
	$results = array_merge( $results, garry_security_collect_environment() );
	$results = array_merge( $results, garry_security_collect_updates() );
	$results = array_merge( $results, garry_security_collect_users() );
	$results = array_merge( $results, garry_security_collect_web() );
	$results = array_merge( $results, garry_security_collect_scheduler() );
	$results = array_merge( $results, garry_security_collect_providers() );
	$results = array_merge( $results, garry_security_collect_ownership() );
	$results = array_merge( $results, garry_security_collect_ecosystem() );
	$results = array_merge( $results, garry_security_collect_hardening() );
	$results = array_merge( $results, garry_security_collect_http_health() );
	$results = array_merge( $results, garry_security_collect_access_hardening() );
	$results = array_merge( $results, garry_security_collect_exposure() );
	$results = array_merge( $results, garry_security_collect_exposed_files() );
	$results = array_merge( $results, garry_security_collect_plugin_hygiene() );
	$results['PROV-COMPLIANCE-PLUGIN'] = garry_security_check_compliance_plugin();
	return $results;
}
