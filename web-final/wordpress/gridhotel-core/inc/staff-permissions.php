<?php
/**
 * GARRY – GRID Core: "Oprávnění personálu" — granulární přístup na kartu
 * v GRID Nastavení (verze 1.6.0).
 *
 * Administrátor (role Administrator) má ke všem kartám přístup vždy –
 * capability se mu přidává při aktivaci každého zúčastněného pluginu
 * (SEC-SELF-001 vzor). Tahle obrazovka řeší jen ostatní uživatele: každá
 * karta = jedna WordPress capability, kterou lze přidat/odebrat
 * jednotlivému uživateli přes WP_User::add_cap()/remove_cap() – žádná
 * nová role, žádný nový registr. Kdo capability nemá, danou submenu
 * položku v GRID Nastavení jednoduše nevidí (WP to řeší nativně přes
 * add_submenu_page()'s capability parametr); do GARRY Nastavení (striktně
 * manage_options) se tím nikdo nedostane.
 *
 * Zdroj karet: sdílený registr aktivních GARRY pluginů
 * $GLOBALS['garry_framework_v24']['participants'] – Framework 2.4 ho staví
 * sám, tahle obrazovka jen čte nepovinný klíč 'grid_capability' v deskriptoru
 * (viz bootstrap.php), obohacený o vlastní dvě capability GRID Core.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @return array<string,string> capability => lidsky čitelný název karty.
 */
function gridcore_staff_permission_catalog() {
	$catalog = array(
		GRIDCORE_CAP_ROOMS_GALLERY => 'Pokoje: fotky a galerie',
		GRIDCORE_CAP_CAREERS       => 'Kariéra',
	);

	$registry = isset( $GLOBALS['garry_framework_v24'] ) && is_array( $GLOBALS['garry_framework_v24'] )
		? $GLOBALS['garry_framework_v24']
		: array();
	$participants = isset( $registry['participants'] ) && is_array( $registry['participants'] ) ? $registry['participants'] : array();

	foreach ( $participants as $descriptor ) {
		if ( empty( $descriptor['grid_capability'] ) ) {
			continue;
		}
		$label = ! empty( $descriptor['grid_capability_label'] )
			? $descriptor['grid_capability_label']
			: ( ! empty( $descriptor['name'] ) ? $descriptor['name'] : $descriptor['grid_capability'] );
		$catalog[ $descriptor['grid_capability'] ] = $label;
	}

	return $catalog;
}

add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return; // GRID Nastavení neexistuje bez ACF PRO options page (viz motiv)
	add_submenu_page(
		'grid-options',
		'Oprávnění personálu',
		'Oprávnění personálu',
		'manage_options',
		'gridcore-staff-permissions',
		'gridcore_render_staff_permissions_page'
	);
}, 100 );

add_action( 'admin_post_gridcore_save_staff_permissions', 'gridcore_handle_save_staff_permissions' );

function gridcore_handle_save_staff_permissions() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gridcore_save_staff_permissions' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'gridhotel-core' ) );
	}

	$catalog = gridcore_staff_permission_catalog();
	$posted  = isset( $_POST['perm'] ) && is_array( $_POST['perm'] ) ? wp_unslash( $_POST['perm'] ) : array();

	foreach ( get_users( array( 'role__not_in' => array( 'administrator' ), 'fields' => array( 'ID' ) ) ) as $u ) {
		$user = get_user_by( 'id', $u->ID );
		if ( ! $user ) {
			continue;
		}
		foreach ( $catalog as $cap => $label ) {
			$checked = ! empty( $posted[ $u->ID ][ $cap ] );
			if ( $checked && ! $user->has_cap( $cap ) ) {
				$user->add_cap( $cap );
			} elseif ( ! $checked && $user->has_cap( $cap ) ) {
				$user->remove_cap( $cap );
			}
		}
	}

	wp_safe_redirect( add_query_arg(
		array( 'page' => 'gridcore-staff-permissions', 'saved' => 1 ),
		admin_url( 'admin.php' )
	) );
	exit;
}

function gridcore_render_staff_permissions_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$catalog = gridcore_staff_permission_catalog();
	$users   = get_users( array( 'role__not_in' => array( 'administrator' ), 'orderby' => 'display_name' ) );
	?>
	<div class="wrap">
		<h1>Oprávnění personálu</h1>
		<p class="description">Administrátoři mají přístup ke všem kartám vždy. Tady určíte, kterou konkrétní kartu v <strong>GRID Nastavení</strong> smí otevřít který zaměstnanec – do <strong>GARRY Nastavení</strong> se odtud nikdo nedostane, to zůstává jen pro administrátory.</p>

		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Oprávnění byla uložena.</p></div>
		<?php endif; ?>

		<?php if ( empty( $catalog ) ) : ?>
			<p class="description">Zatím žádný aktivní plugin nedeklaruje kartu s vlastní capability.</p>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( empty( $users ) ) : ?>
			<p class="description">Na webu zatím není žádný uživatel mimo roli Administrator.</p>
			<?php return; ?>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gridcore_save_staff_permissions">
			<?php wp_nonce_field( 'gridcore_save_staff_permissions' ); ?>
			<table class="widefat striped" style="max-width:900px">
				<thead>
					<tr>
						<th>Uživatel</th>
						<?php foreach ( $catalog as $cap => $label ) : ?>
							<th><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $users as $u ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $u->display_name ); ?></strong> <span class="description">(<?php echo esc_html( $u->user_login ); ?>)</span></td>
							<?php foreach ( $catalog as $cap => $label ) : ?>
								<td style="text-align:center">
									<input type="checkbox" name="perm[<?php echo esc_attr( $u->ID ); ?>][<?php echo esc_attr( $cap ); ?>]" value="1" <?php checked( $u->has_cap( $cap ) ); ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'Uložit oprávnění' ); ?>
		</form>
	</div>
	<?php
}
