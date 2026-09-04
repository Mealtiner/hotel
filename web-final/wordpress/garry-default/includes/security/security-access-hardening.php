<?php
/**
 * GARRY Security – přístup a účty nad rámec Fáze 1/2 (verze 2.1):
 * poslední přihlášení administrátorů, neaktivní administrátorské účty
 * a stav Application Passwords.
 *
 * Podle "GARRY – návrh struktury a bezpečnostních kontrol", sekce
 * "Přístup a účty". GARRY do verze 2.0.0 neměl žádné vlastní sledování
 * přihlášení – bez něj nešlo poslední přihlášení ani neaktivitu ověřit,
 * jen fabrikovat. Místo hádání cizích (Wordfence) interních dat GARRY
 * teď vede vlastní, minimální, neveřejný záznam jen pro role s manage_options
 * (usermeta, ne log s IP/e-maily – LocalLog princip "žádná citlivá data"
 * platí i tady).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const GARRY_SECURITY_LAST_LOGIN_META = 'garry_security_last_login';

/** Práh pro "neaktivní administrátorský účet" – pevná výchozí hodnota, dokud GARRY nemá politiku webu (viz README, "Záměrně nedokončeno"). */
function garry_security_inactive_admin_threshold_days() {
	return 180;
}

/**
 * Zapíše čas přihlášení jen administrátorům – ne kvůli sledování běžných
 * uživatelů, ale protože ACC-001/ACC-002 se ptají výhradně na účty se
 * správcovským přístupem. Ukládá se jen unixový čas, žádná IP ani jiný
 * citlivý údaj.
 */
function garry_security_record_login( $user_login, $user ) {
	if ( ! ( $user instanceof WP_User ) || ! in_array( 'administrator', (array) $user->roles, true ) ) {
		return;
	}
	update_user_meta( $user->ID, GARRY_SECURITY_LAST_LOGIN_META, time() );
}
add_action( 'wp_login', 'garry_security_record_login', 10, 2 );

/**
 * @return array<int,array{id:int,name:string,last_login:int|null}>
 */
function garry_security_admin_login_snapshot() {
	$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID', 'display_name' ) ) );
	$rows   = array();
	foreach ( $admins as $admin ) {
		$last = get_user_meta( $admin->ID, GARRY_SECURITY_LAST_LOGIN_META, true );
		$rows[] = array(
			'id'         => (int) $admin->ID,
			'name'       => $admin->display_name,
			'last_login' => $last ? (int) $last : null,
		);
	}
	return $rows;
}

/**
 * ACC-001: poslední úspěšné přihlášení administrátorů. GARRY sledování
 * spustil až od téhle verze, takže u účtů bez záznamu se poctivě řekne
 * "sledování teprve začalo", ne domněnka o neaktivitě (viz ACC-002).
 */
function garry_security_check_admin_last_login() {
	$rows = garry_security_admin_login_snapshot();
	if ( empty( $rows ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'medium',
			'message'  => 'Nenalezen žádný účet s rolí administrátor.',
			'evidence' => array(),
		);
	}

	$tracked   = array_filter( $rows, function ( $r ) { return null !== $r['last_login']; } );
	$untracked = count( $rows ) - count( $tracked );

	if ( empty( $tracked ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'info',
			'message'  => sprintf( 'GARRY eviduje přihlášení až od aktivace téhle verze (2.1.0) – historická data nedoplňuje. Zatím nemá záznam u žádného z %d administrátorů, objeví se po jejich příštím přihlášení.', count( $rows ) ),
			'evidence' => array( 'admin_count' => count( $rows ) ),
		);
	}

	usort( $tracked, function ( $a, $b ) { return $a['last_login'] <=> $b['last_login']; } );
	$oldest = reset( $tracked );

	$message = sprintf(
		'Nejstarší zaznamenané přihlášení administrátora: %s (%s).',
		esc_html( $oldest['name'] ),
		gmdate( 'Y-m-d', $oldest['last_login'] )
	);
	if ( $untracked > 0 ) {
		$message .= sprintf( ' %d administrátorů se ještě nepřihlásilo od zavedení sledování (GARRY 2.1.0).', $untracked );
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => $message,
		'evidence' => array( 'tracked' => count( $tracked ), 'untracked' => $untracked ),
	);
}

/**
 * ACC-002: administrátorské účty bez přihlášení déle než práh. Počítá jen
 * účty s reálně zaznamenaným přihlášením – účty, u kterých sledování
 * teprve začalo, se do "neaktivních" nepočítají (to by bylo falešně
 * kritické hned po nasazení téhle verze u každého webu).
 */
function garry_security_check_inactive_admins() {
	$rows      = garry_security_admin_login_snapshot();
	$threshold = garry_security_inactive_admin_threshold_days();
	$cutoff    = time() - $threshold * DAY_IN_SECONDS;

	$inactive = array();
	foreach ( $rows as $row ) {
		if ( null !== $row['last_login'] && $row['last_login'] < $cutoff ) {
			$inactive[] = sprintf( '%1$s (naposledy %2$s)', $row['name'], gmdate( 'Y-m-d', $row['last_login'] ) );
		}
	}

	if ( ! empty( $inactive ) ) {
		return array(
			'state'    => 'warning',
			'severity' => 'medium',
			'message'  => sprintf(
				'%1$d administrátorských účtů se nepřihlásilo přes %2$d dní: %3$s. Zvažte omezení role nebo deaktivaci účtu, pokud už není potřeba.',
				count( $inactive ),
				$threshold,
				implode( ', ', $inactive )
			),
			'evidence' => array( 'threshold_days' => $threshold, 'accounts' => $inactive ),
		);
	}

	return array(
		'state'    => 'pass',
		'severity' => 'info',
		'message'  => sprintf( 'Žádný administrátorský účet se sledovaným přihlášením nepřekračuje práh %d dní neaktivity.', $threshold ),
		'evidence' => array( 'threshold_days' => $threshold ),
	);
}

/**
 * ACC-003: dostupnost a využití Application Passwords (WP 5.6+ jádrová
 * funkce). Čte jen počet uložených hesel přes veřejné jádrové API, nikdy
 * jejich hash ani název – ten by mohl nechtěně nést citlivý popisek.
 */
function garry_security_check_application_passwords() {
	if ( ! function_exists( 'wp_is_application_passwords_available' ) || ! wp_is_application_passwords_available() ) {
		return array(
			'state'    => 'not_applicable',
			'severity' => 'info',
			'message'  => 'Application Passwords nejsou na tomto webu dostupné (vypnuto filtrem, nebo starší WordPress).',
			'evidence' => array(),
		);
	}

	if ( ! class_exists( 'WP_Application_Passwords' ) ) {
		return array(
			'state'    => 'unknown',
			'severity' => 'low',
			'message'  => 'Application Passwords hlásí dostupnost, ale GARRY nenašel WP_Application_Passwords API pro ověření počtu.',
			'evidence' => array(),
		);
	}

	$admins     = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID', 'display_name' ) ) );
	$with_count = array();
	$total      = 0;
	foreach ( $admins as $admin ) {
		$passwords = WP_Application_Passwords::get_user_application_passwords( $admin->ID );
		if ( ! empty( $passwords ) ) {
			$with_count[] = sprintf( '%1$s (%2$d)', $admin->display_name, count( $passwords ) );
			$total       += count( $passwords );
		}
	}

	if ( 0 === $total ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Application Passwords jsou dostupné, ale žádný administrátor zatím žádné nemá vytvořené.',
			'evidence' => array( 'total' => 0 ),
		);
	}

	return array(
		'state'    => 'unknown',
		'severity' => 'low',
		'message'  => sprintf(
			'Administrátoři mají celkem %1$d aktivních Application Passwords: %2$s. GARRY nemůže ověřit, jestli jsou všechna stále potřebná – zkontrolujte je v Uživatelé → Profil.',
			$total,
			implode( ', ', $with_count )
		),
		'evidence' => array( 'total' => $total, 'by_admin' => $with_count ),
	);
}

function garry_security_collect_access_hardening() {
	return array(
		'ACC-001' => garry_security_check_admin_last_login(),
		'ACC-002' => garry_security_check_inactive_admins(),
		'ACC-003' => garry_security_check_application_passwords(),
	);
}

function garry_security_access_hardening_catalog_entries() {
	return array(
		'ACC-001' => array( 'title' => 'Poslední přihlášení administrátorů', 'category' => 'Přístup a účty' ),
		'ACC-002' => array( 'title' => 'Neaktivní administrátorské účty', 'category' => 'Přístup a účty' ),
		'ACC-003' => array( 'title' => 'Application Passwords', 'category' => 'Přístup a účty' ),
	);
}
