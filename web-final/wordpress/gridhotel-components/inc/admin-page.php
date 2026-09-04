<?php
/**
 * GRID Hotel Components — vlastní admin stránka "Komponenty" (own_callback
 * pro Framework 2.4). GRID-SUITE-02 §14: seznam shortcodů a poskytovatelů,
 * stav Core API, chybějící volitelné moduly, kolize, odkazy na editační
 * místo vlastníka dat. Needituje žádná data GARRY pluginů (spec §14).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** @return array<int,array{tag:string,domain:string}> Všech 30 tagů, které Components vlastní (bez grid_tracknav/grid_telemetry — viz manifest). */
function gridc_owned_shortcode_inventory() {
	return array(
		array( 'tag' => 'grid_menu_hlavni', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_socials', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_paticka_kontakt', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_video_embed', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_header', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_footer', 'domain' => 'Globální' ),
		array( 'tag' => 'grid_hero', 'domain' => 'Hero a CTA' ),
		array( 'tag' => 'grid_booking', 'domain' => 'Hero a CTA' ),
		array( 'tag' => 'grid_final', 'domain' => 'Hero a CTA' ),
		array( 'tag' => 'grid_rezervace', 'domain' => 'Hero a CTA' ),
		array( 'tag' => 'grid_rooms', 'domain' => 'Pokoje' ),
		array( 'tag' => 'grid_gastro', 'domain' => 'Gastro a týdenní nabídka' ),
		array( 'tag' => 'grid_zazitky', 'domain' => 'Zážitky a sezóna' ),
		array( 'tag' => 'grid_season', 'domain' => 'Zážitky a sezóna' ),
		array( 'tag' => 'grid_poukazy', 'domain' => 'Zážitky a sezóna' ),
		array( 'tag' => 'grid_vstupy', 'domain' => 'Firemní/servisní' ),
		array( 'tag' => 'grid_pribeh', 'domain' => 'Firemní/servisní' ),
		array( 'tag' => 'grid_firemni', 'domain' => 'Firemní/servisní' ),
		array( 'tag' => 'grid_reference', 'domain' => 'Firemní/servisní' ),
		array( 'tag' => 'grid_kontakt', 'domain' => 'Formuláře/kontakt' ),
		array( 'tag' => 'grid_doprava', 'domain' => 'Formuláře/kontakt' ),
		array( 'tag' => 'grid_form_dotaznik', 'domain' => 'Formuláře/kontakt' ),
		array( 'tag' => 'grid_legal', 'domain' => 'Právní' ),
		array( 'tag' => 'grid_podminky', 'domain' => 'Právní' ),
		array( 'tag' => 'grid_gdpr', 'domain' => 'Právní' ),
		array( 'tag' => 'grid_onas', 'domain' => 'Podstránky' ),
		array( 'tag' => 'grid_kariera', 'domain' => 'Podstránky' ),
		array( 'tag' => 'grid_kariera_pozice', 'domain' => 'Podstránky' ),
		array( 'tag' => 'grid_video', 'domain' => 'Podstránky' ),
		array( 'tag' => 'grid_galerie', 'domain' => 'Podstránky' ),
	);
}

/** @return array<int,array{feature:string,tag:string,label:string}> Skládané sekce, které jdou přes ModuleRenderer. */
function gridc_composed_module_inventory() {
	return array(
		array( 'feature' => 'room_comparison', 'tag' => 'grid_rooms_cards', 'label' => 'Karty pokojů (v grid_rooms)' ),
		array( 'feature' => 'room_comparison', 'tag' => 'grid_rooms_table', 'label' => 'Tabulka pokojů (v grid_rooms na podstránce)' ),
		array( 'feature' => 'weekly_menu', 'tag' => 'grid_menu_tydne', 'label' => 'Týdenní menu (v grid_gastro na podstránce)' ),
		array( 'feature' => 'season_events', 'tag' => 'grid_season_events', 'label' => 'Sezónní akce (v grid_season)' ),
		array( 'feature' => 'season_events', 'tag' => 'grid_voucher_form', 'label' => 'Formulář poukazu (v grid_poukazy)' ),
	);
}

function gridc_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$core_ok      = function_exists( 'gridhotel_core_api_version' );
	$core_version = $core_ok ? gridhotel_core_api_version() : null;
	$modules      = $core_ok ? gridhotel_get_modules() : array();
	?>
	<div class="wrap">
		<h1>Komponenty</h1>
		<p class="description">Přehled shortcodů, které tenhle plugin vlastní, a stav skládaných sekcí (kdo je aktuálně vykresluje). Needituje se tady žádný obsah — jen odkazy na místo, kde se skutečně edituje.</p>

		<h2 class="title">Core API</h2>
		<?php if ( $core_ok ) : ?>
			<p style="color:#0a0">GARRY – GRID Core aktivní, API verze <code><?php echo esc_html( $core_version ); ?></code>.</p>
		<?php else : ?>
			<p style="color:#a00"><strong>GARRY – GRID Core nenalezeno.</strong> Components vyžaduje gridhotel-core ≥ 2.0.0 — bez něj shortcody vrací neutrální fallback stavy, ne chybu.</p>
		<?php endif; ?>

		<h2 class="title">Skládané sekce (ModuleRenderer)</h2>
		<table class="widefat striped" style="max-width:900px">
			<thead><tr><th>Feature</th><th>Shortcode</th><th>Kde se používá</th><th>Aktuální poskytovatel</th></tr></thead>
			<tbody>
				<?php foreach ( gridc_composed_module_inventory() as $row ) :
					$provider = '<span style="color:#a00">žádný — fallback Components</span>';
					foreach ( $modules as $id => $m ) {
						if ( ! empty( $m['frontend_enabled'] ) && in_array( $row['feature'], (array) ( $m['features'] ?? array() ), true ) && in_array( $row['tag'], (array) ( $m['shortcodes'] ?? array() ), true ) ) {
							$provider = '<span style="color:#0a0">' . esc_html( $m['name'] ?? $id ) . '</span>';
							break;
						}
					}
					?>
					<tr>
						<td><code><?php echo esc_html( $row['feature'] ); ?></code></td>
						<td><code>[<?php echo esc_html( $row['tag'] ); ?>]</code></td>
						<td><?php echo esc_html( $row['label'] ); ?></td>
						<td><?php echo $provider; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">Fallback neznamená chybu — je to záměrný neutrální stav podle GRID-SUITE-02 §9 (CTA/prázdný stav místo rozbité sekce), dokud sourozenecký plugin nezaregistruje modul.</p>

		<h2 class="title">Vlastněné shortcody (30)</h2>
		<p class="description"><code>[grid_tracknav]</code> a <code>[grid_telemetry]</code> záměrně chybí — vlastní je pluginy „Sekční navigace" a „Situace na trati", ne Components (viz manifest.json).</p>
		<table class="widefat striped" style="max-width:700px">
			<thead><tr><th>Shortcode</th><th>Skupina</th></tr></thead>
			<tbody>
				<?php foreach ( gridc_owned_shortcode_inventory() as $row ) : ?>
					<tr><td><code>[<?php echo esc_html( $row['tag'] ); ?>]</code></td><td><?php echo esc_html( $row['domain'] ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
