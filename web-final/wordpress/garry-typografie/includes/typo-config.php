<?php
/**
 * ============================================================================
 * GARRY – Typografie · konfigurační vrstva
 * ============================================================================
 * Definice typografických úrovní (nadpisy, odstavce, seznamy, tabulky …),
 * jejich CSS selektorů pro WordPress + Divi 5, výchozích hodnot, seznamu
 * dostupných fontů a sanitizace všech vstupů.
 *
 * Tento soubor NEVYKRESLUJE nic. Slouží jako jediný zdroj pravdy pro
 * administraci (typo-admin.php) i pro generátor CSS (typo-frontend.php).
 * ============================================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================================
 * 1) Typografické úrovně a jejich selektory
 * ========================================================================= */

/**
 * Seznam všech typografických úrovní, které plugin umí řídit.
 *
 * Klíč pole = interní klíč nastavení. U každé úrovně:
 *  - label     … název v administraci
 *  - desc      … vysvětlení pro správce webu
 *  - selectors … CSS selektory, na které se nastavení aplikuje
 *  - sample    … typ ukázky v živém náhledu
 *  - extras    … volitelná doplňková pole (zatím jen mezera mezi položkami menu)
 */
function garry_typo_elements() {
    static $elements = null;

    if ($elements !== null) {
        return $elements;
    }

    $elements = array(

        /* ---------- Základní text ---------- */

        'body' => array(
            'label' => 'Základní text webu',
            'desc'  => 'Výchozí písmo celého webu. Všechny ostatní prvky ho dědí, pokud nemají vlastní nastavení.',
            'sample' => 'text',
            'selectors' => array(
                'body',
                '#page-container',
                '.et-l--header',
                '.et-l--body',
                '.et-l--footer',
                '.et_pb_text_inner',
            ),
        ),

        'p' => array(
            'label' => 'Odstavce',
            'desc'  => 'Běžné odstavce v textových modulech i v obsahu příspěvků.',
            'sample' => 'text',
            'selectors' => array(
                'p',
                '.et_pb_text_inner p',
                '.entry-content p',
            ),
        ),

        'a' => array(
            'label' => 'Odkazy v textu',
            'desc'  => 'Odkazy uvnitř obsahu. Netýká se menu ani tlačítek – ty mají vlastní úroveň níže.',
            'sample' => 'link',
            'selectors' => array(
                '.et_pb_text_inner a',
                '.entry-content a',
                '#left-area a',
            ),
        ),

        /* ---------- Nadpisy ---------- */

        'h1' => array(
            'label' => 'Nadpis H1',
            'desc'  => 'Hlavní nadpis stránky.',
            'sample' => 'h1',
            'selectors' => array(
                'h1',
                '.et-l h1',
                '.et_pb_module h1',
                '.et_pb_heading_container h1',
                '.et_pb_title_container h1',
            ),
        ),

        'h2' => array(
            'label' => 'Nadpis H2',
            'desc'  => 'Nadpisy hlavních sekcí stránky.',
            'sample' => 'h2',
            'selectors' => array(
                'h2',
                '.et-l h2',
                '.et_pb_module h2',
                '.et_pb_heading_container h2',
            ),
        ),

        'h3' => array(
            'label' => 'Nadpis H3',
            'desc'  => 'Podnadpisy uvnitř sekcí, často nadpisy karet a medailonků.',
            'sample' => 'h3',
            'selectors' => array(
                'h3',
                '.et-l h3',
                '.et_pb_module h3',
                '.et_pb_heading_container h3',
            ),
        ),

        'h4' => array(
            'label' => 'Nadpis H4',
            'desc'  => 'Nižší úroveň nadpisů, typicky uvnitř sloupců a boxů.',
            'sample' => 'h4',
            'selectors' => array(
                'h4',
                '.et-l h4',
                '.et_pb_module h4',
                '.et_pb_heading_container h4',
            ),
        ),

        'h5' => array(
            'label' => 'Nadpis H5',
            'desc'  => 'Doplňková úroveň nadpisů.',
            'sample' => 'h5',
            'selectors' => array(
                'h5',
                '.et-l h5',
                '.et_pb_module h5',
                '.et_pb_heading_container h5',
            ),
        ),

        'h6' => array(
            'label' => 'Nadpis H6',
            'desc'  => 'Nejnižší úroveň nadpisů, často popisky a mikronadpisy.',
            'sample' => 'h6',
            'selectors' => array(
                'h6',
                '.et-l h6',
                '.et_pb_module h6',
                '.et_pb_heading_container h6',
            ),
        ),

        'module_heading' => array(
            'label' => 'Nadpisy uvnitř Divi modulů',
            'desc'  => 'Divi vykresluje část nadpisů vlastní třídou, ne značkou H1–H6 (nadpisy blurbů, přepínačů, záložek, karet služeb). Bez této úrovně by se na ně nastavení nadpisů nepropsalo.',
            'sample' => 'h3',
            'selectors' => array(
                '.et_pb_module_header',
                '.et_pb_module_heading',
                '.et_pb_toggle_title',
                '.et_pb_tab_title',
                '.et_pb_blurb_title',
            ),
        ),

        /* ---------- Seznamy a tabulky ---------- */

        'ul' => array(
            'label' => 'Odrážkové seznamy',
            'desc'  => 'Položky nečíslovaných seznamů v obsahu stránek.',
            'sample' => 'ul',
            'selectors' => array(
                '.et_pb_text_inner ul li',
                '.entry-content ul li',
                '#left-area ul li',
            ),
        ),

        'ol' => array(
            'label' => 'Číslované seznamy',
            'desc'  => 'Položky číslovaných seznamů v obsahu stránek.',
            'sample' => 'ol',
            'selectors' => array(
                '.et_pb_text_inner ol li',
                '.entry-content ol li',
                '#left-area ol li',
            ),
        ),

        'table_td' => array(
            'label' => 'Tabulky – buňky',
            'desc'  => 'Běžné buňky tabulek. Na webu se týká například tabulky ordinační doby.',
            'sample' => 'table',
            'selectors' => array(
                'table td',
                '.entry-content table td',
                '.et_pb_text_inner table td',
            ),
        ),

        'table_th' => array(
            'label' => 'Tabulky – záhlaví',
            'desc'  => 'Hlavičkové buňky tabulek.',
            'sample' => 'table',
            'selectors' => array(
                'table th',
                '.entry-content table th',
                '.et_pb_text_inner table th',
            ),
        ),

        /* ---------- Ovládací prvky a doplňky ---------- */

        'menu' => array(
            'label' => 'Hlavní menu',
            'desc'  => 'Položky horní navigace. Kromě písma lze nastavit i vodorovnou mezeru mezi položkami – to řeší připomínku, že položky menu působí natěsno.',
            'sample' => 'menu',
            'selectors' => array(
                '.et_pb_menu .et-menu > li > a',
                '.et-menu li a',
                '#top-menu li a',
                '.et_pb_menu__menu nav ul li a',
                '.et_mobile_menu li a',
            ),
            'extras' => array('menu_item_gap'),
        ),

        'section_nav' => array(
            'label' => 'Zelený pruh s odkazy na sekce',
            'desc'  => 'Zelený pás pod hlavičkou s ikonami (Péče o vás, Naše vize, Náš tým …). Právě tady je na webu nastavený rozpal 2 px, který korektura vytýkala. Pokud pruh vykresluje vlastní snippet s jinou třídou, dopište ji do doplňkových selektorů.',
            'sample' => 'menu',
            'selectors' => array(
                '.col-service .et_pb_module_heading',
                '.col-service .et_pb_blurb_title',
                '.col-service a',
            ),
        ),

        'anchor_nav' => array(
            'label' => 'Boční seznam odkazů na sekce',
            'desc'  => 'Svislý seznam odkazů na podsekce stránky, zobrazený vedle obsahu. Řídí se zde jen písmo; chování a rozvržení zůstává na snippetu, který menu vykresluje.',
            'sample' => 'menu',
            'selectors' => array(
                '.cit-anchor-nav a',
                '.cit-bocni-menu a',
                '.et_pb_widget_area a',
            ),
        ),

        'button' => array(
            'label' => 'Tlačítka',
            'desc'  => 'Tlačítka Divi i tlačítka WordPressu (například „Objednejte se“).',
            'sample' => 'button',
            'selectors' => array(
                '.et_pb_button',
                '.wp-element-button',
                '.wp-block-button__link',
                'input[type="submit"]',
            ),
        ),

        'toggle_button' => array(
            'label' => 'Odkaz „Přečíst více…“',
            'desc'  => 'Tlačítko rozbalovacího textu z pluginu Rozbalovací texty. Právě u něj korektura vytýkala příliš velký rozpal písmen. Selektory jsou zde záměrně zapsané se zdvojenou třídou, aby toto nastavení přebilo nastavení druhého pluginu.',
            'sample' => 'toggle',
            'selectors' => array(
                '.divi-toggle-text .divi-text-expand-button.divi-text-expand-button',
                '.divi-toggle-text2 .divi-text-expand-button.divi-text-expand-button',
                '.divi-toggle-text .divi-text-collapse-button.divi-text-collapse-button',
                '.divi-toggle-text2 .divi-text-collapse-button.divi-text-collapse-button',
            ),
        ),

        'blockquote' => array(
            'label' => 'Citace',
            'desc'  => 'Bloky citací (blockquote).',
            'sample' => 'text',
            'selectors' => array(
                'blockquote',
                'blockquote p',
            ),
        ),

        'caption' => array(
            'label' => 'Popisky obrázků',
            'desc'  => 'Popisky pod obrázky a v galeriích.',
            'sample' => 'text',
            'selectors' => array(
                'figcaption',
                '.wp-caption-text',
                '.et_pb_image_wrap figcaption',
            ),
        ),

        'form' => array(
            'label' => 'Formulářové prvky',
            'desc'  => 'Vstupní pole, výběry a popisky ve formulářích – typicky kontaktní formulář.',
            'sample' => 'form',
            'selectors' => array(
                'input',
                'textarea',
                'select',
                'label',
                '.et_pb_contact_form_label',
            ),
        ),
    );

    return $elements;
}

/**
 * Rozdělení úrovní do skupin – jen kvůli přehlednosti administrace.
 */
function garry_typo_element_groups() {
    return array(
        array(
            'label'    => 'Základní text',
            'desc'     => 'Písmo, ze kterého dědí celý web.',
            'elements' => array('body', 'p', 'a'),
        ),
        array(
            'label'    => 'Nadpisy',
            'desc'     => 'Jednotlivé úrovně nadpisů plus nadpisy, které si Divi vykresluje vlastní třídou.',
            'elements' => array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'module_heading'),
        ),
        array(
            'label'    => 'Seznamy a tabulky',
            'desc'     => 'Odrážkové i číslované seznamy a buňky tabulek.',
            'elements' => array('ul', 'ol', 'table_td', 'table_th'),
        ),
        array(
            'label'    => 'Menu, tlačítka a doplňky',
            'desc'     => 'Navigace, tlačítka a další prvky, které mají vlastní typografii.',
            'elements' => array('menu', 'section_nav', 'anchor_nav', 'button', 'toggle_button', 'blockquote', 'caption', 'form'),
        ),
    );
}

/* ============================================================================
 * 2) Fonty – detekce, nabídka, převod na CSS
 * ========================================================================= */

/**
 * Načte @font-face deklarace z CSS souboru pluginu Use Any Font.
 * Vrací pole: family => array('weights' => [], 'files' => [], 'display' => '').
 */
function garry_typo_detect_uaf_fonts() {
    static $fonts = null;

    if ($fonts !== null) {
        return $fonts;
    }

    $fonts = array();
    $upload = wp_upload_dir();

    if (empty($upload['basedir'])) {
        return $fonts;
    }

    $path = trailingslashit($upload['basedir']) . 'useanyfont/uaf.css';

    if (!is_readable($path)) {
        return $fonts;
    }

    $css = file_get_contents($path);

    if ($css === false || $css === '') {
        return $fonts;
    }

    if (!preg_match_all('/@font-face\s*\{([^}]*)\}/i', $css, $blocks)) {
        return $fonts;
    }

    foreach ($blocks[1] as $block) {
        if (!preg_match('/font-family\s*:\s*([^;]+);/i', $block, $family_match)) {
            continue;
        }

        $family = trim($family_match[1]);
        $family = trim($family, "\"' \t");

        if ($family === '') {
            continue;
        }

        if (!isset($fonts[$family])) {
            $fonts[$family] = array(
                'weights' => array(),
                'files'   => array(),
                'display' => '',
            );
        }

        if (preg_match('/font-weight\s*:\s*([^;]+);/i', $block, $weight_match)) {
            $weight = trim($weight_match[1]);
            if ($weight !== '' && !in_array($weight, $fonts[$family]['weights'], true)) {
                $fonts[$family]['weights'][] = $weight;
            }
        }

        if (preg_match('/font-display\s*:\s*([^;]+);/i', $block, $display_match)) {
            $fonts[$family]['display'] = trim($display_match[1]);
        }

        if (preg_match_all('/url\(\s*[\'"]?([^\'")]+)/i', $block, $url_matches)) {
            foreach ($url_matches[1] as $url) {
                $url = trim($url);
                if ($url !== '' && !in_array($url, $fonts[$family]['files'], true)) {
                    $fonts[$family]['files'][] = $url;
                }
            }
        }
    }

    return $fonts;
}

/**
 * Pevně dané fontové sady, které jsou k dispozici vždy.
 */
function garry_typo_preset_font_stacks() {
    return array(
        'arial'     => array('Arial', 'Arial, Helvetica, sans-serif'),
        'helvetica' => array('Helvetica', '"Helvetica Neue", Helvetica, Arial, sans-serif'),
        'georgia'   => array('Georgia', 'Georgia, "Times New Roman", serif'),
        'times'     => array('Times New Roman', '"Times New Roman", Times, serif'),
        'courier'   => array('Courier New', '"Courier New", Courier, monospace'),
        'system'    => array('Systémový font', 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif'),
    );
}

/**
 * Nabídka fontů pro výběrová pole v administraci.
 * Klíč '' znamená „tuto vlastnost neměnit".
 */
function garry_typo_font_choices($settings = null) {
    $choices = array(
        ''        => '— neměnit —',
        'inherit' => 'Zdědit z nadřazeného prvku',
    );

    if (is_array($settings) && !empty($settings['custom_font_enabled']) && $settings['custom_font_enabled'] === '1') {
        $family = isset($settings['custom_font_family']) ? $settings['custom_font_family'] : 'Nagel';
        $choices['plugin'] = $family . ' (registrovaný tímto pluginem)';
    }

    foreach (garry_typo_detect_uaf_fonts() as $family => $info) {
        $choices['family:' . $family] = $family . ' (Use Any Font)';
    }

    foreach (garry_typo_preset_font_stacks() as $key => $stack) {
        $choices[$key] = $stack[0];
    }

    /**
     * Fonty, které jsou v nastavení uložené, ale na webu se je nepodařilo najít
     * (například byl odstraněn plugin Use Any Font). Bez tohoto doplnění by
     * výběrové pole nemělo odpovídající položku a uložení formuláře by nastavení
     * potichu zahodilo.
     */
    if (is_array($settings) && !empty($settings['elements']) && is_array($settings['elements'])) {
        foreach ($settings['elements'] as $element) {
            if (empty($element['font']) || isset($choices[$element['font']])) {
                continue;
            }

            if (strpos($element['font'], 'family:') === 0) {
                $choices[$element['font']] = substr($element['font'], 7) . ' (v nastavení, ale na webu nenalezen)';
            }
        }
    }

    return $choices;
}

/**
 * Převede uloženou volbu fontu na hodnotu CSS font-family.
 * Vrací prázdný řetězec, pokud se vlastnost nemá vůbec vypisovat.
 */
function garry_typo_font_stack($key, $settings) {
    $key = (string) $key;

    if ($key === '') {
        return '';
    }

    if ($key === 'inherit') {
        return 'inherit';
    }

    $fallback = isset($settings['fallback_stack']) && $settings['fallback_stack'] !== ''
        ? $settings['fallback_stack']
        : 'Arial, Helvetica, sans-serif';

    if ($key === 'plugin') {
        $family = isset($settings['custom_font_family']) ? $settings['custom_font_family'] : 'Nagel';

        // Kromě vlastní registrace zkusíme i variantu z Use Any Font (jiné psaní velkých písmen).
        $uaf = array_keys(garry_typo_detect_uaf_fonts());
        $extra = '';

        foreach ($uaf as $uaf_family) {
            if (strcasecmp($uaf_family, $family) === 0 && $uaf_family !== $family) {
                $extra = '"' . $uaf_family . '", ';
                break;
            }
        }

        return '"' . $family . '", ' . $extra . $fallback;
    }

    if (strpos($key, 'family:') === 0) {
        $family = substr($key, 7);

        return '"' . $family . '", ' . $fallback;
    }

    $presets = garry_typo_preset_font_stacks();

    return isset($presets[$key]) ? $presets[$key][1] : '';
}

/**
 * Nabídka tučností písma.
 */
function garry_typo_weight_choices() {
    return array(
        ''    => '— neměnit —',
        '100' => '100 – tenké',
        '200' => '200 – velmi lehké',
        '300' => '300 – lehké',
        '400' => '400 – normální',
        '500' => '500 – střední',
        '600' => '600 – polotučné',
        '700' => '700 – tučné',
        '800' => '800 – velmi tučné',
        '900' => '900 – černé',
    );
}

/**
 * Nabídka převodu velikosti písmen.
 */
function garry_typo_transform_choices() {
    return array(
        ''           => '— neměnit —',
        'none'       => 'Beze změny (none)',
        'uppercase'  => 'VELKÁ PÍSMENA',
        'lowercase'  => 'malá písmena',
        'capitalize' => 'První Písmena Velká',
    );
}

/* ============================================================================
 * Barvy – paleta webu a tmavé sekce
 * ========================================================================= */

/**
 * Barevná paleta nabízená u nastavení barev.
 * Základ tvoří firemní barvy CIT, doplněné o barvy nalezené v nastavení Divi.
 */
function garry_typo_color_palette() {
    $palette = array(
        '#cd9f67' => 'Béžová – nadpisy',
        '#68c020' => 'Světle zelená',
        '#003f00' => 'Tmavě zelená – text',
        '#12350e' => 'Zelené pozadí',
        '#ffffff' => 'Bílá',
        '#f9f4f1' => 'Krémová',
        '#f0e2d1' => 'Světle béžová',
        '#222222' => 'Tmavý text',
    );

    $option_names = array('et_divi', 'et_pb_color_palette', 'et_global_colors', 'et_divi_design_variables');

    foreach ($option_names as $option_name) {
        $value = get_option($option_name);

        if ($value === false || $value === null || $value === '') {
            continue;
        }

        $found = array();
        garry_typo_collect_hex_colors($value, $found);

        foreach ($found as $hex) {
            $key = strtolower($hex);

            if (!isset($palette[$key])) {
                $palette[$key] = 'Barva z Divi ' . strtoupper($hex);
            }
        }
    }

    return $palette;
}

/**
 * Rekurzivně posbírá šestimístné HEX barvy z libovolné struktury nastavení.
 */
function garry_typo_collect_hex_colors($value, &$found) {
    if (is_string($value)) {
        if (preg_match_all('/#[0-9a-fA-F]{6}\b/', $value, $matches)) {
            foreach ($matches[0] as $hex) {
                $hex = strtolower($hex);

                if (!in_array($hex, $found, true)) {
                    $found[] = $hex;
                }
            }
        }

        return;
    }

    if (is_array($value) || is_object($value)) {
        foreach ((array) $value as $sub) {
            garry_typo_collect_hex_colors($sub, $found);
        }
    }
}

/**
 * Nabídka způsobu registrace vlastního fontu.
 */
function garry_typo_font_mode_choices() {
    return array(
        'faces'    => 'Více samostatných řezů (Regular, Medium, Bold …) – doporučeno',
        'variable' => 'Jeden variabilní soubor pokrývající rozsah řezů',
    );
}

/**
 * Nabídka řezů písma pro seznam nahraných souborů.
 */
function garry_typo_face_weight_choices() {
    return array(
        '100' => '100 – Thin',
        '200' => '200 – ExtraLight',
        '300' => '300 – Light',
        '400' => '400 – Regular',
        '500' => '500 – Medium',
        '600' => '600 – SemiBold',
        '700' => '700 – Bold',
        '800' => '800 – ExtraBold',
        '900' => '900 – Black',
    );
}

/**
 * Nabídka řezu (normální / kurziva).
 */
function garry_typo_face_style_choices() {
    return array(
        'normal' => 'normální',
        'italic' => 'kurziva',
    );
}

/**
 * Nabídka síly přepisu cizích stylů.
 *
 * „normal“ vypisuje běžné selektory s !important. To bezpečně přebije
 * globální nastavení motivu Divi i běžné styly WordPressu.
 *
 * „high“ navíc předřadí trojitý :root, čímž zvedne specificitu natolik,
 * že přebije i styly nastavené u jednotlivých Divi modulů, vlastní CSS
 * v možnostech motivu a třídy typu .has-nagel-font-family.
 */
function garry_typo_strength_choices() {
    return array(
        'normal' => 'Běžná – přebije globální nastavení Divi a WordPressu',
        'high'   => 'Vysoká – přebije i nastavení jednotlivých modulů a vlastní CSS',
    );
}

/**
 * Nabídka zarovnání textu.
 */
function garry_typo_align_choices() {
    return array(
        ''        => '— neměnit —',
        'left'    => 'vlevo',
        'center'  => 'na střed',
        'right'   => 'vpravo',
        'justify' => 'do bloku',
    );
}

/**
 * Nabídka podtržení textu.
 */
function garry_typo_decoration_choices() {
    return array(
        ''             => '— neměnit —',
        'none'         => 'bez podtržení',
        'underline'    => 'podtržené',
        'line-through' => 'přeškrtnuté',
    );
}

/**
 * Nabídka chování při načítání webfontu.
 */
function garry_typo_font_display_choices() {
    return array(
        'swap'     => 'swap – text je hned vidět náhradním fontem (doporučeno)',
        'fallback' => 'fallback – krátká pauza, pak náhradní font',
        'optional' => 'optional – prohlížeč může webfont vynechat',
        'auto'     => 'auto – rozhodne prohlížeč',
        'block'    => 'block – text se do načtení fontu nezobrazí',
    );
}

/* ============================================================================
 * 3) Sanitizace
 * ========================================================================= */

/**
 * Bezpečná hodnota CSS vlastnosti (velikost, řádkování, rozpal).
 * Prázdný řetězec = vlastnost se nebude vypisovat.
 */
function garry_typo_sanitize_css_value($value, $max_length = 60) {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (strlen($value) > $max_length) {
        return '';
    }

    if (preg_match('/[;{}<>\\\\]/', $value)) {
        return '';
    }

    $lower = strtolower($value);

    foreach (array('url(', 'expression', 'javascript', '@import', 'var(') as $forbidden) {
        if (strpos($lower, $forbidden) !== false) {
            return '';
        }
    }

    if (!preg_match('/^[0-9a-zA-Z\s\.\,\-\+\*\/\(\)%]+$/', $value)) {
        return '';
    }

    return $value;
}

/**
 * Bezpečný seznam doplňkových CSS selektorů.
 */
function garry_typo_sanitize_selector_list($value) {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (strlen($value) > 300) {
        return '';
    }

    // Znak > je platný kombinátor potomka, proto se nezakazuje.
    if (preg_match('/[{}<;\\\\@]/', $value)) {
        return '';
    }

    if (stripos($value, 'url(') !== false) {
        return '';
    }

    if (!preg_match('/^[a-zA-Z0-9\s\.\#\-\_\,\>\+\~\:\(\)\[\]\=\"\']+$/', $value)) {
        return '';
    }

    return $value;
}

/**
 * Bezpečná volba fontu.
 */
function garry_typo_sanitize_font_key($value) {
    $value = trim((string) $value);

    if ($value === '' || $value === 'inherit' || $value === 'plugin') {
        return $value;
    }

    $presets = garry_typo_preset_font_stacks();

    if (isset($presets[$value])) {
        return $value;
    }

    if (preg_match('/^family:[A-Za-z0-9 _\-]{1,60}$/', $value)) {
        return $value;
    }

    return '';
}

/**
 * Převede zkrácený zápis plugin:soubor.woff2 na skutečnou adresu souboru
 * dodaného přímo s pluginem. Díky tomu nastavení přežije přesun webu
 * na jinou doménu i přejmenování složky pluginu.
 */
function garry_typo_resolve_font_url($url) {
    $url = (string) $url;

    if (strpos($url, 'plugin:') !== 0) {
        return $url;
    }

    return GARRY_TYPO_URL . 'assets/fonts/' . basename(substr($url, 7));
}

/**
 * Bezpečná adresa souboru fontu.
 * Povoluje relativní cestu i absolutní URL, jen pro známé formáty webfontů.
 */
function garry_typo_sanitize_font_url($value, $fallback = '') {
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (strlen($value) > 500) {
        return $fallback;
    }

    // Soubor dodaný s pluginem.
    if (preg_match('/^plugin:[A-Za-z0-9._\-]+\.(woff2|woff|ttf|otf)$/i', $value)) {
        return $value;
    }

    $path = strtok($value, '?#');
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

    if (!in_array($extension, array('woff2', 'woff', 'ttf', 'otf'), true)) {
        return $fallback;
    }

    if (strpos($value, '/') === 0) {
        // Relativní cesta v rámci webu.
        if (preg_match('#^/[A-Za-z0-9/._\-]+$#', $value)) {
            return $value;
        }

        return $fallback;
    }

    $clean = esc_url_raw($value);

    return $clean !== '' ? $clean : $fallback;
}

/**
 * Formát webfontu podle přípony souboru – pro deklaraci @font-face.
 */
function garry_typo_font_format($url) {
    $path = strtok((string) $url, '?#');
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

    $map = array(
        'woff2' => 'woff2',
        'woff'  => 'woff',
        'ttf'   => 'truetype',
        'otf'   => 'opentype',
    );

    return isset($map[$extension]) ? $map[$extension] : '';
}

/* ============================================================================
 * 4) Výchozí hodnoty
 * ========================================================================= */

/**
 * Prázdná úroveň – nic se nevypisuje.
 */
function garry_typo_empty_element_defaults() {
    return array(
        'on'     => '0',
        'font'   => '',
        'weight' => '',
        'size'   => '',
        'size_t' => '',
        'size_m' => '',
        'lh'     => '',
        'ls'     => '',
        'tt'     => '',
        /**
         * Tmavý protějšek ke každé z vlastností výše (kromě 'on', to je
         * jeden vypínač pro obě varianty). Prázdná hodnota = na tmavém
         * pozadí se použije stejná hodnota jako na světlém (žádné tiché
         * "zmizení" nastavení, jen doplnění pro pozadí, kde se má lišit).
         */
        'font_dark'   => '',
        'weight_dark' => '',
        'size_dark'   => '',
        'size_t_dark' => '',
        'size_m_dark' => '',
        'lh_dark'     => '',
        'ls_dark'     => '',
        'tt_dark'     => '',
        'color'  => '',
        'color_dark' => '',
        'color_hover' => '',
        'color_hover_dark' => '',
        'mb'     => '',
        'pad'    => '',
        'align'  => '',
        'deco'   => '',
        'sel'    => '',
    );
}

/**
 * Doporučené nastavení pro web CIT: nadpisy Nagel, ostatní text Arial,
 * všude vynulovaný rozpal písma.
 *
 * Používá se jako výchozí hodnota po instalaci i jako tlačítko
 * „Načíst doporučené nastavení" v administraci.
 */
function garry_typo_recommended_elements() {
    $empty = garry_typo_empty_element_defaults();

    /**
     * Doporučené hodnoty vycházejí ze snippetu „CIT – Základní typografie WCAG“,
     * který tento plugin nahrazuje: fluidní velikosti nadpisů přes clamp(),
     * řádkování 1.7, spodní mezery a podtržené odkazy. Doplněné jsou barvy
     * pro světlé i zelené pozadí a font Nagel u nadpisů.
     */

    // Béžové nadpisy sekcí – stejné na světlém i zeleném pozadí.
    $heading_beige = array(
        'on'         => '1',
        'font'       => 'plugin',
        'weight'     => '700',
        'lh'         => '1.3',
        'ls'         => '',
        'mb'         => '0.6em',
        'color'      => '#cd9f67',
        'color_dark' => '#cd9f67',
    );

    // Zelené nadpisy, které musí na zeleném pozadí zbělat.
    $heading_green = array(
        'on'         => '1',
        'font'       => 'plugin',
        'weight'     => '700',
        'lh'         => '1.3',
        'ls'         => '',
        'mb'         => '0.6em',
        'color'      => '#68c020',
        'color_dark' => '#ffffff',
    );

    // Běžný text – tmavě zelený, na zeleném pozadí bílý.
    $text_colors = array(
        'color'      => '#003f00',
        'color_dark' => '#ffffff',
    );

    $elements = array(
        'body' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'arial',
            'weight' => '400',
            'size'   => '1rem',
            'lh'     => '1.7',
            'ls'     => 'normal',
        ), $text_colors),

        'p' => array_merge($empty, array(
            'on'   => '1',
            'font' => 'arial',
            'size' => '1rem',
            'lh'   => '1.7',
            'ls'   => 'normal',
            'mb'   => '1em',
        ), $text_colors),

        'a' => array_merge($empty, array(
            'on'               => '1',
            'ls'               => 'normal',
            'deco'             => 'underline',
            'color'            => '#003f00',
            'color_dark'       => '#ffffff',
            'color_hover'      => '#68c020',
            'color_hover_dark' => '#68c020',
        )),

        'h1' => array_merge($empty, $heading_beige, array('size' => 'clamp(2rem, 1.8rem + 1vw, 3rem)')),
        'h2' => array_merge($empty, $heading_beige, array('size' => 'clamp(1.75rem, 1.5rem + 0.8vw, 2.5rem)')),
        'h3' => array_merge($empty, $heading_green, array('size' => 'clamp(1.5rem, 1.3rem + 0.6vw, 2rem)')),
        'h4' => array_merge($empty, $heading_green, array('size' => 'clamp(1.25rem, 1.1rem + 0.4vw, 1.6rem)')),
        'h5' => array_merge($empty, $heading_green, array('size' => 'clamp(1.125rem, 1rem + 0.3vw, 1.4rem)')),
        'h6' => array_merge($empty, $heading_green, array('size' => 'clamp(1rem, 0.95rem + 0.2vw, 1.2rem)')),
        'module_heading' => array_merge($empty, $heading_green),

        'ul' => array_merge($empty, array(
            'on'   => '1',
            'font' => 'arial',
            'size' => '1rem',
            'lh'   => '1.7',
            'ls'   => 'normal',
            'mb'   => '0.4em',
        ), $text_colors),

        'ol' => array_merge($empty, array(
            'on'   => '1',
            'font' => 'arial',
            'size' => '1rem',
            'lh'   => '1.7',
            'ls'   => 'normal',
            'mb'   => '0.4em',
        ), $text_colors),

        'table_td' => array_merge($empty, array(
            'on'   => '1',
            'font' => 'arial',
            'size' => '1rem',
            'lh'   => '1.5',
            'ls'   => 'normal',
            'pad'  => '0.5em',
        ), $text_colors),

        'table_th' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'arial',
            'size'   => '1rem',
            'weight' => '700',
            'lh'     => '1.5',
            'ls'     => 'normal',
            'pad'    => '0.5em',
            'align'  => 'left',
        ), $text_colors),

        'menu' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'plugin',
            'weight' => '700',
        )),

        'button' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'plugin',
            'weight' => '700',
        )),

        'toggle_button' => array_merge($empty, array(
            'on'               => '1',
            'font'             => 'family:nagel',
            'weight'           => '700',
            'ls'               => 'normal',
            'color'            => '#003f00',
            'color_dark'       => '#ffffff',
            'color_hover'      => '#68c020',
            'color_hover_dark' => '#68c020',
        )),

        'section_nav' => array_merge($empty, array(
            'on'         => '1',
            'font'       => 'plugin',
            'weight'     => '700',
            'color_dark' => '#ffffff',
        )),

        'anchor_nav' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'plugin',
            'weight' => '700',
        )),

        'blockquote' => $empty,
        'caption'    => $empty,

        'form' => array_merge($empty, array(
            'on'   => '1',
            'font' => 'arial',
            'ls'   => 'normal',
        )),
    );

    // Jistota, že seznam vždy odpovídá definici úrovní.
    foreach (garry_typo_elements() as $key => $definition) {
        if (!isset($elements[$key])) {
            $elements[$key] = $empty;
        }
    }

    return $elements;
}

/**
 * Doporučené nastavení pro GRID Hotel: fonty, velikosti, řádkování i barvy
 * vycházejí přímo z aktuálního child theme (style.css) — `--f-head:'Saira
 * Condensed'` pro nadpisy, `--f-body:'Inter'` pro běžný text, stejná
 * tučnost/rozpal/transformace jako u globálního `h1,h2,h3{}` pravidla v
 * theme. Barvy nadpisů/textu odpovídají theme proměnným `--fg`/`--muted`,
 * jak je nastavují `.sec-light`/`.sec-dark` – jde o stejné hodnoty, jaké web
 * dnes používá (u odstavců přes `style="color:var(--muted)"`), takže
 * vyplnění nic nemění, jen to sjednocuje na jedno místo v adminu.
 *
 * Světlá/tmavá varianta zde odpovídá theme dvojici `.sec-light`/`.sec-dark`
 * (viz `dark_context` níže v garry_typo_defaults()), NE Divi vlastnímu
 * `.et_pb_section_dark` (to je CIT-specifický výchozí kontext).
 */
function garry_typo_grid_hotel_elements() {
    $empty = garry_typo_empty_element_defaults();

    $heading_shared = array(
        'on'         => '1',
        'font'       => 'family:Saira Condensed',
        'weight'     => '700',
        'lh'         => '.95',
        'ls'         => '.01em',
        'tt'         => 'uppercase',
        /**
         * Odpovídá theme proměnné --fg, jak ji nastavují .sec-light/.sec-dark
         * v style.css (žádná barva na nadpisech není přes inline style
         * přebíjená, takže tady bezpečně jde vyplnit přímo).
         */
        'color'      => '#16181B',
        'color_dark' => '#F4F2F0',
    );

    $body_shared = array(
        'on'         => '1',
        'font'       => 'family:Inter',
        'weight'     => '400',
        'lh'         => '1.65',
        /**
         * Odpovídá theme proměnné --muted – stejná hodnota, jakou dnes web
         * nastavuje ručně přes style="color:var(--muted)" u odstavců, takže
         * vyplnění stejné barvy tady nic nemění, jen to sjednocuje na
         * jedno místo.
         */
        'color'      => '#5b5f66',
        'color_dark' => '#B9B7B9',
    );

    $elements = array(
        'body' => array_merge($empty, $body_shared),
        'p'    => array_merge($empty, $body_shared, array('lh' => '1.65')),
        'a'    => $empty,

        'h1' => array_merge($empty, $heading_shared, array('size' => 'clamp(2.6rem,7vw,6.5rem)')),
        'h2' => array_merge($empty, $heading_shared, array('size' => 'clamp(2rem,4vw,3.6rem)')),
        'h3' => array_merge($empty, $heading_shared),
        'h4' => $empty,
        'h5' => $empty,
        'h6' => $empty,
        'module_heading' => array_merge($empty, $heading_shared),

        'ul' => array_merge($empty, array('on' => '1', 'font' => 'family:Inter')),
        'ol' => array_merge($empty, array('on' => '1', 'font' => 'family:Inter')),
        'table_td' => array_merge($empty, array('on' => '1', 'font' => 'family:Inter')),
        'table_th' => array_merge($empty, array('on' => '1', 'font' => 'family:Inter', 'weight' => '700')),

        'menu' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'family:JetBrains Mono',
            'weight' => '400',
            'size'   => '.74rem',
            'ls'     => '.12em',
            'tt'     => 'uppercase',
        )),

        'button' => array_merge($empty, array(
            'on'     => '1',
            'font'   => 'family:JetBrains Mono',
            'weight' => '400',
            'size'   => '.74rem',
            'ls'     => '.14em',
            'tt'     => 'uppercase',
        )),

        'toggle_button' => $empty,
        'section_nav'   => $empty,
        'anchor_nav'    => $empty,
        'blockquote'    => $empty,
        'caption'       => $empty,
        'form'          => array_merge($empty, array('on' => '1', 'font' => 'family:Inter')),
    );

    foreach (garry_typo_elements() as $key => $definition) {
        if (!isset($elements[$key])) {
            $elements[$key] = $empty;
        }
    }

    return $elements;
}

/**
 * Výchozí nastavení celého pluginu.
 * Hlavní vypínač je záměrně vypnutý – aktivace pluginu tak sama o sobě
 * vzhled webu nezmění, dokud správce nastavení vědomě nezapne.
 */
function garry_typo_defaults() {
    return array(
        'enabled'          => '0',
        'force_important'  => '1',
        'override_strength' => 'normal',
        'apply_in_builder' => '1',

        'fallback_stack'   => 'Arial, Helvetica, sans-serif',

        /**
         * GRID Hotel nepoužívá vlastní nahraný font (na rozdíl od CIT s
         * Nagel) — Saira Condensed/Inter/JetBrains Mono jsou Google Fonts,
         * které si theme už načítá samo, proto tady zůstává tenhle
         * registrační mechanismus vypnutý (žádné zbytečné/chybějící
         * @font-face pro font, který web vůbec nepoužívá). Elementy výše
         * proto referencují fonty přes 'family:X', ne 'plugin'.
         */
        'custom_font_enabled'    => '0',
        'custom_font_family'     => '',
        'custom_font_mode'       => 'faces',
        'custom_font_display'    => 'swap',

        /**
         * Jednotlivé nahrané řezy. Každý řez je samostatná deklarace @font-face
         * se stejným názvem rodiny, takže si prohlížeč sám vybere správný soubor
         * podle požadované tučnosti a tučnost si nedopočítává.
         *
         * Záměrně prázdné ve výchozím stavu – balíček s pluginem sice pořád
         * nese soubory fontu Nagel (assets/fonts/), ale ten patřil původnímu
         * klientovi CIT. Nový klient si vlastní font zvolí sám přes admin UI;
         * neměl by se mu tam objevit cizí název fontu, který nepoužívá.
         */
        'custom_font_faces'      => array(),

        // Použije se pouze v režimu jednoho variabilního souboru.
        'custom_font_url'        => '',
        'custom_font_weight_min' => '100',
        'custom_font_weight_max' => '900',

        'menu_item_gap'    => '',

        /**
         * Společný rozpal písmen. Použije se u každé zapnuté úrovně, která
         * má vybraný níže uvedený font a zároveň nemá vyplněný vlastní rozpal.
         */
        'ls_global_value'  => 'normal',
        'ls_global_font'   => 'plugin',

        /**
         * Selektory, podle kterých se pozná tmavá sekce. GRID Hotel theme
         * značí tmavé sekce třídou .sec-dark (viz style.css) — Divi vlastní
         * .et_pb_section_dark/.et_pb_bg_layout_dark tady nic neznamená,
         * proto oba přidáváme, ne jen jeden.
         */
        'dark_context'     => '.sec-dark, .et_pb_bg_layout_dark, .et_pb_section_dark',

        /**
         * Prefix pro globální třídy, které plugin generuje navíc k
         * obvyklému tag/Divi-selektor stylování (např. .garry-typo-h1) —
         * jde ručně přiřadit libovolnému modulu v Divi builderu přes pole
         * "CSS Class", nezávisle na tom, jaký HTML tag modul zrovna
         * vykresluje.
         */
        'class_prefix'     => 'garry-typo',

        'elements'         => garry_typo_grid_hotel_elements(),
    );
}

/**
 * Uložené nastavení doplněné o výchozí hodnoty.
 */
function garry_typo_get_settings() {
    $saved = get_option(GARRY_TYPO_OPTION, array());

    if (!is_array($saved)) {
        $saved = array();
    }

    $defaults = garry_typo_defaults();
    $settings = array_merge($defaults, $saved);

    $elements = isset($saved['elements']) && is_array($saved['elements']) ? $saved['elements'] : array();
    $empty = garry_typo_empty_element_defaults();

    $merged = array();

    foreach (garry_typo_elements() as $key => $definition) {
        $stored = isset($elements[$key]) && is_array($elements[$key]) ? $elements[$key] : array();
        $base = isset($defaults['elements'][$key]) ? $defaults['elements'][$key] : $empty;

        $merged[$key] = array_merge($empty, $base, $stored);
    }

    $settings['elements'] = $merged;

    return $settings;
}

/**
 * Sanitizace celého nastavení před uložením do databáze.
 */
function garry_typo_sanitize_settings($input) {
    $defaults = garry_typo_defaults();

    if (!is_array($input)) {
        return $defaults;
    }

    $output = array();

    // Značka verze, podle které se pozná potřeba povýšit nastavení.
    $output['plugin_version'] = GARRY_TYPO_VERSION;

    $output['enabled']          = !empty($input['enabled']) ? '1' : '0';
    $output['force_important']  = !empty($input['force_important']) ? '1' : '0';
    $output['apply_in_builder'] = !empty($input['apply_in_builder']) ? '1' : '0';

    $strength_choices = array_keys(garry_typo_strength_choices());
    $output['override_strength'] = isset($input['override_strength']) && in_array($input['override_strength'], $strength_choices, true)
        ? $input['override_strength']
        : $defaults['override_strength'];

    $fallback = isset($input['fallback_stack']) ? trim((string) $input['fallback_stack']) : '';
    $fallback = preg_replace('/[;{}<>\\\\]/', '', $fallback);
    $output['fallback_stack'] = $fallback !== '' ? $fallback : $defaults['fallback_stack'];

    $output['custom_font_enabled'] = !empty($input['custom_font_enabled']) ? '1' : '0';

    $family = isset($input['custom_font_family']) ? trim((string) $input['custom_font_family']) : '';
    $family = preg_replace('/[^A-Za-z0-9 _\-]/', '', $family);
    $output['custom_font_family'] = $family !== '' ? substr($family, 0, 60) : $defaults['custom_font_family'];

    $mode_choices = array_keys(garry_typo_font_mode_choices());
    $output['custom_font_mode'] = isset($input['custom_font_mode']) && in_array($input['custom_font_mode'], $mode_choices, true)
        ? $input['custom_font_mode']
        : $defaults['custom_font_mode'];

    $output['custom_font_url'] = garry_typo_sanitize_font_url(
        isset($input['custom_font_url']) ? $input['custom_font_url'] : '',
        ''
    );

    // Seznam nahraných řezů.
    // Stejná past s číselnými klíči jako u tučnosti textu výše.
    $face_weights = array_map('strval', array_keys(garry_typo_face_weight_choices()));
    $face_styles = array_keys(garry_typo_face_style_choices());
    $output['custom_font_faces'] = array();

    if (isset($input['custom_font_faces']) && is_array($input['custom_font_faces'])) {
        foreach ($input['custom_font_faces'] as $face) {
            if (!is_array($face)) {
                continue;
            }

            if (count($output['custom_font_faces']) >= 20) {
                break;
            }

            $url = garry_typo_sanitize_font_url(isset($face['url']) ? $face['url'] : '', '');
            $url_fallback = garry_typo_sanitize_font_url(isset($face['url_fallback']) ? $face['url_fallback'] : '', '');

            // Řádek bez souboru nemá smysl ukládat.
            if ($url === '' && $url_fallback === '') {
                continue;
            }

            $output['custom_font_faces'][] = array(
                'weight' => isset($face['weight']) && in_array((string) $face['weight'], $face_weights, true)
                    ? (string) $face['weight']
                    : '400',
                'style' => isset($face['style']) && in_array($face['style'], $face_styles, true)
                    ? $face['style']
                    : 'normal',
                'url'          => $url,
                'url_fallback' => $url_fallback,
            );
        }
    }

    foreach (array('custom_font_weight_min' => '100', 'custom_font_weight_max' => '900') as $key => $fallback_weight) {
        $weight = isset($input[$key]) ? absint($input[$key]) : 0;
        $output[$key] = ($weight >= 1 && $weight <= 1000) ? (string) $weight : $fallback_weight;
    }

    if ((int) $output['custom_font_weight_min'] > (int) $output['custom_font_weight_max']) {
        $output['custom_font_weight_min'] = $defaults['custom_font_weight_min'];
        $output['custom_font_weight_max'] = $defaults['custom_font_weight_max'];
    }

    $display_choices = array_keys(garry_typo_font_display_choices());
    $output['custom_font_display'] = isset($input['custom_font_display']) && in_array($input['custom_font_display'], $display_choices, true)
        ? $input['custom_font_display']
        : $defaults['custom_font_display'];

    $output['menu_item_gap'] = garry_typo_sanitize_css_value(
        isset($input['menu_item_gap']) ? $input['menu_item_gap'] : '',
        20
    );

    $output['ls_global_value'] = garry_typo_sanitize_css_value(
        isset($input['ls_global_value']) ? $input['ls_global_value'] : '',
        20
    );

    $output['ls_global_font'] = garry_typo_sanitize_font_key(
        isset($input['ls_global_font']) ? $input['ls_global_font'] : ''
    );

    $output['dark_context'] = garry_typo_sanitize_selector_list(
        isset($input['dark_context']) ? $input['dark_context'] : ''
    );

    $prefix = isset($input['class_prefix']) ? trim((string) $input['class_prefix']) : '';
    $prefix = preg_replace('/[^a-z0-9\-]/', '', strtolower($prefix));
    $output['class_prefix'] = $prefix !== '' ? substr($prefix, 0, 40) : $defaults['class_prefix'];

    /**
     * PHP převádí číselné klíče pole automaticky na celá čísla, takže
     * array_keys() u tučností vrací 100 až 900 jako integery. Striktní
     * porovnání s řetězcem "700" by proto vždy selhalo a hodnota by se
     * zahodila. Klíče je tedy nutné převést zpět na řetězce.
     */
    $weight_choices = array_map('strval', array_keys(garry_typo_weight_choices()));
    $transform_choices = array_keys(garry_typo_transform_choices());
    $empty = garry_typo_empty_element_defaults();

    $output['elements'] = array();

    foreach (garry_typo_elements() as $key => $definition) {
        $stored = isset($input['elements'][$key]) && is_array($input['elements'][$key])
            ? $input['elements'][$key]
            : array();

        $element = $empty;

        $element['on'] = !empty($stored['on']) ? '1' : '0';

        $element['font'] = garry_typo_sanitize_font_key(isset($stored['font']) ? $stored['font'] : '');

        $element['weight'] = isset($stored['weight']) && in_array((string) $stored['weight'], $weight_choices, true)
            ? (string) $stored['weight']
            : '';

        $element['size']   = garry_typo_sanitize_css_value(isset($stored['size']) ? $stored['size'] : '');
        $element['size_t'] = garry_typo_sanitize_css_value(isset($stored['size_t']) ? $stored['size_t'] : '');
        $element['size_m'] = garry_typo_sanitize_css_value(isset($stored['size_m']) ? $stored['size_m'] : '');
        $element['lh']     = garry_typo_sanitize_css_value(isset($stored['lh']) ? $stored['lh'] : '');
        $element['ls']     = garry_typo_sanitize_css_value(isset($stored['ls']) ? $stored['ls'] : '');

        $element['tt'] = isset($stored['tt']) && in_array($stored['tt'], $transform_choices, true)
            ? $stored['tt']
            : '';

        /**
         * Tmavý protějšek ke každé z vlastností výše — stejné sanitizační
         * funkce jako u světlé varianty, jen čtou z klíčů se sufixem _dark.
         */
        $element['font_dark'] = garry_typo_sanitize_font_key(isset($stored['font_dark']) ? $stored['font_dark'] : '');

        $element['weight_dark'] = isset($stored['weight_dark']) && in_array((string) $stored['weight_dark'], $weight_choices, true)
            ? (string) $stored['weight_dark']
            : '';

        $element['size_dark']   = garry_typo_sanitize_css_value(isset($stored['size_dark']) ? $stored['size_dark'] : '');
        $element['size_t_dark'] = garry_typo_sanitize_css_value(isset($stored['size_t_dark']) ? $stored['size_t_dark'] : '');
        $element['size_m_dark'] = garry_typo_sanitize_css_value(isset($stored['size_m_dark']) ? $stored['size_m_dark'] : '');
        $element['lh_dark']     = garry_typo_sanitize_css_value(isset($stored['lh_dark']) ? $stored['lh_dark'] : '');
        $element['ls_dark']     = garry_typo_sanitize_css_value(isset($stored['ls_dark']) ? $stored['ls_dark'] : '');

        $element['tt_dark'] = isset($stored['tt_dark']) && in_array($stored['tt_dark'], $transform_choices, true)
            ? $stored['tt_dark']
            : '';

        $element['mb']  = garry_typo_sanitize_css_value(isset($stored['mb']) ? $stored['mb'] : '');
        $element['pad'] = garry_typo_sanitize_css_value(isset($stored['pad']) ? $stored['pad'] : '');

        $align_choices = array_keys(garry_typo_align_choices());
        $element['align'] = isset($stored['align']) && in_array($stored['align'], $align_choices, true)
            ? $stored['align']
            : '';

        $deco_choices = array_keys(garry_typo_decoration_choices());
        $element['deco'] = isset($stored['deco']) && in_array($stored['deco'], $deco_choices, true)
            ? $stored['deco']
            : '';

        foreach (array('color', 'color_dark', 'color_hover', 'color_hover_dark') as $color_key) {
            $color = isset($stored[$color_key]) ? sanitize_hex_color($stored[$color_key]) : '';
            $element[$color_key] = $color ? $color : '';
        }

        $element['sel'] = garry_typo_sanitize_selector_list(isset($stored['sel']) ? $stored['sel'] : '');

        $output['elements'][$key] = $element;
    }

    return $output;
}
