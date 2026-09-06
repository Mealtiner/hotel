<?php
/**
 * Vzhled bubliny — platí pro celý web, spravuje administrátor v GARRY nastavení.
 * Obsah jednotlivých stránek je v includes/stranky.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function gbub_vzhled_defaults() {
	return array(
		'aktivni'          => 1,

		/* --- rozměry --- */
		'sirka'            => 'clamp(320px,34vw,560px)',
		'sirka_mobil'      => 'min(92vw,430px)',
		'podil_fotky'      => 46,      // % plochy pro fotku u šablon s fotkou
		'zaobleni'         => 6,       // px
		'odsazeni'         => 28,      // px vnitřní odsazení textové části

		/* --- barvy --- */
		'barva_pozadi'     => '#16181B',
		'barva_nadpisu'    => '#FFFFFF',
		'barva_textu'      => '#D8D6D4',
		'barva_ramecku'    => 'rgba(255,255,255,.12)',
		'barva_krizku'     => '#F4F2F0',
		'barva_krizku_hover' => '#FF5A50',
		'barva_stinu'      => 'rgba(0,0,0,.45)',

		/* --- typografie --- */
		'velikost_nadpisu' => 'clamp(20px,1.8vw,28px)',
		'velikost_nadpisu_mobil' => '19px',
		'velikost_textu'   => 'clamp(14px,1vw,16px)',
		'velikost_textu_mobil' => '14px',

		/* --- pozice --- */
		'pozice'           => 'stred',  // stred | vpravo-dole | vlevo-dole | vpravo-nahore | vlevo-nahore
		'odstup'           => 26,       // px od okraje u rohových pozic
		'odstup_mobil'     => 14,

		/* --- chování --- */
		'zpozdeni'         => 900,      // ms
		'auto_zavrit'      => 0,        // ms, 0 = nezavírat samo
		'cetnost'          => 'sezeni', // vzdy | sezeni | dny
		'cetnost_dny'      => 7,
		'animace'          => 'zdola',  // zdola | prolnuti | zvetseni
		'preklryt'         => 1,        // ztmavit pozadí stránky
		'barva_prekryvu'   => 'rgba(8,9,11,.55)',
	);
}

function gbub_vzhled() {
	$ulozene = get_option( GBUB_VZHLED, array() );
	if ( ! is_array( $ulozene ) ) $ulozene = array();
	return array_merge( gbub_vzhled_defaults(), $ulozene );
}

add_action( 'admin_init', function () {
	register_setting( 'gbub_vzhled_group', GBUB_VZHLED, array(
		'type'              => 'array',
		'sanitize_callback' => 'gbub_vzhled_sanitize',
		'default'           => gbub_vzhled_defaults(),
	) );
} );
add_filter( 'option_page_capability_gbub_vzhled_group', function () { return 'manage_options'; } );

/** Whitelist podle výchozích hodnot — co plugin nezná, do volby nepropadne. */
function gbub_vzhled_sanitize( $vstup ) {
	$vychozi = gbub_vzhled_defaults();
	$vstup   = is_array( $vstup ) ? $vstup : array();
	$ven     = array();

	$prepinace = array( 'aktivni', 'preklryt' );
	$vycty = array(
		'pozice'   => array( 'stred', 'vpravo-dole', 'vlevo-dole', 'vpravo-nahore', 'vlevo-nahore' ),
		'cetnost'  => array( 'vzdy', 'sezeni', 'dny' ),
		'animace'  => array( 'zdola', 'prolnuti', 'zvetseni' ),
	);
	$rozsahy = array(
		'podil_fotky' => array( 20, 75 ), 'zaobleni' => array( 0, 40 ),
		'odsazeni' => array( 10, 70 ), 'odstup' => array( 0, 120 ),
		'odstup_mobil' => array( 0, 80 ), 'zpozdeni' => array( 0, 20000 ),
		'auto_zavrit' => array( 0, 120000 ), 'cetnost_dny' => array( 1, 365 ),
	);

	foreach ( $vychozi as $klic => $vych ) {
		if ( in_array( $klic, $prepinace, true ) ) { $ven[ $klic ] = empty( $vstup[ $klic ] ) ? 0 : 1; continue; }
		$h = $vstup[ $klic ] ?? $vych;
		if ( isset( $vycty[ $klic ] ) ) {
			$ven[ $klic ] = in_array( $h, $vycty[ $klic ], true ) ? $h : $vych;
		} elseif ( isset( $rozsahy[ $klic ] ) ) {
			$ven[ $klic ] = max( $rozsahy[ $klic ][0], min( $rozsahy[ $klic ][1], (int) $h ) );
		} elseif ( strpos( $klic, 'barva' ) === 0 ) {
			$ven[ $klic ] = gbub_sanitize_barvu( (string) $h, $vych );
		} else {
			/* Rozměry přijímáme jako CSS délku včetně clamp()/min() — validujeme
			   tvarem, ne výčtem, jinak by šlo do stylu propašovat cokoli. */
			$ven[ $klic ] = gbub_sanitize_delku( (string) $h, $vych );
		}
	}
	return $ven;
}

function gbub_sanitize_barvu( $h, $vychozi ) {
	$h = trim( $h );
	if ( $h === '' ) return '';
	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $h ) ) return $h;
	if ( preg_match( '/^rgba?\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*(,\s*[\d.]+\s*)?\)$/i', $h ) ) return $h;
	return $vychozi;
}

/** Povolí jen čísla, jednotky a funkce clamp/min/max/calc — nic jiného. */
function gbub_sanitize_delku( $h, $vychozi ) {
	$h = trim( $h );
	if ( $h === '' ) return $vychozi;
	if ( ! preg_match( '/^[0-9a-z%.,()\/\s+*-]+$/i', $h ) ) return $vychozi;
	if ( preg_match( '/[;{}<>"\']|url\s*\(|expression|@import/i', $h ) ) return $vychozi;
	if ( ! preg_match( '/^(clamp|min|max|calc)?\s*\(?[\d.]/i', $h ) ) return $vychozi;
	return $h;
}

/* ============================================================================
 * Administrace vzhledu
 * ============================================================================ */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( (string) $hook, 'garry-informacni-bublina' ) === false ) return;
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_media();
	wp_enqueue_style( 'gbub-admin', GBUB_URL . 'assets/admin.css', array(), GBUB_VERSION );
	wp_enqueue_script( 'gbub-admin', GBUB_URL . 'assets/admin.js',
		array( 'wp-color-picker', 'jquery' ), GBUB_VERSION, true );
} );

function gbub_pole( $n, $klic, $popis = '', $typ = 'text', $extra = '' ) {
	printf( '<input type="%s" name="%s[%s]" value="%s" class="regular-text" %s>%s',
		esc_attr( $typ ), esc_attr( GBUB_VZHLED ), esc_attr( $klic ),
		esc_attr( (string) $n[ $klic ] ), $extra, // phpcs:ignore -- literál z volání níže
		$popis ? '<p class="description">' . esc_html( $popis ) . '</p>' : '' );
}
function gbub_barva( $n, $klic ) {
	printf( '<input type="text" name="%s[%s]" value="%s" class="gbub-barva" data-default-color="%s">',
		esc_attr( GBUB_VZHLED ), esc_attr( $klic ), esc_attr( (string) $n[ $klic ] ),
		esc_attr( (string) gbub_vzhled_defaults()[ $klic ] ) );
}
function gbub_prepinac( $n, $klic, $popisek ) {
	printf( '<label><input type="checkbox" name="%s[%s]" value="1" %s> %s</label>',
		esc_attr( GBUB_VZHLED ), esc_attr( $klic ),
		checked( ! empty( $n[ $klic ] ), true, false ), esc_html( $popisek ) );
}
function gbub_vyber( $n, $klic, array $volby ) {
	printf( '<select name="%s[%s]">', esc_attr( GBUB_VZHLED ), esc_attr( $klic ) );
	foreach ( $volby as $h => $p ) {
		printf( '<option value="%s" %s>%s</option>', esc_attr( $h ),
			selected( (string) $n[ $klic ], (string) $h, false ), esc_html( $p ) );
	}
	echo '</select>';
}

function gbub_admin_vzhled() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$n = gbub_vzhled();
	?>
	<div class="wrap gbub-wrap">
		<h1>Informační bublina — vzhled</h1>
		<p class="description" style="max-width:860px">
			Tady se nastavuje, <strong>jak</strong> bublina vypadá — platí pro celý web.
			<strong>Co</strong> se na které stránce zobrazí (šablona, texty ve třech jazycích,
			fotka) se zadává v <strong>GRID Nastavení → Informační bublina</strong>.
		</p>

		<form method="post" action="options.php" class="gbub-form">
			<?php settings_fields( 'gbub_vzhled_group' ); ?>
			<div class="gbub-sloupce">
			<div class="gbub-hlavni">

			<h2 class="title">Zapnutí</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Bublina</th><td>
					<?php gbub_prepinac( $n, 'aktivni', 'Zobrazovat bublinu na webu' ); ?>
					<p class="description">Hlavní vypínač. I když je zapnutý, bublina se objeví jen na stránkách, které mají v GRID Nastavení vyplněný obsah.</p>
				</td></tr>
			</table>

			<h2 class="title">Rozměry</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Šířka</th><td><?php gbub_pole( $n, 'sirka', 'CSS délka, může být clamp() nebo min().' ); ?></td></tr>
				<tr><th scope="row">Šířka na mobilu</th><td><?php gbub_pole( $n, 'sirka_mobil' ); ?></td></tr>
				<tr><th scope="row">Podíl fotky</th><td>
					<?php gbub_pole( $n, 'podil_fotky', 'V procentech plochy bubliny u šablon s fotkou. Zbytek zabere text.', 'number', 'min="20" max="75"' ); ?>
				</td></tr>
				<tr><th scope="row">Zaoblení rohů</th><td><?php gbub_pole( $n, 'zaobleni', 'V pixelech.', 'number', 'min="0" max="40"' ); ?></td></tr>
				<tr><th scope="row">Vnitřní odsazení</th><td><?php gbub_pole( $n, 'odsazeni', 'V pixelech, kolem textu.', 'number', 'min="10" max="70"' ); ?></td></tr>
			</table>

			<h2 class="title">Barvy</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Pozadí</th><td><?php gbub_barva( $n, 'barva_pozadi' ); ?></td></tr>
				<tr><th scope="row">Nadpis</th><td><?php gbub_barva( $n, 'barva_nadpisu' ); ?></td></tr>
				<tr><th scope="row">Text</th><td><?php gbub_barva( $n, 'barva_textu' ); ?></td></tr>
				<tr><th scope="row">Rámeček</th><td><?php gbub_barva( $n, 'barva_ramecku' ); ?></td></tr>
				<tr><th scope="row">Stín</th><td><?php gbub_barva( $n, 'barva_stinu' ); ?></td></tr>
				<tr><th scope="row">Křížek</th><td>
					<label class="gbub-vedle">základní <?php gbub_barva( $n, 'barva_krizku' ); ?></label>
					<label class="gbub-vedle">při najetí <?php gbub_barva( $n, 'barva_krizku_hover' ); ?></label>
				</td></tr>
			</table>

			<h2 class="title">Písmo</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Nadpis</th><td>
					<?php gbub_pole( $n, 'velikost_nadpisu' ); ?>
					<p><?php gbub_pole( $n, 'velikost_nadpisu_mobil', 'Druhé pole platí pro mobil.' ); ?></p>
				</td></tr>
				<tr><th scope="row">Text</th><td>
					<?php gbub_pole( $n, 'velikost_textu' ); ?>
					<p><?php gbub_pole( $n, 'velikost_textu_mobil', 'Druhé pole platí pro mobil.' ); ?></p>
				</td></tr>
			</table>

			<h2 class="title">Umístění</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Pozice</th><td>
					<?php gbub_vyber( $n, 'pozice', array(
						'stred'          => 'Uprostřed obrazovky',
						'vpravo-dole'    => 'Vpravo dole',
						'vlevo-dole'     => 'Vlevo dole',
						'vpravo-nahore'  => 'Vpravo nahoře',
						'vlevo-nahore'   => 'Vlevo nahoře',
					) ); ?>
				</td></tr>
				<tr><th scope="row">Odstup od okraje</th><td>
					<?php gbub_pole( $n, 'odstup', 'V pixelech, platí u rohových pozic.', 'number', 'min="0" max="120"' ); ?>
					<p><?php gbub_pole( $n, 'odstup_mobil', 'Odstup na mobilu.', 'number', 'min="0" max="80"' ); ?></p>
				</td></tr>
				<tr><th scope="row">Ztmavit pozadí</th><td>
					<?php gbub_prepinac( $n, 'preklryt', 'Ztmavit stránku pod bublinou' ); ?>
					<p><?php gbub_barva( $n, 'barva_prekryvu' ); ?></p>
				</td></tr>
			</table>

			<h2 class="title">Chování</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Zpoždění</th><td><?php gbub_pole( $n, 'zpozdeni', 'V milisekundách od načtení stránky.', 'number', 'min="0" max="20000" step="100"' ); ?></td></tr>
				<tr><th scope="row">Zavřít samo</th><td><?php gbub_pole( $n, 'auto_zavrit', 'V milisekundách, 0 = nezavírat samo.', 'number', 'min="0" max="120000" step="500"' ); ?></td></tr>
				<tr><th scope="row">Jak často</th><td>
					<?php gbub_vyber( $n, 'cetnost', array(
						'vzdy'   => 'Při každém načtení stránky',
						'sezeni' => 'Jednou za návštěvu',
						'dny'    => 'Jednou za N dní',
					) ); ?>
					<p><?php gbub_pole( $n, 'cetnost_dny', 'Počet dní pro volbu „jednou za N dní".', 'number', 'min="1" max="365"' ); ?></p>
					<p class="description">Zavření si pamatuje prohlížeč návštěvníka, nic se neukládá na server.</p>
				</td></tr>
				<tr><th scope="row">Animace</th><td>
					<?php gbub_vyber( $n, 'animace', array(
						'zdola'     => 'Příjezd zdola',
						'prolnuti'  => 'Prolnutí',
						'zvetseni'  => 'Zvětšení',
					) ); ?>
					<p class="description">Návštěvníkovi s vypnutými animacemi (prefers-reduced-motion) se bublina zobrazí rovnou.</p>
				</td></tr>
			</table>

			<?php submit_button(); ?>
			</div>

			<div class="gbub-nahled-sloupec">
				<h2 class="title">Náhled</h2>
				<p>
					<label>Šablona
						<select id="gbub-nahled-sablona">
							<?php foreach ( gbub_sablony() as $klic => $s ) : ?>
								<option value="<?php echo esc_attr( $klic ); ?>"><?php echo esc_html( $s['nazev'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</p>
				<div class="gbub-nahled" id="gbub-nahled">
					<div class="gbub-nahled-ram">
						<div class="gbub-nahled-foto"><span>FOTO</span></div>
						<div class="gbub-nahled-text">
							<p class="gbub-nahled-nadpis">Nadpis bubliny</p>
							<p class="gbub-nahled-telo">Krátký text oznámení, který se na stránce návštěvníkovi ukáže.</p>
						</div>
						<span class="gbub-nahled-krizek">&times;</span>
					</div>
				</div>
				<p class="description">Náhled je orientační — ukazuje rozvržení, barvy a proporce podle nastavení vlevo.</p>
			</div>
			</div>
		</form>
	</div>
	<?php
}
