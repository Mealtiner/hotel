<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
delete_option( 'garry_menu' );

/**
 * 'garry_grid_visibility' je sdílená option napříč více GARRY pluginy
 * (denní menu, kategorie pokojů, sezóna – každý má svůj vlastní klíč
 * uvnitř), takže se celá nemaže – jen se odstraní klíč tohoto pluginu.
 */
$grid_visibility = get_option( 'garry_grid_visibility' );
if ( is_array( $grid_visibility ) && array_key_exists( 'garry-denni-menu', $grid_visibility ) ) {
	unset( $grid_visibility['garry-denni-menu'] );
	update_option( 'garry_grid_visibility', $grid_visibility, false );
}

// Granulární capability na kartu (viz GARRY – GRID Core) – smazat ze všech rolí i jednotlivě přidělených uživatelů.
foreach ( wp_roles()->roles as $role_key => $role_info ) {
	$role = get_role( $role_key );
	if ( $role && $role->has_cap( 'garry_grid_manage_denni_menu' ) ) {
		$role->remove_cap( 'garry_grid_manage_denni_menu' );
	}
}
foreach ( get_users( array( 'fields' => array( 'ID' ) ) ) as $u ) {
	$user = get_user_by( 'id', $u->ID );
	if ( $user && $user->has_cap( 'garry_grid_manage_denni_menu' ) ) {
		$user->remove_cap( 'garry_grid_manage_denni_menu' );
	}
}
