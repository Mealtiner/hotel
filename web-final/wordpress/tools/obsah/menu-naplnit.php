<?php
/**
 * Naplnění hlavních menu (CZ/EN/DE) dvouúrovňovou strukturou.
 *
 * Odkazy na kategorie pokojů a na jídelníčky provozů se berou z pluginů, ne
 * natvrdo — když personál přidá kategorii nebo provoz, stačí skript spustit
 * znovu a menu se srovná.
 */
$LANG = array( 'cs' => 'grid-hlavni', 'en' => 'grid-hlavni___en', 'de' => 'grid-hlavni___de' );
$PREFIX = array( 'cs' => '', 'en' => '/en', 'de' => '/de' );

/** Adresa stránky podle slugu a jazyka. */
function menu_url( $slug, $lang ) {
	$p = get_page_by_path( $slug );
	if ( ! $p ) return '';
	$id = $p->ID;
	if ( $lang !== 'cs' && function_exists( 'pll_get_post' ) ) {
		$t = pll_get_post( $p->ID, $lang );
		if ( $t ) $id = $t;
	}
	return (string) get_permalink( $id );
}

/* --- kategorie pokojů z pluginu --- */
function menu_pokoje( $lang ) {
	if ( ! function_exists( 'garry_pok_get' ) ) return array();
	$suf = $lang === 'cs' ? 'cz' : $lang;
	$ven = array();
	foreach ( garry_pok_get()['rooms'] as $r ) {
		if ( empty( $r['key'] ) ) continue;
		$slug = $r['key'] . ( $lang === 'cs' ? '' : '-' . $lang );
		$t = get_term_by( 'slug', $slug, 'grid_room_cat' );
		if ( ! $t || is_wp_error( $t ) ) continue;
		$odkaz = get_term_link( $t );
		if ( is_wp_error( $odkaz ) ) continue;
		$nazev = $r[ 'nazev_' . $suf ] ?: $r['nazev_cz'];
		$ven[] = array( 'title' => $nazev, 'url' => $odkaz );
	}
	return $ven;
}

/* --- provozy s jídelníčkem z pluginu --- */
function menu_provozy( $lang ) {
	if ( ! function_exists( 'garry_menu_venue_options' ) ) return array();
	$zaklad = menu_url( 'gastronomie', $lang );
	$slovo = array( 'cs' => 'MENU', 'en' => 'MENU', 'de' => 'MENU' )[ $lang ];
	$ven = array();
	foreach ( garry_menu_venue_options() as $slug => $nazev ) {
		$v = function_exists( 'garry_menu_get_venue' ) ? garry_menu_get_venue( $slug ) : array();
		$en = (array) ( $v['enabled'] ?? array() );
		/* Do menu jen provozy, které opravdu nějaký jídelníček zobrazují. */
		if ( ! array_filter( $en ) ) continue;
		$ven[] = array( 'title' => $nazev . ' · ' . $slovo, 'url' => $zaklad . '#jidelnicek-' . $slug );
	}
	return $ven;
}

function menu_struktura( $lang ) {
	$p = array( 'cs' => '', 'en' => '/en', 'de' => '/de' )[ $lang ];
	$T = array(
		'cs' => array( 'grid'=>'GRID', 'pribeh'=>'Příběh hotelu', 'proc'=>'Proč GRID', 'okruh'=>'Masarykův okruh', 'okoli'=>'Okolí hotelu',
			'pokoje'=>'Pokoje', 'prehled'=>'Přehled ubytování', 'srovnani'=>'Srovnání pokojů',
			'zazitky'=>'Zážitky', 'vse_zazitky'=>'Všechny zážitky', 'poukazy'=>'Dárkové poukazy',
			'gastro'=>'Gastronomie', 'gastro_vse'=>'GRID Gastronomie',
			'sezona'=>'Sezóna', 'akce'=>'Nadcházející akce', 'cekaci'=>'Rezervace & čekací list',
			'firemni'=>'Firemní akce & svatby', 'nabidka'=>'Nabídka hotelu', 'kontakt'=>'Kontakt' ),
		'en' => array( 'grid'=>'GRID', 'pribeh'=>'Our story', 'proc'=>'Why GRID', 'okruh'=>'The Masaryk Circuit', 'okoli'=>'The area',
			'pokoje'=>'Rooms', 'prehled'=>'Accommodation overview', 'srovnani'=>'Room comparison',
			'zazitky'=>'Experiences', 'vse_zazitky'=>'All experiences', 'poukazy'=>'Gift vouchers',
			'gastro'=>'Dining', 'gastro_vse'=>'GRID Dining',
			'sezona'=>'Season', 'akce'=>'Upcoming events', 'cekaci'=>'Booking & waiting list',
			'firemni'=>'Corporate events & weddings', 'nabidka'=>'What we offer', 'kontakt'=>'Contact' ),
		'de' => array( 'grid'=>'GRID', 'pribeh'=>'Unsere Geschichte', 'proc'=>'Warum GRID', 'okruh'=>'Der Masaryk-Ring', 'okoli'=>'Die Umgebung',
			'pokoje'=>'Zimmer', 'prehled'=>'Übersicht der Unterkunft', 'srovnani'=>'Zimmervergleich',
			'zazitky'=>'Erlebnisse', 'vse_zazitky'=>'Alle Erlebnisse', 'poukazy'=>'Geschenkgutscheine',
			'gastro'=>'Gastronomie', 'gastro_vse'=>'GRID Gastronomie',
			'sezona'=>'Saison', 'akce'=>'Kommende Events', 'cekaci'=>'Buchung & Warteliste',
			'firemni'=>'Firmenevents & Hochzeiten', 'nabidka'=>'Unser Angebot', 'kontakt'=>'Kontakt' ),
	)[ $lang ];

	$okruh  = menu_url( $lang === 'cs' ? 'masarykuv-okruh' : ( $lang === 'en' ? 'masaryk-circuit' : 'masaryk-ring' ), $lang );
	$onas   = menu_url( $lang === 'cs' ? 'o-nas' : ( $lang === 'en' ? 'about-the-hotel' : 'ueber-uns' ), $lang );
	$ubyt   = menu_url( $lang === 'cs' ? 'ubytovani' : ( $lang === 'en' ? 'accommodation' : 'unterkunft' ), $lang );
	$zaz    = menu_url( $lang === 'cs' ? 'zazitky' : ( $lang === 'en' ? 'experiences' : 'erlebnisse' ), $lang );
	$gastro = menu_url( 'gastronomie', $lang );
	$sez    = menu_url( $lang === 'cs' ? 'sezona' : ( $lang === 'en' ? 'season' : 'saison' ), $lang );
	$firm   = menu_url( $lang === 'cs' ? 'firemni-akce-svatby' : ( $lang === 'en' ? 'corporate-events-weddings' : 'firmenevents-hochzeiten' ), $lang );
	$kont   = menu_url( $lang === 'cs' ? 'kontakt' : ( $lang === 'en' ? 'contact' : 'kontakt-de' ), $lang );

	return array(
		array( 'title' => $T['grid'], 'url' => $p . '/#pribeh', 'deti' => array(
			array( 'title' => $T['pribeh'], 'url' => $onas ),
			array( 'title' => $T['proc'],   'url' => $onas . '#proc-grid' ),
			array( 'title' => $T['okruh'],  'url' => $okruh ),
			array( 'title' => $T['okoli'],  'url' => $onas . '#okoli' ),
		) ),
		array( 'title' => $T['pokoje'], 'url' => $p . '/#pokoje', 'deti' => array_merge( array(
			array( 'title' => $T['prehled'],  'url' => $ubyt ),
			array( 'title' => $T['srovnani'], 'url' => $ubyt . '#srovnani-pokoju' ),
		), menu_pokoje( $lang ) ) ),
		array( 'title' => $T['zazitky'], 'url' => $p . '/#zazitky', 'deti' => array(
			array( 'title' => $T['vse_zazitky'], 'url' => $zaz ),
			array( 'title' => $T['poukazy'],     'url' => $zaz . '#poukazy' ),
		) ),
		array( 'title' => $T['gastro'], 'url' => $p . '/#restaurace', 'deti' => array_merge( array(
			array( 'title' => $T['gastro_vse'], 'url' => $gastro ),
		), menu_provozy( $lang ) ) ),
		array( 'title' => $T['sezona'], 'url' => $p . '/#sezona', 'deti' => array(
			array( 'title' => $T['akce'],   'url' => $sez ),
			array( 'title' => $T['cekaci'], 'url' => $sez . '#cekaci-list' ),
		) ),
		array( 'title' => $T['firemni'], 'url' => $p . '/#firemni', 'deti' => array(
			array( 'title' => $T['nabidka'], 'url' => $firm ),
		) ),
		array( 'title' => $T['kontakt'], 'url' => $kont, 'deti' => array() ),
	);
}

foreach ( $LANG as $lang => $lokace ) {
	$lokace_mapa = get_nav_menu_locations();
	$menu_id = $lokace_mapa[ $lokace ] ?? 0;
	if ( ! $menu_id ) { WP_CLI::warning( "menu pro $lang nenalezeno" ); continue; }

	/* Staré položky pryč — struktura se přestavuje celá, ne po kusech. */
	foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $i ) wp_delete_post( $i->ID, true );

	$poradi = 0;
	foreach ( menu_struktura( $lang ) as $polozka ) {
		$rodic = wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'  => $polozka['title'],
			'menu-item-url'    => $polozka['url'],
			'menu-item-status' => 'publish',
			'menu-item-position' => ++$poradi,
		) );
		if ( is_wp_error( $rodic ) ) continue;
		foreach ( $polozka['deti'] as $dite ) {
			if ( empty( $dite['url'] ) ) continue;
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => $dite['title'],
				'menu-item-url'    => $dite['url'],
				'menu-item-parent-id' => $rodic,
				'menu-item-status' => 'publish',
				'menu-item-position' => ++$poradi,
			) );
		}
		WP_CLI::log( sprintf( '  %s  %-28s %-46s (%d podpoložek)', strtoupper( $lang ), $polozka['title'], $polozka['url'], count( $polozka['deti'] ) ) );
	}
}
WP_CLI::success( 'menu naplnena' );
