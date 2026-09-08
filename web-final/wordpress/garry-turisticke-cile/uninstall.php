<?php
/**
 * Odinstalace pluginu GARRY – Turistické cíle.
 * Maže se jen vlastní volba s obsahem; nic jiného plugin v databázi nedrží.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
delete_option( 'garry_turisticke_cile' );
delete_option( 'garry_turisticke_cile_log' );
