<?php
/**
 * Plugin Name:       GARRY – Situace na trati (widget)
 * Plugin URI:        https://www.garry.cz
 * Description:       Plovoucí widget „živá situace na trati" pro GRID Hotel. Data se tahají automaticky z Open-Meteo podle GPS okruhu (teplota, pocitová, vlhkost, oblačnost, srážky, vítr, slovní popis počasí) a povrch trati se z počasí automaticky odhaduje. V nastavení lze volit zdroj (GPS), obnovovací interval, zobrazené údaje, ruční „Status" a výškovou pozici; živý náhled.
 * Version:           1.3.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-situace-na-trati
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'ABSPATH' ) ) exit;

/* ============================================================================
 * GARRY Promotion – sdílený rámec pro mikropluginy (verze 2.0.0)
 * ============================================================================
 * Tento blok je IDENTICKY obsažen v každém mikropluginu od GARRY Promotion.
 * Veškeré definice jsou chráněné podmínkami if (!class_exists()) tak, aby si
 * pluginy navzájem nepřebíjely kód při různém pořadí načtení.
 *
 * Princip:
 *  - Každý plugin se zaregistruje voláním Garry_Promotion_Registry::register().
 *  - Registr je uložen v $GLOBALS['garry_promotion_plugins'] (sdílený mezi pluginy).
 *  - V admin_menu hooku se z registru vytvoří JEDINÁ hlavní položka „GARRY
 *    nastavení" s podpoložkami pro každý zaregistrovaný plugin a vždy
 *    poslední podpoložkou „Info" s prezentací agentury.
 *  - Hlavní položka má barevné SVG logo (vykreslené přes inline CSS data: URI),
 *    podpoložky mají dashicons ikonu zadanou v registru.
 *  - Vše respektuje aktivní/neaktivní stav: neaktivní plugin neregistruje nic,
 *    takže jeho podpoložka v menu prostě není.
 *
 * Tím lze do budoucna přidávat libovolné další mikropluginy bez úprav existujících.
 * ============================================================================
 */

if (!class_exists('Garry_Promotion_Registry')) {

    class Garry_Promotion_Registry {

        const FRAMEWORK_VERSION = '2.1.0';
        const MENU_SLUG         = 'garry-nastaveni';
        const INFO_SLUG         = 'garry-info';
        const CAPABILITY        = 'manage_options';
        const STAFF_CAPABILITY  = 'edit_others_posts'; // personál hotelu (role Editor a výš)
        const VISIBILITY_OPTION = 'garry_grid_visibility';
        const MENU_POSITION     = 81;

        /**
         * Zaregistruje plugin v globálním sdíleném registru.
         *
         * @param array $args {
         *     @type string   $slug         Slug pro add_submenu_page (povinný).
         *     @type string   $title        Název podstránky v menu (povinný).
         *     @type callable $callback     Vykreslovací funkce admin stránky (povinný).
         *     @type string   $plugin_file  __FILE__ daného pluginu (povinný kvůli lokaci loga).
         *     @type string   $dashicon     CSS třída dashicons (např. dashicons-editor-expand).
         *     @type int      $position     Pořadí v podnabídce (čím menší, tím dřív).
         * }
         */
        public static function register(array $args) {
            $defaults = array(
                'slug'        => '',
                'title'       => '',
                'callback'    => null,
                'plugin_file' => '',
                'dashicon'    => 'dashicons-admin-generic',
                'position'    => 50,
                'doc'         => '',  // HTML dokumentace implementace (shortcody, widgety)
                'grid_slug'   => '',  // slug editační podstránky v „GRID Nastavení" (pokud existuje)
            );

            $args = array_merge($defaults, $args);

            // Minimální validace – bez ní položku do registru nepřidáme.
            if ($args['slug'] === '' || $args['title'] === '' || !is_callable($args['callback'])) {
                return;
            }

            if (!isset($GLOBALS['garry_promotion_plugins']) || !is_array($GLOBALS['garry_promotion_plugins'])) {
                $GLOBALS['garry_promotion_plugins'] = array();
            }

            // Klíč podle slugu eliminuje duplicity.
            $GLOBALS['garry_promotion_plugins'][$args['slug']] = $args;
        }

        /**
         * Vrátí všechny registrované (= aktivní) pluginy seřazené podle position.
         */
        public static function get_plugins() {
            $plugins = isset($GLOBALS['garry_promotion_plugins']) && is_array($GLOBALS['garry_promotion_plugins'])
                ? $GLOBALS['garry_promotion_plugins']
                : array();

            uasort($plugins, function ($a, $b) {
                $pa = isset($a['position']) ? (int) $a['position'] : 50;
                $pb = isset($b['position']) ? (int) $b['position'] : 50;
                if ($pa === $pb) {
                    return strcmp((string) $a['title'], (string) $b['title']);
                }
                return $pa - $pb;
            });

            return $plugins;
        }

        /**
         * Má se editační stránka pluginu zobrazovat personálu v „GRID Nastavení"?
         * Řídí se checkboxy na přehledové stránce GARRY nastavení. Výchozí: ano.
         */
        public static function grid_visible($slug) {
            $v = get_option(self::VISIBILITY_OPTION, array());
            if (!is_array($v) || !array_key_exists($slug, $v)) {
                return true;
            }
            return !empty($v[$slug]);
        }

        /**
         * Najde data: URI loga GARRY. Logo bere z prvního dostupného pluginu,
         * který ho má ve své assets/garry-logo.svg složce.
         * Výsledek je cachovaný v rámci jednoho requestu.
         */
        public static function locate_logo_uri() {
            static $cached_uri = null;

            if ($cached_uri !== null) {
                return $cached_uri;
            }

            foreach (self::get_plugins() as $plugin) {
                if (empty($plugin['plugin_file'])) {
                    continue;
                }

                $path = plugin_dir_path($plugin['plugin_file']) . 'assets/garry-logo.svg';

                if (is_readable($path)) {
                    $svg = @file_get_contents($path);
                    if ($svg !== false && $svg !== '') {
                        $cached_uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
                        return $cached_uri;
                    }
                }
            }

            $cached_uri = '';
            return $cached_uri;
        }

        /**
         * Mapuje názvy dashicons CSS tříd na unicode glyfy.
         * Submenu položkám WordPress dashicon ikonu sám nevykresluje,
         * takže si ji vykreslíme přes ::before pseudoprvek.
         */
        public static function dashicon_glyph($dashicon_class) {
            $map = array(
                'dashicons-admin-generic'      => 'f111',
                'dashicons-admin-settings'     => 'f108',
                'dashicons-admin-tools'        => 'f107',
                'dashicons-admin-customizer'   => 'f540',
                'dashicons-admin-appearance'   => 'f100',
                'dashicons-admin-comments'     => 'f117',
                'dashicons-editor-textcolor'   => 'f215',
                'dashicons-editor-expand'      => 'f211',
                'dashicons-editor-contract'    => 'f506',
                'dashicons-editor-alignleft'   => 'f207',
                'dashicons-editor-paragraph'   => 'f476',
                'dashicons-text'               => 'f478',
                'dashicons-format-aside'       => 'f123',
                'dashicons-format-status'      => 'f130',
                'dashicons-format-chat'        => 'f125',
                'dashicons-megaphone'          => 'f488',
                'dashicons-info'               => 'f348',
                'dashicons-info-outline'       => 'f14c',
                'dashicons-welcome-write-blog' => 'f119',
                'dashicons-lightbulb'          => 'f339',
                'dashicons-warning'            => 'f534',
                'dashicons-bell'               => 'f471',
                'dashicons-flag'               => 'f227',
            );

            return isset($map[$dashicon_class]) ? $map[$dashicon_class] : 'f111';
        }

        /**
         * Sestavení hlavní položky menu, podpoložek a stránky „Info".
         * Volá se v admin_menu hooku s prioritou 99, aby byl registr již naplněný.
         */
        public static function build_admin_menu() {
            add_menu_page(
                'GARRY nastavení',
                'GARRY nastavení',
                self::CAPABILITY,
                self::MENU_SLUG,
                array(__CLASS__, 'render_overview_page'),
                'none', // ikonu vykreslíme inline CSS s barevným SVG
                self::MENU_POSITION
            );

            // Přejmenujeme výchozí duplicitní podpoložku „GARRY nastavení" na „Přehled".
            add_submenu_page(
                self::MENU_SLUG,
                'GARRY nastavení – přehled',
                'Přehled',
                self::CAPABILITY,
                self::MENU_SLUG,
                array(__CLASS__, 'render_overview_page')
            );

            // Podpoložky podle registru (řazené podle position).
            $position = 10;
            foreach (self::get_plugins() as $plugin) {
                add_submenu_page(
                    self::MENU_SLUG,
                    $plugin['title'],
                    $plugin['title'],
                    self::CAPABILITY,
                    $plugin['slug'],
                    $plugin['callback'],
                    $position
                );
                $position += 10;
            }

            // Vždy poslední položka „Info" (vysoká position).
            add_submenu_page(
                self::MENU_SLUG,
                'GARRY Promotion – Info',
                'Info',
                self::CAPABILITY,
                self::INFO_SLUG,
                array(__CLASS__, 'render_info_page'),
                9999
            );
        }

        /**
         * Vykreslení přehledové stránky GARRY nastavení.
         */
        public static function render_overview_page() {
            if (!current_user_can(self::CAPABILITY)) {
                return;
            }

            $plugins = self::get_plugins();

            // Uložení viditelnosti v GRID Nastavení (pouze administrátor).
            if (isset($_POST['garry_grid_visibility_nonce'])
                && wp_verify_nonce($_POST['garry_grid_visibility_nonce'], 'garry_grid_visibility')
                && current_user_can('manage_options')) {
                $vis = array();
                foreach ($plugins as $slug => $p) {
                    if (empty($p['grid_slug'])) continue;
                    $vis[$slug] = empty($_POST['garry_vis'][$slug]) ? 0 : 1;
                }
                update_option(self::VISIBILITY_OPTION, $vis, false);
                echo '<div class="notice notice-success is-dismissible"><p>Viditelnost v GRID Nastavení uložena. Změna se projeví po novém načtení stránky.</p></div>';
            }
            ?>
            <div class="wrap garry-admin-wrap">
                <h1>GARRY nastavení</h1>
                <p class="garry-admin-lead">
                    Společné administrační místo pro zakázkové mikropluginy vytvořené agenturou
                    <strong>GARRY Promotion</strong>. Jednotlivé pluginy se zde objevují automaticky podle toho,
                    které jsou aktivní.
                </p>

                <?php if (!empty($plugins)) : ?>
                    <h2 class="title">Aktivní GARRY mikropluginy na tomto webu</h2>
                    <div class="garry-admin-cards">
                        <?php foreach ($plugins as $plugin) : ?>
                            <a class="garry-admin-card" href="<?php echo esc_url(admin_url('admin.php?page=' . $plugin['slug'])); ?>">
                                <span class="dashicons <?php echo esc_attr($plugin['dashicon']); ?>"></span>
                                <span class="garry-admin-card-title"><?php echo esc_html($plugin['title']); ?></span>
                                <span class="garry-admin-card-arrow dashicons dashicons-arrow-right-alt2"></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="notice notice-warning inline">
                        <p>Aktuálně není zaregistrován žádný GARRY mikroplugin. Aktivujte některý z pluginů,
                        nebo se podívejte do nabídky agentury.</p>
                    </div>
                <?php endif; ?>

                <?php $grid_plugins = array_filter($plugins, function ($p) { return !empty($p['grid_slug']); }); ?>
                <?php if (!empty($grid_plugins)) : ?>
                    <h2 class="title">Viditelnost v „GRID Nastavení" (co vidí personál)</h2>
                    <p class="garry-admin-lead">Zaškrtnuté pluginy mají svou editační stránku v menu
                    <strong>GRID Nastavení</strong>, kde obsah spravuje personál hotelu (role Editor a výš).
                    Odškrtnutím položku personálu skryjete — data zůstávají, zmizí jen položka menu.</p>
                    <form method="post">
                        <?php wp_nonce_field('garry_grid_visibility', 'garry_grid_visibility_nonce'); ?>
                        <table class="widefat striped" style="max-width:620px">
                            <tbody>
                            <?php foreach ($grid_plugins as $slug => $p) : ?>
                                <tr><td style="padding:10px 14px">
                                    <label><input type="checkbox" name="garry_vis[<?php echo esc_attr($slug); ?>]" value="1" <?php checked(self::grid_visible($slug)); ?>>
                                    <strong><?php echo esc_html($p['title']); ?></strong></label>
                                </td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php submit_button('Uložit viditelnost'); ?>
                    </form>
                <?php endif; ?>

                <?php $doc_plugins = array_filter($plugins, function ($p) { return !empty($p['doc']); }); ?>
                <?php if (!empty($doc_plugins)) : ?>
                    <h2 class="title">Implementační dokumentace (shortcody a widgety)</h2>
                    <p class="garry-admin-lead">Technické informace pro správce webu — kde se jednotlivé
                    prvky vykreslují a jakými shortcody je lze vložit do obsahu.</p>
                    <?php foreach ($doc_plugins as $p) : ?>
                        <details style="background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:10px 14px;margin-bottom:8px;max-width:860px">
                            <summary style="cursor:pointer;font-weight:600"><?php echo esc_html($p['title']); ?></summary>
                            <div style="padding-top:8px"><?php echo wp_kses_post($p['doc']); ?></div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="garry-admin-foot">
                    Více o agentuře a všech našich službách najdete v podnabídce
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::INFO_SLUG)); ?>"><strong>Info</strong></a>.
                </p>
            </div>
            <?php
        }

        /**
         * Vykreslení stránky „Info" – prezentace agentury GARRY Promotion.
         */
        public static function render_info_page() {
            if (!current_user_can(self::CAPABILITY)) {
                return;
            }

            $logo    = self::locate_logo_uri();
            $plugins = self::get_plugins();
            ?>
            <div class="wrap garry-info-wrap">

                <div class="garry-info-hero">
                    <?php if ($logo) : ?>
                        <img class="garry-info-logo" src="<?php echo esc_attr($logo); ?>" alt="GARRY Promotion" />
                    <?php endif; ?>
                    <p class="garry-info-tagline">
                        Fluidní marketingová agentura<br>
                        <span>offline · online · AI</span>
                    </p>
                </div>

                <div class="garry-info-grid">
                    <div class="garry-info-card garry-info-card--about">
                        <h2>Kdo jsme</h2>
                        <p>
                            <strong>GARRY Promotion</strong> je <em>fluidní marketingová agentura</em>,
                            která propojuje tři světy reklamy do jednoho funkčního celku:
                            klasický <strong>offline marketing</strong>, datový <strong>online marketing</strong>
                            a moderní <strong>AI marketing</strong>.
                        </p>
                        <p>
                            Pracujeme rychle, koncepčně a tak, aby každý nástroj v komunikaci klienta měl smysl
                            a navazoval na zbytek. Od velkého formátu na fasádě až po automatizaci ve skladovém
                            systému – pro nás je to jeden projekt.
                        </p>
                    </div>

                    <div class="garry-info-card garry-info-card--services">
                        <h2>Co pro vás děláme</h2>
                        <ul class="garry-info-list">
                            <li><span class="dashicons dashicons-admin-site-alt3"></span><span class="garry-info-list-text">Tvorba <strong>webů na míru</strong> – WordPress, Divi, vlastní šablony</span></li>
                            <li><span class="dashicons dashicons-admin-plugins"></span><span class="garry-info-list-text">Vývoj <strong>pluginů a webových aplikací</strong> přesně podle zadání</span></li>
                            <li><span class="dashicons dashicons-car"></span><span class="garry-info-list-text"><strong>Polepy vozidel</strong>, fasád, výloh a velkoformátový tisk</span></li>
                            <li><span class="dashicons dashicons-megaphone"></span><span class="garry-info-list-text">Výroba <strong>offline reklamy a tiskovin</strong> – od vizitky po billboard</span></li>
                            <li><span class="dashicons dashicons-chart-line"></span><span class="garry-info-list-text"><strong>Online marketing</strong>: SEO, PPC, sociální sítě, mailing</span></li>
                            <li><span class="dashicons dashicons-update-alt"></span><span class="garry-info-list-text"><strong>Automatizace procesů</strong> a integrace mezi systémy</span></li>
                            <li><span class="dashicons dashicons-database"></span><span class="garry-info-list-text">Nastavení <strong>CRM, ERP a WMS</strong> systémů</span></li>
                            <li><span class="dashicons dashicons-superhero"></span><span class="garry-info-list-text"><strong>AI marketing a vibecoding</strong> – nasazení AI nástrojů přímo do provozu firmy</span></li>
                        </ul>
                    </div>

                    <div class="garry-info-card garry-info-card--contact">
                        <h2>Kontakt</h2>
                        <ul class="garry-info-contact">
                            <li>
                                <span class="dashicons dashicons-admin-site"></span>
                                <span class="garry-info-contact-label">Web agentury</span>
                                <a href="https://garry.cz/" target="_blank" rel="noopener noreferrer">garry.cz</a>
                            </li>
                            <li>
                                <span class="dashicons dashicons-businessman"></span>
                                <span class="garry-info-contact-label">Realizace projektů</span>
                                <strong>Michal Truhlář</strong>
                                <a href="mailto:michal@garry.eu">michal@garry.eu</a>
                            </li>
                            <li>
                                <span class="dashicons dashicons-sos"></span>
                                <span class="garry-info-contact-label">Technická podpora</span>
                                <a href="mailto:podpora@garry.eu">podpora@garry.eu</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <?php if (!empty($plugins)) : ?>
                    <div class="garry-info-installed">
                        <h2>Aktivní GARRY mikropluginy na tomto webu</h2>
                        <ul>
                            <?php foreach ($plugins as $plugin) : ?>
                                <li>
                                    <span class="dashicons <?php echo esc_attr($plugin['dashicon']); ?>"></span>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $plugin['slug'])); ?>"><?php echo esc_html($plugin['title']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="garry-info-foot">
                    <small>
                        GARRY framework verze <?php echo esc_html(self::FRAMEWORK_VERSION); ?>.
                        Doprovodné texty: Creative Commons Attribution / Uveďte původ – <strong>GARRY Promotion / Michal Truhlář</strong>.
                    </small>
                </div>
            </div>
            <?php
        }

        /**
         * Inline CSS pro GARRY menu (logo, ikony podpoložek) a stránku Info.
         */
        public static function render_admin_styles() {
            $logo    = self::locate_logo_uri();
            $plugins = self::get_plugins();
            ?>
            <style id="garry-promotion-admin-css">
                /* === Hlavní položka menu – barevné SVG logo === */
                #adminmenu .toplevel_page_<?php echo esc_attr(self::MENU_SLUG); ?> .wp-menu-image {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    opacity: 1 !important;
                    filter: none !important;
                }
                #adminmenu .toplevel_page_<?php echo esc_attr(self::MENU_SLUG); ?> .wp-menu-image:before {
                    content: "" !important;
                    display: block !important;
                    width: 22px !important;
                    height: 22px !important;
                    margin: 0 !important;
                    opacity: 1 !important;
                    filter: none !important;
                    background-repeat: no-repeat !important;
                    background-position: center center !important;
                    background-size: contain !important;
                    <?php if ($logo) : ?>
                    background-image: url('<?php echo esc_attr($logo); ?>') !important;
                    <?php endif; ?>
                }

                /* === Ikony v podnabídce GARRY menu (dynamicky podle registru) === */
                <?php foreach ($plugins as $plugin) : ?>
                #adminmenu .toplevel_page_<?php echo esc_attr(self::MENU_SLUG); ?> .wp-submenu a[href*="page=<?php echo esc_attr($plugin['slug']); ?>"]::before {
                    font-family: dashicons;
                    content: "\<?php echo esc_attr(self::dashicon_glyph($plugin['dashicon'])); ?>";
                    display: inline-block;
                    width: 18px;
                    margin-right: 6px;
                    color: currentColor;
                    font-size: 15px;
                    line-height: 1;
                    vertical-align: -2px;
                }
                <?php endforeach; ?>

                /* Ikona Info položky */
                #adminmenu .toplevel_page_<?php echo esc_attr(self::MENU_SLUG); ?> .wp-submenu a[href*="page=<?php echo esc_attr(self::INFO_SLUG); ?>"]::before {
                    font-family: dashicons;
                    content: "\f348";
                    display: inline-block;
                    width: 18px;
                    margin-right: 6px;
                    color: currentColor;
                    font-size: 15px;
                    line-height: 1;
                    vertical-align: -2px;
                }

                /* === Přehledová stránka – karty === */
                .garry-admin-wrap .garry-admin-lead {
                    font-size: 14px;
                    max-width: 780px;
                    color: #50575e;
                }
                .garry-admin-wrap .garry-admin-cards {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    gap: 14px;
                    margin: 12px 0 18px;
                }
                .garry-admin-wrap .garry-admin-card {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 16px 18px;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 6px;
                    color: #1d2327;
                    text-decoration: none;
                    transition: box-shadow .15s, border-color .15s, transform .15s;
                }
                .garry-admin-wrap .garry-admin-card:hover {
                    border-color: #e30613;
                    box-shadow: 0 4px 12px rgba(227, 6, 19, .12);
                    transform: translateY(-1px);
                }
                .garry-admin-wrap .garry-admin-card > .dashicons {
                    font-size: 22px;
                    width: 22px;
                    height: 22px;
                    color: #e30613;
                    flex-shrink: 0;
                }
                .garry-admin-wrap .garry-admin-card-title {
                    flex: 1;
                    font-weight: 600;
                    font-size: 14px;
                }
                .garry-admin-wrap .garry-admin-card-arrow {
                    color: #8c8f94;
                    font-size: 18px;
                }
                .garry-admin-wrap .garry-admin-foot {
                    margin-top: 16px;
                    font-size: 13px;
                    color: #50575e;
                }

                /* === Info stránka === */
                .garry-info-wrap {
                    max-width: 1100px;
                }
                .garry-info-wrap .garry-info-hero {
                    background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
                    border: 1px solid #c3c4c7;
                    border-radius: 8px;
                    padding: 32px 24px 24px;
                    text-align: center;
                    margin: 16px 0 20px;
                }
                .garry-info-wrap .garry-info-logo {
                    display: block;
                    margin: 0 auto;
                    max-width: 360px;
                    width: 100%;
                    height: auto;
                }
                .garry-info-wrap .garry-info-tagline {
                    margin: 8px 0 0;
                    color: #1d1d1b;
                    font-size: 15px;
                    line-height: 1.5;
                }
                .garry-info-wrap .garry-info-tagline span {
                    color: #e30613;
                    font-weight: 600;
                    letter-spacing: .04em;
                }
                .garry-info-wrap .garry-info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 16px;
                }
                .garry-info-wrap .garry-info-card {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 8px;
                    padding: 20px 22px;
                }
                .garry-info-wrap .garry-info-card h2 {
                    margin: 0 0 12px;
                    color: #1d1d1b;
                    font-size: 17px;
                    border-bottom: 2px solid #e30613;
                    padding-bottom: 8px;
                    display: inline-block;
                }
                .garry-info-wrap .garry-info-card p {
                    margin: 0 0 10px;
                    line-height: 1.55;
                }
                .garry-info-wrap .garry-info-list,
                .garry-info-wrap .garry-info-contact {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                .garry-info-wrap .garry-info-list li {
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                    padding: 8px 0;
                    border-bottom: 1px dashed #e5e7eb;
                    line-height: 1.45;
                }
                .garry-info-wrap .garry-info-list li:last-child {
                    border-bottom: none;
                }
                .garry-info-wrap .garry-info-list .dashicons {
                    color: #e30613;
                    flex-shrink: 0;
                    margin-top: 1px;
                }
                .garry-info-wrap .garry-info-list-text {
                    flex: 1 1 auto;
                    min-width: 0;
                }
                .garry-info-wrap .garry-info-contact li {
                    display: grid;
                    grid-template-columns: 22px 1fr;
                    column-gap: 10px;
                    align-items: baseline;
                    padding: 10px 0;
                    border-bottom: 1px dashed #e5e7eb;
                }
                .garry-info-wrap .garry-info-contact li:last-child {
                    border-bottom: none;
                }
                .garry-info-wrap .garry-info-contact > li > .dashicons {
                    grid-row: 1 / 3;
                    color: #e30613;
                    align-self: center;
                }
                .garry-info-wrap .garry-info-contact-label {
                    grid-column: 2;
                    font-size: 12px;
                    color: #6b7280;
                    text-transform: uppercase;
                    letter-spacing: .04em;
                }
                .garry-info-wrap .garry-info-contact li > strong,
                .garry-info-wrap .garry-info-contact li > a {
                    grid-column: 2;
                }
                .garry-info-wrap .garry-info-contact a {
                    color: #e30613;
                    text-decoration: none;
                    font-weight: 500;
                }
                .garry-info-wrap .garry-info-contact a:hover {
                    text-decoration: underline;
                }
                .garry-info-wrap .garry-info-installed {
                    margin-top: 20px;
                    padding: 18px 22px;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 8px;
                }
                .garry-info-wrap .garry-info-installed h2 {
                    margin-top: 0;
                    font-size: 16px;
                }
                .garry-info-wrap .garry-info-installed ul {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                .garry-info-wrap .garry-info-installed li {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    padding: 6px 0;
                }
                .garry-info-wrap .garry-info-installed .dashicons {
                    color: #e30613;
                }
                .garry-info-wrap .garry-info-installed a {
                    text-decoration: none;
                    font-weight: 500;
                }
                .garry-info-wrap .garry-info-installed a:hover {
                    text-decoration: underline;
                }
                .garry-info-wrap .garry-info-foot {
                    margin-top: 18px;
                    color: #6b7280;
                    font-size: 12px;
                    text-align: right;
                }
            </style>
            <?php
        }

        /**
         * Bootstrap registru – připojí akce na admin_menu a admin_head.
         * Idempotentní: lze volat z každého pluginu, hooky se připojí jen jednou.
         */
        public static function bootstrap() {
            static $bootstrapped = false;
            if ($bootstrapped) {
                return;
            }
            $bootstrapped = true;

            // Vysoká priorita zaručí, že registr je už naplněný.
            add_action('admin_menu', array(__CLASS__, 'build_admin_menu'), 99);

            // CSS do <head> v administraci.
            add_action('admin_head', array(__CLASS__, 'render_admin_styles'));
        }
    }
}

// Bootstrap je idempotentní, takže ho volá každý plugin – sjednotí se uvnitř.
Garry_Promotion_Registry::bootstrap();


/* ============================================================================
 * GARRY – Situace na trati (widget) — živá data z Open-Meteo dle GPS
 * ============================================================================ */

define( 'GARRY_SIT_OPT', 'garry_sit_settings' );

/* Dostupné údaje (klíč => [popisek v adminu, popisek na webu, výchozí zapnuto]) */
function garry_sit_fields() {
	return array(
		'status'    => array( 'Status (ruční text)',              'STATUS',       1 ),
		'cas'       => array( 'Místní čas',                       'MÍSTNÍ ČAS',   1 ),
		'teplota'   => array( 'Teplota',                          'TEPLOTA',      1 ),
		'pocitova'  => array( 'Pocitová teplota',                 'POCITOVÁ',     0 ),
		'vlhkost'   => array( 'Vlhkost',                          'VLHKOST',      0 ),
		'oblacnost' => array( 'Oblačnost',                        'OBLAČNOST',    0 ),
		'srazky'    => array( 'Srážky',                           'SRÁŽKY',       0 ),
		'vitr'      => array( 'Vítr',                             'VÍTR',         0 ),
		'popis'     => array( 'Slovní popis počasí',              'POČASÍ',       1 ),
		'povrch'    => array( 'Povrch trati (automatický odhad)', 'POVRCH TRATI', 1 ),
	);
}
/* Aktuální jazyk (Polylang, fallback locale) + slovníky frontendu */
function garry_sit_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_sit_l10n() {
	$t = array(
		'cs' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'MÍSTNÍ ČAS','teplota'=>'TEPLOTA','pocitova'=>'POCITOVÁ','vlhkost'=>'VLHKOST','oblacnost'=>'OBLAČNOST','srazky'=>'SRÁŽKY','vitr'=>'VÍTR','popis'=>'POČASÍ','povrch'=>'POVRCH TRATI' ),
			'status' => array(), 'aria'=>'Živá situace na trati', 'skryt'=>'Skrýt widget', 'zobrazit'=>'Zobrazit widget', 'locale'=>'cs-CZ',
			'povrch' => array( 'mokra'=>'Mokrá', 'vlhka'=>'Vlhká (odhad)', 'sucha'=>'Suchá', 'sucha_odhad'=>'Suchá (odhad)' ),
			'codes'  => array( 0=>'Jasno',1=>'Skoro jasno',2=>'Polojasno',3=>'Zataženo',45=>'Mlha',48=>'Námraza',51=>'Mrholení',53=>'Mrholení',55=>'Mrholení',56=>'Mrznoucí mrholení',57=>'Mrznoucí mrholení',61=>'Slabý déšť',63=>'Déšť',65=>'Silný déšť',66=>'Mrznoucí déšť',67=>'Mrznoucí déšť',71=>'Slabé sněžení',73=>'Sněžení',75=>'Silné sněžení',77=>'Sněhové krupky',80=>'Přeháňky',81=>'Přeháňky',82=>'Silné přeháňky',85=>'Sněhové přeháňky',86=>'Sněhové přeháňky',95=>'Bouřka',96=>'Bouřka s kroupami',99=>'Bouřka s kroupami' ),
		),
		'en' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'LOCAL TIME','teplota'=>'TEMP','pocitova'=>'FEELS','vlhkost'=>'HUMIDITY','oblacnost'=>'CLOUDS','srazky'=>'RAIN','vitr'=>'WIND','popis'=>'WEATHER','povrch'=>'SURFACE' ),
			'status' => array( 'OTEVŘENO'=>'OPEN', 'ZAVŘENO'=>'CLOSED' ), 'aria'=>'Live track conditions', 'skryt'=>'Hide widget', 'zobrazit'=>'Show widget', 'locale'=>'en-GB',
			'povrch' => array( 'mokra'=>'Wet', 'vlhka'=>'Damp (est.)', 'sucha'=>'Dry', 'sucha_odhad'=>'Dry (est.)' ),
			'codes'  => array( 0=>'Clear',1=>'Mostly clear',2=>'Partly cloudy',3=>'Overcast',45=>'Fog',48=>'Rime fog',51=>'Drizzle',53=>'Drizzle',55=>'Drizzle',56=>'Frz. drizzle',57=>'Frz. drizzle',61=>'Light rain',63=>'Rain',65=>'Heavy rain',66=>'Frz. rain',67=>'Frz. rain',71=>'Light snow',73=>'Snow',75=>'Heavy snow',77=>'Snow grains',80=>'Showers',81=>'Showers',82=>'Heavy showers',85=>'Snow showers',86=>'Snow showers',95=>'Thunderstorm',96=>'T-storm + hail',99=>'T-storm + hail' ),
		),
		'de' => array(
			'labels' => array( 'status'=>'STATUS','cas'=>'ORTSZEIT','teplota'=>'TEMPERATUR','pocitova'=>'GEFÜHLT','vlhkost'=>'FEUCHTE','oblacnost'=>'WOLKEN','srazky'=>'REGEN','vitr'=>'WIND','popis'=>'WETTER','povrch'=>'FAHRBAHN' ),
			'status' => array( 'OTEVŘENO'=>'GEÖFFNET', 'ZAVŘENO'=>'GESCHLOSSEN' ), 'aria'=>'Live-Streckenzustand', 'skryt'=>'Widget ausblenden', 'zobrazit'=>'Widget anzeigen', 'locale'=>'de-DE',
			'povrch' => array( 'mokra'=>'Nass', 'vlhka'=>'Feucht (ca.)', 'sucha'=>'Trocken', 'sucha_odhad'=>'Trocken (ca.)' ),
			'codes'  => array( 0=>'Klar',1=>'Meist klar',2=>'Teils bewölkt',3=>'Bedeckt',45=>'Nebel',48=>'Raureif',51=>'Niesel',53=>'Niesel',55=>'Niesel',56=>'Gefr. Niesel',57=>'Gefr. Niesel',61=>'Leichter Regen',63=>'Regen',65=>'Starker Regen',66=>'Gefr. Regen',67=>'Gefr. Regen',71=>'Leichter Schnee',73=>'Schneefall',75=>'Starker Schnee',77=>'Schneegriesel',80=>'Schauer',81=>'Schauer',82=>'Starke Schauer',85=>'Schneeschauer',86=>'Schneeschauer',95=>'Gewitter',96=>'Gewitter+Hagel',99=>'Gewitter+Hagel' ),
		),
	);
	$l = garry_sit_lang();
	return isset( $t[ $l ] ) ? $t[ $l ] : $t['cs'];
}
function garry_sit_defaults() {
	$d = array( 'enabled' => 1, 'pos' => 85, 'lat' => '49.2043', 'lon' => '16.4471', 'refresh' => 10, 'status_text' => 'OTEVŘENO' );
	foreach ( garry_sit_fields() as $k => $f ) $d[ 'f_' . $k ] = $f[2];
	return $d;
}
function garry_sit_get() {
	$o = get_option( GARRY_SIT_OPT, array() );
	return wp_parse_args( is_array( $o ) ? $o : array(), garry_sit_defaults() );
}

Garry_Promotion_Registry::register( array(
	'slug' => 'garry-situace-na-trati', 'title' => 'Situace na trati',
	'callback' => 'garry_sit_admin_page', 'plugin_file' => __FILE__,
	'doc' => '<p>Vyjížděcí karta „GRID · LIVE" (počasí, stav trati, otevírací doba) se vykresluje na frontendu automaticky — bez shortcodu; nahradila starší <code>[grid_telemetry]</code>. Nastavení pouze zde (administrátor).</p>', 'dashicon' => 'dashicons-info', 'position' => 10,
) );

add_action( 'admin_init', function () { register_setting( 'garry_sit_group', GARRY_SIT_OPT, 'garry_sit_sanitize' ); } );
function garry_sit_sanitize( $in ) {
	$d = garry_sit_defaults(); $o = array();
	$o['enabled'] = empty( $in['enabled'] ) ? 0 : 1;
	foreach ( garry_sit_fields() as $k => $f ) $o[ 'f_' . $k ] = empty( $in[ 'f_' . $k ] ) ? 0 : 1;
	$o['pos'] = max( 0, min( 100, (int) ( $in['pos'] ?? 85 ) ) );
	$o['refresh'] = max( 0, min( 120, (int) ( $in['refresh'] ?? 10 ) ) );
	$o['lat'] = sanitize_text_field( $in['lat'] ?? $d['lat'] );
	$o['lon'] = sanitize_text_field( $in['lon'] ?? $d['lon'] );
	$o['status_text'] = sanitize_text_field( $in['status_text'] ?? $d['status_text'] );
	return $o;
}

function garry_sit_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$s = garry_sit_get(); $F = garry_sit_fields();
	$name = function ( $k ) { return esc_attr( GARRY_SIT_OPT ) . '[' . esc_attr( $k ) . ']'; };
	?>
	<div class="wrap"><h1>Situace na trati — živý widget</h1>
	<div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start">
	  <form method="post" action="options.php" style="flex:1;min-width:340px;max-width:560px">
	    <?php settings_fields( 'garry_sit_group' ); ?>
	    <table class="form-table"><tbody>
	      <tr><th>Zobrazit widget</th><td><label><input type="checkbox" name="<?php echo $name('enabled'); ?>" value="1" <?php checked($s['enabled'],1); ?> data-sit="enabled"> Zapnout na webu</label></td></tr>
	      <tr><th>Zobrazené údaje</th><td>
	        <?php foreach ( $F as $k => $f ) printf(
	          '<label style="display:block;margin:5px 0"><input type="checkbox" name="%s" value="1" %s data-sit="f_%s"> %s</label>',
	          $name('f_'.$k), checked($s['f_'.$k],1,false), esc_attr($k), esc_html($f[0]) ); ?>
	      </td></tr>
	      <tr><th>Text „Status"</th><td><input type="text" name="<?php echo $name('status_text'); ?>" value="<?php echo esc_attr($s['status_text']); ?>" data-sit="status_text" class="regular-text"><p class="description">Ruční provozní stav (jediné needitované automaticky).</p></td></tr>
	      <tr><th>Výšková pozice</th><td><input type="range" min="0" max="100" name="<?php echo $name('pos'); ?>" value="<?php echo esc_attr($s['pos']); ?>" data-sit="pos" style="width:100%"><p class="description">0 % nahoře … 100 % dole. <b id="sit-pos-val"><?php echo (int)$s['pos']; ?> %</b></p></td></tr>
	      <tr><th colspan="2"><h2 style="margin:8px 0">Zdroj dat</h2></th></tr>
	      <tr><th>Poskytovatel</th><td><strong>Open-Meteo</strong> — zdarma, bez klíče, automaticky dle GPS. <a href="https://open-meteo.com/" target="_blank" rel="noopener">open-meteo.com</a></td></tr>
	      <tr><th>GPS okruhu</th><td>
	        <input type="text" name="<?php echo $name('lat'); ?>" value="<?php echo esc_attr($s['lat']); ?>" placeholder="49.2043" style="width:120px"> ,
	        <input type="text" name="<?php echo $name('lon'); ?>" value="<?php echo esc_attr($s['lon']); ?>" placeholder="16.4471" style="width:120px">
	        <p class="description">Výchozí = Masarykův okruh (49.2043 N, 16.4471 E).</p>
	      </td></tr>
	      <tr><th>Obnovovat po</th><td><input type="number" name="<?php echo $name('refresh'); ?>" value="<?php echo esc_attr($s['refresh']); ?>" min="0" max="120" style="width:80px"> min <span class="description">(0 = jen při načtení)</span></td></tr>
	    </tbody></table>
	    <?php submit_button( 'Uložit' ); ?>
	    <p class="description">Povrch trati se počítá automaticky: aktuální srážky → „Mokrá", déšť v posledních hodinách → „Vlhká (odhad)", jinak dle teploty a oblačnosti → „Suchá". Jde o odhad z počasí, ne o oficiální stav trati.</p>
	  </form>
	  <div style="flex:0 0 260px">
	    <p style="font-weight:600;margin:0 0 8px">Náhled</p>
	    <div style="position:relative;height:420px;border:1px solid #ccd0d4;border-radius:8px;background:#0d0f12;overflow:hidden">
	      <aside id="sit-preview" style="position:absolute;left:14px;top:85%;transform:translateY(-50%);width:214px;background:rgba(20,22,26,.92);border:1px solid rgba(255,255,255,.16);border-radius:3px;color:#B9B7B9;font-family:monospace;font-size:11.5px">
	        <div style="display:flex;justify-content:space-between;padding:8px 11px;border-bottom:1px solid rgba(255,255,255,.1)"><span style="color:#caa75f;letter-spacing:.16em">GRID · LIVE</span><span>×</span></div>
	        <div id="sit-prev-body" style="padding:8px 11px"></div>
	      </aside>
	    </div>
	    <p class="description" style="margin-top:8px">Náhled hodnot je ilustrativní; na webu se tahají živě.</p>
	  </div>
	</div></div>
	<script>
	(function(){
	  var F=<?php echo wp_json_encode( array_map( function($f){ return $f[1]; }, $F ) ); ?>;
	  var demo={status:'OTEVŘENO',cas:'14:22:07',teplota:'18 °C',pocitova:'17 °C',vlhkost:'62 %',oblacnost:'40 %',srazky:'0.0 mm',vitr:'12 km/h',popis:'Polojasno',povrch:'Suchá'};
	  var body=document.getElementById('sit-prev-body'), box=document.getElementById('sit-preview');
	  function q(s){return document.querySelector('[data-sit="'+s+'"]');}
	  function upd(){
	    if(!body)return; var h='';
	    Object.keys(F).forEach(function(k){
	      var cb=q('f_'+k); if(cb&&cb.checked){
	        var val=(k==='status')?(q('status_text').value||'OTEVŘENO'):demo[k];
	        h+='<div style="display:flex;justify-content:space-between;padding:3px 0"><span>'+F[k]+'</span><b style="color:'+(k==='teplota'?'#FF5A50':'#fff')+'">'+val+'</b></div>';
	      }
	    });
	    body.innerHTML=h;
	    var pos=q('pos').value; box.style.top=pos+'%'; var pv=document.getElementById('sit-pos-val'); if(pv)pv.textContent=pos+' %';
	    box.style.opacity=q('enabled').checked?'1':'.3';
	  }
	  document.querySelectorAll('[data-sit]').forEach(function(el){el.addEventListener('input',upd);el.addEventListener('change',upd);});
	  upd();
	})();
	</script>
	<?php
}

/* Frontend: plugin řídí widget (child [grid_telemetry] vypnut) */
add_action( 'init', function () { add_shortcode( 'grid_telemetry', '__return_empty_string' ); }, 30 );

add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$s = garry_sit_get(); if ( empty( $s['enabled'] ) ) return;
	$F = garry_sit_fields(); $pos = (int) $s['pos'];
	$L = garry_sit_l10n();
	ob_start();
	foreach ( $F as $k => $f ) {
		if ( empty( $s[ 'f_' . $k ] ) ) continue;
		if ( $k === 'status' ) { $st = $s['status_text']; $val = esc_html( isset( $L['status'][ $st ] ) ? $L['status'][ $st ] : $st ); }
		elseif ( $k === 'cas' )     $val = '--:--:--';
		elseif ( $k === 'teplota' ) $val = '–&nbsp;°C';
		else                        $val = '…';
		$cls = ( $k === 'teplota' ) ? 'v hot' : 'v';
		$dot = ( $k === 'status' ) ? '<span class="dot"></span>' : '';
		$lbl = isset( $L['labels'][ $k ] ) ? $L['labels'][ $k ] : $f[1];
		echo '<div class="hud-row"><span class="k">' . $dot . esc_html( $lbl ) . '</span><span class="' . $cls . '" data-field="' . esc_attr( $k ) . '">' . $val . '</span></div>';
	}
	$rows = ob_get_clean();
	?>
	<aside class="telemetry-hud" id="hud" aria-label="<?php echo esc_attr( $L['aria'] ); ?>" data-lat="<?php echo esc_attr($s['lat']); ?>" data-lon="<?php echo esc_attr($s['lon']); ?>" data-refresh="<?php echo (int)$s['refresh']; ?>" style="top:<?php echo $pos; ?>%;bottom:auto;transform:translateY(-50%)">
	  <div class="hud-head"><span class="hud-title">GRID · Live</span><button class="hud-x" id="hudX" aria-label="<?php echo esc_attr( $L['skryt'] ); ?>">&times;</button></div>
	  <div class="hud-body"><?php echo $rows; ?></div>
	</aside>
	<button class="hud-reopen" id="hudReopen" aria-label="<?php echo esc_attr( $L['zobrazit'] ); ?>">Live</button>
	<script>
	(function(){
	  var GSIT=<?php echo wp_json_encode( array( 'locale' => $L['locale'], 'codes' => $L['codes'], 'povrch' => $L['povrch'] ) ); ?>;
	  var el=document.getElementById('hud'); if(!el)return;
	  var reopen=document.getElementById('hudReopen');
	  /* V Divi Visual/Backend Builderu se do DOM nezasahuje — reparenting mimo modul
	     by mátl React strom builderu. Fixed positioning tam navíc neřeší, canvas je jen náhled. */
	  var isBuilder = /[?&]et_fb=1/.test(location.search)
	    || document.body.classList.contains('et-fb')
	    || document.body.classList.contains('et-bfb')
	    || document.documentElement.classList.contains('et-fb-preview');
	  /* Přesun mimo Divi kontejnery (transform ruší position:fixed) — řídí plugin sám,
	     nezávisle na pořadí načtení skriptů šablony. */
	  if(!isBuilder){
	    if(el.parentNode!==document.body) document.body.appendChild(el);
	    if(reopen && reopen.parentNode!==document.body) document.body.appendChild(reopen);
	  }
	  /* Zavření křížkem + znovuotevření boční kartou „Live" */
	  var xbtn=document.getElementById('hudX');
	  if(xbtn) xbtn.addEventListener('click', function(){ el.classList.add('hidden'); if(reopen) reopen.classList.add('show'); });
	  if(reopen) reopen.addEventListener('click', function(){ el.classList.remove('hidden'); reopen.classList.remove('show'); });
	  var lat=el.getAttribute('data-lat')||'49.2043', lon=el.getAttribute('data-lon')||'16.4471', refresh=parseInt(el.getAttribute('data-refresh')||'10',10);
	  function setF(f,v){ var s=el.querySelector('[data-field="'+f+'"]'); if(s)s.innerHTML=v; }
	  if(el.querySelector('[data-field="cas"]')){ var tick=function(){ setF('cas', new Date().toLocaleTimeString(GSIT.locale,{hour:'2-digit',minute:'2-digit',second:'2-digit'})); }; tick(); setInterval(tick,1000); }
	  var codes=GSIT.codes;
	  function loadW(){
	    var u='https://api.open-meteo.com/v1/forecast?latitude='+encodeURIComponent(lat)+'&longitude='+encodeURIComponent(lon)+'&current=temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,rain,showers,weather_code,cloud_cover,wind_speed_10m&hourly=precipitation&past_hours=3&forecast_hours=1&timezone=Europe%2FPrague';
	    fetch(u).then(function(r){return r.json();}).then(function(j){
	      var c=j.current||{};
	      if(typeof c.temperature_2m==='number') setF('teplota', Math.round(c.temperature_2m)+'&nbsp;°C');
	      if(typeof c.apparent_temperature==='number') setF('pocitova', Math.round(c.apparent_temperature)+'&nbsp;°C');
	      if(typeof c.relative_humidity_2m==='number') setF('vlhkost', Math.round(c.relative_humidity_2m)+'&nbsp;%');
	      if(typeof c.cloud_cover==='number') setF('oblacnost', Math.round(c.cloud_cover)+'&nbsp;%');
	      if(typeof c.precipitation==='number') setF('srazky', (c.precipitation||0).toFixed(1)+'&nbsp;mm');
	      if(typeof c.wind_speed_10m==='number') setF('vitr', Math.round(c.wind_speed_10m)+'&nbsp;km/h');
	      if(typeof c.weather_code!=='undefined') setF('popis', codes[c.weather_code]||'—');
	      var recent=0; if(j.hourly&&j.hourly.precipitation){ recent=j.hourly.precipitation.reduce(function(a,b){return a+(b||0);},0); }
	      var now=(c.precipitation||0)+(c.rain||0)+(c.showers||0), povrch;
	      if(now>0) povrch=GSIT.povrch.mokra;
	      else if(recent>0.1) povrch=GSIT.povrch.vlhka;
	      else if((c.temperature_2m||0)>=15 && (c.cloud_cover||100)<60) povrch=GSIT.povrch.sucha;
	      else povrch=GSIT.povrch.sucha_odhad;
	      setF('povrch', povrch);
	    }).catch(function(){});
	  }
	  loadW(); if(refresh>0) setInterval(loadW, refresh*60000);
	})();
	</script>
	<?php
}, 20 );
