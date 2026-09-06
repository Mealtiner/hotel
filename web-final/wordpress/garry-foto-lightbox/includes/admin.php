<?php
/**
 * Administrace pluginu GARRY – Foto lightbox.
 * Ukládá se přes Settings API do jediné volby GFLB_OPTION.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', function () {
	register_setting( 'gflb_group', GFLB_OPTION, array(
		'type'              => 'array',
		'sanitize_callback' => 'gflb_sanitize',
		'default'           => gflb_defaults(),
	) );
	register_setting( 'gflb_group', GFLB_STRANKY, array(
		'type'              => 'array',
		'sanitize_callback' => 'gflb_sanitize_stranky',
		'default'           => array(),
	) );
} );

add_filter( 'option_page_capability_gflb_group', function () { return 'manage_options'; } );

/**
 * Sanitizace tabulky stránek.
 *
 * Klíče i názvy pocházejí ze zápisu na frontendu, ne z formuláře — z odeslaných
 * dat proto bereme jen zaškrtávátka, a jen pro klíče, které v uložené tabulce
 * opravdu jsou. Formulář tak nemůže do seznamu přidat cizí položku.
 */
function gflb_sanitize_stranky( $vstup ) {
	$ulozene = gflb_stranky();
	$vstup   = is_array( $vstup ) ? $vstup : array();
	$ven     = array();
	foreach ( $ulozene as $klic => $radek ) {
		$novy = array(
			'nazev'  => sanitize_text_field( (string) ( $radek['nazev'] ?? '' ) ),
			'url'    => esc_url_raw( (string) ( $radek['url'] ?? '' ) ),
			'videno' => (int) ( $radek['videno'] ?? 0 ),
		);
		// Řádek se objeví v odeslaných datech jen tehdy, když byl formulář
		// opravdu vykreslený — jinak by uložení smazalo dosud neviděné stránky.
		if ( isset( $vstup['__odeslano'] ) && $vstup['__odeslano'] === '1' ) {
			foreach ( array_keys( gflb_prepinatelne() ) as $funkce ) {
				$novy[ $funkce ] = ! empty( $vstup[ $klic ][ $funkce ] ) ? 1 : 0;
			}
			if ( empty( $vstup[ $klic ]['__vlastni'] ) ) {
				// Bez zapnutého vlastního nastavení se řádek chová podle globálu.
				foreach ( array_keys( gflb_prepinatelne() ) as $funkce ) unset( $novy[ $funkce ] );
			}
		} else {
			foreach ( array_keys( gflb_prepinatelne() ) as $funkce ) {
				if ( array_key_exists( $funkce, $radek ) ) $novy[ $funkce ] = (int) $radek[ $funkce ];
			}
		}
		$ven[ $klic ] = $novy;
	}
	return $ven;
}

/**
 * Sanitizace. Whitelist klíčů z gflb_defaults() — cokoli navíc se zahodí,
 * takže do volby nemůže propadnout nic, co plugin nezná.
 */
function gflb_sanitize( $vstup ) {
	$vychozi = gflb_defaults();
	$ven     = array();
	$vstup   = is_array( $vstup ) ? $vstup : array();

	$prepinace = array( 'logo_zobrazit', 'nadpis_zobrazit', 'popisek_zobrazit', 'ukazatel_zobrazit',
		'ukazatel_cisla', 'ukazatel_auto', 'nahledy_zobrazit', 'sipky_zobrazit', 'dalsi_zobrazit',
		'smycka', 'klavesnice', 'gesta', 'kolecko', 'predlozit', 'hash', 'autoplay', 'aktivni' );
	$vycty = array(
		'pozadi_typ'       => array( 'solid', 'linear', 'radial', 'conic', 'rohy' ),
		'logo_zdroj'       => array( 'web', 'priloha', 'url' ),
		'logo_pozice'      => array( 'vlevo-nahore', 'vpravo-nahore', 'vlevo-dole', 'vpravo-dole' ),
		'nadpis_zarovnani' => array( 'left', 'center', 'right' ),
		'popisek_zdroj'    => array( 'caption', 'description', 'alt', 'title' ),
		'sipky_styl'       => array( 'sipka', 'kruh', 'ctverec' ),
	);
	$rozsahy = array(
		'pozadi_uhel' => array( 0, 360 ), 'pozadi_kryti' => array( 0, 100 ), 'pozadi_zlom' => array( 5, 95 ),
		'pozadi_rozostreni' => array( 0, 40 ), 'logo_vyska' => array( 10, 200 ),
		'logo_kryti' => array( 0, 100 ), 'nadpis_velikost' => array( 8, 40 ),
		'popisek_velikost' => array( 8, 40 ), 'ukazatel_max_bodu' => array( 2, 200 ),
		'nahledy_vyska' => array( 28, 160 ), 'nahledy_kryti' => array( 0, 100 ),
		'nahledy_mezera' => array( 0, 40 ),
		'sipky_velikost' => array( 20, 120 ), 'autoplay_ms' => array( 1000, 60000 ),
		'logo_priloha' => array( 0, PHP_INT_MAX ),
	);

	foreach ( $vychozi as $klic => $vych ) {
		if ( in_array( $klic, $prepinace, true ) ) {
			$ven[ $klic ] = empty( $vstup[ $klic ] ) ? 0 : 1;
			continue;
		}
		$hodnota = $vstup[ $klic ] ?? $vych;
		if ( isset( $vycty[ $klic ] ) ) {
			$ven[ $klic ] = in_array( $hodnota, $vycty[ $klic ], true ) ? $hodnota : $vych;
		} elseif ( isset( $rozsahy[ $klic ] ) ) {
			$ven[ $klic ] = max( $rozsahy[ $klic ][0], min( $rozsahy[ $klic ][1], (int) $hodnota ) );
		} elseif ( $klic === 'logo_url' ) {
			$ven[ $klic ] = esc_url_raw( (string) $hodnota );
		} elseif ( $klic === 'selektory' ) {
			// CSS selektory: povolena jen běžná selektorová abeceda, žádné závorky ani uvozovky navíc.
			$ven[ $klic ] = trim( preg_replace( '/[^A-Za-z0-9\s\.\#\-\_\[\]\=\"\'\:\(\)\,\>\+\~\*]/', '', (string) $hodnota ) );
		} elseif ( strpos( $klic, 'barva' ) !== false || strpos( $klic, 'roh_' ) !== false ) {
			$ven[ $klic ] = gflb_sanitize_barvu( (string) $hodnota, $vych );
		} else {
			$ven[ $klic ] = sanitize_text_field( (string) $hodnota );
		}
	}
	return $ven;
}

/** Přijme hex i rgb()/rgba(); cokoli jiného spadne na výchozí hodnotu. */
function gflb_sanitize_barvu( $hodnota, $vychozi ) {
	$hodnota = trim( $hodnota );
	if ( $hodnota === '' ) return '';
	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $hodnota ) ) return $hodnota;
	if ( preg_match( '/^rgba?\(\s*[\d\.]+\s*,\s*[\d\.]+\s*,\s*[\d\.]+\s*(,\s*[\d\.]+\s*)?\)$/i', $hodnota ) ) return $hodnota;
	return $vychozi;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( (string) $hook, 'garry-foto-lightbox' ) === false ) return;
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_media();
	wp_enqueue_script( 'gflb-admin', GFLB_URL . 'assets/admin.js',
		array( 'wp-color-picker', 'jquery' ), GFLB_VERSION, true );
	wp_enqueue_style( 'gflb-admin', GFLB_URL . 'assets/admin.css', array(), GFLB_VERSION );
} );

/** Pomocníci pro formulář — drží šablonu čitelnou. */
function gflb_pole_text( $n, $klic, $popis = '', $typ = 'text', $extra = '' ) {
	printf(
		'<input type="%s" name="%s[%s]" value="%s" class="regular-text" %s>%s',
		esc_attr( $typ ), esc_attr( GFLB_OPTION ), esc_attr( $klic ),
		esc_attr( (string) $n[ $klic ] ), $extra, // phpcs:ignore -- $extra je literál z volání níže
		$popis ? '<p class="description">' . esc_html( $popis ) . '</p>' : ''
	);
}
function gflb_pole_barva( $n, $klic ) {
	printf(
		'<input type="text" name="%s[%s]" value="%s" class="gflb-barva" data-default-color="%s">',
		esc_attr( GFLB_OPTION ), esc_attr( $klic ), esc_attr( (string) $n[ $klic ] ),
		esc_attr( (string) gflb_defaults()[ $klic ] )
	);
}
function gflb_pole_prepinac( $n, $klic, $popisek ) {
	printf(
		'<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>',
		esc_attr( GFLB_OPTION ), esc_attr( $klic ),
		checked( ! empty( $n[ $klic ] ), true, false ), esc_html( $popisek )
	);
}
function gflb_pole_vyber( $n, $klic, array $volby ) {
	printf( '<select name="%s[%s]">', esc_attr( GFLB_OPTION ), esc_attr( $klic ) );
	foreach ( $volby as $hodnota => $popisek ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $hodnota ),
			selected( (string) $n[ $klic ], (string) $hodnota, false ), esc_html( $popisek ) );
	}
	echo '</select>';
}
