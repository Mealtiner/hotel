<?php
/**
 * Založí nebo aktualizuje zážitky u okruhu ve třech jazycích, propojí je
 * v Polylangu a naplní ACF pole včetně přepínačů „Zobrazit na homepage"
 * a „Zobrazit na stránce přehledu zážitků". Data připravuje zazitky-build.py.
 */
$SP  = getenv( 'SP' );
$vse = json_decode( (string) file_get_contents( $SP . '/zazitky.json' ), true );
if ( ! $vse ) { WP_CLI::error( 'chybí zazitky.json' ); }

$skupiny = array();
foreach ( $vse as $d ) {
	$stav = get_page_by_path( $d['slug'], OBJECT, 'grid_experience' );
	$data = array(
		'post_type'    => 'grid_experience',
		'post_status'  => 'publish',
		'post_title'   => $d['titul'],
		'post_name'    => $d['slug'],
		'post_content' => '',
		'menu_order'   => (int) $d['poradi'],
		'post_author'  => 1,
	);
	if ( $stav ) {
		$data['ID'] = $stav->ID;
		wp_update_post( wp_slash( $data ) );
		$id = (int) $stav->ID;
		$co = 'aktualizovan';
	} else {
		$id = wp_insert_post( wp_slash( $data ) );
		if ( is_wp_error( $id ) ) { WP_CLI::warning( $id->get_error_message() ); continue; }
		$co = 'vytvoren  ';
	}

	$pole = array(
		'num' => $d['num'], 'text' => $d['text'], 'cta' => $d['cta'],
		'odkaz' => $d['odkaz'], 'odkaz_text' => $d['odkaz_text'],
		'perex' => $d['perex'], 'parametry' => $d['parametry'], 'popis' => $d['popis'],
		'doporuceno' => (int) $d['doporuceno'], 'v_prehledu' => (int) $d['v_prehledu'],
		'prime' => 0,
	);
	foreach ( $pole as $k => $v ) {
		if ( function_exists( 'update_field' ) ) { update_field( $k, $v, $id ); }
		else { update_post_meta( $id, $k, $v ); }
	}
	update_post_meta( $id, '_yoast_wpseo_title', $d['seo_titul'] );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $d['seo_popis'] );

	if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, $d['lang'] ); }
	$skupiny[ $d['skupina'] ][ $d['lang'] ] = $id;
	WP_CLI::log( sprintf( '  %s %-4s %-24s #%d', $co, $d['lang'], $d['slug'], $id ) );
}

foreach ( $skupiny as $skupina => $ids ) {
	if ( count( $ids ) === 3 && function_exists( 'pll_save_post_translations' ) ) {
		pll_save_post_translations( $ids );
	}
}
WP_CLI::success( 'zážitky hotové: ' . count( $skupiny ) );
