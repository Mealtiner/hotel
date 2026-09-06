<?php
/**
 * Zpětné doplnění uplynulých závodů roku 2026 do pluginu Sezóna.
 *
 * Kalendář Automotodromu vypisuje jen nadcházející akce, uplynulé už v něm
 * nejsou. Termíny proto pocházejí z detailů akcí, názvy a popisy z REST API
 * zdroje — tedy ze stejných míst, ze kterých čerpá týdenní import.
 *
 * Akce se zakládají jako publikované a bez příznaku „nová": na web se stejně
 * nedostanou (jsou v minulosti) a v upozornění na nástěnce by jen překážely.
 */

$doplnit = array(
	array( 'slug' => 'mezinarodni-mistrovstvi-zavodu-automobilu-do-vrchu-2026', 'od' => '2026-04-11', 'do' => '2026-04-12' ),
	array( 'slug' => 'jarni-cena-brna2026',                                     'od' => '2026-04-24', 'do' => '2026-04-26' ),
	array( 'slug' => 'histo-cup-2026',                                          'od' => '2026-05-08', 'do' => '2026-05-10' ),
	array( 'slug' => 'euro-moto-mezinarodni-sampionat-silnicnich-motocyklu-2026','od' => '2026-05-29', 'do' => '2026-05-31' ),
	array( 'slug' => 'alpe-adria-international-motorcycle-championship-2026',    'od' => '2026-07-10', 'do' => '2026-07-12' ),
);

$o = garry_sez_get();
$zname = array();
foreach ( $o['events'] as $e ) {
	if ( ! empty( $e['url'] ) ) $zname[ untrailingslashit( $e['url'] ) ] = true;
}

$pridano = 0;
foreach ( $doplnit as $d ) {
	$url = 'https://www.automotodrombrno.cz/' . $d['slug'] . '/';
	if ( isset( $zname[ untrailingslashit( $url ) ] ) ) {
		WP_CLI::log( sprintf( '  %-46s uz existuje, preskoceno', substr( $d['slug'], 0, 44 ) ) );
		continue;
	}

	/* Název bereme z REST — v kalendáři už akce není, odkud ho jinak vzít. */
	$telo = garry_sez_stahni( GARRY_SEZ_ZDROJ_HOST . '/wp-json/wp/v2/posts?_fields=title&slug=' . rawurlencode( $d['slug'] ) );
	$nazev = '';
	if ( ! is_wp_error( $telo ) ) {
		$data = json_decode( $telo, true );
		if ( ! empty( $data[0]['title']['rendered'] ) ) {
			$nazev = trim( html_entity_decode( wp_strip_all_tags( $data[0]['title']['rendered'] ), ENT_QUOTES, 'UTF-8' ) );
		}
	}
	if ( $nazev === '' ) { WP_CLI::warning( '  nazev se nepodarilo nacist: ' . $d['slug'] ); continue; }

	$o['events'][] = array(
		'od' => $d['od'], 'do' => $d['do'],
		'cz' => $nazev, 'en' => '', 'de' => '',
		'pcz' => '', 'pen' => '', 'pde' => '',
		'dcz' => garry_sez_popis_ze_zdroje( $d['slug'] ), 'den' => '', 'dde' => '',
		'stav' => 'volne',
		'url' => $url,
		'url_en' => garry_sez_url_en( $d['slug'] ),
		'publikovano' => 1,
		'nova' => 0,
		'zdroj' => 'automotodrom-historie',
		'nacteno' => current_time( 'mysql' ),
	);
	$pridano++;
	WP_CLI::log( sprintf( '  %-46s %s .. %s', substr( $nazev, 0, 44 ), $d['od'], $d['do'] ) );
}

if ( $pridano ) {
	usort( $o['events'], function ( $a, $b ) { return strcmp( $a['od'] ?? '', $b['od'] ?? '' ); } );
	update_option( GARRY_SEZ_OPT, $o, false );
}
WP_CLI::success( 'doplneno ' . $pridano );
