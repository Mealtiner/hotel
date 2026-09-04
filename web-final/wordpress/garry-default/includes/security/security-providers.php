<?php
/**
 * GARRY Security – provider registry (Fáze 2).
 *
 * Deklarativní seznam schválených externích pluginů z
 * docs/GARRY-WP-SECURITY-PLUGIN-BLUEPRINT.md, sekce 12 „Provider registry".
 * Registry obsahuje jen pevně povolené WordPress.org slugy a entry files –
 * nikdy nepřijímá libovolnou hodnotu z requestu (viz garry_security_install_plugin_from_wp_org()).
 *
 * Poznámka k `entry_file`: u méně známých pluginů z katalogu je hodnota
 * best-effort podle obvyklé konvence <slug>/<slug>.php. Pokud se skutečný
 * hlavní soubor liší, discovery jen bezpečně „selže" směrem k not_installed
 * (nikdy false-positive aktivní stav) – při prvním ostrém nasazení stojí
 * za to hodnoty ověřit proti reálně nainstalovaným pluginům.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Schválený katalog providerů. `capabilities` – jen ty, u kterých je
 * „plugin je aktivní" dostatečně silný signál vlastnictví (viz komentář
 * u garry_security_collect_ownership() k tomu, proč Wordfence nemá
 * captcha_login – jeho reCAPTCHA je ve výchozím stavu vypnutá a bez
 * ověřeného čtení jeho options bychom hlásili false-positive konflikt).
 */
function garry_security_provider_registry() {
	return array(
		'wordfence'          => array(
			'label'         => 'Wordfence Security',
			'entry_file'    => 'wordfence/wordfence.php',
			'wp_org_slug'   => 'wordfence',
			'capabilities'  => array( 'firewall', 'malware_scan', 'login_rate_limit', 'two_factor' ),
			'settings_path' => 'admin.php?page=Wordfence',
		),
		'updraftplus'        => array(
			'label'         => 'UpdraftPlus',
			'entry_file'    => 'updraftplus/updraftplus.php',
			'wp_org_slug'   => 'updraftplus',
			'capabilities'  => array( 'backup' ),
			'settings_path' => 'options-general.php?page=updraftplus',
		),
		'simple-cloudflare-turnstile' => array(
			'label'         => 'Simple Cloudflare Turnstile',
			'entry_file'    => 'simple-cloudflare-turnstile/simple-cloudflare-turnstile.php',
			'wp_org_slug'   => 'simple-cloudflare-turnstile',
			'capabilities'  => array( 'captcha_login' ),
			'settings_path' => 'options-general.php?page=simple-cloudflare-turnstile',
		),
		'two-factor'         => array(
			'label'         => 'Two-Factor',
			'entry_file'    => 'two-factor/two-factor.php',
			'wp_org_slug'   => 'two-factor',
			'capabilities'  => array( 'two_factor' ),
			'settings_path' => 'profile.php',
		),
		'fluent-smtp'        => array(
			'label'         => 'FluentSMTP',
			'entry_file'    => 'fluent-smtp/fluent-smtp.php',
			'wp_org_slug'   => 'fluent-smtp',
			'capabilities'  => array( 'smtp' ),
			'settings_path' => 'admin.php?page=fluent-mail-settings',
		),
		'wp-mail-smtp'       => array(
			'label'         => 'WP Mail SMTP',
			'entry_file'    => 'wp-mail-smtp/wp_mail_smtp.php',
			'wp_org_slug'   => 'wp-mail-smtp',
			'capabilities'  => array( 'smtp' ),
			'settings_path' => 'admin.php?page=wp-mail-smtp',
		),
		'simple-history'     => array(
			'label'         => 'Simple History',
			'entry_file'    => 'simple-history/simple-history.php',
			'wp_org_slug'   => 'simple-history',
			'capabilities'  => array( 'activity_log' ),
			'settings_path' => 'admin.php?page=simple_history_page',
		),
		'all-in-one-wp-security-and-firewall' => array(
			'label'         => 'All-In-One Security (AIOS)',
			'entry_file'    => 'all-in-one-wp-security-and-firewall/wp-security.php',
			'wp_org_slug'   => 'all-in-one-wp-security-and-firewall',
			'capabilities'  => array( 'firewall', 'malware_scan', 'login_rate_limit', 'two_factor' ),
			'settings_path' => 'admin.php?page=aiowpsec',
		),
		'patchstack'         => array(
			'label'         => 'Patchstack',
			'entry_file'    => 'patchstack/patchstack.php',
			'wp_org_slug'   => 'patchstack',
			'capabilities'  => array( 'vulnerability_intelligence' ),
			'settings_path' => 'admin.php?page=patchstack',
		),
	);
}

/** Capability, kde je konflikt dvou současně aktivních vlastníků skutečně relevantní. */
function garry_security_exclusive_capabilities() {
	return array( 'firewall', 'malware_scan', 'login_rate_limit', 'two_factor', 'captcha_login', 'backup', 'smtp' );
}

function garry_security_provider_is_installed( $slug ) {
	$registry = garry_security_provider_registry();
	if ( ! isset( $registry[ $slug ] ) ) {
		return false;
	}
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$all_plugins = get_plugins();
	return isset( $all_plugins[ $registry[ $slug ]['entry_file'] ] );
}

function garry_security_provider_is_active( $slug ) {
	$registry = garry_security_provider_registry();
	if ( ! isset( $registry[ $slug ] ) ) {
		return false;
	}
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active( $registry[ $slug ]['entry_file'] );
}

/**
 * Stavový žebříček providera. „Configured"/„Healthy" vyžadují ověřené
 * čtení interních options konkrétního pluginu – v Fázi 2 na to zatím
 * nemáme ověřený adapter, takže strop je „active" a další stav je
 * `unknown` (ne fabrikovaný „zdravý" stav bez důkazu).
 */
function garry_security_provider_ladder_state( $slug ) {
	if ( garry_security_provider_is_active( $slug ) ) {
		return 'active';
	}
	if ( garry_security_provider_is_installed( $slug ) ) {
		return 'installed';
	}
	return 'recommended';
}

/**
 * PROV-TURNSTILE-CONFIG (Fáze D, bod 3 – "Turnstile adapter"): ověří, jestli
 * je Simple Cloudflare Turnstile nejen aktivní, ale i skutečně nakonfigurovaný
 * a vykreslující se – bez hádání jeho interní struktury options (ta není
 * dokumentované veřejné API a GARRY ji nemá jak ověřit proti živé instalaci,
 * viz stejná zásada u UpdraftPlus v class-garry-security-backup-gate.php).
 *
 * Místo toho self-request na vlastní přihlašovací stránku (stejný bezpečný
 * vzor jako HTTP/Site Health adapter – cíl je vždy jen wp_login_url() na
 * tomto webu, žádné SSRF riziko) a černá skříňka: hledá se Cloudflare
 * Turnstile skript a widget se skutečně vyplněným site key v HTML výstupu.
 * To je totéž, co by viděl návštěvník – funkční důkaz, ne domněnka o obsahu
 * databáze.
 */
function garry_security_check_turnstile_configuration() {
	$slug = 'simple-cloudflare-turnstile';
	if ( ! garry_security_provider_is_active( $slug ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Simple Cloudflare Turnstile není aktivní – kontrola konfigurace se neuplatňuje.',
			'evidence' => array(),
		);
	}

	$response = wp_safe_remote_get( wp_login_url(), array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'Turnstile je aktivní, ale GARRY se nepodařilo ověřit jeho konfiguraci (chyba požadavku na přihlašovací stránku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$body = wp_remote_retrieve_body( $response );

	if ( false === strpos( $body, 'challenges.cloudflare.com/turnstile' ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'Turnstile je aktivní, ale na přihlašovací stránce nebyl nalezen žádný jeho skript – zkontrolujte, že je v jeho nastavení zapnutý pro přihlašovací formulář.',
			'evidence' => array( 'login_url' => wp_login_url() ),
		);
	}

	if ( preg_match( '/data-sitekey=["\']([^"\']*)["\']/', $body, $m ) ) {
		$site_key = trim( $m[1] );
		if ( '' !== $site_key ) {
			return array(
				'state'    => 'pass',
				'severity' => 'info',
				'message'  => 'Turnstile je aktivní a na přihlašovací stránce se vykresluje s nastaveným site key.',
				'evidence' => array( 'login_url' => wp_login_url() ),
			);
		}
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => 'Turnstile je aktivní a widget se na přihlašovací stránce vykresluje, ale site key je prázdný – dokončete konfiguraci v Nastavení → Simple Cloudflare Turnstile (site key a secret key z Cloudflare).',
			'evidence' => array( 'login_url' => wp_login_url() ),
		);
	}

	return array(
		'state'    => 'unknown',
		'severity' => 'medium',
		'message'  => 'Turnstile skript byl na přihlašovací stránce nalezen, ale GARRY z výstupu nedokázal ověřit nastavený site key – zkontrolujte konfiguraci ručně.',
		'evidence' => array( 'login_url' => wp_login_url() ),
	);
}

/**
 * PROV-WORDFENCE-WAF (verze 2.2, P0 "WAF skutečně načtený"): Wordfence
 * veřejně dokumentuje, že "rozšířená ochrana" (extended protection) běží
 * přes vlastní bootstrap soubor `wordfence-waf.php` v kořeni webu, který se
 * načte (přes auto_prepend_file) dřív, než se vůbec začne načítat WordPress
 * – bez něj běží jen základní ochrana z pluginu samotného. GARRY ověřuje
 * jen PŘÍTOMNOST tohoto souboru na disku, nikdy nehádá vnitřní stav
 * Wordfence (nedokumentované interní API) – chybějící soubor proto vrací
 * `unknown`, ne `warning`: může jít o hosting, který auto_prepend_file
 * nepovoluje, ne o chybu konfigurace.
 */
function garry_security_check_wordfence_waf_extended() {
	if ( ! garry_security_provider_is_active( 'wordfence' ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Wordfence není aktivní – kontrola rozšířené ochrany se neuplatňuje.',
			'evidence' => array(),
		);
	}

	$bootstrap_exists = file_exists( ABSPATH . 'wordfence-waf.php' );

	if ( $bootstrap_exists ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Wordfence je aktivní a nalezen jeho bootstrap soubor rozšířené ochrany (wordfence-waf.php) v kořeni webu – firewall pravděpodobně běží v rozšířeném režimu. GARRY ověřuje jen přítomnost souboru, ne vnitřní stav Wordfence.',
			'evidence' => array( 'bootstrap_file_present' => true ),
		);
	}

	return array(
		'state'    => 'unknown',
		'severity' => 'low',
		'message'  => 'Wordfence je aktivní, ale bootstrap soubor rozšířené ochrany (wordfence-waf.php) nebyl v kořeni webu nalezen – může běžet jen v základním režimu, nebo to hosting neumožňuje. Ověřte přímo ve Wordfence → Firewall.',
		'evidence' => array( 'bootstrap_file_present' => false ),
	);
}

/**
 * PROV-TURNSTILE-RESET (verze 2.2, P0 "Bezpečnost resetu hesla"): stejný
 * self-request vzor jako garry_security_check_turnstile_configuration(),
 * jen na `wp_lostpassword_url()` místo přihlašovací stránky. Reset hesla je
 * alternativní vstup do účtu a měl by mít stejnou ochranu jako login (OWASP
 * Authentication Cheat Sheet). GARRY záměrně nezkouší aktivně ověřit
 * rate-limit ani to, jestli odpověď prozrazuje existenci účtu – to by
 * vyžadovalo simulovat odeslání formuláře (aktivní test, ne pasivní čtení),
 * což je mimo rozsah tohoto pluginu (viz "Nepřidávat" v master specu).
 */
function garry_security_check_turnstile_on_reset() {
	$slug = 'simple-cloudflare-turnstile';
	if ( ! garry_security_provider_is_active( $slug ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Simple Cloudflare Turnstile není aktivní – kontrola ochrany resetu hesla se neuplatňuje.',
			'evidence' => array(),
		);
	}

	$reset_url = wp_lostpassword_url();
	$response  = wp_safe_remote_get( $reset_url, array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'Turnstile je aktivní, ale GARRY se nepodařilo ověřit stránku resetu hesla (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$body = wp_remote_retrieve_body( $response );
	if ( false === strpos( $body, 'challenges.cloudflare.com/turnstile' ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => 'Turnstile je aktivní na loginu, ale na stránce resetu hesla nebyl nalezen žádný jeho skript. Reset hesla je alternativní vstup do účtu a měl by mít stejnou ochranu jako přihlášení – zkontrolujte, že je Turnstile zapnutý i pro formulář zapomenutého hesla.',
			'evidence' => array( 'reset_url' => $reset_url ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Turnstile se vykresluje i na stránce resetu hesla, ne jen na loginu.',
		'evidence' => array( 'reset_url' => $reset_url ),
	);
}

/**
 * Pro neaktivního providera vrátí štítky aktivních providerů, kteří už
 * pokrývají VŠECHNY jeho deklarované capabilities – tedy instalace/aktivace
 * by byla čistá duplicita (verze 2.2, zpětná vazba "neduplikovat řešení").
 * Prázdné pole = není čistá duplicita (aspoň jedna capabilita ještě nemá
 * aktivního vlastníka), takže se nabídka zobrazí normálně.
 */
function garry_security_provider_redundant_owners( $slug ) {
	$registry = garry_security_provider_registry();
	if ( ! isset( $registry[ $slug ] ) || empty( $registry[ $slug ]['capabilities'] ) ) {
		return array();
	}

	$owners_by_cap = array();
	foreach ( $registry as $other_slug => $info ) {
		if ( $other_slug === $slug || ! garry_security_provider_is_active( $other_slug ) ) {
			continue;
		}
		foreach ( $info['capabilities'] as $cap ) {
			$owners_by_cap[ $cap ][] = $info['label'];
		}
	}

	$labels = array();
	foreach ( $registry[ $slug ]['capabilities'] as $cap ) {
		if ( empty( $owners_by_cap[ $cap ] ) ) {
			return array();
		}
		foreach ( $owners_by_cap[ $cap ] as $label ) {
			if ( ! in_array( $label, $labels, true ) ) {
				$labels[] = $label;
			}
		}
	}
	return $labels;
}

/**
 * Findings PROV-<SLUG> pro každého providera v registru – čistá discovery,
 * žádné zásahy. Nevybraná/nenainstalovaná komponenta je `not_applicable`,
 * nikoli warning (katalog není povinný balík).
 */
function garry_security_collect_providers() {
	$results  = array();
	$registry = garry_security_provider_registry();

	foreach ( $registry as $slug => $info ) {
		$active    = garry_security_provider_is_active( $slug );
		$installed = $active ? true : garry_security_provider_is_installed( $slug );
		$control_id = 'PROV-' . strtoupper( str_replace( '-', '_', $slug ) );

		if ( $active ) {
			$state   = 'delegated';
			$message = sprintf( '%s je aktivní.', $info['label'] );
		} elseif ( $installed ) {
			$state   = 'warning';
			$message = sprintf( '%s je nainstalovaný, ale neaktivní.', $info['label'] );
		} else {
			$state   = 'not_applicable';
			$message = sprintf( '%s není nainstalován (doporučená, nikoli povinná komponenta).', $info['label'] );
		}

		$results[ $control_id ] = array(
			'state'    => $state,
			'severity' => 'info',
			'message'  => $message,
			'evidence' => array( 'installed' => $installed, 'active' => $active ),
		);
	}

	$results['PROV-TURNSTILE-CONFIG'] = garry_security_check_turnstile_configuration();
	$results['PROV-TURNSTILE-RESET']  = garry_security_check_turnstile_on_reset();
	$results['PROV-WORDFENCE-WAF']    = garry_security_check_wordfence_waf_extended();

	return $results;
}

/**
 * Dynamické položky katalogu pro PROV-* kontroly, aby administrace
 * nemusela ručně duplikovat popisky ze security-catalog.php.
 */
function garry_security_provider_catalog_entries() {
	$entries = array();
	foreach ( garry_security_provider_registry() as $slug => $info ) {
		$control_id             = 'PROV-' . strtoupper( str_replace( '-', '_', $slug ) );
		$entries[ $control_id ] = array( 'title' => $info['label'] . ' (discovery)', 'category' => 'Provider registry' );
	}
	$entries['PROV-TURNSTILE-CONFIG'] = array( 'title' => 'Simple Cloudflare Turnstile – ověřená konfigurace loginu', 'category' => 'Provider registry' );
	$entries['PROV-TURNSTILE-RESET']  = array( 'title' => 'Simple Cloudflare Turnstile – ochrana resetu hesla', 'category' => 'Provider registry' );
	$entries['PROV-WORDFENCE-WAF']    = array( 'title' => 'Wordfence – rozšířená ochrana (WAF bootstrap)', 'category' => 'Provider registry' );
	return $entries;
}

/**
 * Bezpečná instalace pluginu jen z WordPress.org, jen pro allowlisted slug
 * z registru. URL balíčku se vždy dotazuje čerstvě přes plugins_api() –
 * z requestu se nikdy nepřebírá žádná URL ani cesta k souboru.
 */
function garry_security_install_plugin_from_wp_org( $slug ) {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return new WP_Error( 'garry_forbidden', 'Chybí oprávnění install_plugins.' );
	}

	$allowed_slugs = wp_list_pluck( garry_security_provider_registry(), 'wp_org_slug' );
	if ( ! in_array( $slug, $allowed_slugs, true ) ) {
		return new WP_Error( 'garry_not_allowed', 'Tento slug není na GARRY Security allowlistu.' );
	}

	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	$api = plugins_api( 'plugin_information', array(
		'slug'   => $slug,
		'fields' => array( 'sections' => false ),
	) );

	if ( is_wp_error( $api ) ) {
		return $api;
	}
	if ( empty( $api->download_link ) ) {
		return new WP_Error( 'garry_no_package', 'WordPress.org nevrátil odkaz na balíček pluginu.' );
	}

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( ! $result ) {
		$skin_error = $skin->get_errors();
		return new WP_Error( 'garry_install_failed', is_wp_error( $skin_error ) ? $skin_error->get_error_message() : 'Instalace pluginu selhala.' );
	}

	return true;
}

/**
 * Bezpečná aktivace jen pro slug z registru (nikdy libovolná cesta k souboru z requestu).
 */
function garry_security_activate_provider( $slug ) {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return new WP_Error( 'garry_forbidden', 'Chybí oprávnění activate_plugins.' );
	}

	$registry = garry_security_provider_registry();
	if ( ! isset( $registry[ $slug ] ) ) {
		return new WP_Error( 'garry_not_allowed', 'Tento slug není na GARRY Security allowlistu.' );
	}

	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = activate_plugin( $registry[ $slug ]['entry_file'] );

	return is_wp_error( $result ) ? $result : true;
}
