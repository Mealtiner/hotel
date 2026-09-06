<?php
/**
 * Vytvoří stránku Masarykův okruh ve třech jazycích a propojí je v Polylangu.
 * Obsah se načítá ze souborů, které připravil build-okruh.py.
 */
$SP = getenv( 'SP' );
$def = array(
	'cs' => array( 'Masarykův okruh',     'masarykuv-okruh' ),
	'en' => array( 'The Masaryk Circuit', 'masaryk-circuit' ),
	'de' => array( 'Der Masaryk-Ring',    'masaryk-ring' ),
);
$mapa = array( 'cs' => 'cz', 'en' => 'en', 'de' => 'de' );
$ids = array();

foreach ( $def as $lang => $d ) {
	list( $titul, $slug ) = $d;
	$obsah = file_get_contents( $SP . '/okruh-' . $mapa[ $lang ] . '.txt' );
	if ( $obsah === false ) { WP_CLI::warning( "chybi obsah pro $lang" ); continue; }

	$stav = get_page_by_path( $slug );
	if ( $stav ) {
		wp_update_post( wp_slash( array( 'ID' => $stav->ID, 'post_title' => $titul, 'post_content' => $obsah ) ) );
		$id = $stav->ID;
		WP_CLI::log( "  $lang: aktualizovana stranka #$id ($slug)" );
	} else {
		$id = wp_insert_post( wp_slash( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $titul,
			'post_name'    => $slug,
			'post_content' => $obsah,
			'post_author'  => 1,
		) ) );
		if ( is_wp_error( $id ) ) { WP_CLI::warning( $id->get_error_message() ); continue; }
		WP_CLI::log( "  $lang: vytvorena stranka #$id ($slug)" );
	}
	if ( function_exists( 'pll_set_post_language' ) ) pll_set_post_language( $id, $lang );
	$ids[ $lang ] = (int) $id;
}

if ( count( $ids ) === 3 && function_exists( 'pll_save_post_translations' ) ) {
	pll_save_post_translations( $ids );
	WP_CLI::log( '  propojeni Polylang: ' . json_encode( $ids ) );
}
WP_CLI::success( 'hotovo' );
