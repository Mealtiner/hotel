<?php
/**
 * Přerovná pořadí fotek u kategorií pokojů tak, aby první (náhledová) fotka
 * ukazovala pokoj, ne koupelnu, a aby se karty Apartmá a Apartmá Superior
 * vizuálně lišily — klient dodal pro obě kategorie stejnou sadu snímků.
 * Klíčem je jméno souboru přílohy, ne ID, aby skript šel spustit znovu.
 */
$poradi = array(
	'standard'           => array('dvouluzkovy-pokoj-standard-01','dvouluzkovy-pokoj-standard-02','dvouluzkovy-pokoj-standard-03','dvouluzkovy-pokoj-standard-04'),
	'superior'           => array('dvouluzkovy-pokoj-superior-01','dvouluzkovy-pokoj-superior-03','dvouluzkovy-pokoj-superior-02','dvouluzkovy-pokoj-superior-04'),
	// 02 = pokoj s otevřenými dveřmi na terasu, 01 a 06 jsou koupelny
	'superior-s-terasou' => array('dvouluzkovy-pokoj-superior-terasa-02','dvouluzkovy-pokoj-superior-terasa-03','dvouluzkovy-pokoj-superior-terasa-05','dvouluzkovy-pokoj-superior-terasa-01','dvouluzkovy-pokoj-superior-terasa-04','dvouluzkovy-pokoj-superior-terasa-06'),
	'apartma'            => array('apartma-01','apartma-04','apartma-06','apartma-02','apartma-03','apartma-05'),
	// jiný úvodní snímek než u Apartmá, ať se karty vedle sebe neopakují
	'apartma-superior'   => array('apartma-superior-06','apartma-superior-04','apartma-superior-01','apartma-superior-02','apartma-superior-03','apartma-superior-05'),
);

/* Hledáme podle názvu souboru (_wp_attached_file), ne podle post_name — ten se
   odvozuje z titulku přílohy, který je u všech fotek jedné kategorie stejný. */
$id_podle_jmena = function ( $jmeno ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_attached_file'
		 WHERE p.post_type = 'attachment' AND m.meta_value LIKE %s
		 ORDER BY p.ID LIMIT 1",
		'%/' . $wpdb->esc_like( $jmeno ) . '.jpg' ) );
};

$s = get_option( 'garry_pokoje' );
foreach ( $poradi as $klic => $jmena ) {
	$ids = array();
	foreach ( $jmena as $j ) {
		$id = $id_podle_jmena( $j );
		if ( $id ) { $ids[] = $id; } else { WP_CLI::warning( "priloha nenalezena: $j" ); }
	}
	if ( ! $ids ) continue;

	foreach ( array( 'cs', 'en', 'de' ) as $lang ) {
		$slug = $klic . ( $lang === 'cs' ? '' : '-' . $lang );
		$t = get_term_by( 'slug', $slug, 'grid_room_cat' );
		if ( ! $t || is_wp_error( $t ) ) continue;
		update_term_meta( $t->term_id, 'nahled', $ids[0] );
		update_term_meta( $t->term_id, 'galerie', array_slice( $ids, 1 ) );
	}
	foreach ( $s['rooms'] as &$r ) {
		if ( $r['key'] === $klic ) {
			$u = wp_get_attachment_image_url( $ids[0], 'full' );
			if ( $u ) $r['img'] = wp_make_link_relative( $u );
		}
	}
	unset( $r );
	WP_CLI::log( sprintf( '  %-20s nahled=%s (%s), galerie=%d',
		$klic, $ids[0], basename( (string) wp_get_attachment_url( $ids[0] ) ), count( $ids ) - 1 ) );
}
update_option( 'garry_pokoje', $s, false );
WP_CLI::success( 'poradi fotek upraveno' );
