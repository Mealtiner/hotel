<?php
/**
 * GRID Hotel Core — vlastník stránky „GRID Nastavení" / `grid-options`
 * (verze 2.0.0, přesunuto z child theme grid-divi5-child/functions.php).
 *
 * Stejný menu_slug/capability/position jako dřívější registrace v theme, aby
 * nic nepřestalo fungovat v přechodném období, než se i theme aktualizuje
 * (theme se podle GRID-SUITE-09/master plánu upravuje až jako úplně poslední
 * krok celé sady). ACF options-page/local-field-group úložiště je klíčované
 * podle menu_slug/group key, takže dočasná souběžná registrace stejné
 * stránky z theme i z Core je neškodná (poslední volání jen přepíše stejný
 * záznam v interním úložišti ACF) – ověřit na stagingu před nasazením na
 * produkci, je to interní chování ACF, ne zdokumentovaná veřejná záruka.
 *
 * Skupiny `group_grid_options`/`group_grid_content` (acf-json/) mají STEJNÉ
 * group key i field names jako doposud v theme – žádná migrace uložených
 * hodnot, mění se jen KDO stránku/pole registruje.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => 'GRID — Nastavení webu',
		'menu_title' => 'GRID Nastavení',
		'menu_slug'  => 'grid-options',
		'capability' => 'manage_options',
		'redirect'   => false,
		'icon_url'   => 'none',
		'position'   => 3,
	) );
} );

/**
 * Fallback bez ACF PRO: „GRID Nastavení" musí vzniknout i tak (spec §2/§6.2 –
 * plugin musí fungovat bez ACF). Bez tohohle bloku by bez ACF PRO stránka
 * `grid-options` vůbec neexistovala a všechny na ni navázané podpoložky
 * (CPT, Pokoje — fotky a galerie, Kariéra, Oprávnění personálu…) by ztratily
 * rodiče. Registruje se jen tehdy, když ACF PRO NENÍ k dispozici – priorita 5,
 * tedy PŘED podpoložkami v inc/cpt.php (priorita 30) a inc/staff-permissions.php
 * (priorita 100), aby rodičovská stránka existovala dřív, než se do ní něco
 * vkládá.
 */
add_action( 'admin_menu', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	add_menu_page(
		'GRID — Nastavení webu',
		'GRID Nastavení',
		GRIDCORE_DCAP_SETTINGS,
		'grid-options',
		'gridcore_render_settings_fallback_page',
		'dashicons-admin-generic',
		30
	);
}, 5 );

/**
 * Definice polí fallback formuláře. Obrázkové/gallery/repeater typy
 * (hero_obrazek, pribeh_obrazek, firemni_obrazek, logo_negativ,
 * galerie_bloky) tady záměrně NEJSOU – vyžadují Media Library integraci,
 * kterou by bylo neúměrné duplikovat mimo ACF; bez ACF PRO se tahle pole
 * jednoduše nezobrazí (gridhotel_get_option() pro ně vrátí $default, ne chybu).
 *
 * @return array<string,array{label:string,type:string}> field_name => {label, type}
 */
function gridcore_settings_fallback_schema() {
	return array(
		'Kontakt' => array(
			'sirka_webu'     => array( 'label' => 'Šířka webu (px)', 'type' => 'number' ),
			'adresa_1'       => array( 'label' => 'Adresa — řádek 1', 'type' => 'text' ),
			'adresa_2'       => array( 'label' => 'Adresa — řádek 2', 'type' => 'text' ),
			'tel_recepce'    => array( 'label' => 'Telefon — recepce', 'type' => 'text' ),
			'tel_rezervace'  => array( 'label' => 'Telefon — rezervace', 'type' => 'text' ),
			'tel_shuttle'    => array( 'label' => 'Telefon — shuttle bus', 'type' => 'text' ),
			'email'          => array( 'label' => 'E-mail', 'type' => 'email' ),
			'ico'            => array( 'label' => 'IČ', 'type' => 'text' ),
			'dic'            => array( 'label' => 'DIČ', 'type' => 'text' ),
			'spis_znacka'    => array( 'label' => 'Spisová značka', 'type' => 'text' ),
			'rezervace_url'  => array( 'label' => 'Rezervační URL (tlačítko)', 'type' => 'text' ),
		),
		'Hero' => array(
			'hero_kicker'          => array( 'label' => 'Hero kicker — CZ', 'type' => 'text' ),
			'hero_kicker_en'       => array( 'label' => 'Hero kicker — EN', 'type' => 'text' ),
			'hero_kicker_de'       => array( 'label' => 'Hero kicker — DE', 'type' => 'text' ),
			'hero_nadpis'          => array( 'label' => 'Hero nadpis (HTML povoleno) — CZ', 'type' => 'text' ),
			'hero_nadpis_en'       => array( 'label' => 'Hero nadpis (HTML povoleno) — EN', 'type' => 'text' ),
			'hero_nadpis_de'       => array( 'label' => 'Hero nadpis (HTML povoleno) — DE', 'type' => 'text' ),
			'hero_podtitulek'      => array( 'label' => 'Hero podtitulek — CZ', 'type' => 'textarea' ),
			'hero_podtitulek_en'   => array( 'label' => 'Hero podtitulek — EN', 'type' => 'textarea' ),
			'hero_podtitulek_de'   => array( 'label' => 'Hero podtitulek — DE', 'type' => 'textarea' ),
		),
		'Video' => array(
			'video_url' => array( 'label' => 'Video URL (YouTube/Vimeo embed)', 'type' => 'url' ),
		),
		'Sociální sítě' => array(
			'soc_facebook'  => array( 'label' => 'Facebook — URL', 'type' => 'url' ),
			'soc_instagram' => array( 'label' => 'Instagram — URL', 'type' => 'url' ),
			'soc_youtube'   => array( 'label' => 'YouTube — URL', 'type' => 'url' ),
			'soc_linkedin'  => array( 'label' => 'LinkedIn — URL', 'type' => 'url' ),
			'soc_tiktok'    => array( 'label' => 'TikTok — URL', 'type' => 'url' ),
			'soc_x'         => array( 'label' => 'X / Twitter — URL', 'type' => 'url' ),
		),
	);
}

function gridcore_render_settings_fallback_page() {
	if ( ! current_user_can( GRIDCORE_DCAP_SETTINGS ) ) {
		return;
	}
	$values = get_option( GRIDCORE_SETTINGS_FALLBACK_OPTION, array() );
	?>
	<div class="wrap">
		<h1>GRID — Nastavení webu</h1>
		<p class="description">
			<strong>Advanced Custom Fields PRO</strong> není aktivní — pole níže se ukládají přes jednoduchý
			formulář místo ACF. Obrázky a galerie (hero obrázek, logo, fotogalerie) vyžadují ACF PRO a v téhle
			variantě nejsou dostupné.
		</p>
		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Nastavení bylo uloženo.</p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gridcore_save_settings_fallback">
			<?php wp_nonce_field( 'gridcore_save_settings_fallback' ); ?>
			<?php foreach ( gridcore_settings_fallback_schema() as $section_label => $fields ) : ?>
				<h2 class="title"><?php echo esc_html( $section_label ); ?></h2>
				<table class="form-table" role="presentation"><tbody>
					<?php foreach ( $fields as $name => $field ) :
						$val = isset( $values[ $name ] ) ? $values[ $name ] : '';
						?>
						<tr>
							<th scope="row"><label for="gcf-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field['type'] ) : ?>
									<textarea id="gcf-<?php echo esc_attr( $name ); ?>" name="fallback[<?php echo esc_attr( $name ); ?>]" rows="3" class="large-text"><?php echo esc_textarea( $val ); ?></textarea>
								<?php else : ?>
									<input type="<?php echo esc_attr( 'number' === $field['type'] ? 'number' : ( 'email' === $field['type'] ? 'email' : ( 'url' === $field['type'] ? 'url' : 'text' ) ) ); ?>"
										id="gcf-<?php echo esc_attr( $name ); ?>"
										name="fallback[<?php echo esc_attr( $name ); ?>]"
										value="<?php echo esc_attr( $val ); ?>"
										class="regular-text">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody></table>
			<?php endforeach; ?>
			<?php submit_button( 'Uložit nastavení' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_gridcore_save_settings_fallback', function () {
	if ( ! current_user_can( GRIDCORE_DCAP_SETTINGS ) || ! check_admin_referer( 'gridcore_save_settings_fallback' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'gridhotel-core' ) );
	}

	$posted = isset( $_POST['fallback'] ) && is_array( $_POST['fallback'] ) ? wp_unslash( $_POST['fallback'] ) : array();
	$clean  = array();

	foreach ( gridcore_settings_fallback_schema() as $fields ) {
		foreach ( $fields as $name => $field ) {
			if ( ! isset( $posted[ $name ] ) ) {
				continue;
			}
			$raw = $posted[ $name ];
			switch ( $field['type'] ) {
				case 'number':
					$clean[ $name ] = max( 960, min( 1600, (int) $raw ) );
					break;
				case 'email':
					$clean[ $name ] = sanitize_email( $raw );
					break;
				case 'url':
					$clean[ $name ] = esc_url_raw( $raw );
					break;
				case 'textarea':
					$clean[ $name ] = sanitize_textarea_field( $raw );
					break;
				default:
					$clean[ $name ] = sanitize_text_field( $raw );
			}
		}
	}

	update_option( GRIDCORE_SETTINGS_FALLBACK_OPTION, $clean, false );

	wp_safe_redirect( add_query_arg( array( 'page' => 'grid-options', 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );
