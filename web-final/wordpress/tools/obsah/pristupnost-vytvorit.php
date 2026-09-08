<?php
/**
 * Založí stránku „Prohlášení o přístupnosti" ve třech jazycích a propojí je
 * v Polylangu. Obsah připravuje pristupnost-build.py (stejná struktura jako
 * ostatní právní stránky: jedna Divi sekce s textovým modulem).
 */
$SP  = getenv( 'SP' );
$ids = array();

foreach ( array( 'cs', 'en', 'de' ) as $lang ) {
	$d = json_decode( (string) file_get_contents( $SP . '/pristupnost-' . $lang . '.json' ), true );
	if ( ! $d ) { WP_CLI::warning( "chybí data pro $lang" ); continue; }

	$stav = get_page_by_path( $d['slug'] );
	$data = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $d['titul'],
		'post_name'    => $d['slug'],
		'post_content' => $d['obsah'],
		'post_author'  => 1,
	);
	if ( $stav ) {
		$data['ID'] = $stav->ID;
		wp_update_post( wp_slash( $data ) );
		$id = (int) $stav->ID;
		WP_CLI::log( "  $lang: aktualizována stránka #$id ({$d['slug']})" );
	} else {
		$id = wp_insert_post( wp_slash( $data ) );
		if ( is_wp_error( $id ) ) { WP_CLI::warning( $id->get_error_message() ); continue; }
		WP_CLI::log( "  $lang: vytvořena stránka #$id ({$d['slug']})" );
	}

	update_post_meta( $id, '_yoast_wpseo_title', $d['seo_titul'] );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $d['seo_popis'] );
	/* Divi musí vědět, že obsah je blokový layout, jinak stránku nevykreslí. */
	update_post_meta( $id, '_et_pb_use_builder', 'on' );
	update_post_meta( $id, '_et_pb_built_for_post_type', 'page' );

	if ( function_exists( 'pll_set_post_language' ) ) { pll_set_post_language( $id, $lang ); }
	$ids[ $lang ] = $id;
}

if ( count( $ids ) === 3 && function_exists( 'pll_save_post_translations' ) ) {
	pll_save_post_translations( $ids );
	WP_CLI::log( '  propojení Polylang: ' . wp_json_encode( $ids ) );
}
WP_CLI::success( 'hotovo' );
