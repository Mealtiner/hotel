<?php
/**
 * Obsah bubliny pro jednotlivé stránky — spravuje personál v GRID Nastavení.
 *
 * Jeden záznam platí pro stránku i všechny její jazykové mutace. Klíčem je proto
 * ID stránky v základním jazyce (české), ne ID konkrétní mutace: kdyby se klíčem
 * stalo ID mutace, musel by se stejný text zadávat třikrát a rozešel by se.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function gbub_jazyky() {
	return array( 'cz' => 'Česky', 'en' => 'English', 'de' => 'Deutsch' );
}

/** Kód jazyka pro Polylang podle našeho sufixu. */
function gbub_pll_kod( $suf ) {
	return $suf === 'cz' ? 'cs' : $suf;
}

/** Sufix jazyka pro aktuální požadavek. */
function gbub_suffix() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language();
		if ( $l === 'en' ) return 'en';
		if ( $l === 'de' ) return 'de';
	}
	return 'cz';
}

function gbub_stranky() {
	$v = get_option( GBUB_STRANKY, array() );
	return is_array( $v ) ? $v : array();
}

/**
 * ID stránky v základním jazyce. Bez Polylangu je to prostě ID samotné stránky.
 */
function gbub_zakladni_id( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id ) return 0;
	if ( function_exists( 'pll_get_post' ) ) {
		$zaklad = pll_get_post( $post_id, 'cs' );
		if ( $zaklad ) return (int) $zaklad;
	}
	return $post_id;
}

/** Záznam pro aktuálně zobrazenou stránku, nebo null. */
function gbub_zaznam_pro_stranku() {
	if ( is_admin() || ! is_singular() ) return null;
	$id = gbub_zakladni_id( get_queried_object_id() );
	if ( ! $id ) return null;
	$vse = gbub_stranky();
	$z = $vse[ $id ] ?? null;
	if ( ! $z || empty( $z['aktivni'] ) ) return null;
	return $z;
}

/* ============================================================================
 * Uložení
 * ============================================================================ */
add_action( 'admin_init', function () {
	register_setting( 'gbub_stranky_group', GBUB_STRANKY, array(
		'type'              => 'array',
		'sanitize_callback' => 'gbub_stranky_sanitize',
		'default'           => array(),
	) );
} );
/* Personál (Editor) ukládá přes options.php — bez tohohle filtru by mu WordPress
   uložení odmítl, protože options.php má natvrdo manage_options. */
add_filter( 'option_page_capability_gbub_stranky_group', function () { return GBUB_STAFF_CAP; } );

function gbub_stranky_sanitize( $vstup ) {
	$vstup = is_array( $vstup ) ? $vstup : array();
	$ven   = array();
	$sablony = array_keys( gbub_sablony() );

	foreach ( $vstup as $id => $r ) {
		$id = (int) $id;
		if ( ! $id || ! is_array( $r ) ) continue;
		/* Beze jména i textu nemá smysl řádek držet — prázdné záznamy by jen
		   bobtnaly volbu při každém uložení formuláře. */
		$mahoObsah = false;
		$radek = array(
			'aktivni' => empty( $r['aktivni'] ) ? 0 : 1,
			'sablona' => in_array( $r['sablona'] ?? '', $sablony, true ) ? $r['sablona'] : 'ctverec',
			'obrazek' => max( 0, (int) ( $r['obrazek'] ?? 0 ) ),
			'odkaz'   => esc_url_raw( (string) ( $r['odkaz'] ?? '' ) ),
		);
		foreach ( array_keys( gbub_jazyky() ) as $j ) {
			$nadpis = sanitize_text_field( (string) ( $r[ 'nadpis_' . $j ] ?? '' ) );
			$text   = wp_kses_post( (string) ( $r[ 'text_' . $j ] ?? '' ) );
			$tlac   = sanitize_text_field( (string) ( $r[ 'tlacitko_' . $j ] ?? '' ) );
			$radek[ 'nadpis_' . $j ]   = $nadpis;
			$radek[ 'text_' . $j ]     = $text;
			$radek[ 'tlacitko_' . $j ] = $tlac;
			if ( $nadpis !== '' || trim( wp_strip_all_tags( $text ) ) !== '' ) $mahoObsah = true;
		}
		if ( ! $mahoObsah && empty( $radek['obrazek'] ) ) continue;
		$ven[ $id ] = $radek;
	}
	return $ven;
}

/* ============================================================================
 * Položka v GRID Nastavení
 * ============================================================================ */
function gbub_grid_visible() {
	$v = get_option( 'garry_grid_visibility', array() );
	if ( ! is_array( $v ) || ! array_key_exists( 'garry-informacni-bublina-2', $v ) ) return true;
	return ! empty( $v['garry-informacni-bublina-2'] );
}

add_action( 'admin_menu', function () {
	if ( ! gbub_grid_visible() ) return;
	add_submenu_page( 'grid-options', 'Informační bublina', 'Informační bublina',
		GBUB_STAFF_CAP, 'garry-informacni-bublina-obsah', 'gbub_admin_stranky' );
}, 100 );

/** Stránky, které jde nastavit: publikované v základním jazyce. */
function gbub_nabidka_stranek() {
	$args = array(
		'post_type'      => array( 'page' ),
		'post_status'    => 'publish',
		'posts_per_page' => 300,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	);
	if ( function_exists( 'pll_default_language' ) ) $args['lang'] = pll_default_language();
	$ids = get_posts( $args );

	$ven = array();
	foreach ( $ids as $id ) {
		$mutace = array();
		if ( function_exists( 'pll_get_post' ) ) {
			foreach ( array( 'en', 'de' ) as $l ) {
				$m = pll_get_post( $id, $l );
				if ( $m ) $mutace[] = strtoupper( $l );
			}
		}
		$ven[ (int) $id ] = array(
			'nazev'  => get_the_title( $id ),
			'url'    => (string) get_permalink( $id ),
			'mutace' => $mutace,
		);
	}
	return $ven;
}

function gbub_admin_stranky() {
	if ( ! current_user_can( GBUB_STAFF_CAP ) ) return;
	$stranky  = gbub_nabidka_stranek();
	$ulozene  = gbub_stranky();
	$jazyky   = gbub_jazyky();
	$sablony  = gbub_sablony();
	?>
	<div class="wrap gbub-wrap">
		<h1>Informační bublina — obsah stránek</h1>
		<p class="description" style="max-width:900px">
			Pro každou stránku se dá zapnout vyskakovací bublina s vlastním obsahem.
			<strong>Jeden záznam platí i pro anglickou a německou mutaci téže stránky</strong> —
			texty proto zadáváte vedle sebe ve všech třech jazycích. Vzhled (barvy, rozměry,
			umístění) je společný pro celý web a nastavuje ho administrátor v
			<strong>GARRY nastavení → Informační bublina</strong>.
		</p>

		<form method="post" action="options.php" class="gbub-form">
			<?php settings_fields( 'gbub_stranky_group' ); ?>

			<?php foreach ( $stranky as $id => $s ) :
				$r = $ulozene[ $id ] ?? array();
				$zapnuto = ! empty( $r['aktivni'] );
				$pole = GBUB_STRANKY . '[' . $id . ']';
				$obrazek = (int) ( $r['obrazek'] ?? 0 );
				?>
				<details class="gbub-stranka<?php echo $zapnuto ? ' je-zapnuta' : ''; ?>" <?php echo $zapnuto ? 'open' : ''; ?>>
					<summary>
						<span class="gbub-stav" aria-hidden="true"></span>
						<strong><?php echo esc_html( $s['nazev'] ); ?></strong>
						<?php if ( $s['mutace'] ) : ?>
							<span class="gbub-mutace"><?php echo esc_html( 'CZ + ' . implode( ' + ', $s['mutace'] ) ); ?></span>
						<?php else : ?>
							<span class="gbub-mutace gbub-mutace--sama">jen CZ</span>
						<?php endif; ?>
					</summary>

					<div class="gbub-radek">
						<label class="gbub-zapnout">
							<input type="checkbox" name="<?php echo esc_attr( $pole ); ?>[aktivni]" value="1" <?php checked( $zapnuto ); ?>>
							Zobrazovat bublinu na této stránce
						</label>

						<label class="gbub-sablona">Šablona
							<select name="<?php echo esc_attr( $pole ); ?>[sablona]" class="gbub-vyber-sablony">
								<?php foreach ( $sablony as $klic => $sab ) : ?>
									<option value="<?php echo esc_attr( $klic ); ?>" <?php selected( $r['sablona'] ?? 'ctverec', $klic ); ?>>
										<?php echo esc_html( $sab['nazev'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</label>

						<div class="gbub-foto<?php echo ( ( $r['sablona'] ?? 'ctverec' ) === 'ctverec' ) ? ' je-skryta' : ''; ?>">
							<span class="gbub-popisek">Fotka</span>
							<input type="hidden" name="<?php echo esc_attr( $pole ); ?>[obrazek]" value="<?php echo esc_attr( (string) $obrazek ); ?>" class="gbub-obrazek-id">
							<button type="button" class="button gbub-vybrat">Vybrat z médií</button>
							<button type="button" class="button-link gbub-odebrat">odebrat</button>
							<div class="gbub-nahled-obrazku">
								<?php if ( $obrazek ) echo wp_get_attachment_image( $obrazek, 'medium' ); ?>
							</div>
						</div>

						<div class="gbub-jazyky">
							<?php foreach ( $jazyky as $j => $popisek ) : ?>
								<div class="gbub-jazyk">
									<h4><?php echo esc_html( $popisek ); ?></h4>
									<label>Nadpis
										<input type="text" name="<?php echo esc_attr( $pole . '[nadpis_' . $j . ']' ); ?>"
											value="<?php echo esc_attr( (string) ( $r[ 'nadpis_' . $j ] ?? '' ) ); ?>">
									</label>
									<label>Text
										<textarea rows="5" name="<?php echo esc_attr( $pole . '[text_' . $j . ']' ); ?>"><?php
											echo esc_textarea( (string) ( $r[ 'text_' . $j ] ?? '' ) ); ?></textarea>
									</label>
									<label>Popisek tlačítka
										<input type="text" name="<?php echo esc_attr( $pole . '[tlacitko_' . $j . ']' ); ?>"
											value="<?php echo esc_attr( (string) ( $r[ 'tlacitko_' . $j ] ?? '' ) ); ?>">
									</label>
								</div>
							<?php endforeach; ?>
						</div>

						<label class="gbub-odkaz">Odkaz tlačítka
							<input type="url" name="<?php echo esc_attr( $pole ); ?>[odkaz]"
								value="<?php echo esc_attr( (string) ( $r['odkaz'] ?? '' ) ); ?>" placeholder="https://…">
							<span class="description">Společný pro všechny jazyky. Bez popisku tlačítka se tlačítko nevykreslí.</span>
						</label>
					</div>
				</details>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
