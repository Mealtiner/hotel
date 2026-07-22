<?php
/**
 * Plugin Name:       GARRY – Boční posuvník (trať)
 * Plugin URI:        https://www.garry.cz
 * Description:       Boční „progress" navigace po sekcích stránky (styl trať) pro web GRID Hotel. Per-stránka: zapnutí, načtení sekcí, editace názvů, skrytí sekcí (přečíslování) a živý náhled.
 * Version:           1.3.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-bocni-posuvnik
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

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
 * GARRY – Boční posuvník (track progress) — per-stránka konfigurace
 * ============================================================================ */

define( 'GARRY_SCR_OPT', 'garry_scroller' );

/* Aktuální jazyk (Polylang, fallback locale) */
function garry_scr_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
/* mapa shortcode → [anchor, výchozí název (CS/EN/DE), typ] */
function garry_scr_map( $lang = null ) {
	$lang = $lang ?: garry_scr_lang();
	$names = array(
		'cs' => array( 'Okruh','Vstupy','Příběh','Pokoje','Zážitky','Gastro','Sezóna','Firmy','Reference','Rezervace' ),
		'en' => array( 'Circuit','Ways in','Story','Rooms','Experiences','Dining','Season','Business','Reviews','Booking' ),
		'de' => array( 'Ring','Einstiege','Story','Zimmer','Erlebnisse','Gastro','Saison','Firmen','Referenzen','Buchung' ),
	);
	$n = isset( $names[ $lang ] ) ? $names[ $lang ] : $names['cs'];
	return array(
		'grid_hero'      => array( 'start',     $n[0], 'start' ),
		'grid_vstupy'    => array( 'vstupy',    $n[1], 'mid' ),
		'grid_pribeh'    => array( 'pribeh',    $n[2], 'mid' ),
		'grid_rooms'     => array( 'pokoje',    $n[3], 'mid' ),
		'grid_zazitky'   => array( 'zazitky',   $n[4], 'mid' ),
		'grid_gastro'    => array( 'restaurace',$n[5], 'mid' ),
		'grid_season'    => array( 'sezona',    $n[6], 'mid' ),
		'grid_firemni'   => array( 'firemni',   $n[7], 'mid' ),
		'grid_reference' => array( 'duvera',    $n[8], 'mid' ),
		'grid_final'     => array( 'cil',       $n[9], 'cil' ),
	);
}
/* Detekce sekcí na stránce podle výskytu shortcodů v obsahu (seřazeno dle pozice) */
function garry_scr_detect( $content, $lang = null ) {
	$map = garry_scr_map( $lang );
	$found = array();
	foreach ( $map as $sc => $def ) {
		$needles = array(
			'[' . $sc,                            // shortcode v obsahu
			'id="' . $def[0] . '"',               // kotva v HTML
			'id=\\"' . $def[0] . '\\"',       // kotva v Divi 5 bloku (escapované uvozovky)
			'id=\\u0022' . $def[0] . '\\u0022', // kotva v Divi 5 bloku (unicode escapes)
		);
		foreach ( $needles as $n ) {
			$pos = strpos( $content, $n );
			if ( $pos !== false ) { $found[ $sc ] = min( $pos, $found[ $sc ] ?? PHP_INT_MAX ); }
		}
	}
	asort( $found );
	$out = array();
	foreach ( $found as $sc => $pos ) { $d = $map[ $sc ]; $out[] = array( 'sc' => $sc, 'id' => $d[0], 'label' => $d[1], 'type' => $d[2] ); }
	return $out;
}
/* Kompletní kanonická sada sekcí úvodní stránky (shoduje se s funkčním [grid_tracknav]
 * v child theme). Použije se jako předvyplnění na titulní stránce, když se v Divi
 * obsahu nepodaří shortcody detekovat (obsah je uložen v Divi struktuře). */
function garry_scr_front_sections( $lang = null ) {
	$out = array();
	foreach ( garry_scr_map( $lang ) as $sc => $d ) $out[] = array( 'sc' => $sc, 'id' => $d[0], 'label' => $d[1], 'type' => $d[2] );
	return $out;
}
/* Jazyk konkrétní stránky (Polylang), fallback aktuální jazyk */
function garry_scr_page_lang( $pid ) {
	if ( function_exists( 'pll_get_post_language' ) ) { $l = pll_get_post_language( $pid ); if ( $l ) return $l; }
	return garry_scr_lang();
}
/* Je stránka titulní stránkou NEBO jejím jazykovým překladem (Polylang)? */
function garry_scr_is_front( $pid ) {
	$front = (int) get_option( 'page_on_front' );
	if ( $front === (int) $pid ) return true;
	if ( $front && function_exists( 'pll_get_post_translations' ) ) {
		$tr = pll_get_post_translations( $front );
		if ( in_array( (int) $pid, array_map( 'intval', (array) $tr ), true ) ) return true;
	}
	return false;
}
/* Sekce pro danou stránku: detekce z obsahu; na titulní stránce (vč. mutací) fallback na kanonickou sadu. */
function garry_scr_sections_for( $pid ) {
	$page = get_post( $pid );
	$lang = garry_scr_page_lang( $pid );
	$sections = $page ? garry_scr_detect( $page->post_content, $lang ) : array();
	if ( empty( $sections ) && garry_scr_is_front( $pid ) ) {
		$sections = garry_scr_front_sections( $lang );
	}
	return $sections;
}
function garry_scr_all() { $o = get_option( GARRY_SCR_OPT, array() ); return is_array( $o ) ? $o : array(); }
function garry_scr_cfg( $pid ) { $a = garry_scr_all(); return isset( $a[ $pid ] ) ? $a[ $pid ] : array(); }

/* Body posuvníku pro stránku (aplikuje labely/skrytí + přečísluje T1.. ) */
function garry_scr_points( $pid ) {
	$page = get_post( $pid ); if ( ! $page ) return array();
	$sections = garry_scr_sections_for( $pid );
	$cfg = garry_scr_cfg( $pid );
	$labels = isset( $cfg['labels'] ) ? $cfg['labels'] : array();
	$hidden = isset( $cfg['hidden'] ) ? $cfg['hidden'] : array();
	$points = array(); $t = 0;
	foreach ( $sections as $s ) {
		if ( ! empty( $hidden[ $s['id'] ] ) ) continue;
		$label = isset( $labels[ $s['id'] ] ) && $labels[ $s['id'] ] !== '' ? $labels[ $s['id'] ] : $s['label'];
		$cil = array( 'cs' => 'CÍL', 'en' => 'FINISH', 'de' => 'ZIEL' );
		$lang = garry_scr_page_lang( $pid );
		if ( $s['type'] === 'start' ) $num = 'START';
		elseif ( $s['type'] === 'cil' ) $num = isset( $cil[ $lang ] ) ? $cil[ $lang ] : 'CÍL';
		else { $t++; $num = 'T' . $t; }
		$points[] = array( 'target' => $s['id'], 'num' => $num, 'name' => $label );
	}
	return $points;
}
/* Má se na této stránce posuvník zobrazit? (výchozí: titulní stránka vč. jazykových mutací) */
function garry_scr_enabled_for( $pid ) {
	$cfg = garry_scr_cfg( $pid );
	if ( isset( $cfg['enabled'] ) ) return ! empty( $cfg['enabled'] );
	return garry_scr_is_front( $pid ); // default jen homepage (CS/EN/DE)
}

/* Registrace do GARRY menu */
Garry_Promotion_Registry::register( array(
	'slug'        => 'garry-bocni-posuvnik',
	'title'       => 'Boční posuvník',
	'callback'    => 'garry_scr_admin_page',
	'plugin_file' => __FILE__,
	'doc' => '<p>Boční posuvník (pravá svislá navigace sekcí) se vykresluje na frontendu automaticky — bez shortcodu. Nastavení je pouze zde v GARRY nastavení (administrátor).</p>',
	'dashicon'    => 'dashicons-editor-ol',
	'position'    => 20,
) );

/* Uložení konfigurace (admin-post) */
add_action( 'admin_post_garry_scr_save', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'garry_scr_save' ) ) wp_die( 'Nedostatečná oprávnění.' );
	$pid = (int) $_POST['pid'];
	$all = garry_scr_all();
	$labels = array(); $hidden = array();
	$defaults = array();
	foreach ( garry_scr_sections_for( $pid ) as $sec ) $defaults[ $sec['id'] ] = $sec['label'];
	if ( ! empty( $_POST['label'] ) && is_array( $_POST['label'] ) ) foreach ( $_POST['label'] as $id => $v ) {
		$id = sanitize_key( $id ); $v = sanitize_text_field( $v );
		if ( $v === '' || ( isset( $defaults[ $id ] ) && $v === $defaults[ $id ] ) ) continue; // default → neukládat
		$labels[ $id ] = $v;
	}
	if ( ! empty( $_POST['hidden'] ) && is_array( $_POST['hidden'] ) ) foreach ( $_POST['hidden'] as $id => $v ) $hidden[ sanitize_key( $id ) ] = 1;
	$all[ $pid ] = array( 'enabled' => empty( $_POST['enabled'] ) ? 0 : 1, 'labels' => $labels, 'hidden' => $hidden );
	update_option( GARRY_SCR_OPT, $all );
	wp_safe_redirect( add_query_arg( array( 'page' => 'garry-bocni-posuvnik', 'gp' => $pid, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* Admin stránka */
function garry_scr_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	$gp = isset( $_GET['gp'] ) ? (int) $_GET['gp'] : ( $pages ? $pages[0]->ID : 0 );
	?>
	<div class="wrap"><h1>Boční posuvník (trať)</h1>
	<?php if ( isset( $_GET['saved'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Uloženo.</p></div>'; ?>
	<p>Pro každou publikovanou stránku můžeš zapnout posuvník, upravit názvy sekcí nebo některé skrýt (čísla se přečíslují).</p>
	<form method="get" style="margin:12px 0"><input type="hidden" name="page" value="garry-bocni-posuvnik">
	  <label>Stránka: <select name="gp" onchange="this.form.submit()">
	    <?php foreach ( $pages as $p ) printf( '<option value="%d" %s>%s</option>', $p->ID, selected( $gp, $p->ID, false ), esc_html( $p->post_title ) ); ?>
	  </select></label>
	</form>
	<?php if ( $gp ) :
		$cfg = garry_scr_cfg( $gp ); $page = get_post( $gp );
		$sections = garry_scr_sections_for( $gp );
		$is_front = ( (int) get_option( 'page_on_front' ) === (int) $gp );
		$labels = isset( $cfg['labels'] ) ? $cfg['labels'] : array();
		$hidden = isset( $cfg['hidden'] ) ? $cfg['hidden'] : array();
		$enabled = garry_scr_enabled_for( $gp );
	?>
	<div style="display:flex;gap:30px;flex-wrap:wrap;align-items:flex-start">
	  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="flex:1;min-width:340px;max-width:560px">
	    <input type="hidden" name="action" value="garry_scr_save"><input type="hidden" name="pid" value="<?php echo (int) $gp; ?>">
	    <?php wp_nonce_field( 'garry_scr_save' ); ?>
	    <p><label><input type="checkbox" name="enabled" value="1" <?php checked( $enabled, true ); ?> id="scr-enabled"> <strong>Zobrazit posuvník na této stránce</strong></label></p>
	    <?php if ( $is_front ) : ?>
	      <p class="description">Toto je <strong>úvodní stránka</strong> — posuvník je předvyplněn kompletní sadou sekcí (START → CÍL) shodnou s funkčním posuvníkem na webu. Názvy uprav nebo sekce skryj dle potřeby.</p>
	    <?php endif; ?>
	    <?php if ( empty( $sections ) ) : ?>
	      <p class="description">Na této stránce nebyly nalezeny žádné sekce GRID (shortcody). Posuvník potřebuje sekce jako <code>[grid_hero]</code>, <code>[grid_rooms]</code>…</p>
	    <?php else : ?>
	      <table class="widefat" style="max-width:560px"><thead><tr><th>Sekce</th><th>Název v posuvníku</th><th>Skrýt</th></tr></thead><tbody>
	        <?php foreach ( $sections as $s ) :
	          $lbl = isset( $labels[ $s['id'] ] ) ? $labels[ $s['id'] ] : $s['label']; ?>
	          <tr>
	            <td><code>#<?php echo esc_html( $s['id'] ); ?></code></td>
	            <td><input type="text" name="label[<?php echo esc_attr( $s['id'] ); ?>]" value="<?php echo esc_attr( $lbl ); ?>" class="regular-text scr-label" data-id="<?php echo esc_attr( $s['id'] ); ?>" data-type="<?php echo esc_attr( $s['type'] ); ?>"></td>
	            <td style="text-align:center"><input type="checkbox" name="hidden[<?php echo esc_attr( $s['id'] ); ?>]" value="1" <?php checked( ! empty( $hidden[ $s['id'] ] ), true ); ?> class="scr-hide" data-id="<?php echo esc_attr( $s['id'] ); ?>"></td>
	          </tr>
	        <?php endforeach; ?>
	      </tbody></table>
	    <?php endif; ?>
	    <?php submit_button( 'Uložit posuvník' ); ?>
	  </form>
	  <div style="flex:0 0 240px">
	    <p style="font-weight:600;margin:0 0 8px">Živý náhled</p>
	    <div style="background:#0d0f12;border:1px solid #ccd0d4;border-radius:8px;padding:26px 18px;min-height:360px">
	      <div style="font-family:monospace;font-size:10px;letter-spacing:.18em;color:#8b8884;text-transform:uppercase;margin-bottom:14px">Masaryk Circuit</div>
	      <div id="scr-preview" style="position:relative;padding-left:22px"></div>
	    </div>
	  </div>
	</div>
	<script>
	(function(){
	  var prev=document.getElementById('scr-preview'); if(!prev)return;
	  function build(){
	    var rows=[].slice.call(document.querySelectorAll('.scr-label'));
	    var t=0, html='<div style="position:absolute;left:5px;top:4px;bottom:4px;width:2px;background:rgba(150,150,150,.4)"></div>';
	    rows.forEach(function(inp){
	      var id=inp.getAttribute('data-id'), type=inp.getAttribute('data-type');
	      var hide=document.querySelector('.scr-hide[data-id="'+id+'"]');
	      if(hide&&hide.checked) return;
	      var num = type==='start'?'START':(type==='cil'?'CÍL':'T'+(++t));
	      html+='<div style="display:flex;align-items:center;gap:10px;margin:0 0 18px;position:relative">'+
	        '<span style="width:11px;height:11px;border-radius:50%;border:2px solid #7A797B;background:#0d0f12;position:relative;left:-1px;flex:none"></span>'+
	        '<span style="line-height:1.1"><span style="display:block;font-family:monospace;font-size:9px;letter-spacing:.15em;color:#caa75f">'+num+'</span>'+
	        '<span style="display:block;font-family:sans-serif;font-size:12px;text-transform:uppercase;color:#e6e4e2">'+(inp.value||'—')+'</span></span></div>';
	    });
	    prev.innerHTML=html;
	    prev.style.opacity=(document.getElementById('scr-enabled')&&document.getElementById('scr-enabled').checked)?'1':'.3';
	  }
	  document.querySelectorAll('.scr-label,.scr-hide,#scr-enabled').forEach(function(el){el.addEventListener('input',build);el.addEventListener('change',build);});
	  build();
	})();
	</script>
	<?php endif; ?>
	</div>
	<?php
}

/* Frontend: plugin řídí posuvník — child [grid_tracknav] vypneme a vykreslíme vlastní */
add_action( 'init', function () { add_shortcode( 'grid_tracknav', '__return_empty_string' ); }, 30 );

add_action( 'wp_footer', function () {
	if ( is_admin() || ! is_page() ) return;
	$pid = get_queried_object_id();
	if ( ! garry_scr_enabled_for( $pid ) ) return;
	$points = garry_scr_points( $pid );
	if ( empty( $points ) ) return;
	?>
	<?php $aria = array( 'cs' => 'Postup po stránce', 'en' => 'Page progress', 'de' => 'Seitenfortschritt' ); $al = garry_scr_lang(); ?>
	<aside class="track-progress" aria-label="<?php echo esc_attr( isset( $aria[ $al ] ) ? $aria[ $al ] : $aria['cs'] ); ?>">
	  <div class="tp-inner">
	    <span class="tp-label">Masaryk Circuit</span>
	    <div class="tp-rail"></div><div class="tp-fill" id="tpFill"></div>
	    <div class="tp-points" id="tpPoints">
	      <?php foreach ( $points as $p ) : ?>
	      <button class="tp-point" data-target="<?php echo esc_attr( $p['target'] ); ?>"><span class="tp-dot"></span><span class="tp-text"><span class="tp-num"><?php echo esc_html( $p['num'] ); ?></span><span class="tp-name"><?php echo esc_html( $p['name'] ); ?></span></span></button>
	      <?php endforeach; ?>
	    </div>
	  </div>
	</aside>
	<?php
}, 15 );
