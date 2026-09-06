<?php
/**
 * Týdenní import závodů z kalendáře Automotodromu Brno.
 *
 * Zdroj: https://www.automotodrombrno.cz/kalendar-akci/zavody/ — filtrovaný výpis,
 * který obsahuje POUZE oficiální závody. Pronájmy, trackdays a testování se tam
 * nedostanou, takže se nemusí nic dodatečně odfiltrovávat.
 *
 * Nalezené akce se zapíšou jako NEPUBLIKOVANÉ s příznakem „nová akce". Na web se
 * dostanou až poté, co je někdo v administraci doplní a publikuje — zdroj neumí
 * němčinu vůbec a angličtinu jen na detailech akcí, takže automaticky vloženou
 * akci nelze rovnou zveřejnit ve všech jazycích.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GARRY_SEZ_ZDROJ_CZ', 'https://www.automotodrombrno.cz/kalendar-akci/zavody/' );
define( 'GARRY_SEZ_ZDROJ_HOST', 'https://www.automotodrombrno.cz' );
define( 'GARRY_SEZ_IMPORT_HOOK', 'garry_sez_tydenni_import' );

/* ============================================================================
 * Plánování
 * ============================================================================ */
/** Periody, ze kterých jde v administraci vybírat. */
function garry_sez_periody() {
	return array(
		'daily'      => array( 'popisek' => 'Každý den',      'interval' => DAY_IN_SECONDS ),
		'twicedaily' => array( 'popisek' => 'Dvakrát denně',  'interval' => 12 * HOUR_IN_SECONDS ),
		'weekly'     => array( 'popisek' => 'Jednou týdně',   'interval' => WEEK_IN_SECONDS ),
		'fortnightly'=> array( 'popisek' => 'Jednou za 14 dní', 'interval' => 2 * WEEK_IN_SECONDS ),
		'monthly'    => array( 'popisek' => 'Jednou měsíčně', 'interval' => 30 * DAY_IN_SECONDS ),
	);
}

function garry_sez_perioda() {
	$n = garry_sez_get();
	$p = (string) ( $n['import_perioda'] ?? 'weekly' );
	return array_key_exists( $p, garry_sez_periody() ) ? $p : 'weekly';
}

/**
 * Naplánuje import na zvolenou periodu.
 *
 * Když už úloha běží na jiné periodě, musí se nejdřív zrušit — wp_schedule_event()
 * existující plán nepřepíše a změna nastavení by se nikdy neprojevila.
 */
function garry_sez_naplanuj_import() {
	$perioda = garry_sez_perioda();
	$dalsi = wp_next_scheduled( GARRY_SEZ_IMPORT_HOOK );

	if ( $dalsi ) {
		$soucasna = wp_get_schedule( GARRY_SEZ_IMPORT_HOOK );
		if ( $soucasna === $perioda ) return;
		garry_sez_zrus_import();
	}

	/* Vždycky ve 3:20 ráno MÍSTNÍHO času — v provozu hotelu nejklidnější okno.
	   PHP běží pod WordPressem v UTC, takže strtotime() by naplánoval 3:20 UTC,
	   tedy 5:20 v Praze. Čas proto skládáme ve zvoleném pásmu webu. */
	$kdy = in_array( $perioda, array( 'daily', 'twicedaily' ), true ) ? 'tomorrow' : 'next sunday';
	try {
		$start = new DateTime( $kdy . ' 03:20', wp_timezone() );
		$start = $start->getTimestamp();
	} catch ( Exception $e ) {
		$start = time() + HOUR_IN_SECONDS;
	}
	wp_schedule_event( $start, $perioda, GARRY_SEZ_IMPORT_HOOK );
}
function garry_sez_zrus_import() {
	$dalsi = wp_next_scheduled( GARRY_SEZ_IMPORT_HOOK );
	if ( $dalsi ) wp_unschedule_event( $dalsi, GARRY_SEZ_IMPORT_HOOK );
}

/* WordPress sám zná jen hourly/twicedaily/daily — delší intervaly doplníme. */
add_filter( 'cron_schedules', function ( $s ) {
	foreach ( garry_sez_periody() as $klic => $p ) {
		if ( ! isset( $s[ $klic ] ) ) {
			$s[ $klic ] = array( 'interval' => $p['interval'], 'display' => $p['popisek'] );
		}
	}
	return $s;
} );

add_action( 'admin_init', function () {
	$n = garry_sez_get();
	if ( ! empty( $n['import_aktivni'] ) ) garry_sez_naplanuj_import();
	else garry_sez_zrus_import();
} );

add_action( GARRY_SEZ_IMPORT_HOOK, 'garry_sez_spust_import' );

/* ============================================================================
 * Stažení a rozbor zdroje
 * ============================================================================ */

/**
 * Zdroj stojí za Cloudflarem, který odmítá požadavky bez hlavičky prohlížeče
 * (vrací 403). Posíláme proto běžnou hlavičku a k tomu kontakt na provozovatele,
 * aby bylo z logu zdroje poznat, kdo se ptá.
 */
function garry_sez_stahni( $url ) {
	$odpoved = wp_remote_get( $url, array(
		'timeout'     => 20,
		'redirection' => 3,
		'user-agent'  => 'Mozilla/5.0 (compatible; GRIDHotelBot/1.0; +' . home_url( '/' ) . ')',
		'headers'     => array( 'Accept' => 'text/html,application/json' ),
	) );
	if ( is_wp_error( $odpoved ) ) return $odpoved;
	$kod = (int) wp_remote_retrieve_response_code( $odpoved );
	if ( $kod !== 200 ) return new WP_Error( 'garry_sez_http', 'Zdroj odpověděl HTTP ' . $kod );
	return (string) wp_remote_retrieve_body( $odpoved );
}

/**
 * Vytáhne závody z výpisu kalendáře.
 *
 * Značkování zdroje je ruční HTML bez mikrodat, takže rozbor stojí na třídách
 * events-list__item / title-heading / event-detail. Kdyby je redakce zdroje
 * změnila, vrátí se prázdné pole a import jen zaloguje, že nic nenašel —
 * radši nic než rozsypaná data.
 */
function garry_sez_rozeber( $html ) {
	$ven = array();
	$bloky = preg_split( '/<div class="events-list__item/', $html );
	array_shift( $bloky );

	foreach ( $bloky as $b ) {
		if ( ! preg_match( '/title-heading"><span>(.*?)<\/span>/s', $b, $mn ) ) continue;
		if ( ! preg_match( '/<a href="([^"]+)"\s+class="uri/', $b, $mu ) ) continue;

		$nazev = trim( html_entity_decode( wp_strip_all_tags( $mn[1] ), ENT_QUOTES, 'UTF-8' ) );
		$url   = esc_url_raw( $mu[1] );
		if ( $nazev === '' || $url === '' ) continue;

		$od = $do = '';
		if ( preg_match( '/<div class="event-detail">(.*?)<\/div>/s', $b, $md )
			&& preg_match( '/(\d{1,2})\.\s?(\d{1,2})\.\s?[-–]\s?(\d{1,2})\.\s?(\d{1,2})\.\s?(\d{4})/', $md[1], $mr ) ) {
			$od = sprintf( '%04d-%02d-%02d', $mr[5], $mr[2], $mr[1] );
			$do = sprintf( '%04d-%02d-%02d', $mr[5], $mr[4], $mr[3] );
		}
		if ( $od === '' ) continue; // bez data by akce v přehledu neměla kam patřit

		$ven[] = array( 'nazev' => $nazev, 'url' => $url, 'od' => $od, 'do' => $do );
	}
	return $ven;
}

/** Slug akce z její adresy — klíč pro dohledání popisu i anglické mutace. */
function garry_sez_slug_z_url( $url ) {
	$cesta = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$casti = explode( '/', $cesta );
	return sanitize_title( end( $casti ) );
}

/** Český popis akce z REST API zdroje. Bez něj se akce založí jen bez textu. */
function garry_sez_popis_ze_zdroje( $slug ) {
	$telo = garry_sez_stahni( GARRY_SEZ_ZDROJ_HOST . '/wp-json/wp/v2/posts?_fields=excerpt&slug=' . rawurlencode( $slug ) );
	if ( is_wp_error( $telo ) ) return '';
	$data = json_decode( $telo, true );
	if ( ! is_array( $data ) || empty( $data[0]['excerpt']['rendered'] ) ) return '';
	$text = html_entity_decode( wp_strip_all_tags( $data[0]['excerpt']['rendered'] ), ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Anglická adresa akce. Zdroj má anglickou mutaci jen na detailech akcí, ne na
 * výpisu kalendáře, a ne u všech. Existenci proto ověřujeme a ukládáme jen
 * adresu, která opravdu odpovídá — mrtvý odkaz je horší než žádný.
 */
function garry_sez_url_en( $slug ) {
	$url = GARRY_SEZ_ZDROJ_HOST . '/en/' . $slug . '/';
	$odpoved = wp_remote_head( $url, array(
		'timeout'     => 12,
		'redirection' => 0,
		'user-agent'  => 'Mozilla/5.0 (compatible; GRIDHotelBot/1.0; +' . home_url( '/' ) . ')',
	) );
	if ( is_wp_error( $odpoved ) ) return '';
	return (int) wp_remote_retrieve_response_code( $odpoved ) === 200 ? $url : '';
}

/* ============================================================================
 * Vlastní import
 * ============================================================================ */

/**
 * @param bool $rucne Spuštění tlačítkem v administraci (jinak cron).
 * @return array{pridano:int,preskoceno:int,chyba:string}
 */
function garry_sez_spust_import( $rucne = false ) {
	$vysledek = array( 'pridano' => 0, 'preskoceno' => 0, 'chyba' => '' );

	$html = garry_sez_stahni( GARRY_SEZ_ZDROJ_CZ );
	if ( is_wp_error( $html ) ) {
		$vysledek['chyba'] = $html->get_error_message();
		garry_sez_zapis_stav( $vysledek, $rucne );
		return $vysledek;
	}

	$nalezene = garry_sez_rozeber( $html );
	if ( ! $nalezene ) {
		$vysledek['chyba'] = 'Ve zdroji se nepodařilo najít žádnou akci — pravděpodobně se změnilo značkování stránky.';
		garry_sez_zapis_stav( $vysledek, $rucne );
		return $vysledek;
	}

	$o = garry_sez_get();
	$akce = is_array( $o['events'] ) ? $o['events'] : array();

	/* Shodu hledáme podle adresy, ne podle názvu — název se na zdroji mění
	   (ročník v závorce, zkrácení), adresa zůstává. */
	$zname = array();
	foreach ( $akce as $a ) {
		if ( ! empty( $a['url'] ) ) $zname[ untrailingslashit( $a['url'] ) ] = true;
	}

	$nove = array();
	foreach ( $nalezene as $n ) {
		if ( isset( $zname[ untrailingslashit( $n['url'] ) ] ) ) { $vysledek['preskoceno']++; continue; }
		$slug = garry_sez_slug_z_url( $n['url'] );
		$nove[] = array(
			'od' => $n['od'], 'do' => $n['do'],
			'cz' => $n['nazev'], 'en' => '', 'de' => '',
			'pcz' => '', 'pen' => '', 'pde' => '',
			'dcz' => garry_sez_popis_ze_zdroje( $slug ), 'den' => '', 'dde' => '',
			'stav' => 'volne',
			'url' => $n['url'],
			'url_en' => garry_sez_url_en( $slug ),
			'publikovano' => 0,
			'nova' => 1,
			'zdroj' => 'automotodrom',
			'nacteno' => current_time( 'mysql' ),
		);
		$vysledek['pridano']++;
	}

	if ( $nove ) {
		$akce = array_merge( $akce, $nove );
		usort( $akce, function ( $x, $y ) { return strcmp( $x['od'] ?? '', $y['od'] ?? '' ); } );
		$o['events'] = $akce;
		update_option( GARRY_SEZ_OPT, $o, false );
	}

	garry_sez_zapis_stav( $vysledek, $rucne );
	return $vysledek;
}

function garry_sez_zapis_stav( array $vysledek, $rucne ) {
	$o = garry_sez_get();
	$o['import_posledni'] = current_time( 'mysql' );
	$o['import_stav'] = $vysledek['chyba'] !== ''
		? 'Chyba: ' . $vysledek['chyba']
		: sprintf( 'Přidáno %d, beze změny %d.', $vysledek['pridano'], $vysledek['preskoceno'] );
	$o['import_rucne'] = $rucne ? 1 : 0;
	update_option( GARRY_SEZ_OPT, $o, false );

	if ( function_exists( 'garry_sez_log_add' ) ) {
		garry_sez_log_add( array(
			'typ'  => 'import',
			'text' => ( $rucne ? 'Ruční import: ' : 'Týdenní import: ' ) . $o['import_stav'],
		) );
	}
}

/* ============================================================================
 * Ruční spuštění z administrace
 * ============================================================================ */
add_action( 'admin_post_garry_sez_import_ted', function () {
	if ( ! current_user_can( GARRY_SEZ_STAFF_CAP ) || ! check_admin_referer( 'garry_sez_import_ted' ) ) {
		wp_die( 'Nedostatečná oprávnění.' );
	}
	$v = garry_sez_spust_import( true );
	wp_safe_redirect( add_query_arg( array(
		'page' => sanitize_key( $_REQUEST['back'] ?? 'garry-sezona-cekaci-list' ),
		'tab'  => 'akce',
		'import' => $v['chyba'] !== '' ? 'chyba' : $v['pridano'],
	), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ============================================================================
 * Upozornění na nástěnce
 * ============================================================================ */

/** Akce čekající na doplnění a publikaci. */
function garry_sez_nove_akce() {
	$ven = array();
	foreach ( garry_sez_get()['events'] as $i => $e ) {
		if ( ! empty( $e['nova'] ) ) $ven[ $i ] = $e;
	}
	return $ven;
}

/** Které jazykové části akci ještě chybí — podle toho se pole zvýrazní. */
function garry_sez_chybi( array $e ) {
	$chybi = array();
	foreach ( array( 'en' => 'Název EN', 'de' => 'Název DE', 'pcz' => 'Perex CZ',
		'pen' => 'Perex EN', 'pde' => 'Perex DE', 'dcz' => 'O akci CZ',
		'den' => 'O akci EN', 'dde' => 'O akci DE' ) as $klic => $popisek ) {
		if ( trim( (string) ( $e[ $klic ] ?? '' ) ) === '' ) $chybi[ $klic ] = $popisek;
	}
	return $chybi;
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( GARRY_SEZ_STAFF_CAP ) ) return;
	$nove = garry_sez_nove_akce();
	if ( ! $nove ) return;

	/* Na vlastní stránce pluginu je upozornění zbytečné — seznam je hned pod ním. */
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && strpos( (string) $screen->id, 'garry-sezona' ) !== false ) return;

	$odkaz = admin_url( 'admin.php?page=garry-sezona-grid&tab=akce' );
	$pocet = count( $nove );
	$slovo = $pocet === 1 ? 'nová akce' : ( $pocet < 5 ? 'nové akce' : 'nových akcí' );
	printf(
		'<div class="notice notice-warning"><p><strong>Sezóna &amp; čekací list:</strong> '
		. 'z kalendáře Automotodromu %s %d %s, %s na doplnění překladů a publikaci. '
		. '<a href="%s">Otevřít přehled akcí</a></p></div>',
		$pocet === 1 ? 'přibyla' : 'přibyly',
		$pocet, esc_html( $slovo ),
		$pocet === 1 ? 'která čeká' : 'které čekají',
		esc_url( $odkaz )
	);
} );
