<?php
/**
 * Plugin Name:       GARRY – Turistické cíle
 * Plugin URI:        https://www.garry.cz
 * Description:       Spravuje místa v okolí (památky, příroda, gastronomie, doprava) a vypisuje je jako filtrovatelné karty. Obsah se vkládá shortcodem grid_turisticke_cile; původně vytvořeno pro GRID Hotel.
 * Version:           1.2.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-turisticke-cile
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GARRY_TC_VER', '1.2.0' );
define( 'GARRY_TC_OPT', 'garry_turisticke_cile' );

/* ============================================================================
 * Jazyk
 * ========================================================================== */
function garry_tc_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language();
		if ( $l ) return $l;
	}
	return substr( (string) get_locale(), 0, 2 );
}
function garry_tc_lang_idx() {
	$l = garry_tc_lang();
	return 'en' === $l ? 1 : ( 'de' === $l ? 2 : 0 );
}
/** Hodnota pole v jazyce stránky s návratem na češtinu, když překlad chybí. */
function garry_tc_pole( array $radek, $zaklad ) {
	$suf = array( 'cz', 'en', 'de' )[ garry_tc_lang_idx() ];
	$v   = trim( (string) ( $radek[ $zaklad . '_' . $suf ] ?? '' ) );
	return '' !== $v ? $v : trim( (string) ( $radek[ $zaklad . '_cz' ] ?? '' ) );
}

/* ============================================================================
 * Ikony štítků — vlastní sada, žádná externí knihovna
 * ========================================================================== */
function garry_tc_ikony() {
	return array(
		'pamatka' => array( 'Památka / stavba', 'M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6' ),
		'strom'   => array( 'Strom / příroda',  'M12 22v-5M12 17c-3.5 0-6-2.4-6-5.5S8.5 6 12 6s6 2.4 6 5.5S15.5 17 12 17Z' ),
		'pribor'  => array( 'Jídlo a pití',     'M6 3v8a2 2 0 0 0 4 0V3M8 11v10M18 3c-1.7 1.2-2.5 3-2.5 5.5S16.3 13 18 13v8' ),
		'vlak'    => array( 'Vlak / doprava',   'M8 19 6 22M16 19l2 3M5 15h14M7 3h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z' ),
		'auto'    => array( 'Auto / parkování', 'M5 16v3M19 16v3M3 12h18M6 12l1.5-4.5A2 2 0 0 1 9.4 6h5.2a2 2 0 0 1 1.9 1.5L18 12v4H6v-4Z' ),
		'hrad'    => array( 'Hrad',             'M4 21V8l3 2 2.5-3L12 10l2.5-3L17 10l3-2v13M4 21h16M10 21v-4h4v4' ),
		'kava'    => array( 'Kavárna / bar',    'M4 8h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V8ZM17 9h2a2 2 0 0 1 0 4h-2M5 22h12' ),
		'voda'    => array( 'Voda / koupání',   'M12 3s6 6.3 6 10a6 6 0 0 1-12 0c0-3.7 6-10 6-10Z' ),
		'mapa'    => array( 'Mapa / vyhlídka',  'M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14' ),
		'letadlo' => array( 'Letiště',          'M10 20h4M12 20v-5M2 12l20-7-7 20-3-7-10-6Z' ),
	);
}
function garry_tc_ikona_svg( $klic ) {
	$ikony = garry_tc_ikony();
	if ( ! isset( $ikony[ $klic ] ) ) return '';
	return '<svg class="tc-ikona" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="'
		. esc_attr( $ikony[ $klic ][1] ) . '"/></svg>';
}

/* ============================================================================
 * Data
 * ========================================================================== */
function garry_tc_defaults() {
	static $d = null;
	if ( null === $d ) {
		$raw = @file_get_contents( plugin_dir_path( __FILE__ ) . 'data-default.json' );
		$d   = $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $d ) ) $d = array();
		$d = wp_parse_args( $d, array( 'stitky' => array(), 'mista' => array() ) );
	}
	return $d;
}

function garry_tc_get() {
	$o = get_option( GARRY_TC_OPT, null );
	if ( ! is_array( $o ) ) $o = array();
	$o = wp_parse_args( $o, array( 'stitky' => array(), 'mista' => array() ) );
	foreach ( array( 'stitky', 'mista' ) as $k ) {
		if ( empty( $o[ $k ] ) ) $o[ $k ] = garry_tc_defaults()[ $k ];
	}
	/* Doplnění klíčů u záznamů uložených starší verzí — konzistentní tvar
	   se hlídá při čtení, ne jednorázovou migrací. */
	foreach ( $o['stitky'] as $i => $s ) {
		$o['stitky'][ $i ] = wp_parse_args( (array) $s, array(
			'key' => '', 'ikona' => 'mapa', 'nazev_cz' => '', 'nazev_en' => '', 'nazev_de' => '',
		) );
	}
	foreach ( $o['mista'] as $i => $m ) {
		$o['mista'][ $i ] = wp_parse_args( (array) $m, array(
			'id' => '', 'stitek' => '', 'vzdalenost' => '', 'odkaz' => '', 'zobrazit' => 1, 'poradi' => 0,
			'nazev_cz' => '', 'nazev_en' => '', 'nazev_de' => '',
			'text_cz'  => '', 'text_en'  => '', 'text_de'  => '',
		) );
	}
	usort( $o['mista'], function ( $a, $b ) { return ( (int) $a['poradi'] ) <=> ( (int) $b['poradi'] ); } );
	return $o;
}

/**
 * Hodnoty přepínače „zobrazit" na jednu na řádek.
 *
 * Vypnutý checkbox se neodesílá, proto před ním stojí skryté pole s nulou.
 * V poli tak přijde 0 pro vypnuté a dvojice 0,1 pro zapnuté — bez přepočtu by
 * se pořadí hodnot rozešlo se zbytkem řádků.
 */
function garry_tc_prepocti_prepinace( array $hodnoty, $pocet_radku ) {
	if ( count( $hodnoty ) === $pocet_radku ) {
		return $hodnoty;
	}
	$out = array();
	for ( $i = 0; $i < count( $hodnoty ); $i++ ) {
		$aktualni = (int) $hodnoty[ $i ];
		if ( 0 === $aktualni && isset( $hodnoty[ $i + 1 ] ) && 1 === (int) $hodnoty[ $i + 1 ] ) {
			$out[] = 1; $i++;                    // dvojice 0,1 = zapnuto
		} else {
			$out[] = $aktualni ? 1 : 0;
		}
	}
	return $out;
}

function garry_tc_sanitize( $in ) {
	$ikony = array_keys( garry_tc_ikony() );
	$out   = array( 'stitky' => array(), 'mista' => array() );

	$s = $in['stitky'] ?? array();
	$n = max( count( $s['key'] ?? array() ), count( $s['nazev_cz'] ?? array() ) );
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $s['key'][ $i ] ?? '' );
		if ( '' === $key ) continue;
		$out['stitky'][] = array(
			'key'      => $key,
			'ikona'    => in_array( $s['ikona'][ $i ] ?? '', $ikony, true ) ? $s['ikona'][ $i ] : 'mapa',
			'nazev_cz' => sanitize_text_field( $s['nazev_cz'][ $i ] ?? '' ),
			'nazev_en' => sanitize_text_field( $s['nazev_en'][ $i ] ?? '' ),
			'nazev_de' => sanitize_text_field( $s['nazev_de'][ $i ] ?? '' ),
		);
	}

	$m = $in['mista'] ?? array();
	/* Pole „zobrazit" chodí jako dvojice hidden+checkbox, aby se vypnutá hodnota
	   vůbec odeslala. Přepočítáme ho na jednu hodnotu na řádek, jinak by se
	   indexy rozešly s ostatními poli. */
	$m['zobrazit'] = garry_tc_prepocti_prepinace( (array) ( $m['zobrazit'] ?? array() ),
		count( $m['nazev_cz'] ?? array() ) );
	$n = max( count( $m['nazev_cz'] ?? array() ), count( $m['id'] ?? array() ) );
	for ( $i = 0; $i < $n; $i++ ) {
		$nazev = sanitize_text_field( $m['nazev_cz'][ $i ] ?? '' );
		if ( '' === $nazev ) continue;
		$id = sanitize_title( $m['id'][ $i ] ?? '' );
		$out['mista'][] = array(
			'id'         => '' !== $id ? $id : sanitize_title( $nazev ),
			'stitek'     => sanitize_key( $m['stitek'][ $i ] ?? '' ),
			'vzdalenost' => sanitize_text_field( $m['vzdalenost'][ $i ] ?? '' ),
			'odkaz'      => esc_url_raw( $m['odkaz'][ $i ] ?? '' ),
			'zobrazit'   => empty( $m['zobrazit'][ $i ] ) ? 0 : 1,
			/* Pořadí = pozice ve formuláři. Ruční číslování odpadá, seznam se
			   řadí přetažením karty nebo šipkami. */
			'poradi'     => ( $i + 1 ) * 10,
			'nazev_cz'   => $nazev,
			'nazev_en'   => sanitize_text_field( $m['nazev_en'][ $i ] ?? '' ),
			'nazev_de'   => sanitize_text_field( $m['nazev_de'][ $i ] ?? '' ),
			'text_cz'    => sanitize_textarea_field( $m['text_cz'][ $i ] ?? '' ),
			'text_en'    => sanitize_textarea_field( $m['text_en'][ $i ] ?? '' ),
			'text_de'    => sanitize_textarea_field( $m['text_de'][ $i ] ?? '' ),
		);
	}
	return $out;
}
add_action( 'admin_init', function () {
	register_setting( 'garry_tc_group', GARRY_TC_OPT, 'garry_tc_sanitize' );
} );

/**
 * Granulární capability na kartu v GRID Nastavení (viz GARRY – GRID Core).
 * Definuje se PŘED bootstrap() — descriptor frameworku ji čte v okamžiku
 * volání. Dřív tu stála konstanta kategorií pokojů, ze kterých byl deskriptor
 * zkopírovaný, takže se přístup k Turistickým cílům řídil cizím oprávněním.
 */
define( 'GARRY_TC_STAFF_CAP', 'garry_grid_manage_turisticke_cile' );

/* Registrace do GARRY menu */
require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\TuristickeCile\V23\bootstrap( __FILE__, 'garry_tc_admin_page', 'Turistické cíle' );

/**
 * register_activation_hook() se spustí jen při skutečném přechodu neaktivní →
 * aktivní, ne při pouhém přepsání souborů pluginu. Bez doplňku na admin_init
 * by administrátor novou capabilitu nikdy nedostal a karta by mu z GRID
 * Nastavení zmizela.
 */
function garry_tc_zajisti_cap() {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_TC_STAFF_CAP ) ) {
		$role->add_cap( GARRY_TC_STAFF_CAP );
	}
}
register_activation_hook( __FILE__, 'garry_tc_zajisti_cap' );
add_action( 'admin_init', 'garry_tc_zajisti_cap' );

/* ============================================================================
 * Administrace
 * ==========================================================================
 * Dvě záložky (Cíle / Štítky). Každý cíl je sbalitelný <details>, pořadí se
 * určuje pozicí v seznamu — přetažením myší nebo šipkami z klávesnice, žádné
 * ruční číslování. Uložené pořadí odpovídá pořadí polí ve formuláři.
 * ========================================================================== */
define( 'GARRY_TC_CAP', 'manage_options' );

function garry_tc_admin_page() {
	if ( ! current_user_can( GARRY_TC_CAP ) ) return;
	$d     = garry_tc_get();
	$O     = GARRY_TC_OPT;
	$ikony = garry_tc_ikony();
	?>
	<div class="garry-tc">
	<h2 class="nav-tab-wrapper" id="tc-tabs">
		<a href="#" class="nav-tab nav-tab-active" data-tab="cile">Cíle</a>
		<a href="#" class="nav-tab" data-tab="stitky">Štítky</a>
	</h2>

	<form method="post" action="options.php">
		<?php settings_fields( 'garry_tc_group' ); ?>

		<!-- ============ CÍLE ============ -->
		<div class="tc-tab" data-tab="cile">
			<p class="description">Pořadí určuje pořadí v tomto seznamu — kartu přetáhněte za úchyt <span aria-hidden="true">⠿</span>, nebo ji posuňte šipkami. Vypnutý cíl se na webu nezobrazí ani se nepočítá do filtru.</p>
			<div id="tc-mista">
			<?php foreach ( $d['mista'] as $m ) :
				$stitek = '';
				foreach ( $d['stitky'] as $s ) { if ( $s['key'] === $m['stitek'] ) { $stitek = $s['nazev_cz'] ?: $s['key']; } }
				?>
				<details class="tc-misto">
					<summary>
						<span class="tc-uchyt" title="Přetažením změníte pořadí" aria-hidden="true">⠿</span>
						<strong class="tc-nazev"><?php echo esc_html( $m['nazev_cz'] ?: 'Nový cíl' ); ?></strong>
						<span class="description tc-souhrn"><?php echo esc_html( trim( $m['vzdalenost'] . ' · ' . $stitek, ' ·' ) ); ?></span>
						<?php if ( empty( $m['zobrazit'] ) ) : ?><span class="tc-skryto">skrytý</span><?php endif; ?>
						<span class="tc-ovladani">
							<button type="button" class="button-link tc-nahoru" aria-label="Posunout výš">↑</button>
							<button type="button" class="button-link tc-dolu" aria-label="Posunout níž">↓</button>
							<button type="button" class="button-link tc-smazat" aria-label="Odebrat cíl">✕</button>
						</span>
					</summary>
					<div class="tc-telo">
						<div class="tc-radek">
							<label>Zobrazit na webu<input type="hidden" name="<?php echo $O; ?>[mista][zobrazit][]" value="0">
								<input type="checkbox" class="tc-zobrazit" name="<?php echo $O; ?>[mista][zobrazit][]" value="1" <?php checked( ! empty( $m['zobrazit'] ) ); ?>></label>
							<label>Štítek<select class="tc-stitek" name="<?php echo $O; ?>[mista][stitek][]">
								<?php foreach ( $d['stitky'] as $s ) : ?>
									<option value="<?php echo esc_attr( $s['key'] ); ?>" <?php selected( $m['stitek'], $s['key'] ); ?>><?php echo esc_html( $s['nazev_cz'] ?: $s['key'] ); ?></option>
								<?php endforeach; ?>
							</select></label>
							<label>Vzdálenost<input type="text" class="tc-vzdalenost" name="<?php echo $O; ?>[mista][vzdalenost][]" value="<?php echo esc_attr( $m['vzdalenost'] ); ?>" style="width:110px" placeholder="7 km"></label>
							<label style="flex:1;min-width:260px">Web lokality<input type="url" name="<?php echo $O; ?>[mista][odkaz][]" value="<?php echo esc_attr( $m['odkaz'] ); ?>" style="width:100%" placeholder="https://…"></label>
							<input type="hidden" name="<?php echo $O; ?>[mista][id][]" value="<?php echo esc_attr( $m['id'] ); ?>">
						</div>
						<div class="tc-radek">
							<label style="flex:1">Název CZ<input type="text" class="tc-nazev-pole" name="<?php echo $O; ?>[mista][nazev_cz][]" value="<?php echo esc_attr( $m['nazev_cz'] ); ?>" style="width:100%"></label>
							<label style="flex:1">Název EN<input type="text" name="<?php echo $O; ?>[mista][nazev_en][]" value="<?php echo esc_attr( $m['nazev_en'] ); ?>" style="width:100%"></label>
							<label style="flex:1">Název DE<input type="text" name="<?php echo $O; ?>[mista][nazev_de][]" value="<?php echo esc_attr( $m['nazev_de'] ); ?>" style="width:100%"></label>
						</div>
						<div class="tc-radek">
							<label style="flex:1">Popis CZ<textarea rows="2" name="<?php echo $O; ?>[mista][text_cz][]" style="width:100%"><?php echo esc_textarea( $m['text_cz'] ); ?></textarea></label>
							<label style="flex:1">Popis EN<textarea rows="2" name="<?php echo $O; ?>[mista][text_en][]" style="width:100%"><?php echo esc_textarea( $m['text_en'] ); ?></textarea></label>
							<label style="flex:1">Popis DE<textarea rows="2" name="<?php echo $O; ?>[mista][text_de][]" style="width:100%"><?php echo esc_textarea( $m['text_de'] ); ?></textarea></label>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
			</div>
			<p><button type="button" class="button" data-tc-pridat="tc-mista">+ Přidat cíl</button></p>
		</div>

		<!-- ============ ŠTÍTKY ============ -->
		<div class="tc-tab" data-tab="stitky" style="display:none">
			<p class="description">Skupiny, podle kterých se cíle filtrují. Klíč se ukládá u jednotlivých cílů — po jeho změně je potřeba cíle přeřadit.</p>
			<table class="widefat striped" id="tc-stitky">
				<thead><tr>
					<th style="width:140px">Klíč</th><th style="width:190px">Ikona</th>
					<th>Název CZ</th><th>Název EN</th><th>Název DE</th><th style="width:40px"></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $d['stitky'] as $s ) : ?>
					<tr>
						<td><input type="text" name="<?php echo $O; ?>[stitky][key][]" value="<?php echo esc_attr( $s['key'] ); ?>" style="width:100%"></td>
						<td><select name="<?php echo $O; ?>[stitky][ikona][]" style="width:100%">
							<?php foreach ( $ikony as $k => $i ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['ikona'], $k ); ?>><?php echo esc_html( $i[0] ); ?></option>
							<?php endforeach; ?>
						</select></td>
						<td><input type="text" name="<?php echo $O; ?>[stitky][nazev_cz][]" value="<?php echo esc_attr( $s['nazev_cz'] ); ?>" style="width:100%"></td>
						<td><input type="text" name="<?php echo $O; ?>[stitky][nazev_en][]" value="<?php echo esc_attr( $s['nazev_en'] ); ?>" style="width:100%"></td>
						<td><input type="text" name="<?php echo $O; ?>[stitky][nazev_de][]" value="<?php echo esc_attr( $s['nazev_de'] ); ?>" style="width:100%"></td>
						<td><button type="button" class="button-link tc-smazat" aria-label="Odebrat štítek">✕</button></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<p><button type="button" class="button" data-tc-pridat="tc-stitky">+ Přidat štítek</button></p>
		</div>

		<p class="description">Na stránku se výpis vloží shortcodem <code>[grid_turisticke_cile]</code>.</p>
		<?php submit_button(); ?>
	</form>
	</div>
	<?php
}

/* Stejná stránka i pod GRID Nastavení — obsah se spravuje tam, kde ho člověk hledá.
   Kartu drží granulární capability, ne manage_options: personál hotelu ji tak
   může dostat samostatně, stejně jako Kategorie pokojů nebo Jídelníček. */
add_action( 'admin_menu', function () {
	add_submenu_page( 'grid-options', 'Turistické cíle', 'Turistické cíle',
		GARRY_TC_STAFF_CAP, 'garry-turisticke-cile-grid', 'garry_tc_admin_page' );
}, 100 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( false === strpos( (string) $hook, 'garry-turisticke-cile' ) ) return;
	wp_enqueue_style( 'garry-tc-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), GARRY_TC_VER );
	wp_enqueue_script( 'garry-tc-admin', plugins_url( 'assets/admin.js', __FILE__ ), array(), GARRY_TC_VER, true );
} );

/* ============================================================================
 * Výpis na webu
 * ========================================================================== */
function garry_tc_render( $atts = array() ) {
	$atts = shortcode_atts( array( 'stitky' => '1' ), $atts, 'grid_turisticke_cile' );
	$d    = garry_tc_get();
	$li   = garry_tc_lang_idx();

	$mista = array_values( array_filter( $d['mista'], function ( $m ) { return ! empty( $m['zobrazit'] ); } ) );
	if ( ! $mista ) {
		return current_user_can( 'manage_options' )
			? '<!-- GARRY – Turistické cíle: žádné zobrazené místo. -->' : '';
	}

	/* Ikona a popisek podle štítku — mapa se staví jednou, ne v každé kartě. */
	$stitky = array();
	foreach ( $d['stitky'] as $s ) { $stitky[ $s['key'] ] = $s; }

	$pocty = array();
	foreach ( $mista as $m ) {
		$k = $m['stitek'];
		$pocty[ $k ] = ( $pocty[ $k ] ?? 0 ) + 1;
	}

	$T = array(
		'vse'   => array( 'Vše', 'All', 'Alle' )[ $li ],
		'filtr' => array( 'Filtr míst v okolí', 'Filter places nearby', 'Orte in der Umgebung filtern' )[ $li ],
		'web'   => array( 'Web lokality', 'Website', 'Website' )[ $li ],
	);

	ob_start();

	if ( '0' !== $atts['stitky'] && count( $pocty ) > 1 ) {
		echo '<div class="okoli-filtr" role="group" aria-label="' . esc_attr( $T['filtr'] ) . '">';
		printf(
			'<button type="button" class="gh-filter" data-filtr="vse" aria-pressed="true">%s <span>%d</span></button>',
			esc_html( $T['vse'] ), count( $mista )
		);
		foreach ( $d['stitky'] as $s ) {
			if ( empty( $pocty[ $s['key'] ] ) ) continue;
			printf(
				'<button type="button" class="gh-filter" data-filtr="%s" aria-pressed="false">%s <span>%d</span></button>',
				esc_attr( $s['key'] ),
				esc_html( garry_tc_pole( $s, 'nazev' ) ?: $s['key'] ),
				(int) $pocty[ $s['key'] ]
			);
		}
		echo '</div>';
	}

	echo '<div class="okoli-grid">';
	foreach ( $mista as $m ) {
		$stitek = $stitky[ $m['stitek'] ] ?? null;
		$nazev  = garry_tc_pole( $m, 'nazev' );
		$popis  = garry_tc_pole( $m, 'text' );

		printf( '<article class="okoli-card" data-typ="%s">', esc_attr( $m['stitek'] ) );
		if ( '' !== trim( (string) $m['vzdalenost'] ) ) {
			echo '<span class="okoli-card__dist">' . esc_html( $m['vzdalenost'] ) . '</span>';
		}
		if ( $stitek ) {
			/* Ikona je dekorace — kategorii nese i tlačítko filtru a datový atribut. */
			echo '<span class="okoli-card__ikona" title="' . esc_attr( garry_tc_pole( $stitek, 'nazev' ) ) . '">'
				. garry_tc_ikona_svg( $stitek['ikona'] ) . '</span>';
		}
		/* h3, ne h4: karty stojí ve stejné rovině jako vedoucí karta nad mřížkou
		   (ta je h3), nejsou jí podřízené. Úroveň navíc rozhoduje o písmu —
		   nadpisové písmo motiv vynucuje jen pro h1–h3, takže h4 tiše padalo
		   na písmo běžného textu a název místa se lišil od ostatních karet. */
		echo '<h3>' . esc_html( $nazev ) . '</h3>';
		if ( '' !== $popis ) echo '<p>' . esc_html( $popis ) . '</p>';
		if ( '' !== trim( (string) $m['odkaz'] ) ) {
			printf(
				'<a class="okoli-card__odkaz" href="%s" target="_blank" rel="noopener">%s <span aria-hidden="true">&nearr;</span></a>',
				esc_url( $m['odkaz'] ),
				esc_html( $T['web'] )
			);
		}
		echo '</article>';
	}
	echo '</div>';

	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_turisticke_cile', 'garry_tc_render' ); }, 5 );

/* Styl karet — načítá se jen tam, kde je výpis. */
add_action( 'wp_enqueue_scripts', function () {
	$post = get_post();
	$je   = $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'grid_turisticke_cile' );
	if ( ! apply_filters( 'garry_tc_nacist_styl', $je, $post ) ) return;
	wp_enqueue_style( 'garry-tc', plugins_url( 'assets/turisticke-cile.css', __FILE__ ), array(), GARRY_TC_VER );
} );
