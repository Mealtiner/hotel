<?php
/**
 * Odinstalace pluginu CIT – Divi Toggle Text.
 * Maže jedinou option, do které plugin ukládá své nastavení.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('cit_divi_toggle_text_settings');
