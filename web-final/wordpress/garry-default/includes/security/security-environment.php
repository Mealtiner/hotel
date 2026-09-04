<?php
/**
 * GARRY Security – karta prostředí a serveru + bezpečné stažení .htaccess.
 *
 * Ryze informativní, read-only. Server/LiteSpeed detekce kombinuje statický
 * $_SERVER['SERVER_SOFTWARE'] s funkčním self-requestem na vlastní web (čte
 * skutečné HTTP hlavičky odpovědi – stejný bezpečný self-request vzor jako
 * HTTP/Site Health adapter, cíl je vždy jen tento web). Stažení .htaccess
 * jen čte a streamuje existující soubor, nikdy nic nezapisuje.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function garry_security_server_software_raw() {
	return isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
}

function garry_security_detect_server_type_from_string( $sw ) {
	if ( '' === $sw ) {
		return null;
	}
	if ( false !== stripos( $sw, 'litespeed' ) ) {
		return 'LiteSpeed';
	}
	if ( false !== stripos( $sw, 'nginx' ) ) {
		return 'Nginx';
	}
	if ( false !== stripos( $sw, 'apache' ) ) {
		return 'Apache';
	}
	if ( false !== stripos( $sw, 'iis' ) ) {
		return 'Microsoft IIS';
	}
	if ( false !== stripos( $sw, 'cloudflare' ) ) {
		return 'Cloudflare (proxy – skutečný server za ním není z hlavičky vidět)';
	}
	return $sw;
}

/**
 * Funkční self-request na vlastní homepage – čte skutečnou "Server" hlavičku
 * HTTP odpovědi (může se lišit od $_SERVER['SERVER_SOFTWARE'], které je jen
 * to, co PHP samo hlásí, a hosting/CDN ho může na cestě k prohlížeči změnit
 * nebo skrýt). Výsledek se cachuje jen v rámci jednoho requestu.
 */
function garry_security_server_header_via_self_request() {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}
	$response = wp_safe_remote_get( home_url( '/' ), array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		$cached = array( 'ok' => false, 'headers' => array() );
		return $cached;
	}
	$cached = array( 'ok' => true, 'headers' => wp_remote_retrieve_headers( $response ) );
	return $cached;
}

function garry_security_detect_server_type() {
	$sw       = garry_security_server_software_raw();
	$detected = garry_security_detect_server_type_from_string( $sw );
	if ( null !== $detected ) {
		return $detected;
	}
	// $_SERVER['SERVER_SOFTWARE'] chybí/je prázdné (běžné za některými proxy/CDN) –
	// zkus to samé z HTTP hlavičky skutečné odpovědi vlastního webu.
	$self = garry_security_server_header_via_self_request();
	if ( $self['ok'] && ! empty( $self['headers']['server'] ) ) {
		$from_header = garry_security_detect_server_type_from_string( (string) $self['headers']['server'] );
		if ( null !== $from_header ) {
			return $from_header . ' (zjištěno z HTTP odpovědi, SERVER_SOFTWARE nedostupné)';
		}
	}
	return 'Nezjištěno';
}

function garry_security_is_litespeed() {
	if ( false !== stripos( garry_security_server_software_raw(), 'litespeed' ) ) {
		return true;
	}
	$self = garry_security_server_header_via_self_request();
	return $self['ok'] && ! empty( $self['headers']['server'] ) && false !== stripos( (string) $self['headers']['server'], 'litespeed' );
}

/**
 * LiteSpeed Cache – funkční ověření, ne jen "server je LiteSpeed". Tři
 * nezávislé signály, protože žádný sám o sobě není spolehlivý na 100 %:
 *  - je aktivní plugin LiteSpeed Cache (definitivní signál na straně WP),
 *  - odpověď vlastního webu nese hlavičku x-litespeed-cache (definitivní
 *    signál, že LSCache modul skutečně zpracovává požadavky),
 *  - server je vůbec LiteSpeed (nutná, ne dostačující podmínka).
 */
function garry_security_litespeed_cache_status() {
	$plugin_active = function_exists( 'is_plugin_active' ) && is_plugin_active( 'litespeed-cache/litespeed-cache.php' );
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugin_active = is_plugin_active( 'litespeed-cache/litespeed-cache.php' );
	}

	$self          = garry_security_server_header_via_self_request();
	$cache_header  = false;
	if ( $self['ok'] ) {
		foreach ( $self['headers'] as $name => $value ) {
			if ( false !== stripos( (string) $name, 'litespeed-cache' ) ) {
				$cache_header = true;
				break;
			}
		}
	}

	return array(
		'is_litespeed_server' => garry_security_is_litespeed(),
		'plugin_active'       => (bool) $plugin_active,
		'cache_header_seen'   => $cache_header,
		'request_ok'          => $self['ok'],
	);
}

/**
 * Heuristika pro statický obsah .htaccess: Apache a LiteSpeed ho běžně
 * respektují (pokud to není vypnuté v hlavní konfiguraci serveru, což
 * zvenčí nelze zjistit), Nginx a IIS ho nativně nečtou vůbec. Skutečně
 * FUNKČNÍ důkaz dává ENV-007 (garry_security_check_htaccess_content) tím,
 * že .htaccess umí přečíst a najde v něm platný WordPress rewrite blok –
 * to je nejbližší ověřitelný důkaz, že server soubor opravdu používá.
 */
function garry_security_htaccess_likely_supported() {
	$type = garry_security_detect_server_type();
	return false !== strpos( $type, 'Apache' ) || false !== strpos( $type, 'LiteSpeed' );
}

function garry_security_htaccess_path() {
	return ABSPATH . '.htaccess';
}

function garry_security_htaccess_exists() {
	return is_readable( garry_security_htaccess_path() );
}

/**
 * Statická textová analýza obsahu .htaccess – nikdy zápis, jen čtení
 * souboru, ke kterému už GARRY přistupuje pro zálohu (viz výše). Hledá
 * jednoznačné rizikové direktivy (skutečné, dobře známé vzory) a
 * přítomnost/nepřítomnost běžného doporučeného zpevnění. Neposuzuje
 * konfiguraci mimo tento soubor (vhost/nginx apod. GARRY nevidí).
 */
function garry_security_htaccess_analysis() {
	if ( ! garry_security_htaccess_exists() ) {
		return array( 'readable' => false );
	}
	$content = @file_get_contents( garry_security_htaccess_path(), false, null, 0, 500000 );
	if ( false === $content ) {
		return array( 'readable' => false );
	}

	$red_flags = array();

	if ( preg_match( '/Options\s+\+Indexes/i', $content ) ) {
		$red_flags[] = array(
			'severity' => 'medium',
			'text'     => 'Direktiva "Options +Indexes" výslovně zapíná výpis obsahu adresářů bez indexového souboru.',
		);
	}
	if ( preg_match( '/AddType\s+application\/x-httpd-php\s+\.?(jpe?g|png|gif|bmp|ico|txt|zip)\b/i', $content )
		|| preg_match( '/AddHandler\s+application\/x-httpd-php\s+\.?(jpe?g|png|gif|bmp|ico|txt|zip)\b/i', $content ) ) {
		$red_flags[] = array(
			'severity' => 'critical',
			'text'     => 'Nalezena direktiva, která nechá server spouštět PHP kód v souborech s neobvyklou příponou (obrázek/text/zip) – typický vzor webshellu/malware persistence, ne běžná WordPress konfigurace.',
		);
	}
	if ( preg_match( '/php_flag\s+engine\s+on/i', $content ) && preg_match( '/uploads/i', $content ) ) {
		$red_flags[] = array(
			'severity' => 'high',
			'text'     => 'Nalezena direktiva výslovně zapínající PHP engine v kontextu obsahujícím "uploads" – ověřte ručně, jestli nejde o povolení spouštění PHP v nahrávaném obsahu.',
		);
	}

	return array(
		'readable'              => true,
		'size'                  => strlen( $content ),
		'red_flags'             => $red_flags,
		'has_wp_core_block'     => false !== strpos( $content, '# BEGIN WordPress' ),
		'has_indexes_off'       => (bool) preg_match( '/Options\s+-Indexes/i', $content ),
		'has_htaccess_self_protect' => (bool) preg_match( '/<Files(Match)?\s+~?\s*["\']?\^?\\\\?\.ht/i', $content ),
		'has_wpconfig_protect'  => (bool) preg_match( '/wp-config\.php/i', $content ),
		'has_xmlrpc_block'      => (bool) preg_match( '/xmlrpc\.php/i', $content ),
	);
}

/**
 * ENV-007: shrne garry_security_htaccess_analysis() do jednoho nálezu.
 * Rudé vlajky (známý malware/webshell vzor) = critical bez ohledu na
 * cokoli dalšího. Jinak jen informuje, které běžné zpevnění chybí –
 * chybějící volitelné zpevnění samo o sobě není `critical` ani `warning`
 * s vysokou závažností, je to doporučení.
 */
function garry_security_check_htaccess_content() {
	$analysis = garry_security_htaccess_analysis();

	if ( empty( $analysis['readable'] ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Soubor .htaccess nebyl nalezen nebo není čitelný – kontrola obsahu se neuplatňuje (typicky Nginx, nebo server bez podpory .htaccess).',
			'evidence' => array(),
		);
	}

	if ( ! empty( $analysis['red_flags'] ) ) {
		$worst = 'high';
		foreach ( $analysis['red_flags'] as $flag ) {
			if ( 'critical' === $flag['severity'] ) {
				$worst = 'critical';
			}
		}
		return array(
			'state'    => 'critical',
			'severity' => $worst,
			'message'  => 'V .htaccess nalezeny rizikové direktivy: ' . implode( ' ', wp_list_pluck( $analysis['red_flags'], 'text' ) ),
			'evidence' => array( 'red_flags' => $analysis['red_flags'] ),
		);
	}

	$missing = array();
	if ( empty( $analysis['has_indexes_off'] ) ) {
		$missing[] = 'výpis adresářů není v .htaccess výslovně vypnutý (Options -Indexes)';
	}
	if ( empty( $analysis['has_wpconfig_protect'] ) ) {
		$missing[] = 'chybí pravidlo blokující přímý HTTP přístup k wp-config.php';
	}
	if ( empty( $analysis['has_htaccess_self_protect'] ) ) {
		$missing[] = 'chybí pravidlo blokující přímý HTTP přístup k souborům .ht*';
	}

	if ( ! empty( $missing ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'Obsah .htaccess neobsahuje žádné rizikové direktivy, ale chybí běžné doporučené zpevnění: ' . implode( '; ', $missing ) . '.',
			'evidence' => $analysis,
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Obsah .htaccess neobsahuje rizikové direktivy a má běžné doporučené zpevnění (blokace adresářového výpisu, wp-config.php a .ht* souborů).',
		'evidence' => $analysis,
	);
}

/**
 * DNS záznamy domény, na které tenhle web běží – čistě read-only DNS
 * dotazy přes vestavěné PHP dns_get_record() (žádná externí API, jen
 * standardní DNS resolver serveru). Cloudflare detekce kombinuje dva
 * nezávislé signály: nameservery (NS záznamy) a hlavičku cf-ray ve
 * skutečné HTTP odpovědi vlastního webu (stejný self-request jako
 * jinde v tomhle souboru). Rozlišit běžné Cloudflare proxy od
 * Cloudflare Tunnel (cloudflared bez veřejného portu na originu) zvenčí
 * spolehlivě nejde – GARRY hlásí jen "web je za Cloudflare", ne který
 * konkrétní produkt.
 */
function garry_security_dns_domain() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return is_string( $host ) ? $host : '';
}

function garry_security_collect_dns_records() {
	$domain = garry_security_dns_domain();
	if ( '' === $domain || ! function_exists( 'dns_get_record' ) ) {
		return array( 'supported' => false, 'domain' => $domain, 'records' => array() );
	}

	$types = array(
		'A'     => DNS_A,
		'AAAA'  => DNS_AAAA,
		'MX'    => DNS_MX,
		'TXT'   => DNS_TXT,
		'NS'    => DNS_NS,
		'CNAME' => DNS_CNAME,
	);
	$records = array();
	foreach ( $types as $label => $type ) {
		$result           = @dns_get_record( $domain, $type );
		$records[ $label ] = is_array( $result ) ? $result : array();
	}

	return array( 'supported' => true, 'domain' => $domain, 'records' => $records );
}

function garry_security_cloudflare_status() {
	$dns              = garry_security_collect_dns_records();
	$ns_is_cloudflare = false;
	$ns_list          = array();
	if ( ! empty( $dns['records']['NS'] ) ) {
		foreach ( $dns['records']['NS'] as $rec ) {
			if ( ! empty( $rec['target'] ) ) {
				$ns_list[] = $rec['target'];
				if ( false !== stripos( $rec['target'], 'cloudflare.com' ) ) {
					$ns_is_cloudflare = true;
				}
			}
		}
	}

	$self        = garry_security_server_header_via_self_request();
	$cf_ray_seen = $self['ok'] && ! empty( $self['headers']['cf-ray'] );

	return array(
		'ns_is_cloudflare' => $ns_is_cloudflare,
		'ns_list'          => $ns_list,
		'cf_ray_seen'      => $cf_ray_seen,
		'proxied'          => $ns_is_cloudflare || $cf_ray_seen,
	);
}

/** ENV-008: ryze informativní shrnutí DNS/Cloudflare stavu. */
function garry_security_check_dns_records() {
	$dns = garry_security_collect_dns_records();
	if ( empty( $dns['supported'] ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'info',
			'message'  => 'DNS dotazy nejsou na tomto serveru dostupné (funkce dns_get_record chybí nebo je zakázaná) – kontrola se neuplatňuje.',
			'evidence' => array(),
		);
	}

	$cf = garry_security_cloudflare_status();
	$a_count = count( $dns['records']['A'] ?? array() );
	$aaaa_count = count( $dns['records']['AAAA'] ?? array() );

	$message = sprintf( 'Doména %1$s: %2$d A záznam(ů), %3$d AAAA záznam(ů).', $dns['domain'], $a_count, $aaaa_count );
	$message .= $cf['proxied']
		? ' Web je pravděpodobně za Cloudflare (proxy nebo Tunnel – zvenčí nelze spolehlivě rozlišit).'
		: ' Cloudflare proxy nebyla detekována (podle nameserverů ani hlavičky cf-ray).';

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => $message,
		'evidence' => array( 'dns' => $dns, 'cloudflare' => $cf ),
	);
}

/**
 * ENV-009 (verze 2.2, P2 "E-mailová důvěryhodnost"): SPF a DMARC. Oba
 * jsou obyčejné TXT záznamy, čte se stejným dns_get_record() jako výše –
 * SPF z už načtených TXT záznamů domény, DMARC samostatným dotazem na
 * `_dmarc.<doména>`. DKIM se záměrně nekontroluje – jeho selector se u
 * každého poskytovatele/pluginu jmenuje jinak a bez znalosti konkrétního
 * selectoru by GARRY musel hádat, což by mohlo dát falešně negativní
 * výsledek (viz zásada "nikdy nefabrikovat jistotu").
 */
function garry_security_check_email_trust_records() {
	$dns = garry_security_collect_dns_records();
	if ( empty( $dns['supported'] ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'info',
			'message'  => 'DNS dotazy nejsou na tomto serveru dostupné – kontrolu SPF/DMARC nelze provést.',
			'evidence' => array(),
		);
	}

	$spf_found = false;
	foreach ( $dns['records']['TXT'] ?? array() as $rec ) {
		if ( ! empty( $rec['txt'] ) && 0 === stripos( $rec['txt'], 'v=spf1' ) ) {
			$spf_found = true;
			break;
		}
	}

	$dmarc_found  = false;
	$dmarc_domain = '_dmarc.' . $dns['domain'];
	if ( '' !== $dns['domain'] ) {
		$dmarc_records = @dns_get_record( $dmarc_domain, DNS_TXT );
		if ( is_array( $dmarc_records ) ) {
			foreach ( $dmarc_records as $rec ) {
				if ( ! empty( $rec['txt'] ) && 0 === stripos( $rec['txt'], 'v=DMARC1' ) ) {
					$dmarc_found = true;
					break;
				}
			}
		}
	}

	$missing = array();
	if ( ! $spf_found ) { $missing[] = 'SPF'; }
	if ( ! $dmarc_found ) { $missing[] = 'DMARC'; }

	if ( empty( $missing ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Doména má nastavený SPF i DMARC záznam. DKIM GARRY neověřuje (selector se liší podle poskytovatele) – ověřte přímo u SMTP poskytovatele.',
			'evidence' => array( 'spf' => $spf_found, 'dmarc' => $dmarc_found ),
		);
	}

	return array(
		'state'    => 'warning',
		'severity' => 'medium',
		'message'  => sprintf(
			'Doméně chybí: %s. Bez nich se e-maily z webu (reset hesla, objednávky) snáz označí jako spam nebo je lze podvrhnout. DKIM GARRY neověřuje – ověřte přímo u SMTP poskytovatele.',
			implode( ' a ', $missing )
		),
		'evidence' => array( 'spf' => $spf_found, 'dmarc' => $dmarc_found ),
	);
}

/**
 * PHP limity a nastavení relevantní pro provoz WordPressu – ryze
 * informativní čtení přes ini_get(), nikdy zápis (změna PHP limitů
 * vyžaduje php.ini/.htaccess/hostingové rozhraní, GARRY do nich nesahá).
 */
function garry_security_collect_php_limits() {
	return array(
		'memory_limit'        => ini_get( 'memory_limit' ),
		'wp_memory_limit'      => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : null,
		'wp_max_memory_limit'  => defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : null,
		'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
		'post_max_size'       => ini_get( 'post_max_size' ),
		'max_execution_time'  => ini_get( 'max_execution_time' ),
		'max_input_time'      => ini_get( 'max_input_time' ),
		'max_input_vars'      => ini_get( 'max_input_vars' ),
		'opcache_enabled'     => function_exists( 'opcache_get_status' ) && @opcache_get_status( false ) !== false,
	);
}

/**
 * Sdílený zdroj pravdy pro doporučená minima PHP limitů – čte ho jak
 * garry_security_check_php_limits() (nález ENV-006), tak admin karta PHP
 * limity (zvýraznění hodnot pod prahem), aby se prahové hodnoty neduplikovaly
 * na dvou místech.
 *
 * @return array<string,string> klíč limitu => lidsky čitelný důvod, jen pro klíče pod prahem.
 */
function garry_security_php_limits_below_threshold( array $limits ) {
	$issues = array();

	$memory_bytes = wp_convert_hr_to_bytes( $limits['memory_limit'] );
	if ( $memory_bytes > 0 && $memory_bytes < 128 * MB_IN_BYTES ) {
		$issues['memory_limit'] = sprintf( 'memory_limit (%s) je nižší než doporučených 128M', $limits['memory_limit'] );
	}
	$upload_bytes = wp_convert_hr_to_bytes( $limits['upload_max_filesize'] );
	if ( $upload_bytes > 0 && $upload_bytes < 32 * MB_IN_BYTES ) {
		$issues['upload_max_filesize'] = sprintf( 'upload_max_filesize (%s) je nižší než doporučených 32M', $limits['upload_max_filesize'] );
	}
	if ( '' !== (string) $limits['max_execution_time'] && (int) $limits['max_execution_time'] > 0 && (int) $limits['max_execution_time'] < 30 ) {
		$issues['max_execution_time'] = sprintf( 'max_execution_time (%ds) je nižší než doporučených 30s', (int) $limits['max_execution_time'] );
	}
	if ( '' !== (string) $limits['max_input_vars'] && (int) $limits['max_input_vars'] < 1000 ) {
		$issues['max_input_vars'] = sprintf( 'max_input_vars (%d) je nižší než doporučených 1000 (velké formuláře/nastavení s pluginy mohou ořezávat data)', (int) $limits['max_input_vars'] );
	}

	return $issues;
}

/**
 * Heuristické, ryze informativní upozornění na limity, které bývají pod
 * doporučeným minimem pro běžný WordPress provoz (media upload, pluginy
 * s většími importy apod.). Nejde o tvrdý požadavek – jen doporučení.
 */
function garry_security_check_php_limits() {
	$limits = garry_security_collect_php_limits();
	$issues = garry_security_php_limits_below_threshold( $limits );

	if ( ! empty( $issues ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'Některé PHP limity jsou pod doporučeným minimem: ' . implode( '; ', $issues ) . '. Úprava vyžaduje php.ini/hosting – GARRY to sám nemění.',
			'evidence' => $limits,
		);
	}
	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'PHP limity odpovídají doporučenému minimu pro provoz WordPressu.',
		'evidence' => $limits,
	);
}

function garry_security_collect_environment_card() {
	global $wpdb;
	return array(
		'php_version'        => PHP_VERSION,
		'mysql_version'      => method_exists( $wpdb, 'db_version' ) ? $wpdb->db_version() : 'neznámá',
		'db_charset'         => isset( $wpdb->charset ) ? $wpdb->charset : 'neznámý',
		'server_type'        => garry_security_detect_server_type(),
		'server_software_raw' => garry_security_server_software_raw(),
		'is_litespeed'       => garry_security_is_litespeed(),
		'litespeed_cache'    => garry_security_litespeed_cache_status(),
		'htaccess_supported' => garry_security_htaccess_likely_supported(),
		'htaccess_exists'    => garry_security_htaccess_exists(),
	);
}

/**
 * Bezpečné stažení .htaccess jako lokální zálohy před ruční úpravou.
 * Jen čte a streamuje – nikdy nezapisuje, nemaže, nepřejmenovává.
 */
function garry_security_handle_download_htaccess() {
	if ( ! current_user_can( GARRY_SECURITY_CAP_VIEW ) || ! check_admin_referer( 'garry_security_download_htaccess' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
	}
	$path = garry_security_htaccess_path();
	if ( ! is_readable( $path ) ) {
		wp_die( esc_html__( 'Soubor .htaccess nebyl nalezen nebo není čitelný.', 'garry-default' ) );
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="htaccess-zaloha-' . gmdate( 'Y-m-d' ) . '.txt"' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path );
	exit;
}
