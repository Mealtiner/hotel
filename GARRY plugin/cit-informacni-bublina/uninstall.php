<?php
/**
 * Odinstalace pluginu CIT – Informační bublina.
 * Maže jedinou option, do které plugin ukládá své nastavení.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('cit_bubble_settings');
