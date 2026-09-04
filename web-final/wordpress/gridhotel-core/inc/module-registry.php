<?php
/**
 * GRID Hotel Core — registr volitelných GRID modulů (verze 2.0.0).
 *
 * Veřejné API pro sourozenecké GARRY pluginy (Týdenní menu, Kategorie
 * pokojů, Sezóna, Situace na trati, Hero křivka, Sekční navigace…) – Core
 * nikdy nekopíruje jejich formulář ani data, jen si drží popisek "tenhle
 * modul existuje, umí tohle, spouští se tímhle callbackem/capabilitou".
 * Neplatný descriptor nikdy nezpůsobí fatal – jen se zaloguje a přeskočí.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDHOTEL_CORE_API_VERSION', '1.0.0' );
define( 'GRIDCORE_MODULE_ERRORS_OPTION', 'gridhotel_core_module_errors' );

/** @return array<string,array> reference na interní registr (in-memory, per request). */
function &gridcore_module_registry_ref() {
	static $registry = array();
	return $registry;
}

/**
 * @param array $descriptor {
 *   @type string $id               Unikátní slug, např. 'garry-denni-menu'.
 *   @type string $name             Lidský název modulu.
 *   @type string $version          Verze pluginu, který modul registruje.
 *   @type string $admin_slug       Admin URL/slug modulu (nebo $admin_callback).
 *   @type callable $admin_callback Vykreslovací callback modulu (nebo $admin_slug).
 *   @type string $capability       garry_grid_manage_* capabilita pro tenhle modul.
 *   @type array  $shortcodes       Poskytované shortcode tagy.
 *   @type array  $features         Poskytované feature ID (viz GRID-SUITE-02 §9).
 *   @type bool   $frontend_enabled Výchozí true.
 *   @type bool   $hidden_in_grid_menu Výchozí false – true jen skryje kartu
 *                                  v GRID Nastavení, NEVYPÍNÁ standalone admin
 *                                  ani frontend modulu.
 * }
 * @return bool True při úspěšné registraci, false při neplatném descriptoru
 *              (chyba se zaloguje pro Diagnostiku, nikdy nefatáluje).
 */
function gridhotel_register_module( array $descriptor ) {
	$registry =& gridcore_module_registry_ref();

	$id     = isset( $descriptor['id'] ) ? (string) $descriptor['id'] : '';
	$errors = array();

	if ( '' === $id || ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $id ) ) {
		$errors[] = 'chybějící nebo neplatné id (povoleno jen a-z, 0-9, _, -)';
	} elseif ( isset( $registry[ $id ] ) ) {
		$errors[] = 'duplicitní id — modul s tímto id je už registrovaný';
	}
	if ( empty( $descriptor['name'] ) )        $errors[] = 'chybí name';
	if ( empty( $descriptor['version'] ) )     $errors[] = 'chybí version';
	if ( empty( $descriptor['capability'] ) )  $errors[] = 'chybí capability';
	if ( ! isset( $descriptor['features'] ) || ! is_array( $descriptor['features'] ) )     $errors[] = 'chybí nebo neplatné features (musí být pole)';
	if ( ! isset( $descriptor['shortcodes'] ) || ! is_array( $descriptor['shortcodes'] ) ) $errors[] = 'chybí nebo neplatné shortcodes (musí být pole)';
	if ( empty( $descriptor['admin_slug'] ) && empty( $descriptor['admin_callback'] ) )    $errors[] = 'chybí admin_slug i admin_callback (aspoň jedno je povinné)';

	if ( ! empty( $errors ) ) {
		gridcore_log_module_registration_error( '' !== $id ? $id : '(bez id)', $errors );
		return false;
	}

	$registry[ $id ] = wp_parse_args( $descriptor, array(
		'frontend_enabled'    => true,
		'hidden_in_grid_menu' => false,
		'order'               => 100,
	) );

	return true;
}

/** @return array|null Descriptor podle id, nebo null. */
function gridhotel_get_module( $id ) {
	$registry = gridcore_module_registry_ref();
	return isset( $registry[ (string) $id ] ) ? $registry[ (string) $id ] : null;
}

/**
 * @return array<string,array> Všechny platně registrované moduly, seřazené
 *         podle 'order'. Filtr `gridhotel_modules_display_order` smí měnit
 *         jen POŘADÍ/VIDITELNOST výpisu (Moduly, Diagnostika) — nikdy
 *         nedeaktivuje cizí plugin ani neovlivní, zda modul reálně funguje.
 */
function gridhotel_get_modules() {
	$modules = gridcore_module_registry_ref();
	uasort( $modules, function ( $a, $b ) {
		return ( $a['order'] ?? 100 ) <=> ( $b['order'] ?? 100 );
	} );
	return apply_filters( 'gridhotel_modules_display_order', $modules );
}

/** @return bool True, pokud aspoň jeden aktivní modul poskytuje danou feature a je frontend_enabled. */
function gridhotel_has_feature( $feature ) {
	foreach ( gridcore_module_registry_ref() as $descriptor ) {
		if ( empty( $descriptor['frontend_enabled'] ) ) {
			continue;
		}
		if ( ! empty( $descriptor['features'] ) && in_array( $feature, $descriptor['features'], true ) ) {
			return true;
		}
	}
	return false;
}

/** @return string Major.minor.patch verze tohoto registračního API — sourozenecké pluginy si ji mohou ověřit před voláním. */
function gridhotel_core_api_version() {
	return GRIDHOTEL_CORE_API_VERSION;
}

/**
 * Moduly se registrují na tomhle dokumentovaném hooku, ne přímo při načtení
 * pluginu — spouští se na `init` až po tom, co všechny aktivní pluginy měly
 * příležitost zaregistrovat svoje vlastní `init` handlery (CPT registrace
 * v inc/cpt.php běží na výchozí prioritě 10, tenhle hook až na 20).
 *
 * Sourozenecký plugin: add_action( 'gridhotel_register_modules', function () {
 *     if ( ! function_exists( 'gridhotel_register_module' ) ) return;
 *     gridhotel_register_module( array( 'id' => 'garry-denni-menu', ... ) );
 * } );
 */
add_action( 'init', function () {
	do_action( 'gridhotel_register_modules' );
}, 20 );

/**
 * Admin obrazovka „Moduly" (jedna z 9 sekcí GRID Nastavení, spec §5). Jen
 * výpis a odkazy na vlastní administraci modulu — Core sem nekopíruje žádný
 * formulář ani data modulu samotného (spec §9).
 */
/**
 * Pod GARRY Nastavení, ne GRID Nastavení — registr modulů je technická
 * administrace webu, ne obsah pro personál hotelu. Zpětná vazba po nasazení
 * 2.0.0; stejný důvod jako v inc/access-page.php.
 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'garry-nastaveni',
		'Moduly',
		'Moduly',
		GRIDCORE_DCAP_MODULES,
		'gridcore-modules',
		'gridcore_render_modules_page'
	);
}, 40 );

function gridcore_render_modules_page() {
	if ( ! current_user_can( GRIDCORE_DCAP_MODULES ) ) {
		return;
	}
	$modules = gridhotel_get_modules();
	$errors  = get_option( GRIDCORE_MODULE_ERRORS_OPTION, array() );
	?>
	<div class="wrap">
		<h1>Moduly</h1>
		<p class="description">Volitelné GARRY pluginy registrované přes <code>gridhotel_register_module()</code>. Core tady nic needituje — jen odkazuje na vlastní administraci modulu.</p>

		<?php if ( empty( $modules ) ) : ?>
			<p class="description">Zatím žádný aktivní plugin nic neregistroval (API verze: <code><?php echo esc_html( gridhotel_core_api_version() ); ?></code>).</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th>Modul</th><th>Verze</th><th>Features</th><th>Shortcody</th><th>V GRID menu</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $modules as $id => $m ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $m['name'] ?? $id ); ?></strong> <span class="description">(<?php echo esc_html( $id ); ?>)</span></td>
							<td><?php echo esc_html( $m['version'] ?? '—' ); ?></td>
							<td><?php echo esc_html( implode( ', ', (array) ( $m['features'] ?? array() ) ) ); ?></td>
							<td><?php echo esc_html( implode( ', ', (array) ( $m['shortcodes'] ?? array() ) ) ); ?></td>
							<td><?php echo empty( $m['hidden_in_grid_menu'] ) ? 'ano' : 'skryto'; ?></td>
							<td>
								<?php if ( ! empty( $m['admin_slug'] ) ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $m['admin_slug'] ) ); ?>">Otevřít nastavení →</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ( ! empty( $errors ) ) : ?>
			<h2 class="title">Odmítnuté registrace</h2>
			<p class="description">Neplatné pokusy o registraci modulu — nikdy nezpůsobí chybu stránky, jen se přeskočí.</p>
			<ul style="list-style:disc;margin-left:20px">
				<?php foreach ( array_reverse( $errors ) as $e ) : ?>
					<li><code><?php echo esc_html( $e['id'] ); ?></code> — <?php echo esc_html( implode( '; ', (array) $e['errors'] ) ); ?> <span class="description">(<?php echo esc_html( gmdate( 'Y-m-d H:i', (int) $e['time'] ) ); ?> UTC)</span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/** Anonymizovaný log neplatných registrací pro Diagnostiku — jen technické id/důvody, žádná osobní data. */
function gridcore_log_module_registration_error( $id, array $errors ) {
	$log   = get_option( GRIDCORE_MODULE_ERRORS_OPTION, array() );
	$log[] = array(
		'id'     => substr( (string) $id, 0, 80 ),
		'errors' => array_map( function ( $e ) { return substr( (string) $e, 0, 120 ); }, $errors ),
		'time'   => time(),
	);
	if ( count( $log ) > 20 ) {
		$log = array_slice( $log, -20 );
	}
	update_option( GRIDCORE_MODULE_ERRORS_OPTION, $log, false );
}
