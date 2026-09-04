<?php
/**
 * Plugin Name: GARRY – Typografie
 * Plugin URI:  https://www.garry.cz
 * Description: Centrálně nastavuje fonty a základní typografii nadpisů, textů, seznamů, tabulek a menu pro celý web. Funguje ve WordPressu; obsahuje cílené CSS selektory pro Divi, které je nutné ověřit na používané verzi; původně vytvořeno pro Centrum inovativní terapie a Kliniku Podané ruce.
 * Version:     1.2.2
 * Author:      GARRY Promotion
 * Author URI:  https://garry.cz/
 * License:     Proprietary - Copyright (c) GARRY Promotion
 * Text Domain: garry-typografie
 * Update URI: https://www.garry.cz
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GARRY_TYPO_OPTION', 'garry_typografie_settings');
define('GARRY_TYPO_VERSION', '1.2.2');
define('GARRY_TYPO_PUBLISHED', '1. 9. 2026');
define('GARRY_TYPO_SLUG', 'garry-typografie');
define('GARRY_TYPO_FILE', __FILE__);
define('GARRY_TYPO_DIR', plugin_dir_path(__FILE__));
define('GARRY_TYPO_URL', plugin_dir_url(__FILE__));

define('GARRY_TYPO_SWITCH_ON_COLOR', '#68C020');
define('GARRY_TYPO_SWITCH_OFF_COLOR', '#E02B20');

/* ============================================================================
 * Sdílený GARRY rámec (společné menu „GARRY nastavení" + stránka Info)
 * ========================================================================= */

/* ============================================================================
 * Vlastní části pluginu
 * ========================================================================= */
require_once GARRY_TYPO_DIR . 'includes/typo-config.php';
require_once GARRY_TYPO_DIR . 'includes/typo-frontend.php';

if (is_admin()) {
    require_once GARRY_TYPO_DIR . 'includes/typo-admin.php';
}

/* ============================================================================
 * Registrace do společného GARRY menu
 * ========================================================================= */
require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\Typografie\V23\bootstrap( __FILE__, 'garry_typo_render_admin_page', 'Typografie' );

/* ============================================================================
 * Nastavení
 * ========================================================================= */

function garry_typo_register_settings() {
    register_setting(
        'garry_typo_settings_group',
        GARRY_TYPO_OPTION,
        array(
            'type'              => 'array',
            'sanitize_callback' => 'garry_typo_sanitize_settings',
            'default'           => garry_typo_defaults(),
        )
    );
}
add_action('admin_init', 'garry_typo_register_settings');

/* ============================================================================
 * Nahrávání souborů fontů do knihovny médií
 * ----------------------------------------------------------------------------
 * WordPress soubory fontů ve výchozím stavu nepřijímá. Povolujeme je jen
 * správcům webu, tedy uživatelům, kteří stejně mohou instalovat pluginy.
 * ========================================================================= */

function garry_typo_font_mime_types() {
    return array(
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
    );
}

function garry_typo_allow_font_uploads($mimes) {
    if (!current_user_can('manage_options')) {
        return $mimes;
    }

    return array_merge($mimes, garry_typo_font_mime_types());
}
add_filter('upload_mimes', 'garry_typo_allow_font_uploads');

/**
 * PHP u souborů fontů často hlásí obecný typ application/octet-stream,
 * kvůli čemuž by WordPress nahrání odmítl. Pro známé přípony proto typ doplníme.
 */
function garry_typo_fix_font_filetype($data, $file, $filename, $mimes = null, $real_mime = null) {
    if (!empty($data['ext']) && !empty($data['type'])) {
        return $data;
    }

    if (!current_user_can('manage_options')) {
        return $data;
    }

    $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    $font_types = garry_typo_font_mime_types();

    if (isset($font_types[$extension])) {
        $data['ext'] = $extension;
        $data['type'] = $font_types[$extension];
    }

    return $data;
}
add_filter('wp_check_filetype_and_ext', 'garry_typo_fix_font_filetype', 10, 5);

function garry_typo_activate() {
    if (get_option(GARRY_TYPO_OPTION, null) === null) {
        add_option(GARRY_TYPO_OPTION, garry_typo_defaults(), '', false);
    }

    garry_typo_maybe_upgrade();
}
register_activation_hook(__FILE__, 'garry_typo_activate');

/* ============================================================================
 * Povýšení uloženého nastavení na novou verzi
 * ----------------------------------------------------------------------------
 * Uložené nastavení má vždy přednost před výchozími hodnotami, takže samotná
 * aktualizace pluginu by se do stávající konfigurace nijak nepromítla.
 * Tato rutina proto po změně verze doplní to, co nová verze přináší.
 * ========================================================================= */

function garry_typo_maybe_upgrade() {
    /**
     * Bez uloženého záznamu jde o čerstvou instalaci, ne o povyšování
     * staršího webu – ta má dostat garry_typo_defaults() přesně tak, jak
     * je (u GRID Hotel např. custom_font_enabled '0'), ne migraci na font
     * Nagel určenou pro staré instalace CIT. get_option() bez záznamu
     * a get_option() s uloženým prázdným polem od sebe jinak nejde rozeznat.
     */
    if (get_option(GARRY_TYPO_OPTION, null) === null) {
        return;
    }

    $settings = get_option(GARRY_TYPO_OPTION, array());

    if (!is_array($settings)) {
        return;
    }

    $stored_version = isset($settings['plugin_version']) ? (string) $settings['plugin_version'] : '1.0.0';

    if ($stored_version === GARRY_TYPO_VERSION) {
        return;
    }

    $defaults = garry_typo_defaults();
    $changed = false;

    /**
     * 1.1.0 – font Nagel je nově součástí balíčku. Pokud v nastavení není
     * jediný použitelný soubor řezu, dosadíme dodané soubory a registraci
     * zapneme. Vlastní soubory nahrané správcem zůstávají nedotčené.
     */
    $has_usable_face = false;

    if (!empty($settings['custom_font_faces']) && is_array($settings['custom_font_faces'])) {
        foreach ($settings['custom_font_faces'] as $face) {
            if (!empty($face['url']) || !empty($face['url_fallback'])) {
                $has_usable_face = true;
                break;
            }
        }
    }

    if (!$has_usable_face) {
        $settings['custom_font_faces'] = $defaults['custom_font_faces'];
        $settings['custom_font_enabled'] = '1';
        $settings['custom_font_mode'] = 'faces';
        $changed = true;

        /**
         * Úrovně, které dosud používaly jednořezový font z Use Any Font,
         * převedeme na font registrovaný pluginem. Ten má skutečné řezy,
         * takže si prohlížeč tučnost nedopočítává.
         */
        $family = isset($settings['custom_font_family']) ? $settings['custom_font_family'] : 'Nagel';

        if (!empty($settings['elements']) && is_array($settings['elements'])) {
            foreach ($settings['elements'] as $key => $element) {
                if (!is_array($element) || empty($element['font'])) {
                    continue;
                }

                if (strpos($element['font'], 'family:') !== 0) {
                    continue;
                }

                if (strcasecmp(substr($element['font'], 7), $family) === 0) {
                    $settings['elements'][$key]['font'] = 'plugin';
                }
            }
        }

        if (empty($settings['ls_global_font']) || $settings['ls_global_font'] === '') {
            $settings['ls_global_font'] = 'plugin';
            $settings['ls_global_value'] = isset($settings['ls_global_value']) && $settings['ls_global_value'] !== ''
                ? $settings['ls_global_value']
                : $defaults['ls_global_value'];
        }
    }

    /**
     * 1.1.3 – náprava po chybě v ověřování tučnosti. Do verze 1.1.2 se
     * kvůli porovnávání číselných klíčů zahazovala jak tučnost jednotlivých
     * úrovní, tak řezy nahraných souborů písma. V databázi proto zůstaly
     * prázdné hodnoty bez ohledu na to, co bylo ve formuláři vybráno.
     * Doplníme je tedy z doporučeného nastavení.
     */
    if (version_compare($stored_version, '1.1.3', '<')) {
        $recommended = garry_typo_recommended_elements();

        if (!empty($settings['elements']) && is_array($settings['elements'])) {
            foreach ($settings['elements'] as $key => $element) {
                if (!is_array($element)) {
                    continue;
                }

                $is_on = !empty($element['on']) && $element['on'] === '1';
                $has_weight = isset($element['weight']) && $element['weight'] !== '';

                if ($is_on && !$has_weight && !empty($recommended[$key]['weight'])) {
                    $settings['elements'][$key]['weight'] = $recommended[$key]['weight'];
                    $changed = true;
                }
            }
        }

        // Řezy, které se srovnaly na jedinou váhu, vrátíme na dodané hodnoty.
        if (!empty($settings['custom_font_faces']) && is_array($settings['custom_font_faces'])) {
            $weights = array();

            foreach ($settings['custom_font_faces'] as $face) {
                if (isset($face['weight'])) {
                    $weights[] = (string) $face['weight'];
                }
            }

            if (count($weights) > 1 && count(array_unique($weights)) === 1) {
                $settings['custom_font_faces'] = $defaults['custom_font_faces'];
                $changed = true;
            }
        }
    }

    $settings['plugin_version'] = GARRY_TYPO_VERSION;

    if ($changed || $stored_version !== GARRY_TYPO_VERSION) {
        update_option(GARRY_TYPO_OPTION, $settings, false);
    }
}
add_action('admin_init', 'garry_typo_maybe_upgrade', 5);
