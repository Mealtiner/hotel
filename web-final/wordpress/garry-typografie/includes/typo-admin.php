<?php
/**
 * ============================================================================
 * GARRY – Typografie · administrace
 * ============================================================================
 * Nastavovací stránka pod společným menu „GARRY nastavení“.
 * Obsahuje diagnostiku fontů na webu, vlastní registraci webfontu,
 * nastavení jednotlivých typografických úrovní, živý náhled a test
 * vykreslení české diakritiky.
 * ============================================================================
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================================
 * 1) Pomocné funkce pro diagnostiku
 * ========================================================================= */

/**
 * Převede adresu souboru fontu na cestu na disku, pokud jde o soubor
 * z tohoto webu. Umožní ověřit, že soubor skutečně existuje.
 */
function garry_typo_font_local_path($url) {
    $url = (string) $url;

    if ($url === '') {
        return '';
    }

    // Soubor dodaný přímo s pluginem leží ve složce assets/fonts.
    if (strpos($url, 'plugin:') === 0) {
        return GARRY_TYPO_DIR . 'assets/fonts/' . basename(substr($url, 7));
    }

    $url = strtok($url, '?#');

    if (strpos($url, '//') === 0) {
        $url = (is_ssl() ? 'https:' : 'http:') . $url;
    }

    $home = home_url();

    if (strpos($url, $home) === 0) {
        $url = substr($url, strlen($home));
    }

    if (strpos($url, '/') !== 0) {
        return '';
    }

    return untrailingslashit(ABSPATH) . $url;
}

/**
 * Globální fonty nastavené v Divi (Theme Options / Design).
 */
function garry_typo_divi_global_fonts() {
    $result = array(
        'heading' => '',
        'body'    => '',
    );

    $et_divi = get_option('et_divi');

    if (is_array($et_divi)) {
        if (!empty($et_divi['heading_font'])) {
            $result['heading'] = (string) $et_divi['heading_font'];
        }

        if (!empty($et_divi['body_font'])) {
            $result['body'] = (string) $et_divi['body_font'];
        }
    }

    return $result;
}

/**
 * Najde snippety WPCode, které zasahují do písem.
 * Slouží k odhalení konfliktu více vrstev typografie na jednom webu.
 */
function garry_typo_find_font_snippets() {
    $found = array();

    if (!post_type_exists('wpcode')) {
        return $found;
    }

    $snippets = get_posts(array(
        'post_type'        => 'wpcode',
        'post_status'      => array('publish', 'draft', 'private'),
        'numberposts'      => 100,
        'suppress_filters' => true,
    ));

    foreach ($snippets as $snippet) {
        $code = (string) $snippet->post_content;

        if (stripos($code, 'font-family') === false && stripos($code, '@font-face') === false) {
            continue;
        }

        $families = array();

        if (preg_match_all('/font-family\s*:\s*([^;{}]+)/i', $code, $matches)) {
            foreach ($matches[1] as $value) {
                $value = trim($value);

                if ($value !== '' && !in_array($value, $families, true)) {
                    $families[] = $value;
                }
            }
        }

        $found[] = array(
            'id'       => (int) $snippet->ID,
            'title'    => $snippet->post_title !== '' ? $snippet->post_title : ('Snippet #' . $snippet->ID),
            'status'   => $snippet->post_status,
            'families' => array_slice($families, 0, 6),
        );
    }

    return $found;
}

/* ============================================================================
 * 2) Načtení stylů a skriptů administrace
 * ========================================================================= */

/**
 * Pozná, že jsme na nastavovací stránce tohoto pluginu.
 *
 * Název hooku sestavuje WordPress z názvu nadřazené nabídky, takže se může
 * lišit podle toho, jak je společné GARRY menu pojmenované. Spolehlivější je
 * proto porovnat koncovku hooku a pro jistotu i parametr page v adrese.
 */
function garry_typo_is_settings_screen($hook) {
    $suffix = '_page_' . GARRY_TYPO_SLUG;

    if ($hook === 'toplevel_page_' . GARRY_TYPO_SLUG) {
        return true;
    }

    if (substr((string) $hook, -strlen($suffix)) === $suffix) {
        return true;
    }

    return isset($_GET['page']) && $_GET['page'] === GARRY_TYPO_SLUG;
}

function garry_typo_admin_assets($hook) {
    if (!garry_typo_is_settings_screen($hook)) {
        return;
    }

    // Knihovna médií kvůli nahrávání souborů fontů.
    wp_enqueue_media();

    wp_enqueue_style(
        'garry-typo-admin',
        GARRY_TYPO_URL . 'assets/typo-admin.css',
        array(),
        GARRY_TYPO_VERSION
    );

    /**
     * Závislost na media-editor zaručí, že se objekt wp.media načte dřív
     * než tento skript. Bez ní by tlačítko pro výběr souboru nemuselo
     * mít co otevřít.
     */
    wp_enqueue_script(
        'garry-typo-admin',
        GARRY_TYPO_URL . 'assets/typo-admin.js',
        array('jquery', 'media-editor', 'media-views'),
        GARRY_TYPO_VERSION,
        true
    );

    $settings = garry_typo_get_settings();

    // Mapa klíč fontu → CSS hodnota, aby náhled uměl fonty přepínat bez reloadu.
    $font_map = array();

    foreach (garry_typo_font_choices($settings) as $key => $label) {
        if ($key === '') {
            continue;
        }

        $font_map[$key] = garry_typo_font_stack($key, $settings);
    }

    wp_localize_script('garry-typo-admin', 'garryTypoData', array(
        'fontMap'     => $font_map,
        'bundledFaces' => garry_typo_defaults()['custom_font_faces'],
        /**
         * Prázdné záměrně – "doporučené nastavení" bylo specifické pro CIT
         * (font Nagel, barvy CIT). Tato instalace má vlastní reálné hodnoty
         * už rovnou v defaultech (garry_typo_grid_hotel_elements()), takže
         * samostatné tlačítko na dosazení jiného klienta sem nepatří.
         */
        'recommended' => array(),
        'switchOn'    => GARRY_TYPO_SWITCH_ON_COLOR,
        'switchOff'   => GARRY_TYPO_SWITCH_OFF_COLOR,
    ));

    $inline = ':root{--garry-typo-switch-on:' . GARRY_TYPO_SWITCH_ON_COLOR
        . ';--garry-typo-switch-off:' . GARRY_TYPO_SWITCH_OFF_COLOR . ';}';

    /**
     * Deklarace @font-face patří i do administrace. Bez ní by živý náhled
     * ani test diakritiky neměly vlastní font k dispozici – stylesheety
     * z frontendu se sem totiž nenačítají. Vypisujeme je i při vypnuté
     * registraci, aby náhled fungoval už během nastavování.
     */
    $inline .= garry_typo_build_font_face($settings, true);

    wp_add_inline_style('garry-typo-admin', $inline);
}
add_action('admin_enqueue_scripts', 'garry_typo_admin_assets');

/* ============================================================================
 * 3) Dílčí části stránky
 * ========================================================================= */

/**
 * Vyhledá zdroje, které mohou registrovat webfont pod názvem systémového
 * písma. To je zákeřná past: názvy rodin se v CSS porovnávají bez ohledu
 * na velikost písmen, takže webfont pojmenovaný „arial“ přebije skutečný
 * systémový Arial. Když je navíc takový webfont ořezaný, začne u části
 * znaků vypadávat diakritika.
 */
function garry_typo_find_font_shadowing() {
    $found = array();
    $system_names = array('arial', 'helvetica', 'verdana', 'georgia', 'tahoma', 'times', 'courier');

    $sources = array();

    if (post_type_exists('wpcode')) {
        $snippets = get_posts(array(
            'post_type'        => 'wpcode',
            'post_status'      => array('publish', 'draft', 'private'),
            'numberposts'      => 100,
            'suppress_filters' => true,
        ));

        foreach ($snippets as $snippet) {
            $sources[] = array(
                'label' => 'Snippet WPCode: ' . ($snippet->post_title !== '' ? $snippet->post_title : ('#' . $snippet->ID)),
                'code'  => (string) $snippet->post_content,
            );
        }
    }

    $et_divi = get_option('et_divi');

    if (is_array($et_divi)) {
        foreach (array('divi_header_code', 'divi_body_code', 'divi_footer_code') as $key) {
            if (!empty($et_divi[$key])) {
                $sources[] = array(
                    'label' => 'Vlastní kód v Divi (' . $key . ')',
                    'code'  => (string) $et_divi[$key],
                );
            }
        }
    }

    foreach ($sources as $source) {
        $code = $source['code'];
        $problems = array();

        if (stripos($code, 'typekit.net') !== false || stripos($code, 'adobe.com/fonts') !== false) {
            $problems[] = 'načítá sadu fontů z Adobe Fonts / Typekit';
        }

        if (preg_match_all('/@font-face\s*\{[^}]*font-family\s*:\s*[\'"]?([A-Za-z0-9 _-]+)/i', $code, $matches)) {
            foreach ($matches[1] as $family) {
                if (in_array(strtolower(trim($family)), $system_names, true)) {
                    $problems[] = 'registruje webfont pod názvem systémového písma „' . trim($family) . '“';
                }
            }
        }

        if (!empty($problems)) {
            $found[] = array(
                'label'    => $source['label'],
                'problems' => array_unique($problems),
            );
        }
    }

    return $found;
}

/**
 * Karta „Nasazení na webu“ – rychlá odpověď na otázku, proč se nastavení
 * (ne)projevuje. Ukazuje, zda plugin vůbec něco vypisuje a kolik toho je.
 */
function garry_typo_render_deployment_check($settings, $generated_css) {
    $enabled = $settings['enabled'] === '1';

    $active = 0;

    foreach ($settings['elements'] as $element) {
        if (!empty($element['on']) && $element['on'] === '1') {
            $active++;
        }
    }

    $bytes = strlen(trim($generated_css));
    $emitting = $enabled && $bytes > 0;
    ?>
    <div class="garry-typo-card">
        <h2>Nasazení na webu</h2>

        <div class="garry-typo-notice <?php echo $emitting ? 'garry-typo-notice--ok' : 'garry-typo-notice--warn'; ?>">
            <?php if (!$enabled) : ?>
                <strong>Plugin je vypnutý, na web se nic neposílá.</strong>
                Zapněte hlavní vypínač níže a nastavení uložte.
            <?php elseif ($bytes === 0) : ?>
                <strong>Plugin je zapnutý, ale nemá co vypsat.</strong>
                Není zapnutá žádná typografická úroveň, nebo nemají vyplněnou žádnou hodnotu.
            <?php else : ?>
                <strong>Plugin na web posílá vlastní styly.</strong>
                Ověřit to jde ve zdrojovém kódu stránky – hledejte
                <code>&lt;!-- GARRY Typografie <?php echo esc_html(GARRY_TYPO_VERSION); ?> --&gt;</code>.
                Pokud tam značka je, ale vzhled se nemění, přebíjí nastavení jiné pravidlo –
                přepněte níže sílu přepisu na vysokou.
            <?php endif; ?>
        </div>

        <table class="garry-typo-table">
            <tbody>
                <tr>
                    <th>Hlavní vypínač</th>
                    <td><?php echo $enabled ? 'zapnuto' : 'vypnuto'; ?></td>
                </tr>
                <tr>
                    <th>Zapnutých úrovní</th>
                    <td><?php echo (int) $active; ?> z <?php echo count(garry_typo_elements()); ?></td>
                </tr>
                <tr>
                    <th>Velikost vypisovaného CSS</th>
                    <td><?php echo $bytes > 0 ? esc_html(size_format($bytes)) : 'nic se nevypisuje'; ?></td>
                </tr>
                <tr>
                    <th>Síla přepisu</th>
                    <td><?php echo $settings['override_strength'] === 'high' ? 'vysoká' : 'běžná'; ?></td>
                </tr>
            </tbody>
        </table>

        <p class="garry-typo-help">
            Údaje vycházejí z <strong>naposledy uloženého</strong> nastavení, ne z rozpracovaného formuláře.
            Po každé změně je proto potřeba nejdřív uložit. Pokud web používá mezipaměť nebo optimalizaci CSS,
            po uložení ji vyprázdněte.
        </p>
    </div>
    <?php
}

/**
 * Karta s diagnostikou – co je na webu skutečně nastavené.
 */
function garry_typo_render_diagnostics($settings) {
    $uaf_fonts = garry_typo_detect_uaf_fonts();
    $divi_fonts = garry_typo_divi_global_fonts();
    $snippets = garry_typo_find_font_snippets();

    $heading_font_key = isset($settings['elements']['h1']['font']) ? $settings['elements']['h1']['font'] : '';
    $body_font_key = isset($settings['elements']['body']['font']) ? $settings['elements']['body']['font'] : '';
    ?>
    <div class="garry-typo-card">
        <h2>Kontrola nastavení fontů na webu</h2>

        <p class="garry-typo-help">
            Přehled toho, co je na webu skutečně zaregistrované a nastavené. Slouží k ověření,
            že se nepřetahuje více vrstev typografie najednou.
        </p>

        <h3>Fonty nahrané přes Use Any Font</h3>

        <?php if (empty($uaf_fonts)) : ?>
            <div class="garry-typo-notice garry-typo-notice--warn">
                Nenašel jsem soubor <code>uploads/useanyfont/uaf.css</code>. Buď plugin Use Any Font není aktivní,
                nebo přes něj není nahraný žádný font.
            </div>
        <?php else : ?>
            <table class="garry-typo-table">
                <thead>
                    <tr>
                        <th>Název fontu (font-family)</th>
                        <th>Registrované řezy</th>
                        <th>font-display</th>
                        <th>Soubor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uaf_fonts as $family => $info) : ?>
                        <tr>
                            <td><code><?php echo esc_html($family); ?></code></td>
                            <td>
                                <?php
                                echo $info['weights']
                                    ? esc_html(implode(', ', $info['weights']))
                                    : '<span class="garry-typo-dim">neuvedeno</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                echo $info['display'] !== ''
                                    ? esc_html($info['display'])
                                    : '<span class="garry-typo-dim">neuvedeno</span>';
                                ?>
                            </td>
                            <td>
                                <?php foreach ($info['files'] as $file) : ?>
                                    <div class="garry-typo-file"><?php echo esc_html(basename($file)); ?></div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $suspicious = array();

            foreach ($uaf_fonts as $family => $info) {
                $weights = array_map('strval', $info['weights']);

                if (count($weights) === 1 && in_array($weights[0], array('100', '200', '300'), true)) {
                    $suspicious[] = $family . ' (pouze řez ' . $weights[0] . ')';
                }
            }
            ?>

            <?php if (!empty($suspicious)) : ?>
                <div class="garry-typo-notice garry-typo-notice--warn">
                    <strong>Pozor:</strong> tyto fonty jsou registrované jen v jednom lehkém řezu:
                    <?php echo esc_html(implode(', ', $suspicious)); ?>.
                    Pokud se na webu používají tučně (600 / 700), prohlížeč si tučnost dopočítává sám
                    a výsledek bývá nepřesný. Řešením je registrovat font níže v sekci
                    <em>Vlastní registrace webfontu</em> s plným rozsahem řezů.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h3>Globální fonty nastavené v Divi</h3>

        <?php if ($divi_fonts['heading'] === '' && $divi_fonts['body'] === '') : ?>
            <p class="garry-typo-help">
                V nastavení motivu Divi nejsou uložené vlastní globální fonty, nebo je Divi 5 ukládá
                jinam než do klasické položky <code>et_divi</code>. Zkontrolujte prosím ručně
                <strong>Divi → Možnosti motivu → Typografie</strong>, případně nastavení záhlaví v editoru.
            </p>
        <?php else : ?>
            <table class="garry-typo-table">
                <tbody>
                    <tr>
                        <th>Font nadpisů v Divi</th>
                        <td><code><?php echo esc_html($divi_fonts['heading'] !== '' ? $divi_fonts['heading'] : 'výchozí'); ?></code></td>
                    </tr>
                    <tr>
                        <th>Font základního textu v Divi</th>
                        <td><code><?php echo esc_html($divi_fonts['body'] !== '' ? $divi_fonts['body'] : 'výchozí'); ?></code></td>
                    </tr>
                </tbody>
            </table>

            <?php if (stripos($divi_fonts['heading'], 'arial') !== false && strpos($heading_font_key, 'family:') === 0) : ?>
                <div class="garry-typo-notice garry-typo-notice--warn">
                    Divi má jako font nadpisů nastavený <strong><?php echo esc_html($divi_fonts['heading']); ?></strong>,
                    zatímco tento plugin nastavuje nadpisům jiný font. Plugin sice vyhraje díky pozdějšímu
                    zápisu stylů, ale čistší je srovnat i nastavení v Divi.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h3>Přebije plugin současné nastavení?</h3>

        <p class="garry-typo-help">
            O tom, které pravidlo vyhraje, nerozhoduje pořadí v kódu, ale takzvaná specificita selektoru
            a značka <code>!important</code>. Níže je porovnání s vrstvami, které na tomto webu písma nastavují.
        </p>

        <table class="garry-typo-table">
            <thead>
                <tr>
                    <th>Vrstva, která nastavuje písmo</th>
                    <th>Příklad pravidla</th>
                    <th>Přebije to plugin?</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Základní styly motivu Divi</td>
                    <td><code>body{font-family:Open Sans,Arial,sans-serif}</code></td>
                    <td class="garry-typo-ok">Ano, při běžné síle</td>
                </tr>
                <tr>
                    <td>Globální fonty Divi (Možnosti motivu)</td>
                    <td><code>h1,h2,…{font-family:var(--et_global_heading_font)}</code></td>
                    <td class="garry-typo-ok">Ano, při běžné síle</td>
                </tr>
                <tr>
                    <td>Vlastní snippet WPCode bez <code>!important</code></td>
                    <td><code>h1,h2,…{font-family:"Nagel Variable",…}</code></td>
                    <td class="garry-typo-ok">Ano, při běžné síle</td>
                </tr>
                <tr>
                    <td>Třídy WordPressu z globálních stylů</td>
                    <td><code>.has-nagel-font-family{font-family:…!important}</code></td>
                    <td class="garry-typo-warn-cell">Až při vysoké síle</td>
                </tr>
                <tr>
                    <td>Font nastavený u konkrétního Divi modulu</td>
                    <td><code>.et_pb_text_0 h2{font-family:nagel!important}</code></td>
                    <td class="garry-typo-warn-cell">Až při vysoké síle</td>
                </tr>
                <tr>
                    <td>Vlastní CSS s vlastní třídou</td>
                    <td><code>.col-service .et_pb_module_heading{letter-spacing:2px!important}</code></td>
                    <td class="garry-typo-warn-cell">Až při vysoké síle</td>
                </tr>
            </tbody>
        </table>

        <p class="garry-typo-help">
            Aktuálně je nastavená síla přepisu
            <strong><?php echo esc_html($settings['override_strength'] === 'high' ? 'vysoká' : 'běžná'); ?></strong>.
            I při vysoké síle ale platí, že nejčistší je konfliktní pravidlo v Divi nebo ve snippetu opravdu
            odstranit – plugin ho jen přebíjí, nemaže.
        </p>

        <h3>Snippety WPCode, které zasahují do písem</h3>

        <?php if (empty($snippets)) : ?>
            <p class="garry-typo-help">
                Nenašel jsem žádný snippet WPCode s deklarací písma. Typografii tak neřídí žádná další skrytá vrstva.
            </p>
        <?php else : ?>
            <div class="garry-typo-notice garry-typo-notice--warn">
                Následující snippety nastavují písmo souběžně s tímto pluginem. Doporučuji je projít
                a typografii nechat na jednom místě.
            </div>

            <table class="garry-typo-table">
                <thead>
                    <tr>
                        <th>Snippet</th>
                        <th>Stav</th>
                        <th>Nalezené deklarace písma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snippets as $snippet) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($snippet['title']); ?></strong></td>
                            <td><?php echo esc_html($snippet['status']); ?></td>
                            <td>
                                <?php if (empty($snippet['families'])) : ?>
                                    <span class="garry-typo-dim">deklarace @font-face</span>
                                <?php else : ?>
                                    <?php foreach ($snippet['families'] as $family) : ?>
                                        <div><code><?php echo esc_html($family); ?></code></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Fonty přebíjející systémová písma</h3>

        <div class="garry-typo-notice garry-typo-notice--warn">
            <strong>Na co si dát pozor.</strong> Názvy fontových rodin se v CSS porovnávají bez ohledu na velikost
            písmen. Pokud tedy nějaká sada registruje webfont pod názvem <code>arial</code>, použije se
            <em>ten</em> místo skutečného systémového Arialu – a to i tam, kde je v nastavení napsáno
            <code>Arial</code>. Bývá-li takový webfont ořezaný jen na západní znaky, začnou se česká
            písmena s háčky a kroužky (č, ě, ř, š, ž, ů, ď, ť, ň) brát z náhradního písma, zatímco
            čárkované samohlásky (á, é, í, ó, ú, ý) vypadají správně. Přesně tak vypadá závada,
            kterou hlásil klient.
        </div>

        <?php $shadowing = garry_typo_find_font_shadowing(); ?>

        <?php if (empty($shadowing)) : ?>
            <p class="garry-typo-help">
                V kódu, který umím prohledat (snippety WPCode a vlastní kód v Divi), jsem nic takového nenašel.
                Pokud se stylesheet s fonty vkládá jinde – například přímo do šablony nebo přes jiný plugin –
                zkontrolujte ho prosím ručně.
            </p>
        <?php else : ?>
            <table class="garry-typo-table">
                <thead>
                    <tr>
                        <th>Zdroj</th>
                        <th>Co je na něm rizikové</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shadowing as $item) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($item['label']); ?></strong></td>
                            <td>
                                <?php foreach ($item['problems'] as $problem) : ?>
                                    <div><?php echo esc_html($problem); ?></div>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3>Prvky řízené jinými GARRY pluginy</h3>

        <p class="garry-typo-help">
            Písmo a rozpal odkazu <strong>„Přečíst více…“</strong> se nastavuje v pluginu
            <strong>Rozbalovací texty</strong>, ne zde. Tento plugin do něj záměrně nezasahuje,
            aby se dvě nastavení nepřetahovala.
        </p>
    </div>
    <?php
}

/**
 * Jedno pole pro výběr barvy.
 * Prázdná hodnota znamená, že plugin barvu vůbec nenastavuje.
 */
function garry_typo_render_color_field($field_name, $field_id, $label, $value, $element_key, $prop, $palette) {
    ?>
    <div class="garry-typo-field garry-typo-field--color">
        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?></label>

        <div class="garry-typo-color-row">
            <input
                type="color"
                class="garry-typo-color-pick"
                value="<?php echo esc_attr($value !== '' ? $value : '#ffffff'); ?>"
                aria-label="Výběr barvy"
            >
            <input
                id="<?php echo esc_attr($field_id); ?>"
                type="text"
                class="garry-typo-input garry-typo-color-text"
                data-el="<?php echo esc_attr($element_key); ?>"
                data-prop="<?php echo esc_attr($prop); ?>"
                name="<?php echo esc_attr($field_name); ?>"
                value="<?php echo esc_attr($value); ?>"
                placeholder="neměnit"
            >
            <button type="button" class="garry-typo-color-clear" title="Nechat beze změny">&times;</button>
        </div>

        <div class="garry-typo-swatches">
            <?php $shown = 0; ?>
            <?php foreach ($palette as $hex => $title) : ?>
                <?php if ($shown >= 5) { break; } $shown++; ?>
                <button
                    type="button"
                    class="garry-typo-swatch"
                    data-color="<?php echo esc_attr($hex); ?>"
                    style="background: <?php echo esc_attr($hex); ?>"
                    title="<?php echo esc_attr($title . ' ' . strtoupper($hex)); ?>"
                ></button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Jeden řádek seznamu nahraných řezů písma.
 */
function garry_typo_render_face_row($index, $face, $option_name) {
    $name = $option_name . '[custom_font_faces][' . $index . ']';
    $url = isset($face['url']) ? $face['url'] : '';
    $url_fallback = isset($face['url_fallback']) ? $face['url_fallback'] : '';
    $path = garry_typo_font_local_path($url);
    $exists = $url !== '' && $path !== '' && file_exists($path);
    ?>
    <div class="garry-typo-face-row">
        <div class="garry-typo-field">
            <label>Řez</label>
            <select name="<?php echo esc_attr($name); ?>[weight]">
                <?php foreach (garry_typo_face_weight_choices() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($face['weight']) ? $face['weight'] : '400', $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="garry-typo-field">
            <label>Sklon</label>
            <select name="<?php echo esc_attr($name); ?>[style]">
                <?php foreach (garry_typo_face_style_choices() as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($face['style']) ? $face['style'] : 'normal', $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="garry-typo-field garry-typo-field--grow">
            <label>Soubor woff2 (hlavní)</label>
            <div class="garry-typo-file-picker">
                <input
                    type="text"
                    class="garry-typo-face-url"
                    name="<?php echo esc_attr($name); ?>[url]"
                    value="<?php echo esc_attr($url); ?>"
                    placeholder="/wp-content/uploads/…/Nagel-Regular.woff2"
                >
                <button type="button" class="button garry-typo-media-pick">Vybrat / nahrát</button>
            </div>
            <?php if ($url !== '') : ?>
                <?php if ($exists) : ?>
                    <p class="garry-typo-ok">Soubor nalezen (<?php echo esc_html(size_format((int) filesize($path))); ?>).</p>
                <?php else : ?>
                    <p class="garry-typo-bad">Soubor se na uvedené cestě nepodařilo najít.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="garry-typo-field garry-typo-field--grow">
            <label>Záložní soubor woff (nepovinné)</label>
            <div class="garry-typo-file-picker">
                <input
                    type="text"
                    class="garry-typo-face-url"
                    name="<?php echo esc_attr($name); ?>[url_fallback]"
                    value="<?php echo esc_attr($url_fallback); ?>"
                    placeholder="/wp-content/uploads/…/Nagel-Regular.woff"
                >
                <button type="button" class="button garry-typo-media-pick">Vybrat / nahrát</button>
            </div>
        </div>

        <div class="garry-typo-field garry-typo-field--action">
            <button type="button" class="button garry-typo-face-remove" title="Odebrat řádek">Odebrat</button>
        </div>
    </div>
    <?php
}

/**
 * Karta s vlastní registrací webfontu.
 */
function garry_typo_render_custom_font_card($settings, $option_name) {
    $faces = isset($settings['custom_font_faces']) && is_array($settings['custom_font_faces'])
        ? $settings['custom_font_faces']
        : array();

    if (empty($faces)) {
        $faces = array(array('weight' => '400', 'style' => 'normal', 'url' => '', 'url_fallback' => ''));
    }

    $variable_url = $settings['custom_font_url'];
    $variable_path = garry_typo_font_local_path($variable_url);
    $variable_exists = $variable_url !== '' && $variable_path !== '' && file_exists($variable_path);
    ?>
    <div class="garry-typo-card">
        <h2>Vlastní registrace webfontu</h2>

        <p class="garry-typo-help">
            Sem lze nahrát vlastní soubory fontu – například Nagel v řezech Regular, Medium a Bold – a nechat je
            zaregistrovat pod jedním názvem. Prohlížeč si pak pro každou tučnost vezme skutečný soubor a nedopočítává
            si tučnost sám, což bývá příčina rozmazaného nebo jinak vypadajícího písma.
        </p>

        <p class="garry-typo-help">
            Původní registrace z pluginu Use Any Font zůstane nedotčená. Pokud chcete jen doplnit chybějící řezy,
            zvolte jiný název fontu a přiřaďte ho v nastavení jednotlivých úrovní níže.
        </p>

        <label class="garry-typo-check">
            <input
                type="checkbox"
                name="<?php echo esc_attr($option_name); ?>[custom_font_enabled]"
                value="1"
                <?php checked($settings['custom_font_enabled'], '1'); ?>
            >
            <span>Zaregistrovat font vlastní deklarací <code>@font-face</code></span>
        </label>

        <div class="garry-typo-form-grid">
            <label for="garry-typo-custom-font-family">Název fontu</label>
            <div>
                <input
                    id="garry-typo-custom-font-family"
                    type="text"
                    name="<?php echo esc_attr($option_name); ?>[custom_font_family]"
                    value="<?php echo esc_attr($settings['custom_font_family']); ?>"
                >
                <p class="description">
                    Pod tímto názvem se font nabídne ve výběru u jednotlivých úrovní.
                    Po uložení se nabídka aktualizuje.
                </p>
            </div>

            <label for="garry-typo-font-mode">Způsob registrace</label>
            <div>
                <select id="garry-typo-font-mode" class="garry-typo-wide" name="<?php echo esc_attr($option_name); ?>[custom_font_mode]">
                    <?php foreach (garry_typo_font_mode_choices() as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['custom_font_mode'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <label for="garry-typo-font-display">Chování při načítání</label>
            <div>
                <select
                    id="garry-typo-font-display"
                    class="garry-typo-wide"
                    name="<?php echo esc_attr($option_name); ?>[custom_font_display]"
                >
                    <?php foreach (garry_typo_font_display_choices() as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['custom_font_display'], $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="garry-typo-mode-block" data-mode="faces">
            <h3>Nahrané řezy písma</h3>

            <p class="garry-typo-help">
                Ke každému řezu přiřaďte odpovídající soubor. Formát <code>woff2</code> je nejúspornější a rozumí mu
                všechny dnešní prohlížeče; soubor <code>woff</code> lze doplnit jako zálohu. Soubory <code>otf</code>
                a <code>svg</code> se na web dávat nemusí.
            </p>

            <div id="garry-typo-faces">
                <?php foreach (array_values($faces) as $index => $face) : ?>
                    <?php garry_typo_render_face_row($index, $face, $option_name); ?>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button" id="garry-typo-face-add">Přidat další řez</button>
                <button type="button" class="button" id="garry-typo-face-bundled">Použít soubory dodané s pluginem</button>
            </p>

            <p class="garry-typo-help">
                Řezy Nagel Regular, Medium a Bold jsou součástí balíčku pluginu. Zkrácený zápis
                <code>plugin:nazev.woff2</code> odkazuje právě na ně a přeloží se na skutečnou adresu
                až při vykreslování stránky.
            </p>
        </div>

        <div class="garry-typo-mode-block" data-mode="variable">
            <h3>Variabilní soubor</h3>

            <div class="garry-typo-form-grid">
                <label for="garry-typo-custom-font-url">Soubor fontu</label>
                <div>
                    <div class="garry-typo-file-picker">
                        <input
                            id="garry-typo-custom-font-url"
                            type="text"
                            class="garry-typo-wide"
                            name="<?php echo esc_attr($option_name); ?>[custom_font_url]"
                            value="<?php echo esc_attr($variable_url); ?>"
                            placeholder="/wp-content/uploads/useanyfont/nazev.woff2"
                        >
                        <button type="button" class="button garry-typo-media-pick">Vybrat / nahrát</button>
                    </div>

                    <?php if ($variable_url !== '') : ?>
                        <?php if ($variable_exists) : ?>
                            <p class="garry-typo-ok">Soubor nalezen (<?php echo esc_html(size_format((int) filesize($variable_path))); ?>).</p>
                        <?php else : ?>
                            <p class="garry-typo-bad">Soubor se na uvedené cestě nepodařilo najít.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <label>Rozsah řezů</label>
                <div>
                    <div class="garry-typo-inline-fields">
                        <label for="garry-typo-weight-min">od</label>
                        <input
                            id="garry-typo-weight-min"
                            type="number"
                            min="1"
                            max="1000"
                            step="1"
                            name="<?php echo esc_attr($option_name); ?>[custom_font_weight_min]"
                            value="<?php echo esc_attr($settings['custom_font_weight_min']); ?>"
                        >
                        <label for="garry-typo-weight-max">do</label>
                        <input
                            id="garry-typo-weight-max"
                            type="number"
                            min="1"
                            max="1000"
                            step="1"
                            name="<?php echo esc_attr($option_name); ?>[custom_font_weight_max]"
                            value="<?php echo esc_attr($settings['custom_font_weight_max']); ?>"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Jedna typografická úroveň.
 */
function garry_typo_render_element($key, $definition, $settings, $option_name) {
    $element = $settings['elements'][$key];
    $name = $option_name . '[elements][' . $key . ']';
    $id = 'garry-typo-' . str_replace('_', '-', $key);
    $font_choices = garry_typo_font_choices($settings);
    ?>
    <div class="garry-typo-element<?php echo $element['on'] === '1' ? ' is-on' : ''; ?>" data-element="<?php echo esc_attr($key); ?>">

        <div class="garry-typo-element-head">
            <label class="garry-typo-check">
                <input
                    type="checkbox"
                    class="garry-typo-element-toggle"
                    name="<?php echo esc_attr($name); ?>[on]"
                    value="1"
                    <?php checked($element['on'], '1'); ?>
                >
                <span class="garry-typo-element-label"><?php echo esc_html($definition['label']); ?></span>
            </label>

            <button type="button" class="garry-typo-selector-toggle" aria-expanded="false">
                Rozšířené nastavení
            </button>
        </div>

        <p class="garry-typo-element-desc"><?php echo esc_html($definition['desc']); ?></p>

        <div class="garry-typo-element-fields">
            <div class="garry-typo-field garry-typo-field--wide">
                <label for="<?php echo esc_attr($id); ?>-font">Font</label>
                <select
                    id="<?php echo esc_attr($id); ?>-font"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="font"
                    name="<?php echo esc_attr($name); ?>[font]"
                >
                    <?php foreach ($font_choices as $font_key => $font_label) : ?>
                        <option value="<?php echo esc_attr($font_key); ?>" <?php selected($element['font'], $font_key); ?>>
                            <?php echo esc_html($font_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-weight">Tučnost</label>
                <select
                    id="<?php echo esc_attr($id); ?>-weight"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="weight"
                    name="<?php echo esc_attr($name); ?>[weight]"
                >
                    <?php foreach (garry_typo_weight_choices() as $weight_key => $weight_label) : ?>
                        <option value="<?php echo esc_attr($weight_key); ?>" <?php selected($element['weight'], $weight_key); ?>>
                            <?php echo esc_html($weight_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-ls">Rozpal písmen</label>
                <input
                    id="<?php echo esc_attr($id); ?>-ls"
                    type="text"
                    class="garry-typo-input garry-typo-letterspacing"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="ls"
                    name="<?php echo esc_attr($name); ?>[ls]"
                    value="<?php echo esc_attr($element['ls']); ?>"
                    placeholder="normal"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-lh">Řádkování</label>
                <input
                    id="<?php echo esc_attr($id); ?>-lh"
                    type="text"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="lh"
                    name="<?php echo esc_attr($name); ?>[lh]"
                    value="<?php echo esc_attr($element['lh']); ?>"
                    placeholder="1.6"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size">Velikost</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size"
                    type="text"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="size"
                    name="<?php echo esc_attr($name); ?>[size]"
                    value="<?php echo esc_attr($element['size']); ?>"
                    placeholder="neměnit"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size-t">Velikost tablet</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size-t"
                    type="text"
                    class="garry-typo-input"
                    name="<?php echo esc_attr($name); ?>[size_t]"
                    value="<?php echo esc_attr($element['size_t']); ?>"
                    placeholder="neměnit"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size-m">Velikost mobil</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size-m"
                    type="text"
                    class="garry-typo-input"
                    name="<?php echo esc_attr($name); ?>[size_m]"
                    value="<?php echo esc_attr($element['size_m']); ?>"
                    placeholder="neměnit"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-tt">Velikost písmen</label>
                <select
                    id="<?php echo esc_attr($id); ?>-tt"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="tt"
                    name="<?php echo esc_attr($name); ?>[tt]"
                >
                    <?php foreach (garry_typo_transform_choices() as $tt_key => $tt_label) : ?>
                        <option value="<?php echo esc_attr($tt_key); ?>" <?php selected($element['tt'], $tt_key); ?>>
                            <?php echo esc_html($tt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h4 class="garry-typo-sub">Tmavé pozadí — jen to, co se má lišit (prázdné pole = použije se hodnota ze světlého pozadí výše)</h4>
        <div class="garry-typo-element-fields">
            <div class="garry-typo-field garry-typo-field--wide">
                <label for="<?php echo esc_attr($id); ?>-font-dark">Font (tmavé)</label>
                <select
                    id="<?php echo esc_attr($id); ?>-font-dark"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="font_dark"
                    name="<?php echo esc_attr($name); ?>[font_dark]"
                >
                    <?php foreach (array_merge(array('' => '— stejné jako světlé —'), $font_choices) as $font_key => $font_label) : ?>
                        <option value="<?php echo esc_attr($font_key); ?>" <?php selected($element['font_dark'], $font_key); ?>>
                            <?php echo esc_html($font_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-weight-dark">Tučnost (tmavé)</label>
                <select
                    id="<?php echo esc_attr($id); ?>-weight-dark"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="weight_dark"
                    name="<?php echo esc_attr($name); ?>[weight_dark]"
                >
                    <option value="">— stejné —</option>
                    <?php foreach (garry_typo_weight_choices() as $weight_key => $weight_label) : ?>
                        <option value="<?php echo esc_attr($weight_key); ?>" <?php selected($element['weight_dark'], $weight_key); ?>>
                            <?php echo esc_html($weight_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-ls-dark">Rozpal písmen (tmavé)</label>
                <input
                    id="<?php echo esc_attr($id); ?>-ls-dark"
                    type="text"
                    class="garry-typo-input garry-typo-letterspacing"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="ls_dark"
                    name="<?php echo esc_attr($name); ?>[ls_dark]"
                    value="<?php echo esc_attr($element['ls_dark']); ?>"
                    placeholder="— stejné —"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-lh-dark">Řádkování (tmavé)</label>
                <input
                    id="<?php echo esc_attr($id); ?>-lh-dark"
                    type="text"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="lh_dark"
                    name="<?php echo esc_attr($name); ?>[lh_dark]"
                    value="<?php echo esc_attr($element['lh_dark']); ?>"
                    placeholder="— stejné —"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size-dark">Velikost (tmavé)</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size-dark"
                    type="text"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="size_dark"
                    name="<?php echo esc_attr($name); ?>[size_dark]"
                    value="<?php echo esc_attr($element['size_dark']); ?>"
                    placeholder="— stejné —"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size-t-dark">Velikost tablet (tmavé)</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size-t-dark"
                    type="text"
                    class="garry-typo-input"
                    name="<?php echo esc_attr($name); ?>[size_t_dark]"
                    value="<?php echo esc_attr($element['size_t_dark']); ?>"
                    placeholder="— stejné —"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-size-m-dark">Velikost mobil (tmavé)</label>
                <input
                    id="<?php echo esc_attr($id); ?>-size-m-dark"
                    type="text"
                    class="garry-typo-input"
                    name="<?php echo esc_attr($name); ?>[size_m_dark]"
                    value="<?php echo esc_attr($element['size_m_dark']); ?>"
                    placeholder="— stejné —"
                >
            </div>

            <div class="garry-typo-field">
                <label for="<?php echo esc_attr($id); ?>-tt-dark">Velikost písmen (tmavé)</label>
                <select
                    id="<?php echo esc_attr($id); ?>-tt-dark"
                    class="garry-typo-input"
                    data-el="<?php echo esc_attr($key); ?>"
                    data-prop="tt_dark"
                    name="<?php echo esc_attr($name); ?>[tt_dark]"
                >
                    <option value="">— stejné —</option>
                    <?php foreach (garry_typo_transform_choices() as $tt_key => $tt_label) : ?>
                        <option value="<?php echo esc_attr($tt_key); ?>" <?php selected($element['tt_dark'], $tt_key); ?>>
                            <?php echo esc_html($tt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php
        $class_prefix = isset($settings['class_prefix']) && $settings['class_prefix'] !== '' ? $settings['class_prefix'] : 'garry-typo';
        $class_name = $class_prefix . '-' . str_replace('_', '-', $key);
        ?>
        <p class="garry-typo-element-desc">
            Třída pro ruční přiřazení v Divi builderu (pole „CSS Class" u libovolného modulu):
            <code>.<?php echo esc_html($class_name); ?></code> — pro vynucení tmavé varianty i mimo rozpoznanou tmavou sekci přidejte navíc <code>.<?php echo esc_html($class_prefix); ?>-dark</code>.
        </p>

        <div class="garry-typo-element-fields">
            <?php
            $palette = garry_typo_color_palette();

            garry_typo_render_color_field(
                $name . '[color]',
                $id . '-color',
                'Barva na světlém pozadí',
                $element['color'],
                $key,
                'color',
                $palette
            );

            garry_typo_render_color_field(
                $name . '[color_dark]',
                $id . '-color-dark',
                'Barva na zeleném pozadí',
                $element['color_dark'],
                $key,
                'color_dark',
                $palette
            );
            ?>

            <?php if (!empty($definition['extras']) && in_array('menu_item_gap', $definition['extras'], true)) : ?>
                <div class="garry-typo-field">
                    <label for="garry-typo-menu-item-gap">Mezera mezi položkami</label>
                    <input
                        id="garry-typo-menu-item-gap"
                        type="text"
                        class="garry-typo-input"
                        name="<?php echo esc_attr($option_name); ?>[menu_item_gap]"
                        value="<?php echo esc_attr($settings['menu_item_gap']); ?>"
                        placeholder="např. 18px"
                    >
                </div>
            <?php endif; ?>
        </div>

        <div class="garry-typo-selectors" hidden>
            <h4 class="garry-typo-sub">Mezery, zarovnání a stavy odkazu</h4>

            <div class="garry-typo-element-fields garry-typo-element-fields--advanced">
                <div class="garry-typo-field">
                    <label for="<?php echo esc_attr($id); ?>-mb">Mezera pod prvkem</label>
                    <input
                        id="<?php echo esc_attr($id); ?>-mb"
                        type="text"
                        class="garry-typo-input"
                        data-el="<?php echo esc_attr($key); ?>"
                        data-prop="mb"
                        name="<?php echo esc_attr($name); ?>[mb]"
                        value="<?php echo esc_attr($element['mb']); ?>"
                        placeholder="např. 1em"
                    >
                </div>

                <div class="garry-typo-field">
                    <label for="<?php echo esc_attr($id); ?>-pad">Vnitřní odsazení</label>
                    <input
                        id="<?php echo esc_attr($id); ?>-pad"
                        type="text"
                        class="garry-typo-input"
                        data-el="<?php echo esc_attr($key); ?>"
                        data-prop="pad"
                        name="<?php echo esc_attr($name); ?>[pad]"
                        value="<?php echo esc_attr($element['pad']); ?>"
                        placeholder="např. 0.5em"
                    >
                </div>

                <div class="garry-typo-field">
                    <label for="<?php echo esc_attr($id); ?>-align">Zarovnání</label>
                    <select
                        id="<?php echo esc_attr($id); ?>-align"
                        class="garry-typo-input"
                        data-el="<?php echo esc_attr($key); ?>"
                        data-prop="align"
                        name="<?php echo esc_attr($name); ?>[align]"
                    >
                        <?php foreach (garry_typo_align_choices() as $align_key => $align_label) : ?>
                            <option value="<?php echo esc_attr($align_key); ?>" <?php selected($element['align'], $align_key); ?>>
                                <?php echo esc_html($align_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="garry-typo-field">
                    <label for="<?php echo esc_attr($id); ?>-deco">Podtržení</label>
                    <select
                        id="<?php echo esc_attr($id); ?>-deco"
                        class="garry-typo-input"
                        data-el="<?php echo esc_attr($key); ?>"
                        data-prop="deco"
                        name="<?php echo esc_attr($name); ?>[deco]"
                    >
                        <?php foreach (garry_typo_decoration_choices() as $deco_key => $deco_label) : ?>
                            <option value="<?php echo esc_attr($deco_key); ?>" <?php selected($element['deco'], $deco_key); ?>>
                                <?php echo esc_html($deco_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php
                garry_typo_render_color_field(
                    $name . '[color_hover]',
                    $id . '-color-hover',
                    'Barva při najetí (světlé)',
                    $element['color_hover'],
                    $key,
                    'color_hover',
                    $palette
                );

                garry_typo_render_color_field(
                    $name . '[color_hover_dark]',
                    $id . '-color-hover-dark',
                    'Barva při najetí (zelené)',
                    $element['color_hover_dark'],
                    $key,
                    'color_hover_dark',
                    $palette
                );
                ?>
            </div>

            <h4 class="garry-typo-sub">Selektory</h4>
            <p class="garry-typo-help">Nastavení se propíše na tyto selektory:</p>
            <div class="garry-typo-selector-list">
                <?php foreach ($definition['selectors'] as $selector) : ?>
                    <code><?php echo esc_html($selector); ?></code>
                <?php endforeach; ?>
            </div>

            <div class="garry-typo-field garry-typo-field--full">
                <label for="<?php echo esc_attr($id); ?>-sel">Doplňkové selektory</label>
                <input
                    id="<?php echo esc_attr($id); ?>-sel"
                    type="text"
                    class="garry-typo-input garry-typo-wide"
                    name="<?php echo esc_attr($name); ?>[sel]"
                    value="<?php echo esc_attr($element['sel']); ?>"
                    placeholder="např. .col-service .et_pb_module_heading"
                >
                <p class="description">Oddělujte čárkou. Slouží pro místa, která standardní selektory nepokryjí.</p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Ukázkový obsah náhledu. Vykresluje se dvakrát – jednou na světlém
 * a jednou na zeleném pozadí, protože web používá obě prostředí.
 */
function garry_typo_render_preview_sample() {
    ?>
    <div class="garry-typo-preview-nav" data-preview="menu">Pokoje &nbsp;·&nbsp; Zážitky &nbsp;·&nbsp; Kontakt</div>
    <div class="garry-typo-preview-nav" data-preview="section_nav">Ubytování &nbsp;·&nbsp; Gastronomie &nbsp;·&nbsp; Sezóna</div>

    <h1 data-preview="h1">Nadpis H1</h1>
    <h2 data-preview="h2">Kde po jízdě zastavíš</h2>
    <h3 data-preview="h3">Jediný hotel uvnitř trati</h3>
    <h4 data-preview="h4">Nadpis H4</h4>
    <h5 data-preview="h5">Nadpis H5</h5>
    <h6 data-preview="h6">Nadpis H6</h6>

    <div class="garry-typo-preview-modheading" data-preview="module_heading">Nadpis modulu Divi</div>

    <p data-preview="p">
        Příklad běžného odstavce s českou diakritikou: příliš žluťoučký kůň úpěl ďábelské ódy.
        Uvnitř je také <a href="#" data-preview="a" onclick="return false;">odkaz v textu</a>.
    </p>

    <ul data-preview="ul">
        <li>První položka odrážkového seznamu</li>
        <li>Druhá položka odrážkového seznamu</li>
    </ul>

    <ol data-preview="ol">
        <li>První položka číslovaného seznamu</li>
        <li>Druhá položka číslovaného seznamu</li>
    </ol>

    <table class="garry-typo-preview-table">
        <tr>
            <th data-preview="table_th">Pokoj</th>
            <th data-preview="table_th">Cena od</th>
        </tr>
        <tr>
            <td data-preview="table_td">Superior</td>
            <td data-preview="table_td">2 490 Kč</td>
        </tr>
    </table>

    <blockquote data-preview="blockquote">Ukázka citace v textu.</blockquote>
    <div class="garry-typo-preview-anchors" data-preview="anchor_nav">Pro koho je pobyt vhodný</div>
    <div class="garry-typo-preview-caption" data-preview="caption">Popisek obrázku</div>

    <p>
        <span class="garry-typo-preview-toggle" data-preview="toggle_button">Přečíst více…</span>
        <span class="garry-typo-preview-button" data-preview="button">Rezervovat pobyt</span>
    </p>

    <label class="garry-typo-preview-formlabel" data-preview="form">Jméno a příjmení</label>
    <?php
}

/**
 * Pravý panel – živý náhled a test diakritiky.
 */
function garry_typo_render_preview_panel($settings) {
    $font_choices = garry_typo_font_choices($settings);
    ?>
    <aside class="garry-typo-preview-panel">

        <div class="garry-typo-preview-card">
            <h2>Živý náhled typografie</h2>
            <p class="garry-typo-help">
                Ukázka se mění podle aktuálně vyplněných hodnot, ještě před uložením.
                Web používá dvě barevná prostředí, proto je náhled ve dvou variantách.
            </p>

            <h3 class="garry-typo-preview-heading">Světlé pozadí</h3>
            <div class="garry-typo-preview garry-typo-preview--light" data-context="light">
                <?php garry_typo_render_preview_sample(); ?>
            </div>

            <h3 class="garry-typo-preview-heading">Zelené pozadí</h3>
            <div class="garry-typo-preview garry-typo-preview--dark" data-context="dark">
                <?php garry_typo_render_preview_sample(); ?>
            </div>
        </div>

        <div class="garry-typo-preview-card">
            <h2>Test české diakritiky</h2>
            <p class="garry-typo-help">
                Prohlížeč zde ověří, zda vybraný font opravdu vykresluje i znaky s diakritikou,
                nebo zda je nahrazuje záložním fontem. Přesně to je závada hlášená klientem.
            </p>

            <div class="garry-typo-field garry-typo-field--full">
                <label for="garry-typo-test-font">Testovaný font</label>
                <select id="garry-typo-test-font" class="garry-typo-wide">
                    <?php foreach ($font_choices as $font_key => $font_label) : ?>
                        <?php if ($font_key === '' || $font_key === 'inherit') { continue; } ?>
                        <option value="<?php echo esc_attr($font_key); ?>"><?php echo esc_html($font_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="garry-typo-test-verdict" class="garry-typo-verdict">Probíhá měření…</div>

            <div class="garry-typo-test-samples" id="garry-typo-test-samples">
                <?php foreach (array('100', '300', '400', '600', '700', '900') as $weight) : ?>
                    <div class="garry-typo-test-row">
                        <span class="garry-typo-test-weight"><?php echo esc_html($weight); ?></span>
                        <span class="garry-typo-test-text" data-weight="<?php echo esc_attr($weight); ?>">
                            ÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ áčďéěíňóřšťúůýž
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
    <?php
}

/* ============================================================================
 * 4) Hlavní stránka
 * ========================================================================= */

function garry_typo_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = garry_typo_get_settings();
    $option_name = GARRY_TYPO_OPTION;
    $elements = garry_typo_elements();
    $generated_css = garry_typo_build_css($settings);
    ?>
    <div class="wrap garry-typo-wrap">
        <h1>Typografie a fonty</h1>

        <div class="garry-typo-version-pill">
            Aktuálně načtená verze pluginu v administraci: <?php echo esc_html(GARRY_TYPO_VERSION); ?>
        </div>

        <div class="garry-typo-card garry-typo-intro">
            <h2>Jednotná typografie webu</h2>

            <p>
                Tento plugin sjednocuje písma na celém webu na jednom místě – místo toho, aby se nastavovala
                zvlášť v motivu Divi, zvlášť v jednotlivých modulech a zvlášť ve vlastních snippetech.
            </p>

            <p>
                Pro každou typografickou úroveň – nadpisy H1 až H6, odstavce, odrážkové i číslované seznamy,
                tabulky, menu a tlačítka – lze nastavit font, tučnost, velikost pro počítač, tablet i mobil,
                řádkování a především <strong>rozpal mezi písmeny</strong>, na který mířila většina připomínek klienta.
            </p>

            <p>
                Protože jsou záhlaví i zápatí v Divi řešená jako globální prvky, projeví se změna nastavení
                na všech stránkách webu najednou.
            </p>
        </div>

        <?php garry_typo_render_deployment_check($settings, $generated_css); ?>

        <?php garry_typo_render_diagnostics($settings); ?>

        <form method="post" action="options.php">
            <?php settings_fields('garry_typo_settings_group'); ?>

            <div class="garry-typo-layout">
                <div class="garry-typo-main">

                    <div class="garry-typo-card">
                        <h2>Zapnutí a vypnutí</h2>

                        <div class="garry-typo-switch-row">
                            <label class="garry-typo-switch">
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr($option_name); ?>[enabled]"
                                    value="1"
                                    <?php checked($settings['enabled'], '1'); ?>
                                >
                                <span class="garry-typo-switch__knob"></span>
                                <span class="garry-typo-switch__text">
                                    <span class="garry-typo-switch__text-on">typografie je zapnutá</span>
                                    <span class="garry-typo-switch__text-off">typografie je vypnutá</span>
                                </span>
                            </label>

                            <div class="garry-typo-state">
                                <strong>Aktuální stav</strong>
                                <span id="garry-typo-state-label">
                                    <?php echo $settings['enabled'] === '1'
                                        ? 'Plugin vypisuje na web vlastní styly písem podle nastavení níže.'
                                        : 'Plugin na web nic nevypisuje. Písma řídí motiv Divi a případné vlastní snippety.'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="garry-typo-card">
                        <h2>Společná nastavení</h2>

                        <div class="garry-typo-form-grid">
                            <label for="garry-typo-fallback">Záložní fontová sada</label>
                            <div>
                                <input
                                    id="garry-typo-fallback"
                                    type="text"
                                    class="garry-typo-wide"
                                    name="<?php echo esc_attr($option_name); ?>[fallback_stack]"
                                    value="<?php echo esc_attr($settings['fallback_stack']); ?>"
                                >
                                <p class="description">
                                    Doplní se za každý vybraný font. Použije se, když se hlavní font nenačte
                                    nebo neobsahuje potřebný znak.
                                </p>
                            </div>

                            <label>Chování stylů</label>
                            <div>
                                <label class="garry-typo-check">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr($option_name); ?>[force_important]"
                                        value="1"
                                        <?php checked($settings['force_important'], '1'); ?>
                                    >
                                    <span>Vynutit přepis stylů Divi pomocí <code>!important</code></span>
                                </label>

                                <label class="garry-typo-check">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr($option_name); ?>[apply_in_builder]"
                                        value="1"
                                        <?php checked($settings['apply_in_builder'], '1'); ?>
                                    >
                                    <span>Použít styly i v editoru Divi, aby náhled odpovídal webu</span>
                                </label>
                            </div>

                            <label for="garry-typo-ls-global">Společný rozpal písmen</label>
                            <div>
                                <div class="garry-typo-inline-fields">
                                    <input
                                        id="garry-typo-ls-global"
                                        type="text"
                                        class="garry-typo-input garry-typo-letterspacing"
                                        data-el="__global__"
                                        data-prop="ls_global"
                                        name="<?php echo esc_attr($option_name); ?>[ls_global_value]"
                                        value="<?php echo esc_attr($settings['ls_global_value']); ?>"
                                        placeholder="normal"
                                    >
                                    <label for="garry-typo-ls-global-font">u textů psaných fontem</label>
                                    <select
                                        id="garry-typo-ls-global-font"
                                        class="garry-typo-input"
                                        data-el="__global__"
                                        data-prop="ls_global_font"
                                        name="<?php echo esc_attr($option_name); ?>[ls_global_font]"
                                    >
                                        <?php foreach (garry_typo_font_choices($settings) as $font_key => $font_label) : ?>
                                            <?php if ($font_key === '') { continue; } ?>
                                            <option value="<?php echo esc_attr($font_key); ?>" <?php selected($settings['ls_global_font'], $font_key); ?>>
                                                <?php echo esc_html($font_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <p class="description">
                                    Nastaví rozpal najednou u všech zapnutých úrovní psaných vybraným fontem, které
                                    nemají vyplněný vlastní rozpal. Vlastní hodnota u úrovně má vždy přednost.
                                    <br>
                                    Používejte jednotku <code>em</code>, ne pixely: <code>em</code> se počítá z velikosti
                                    písma daného prvku, takže stejná hodnota sedí na velký nadpis i na drobný popisek.
                                    Rozumné hodnoty jsou <code>normal</code> (rozpal navržený autorem písma),
                                    <code>0.02em</code> až <code>0.05em</code> pro nadpisy psané verzálkami
                                    a <code>-0.01em</code> pro hodně velké nadpisy.
                                </p>
                            </div>

                            <label for="garry-typo-dark-context">Jak poznat zelenou sekci</label>
                            <div>
                                <input
                                    id="garry-typo-dark-context"
                                    type="text"
                                    class="garry-typo-wide"
                                    name="<?php echo esc_attr($option_name); ?>[dark_context]"
                                    value="<?php echo esc_attr($settings['dark_context']); ?>"
                                    placeholder=".et_pb_bg_layout_dark, .et_pb_section_dark"
                                >
                                <p class="description">
                                    Selektory sekcí s tmavým pozadím, oddělené čárkou. Uvnitř nich se u každé úrovně
                                    použije druhá barva, aby byl text na zelené ploše čitelný. Divi si třídu
                                    <code>.et_pb_bg_layout_dark</code> doplňuje samo; pokud má některá sekce vlastní
                                    třídu, dopište ji sem.
                                </p>
                            </div>

                            <label for="garry-typo-class-prefix">Prefix pro globální třídy</label>
                            <div>
                                <input
                                    id="garry-typo-class-prefix"
                                    type="text"
                                    class="garry-typo-wide"
                                    name="<?php echo esc_attr($option_name); ?>[class_prefix]"
                                    value="<?php echo esc_attr($settings['class_prefix']); ?>"
                                    placeholder="garry-typo"
                                >
                                <p class="description">
                                    Ke každé zapnuté úrovni plugin navíc vygeneruje třídu
                                    <code>.{prefix}-{úroveň}</code> (např. <code>.garry-typo-h1</code>), kterou lze
                                    v Divi builderu ručně přiřadit libovolnému modulu přes pole „CSS Class" — bez
                                    ohledu na to, jaký HTML tag modul zrovna vykresluje. Přesný název třídy pro
                                    každou úroveň je vidět pod jejím nastavením výše.
                                </p>
                            </div>

                            <label for="garry-typo-strength">Síla přepisu</label>
                            <div>
                                <select
                                    id="garry-typo-strength"
                                    class="garry-typo-wide"
                                    name="<?php echo esc_attr($option_name); ?>[override_strength]"
                                >
                                    <?php foreach (garry_typo_strength_choices() as $key => $label) : ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($settings['override_strength'], $key); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <p class="description">
                                    Běžná síla stačí na globální nastavení motivu. Vysokou sílu zapněte, pokud
                                    se některý nadpis nebo blok tvrdohlavě drží starého písma – typicky proto,
                                    že má font nastavený přímo u sebe v editoru Divi nebo ve vlastním CSS.
                                </p>
                            </div>
                        </div>

                        <div class="garry-typo-actions">
                            <button type="button" class="button" id="garry-typo-reset-tracking">
                                Vynulovat rozpal u všech úrovní
                            </button>
                            <span class="garry-typo-actions-note">
                                Tlačítko jen vyplní formulář. Změna se projeví až po uložení.
                            </span>
                        </div>
                    </div>

                    <?php garry_typo_render_custom_font_card($settings, $option_name); ?>

                    <?php foreach (garry_typo_element_groups() as $group) : ?>
                        <div class="garry-typo-card">
                            <h2><?php echo esc_html($group['label']); ?></h2>
                            <p class="garry-typo-help"><?php echo esc_html($group['desc']); ?></p>

                            <?php foreach ($group['elements'] as $element_key) : ?>
                                <?php if (!isset($elements[$element_key])) { continue; } ?>
                                <?php garry_typo_render_element($element_key, $elements[$element_key], $settings, $option_name); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="garry-typo-card">
                        <h2>Vygenerované CSS</h2>
                        <p class="garry-typo-help">
                            Přesně tento blok stylů plugin vypisuje do hlavičky webu podle naposledy uloženého
                            nastavení. Slouží ke kontrole a k případnému předání kódu.
                        </p>

                        <?php if (trim($generated_css) === '') : ?>
                            <p class="garry-typo-dim">Zatím se negeneruje žádné CSS – není zapnutá žádná úroveň.</p>
                        <?php else : ?>
                            <pre class="garry-typo-code"><?php echo esc_html($generated_css); ?></pre>
                        <?php endif; ?>
                    </div>

                    <?php submit_button('Uložit nastavení typografie'); ?>
                </div>

                <?php garry_typo_render_preview_panel($settings); ?>
            </div>
        </form>
    </div>
    <?php
}
