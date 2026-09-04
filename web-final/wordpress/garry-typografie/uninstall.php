<?php
/**
 * Odinstalace pluginu GARRY – Typografie.
 * Maže jedinou položku, do které plugin ukládá své nastavení.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('garry_typografie_settings');
