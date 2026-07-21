<?php
/**
 * wp eval-file wp-lang-deploy.php <job.json>
 * Vytvoří/aktualizuje jazykové mutace stránek a prováže je přes Polylang.
 */
if ( ! function_exists( 'pll_set_post_language' ) ) { echo "Polylang API chybí!\n"; exit( 1 ); }

$job = json_decode( file_get_contents( $args[0] ), true );
if ( ! $job ) { echo "Nelze načíst job: {$args[0]}\n"; exit( 1 ); }

$divi_meta = array(
	'_et_pb_use_builder'   => 'on',
	'_et_pb_page_layout'   => 'et_no_sidebar',
	'_et_pb_side_nav'      => 'off',
	'_et_pb_post_hide_nav' => 'default',
);

foreach ( $job['pages'] as $p ) {
	$content = file_get_contents( $p['content_file'] );
	if ( $content === false ) { echo "CHYBA čtení: {$p['content_file']}\n"; continue; }

	$existing = pll_get_post( $p['cz_id'], $p['lang'] );
	if ( $existing ) {
		wp_update_post( array( 'ID' => $existing, 'post_title' => $p['title'], 'post_name' => $p['slug'], 'post_content' => $content ) );
		$id = $existing; $akce = 'aktualizováno';
	} else {
		$id = wp_insert_post( array(
			'post_type' => 'page', 'post_status' => 'publish',
			'post_title' => $p['title'], 'post_name' => $p['slug'], 'post_content' => $content,
		) );
		if ( is_wp_error( $id ) || ! $id ) { echo "CHYBA insertu {$p['slug']}\n"; continue; }
		pll_set_post_language( $id, $p['lang'] );
		$tr = pll_get_post_translations( $p['cz_id'] );
		$tr['cs'] = $p['cz_id']; $tr[ $p['lang'] ] = $id;
		pll_save_post_translations( $tr );
		$akce = 'vytvořeno';
	}
	foreach ( $divi_meta as $k => $v ) update_post_meta( $id, $k, $v );
	echo "{$p['lang']}  {$p['slug']}  → #$id ($akce)\n";
}

/* Theme Builder hlavička/patička — vícejazyčný obsah */
foreach ( ( $job['tb'] ?? array() ) as $tb ) {
	$content = file_get_contents( $tb['content_file'] );
	wp_update_post( array( 'ID' => $tb['id'], 'post_content' => $content ) );
	echo "TB #{$tb['id']} aktualizován ({$tb['label']})\n";
}

/* kontrola front page překladů */
$front = (int) get_option( 'page_on_front' );
foreach ( array( 'en', 'de' ) as $l ) {
	$t = pll_get_post( $front, $l );
	echo "front page $l: " . ( $t ? "#$t" : 'CHYBÍ' ) . "\n";
}
