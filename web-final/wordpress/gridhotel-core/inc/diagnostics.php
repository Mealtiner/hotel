<?php
/**
 * GRID Hotel Core — „Diagnostika" (verze 2.0.1, spec §14).
 * Jen pro capability grid_view_diagnostics. Nikdy nevypisuje osobní data —
 * jen verze, dostupnost integrací, kolize a stav migrací.
 *
 * Pod GARRY Nastavení, ne GRID Nastavení — technická diagnostika webu, ne
 * obsah pro personál hotelu. Zpětná vazba po nasazení 2.0.0.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'garry-nastaveni',
		'Diagnostika',
		'Diagnostika',
		GRIDCORE_DCAP_DIAGNOSTICS,
		'gridcore-diagnostics',
		'gridcore_render_diagnostics_page'
	);
}, 40 );

/** @return array<int,string> Shortcode tagy, které deklaruje víc než jeden registrovaný modul zároveň. */
function gridcore_detect_shortcode_claim_collisions() {
	$owners = array();
	foreach ( gridhotel_get_modules() as $id => $m ) {
		foreach ( (array) ( $m['shortcodes'] ?? array() ) as $tag ) {
			$owners[ $tag ][] = $id;
		}
	}
	$collisions = array();
	foreach ( $owners as $tag => $ids ) {
		if ( count( $ids ) > 1 ) {
			$collisions[] = $tag . ' (' . implode( ', ', $ids ) . ')';
		}
	}
	return $collisions;
}

/** @return array<int,string> Shortcody, které modul deklaruje, ale aktuálně nejsou registrované v $shortcode_tags. */
function gridcore_detect_missing_shortcode_registrations() {
	global $shortcode_tags;
	$missing = array();
	foreach ( gridhotel_get_modules() as $id => $m ) {
		foreach ( (array) ( $m['shortcodes'] ?? array() ) as $tag ) {
			if ( empty( $shortcode_tags[ $tag ] ) ) {
				$missing[] = $tag . ' (' . $id . ')';
			}
		}
	}
	return $missing;
}

/** @return array<int,string> Doménové capability (grid_manage_*), které role Administrator aktuálně nemá – za normálních okolností prázdné. */
function gridcore_detect_missing_admin_capabilities() {
	$role = get_role( 'administrator' );
	if ( ! $role ) {
		return array( 'role administrator neexistuje' );
	}
	$missing = array();
	foreach ( gridcore_domain_capability_catalog() as $cap => $label ) {
		if ( ! $role->has_cap( $cap ) ) {
			$missing[] = $cap;
		}
	}
	foreach ( array( GRIDCORE_CAP_ROOMS_GALLERY, GRIDCORE_CAP_CAREERS ) as $cap ) {
		if ( ! $role->has_cap( $cap ) ) {
			$missing[] = $cap;
		}
	}
	return $missing;
}

function gridcore_render_diagnostics_page() {
	if ( ! current_user_can( GRIDCORE_DCAP_DIAGNOSTICS ) ) {
		return;
	}

	$db_version      = (int) get_option( GRIDCORE_DB_VERSION_OPTION, 0 );
	$target_version  = GRIDCORE_DB_VERSION;
	$migration_log   = array_reverse( (array) get_option( GRIDCORE_MIGRATION_LOG_OPTION, array() ) );
	$missing_caps    = gridcore_detect_missing_admin_capabilities();
	$collisions      = gridcore_detect_shortcode_claim_collisions();
	$missing_sc      = gridcore_detect_missing_shortcode_registrations();
	$modules         = gridhotel_get_modules();
	$acf_active      = class_exists( 'ACF' );
	$acf_options_ui  = function_exists( 'acf_add_options_page' );
	$polylang_active = function_exists( 'pll_current_language' );
	$ff_active       = shortcode_exists( 'fluentform' );
	$legacy_events   = (int) wp_count_posts( 'grid_event' )->publish + (int) wp_count_posts( 'grid_event' )->draft;
	$legacy_log_option_exists = false !== get_option( 'garry_denni_menu_framework_log', false );

	$report_lines = array(
		'GARRY – GRID Core diagnostika',
		'Core verze: ' . GRIDCORE_VER,
		'Schema verze: ' . $db_version . ' / ' . $target_version,
		'Modul registry API verze: ' . gridhotel_core_api_version(),
		'ACF (třída ACF): ' . ( $acf_active ? 'aktivní' : 'neaktivní' ),
		'ACF options page (acf_add_options_page): ' . ( $acf_options_ui ? 'k dispozici' : 'nedostupné — používá se Settings API fallback' ),
		'Polylang: ' . ( $polylang_active ? 'aktivní' : 'neaktivní' ),
		'Fluent Forms (shortcode [fluentform]): ' . ( $ff_active ? 'aktivní' : 'neaktivní' ),
		'Registrované moduly: ' . count( $modules ),
		'Chybějící administrátorské capabilities: ' . ( empty( $missing_caps ) ? 'žádné' : implode( ', ', $missing_caps ) ),
		'Kolize shortcode nároků mezi moduly: ' . ( empty( $collisions ) ? 'žádné' : implode( '; ', $collisions ) ),
		'Legacy grid_event záznamů (jen počet): ' . $legacy_events,
	);
	?>
	<div class="wrap">
		<h1>Diagnostika</h1>

		<h2 class="title">Verze a integrace</h2>
		<table class="widefat striped" style="max-width:640px"><tbody>
			<tr><td>Core verze</td><td><?php echo esc_html( GRIDCORE_VER ); ?></td></tr>
			<tr><td>Schema verze</td><td><?php echo esc_html( $db_version . ' / ' . $target_version ); ?><?php echo $db_version < $target_version ? ' — <strong>migrace neproběhla úspěšně do konce, viz log níže</strong>' : ''; ?></td></tr>
			<tr><td>Modul registry API verze</td><td><?php echo esc_html( gridhotel_core_api_version() ); ?></td></tr>
			<tr><td>ACF</td><td><?php echo $acf_active ? '<span style="color:#0a0">aktivní</span>' : '<span style="color:#a00">neaktivní</span>'; ?></td></tr>
			<tr><td>ACF options page</td><td><?php echo $acf_options_ui ? 'k dispozici' : 'nedostupné — Settings API fallback'; ?></td></tr>
			<tr><td>Polylang</td><td><?php echo $polylang_active ? 'aktivní' : 'neaktivní'; ?></td></tr>
			<tr><td>Fluent Forms</td><td><?php echo $ff_active ? 'aktivní' : 'neaktivní'; ?></td></tr>
		</tbody></table>

		<h2 class="title">Moduly a kolize</h2>
		<p>Registrovaných modulů: <strong><?php echo (int) count( $modules ); ?></strong> — detailní výpis na stránce <a href="<?php echo esc_url( admin_url( 'admin.php?page=gridcore-modules' ) ); ?>">Moduly</a>.</p>
		<?php if ( ! empty( $collisions ) ) : ?>
			<p style="color:#a00"><strong>Kolize shortcode nároků:</strong> <?php echo esc_html( implode( '; ', $collisions ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $missing_sc ) ) : ?>
			<p style="color:#a00"><strong>Deklarovaný, ale neregistrovaný shortcode:</strong> <?php echo esc_html( implode( '; ', $missing_sc ) ); ?></p>
		<?php endif; ?>
		<?php if ( empty( $collisions ) && empty( $missing_sc ) ) : ?>
			<p class="description">Žádné kolize ani chybějící registrace.</p>
		<?php endif; ?>

		<h2 class="title">Capabilities</h2>
		<?php if ( empty( $missing_caps ) ) : ?>
			<p class="description">Administrátor má všechny doménové i granulární capabilities.</p>
		<?php else : ?>
			<p style="color:#a00">Administrátorovi chybí: <strong><?php echo esc_html( implode( ', ', $missing_caps ) ); ?></strong> — příště spuštění <code>gridcore_run_upgrade()</code> na <code>plugins_loaded</code> je doplní automaticky.</p>
		<?php endif; ?>

		<h2 class="title">Legacy zdroje</h2>
		<ul style="list-style:disc;margin-left:20px">
			<li>CPT <code>grid_event</code>: <strong><?php echo (int) $legacy_events; ?></strong> záznamů (jen počet, žádný obsah) — nové se od schema verze 4 dál negenerují, existující zůstávají beze změny.</li>
			<li>Option <code>garry_denni_menu_framework_log</code>: <?php echo $legacy_log_option_exists ? 'existuje — patří pluginu garry-denni-menu (Core od 2.0.0 píše do vlastní <code>gridhotel_core_framework_log</code>, viz Info tab výše)' : 'neexistuje'; ?>.</li>
		</ul>

		<h2 class="title">Historie migrací</h2>
		<?php if ( empty( $migration_log ) ) : ?>
			<p class="description">Zatím žádný záznam.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:640px"><thead><tr><th>Krok</th><th>Stav</th><th>Zpráva</th><th>Kdy</th></tr></thead><tbody>
				<?php foreach ( $migration_log as $entry ) : ?>
					<tr>
						<td><?php echo (int) $entry['step']; ?></td>
						<td><?php echo 'ok' === $entry['status'] ? '<span style="color:#0a0">ok</span>' : '<span style="color:#a00">chyba</span>'; ?></td>
						<td><?php echo esc_html( $entry['message'] ); ?></td>
						<td><?php echo esc_html( gmdate( 'Y-m-d H:i', (int) $entry['time'] ) ); ?> UTC</td>
					</tr>
				<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>

		<h2 class="title">Kopírovatelný anonymizovaný report</h2>
		<textarea readonly rows="12" class="large-text code" onclick="this.select()"><?php echo esc_textarea( implode( "\n", $report_lines ) ); ?></textarea>
	</div>
	<?php
}
