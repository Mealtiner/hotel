<?php
/**
 * ============================================================================
 * GARRY – Typografie · generátor CSS
 * ============================================================================
 * Ze uloženého nastavení sestaví jeden blok CSS. Stejná funkce se používá
 * pro výpis na webu i pro zobrazení výsledného CSS v administraci, takže
 * správce vidí přesně to, co plugin na web opravdu posílá.
 * ============================================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Poslední pojistka před vypsáním hodnoty do <style>.
 *
 * Pozor: uvnitř značky <style> se HTML entity nedekódují, takže se zde
 * NESMÍ použít esc_html() – uvozovky kolem názvu fontu by se změnily na
 * &quot; a celá deklarace font-family by se stala neplatnou. Hodnoty jsou
 * už striktně sanitizované v typo-config.php, tady jen odstraníme znaky,
 * kterými by šlo předčasně ukončit blok stylů.
 *
 * Znak > se záměrně nemaže – je to platný CSS kombinátor potomka
 * (například .et-menu > li) a samotný blok stylů ukončit nedokáže.
 */
function garry_typo_css_escape($value) {
    return str_replace(array('<', '\\'), '', (string) $value);
}

/**
 * Sestaví hodnotu vlastnosti src z jednoho nebo dvou souborů.
 * Prohlížeč použije první formát, kterému rozumí, proto se woff2 uvádí první.
 */
function garry_typo_build_src($urls) {
    $parts = array();

    foreach ($urls as $url) {
        $url = garry_typo_sanitize_font_url($url, '');

        if ($url === '') {
            continue;
        }

        $format = garry_typo_font_format($url);
        $url = garry_typo_resolve_font_url($url);

        if ($format === '') {
            continue;
        }

        $parts[$format] = "url('" . garry_typo_css_escape($url) . "') format('" . $format . "')";
    }

    if (empty($parts)) {
        return '';
    }

    // woff2 je nejúspornější, proto patří na první místo.
    $order = array('woff2', 'woff', 'truetype', 'opentype');
    $sorted = array();

    foreach ($order as $format) {
        if (isset($parts[$format])) {
            $sorted[] = $parts[$format];
            unset($parts[$format]);
        }
    }

    foreach ($parts as $part) {
        $sorted[] = $part;
    }

    return implode(',', $sorted);
}

/**
 * Sestaví deklarace @font-face pro font registrovaný přímo tímto pluginem.
 *
 * V režimu „faces“ vznikne samostatná deklarace pro každý nahraný řez.
 * Všechny mají stejný název rodiny, takže si prohlížeč vybere správný soubor
 * podle požadované tučnosti a nedopočítává si ji sám.
 */
function garry_typo_build_font_face($settings, $force = false) {
    if (!$force && (empty($settings['custom_font_enabled']) || $settings['custom_font_enabled'] !== '1')) {
        return '';
    }

    $family = isset($settings['custom_font_family']) ? $settings['custom_font_family'] : 'Nagel';
    $family = preg_replace('/[^A-Za-z0-9 _\-]/', '', $family);

    if ($family === '') {
        return '';
    }

    $display = isset($settings['custom_font_display']) ? $settings['custom_font_display'] : 'swap';
    $display_choices = garry_typo_font_display_choices();

    if (!isset($display_choices[$display])) {
        $display = 'swap';
    }

    $family = garry_typo_css_escape($family);
    $display = garry_typo_css_escape($display);
    $mode = isset($settings['custom_font_mode']) ? $settings['custom_font_mode'] : 'faces';

    /* --- Jeden variabilní soubor --- */

    if ($mode === 'variable') {
        $src = garry_typo_build_src(array(isset($settings['custom_font_url']) ? $settings['custom_font_url'] : ''));

        if ($src === '') {
            return '';
        }

        $weight = (int) $settings['custom_font_weight_min'] . ' ' . (int) $settings['custom_font_weight_max'];

        return '@font-face{font-family:"' . $family . '";src:' . $src . ';font-style:normal;'
            . 'font-weight:' . $weight . ';font-display:' . $display . ";}\n";
    }

    /* --- Samostatné řezy --- */

    $faces = isset($settings['custom_font_faces']) && is_array($settings['custom_font_faces'])
        ? $settings['custom_font_faces']
        : array();

    $css = '';

    foreach ($faces as $face) {
        if (!is_array($face)) {
            continue;
        }

        $src = garry_typo_build_src(array(
            isset($face['url']) ? $face['url'] : '',
            isset($face['url_fallback']) ? $face['url_fallback'] : '',
        ));

        if ($src === '') {
            continue;
        }

        $weight = isset($face['weight']) ? (int) $face['weight'] : 400;
        $style = (isset($face['style']) && $face['style'] === 'italic') ? 'italic' : 'normal';

        $css .= '@font-face{font-family:"' . $family . '";src:' . $src . ';font-style:' . $style . ';'
            . 'font-weight:' . $weight . ';font-display:' . $display . ";}\n";
    }

    return $css;
}

/**
 * Vrátí seznam selektorů jedné úrovně včetně vlastních doplněných selektorů.
 */
function garry_typo_element_selectors($key, $definition, $element) {
    $selectors = isset($definition['selectors']) ? $definition['selectors'] : array();

    if (!empty($element['sel'])) {
        foreach (explode(',', $element['sel']) as $custom) {
            $custom = trim($custom);

            if ($custom !== '') {
                $selectors[] = $custom;
            }
        }
    }

    return array_values(array_unique($selectors));
}

/**
 * Zvýší specificitu selektoru, aby přebil i styly nastavené u jednotlivých
 * Divi modulů nebo ve vlastním CSS.
 *
 * Trojitý :root přidá váhu tří tříd, aniž by změnil to, na co selektor míří –
 * :root je vždy značka html, tedy předek všeho ostatního na stránce.
 */
function garry_typo_boost_selector($selector, $strength) {
    if ($strength !== 'high') {
        return $selector;
    }

    return ':root:root:root ' . $selector;
}

/**
 * Sestaví jedno CSS pravidlo. Vrací prázdný řetězec, pokud není co vypsat.
 */
function garry_typo_build_rule($selectors, $declarations, $important, $strength = 'normal') {
    if (empty($selectors) || empty($declarations)) {
        return '';
    }

    $suffix = $important ? ' !important' : '';
    $body = '';

    foreach ($declarations as $property => $value) {
        $body .= $property . ':' . garry_typo_css_escape($value) . $suffix . ';';
    }

    $escaped_selectors = array();

    foreach ($selectors as $selector) {
        $escaped_selectors[] = garry_typo_boost_selector(garry_typo_css_escape($selector), $strength);
    }

    return implode(',', $escaped_selectors) . '{' . $body . "}\n";
}

/**
 * Hlavní generátor. Vrací kompletní CSS pluginu jako řetězec.
 */
function garry_typo_build_css($settings) {
    $important = !empty($settings['force_important']) && $settings['force_important'] === '1';
    $strength = isset($settings['override_strength']) ? $settings['override_strength'] : 'normal';

    $base = '';
    $dark = '';
    $tablet = '';
    $mobile = '';

    $global_ls = isset($settings['ls_global_value']) ? $settings['ls_global_value'] : '';
    $global_ls_font = isset($settings['ls_global_font']) ? $settings['ls_global_font'] : '';

    // Selektory, podle kterých se pozná tmavá / zelená sekce.
    $dark_contexts = array();

    if (!empty($settings['dark_context'])) {
        foreach (explode(',', $settings['dark_context']) as $context) {
            $context = trim($context);

            if ($context !== '') {
                $dark_contexts[] = $context;
            }
        }
    }

    // Prefix pro globální třídy (.{prefix}-{key}), přiřaditelné ručně v Divi builderu.
    $prefix = isset($settings['class_prefix']) && $settings['class_prefix'] !== '' ? $settings['class_prefix'] : 'garry-typo';
    $classes = '';
    $classes_dark = '';

    foreach (garry_typo_elements() as $key => $definition) {
        $element = isset($settings['elements'][$key]) ? $settings['elements'][$key] : null;

        if (!is_array($element) || empty($element['on']) || $element['on'] !== '1') {
            continue;
        }

        $selectors = garry_typo_element_selectors($key, $definition, $element);
        $declarations = array();

        $font_stack = garry_typo_font_stack($element['font'], $settings);

        if ($font_stack !== '') {
            $declarations['font-family'] = $font_stack;
        }

        if ($element['weight'] !== '') {
            $declarations['font-weight'] = $element['weight'];
        }

        if ($element['size'] !== '') {
            $declarations['font-size'] = $element['size'];
        }

        if ($element['lh'] !== '') {
            $declarations['line-height'] = $element['lh'];
        }

        /**
         * Rozpal písmen: vlastní hodnota úrovně má přednost. Není-li vyplněná,
         * použije se společný rozpal, ale jen u úrovní psaných zvoleným fontem.
         */
        if ($element['ls'] !== '') {
            $declarations['letter-spacing'] = $element['ls'];
        } elseif ($global_ls !== '' && $element['font'] !== '' && $element['font'] === $global_ls_font) {
            $declarations['letter-spacing'] = $global_ls;
        }

        if ($element['tt'] !== '') {
            $declarations['text-transform'] = $element['tt'];
        }

        /**
         * Tmavá varianta typografických vlastností (ne barva, ta se řeší
         * zvlášť níže jako dosud). Nevyplněná _dark hodnota = zdědí se
         * světlá — u velikosti/fontu/tučnosti se totiž typicky mezi světlým
         * a tmavým pozadím nic nemění, liší se hlavně barva. Správce tedy
         * musí vyplnit jen to, co má být na tmavém pozadí opravdu jiné.
         */
        $dark_declarations = array();

        $font_stack_dark = $element['font_dark'] !== '' ? garry_typo_font_stack($element['font_dark'], $settings) : $font_stack;
        if ($font_stack_dark !== '') {
            $dark_declarations['font-family'] = $font_stack_dark;
        }

        $weight_dark_val = $element['weight_dark'] !== '' ? $element['weight_dark'] : $element['weight'];
        if ($weight_dark_val !== '') {
            $dark_declarations['font-weight'] = $weight_dark_val;
        }

        $size_dark_val = $element['size_dark'] !== '' ? $element['size_dark'] : $element['size'];
        if ($size_dark_val !== '') {
            $dark_declarations['font-size'] = $size_dark_val;
        }

        $lh_dark_val = $element['lh_dark'] !== '' ? $element['lh_dark'] : $element['lh'];
        if ($lh_dark_val !== '') {
            $dark_declarations['line-height'] = $lh_dark_val;
        }

        $ls_dark_val = $element['ls_dark'] !== '' ? $element['ls_dark'] : $element['ls'];
        if ($ls_dark_val !== '') {
            $dark_declarations['letter-spacing'] = $ls_dark_val;
        } elseif (isset($declarations['letter-spacing']) && $element['ls_dark'] === '') {
            $dark_declarations['letter-spacing'] = $declarations['letter-spacing'];
        }

        $tt_dark_val = $element['tt_dark'] !== '' ? $element['tt_dark'] : $element['tt'];
        if ($tt_dark_val !== '') {
            $dark_declarations['text-transform'] = $tt_dark_val;
        }

        if ($element['mb'] !== '') {
            $declarations['margin-bottom'] = $element['mb'];
        }

        if ($element['pad'] !== '') {
            $declarations['padding'] = $element['pad'];
        }

        if ($element['align'] !== '') {
            $declarations['text-align'] = $element['align'];
        }

        if ($element['deco'] !== '') {
            $declarations['text-decoration'] = $element['deco'];
        }

        // Barva pro světlé pozadí.
        if (!empty($element['color'])) {
            $declarations['color'] = $element['color'];
        }

        $base .= garry_typo_build_rule($selectors, $declarations, $important, $strength);

        // Barva při najetí myší a při zaměření z klávesnice.
        if (!empty($element['color_hover'])) {
            $hover_selectors = array();

            foreach ($selectors as $selector) {
                $hover_selectors[] = $selector . ':hover';
                $hover_selectors[] = $selector . ':focus';
            }

            $base .= garry_typo_build_rule(
                $hover_selectors,
                array('color' => $element['color_hover']),
                $important,
                $strength
            );
        }

        /**
         * Pravidla pro tmavé / zelené sekce. Vznikne kombinací každého
         * selektoru tmavé sekce s každým selektorem dané úrovně, takže se
         * stejný nadpis na tmavém pozadí vybarví/naformátuje jinak než na
         * světlém. Barva i typografie (font/velikost/tučnost/rozpal/…) se
         * teď kombinují do jednoho pravidla, ne jen barva jako dřív.
         */
        $dark_rule_declarations = $dark_declarations;
        if (!empty($element['color_dark'])) {
            $dark_rule_declarations['color'] = $element['color_dark'];
        }

        if (!empty($dark_rule_declarations) && !empty($dark_contexts)) {
            $dark_selectors = array();

            foreach ($dark_contexts as $context) {
                foreach ($selectors as $selector) {
                    $dark_selectors[] = $context . ' ' . $selector;
                }
            }

            $dark .= garry_typo_build_rule(
                $dark_selectors,
                $dark_rule_declarations,
                $important,
                $strength
            );

            if (!empty($element['color_hover_dark'])) {
                $dark_hover = array();

                foreach ($dark_selectors as $selector) {
                    $dark_hover[] = $selector . ':hover';
                    $dark_hover[] = $selector . ':focus';
                }

                $dark .= garry_typo_build_rule(
                    $dark_hover,
                    array('color' => $element['color_hover_dark']),
                    $important,
                    $strength
                );
            }
        }

        if ($element['size_t'] !== '') {
            $tablet .= garry_typo_build_rule($selectors, array('font-size' => $element['size_t']), $important, $strength);
        }

        if ($element['size_m'] !== '') {
            $mobile .= garry_typo_build_rule($selectors, array('font-size' => $element['size_m']), $important, $strength);
        }

        $size_t_dark_val = $element['size_t_dark'] !== '' ? $element['size_t_dark'] : $element['size_t'];
        if ($size_t_dark_val !== '' && !empty($dark_contexts)) {
            $dark_t_selectors = array();
            foreach ($dark_contexts as $context) {
                foreach ($selectors as $selector) {
                    $dark_t_selectors[] = $context . ' ' . $selector;
                }
            }
            $tablet .= garry_typo_build_rule($dark_t_selectors, array('font-size' => $size_t_dark_val), $important, $strength);
        }

        $size_m_dark_val = $element['size_m_dark'] !== '' ? $element['size_m_dark'] : $element['size_m'];
        if ($size_m_dark_val !== '' && !empty($dark_contexts)) {
            $dark_m_selectors = array();
            foreach ($dark_contexts as $context) {
                foreach ($selectors as $selector) {
                    $dark_m_selectors[] = $context . ' ' . $selector;
                }
            }
            $mobile .= garry_typo_build_rule($dark_m_selectors, array('font-size' => $size_m_dark_val), $important, $strength);
        }

        /**
         * Globální, ručně přiřaditelná třída (.{prefix}-{key}) — nezávisí na
         * tom, jaký HTML tag/Divi třídu modul zrovna používá. Světlá verze
         * platí vždy; tmavá se aplikuje buď automaticky (modul leží uvnitř
         * dark_context sekce), nebo ručně přidáním druhé třídy
         * ".{prefix}-dark" k tomu samému modulu, pro případy kdy modul
         * v rozpoznaném tmavém kontextu neleží, ale tmavou variantu chceme
         * i tak vynutit.
         */
        $class_selector = '.' . $prefix . '-' . str_replace('_', '-', $key);
        $classes .= garry_typo_build_rule(array($class_selector), $declarations, $important, $strength);

        if (!empty($dark_rule_declarations)) {
            $dark_class_selectors = array($class_selector . '.' . $prefix . '-dark');
            foreach ($dark_contexts as $context) {
                $dark_class_selectors[] = $context . ' ' . $class_selector;
            }
            $classes_dark .= garry_typo_build_rule($dark_class_selectors, $dark_rule_declarations, $important, $strength);
        }
    }

    // Vodorovná mezera mezi položkami hlavního menu (připomínka „menu je natěsno").
    $menu_on = !empty($settings['elements']['menu']['on']) && $settings['elements']['menu']['on'] === '1';

    if ($menu_on && !empty($settings['menu_item_gap'])) {
        $gap = $settings['menu_item_gap'];

        $base .= garry_typo_build_rule(
            array(
                '.et_pb_menu .et-menu > li',
                '.et-menu > li',
                '#top-menu > li',
                '.et_pb_menu__menu nav > ul > li',
            ),
            array(
                'padding-left'  => $gap,
                'padding-right' => $gap,
            ),
            $important,
            $strength
        );
    }

    $css = garry_typo_build_font_face($settings);
    $css .= $base;

    if ($dark !== '') {
        $css .= "/* Tmavé / zelené sekce */\n" . $dark;
    }

    if ($classes !== '') {
        $css .= "/* Globální třídy pro ruční přiřazení v Divi builderu */\n" . $classes;
    }

    if ($classes_dark !== '') {
        $css .= $classes_dark;
    }

    if ($tablet !== '') {
        $css .= "@media (max-width:980px){\n" . $tablet . "}\n";
    }

    if ($mobile !== '') {
        $css .= "@media (max-width:767px){\n" . $mobile . "}\n";
    }

    return $css;
}

/**
 * Výpis CSS na web.
 *
 * Pozdní priorita ve wp_head zajistí, že se blok objeví až za dynamickým
 * CSS Divi, a nastavení pluginu tak nepřebije samotná šablona.
 */
function garry_typo_render_frontend() {
    if (is_admin()) {
        return;
    }

    $settings = garry_typo_get_settings();

    if ($settings['enabled'] !== '1') {
        return;
    }

    $in_builder = isset($_GET['et_fb']) && $_GET['et_fb'] === '1';

    if ($in_builder && $settings['apply_in_builder'] !== '1') {
        return;
    }

    $css = garry_typo_build_css($settings);

    if (trim($css) === '') {
        return;
    }

    // Značka pro rychlé ověření ve zdrojovém kódu stránky.
    echo "\n<!-- GARRY Typografie " . GARRY_TYPO_VERSION . " -->\n";
    echo "<style id=\"garry-typografie-css\">\n" . $css . "</style>\n";
}
add_action('wp_head', 'garry_typo_render_frontend', 999);
