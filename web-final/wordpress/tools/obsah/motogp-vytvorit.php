<?php
/**
 * Vytvoří (nebo aktualizuje) zážitek MotoGP — Grand Prix České republiky ve třech
 * jazycích, propojí je v Polylangu a nastaví ACF pole včetně příznaku „prime".
 * Texty připravuje motogp-obsah.py → motogp-<lang>.json.
 */
$SP = getenv( 'SP' );
$ids = array();

foreach ( array( 'cs', 'en', 'de' ) as $lang ) {
	$d = json_decode( (string) file_get_contents( $SP . '/motogp-' . $lang . '.json' ), true );
	if ( ! $d ) { WP_CLI::warning( "chybi data pro $lang" ); continue; }

	$stav = get_page_by_path( $d['slug'], OBJECT, 'grid_experience' );
	$data = array(
		'post_type'    => 'grid_experience',
		'post_status'  => 'publish',
		'post_title'   => $d['titul'],
		'post_name'    => $d['slug'],
		'post_content' => '',
		'menu_order'   => 0,
		'post_author'  => 1,
	);
	if ( $stav ) {
		$data['ID'] = $stav->ID;
		wp_update_post( wp_slash( $data ) );
		$id = $stav->ID;
		WP_CLI::log( "  $lang: aktualizovan zazitek #$id ({$d['slug']})" );
	} else {
		$id = wp_insert_post( wp_slash( $data ) );
		if ( is_wp_error( $id ) ) { WP_CLI::warning( $id->get_error_message() ); continue; }
		WP_CLI::log( "  $lang: vytvoren zazitek #$id ({$d['slug']})" );
	}

	$pole = array(
		'num'        => $d['num'],
		'text'       => $d['text'],
		'cta'        => $d['cta'],
		'odkaz'      => $d['odkaz'],
		'odkaz_text' => $d['odkaz_text'],
		'perex'      => $d['perex'],
		'parametry'  => $d['parametry'],
		'popis'      => $d['popis'],
		'doporuceno' => 1,
		'prime'      => 1,
	);
	foreach ( $pole as $k => $v ) {
		if ( function_exists( 'update_field' ) ) update_field( $k, $v, $id );
		else update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, '_yoast_wpseo_title', $d['seo_titul'] );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $d['seo_popis'] );

	if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $id, $lang );
	$ids[ $lang ] = (int) $id;
}

if ( count( $ids ) === 3 && function_exists( 'pll_save_post_translations' ) ) {
	pll_save_post_translations( $ids );
	WP_CLI::log( '  propojeni Polylang: ' . json_encode( $ids ) );
}
WP_CLI::success( 'hotovo' );
