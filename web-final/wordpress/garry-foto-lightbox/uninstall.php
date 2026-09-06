<?php
/**
 * Odinstalace pluginu GARRY – Foto lightbox.
 * Manifest deklaruje delete-on-uninstall, takže se volba i log opravdu mažou.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
if ( ! current_user_can( 'activate_plugins' ) ) exit;

delete_option( 'garry_foto_lightbox' );
delete_option( 'garry_foto_lightbox_log' );
delete_option( 'garry_foto_lightbox_stranky' );
delete_transient( 'gflb_cache' );
