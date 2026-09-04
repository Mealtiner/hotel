<?php
/**
 * GRID Hotel Core — „Přístupy" (verze 2.0.1): kdo smí spravovat který typ
 * obsahu (grid_manage_* rodina, viz inc/capabilities.php). Odděleno od
 * "Oprávnění personálu" (inc/staff-permissions.php, garry_grid_manage_*
 * rodina – vidí uživatel vůbec kartu v menu) — obě obrazovky existují vedle
 * sebe, žádná nenahrazuje druhou.
 *
 * Vlastní administraci webu (ne obsah, se kterým denně pracuje personál
 * hotelu) patří pod GARRY Nastavení, ne pod GRID Nastavení — zpětná vazba po
 * nasazení 2.0.0. Registrace pod 'grid-options' navíc reálně končila 404 při
 * kliknutí: theme (dosud neupravený, viz GRID-SUITE-09) pořád registruje
 * vlastní acf_add_options_page() se stejným menu_slug 'grid-options' a
 * souběh dvou registrací stejné ACF options page rozbíjel WP resolving
 * nových vlastních podstránek (staré podpoložky fungovaly dál, protože míří
 * na reálné WP routy edit.php/edit-tags.php, ne na vlastní slug+callback).
 * 'garry-nastaveni' je čistý add_menu_page() bez ACF, bez tohohle rizika.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page(
		'garry-nastaveni',
		'Přístupy',
		'Přístupy',
		GRIDCORE_DCAP_ACCESS,
		'gridcore-access',
		'gridcore_render_access_page'
	);
}, 40 );

/** @return bool True, pokud aspoň jedna z rolí uživatele má danou capabilitu (= "zděděno z role", needitovatelné tady). */
function gridcore_user_has_role_capability( WP_User $user, $cap ) {
	foreach ( (array) $user->roles as $role_slug ) {
		$role = get_role( $role_slug );
		if ( $role && $role->has_cap( $cap ) ) {
			return true;
		}
	}
	return false;
}

function gridcore_render_access_page() {
	if ( ! current_user_can( GRIDCORE_DCAP_ACCESS ) ) {
		return;
	}

	$catalog = gridcore_domain_capability_catalog();
	$users   = get_users( array( 'orderby' => 'display_name' ) );
	?>
	<div class="wrap">
		<h1>Přístupy</h1>
		<p class="description">
			Kdo smí vytvářet/upravovat/mazat/publikovat který typ obsahu. Administrátor má všechna
			práva vždy — role „Administrator" je má přidělenou napevno (viz
			<code>gridcore_grant_domain_capabilities_to_admin()</code>) a v tabulce se needituje.
			Zašedlé/needitovatelné zaškrtnutí = právo přišlo z role uživatele, ne odsud.
		</p>

		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Přístupy byly uloženy.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['blocked_self'] ) ) : ?>
			<div class="notice notice-warning is-dismissible"><p>Vlastní přístup „Přístupy" si tady nemůžete odebrat — o změnu požádejte jiného administrátora.</p></div>
		<?php endif; ?>

		<?php if ( empty( $users ) ) : ?>
			<p class="description">Na webu zatím není žádný uživatel.</p>
			<?php return; ?>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gridcore_save_access">
			<?php wp_nonce_field( 'gridcore_save_access' ); ?>
			<div style="overflow-x:auto">
			<table class="widefat striped" style="min-width:900px">
				<thead>
					<tr>
						<th>Uživatel</th>
						<?php foreach ( $catalog as $label ) : ?>
							<th><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $users as $u ) :
						$user = get_user_by( 'id', $u->ID );
						if ( ! $user ) {
							continue;
						}
						$is_admin = in_array( 'administrator', (array) $user->roles, true );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( $user->display_name ); ?></strong>
								<span class="description">(<?php echo esc_html( $user->user_login ); ?>)</span>
								<?php if ( $is_admin ) : ?><span class="description"> — administrátor</span><?php endif; ?>
							</td>
							<?php foreach ( $catalog as $cap => $label ) :
								$role_has = $is_admin || gridcore_user_has_role_capability( $user, $cap );
								$checked  = $role_has || $user->has_cap( $cap );
								?>
								<td style="text-align:center">
									<input type="checkbox"
										name="access[<?php echo esc_attr( $user->ID ); ?>][<?php echo esc_attr( $cap ); ?>]"
										value="1"
										<?php checked( $checked ); ?>
										<?php disabled( $role_has ); ?>>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php submit_button( 'Uložit přístupy' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_post_gridcore_save_access', function () {
	if ( ! current_user_can( GRIDCORE_DCAP_ACCESS ) || ! check_admin_referer( 'gridcore_save_access' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'gridhotel-core' ) );
	}

	$catalog          = gridcore_domain_capability_catalog();
	$posted           = isset( $_POST['access'] ) && is_array( $_POST['access'] ) ? wp_unslash( $_POST['access'] ) : array();
	$current_user_id  = get_current_user_id();
	$blocked_self     = false;

	// Iterujeme reálné existující uživatele (get_users()), ne ID z requestu –
	// cílový uživatel tak nutně existuje, jak vyžaduje spec §8.
	foreach ( get_users() as $u ) {
		$user = get_user_by( 'id', $u->ID );
		if ( ! $user || in_array( 'administrator', (array) $user->roles, true ) ) {
			continue; // administrátor má práva z role napevno, tahle stránka je needituje
		}

		foreach ( $catalog as $cap => $label ) {
			if ( gridcore_user_has_role_capability( $user, $cap ) ) {
				continue; // právo z role se přes tuhle stránku nikdy nemění (viz disabled() v UI)
			}

			$checked = ! empty( $posted[ $u->ID ][ $cap ] );

			/**
			 * Uživatel si nesmí sám sobě přes tenhle formulář odebrat právo
			 * "Přístupy" — jinak by se do týhle obrazovky sám zamkl (spec §8:
			 * "vlastní přístup si uživatel nesmí odebrat, pokud by nezůstal
			 * jiný správce"). Administrátor má "Přístupy" vždy z role a nikdy
			 * není v týhle smyčce, takže reálné riziko úplného zamčení systému
			 * nehrozí – tahle podmínka je čistě ochrana PŘED zbytečným
			 * sebe-odebráním na téhle konkrétní obrazovce.
			 */
			if ( GRIDCORE_DCAP_ACCESS === $cap && (int) $u->ID === $current_user_id && ! $checked && $user->has_cap( $cap ) ) {
				$blocked_self = true;
				continue;
			}

			if ( $checked && ! $user->has_cap( $cap ) ) {
				$user->add_cap( $cap );
			} elseif ( ! $checked && $user->has_cap( $cap ) ) {
				$user->remove_cap( $cap );
			}
		}
	}

	$redirect_args = array( 'page' => 'gridcore-access', 'saved' => 1 );
	if ( $blocked_self ) {
		$redirect_args['blocked_self'] = 1;
	}
	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
	exit;
} );
