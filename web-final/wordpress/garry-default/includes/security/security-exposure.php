<?php
/**
 * GARRY Security – veřejné vystavení webu nad rámec Fáze D (verze 2.1):
 * XML-RPC, REST API enumerace uživatelů, ?author= enumerace a platnost
 * TLS certifikátu.
 *
 * Podle "GARRY – návrh struktury a bezpečnostních kontrol", sekce
 * "Aplikační ochrana a vystavení". Stejný bezpečný self-request vzor jako
 * v security-http-health.php – všechny požadavky míří výhradně na
 * home_url() tohoto webu, nikdy na URL z requestu, takže SSRF riziko
 * architektonicky nevzniká. Žádná z kontrol nezkouší nic prolomit ani
 * zneužít (žádné XML-RPC multicall probing, žádné hádání hesel) – jen
 * čte, co by viděl běžný návštěvník.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WEB-003: dostupnost xmlrpc.php. GARRY neumí zjistit, jestli je pingback
 * skutečně použitelný k útoku (to by vyžadovalo multicall probing, které
 * je samo o sobě útočná technika) – jen hlásí, že je endpoint dosažitelný,
 * což je nejčastější vstupní bod pro XML-RPC brute-force/amplifikaci.
 */
function garry_security_check_xmlrpc_exposure() {
	$response = wp_safe_remote_get( home_url( '/xmlrpc.php' ), array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se ověřit dostupnost xmlrpc.php (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	if ( in_array( $code, array( 403, 404, 410 ), true ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'xmlrpc.php není zvenčí dosažitelný (blokováno na úrovni serveru nebo pluginu).',
			'evidence' => array( 'status' => $code ),
		);
	}

	if ( false !== stripos( $body, 'XML-RPC server accepts POST requests only' ) || 405 === $code ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => 'xmlrpc.php je zvenčí dosažitelný. Pokud nepoužíváte Jetpack, mobilní WordPress appku ani pingback/trackback, zvažte jeho zablokování (pluginem nebo pravidlem na serveru) – je to častý cíl brute-force a XML-RPC amplifikačních útoků.',
			'evidence' => array( 'status' => $code ),
		);
	}

	return array(
		'state'    => 'unknown',
		'severity' => 'low',
		'message'  => 'Odpověď na xmlrpc.php neodpovídá standardnímu formátu WordPressu – stav nelze spolehlivě určit.',
		'evidence' => array( 'status' => $code ),
	);
}

/**
 * WEB-004: veřejná REST API enumerace uživatelů (/wp-json/wp/v2/users).
 * WordPress ji od 4.7 defaultně vystavuje bez přihlášení. Sama o sobě
 * neprozrazuje hesla, ale usnadňuje cílený brute-force na reálná
 * uživatelská jména.
 */
function garry_security_check_rest_user_enumeration() {
	$response = wp_safe_remote_get( rest_url( 'wp/v2/users' ), array( 'timeout' => 5 ) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se ověřit REST API enumeraci uživatelů (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Seznam uživatelů přes REST API (/wp-json/wp/v2/users) není veřejně dostupný.',
			'evidence' => array( 'status' => $code ),
		);
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) || empty( $data ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'REST API endpoint uživatelů odpověděl, ale bez veřejných záznamů.',
			'evidence' => array( 'status' => $code ),
		);
	}

	$slugs = array_slice( array_filter( array_map( function ( $u ) { return isset( $u['slug'] ) ? $u['slug'] : null; }, $data ) ), 0, 5 );

	return array(
		'state'    => 'warning',
		'severity' => 'medium',
		'message'  => sprintf(
			'REST API veřejně vystavuje %1$d uživatelských jmen (např. %2$s) přes /wp-json/wp/v2/users – běžné WordPress chování, ale usnadňuje cílený brute-force. Řeší se pluginem pro omezení REST API, ne zásahem GARRY do jádra.',
			count( $data ),
			implode( ', ', $slugs )
		),
		'evidence' => array( 'status' => $code, 'count' => count( $data ) ),
	);
}

/**
 * WEB-005: enumerace uživatelských jmen přes ?author=ID (starý WordPress
 * idiom – neregistrovaný požadavek přesměruje na /author/<slug>/, čímž
 * prozradí přihlašovací jméno prvního uživatele).
 */
function garry_security_check_author_enumeration() {
	$response = wp_safe_remote_get( add_query_arg( 'author', 1, home_url( '/' ) ), array(
		'timeout'     => 5,
		'redirection' => 0,
	) );
	if ( is_wp_error( $response ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se ověřit enumeraci přes ?author= (chyba požadavku): ' . $response->get_error_message(),
			'evidence' => array(),
		);
	}

	$code     = (int) wp_remote_retrieve_response_code( $response );
	$location = wp_remote_retrieve_header( $response, 'location' );

	if ( in_array( $code, array( 301, 302, 307, 308 ), true ) && is_string( $location ) && false !== strpos( $location, '/author/' ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'low',
			'message'  => 'Požadavek ?author=1 přesměrovává na veřejnou autorskou stránku a tím prozrazuje uživatelské jméno prvního uživatele. Standardní chování WordPressu – řeší se pluginem, který takové přesměrování blokuje.',
			'evidence' => array( 'status' => $code, 'location' => $location ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => 'Požadavek ?author=1 neprozrazuje uživatelské jméno přesměrováním na autorskou stránku.',
		'evidence' => array( 'status' => $code ),
	);
}

/**
 * WEB-006: platnost TLS certifikátu domény. Čte se přímo z TLS handshake
 * (stream_socket_client s "capture_peer_cert"), ne z HTTP odpovědi – funguje
 * i když web certifikát sám neukazuje v žádné hlavičce.
 */
function garry_security_check_certificate_expiry() {
	$home = home_url();
	if ( 0 !== strpos( $home, 'https://' ) ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Web nedeklaruje HTTPS jako home_url – platnost certifikátu se neuplatňuje (viz WEB-001).',
			'evidence' => array(),
		);
	}
	if ( ! function_exists( 'openssl_x509_parse' ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'PHP rozšíření OpenSSL není na tomto serveru dostupné – platnost certifikátu nelze ověřit.',
			'evidence' => array(),
		);
	}

	$host = wp_parse_url( $home, PHP_URL_HOST );
	if ( empty( $host ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Nepodařilo se určit doménu webu pro ověření certifikátu.',
			'evidence' => array(),
		);
	}

	$context = stream_context_create( array(
		'ssl' => array(
			'capture_peer_cert' => true,
			'verify_peer'       => false,
			'verify_peer_name'  => false,
		),
	) );

	$client = @stream_socket_client(
		'ssl://' . $host . ':443',
		$errno,
		$errstr,
		5,
		STREAM_CLIENT_CONNECT,
		$context
	);

	if ( false === $client ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => sprintf( 'Nepodařilo se navázat TLS spojení k %s pro ověření certifikátu: %s.', $host, $errstr ),
			'evidence' => array( 'host' => $host ),
		);
	}

	$params = stream_context_get_params( $client );
	fclose( $client );

	if ( empty( $params['options']['ssl']['peer_certificate'] ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'TLS spojení proběhlo, ale certifikát se nepodařilo přečíst.',
			'evidence' => array( 'host' => $host ),
		);
	}

	$cert_info = openssl_x509_parse( $params['options']['ssl']['peer_certificate'] );
	if ( empty( $cert_info['validTo_time_t'] ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Certifikát se podařilo přečíst, ale bez rozpoznatelného data platnosti.',
			'evidence' => array( 'host' => $host ),
		);
	}

	$expires_at    = (int) $cert_info['validTo_time_t'];
	$days_left     = (int) floor( ( $expires_at - time() ) / DAY_IN_SECONDS );
	$expires_label = gmdate( 'Y-m-d', $expires_at );

	if ( $days_left < 0 ) {
		return array(
			'state'    => 'critical',
			'severity' => 'critical',
			'message'  => sprintf( 'TLS certifikát pro %1$s vypršel %2$s.', $host, $expires_label ),
			'evidence' => array( 'host' => $host, 'expires_at' => $expires_label, 'days_left' => $days_left ),
		);
	}
	if ( $days_left < 14 ) {
		return array(
			'state'    => 'warning',
			'severity' => 'high',
			'message'  => sprintf( 'TLS certifikát pro %1$s vyprší za %2$d dní (%3$s).', $host, $days_left, $expires_label ),
			'evidence' => array( 'host' => $host, 'expires_at' => $expires_label, 'days_left' => $days_left ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'TLS certifikát pro %1$s je platný do %2$s (%3$d dní).', $host, $expires_label, $days_left ),
		'evidence' => array( 'host' => $host, 'expires_at' => $expires_label, 'days_left' => $days_left ),
	);
}

function garry_security_collect_exposure() {
	return array(
		'WEB-003' => garry_security_check_xmlrpc_exposure(),
		'WEB-004' => garry_security_check_rest_user_enumeration(),
		'WEB-005' => garry_security_check_author_enumeration(),
		'WEB-006' => garry_security_check_certificate_expiry(),
	);
}

function garry_security_exposure_catalog_entries() {
	return array(
		'WEB-003' => array( 'title' => 'Dostupnost XML-RPC (xmlrpc.php)', 'category' => 'HTTPS a povrch' ),
		'WEB-004' => array( 'title' => 'Enumerace uživatelů přes REST API', 'category' => 'HTTPS a povrch' ),
		'WEB-005' => array( 'title' => 'Enumerace uživatelů přes ?author=', 'category' => 'HTTPS a povrch' ),
		'WEB-006' => array( 'title' => 'Platnost TLS certifikátu', 'category' => 'HTTPS a povrch' ),
	);
}
