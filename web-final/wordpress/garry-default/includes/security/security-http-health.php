<?php
/**
 * GARRY Security – HTTP/Site Health adapter (Fáze D, bod 5).
 *
 * Podle docs/GARRY-WP-SECURITY-AUDIT-REMEDIATION-ROADMAP.md, Fáze D:
 * "bezpečně ověřuje HTTPS, redirect, hlavičky, robots/llms response a
 * loopback. SSRF ochrana dovolí pouze vlastní canonical host a explicitní
 * interní targety." Všechny požadavky tady míří výhradně na home_url()
 * tohoto webu – nikdy na URL z requestu ani na cizí host, takže SSRF
 * riziko architektonicky nevzniká.
 *
 * Loopback test se záměrně nevymýšlí znovu – deleguje na WP_Site_Health,
 * které WordPress už dodává a udržuje (viz master spec, sekce "Nepřebírat":
 * neduplikovat existující ochrany/kontroly jiných částí ekosystému).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTTP-001: požadavek na http:// variantu domény musí vést na https://
 * (přes redirect řetězec, ne jen shodou náhody prvního URL). Provádí se
 * jen pokud web sám deklaruje HTTPS (WEB-001 pass) – jinak je to duplicitní
 * hlášení stejného problému.
 */
function garry_security_check_http_redirect() {
	$home = home_url();
	if ( 0 !== strpos( $home, 'https://' ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Web nedeklaruje HTTPS jako home_url – kontrola redirectu se neuplatňuje (viz WEB-001).',
			'evidence' => array(),
		);
	}

	$http_url = 'http://' . substr( $home, strlen( 'https://' ) );
	$response = wp_safe_remote_get( $http_url, array(
		'timeout'     => 5,
		'redirection' => 0,
	) );

	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'Nepodařilo se ověřit HTTP → HTTPS redirect (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$code     = (int) wp_remote_retrieve_response_code( $response );
	$location = wp_remote_retrieve_header( $response, 'location' );
	$redirects_to_https = in_array( $code, array( 301, 302, 307, 308 ), true ) && is_string( $location ) && 0 === strpos( $location, 'https://' );

	return array(
		'state'    => $redirects_to_https ? 'pass' : 'warning',
		'severity' => $redirects_to_https ? 'info' : 'medium',
		'message'  => $redirects_to_https
			? 'HTTP verze domény správně přesměrovává na HTTPS.'
			: sprintf( 'HTTP verze domény nepřesměrovává spolehlivě na HTTPS (status %d).', $code ),
		'evidence' => array( 'status' => $code ),
	);
}

/**
 * HTTP-002: přítomnost běžných bezpečnostních hlaviček na vlastní homepage.
 * Ryze informativní – absence neznamená automaticky zranitelnost (hosting/
 * CDN je často nastavuje mimo WordPress), proto nejvyšší stav je `warning`,
 * ne `critical`.
 */
function garry_security_check_security_headers() {
	$response = wp_safe_remote_get( home_url( '/' ), array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'Nepodařilo se ověřit bezpečnostní hlavičky (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$headers = wp_remote_retrieve_headers( $response );

	/**
	 * Rozšířeno ve verzi 2.2 (P1 "Webové hlavičky") o HSTS a ochranu proti
	 * vložení do iframe. CSP se záměrně nekontroluje jako povinná – návrh
	 * struktury i toenhle plugin ji řeší jako postupný cíl, ne automaticky
	 * kritickou chybu (velmi snadno rozbije vlastní web při špatném
	 * nastavení, GARRY ji proto nehodnotí vůbec, aby nenavrhoval falešnou
	 * jistotu ani netlačil na rizikovou změnu).
	 */
	$checked = array( 'x-content-type-options', 'referrer-policy' );
	$missing = array();
	foreach ( $checked as $h ) {
		if ( empty( $headers[ $h ] ) ) {
			$missing[] = $h;
		}
	}

	$is_https = ( 0 === strpos( home_url(), 'https://' ) );
	if ( $is_https && empty( $headers['strict-transport-security'] ) ) {
		$missing[] = 'strict-transport-security (HSTS)';
	}

	$has_frame_protection = ! empty( $headers['x-frame-options'] )
		|| ( ! empty( $headers['content-security-policy'] ) && false !== stripos( $headers['content-security-policy'], 'frame-ancestors' ) );
	if ( ! $has_frame_protection ) {
		$missing[] = 'x-frame-options (nebo CSP frame-ancestors)';
	}

	if ( empty( $missing ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Sledované bezpečnostní hlavičky jsou přítomné (typ obsahu, referrer, HSTS, ochrana proti vložení do iframe).',
			'evidence' => array(),
		);
	}

	return array(
		'state'    => 'warning',
		'severity' => 'medium',
		'message'  => sprintf( 'Chybí doporučené hlavičky: %s. Často je nastavuje hosting/CDN mimo WordPress – ověřte tam.', implode( ', ', $missing ) ),
		'evidence' => array( 'missing' => $missing ),
	);
}

/**
 * HTTP-003: schopnost webu provést loopback požadavek sám na sebe (nutné
 * pro spolehlivý běh WP-Cron, tedy i denního GARRY Security scanu). Čte
 * výsledek přímo z WP_Site_Health, aby GARRY neduplikoval vlastní logiku.
 */
function garry_security_check_loopback() {
	if ( ! class_exists( 'WP_Site_Health' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
	}
	if ( ! method_exists( 'WP_Site_Health', 'get_instance' ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'WP_Site_Health není na tomto WordPressu dostupné – loopback nelze ověřit.',
			'evidence' => array(),
		);
	}

	$health = WP_Site_Health::get_instance();
	$result = $health->get_test_loopback_requests();
	$status = isset( $result['status'] ) ? $result['status'] : 'unknown';

	$map = array( 'good' => 'pass', 'recommended' => 'warning', 'critical' => 'critical' );
	$state = isset( $map[ $status ] ) ? $map[ $status ] : 'unknown';

	return array(
		'state'    => $state,
		'severity' => 'good' === $status ? 'info' : 'medium',
		'message'  => isset( $result['label'] ) ? wp_strip_all_tags( $result['label'] ) : 'Stav loopback požadavků z WP_Site_Health.',
		'evidence' => array( 'site_health_status' => $status ),
	);
}

function garry_security_collect_http_health() {
	return array(
		'HTTP-001' => garry_security_check_http_redirect(),
		'HTTP-002' => garry_security_check_security_headers(),
		'HTTP-003' => garry_security_check_loopback(),
	);
}

function garry_security_http_health_catalog_entries() {
	return array(
		'HTTP-001' => array( 'title' => 'HTTP → HTTPS redirect', 'category' => 'HTTPS a povrch' ),
		'HTTP-002' => array( 'title' => 'Doporučené bezpečnostní hlavičky', 'category' => 'HTTPS a povrch' ),
		'HTTP-003' => array( 'title' => 'Loopback požadavky (WP-Cron)', 'category' => 'HTTPS a povrch' ),
	);
}
