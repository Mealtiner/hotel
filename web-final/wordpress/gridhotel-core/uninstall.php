<?php
/**
 * GRID Hotel Core — odinstalace.
 *
 * ZÁMĚRNĚ NEMAŽE OBSAH. Pokoje, akce sezóny, zážitky, gastro a reference (CPT)
 * i jejich ACF pole zůstávají v databázi i po odinstalaci pluginu, aby se
 * obsah klienta neztratil. Po případné reinstalaci je vše zpět.
 *
 * (Pokud bys chtěl data smazat kompletně, je nutné je odstranit ručně.)
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
// Bez destruktivních operací nad obsahem — data ponechána.

/**
 * Capabilities (obě rodiny, viz inc/capabilities.php a inc/staff-permissions.php)
 * – tohle je jen oprávnění, ne obsah, takže se při odinstalaci uklidí ze
 * všech rolí i jednotlivě přidělených uživatelů stejně jako u ostatních
 * GARRY pluginů. Domain capabilities (grid_manage_*, 2.0.0) přidané ručně
 * (ne přes gridcore_domain_capability_catalog()) – uninstall.php běží mimo
 * běžný request a nemá zaručeno, že zbytek pluginu je includnutý.
 */
$gridcore_caps = array(
	'garry_grid_manage_pokoje_galerie',
	'garry_grid_manage_kariera',
	'grid_manage_settings',
	'grid_manage_rooms',
	'grid_manage_room_categories',
	'grid_manage_experiences',
	'grid_manage_gastro',
	'grid_manage_testimonials',
	'grid_manage_careers',
	'grid_manage_forms',
	'grid_manage_modules',
	'grid_manage_access',
	'grid_view_diagnostics',
);
foreach ( wp_roles()->roles as $role_key => $role_info ) {
	$role = get_role( $role_key );
	if ( ! $role ) continue;
	foreach ( $gridcore_caps as $cap ) {
		if ( $role->has_cap( $cap ) ) {
			$role->remove_cap( $cap );
		}
	}
}
foreach ( get_users( array( 'fields' => array( 'ID' ) ) ) as $u ) {
	$user = get_user_by( 'id', $u->ID );
	if ( ! $user ) continue;
	foreach ( $gridcore_caps as $cap ) {
		if ( $user->has_cap( $cap ) ) {
			$user->remove_cap( $cap );
		}
	}
}
