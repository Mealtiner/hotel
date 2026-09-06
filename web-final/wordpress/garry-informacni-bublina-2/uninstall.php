<?php
/**
 * Odinstalace pluginu GARRY – Informační bublina 2.
 * Manifest deklaruje delete-on-uninstall, takže se obě volby i capability mažou.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
if ( ! current_user_can( 'activate_plugins' ) ) exit;

delete_option( 'garry_bublina2_vzhled' );
delete_option( 'garry_bublina2_stranky' );

$role = get_role( 'administrator' );
if ( $role ) { $role->remove_cap( 'garry_grid_manage_informacni_bublina' ); }
