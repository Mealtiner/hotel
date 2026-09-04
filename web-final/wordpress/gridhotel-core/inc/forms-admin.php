<?php
/**
 * GRID Hotel Core — „Formuláře a integrace" (verze 2.0.0).
 *
 * Option `grid_ff_forms` (tvar {účel: {jazyk: ID formuláře}}) dosud v celé
 * sadě pluginů/theme neměla ŽÁDNÉ administrační rozhraní — jen ji na čtení
 * používal theme (newsletter v patičce) a garry-sezona-cekaci-list (čekací
 * list, poukaz). Tohle je tedy skutečně chybějící funkce, ne jen refaktoring.
 * Core ukládá POUZE mapování účel → ID externího formuláře, nikdy odeslaná
 * osobní data (ta zůstávají u Fluent Forms, mimo tenhle plugin).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_FF_FORMS_OPTION', 'grid_ff_forms' );

/**
 * @return array<string,string> účel => popisek. Filtrovatelné, aby si i
 *         budoucí GARRY pluginy mohly přidat vlastní účel bez zásahu do Core.
 */
function gridcore_ff_purpose_catalog() {
	return apply_filters( 'gridhotel_ff_purposes', array(
		'newsletter' => 'Newsletter (patička)',
		'cekaci'     => 'Čekací list — Sezóna',
		'voucher'    => 'Dárkový poukaz — Sezóna',
	) );
}

/** @return array<string,string> kód jazyka => popisek. */
function gridcore_ff_languages() {
	return array( 'cs' => 'CZ', 'en' => 'EN', 'de' => 'DE' );
}

/**
 * @param string      $purpose Klíč z gridcore_ff_purpose_catalog().
 * @param string|null $lang    Kód jazyka; null = aktuální/výchozí jazyk webu.
 * @return int ID formuláře Fluent Forms, nebo 0 pokud není nastaveno.
 */
function gridhotel_ff_get_form_id( $purpose, $lang = null ) {
	if ( null === $lang ) {
		$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : gridcore_site_default_locale();
	}
	$map = (array) get_option( GRIDCORE_FF_FORMS_OPTION, array() );
	$id  = isset( $map[ $purpose ][ $lang ] ) ? (int) $map[ $purpose ][ $lang ] : 0;
	return $id > 0 ? $id : 0;
}

/**
 * @return string 'unavailable' (Fluent Forms není aktivní), 'unconfigured'
 *         (plugin je, ale pro tenhle účel/jazyk není vyplněné ID), nebo 'ok'.
 *         Konzument podle tohohle stavu rozhodne, jestli vykreslí formulář,
 *         CTA náhradu, nebo nic — nikdy ne syrový shortcode/falešný úspěch.
 */
function gridhotel_ff_status( $purpose, $lang = null ) {
	if ( ! shortcode_exists( 'fluentform' ) ) {
		return 'unavailable';
	}
	return gridhotel_ff_get_form_id( $purpose, $lang ) > 0 ? 'ok' : 'unconfigured';
}

/**
 * Pod GARRY Nastavení, ne GRID Nastavení — technická integrace (ID formulářů
 * Fluent Forms), ne obsah, se kterým denně pracuje personál hotelu. Zpětná
 * vazba po nasazení 2.0.0; viz stejný důvod v inc/access-page.php (i reálný
 * 404 kvůli souběžné registraci 'grid-options' theme + Core).
 */
add_action( 'admin_menu', function () {
	add_submenu_page(
		'garry-nastaveni',
		'Formuláře a integrace',
		'Formuláře a integrace',
		GRIDCORE_DCAP_FORMS,
		'gridcore-forms',
		'gridcore_render_forms_page'
	);
}, 40 );

function gridcore_render_forms_page() {
	if ( ! current_user_can( GRIDCORE_DCAP_FORMS ) ) {
		return;
	}

	$map = (array) get_option( GRIDCORE_FF_FORMS_OPTION, array() );
	$ff_active = shortcode_exists( 'fluentform' );
	?>
	<div class="wrap">
		<h1>Formuláře a integrace</h1>
		<p class="description">
			Mapování účelu formuláře na ID formuláře ve <strong>Fluent Forms</strong>. Core neukládá
			odeslaná data ani osobní údaje — jen tohle přiřazení. Formulář samotný se vytváří a
			spravuje ve Fluent Forms.
		</p>
		<?php if ( ! $ff_active ) : ?>
			<div class="notice notice-warning"><p><strong>Fluent Forms není aktivní.</strong> Mapování níže si můžete uložit, ale dokud plugin nebude aktivní, žádný konzument formulář nevykreslí.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Mapování bylo uloženo.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gridcore_save_ff_forms">
			<?php wp_nonce_field( 'gridcore_save_ff_forms' ); ?>
			<table class="widefat striped" style="max-width:640px">
				<thead>
					<tr>
						<th>Účel</th>
						<?php foreach ( gridcore_ff_languages() as $label ) : ?><th><?php echo esc_html( $label ); ?></th><?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( gridcore_ff_purpose_catalog() as $purpose => $label ) : ?>
						<tr>
							<td><?php echo esc_html( $label ); ?> <span class="description">(<?php echo esc_html( $purpose ); ?>)</span></td>
							<?php foreach ( gridcore_ff_languages() as $lang => $lang_label ) :
								$val = isset( $map[ $purpose ][ $lang ] ) ? (int) $map[ $purpose ][ $lang ] : '';
								?>
								<td>
									<input type="number" min="0" step="1" style="width:90px"
										name="ff[<?php echo esc_attr( $purpose ); ?>][<?php echo esc_attr( $lang ); ?>]"
										value="<?php echo esc_attr( $val ); ?>"
										placeholder="ID">
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">0 nebo prázdné = pro daný jazyk se formulář nezobrazí.</p>
			<?php submit_button( 'Uložit mapování' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_gridcore_save_ff_forms', function () {
	if ( ! current_user_can( GRIDCORE_DCAP_FORMS ) || ! check_admin_referer( 'gridcore_save_ff_forms' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'gridhotel-core' ) );
	}

	$posted = isset( $_POST['ff'] ) && is_array( $_POST['ff'] ) ? wp_unslash( $_POST['ff'] ) : array();
	$clean  = array();

	foreach ( array_keys( gridcore_ff_purpose_catalog() ) as $purpose ) {
		if ( ! isset( $posted[ $purpose ] ) || ! is_array( $posted[ $purpose ] ) ) {
			continue;
		}
		foreach ( array_keys( gridcore_ff_languages() ) as $lang ) {
			$id = isset( $posted[ $purpose ][ $lang ] ) ? max( 0, (int) $posted[ $purpose ][ $lang ] ) : 0;
			if ( $id > 0 ) {
				$clean[ $purpose ][ $lang ] = $id;
			}
		}
	}

	update_option( GRIDCORE_FF_FORMS_OPTION, $clean, false );

	wp_safe_redirect( add_query_arg( array( 'page' => 'gridcore-forms', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );
