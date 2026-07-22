<?php
/**
 * Plugin Name:       GARRY – Kategorie pokojů
 * Plugin URI:        https://www.garry.cz
 * Description:       Správa kategorií pokojů: karty na titulní stránce, štítky a texty v CZ/EN/DE, srovnávací tabulka pokojů (přesouvání a přidávání řádků). Frontend: [grid_rooms_cards], [grid_rooms_table] a srovnávací tabulka na detailu kategorie se zvýrazněním sloupce.
 * Version:           1.3.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-kategorie-pokoju
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
 * GARRY – Kategorie pokojů
 * ============================================================================ */

define( 'GARRY_POK_OPT', 'garry_pokoje' );

function garry_pok_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_pok_lang_idx() { $l = garry_pok_lang(); return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 ); }
function garry_pok_suffix() { return array( 'cz', 'en', 'de' )[ garry_pok_lang_idx() ]; }

/* Výchozí data (bundlovaný JSON — předvyplněno aktuálním obsahem webu) */
function garry_pok_defaults() {
	static $d = null;
	if ( $d === null ) {
		$raw = @file_get_contents( plugin_dir_path( __FILE__ ) . 'data-default.json' );
		$d = $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $d ) ) $d = array();
		$d = wp_parse_args( $d, array( 'labels' => array(), 'rooms' => array(), 'compare' => array() ) );
	}
	return $d;
}
function garry_pok_normalize_room( $r ) {
	if ( isset( $r['kod'] ) && ! isset( $r['kod_cz'] ) ) $r['kod_cz'] = $r['kod'];
	if ( isset( $r['kratky'] ) && ! isset( $r['kratky_cz'] ) ) $r['kratky_cz'] = $r['kratky'];
	$keys = array( 'key' => '', 'home' => 1, 'pocet' => '', 'kapacita' => '', 'velikost' => '', 'img' => '', 'stitky' => array() );
	foreach ( array( 'kod', 'nazev', 'kratky', 'postel', 'koupelna', 'zarizeni', 'popis' ) as $f ) {
		foreach ( array( 'cz', 'en', 'de' ) as $l ) $keys[ $f . '_' . $l ] = '';
	}
	return wp_parse_args( $r, $keys );
}
function garry_pok_get() {
	$o = get_option( GARRY_POK_OPT, null );
	if ( ! is_array( $o ) ) $o = array();
	$o = wp_parse_args( $o, array( 'labels' => array(), 'rooms' => array(), 'compare' => array() ) );
	foreach ( array( 'labels', 'rooms', 'compare' ) as $k ) if ( empty( $o[ $k ] ) ) $o[ $k ] = garry_pok_defaults()[ $k ];
	$o['rooms'] = array_map( 'garry_pok_normalize_room', $o['rooms'] );
	return $o;
}
function garry_pok_labels_map() {
	$out = array();
	foreach ( garry_pok_get()['labels'] as $l ) if ( ! empty( $l['key'] ) ) $out[ $l['key'] ] = $l;
	return $out;
}
function garry_pok_rooms() {
	$out = array();
	foreach ( garry_pok_get()['rooms'] as $r ) if ( ! empty( $r['key'] ) ) $out[ $r['key'] ] = $r;
	return $out;
}
/* Hodnota pole v aktuálním jazyce s fallbackem na CZ */
function garry_pok_f( $arr, $base ) {
	$suf = garry_pok_suffix();
	$v = $arr[ $base . '_' . $suf ] ?? '';
	return $v !== '' ? $v : ( $arr[ $base . '_cz' ] ?? '' );
}
/* Klíč pokoje z termu (standard-en → standard) */
function garry_pok_key_from_term( $term ) {
	return preg_replace( '/-(en|de)$/', '', (string) $term->slug );
}
/* URL detailu kategorie v aktuálním jazyce */
function garry_pok_term_url( $key ) {
	/* lang=>'' vypne jazykový filtr Polylangu (základní term je český) */
	$found = get_terms( array( 'taxonomy' => 'grid_room_cat', 'slug' => $key, 'hide_empty' => false, 'lang' => '' ) );
	$term = ( ! is_wp_error( $found ) && $found ) ? $found[0] : null;
	if ( $term ) {
		$tid = $term->term_id;
		$lang = garry_pok_lang();
		if ( $lang !== 'cs' && function_exists( 'pll_get_term' ) ) {
			$t2 = pll_get_term( $tid, $lang );
			if ( $t2 ) $tid = $t2;
		}
		$link = get_term_link( (int) $tid, 'grid_room_cat' );
		if ( ! is_wp_error( $link ) ) return $link;
	}
	return home_url( '/kategorie-pokoje/' . $key . '/' );
}

/* Taxonomie kategorií je překládaná Polylangem */
add_filter( 'pll_get_taxonomies', function ( $tax, $is_settings ) {
	$tax['grid_room_cat'] = 'grid_room_cat';
	return $tax;
}, 10, 2 );

Garry_Promotion_Registry::register( array(
	'slug' => 'garry-kategorie-pokoju', 'title' => 'Kategorie pokojů',
	'callback' => 'garry_pok_admin_page', 'plugin_file' => __FILE__,
	'doc' => '<p><code>[grid_rooms_cards]</code> — karty kategorií pokojů (titulní stránka, Ubytování). <code>[grid_rooms_table]</code> — přehledová tabulka pokojů s odkazy na detail. Srovnávací tabulka na detailu kategorie se vykresluje šablonou <code>taxonomy-grid_room_cat.php</code> (funkce <code>garry_pokoje_compare_html()</code>), názvy typů jsou odkazy na detail.</p><p>Texty, štítky a srovnávací tabulku edituje personál v <strong>GRID Nastavení → Kategorie pokojů</strong>; fotky a galerie v <strong>GRID Nastavení → Pokoje: fotky a galerie</strong>.</p>',
	'grid_slug' => 'garry-pokoje-grid',
	'dashicon' => 'dashicons-admin-multisite', 'position' => 22,
) );
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return;
	if ( class_exists( 'Garry_Promotion_Registry' ) && ! Garry_Promotion_Registry::grid_visible( 'garry-kategorie-pokoju' ) ) return;
	add_submenu_page( 'grid-options', 'Kategorie pokojů', 'Kategorie pokojů',
		Garry_Promotion_Registry::STAFF_CAPABILITY, 'garry-pokoje-grid', 'garry_pok_admin_page' );
}, 100 );

add_action( 'admin_init', function () { register_setting( 'garry_pok_group', GARRY_POK_OPT, 'garry_pok_sanitize' ); } );
/* personál (Editor) smí ukládat přes options.php */
add_filter( 'option_page_capability_garry_pok_group', function () { return Garry_Promotion_Registry::STAFF_CAPABILITY; } );
function garry_pok_sanitize( $in ) {
	$out = array( 'labels' => array(), 'rooms' => array(), 'compare' => array() );
	if ( ! is_array( $in ) ) return $out;

	foreach ( (array) ( $in['labels'] ?? array() ) as $l ) {
		if ( ! is_array( $l ) ) continue;
		$key = sanitize_key( $l['key'] ?? '' );
		$cz = sanitize_text_field( $l['cz'] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$out['labels'][] = array( 'key' => $key, 'cz' => $cz,
			'en' => sanitize_text_field( $l['en'] ?? '' ), 'de' => sanitize_text_field( $l['de'] ?? '' ) );
	}

	foreach ( (array) ( $in['rooms'] ?? array() ) as $r ) {
		if ( ! is_array( $r ) ) continue;
		$key = sanitize_key( $r['key'] ?? '' );
		if ( $key === '' ) continue;
		$room = array( 'key' => $key, 'home' => empty( $r['home'] ) ? 0 : 1,
			'pocet' => sanitize_text_field( $r['pocet'] ?? '' ),
			'kapacita' => sanitize_text_field( $r['kapacita'] ?? '' ),
			'velikost' => sanitize_text_field( $r['velikost'] ?? '' ),
			'img' => esc_url_raw( $r['img'] ?? '' ),
			'stitky' => array_values( array_filter( array_map( 'sanitize_key', (array) ( $r['stitky'] ?? array() ) ) ) ),
		);
		foreach ( array( 'kod', 'nazev', 'kratky', 'postel' ) as $f ) {
			foreach ( array( 'cz', 'en', 'de' ) as $l ) $room[ $f . '_' . $l ] = sanitize_text_field( $r[ $f . '_' . $l ] ?? '' );
		}
		/* kod bez jazykových variant v adminu — kod_cz jako hlavní, en/de dopočítat ze vstupu pokud jsou */
		foreach ( array( 'koupelna', 'zarizeni' ) as $f ) {
			foreach ( array( 'cz', 'en', 'de' ) as $l ) $room[ $f . '_' . $l ] = sanitize_text_field( $r[ $f . '_' . $l ] ?? '' );
		}
		foreach ( array( 'cz', 'en', 'de' ) as $l ) $room[ 'popis_' . $l ] = wp_kses_post( $r[ 'popis_' . $l ] ?? '' );
		$out['rooms'][] = $room;
	}

	foreach ( (array) ( $in['compare'] ?? array() ) as $row ) {
		if ( ! is_array( $row ) ) continue;
		$r = array( 'cz' => sanitize_text_field( $row['cz'] ?? '' ),
			'en' => sanitize_text_field( $row['en'] ?? '' ), 'de' => sanitize_text_field( $row['de'] ?? '' ), 'vals' => array() );
		if ( $r['cz'] === '' ) continue;
		foreach ( (array) ( $row['vals'] ?? array() ) as $rk => $v ) {
			$rk = sanitize_key( $rk );
			$r['vals'][ $rk ] = array(
				'cz' => sanitize_text_field( $v['cz'] ?? '' ),
				'en' => sanitize_text_field( $v['en'] ?? '' ),
				'de' => sanitize_text_field( $v['de'] ?? '' ),
			);
		}
		$out['compare'][] = $r;
	}
	if ( ! $out['rooms'] ) $out['rooms'] = garry_pok_defaults()['rooms'];
	return $out;
}

/* ============================================================================
 * Administrace — karty: Pokoje | Štítky | Srovnávací tabulka
 * ============================================================================ */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( strpos( $hook, 'garry-kategorie-pokoju' ) !== false || strpos( $hook, 'garry-pokoje-grid' ) !== false ) wp_enqueue_media();
} );
function garry_pok_admin_page() {
	if ( ! current_user_can( Garry_Promotion_Registry::STAFF_CAPABILITY ) ) return;
	$s = garry_pok_get(); $O = GARRY_POK_OPT;
	$labels = $s['labels']; $rooms = $s['rooms']; $compare = $s['compare'];
	?>
	<div class="wrap"><h1>Kategorie pokojů</h1>
	<p>Karty pokojů na titulní stránce a stránce Ubytování, štítky a srovnávací tabulka na detailu kategorie
	(<code>/kategorie-pokoje/…</code>). Vše v CZ/EN/DE — prázdný překlad = použije se čeština.
	Fotky (náhled + galerie detailu) se spravují dál v <strong>GRID Nastavení → Kategorie pokojů</strong>.</p>
	<h2 class="nav-tab-wrapper" id="pok-tabs">
	  <a href="#" class="nav-tab nav-tab-active" data-tab="pokoje">Pokoje</a>
	  <a href="#" class="nav-tab" data-tab="stitky">Štítky</a>
	  <a href="#" class="nav-tab" data-tab="tabulka">Srovnávací tabulka pokojů</a>
	</h2>
	<form method="post" action="options.php" id="pok-form">
	<?php settings_fields( 'garry_pok_group' ); ?>

	<!-- ============ POKOJE ============ -->
	<div class="pok-tab" data-tab="pokoje">
	  <div id="pok-rooms" style="margin-top:14px;max-width:1200px">
	  <?php foreach ( $rooms as $r ) : ?>
	    <details class="pok-room" style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:10px 16px;margin-bottom:14px">
	      <summary style="cursor:pointer;display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:4px 0">
	        <strong class="pok-room-title"><?php echo esc_html( $r['nazev_cz'] ?: 'Nová kategorie' ); ?></strong>
	        <span class="description"><?php echo esc_html( $r['key'] ); ?><?php if ( ! empty( $r['home'] ) ) echo ' · na titulní stránce'; ?></span>
	      </summary>
	      <div class="pok-room-body" style="padding-top:10px;border-top:1px solid #eee;margin-top:8px">
	      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
	        <label>Klíč (slug) <input type="text" class="pok-key" name="__key" value="<?php echo esc_attr( $r['key'] ); ?>" style="width:170px"></label>
	        <label>Kód <input type="text" name="__kod_cz" value="<?php echo esc_attr( $r['kod_cz'] ); ?>" style="width:210px"></label>
	        <label><input type="checkbox" name="__home" value="1" <?php checked( ! empty( $r['home'] ) ); ?>> na titulní stránce</label>
	        <span style="margin-left:auto;display:flex;gap:4px">
	          <button type="button" class="button pok-up" title="Posunout výš">↑</button>
	          <button type="button" class="button pok-down" title="Posunout níž">↓</button>
	          <button type="button" class="button-link pok-del" style="color:#b32d2e">Smazat ×</button>
	        </span>
	      </div>
	      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:8px">
	        <label>Název CZ<input type="text" style="width:100%" name="__nazev_cz" value="<?php echo esc_attr( $r['nazev_cz'] ); ?>"></label>
	        <label>Název EN<input type="text" style="width:100%" name="__nazev_en" value="<?php echo esc_attr( $r['nazev_en'] ); ?>"></label>
	        <label>Název DE<input type="text" style="width:100%" name="__nazev_de" value="<?php echo esc_attr( $r['nazev_de'] ); ?>"></label>
	      </div>
	      <div style="display:grid;grid-template-columns:1fr;gap:6px;margin-bottom:8px">
	        <label>Krátký popis (karta) CZ<input type="text" style="width:100%" name="__kratky_cz" value="<?php echo esc_attr( $r['kratky_cz'] ); ?>"></label>
	        <label>Krátký popis EN<input type="text" style="width:100%" name="__kratky_en" value="<?php echo esc_attr( $r['kratky_en'] ); ?>"></label>
	        <label>Krátký popis DE<input type="text" style="width:100%" name="__kratky_de" value="<?php echo esc_attr( $r['kratky_de'] ); ?>"></label>
	      </div>
	      <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:8px">
	        <label>Počet pokojů <input type="text" name="__pocet" value="<?php echo esc_attr( $r['pocet'] ); ?>" style="width:70px"></label>
	        <label>Kapacita (os.) <input type="text" name="__kapacita" value="<?php echo esc_attr( $r['kapacita'] ); ?>" style="width:70px"></label>
	        <label>Velikost (m²) <input type="text" name="__velikost" value="<?php echo esc_attr( $r['velikost'] ); ?>" style="width:80px"></label>
	        <label style="flex:1;min-width:280px">Obrázek karty <span style="display:flex;gap:6px"><input type="text" name="__img" value="<?php echo esc_attr( $r['img'] ); ?>" style="flex:1"><button type="button" class="button pok-media">Vybrat z médií</button></span></label>
	      </div>
	      <div class="pok-stitky" style="margin-bottom:8px"><strong style="display:block;margin-bottom:4px">Štítky na kartě</strong>
	        <?php foreach ( $labels as $l ) : ?>
	          <label style="display:inline-block;margin:2px 10px 2px 0"><input type="checkbox" name="__stitky[]" value="<?php echo esc_attr( $l['key'] ); ?>" <?php checked( in_array( $l['key'], (array) $r['stitky'], true ) ); ?>> <?php echo esc_html( $l['cz'] ); ?></label>
	        <?php endforeach; ?>
	      </div>
	      <details><summary style="cursor:pointer;font-weight:600">Detailní stránka (postel, koupelna, zařízení, dlouhý popis)</summary>
	        <p class="description" style="margin:8px 0 4px">Tyto údaje se zobrazují na <strong>detailu kategorie pokoje</strong>
	        (např. <code>/kategorie-pokoje/superior/</code>): „Postel" je součást řádku faktů pod nadpisem,
	        seznamy „V soukromé koupelně" a „Vybavení pokoje" se vypisují s odrážkami ✓ v pravém sloupci
	        a „Dlouhý popis" je hlavní text stránky (jednoduchý editor — tučně, kurzíva, podtržení, odstavce).
	        Prázdný překlad = použije se čeština.</p>
	        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:10px 0 8px">
	          <label>Postel CZ<input type="text" style="width:100%" name="__postel_cz" value="<?php echo esc_attr( $r['postel_cz'] ); ?>"></label>
	          <label>Postel EN<input type="text" style="width:100%" name="__postel_en" value="<?php echo esc_attr( $r['postel_en'] ); ?>"></label>
	          <label>Postel DE<input type="text" style="width:100%" name="__postel_de" value="<?php echo esc_attr( $r['postel_de'] ); ?>"></label>
	        </div>
	        <?php foreach ( array( 'koupelna' => 'V soukromé koupelně', 'zarizeni' => 'Vybavení pokoje' ) as $f => $lbl ) : ?>
	        <p style="margin:6px 0 2px"><strong><?php echo esc_html( $lbl ); ?></strong> — pište položku a stiskněte <strong>čárku</strong> nebo <strong>Enter</strong>, vytvoří se štítek (× jej smaže)</p>
	        <div style="display:grid;grid-template-columns:1fr;gap:4px;margin-bottom:6px">
	          <?php foreach ( array( 'cz' => 'CZ', 'en' => 'EN', 'de' => 'DE' ) as $l => $L ) : ?>
	          <label><?php echo $L; ?><textarea class="pok-tags" style="width:100%" rows="2" name="__<?php echo $f; ?>_<?php echo $l; ?>"><?php echo esc_textarea( $r[ $f . '_' . $l ] ); ?></textarea></label>
	          <?php endforeach; ?>
	        </div>
	        <?php endforeach; ?>
	        <p style="margin:6px 0 2px"><strong>Dlouhý popis</strong></p>
	        <?php foreach ( array( 'cz' => 'CZ', 'en' => 'EN', 'de' => 'DE' ) as $l => $L ) : ?>
	        <label style="display:block;margin-bottom:4px"><?php echo $L; ?><textarea class="pok-rte-src" style="width:100%" rows="4" name="__popis_<?php echo $l; ?>"><?php echo esc_textarea( $r[ 'popis_' . $l ] ); ?></textarea></label>
	        <?php endforeach; ?>
	      </details>
	      </div>
	    </details>
	  <?php endforeach; ?>
	  </div>
	  <p style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="button" id="pok-room-add">+ Přidat kategorii</button>
	  <button type="button" class="button" id="pok-expand">Rozbalit vše</button>
	  <button type="button" class="button" id="pok-collapse">Sbalit vše</button></p>
	</div>

	<!-- ============ ŠTÍTKY ============ -->
	<div class="pok-tab" data-tab="stitky" style="display:none">
	  <p style="margin-top:14px">Štítky (chips) zobrazované na kartách pokojů. Přiřazují se na kartě „Pokoje".</p>
	  <table class="widefat striped" id="pok-labels" style="max-width:900px">
	    <thead><tr><th style="width:160px">Klíč</th><th>CZ</th><th>EN</th><th>DE</th><th style="width:32px"></th></tr></thead><tbody>
	    <?php foreach ( $labels as $l ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="__lkey" value="<?php echo esc_attr( $l['key'] ); ?>"></td>
	        <td><input type="text" style="width:100%" name="__lcz" value="<?php echo esc_attr( $l['cz'] ); ?>"></td>
	        <td><input type="text" style="width:100%" name="__len" value="<?php echo esc_attr( $l['en'] ); ?>"></td>
	        <td><input type="text" style="width:100%" name="__lde" value="<?php echo esc_attr( $l['de'] ); ?>"></td>
	        <td><button type="button" class="button-link pok-lbl-del" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><button type="button" class="button" id="pok-lbl-add">+ Přidat štítek</button></p>
	</div>

	<!-- ============ SROVNÁVACÍ TABULKA ============ -->
	<div class="pok-tab" data-tab="tabulka" style="display:none">
	  <p style="margin-top:14px">Řádky srovnávací tabulky na detailu kategorie. Hodnoty: <code>✓</code>, <code>–</code>, nebo text.
	  Šipkami lze řádky přesouvat. Prázdné EN/DE = použije se CZ.</p>
	  <table class="widefat striped" id="pok-compare" style="max-width:1400px">
	    <thead><tr><th style="width:70px"></th><th style="width:220px">Vlastnost (CZ / EN / DE)</th>
	      <?php foreach ( $rooms as $r ) : ?><th><?php echo esc_html( $r['nazev_cz'] ); ?></th><?php endforeach; ?>
	      <th style="width:32px"></th></tr></thead><tbody>
	    <?php foreach ( $compare as $row ) : ?>
	      <tr>
	        <td style="white-space:nowrap"><button type="button" class="button pok-row-up">↑</button><button type="button" class="button pok-row-down">↓</button></td>
	        <td>
	          <input type="text" style="width:100%" name="__ccz" value="<?php echo esc_attr( $row['cz'] ); ?>" placeholder="CZ">
	          <input type="text" style="width:100%" name="__cen" value="<?php echo esc_attr( $row['en'] ); ?>" placeholder="EN">
	          <input type="text" style="width:100%" name="__cde" value="<?php echo esc_attr( $row['de'] ); ?>" placeholder="DE">
	        </td>
	        <?php foreach ( $rooms as $r ) : $v = $row['vals'][ $r['key'] ] ?? array( 'cz' => '', 'en' => '', 'de' => '' ); ?>
	        <td data-room="<?php echo esc_attr( $r['key'] ); ?>">
	          <input type="text" style="width:100%" name="__vcz" value="<?php echo esc_attr( $v['cz'] ); ?>" placeholder="CZ">
	          <input type="text" style="width:100%" name="__ven" value="<?php echo esc_attr( $v['en'] ); ?>" placeholder="EN">
	          <input type="text" style="width:100%" name="__vde" value="<?php echo esc_attr( $v['de'] ); ?>" placeholder="DE">
	        </td>
	        <?php endforeach; ?>
	        <td><button type="button" class="button-link pok-row-del" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><button type="button" class="button" id="pok-row-add">+ Přidat řádek</button></p>
	</div>

	<?php submit_button( 'Uložit' ); ?>
	</form></div>
	<style>
	.pok-chipbox{display:flex;flex-wrap:wrap;align-items:center;gap:6px;border:1px solid #8c8f94;border-radius:4px;background:#fff;padding:5px 6px;min-height:34px;cursor:text}
	.pok-chip{display:inline-flex;align-items:center;gap:5px;background:#eef3fa;border:1px solid #b5c7e0;border-radius:14px;padding:2px 9px;font-size:12.5px;line-height:1.7;white-space:nowrap}
	.pok-chip-x{border:0;background:none;color:#b32d2e;cursor:pointer;font-size:15px;line-height:1;padding:0}
	.pok-chip-in{border:0!important;box-shadow:none!important;outline:none!important;flex:1;min-width:180px;margin:0!important;padding:2px!important}
	.pok-rte-bar{display:flex;gap:4px;margin:3px 0}
	.pok-rte-bar button{border:1px solid #8c8f94;background:#f6f7f7;border-radius:3px;min-width:30px;height:27px;cursor:pointer;font-weight:700}
	.pok-rte-bar button:hover{background:#eee}
	.pok-rte{border:1px solid #8c8f94;border-radius:4px;background:#fff;padding:8px 12px;min-height:100px;max-height:320px;overflow:auto}
	.pok-rte:focus{outline:2px solid #2271b1;outline-offset:-1px}
	.pok-rte p{margin:0 0 8px}
	</style>
	<script>
	(function(){
	  var O = <?php echo wp_json_encode( GARRY_POK_OPT ); ?>;
	  try{ document.execCommand('defaultParagraphSeparator', false, 'p'); }catch(e){}
	  /* ---- štítkový (chip) vstup: čárka/Enter vytvoří štítek, data v textarei jako a | b | c ---- */
	  function initChips(scope){
	    (scope||document).querySelectorAll('textarea.pok-tags').forEach(function(ta){
	      if(ta.dataset.chipsInit) return; ta.dataset.chipsInit='1';
	      ta.style.display='none';
	      var box=document.createElement('div'); box.className='pok-chipbox';
	      var inp=document.createElement('input'); inp.type='text'; inp.className='pok-chip-in';
	      inp.placeholder='napište položku a stiskněte čárku nebo Enter…';
	      box.appendChild(inp);
	      ta.parentNode.insertBefore(box, ta.nextSibling);
	      function sync(){
	        ta.value=[].map.call(box.querySelectorAll('.pok-chip'),function(c){return c.firstChild.textContent;}).join(' | ');
	      }
	      function addChip(txt){ txt=(txt||'').trim(); if(!txt) return;
	        var c=document.createElement('span'); c.className='pok-chip'; c.appendChild(document.createTextNode(txt));
	        var x=document.createElement('button'); x.type='button'; x.className='pok-chip-x'; x.title='Smazat štítek'; x.textContent='×';
	        c.appendChild(x); box.insertBefore(c, inp); sync();
	      }
	      (ta.value||'').split('|').forEach(addChip);
	      inp.addEventListener('keydown', function(e){
	        if(e.key===','||e.key==='Enter'){ e.preventDefault(); addChip(inp.value); inp.value=''; }
	        else if(e.key==='Backspace'&&!inp.value){ var last=inp.previousElementSibling; if(last){ last.remove(); sync(); } }
	      });
	      inp.addEventListener('input', function(){
	        if(inp.value.indexOf(',')>-1){ var parts=inp.value.split(','); parts.slice(0,-1).forEach(addChip); inp.value=parts[parts.length-1]; }
	      });
	      inp.addEventListener('blur', function(){ if(inp.value.trim()){ addChip(inp.value); inp.value=''; } });
	      box.addEventListener('click', function(e){
	        if(e.target.classList.contains('pok-chip-x')){ e.target.parentNode.remove(); sync(); }
	        else if(e.target===box) inp.focus();
	      });
	    });
	  }
	  /* ---- mini WYSIWYG pro dlouhý popis (B/I/U/odstavec), data v textarei jako HTML ---- */
	  function initRte(scope){
	    (scope||document).querySelectorAll('textarea.pok-rte-src').forEach(function(ta){
	      if(ta.dataset.rteInit) return; ta.dataset.rteInit='1';
	      ta.style.display='none';
	      var bar=document.createElement('div'); bar.className='pok-rte-bar';
	      [['B','bold','Tučně'],['I','italic','Kurzíva'],['U','underline','Podtržení'],['¶','p','Odstavec']].forEach(function(b){
	        var btn=document.createElement('button'); btn.type='button'; btn.textContent=b[0]; btn.title=b[2]; btn.setAttribute('data-cmd',b[1]);
	        if(b[1]==='italic'){ btn.style.fontStyle='italic'; btn.style.fontWeight='400'; }
	        if(b[1]==='underline'){ btn.style.textDecoration='underline'; }
	        bar.appendChild(btn);
	      });
	      var ed=document.createElement('div'); ed.className='pok-rte'; ed.contentEditable='true';
	      ed.innerHTML=ta.value||'<p></p>';
	      ta.parentNode.insertBefore(bar, ta.nextSibling);
	      ta.parentNode.insertBefore(ed, bar.nextSibling);
	      function sync(){ ta.value=ed.innerHTML; }
	      ed.addEventListener('input', sync);
	      ed.addEventListener('blur', sync);
	      bar.addEventListener('click', function(e){
	        var cmd=e.target.getAttribute&&e.target.getAttribute('data-cmd'); if(!cmd) return;
	        e.preventDefault(); ed.focus();
	        if(cmd==='p') document.execCommand('formatBlock', false, 'p');
	        else document.execCommand(cmd, false, null);
	        sync();
	      });
	    });
	  }
	  initChips(); initRte();
	  /* karty */
	  var tabs=document.querySelectorAll('#pok-tabs .nav-tab');
	  tabs.forEach(function(t){ t.addEventListener('click', function(e){ e.preventDefault();
	    tabs.forEach(function(x){ x.classList.remove('nav-tab-active'); }); t.classList.add('nav-tab-active');
	    document.querySelectorAll('.pok-tab').forEach(function(d){ d.style.display=d.getAttribute('data-tab')===t.getAttribute('data-tab')?'':'none'; });
	  }); });
	  /* pokoje: přesun / mazání / přidání */
	  var wrap=document.getElementById('pok-rooms');
	  document.addEventListener('click', function(e){
	    var room=e.target.closest('.pok-room');
	    if(e.target.classList.contains('pok-up') && room && room.previousElementSibling) wrap.insertBefore(room, room.previousElementSibling);
	    if(e.target.classList.contains('pok-down') && room && room.nextElementSibling) wrap.insertBefore(room.nextElementSibling, room);
	    if(e.target.classList.contains('pok-del') && room && wrap.children.length>1 && confirm('Smazat kategorii?')) room.remove();
	    if(e.target.classList.contains('pok-lbl-del')){ var tb=e.target.closest('tbody'); if(tb.rows.length>1) e.target.closest('tr').remove(); }
	    var tr=e.target.closest('tr');
	    if(e.target.classList.contains('pok-row-up') && tr && tr.previousElementSibling) tr.parentNode.insertBefore(tr, tr.previousElementSibling);
	    if(e.target.classList.contains('pok-row-down') && tr && tr.nextElementSibling) tr.parentNode.insertBefore(tr.nextElementSibling, tr);
	    if(e.target.classList.contains('pok-row-del') && tr && tr.parentNode.rows.length>1) tr.remove();
	  });
	  document.addEventListener('click', function(e){
	    if(!e.target.classList.contains('pok-media')) return;
	    e.preventDefault();
	    if(typeof wp==='undefined'||!wp.media) return alert('Knihovna médií není dostupná.');
	    var input=e.target.closest('span').querySelector('input');
	    var frame=wp.media({ title:'Vybrat obrázek karty', multiple:false, library:{type:'image'} });
	    frame.on('select', function(){ var att=frame.state().get('selection').first().toJSON();
	      input.value=(att.sizes&&att.sizes.large?att.sizes.large.url:att.url); });
	    frame.open();
	  });
	  document.getElementById('pok-room-add').addEventListener('click', function(){
	    var c=wrap.querySelector('.pok-room').cloneNode(true);
	    c.querySelectorAll('.pok-chipbox,.pok-rte-bar,.pok-rte').forEach(function(el){ el.remove(); });
	    c.querySelectorAll('textarea').forEach(function(t){ delete t.dataset.chipsInit; delete t.dataset.rteInit; });
	    c.querySelectorAll('input[type=text],input[type=url],textarea').forEach(function(i){ i.value=''; });
	    c.querySelectorAll('input[type=checkbox]').forEach(function(i){ i.checked=false; });
	    c.querySelector('.pok-room-title').textContent='Nová kategorie';
	    c.setAttribute('open','');
	    wrap.appendChild(c);
	    initChips(c); initRte(c);
	  });
	  document.getElementById('pok-expand').addEventListener('click', function(){ wrap.querySelectorAll('.pok-room').forEach(function(d){ d.setAttribute('open',''); }); });
	  document.getElementById('pok-collapse').addEventListener('click', function(){ wrap.querySelectorAll('.pok-room').forEach(function(d){ d.removeAttribute('open'); }); });
	  wrap.addEventListener('input', function(e){
	    var card=e.target.closest('.pok-room'); if(!card) return;
	    var n=card.querySelector('input[name="__nazev_cz"]');
	    var t=card.querySelector('.pok-room-title'); if(t&&n) t.textContent=n.value||'Nová kategorie';
	  });
	  document.getElementById('pok-lbl-add').addEventListener('click', function(){
	    var tb=document.querySelector('#pok-labels tbody'); var tr=tb.rows[0].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; }); tb.appendChild(tr);
	  });
	  document.getElementById('pok-row-add').addEventListener('click', function(){
	    var tb=document.querySelector('#pok-compare tbody'); var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; }); tb.appendChild(tr);
	  });
	  /* při odeslání: přečíslovat name atributy podle aktuálního pořadí v DOM */
	  document.getElementById('pok-form').addEventListener('submit', function(){
	    [].forEach.call(wrap.querySelectorAll('.pok-room'), function(room, i){
	      var P=O+'[rooms]['+i+']';
	      var map={'__key':'[key]','__kod_cz':'[kod_cz]','__home':'[home]','__nazev_cz':'[nazev_cz]','__nazev_en':'[nazev_en]','__nazev_de':'[nazev_de]',
	        '__kratky_cz':'[kratky_cz]','__kratky_en':'[kratky_en]','__kratky_de':'[kratky_de]','__pocet':'[pocet]','__kapacita':'[kapacita]','__velikost':'[velikost]','__img':'[img]',
	        '__postel_cz':'[postel_cz]','__postel_en':'[postel_en]','__postel_de':'[postel_de]',
	        '__koupelna_cz':'[koupelna_cz]','__koupelna_en':'[koupelna_en]','__koupelna_de':'[koupelna_de]',
	        '__zarizeni_cz':'[zarizeni_cz]','__zarizeni_en':'[zarizeni_en]','__zarizeni_de':'[zarizeni_de]',
	        '__popis_cz':'[popis_cz]','__popis_en':'[popis_en]','__popis_de':'[popis_de]'};
	      room.querySelectorAll('input,textarea,select').forEach(function(el){
	        var n=el.getAttribute('name'); if(!n) return;
	        if(n==='__stitky[]'){ el.setAttribute('name', P+'[stitky][]'); return; }
	        if(map[n]) el.setAttribute('name', P+map[n]);
	      });
	    });
	    [].forEach.call(document.querySelectorAll('#pok-labels tbody tr'), function(tr,i){
	      var P=O+'[labels]['+i+']';
	      var m={'__lkey':'[key]','__lcz':'[cz]','__len':'[en]','__lde':'[de]'};
	      tr.querySelectorAll('input').forEach(function(el){ var n=el.getAttribute('name'); if(m[n]) el.setAttribute('name',P+m[n]); });
	    });
	    [].forEach.call(document.querySelectorAll('#pok-compare tbody tr'), function(tr,i){
	      var P=O+'[compare]['+i+']';
	      var m={'__ccz':'[cz]','__cen':'[en]','__cde':'[de]'};
	      tr.querySelectorAll('td').forEach(function(td){
	        var room=td.getAttribute('data-room');
	        td.querySelectorAll('input').forEach(function(el){
	          var n=el.getAttribute('name');
	          if(m[n]) el.setAttribute('name',P+m[n]);
	          else if(room && n==='__vcz') el.setAttribute('name',P+'[vals]['+room+'][cz]');
	          else if(room && n==='__ven') el.setAttribute('name',P+'[vals]['+room+'][en]');
	          else if(room && n==='__vde') el.setAttribute('name',P+'[vals]['+room+'][de]');
	        });
	      });
	    });
	  });
	})();
	</script>
	<?php
}

/* ============================================================================
 * Frontend
 * ============================================================================ */
function garry_pok_strings() {
	$T = array(
		array( 'price' => 'Nejlepší cena <b>přímo u hotelu</b>', 'cta' => 'Detail &amp; vybavení →',
			'th' => array( 'Typ pokoje', 'Velikost', 'Kapacita', 'Počet pokojů' ), 'celkem' => 'Celkem', 'os' => 'os.', 'hint' => 'Zobrazit detail pokoje →' ),
		array( 'price' => 'Best rate <b>direct from the hotel</b>', 'cta' => 'Details &amp; amenities →',
			'th' => array( 'Room type', 'Size', 'Capacity', 'Number of rooms' ), 'celkem' => 'Total', 'os' => 'pers.', 'hint' => 'Show room details →' ),
		array( 'price' => 'Bester Preis <b>direkt beim Hotel</b>', 'cta' => 'Details &amp; Ausstattung →',
			'th' => array( 'Zimmertyp', 'Größe', 'Kapazität', 'Anzahl der Zimmer' ), 'celkem' => 'Gesamt', 'os' => 'Pers.', 'hint' => 'Zimmerdetails anzeigen →' ),
	);
	return $T[ garry_pok_lang_idx() ];
}
/* Karty pokojů (titulní stránka + Ubytování) */
function garry_pok_cards( $atts = array() ) {
	$a = shortcode_atts( array( 'vse' => 0 ), $atts );
	$labels = garry_pok_labels_map(); $t = garry_pok_strings();
	$suf = garry_pok_suffix();
	ob_start();
	echo '<div class="rooms">';
	$d = 1;
	foreach ( garry_pok_get()['rooms'] as $r ) {
		if ( empty( $r['key'] ) ) continue;
		if ( ! $a['vse'] && empty( $r['home'] ) ) continue;
		$kod = ( $suf !== 'cz' && ! empty( $r[ 'kod_' . $suf ] ) ) ? $r[ 'kod_' . $suf ] : $r['kod_cz'];
		$url = garry_pok_term_url( $r['key'] );
		echo '<div class="room room--m2 hover-card2 reveal d' . $d . '">';
		$d = $d == 2 ? 1 : 2;
		echo '<img src="' . esc_url( $r['img'] ) . '" alt="' . esc_attr( garry_pok_f( $r, 'nazev' ) ) . '">';
		echo '<div class="r-body"><span class="r-num">' . wp_kses_post( $kod ) . '</span>';
		echo '<h3>' . esc_html( garry_pok_f( $r, 'nazev' ) ) . '</h3>';
		echo '<p class="r-desc">' . esc_html( garry_pok_f( $r, 'kratky' ) ) . '</p>';
		echo '<div class="r-feat">';
		foreach ( (array) $r['stitky'] as $lk ) {
			if ( empty( $labels[ $lk ] ) ) continue;
			$lv = garry_pok_f( array( 'x_cz' => $labels[ $lk ]['cz'], 'x_en' => $labels[ $lk ]['en'], 'x_de' => $labels[ $lk ]['de'] ), 'x' );
			echo '<span>' . esc_html( $lv ) . '</span>';
		}
		echo '</div>';
		echo '<div class="r-foot"><span class="r-price">' . wp_kses_post( $t['price'] ) . '</span><a href="' . esc_url( $url ) . '" class="btn btn-ghost">' . wp_kses_post( $t['cta'] ) . '</a></div>';
		echo '</div></div>';
	}
	echo '</div>';
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_rooms_cards', 'garry_pok_cards' ); }, 5 );

/* Přehledová tabulka pokojů (stránka Ubytování) */
function garry_pok_table() {
	$t = garry_pok_strings();
	$rooms = garry_pok_get()['rooms'];
	$total = 0; $has = false;
	ob_start();
	echo '<div style="overflow-x:auto"><table class="room-table"><thead><tr>';
	foreach ( $t['th'] as $th ) echo '<th>' . esc_html( $th ) . '</th>';
	echo '</tr></thead><tbody>';
	foreach ( $rooms as $r ) {
		if ( empty( $r['key'] ) ) continue;
		$p = (int) $r['pocet']; if ( $p > 0 ) { $total += $p; $has = true; }
		$url = garry_pok_term_url( $r['key'] );
		echo '<tr><td data-l="' . esc_attr( $t['th'][0] ) . '"><a href="' . esc_url( $url ) . '" data-hint="' . esc_attr( $t['hint'] ) . '">' . esc_html( garry_pok_f( $r, 'nazev' ) ) . '</a></td>';
		echo '<td data-l="' . esc_attr( $t['th'][1] ) . '">' . ( $r['velikost'] !== '' ? esc_html( $r['velikost'] ) . '&nbsp;m²' : '—' ) . '</td>';
		echo '<td data-l="' . esc_attr( $t['th'][2] ) . '">' . ( $r['kapacita'] !== '' ? esc_html( $r['kapacita'] ) . '&nbsp;' . esc_html( $t['os'] ) : '—' ) . '</td>';
		echo '<td data-l="' . esc_attr( $t['th'][3] ) . '">' . ( $r['pocet'] !== '' ? esc_html( $r['pocet'] ) : '—' ) . '</td></tr>';
	}
	echo '</tbody>';
	if ( $has ) echo '<tfoot><tr><td>' . esc_html( $t['celkem'] ) . '</td><td></td><td></td><td><strong>' . (int) $total . '</strong></td></tr></tfoot>';
	echo '</table></div>';
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_rooms_table', 'garry_pok_table' ); }, 5 );

/* Srovnávací tabulka (detail kategorie) — $current = klíč nebo název aktuální kategorie */
function garry_pokoje_compare_html( $current = '' ) {
	$s = garry_pok_get(); $li = garry_pok_lang_idx(); $suf = garry_pok_suffix();
	$rooms = array_values( array_filter( $s['rooms'], function ( $r ) { return ! empty( $r['key'] ); } ) );
	$cur_key = '';
	foreach ( $rooms as $r ) {
		if ( $current === $r['key'] || $current === $r['nazev_cz'] || $current === garry_pok_f( $r, 'nazev' ) ) { $cur_key = $r['key']; break; }
	}
	ob_start();
	echo '<div class="rd-tablewrap"><table class="rd-table"><thead><tr><th>' . esc_html( array( 'Vlastnost', 'Feature', 'Merkmal' )[ $li ] ) . '</th>';
	foreach ( $rooms as $r ) {
		$name = esc_html( garry_pok_f( $r, 'nazev' ) );
		$url  = garry_pok_term_url( $r['key'] );
		if ( $url ) $name = '<a href="' . esc_url( $url ) . '">' . $name . '</a>';
		echo '<th class="' . ( $r['key'] === $cur_key ? 'is-current' : '' ) . '">' . $name . '</th>';
	}
	echo '</tr></thead><tbody>';
	foreach ( $s['compare'] as $row ) {
		$lbl = $row[ $suf === 'cz' ? 'cz' : $suf ] ?: $row['cz'];
		echo '<tr><td>' . esc_html( $lbl ) . '</td>';
		foreach ( $rooms as $r ) {
			$v = $row['vals'][ $r['key'] ] ?? array( 'cz' => '–' );
			$val = ( $v[ $suf === 'cz' ? 'cz' : $suf ] ?? '' ) !== '' ? $v[ $suf === 'cz' ? 'cz' : $suf ] : ( $v['cz'] ?? '–' );
			echo '<td class="' . ( $r['key'] === $cur_key ? 'is-current' : '' ) . '">' . wp_kses_post( $val ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table></div>';
	return ob_get_clean();
}

/* Data pokoje pro šablonu detailu (dle klíče, aktuální jazyk) */
function garry_pokoje_room( $key ) {
	$rooms = garry_pok_rooms();
	return $rooms[ $key ] ?? null;
}
