<?php
/**
 * Plugin Name:       GARRY – Sezóna & čekací list
 * Plugin URI:        https://www.garry.cz
 * Description:       Akce sezóny pro sekci T6: karty administrace (Akce s náhledem widgetu, editovatelné štítky obsazenosti s barvami, log poptávek), funkční čekací formulář s odesíláním na e-mail a háčkem pro Google reCAPTCHA. Frontend: [grid_season_events limit="5"].
 * Version:           2.5.0
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-sezona-cekaci-list
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
 * GARRY – Sezóna & čekací list v2 — data
 * ============================================================================ */

define( 'GARRY_SEZ_OPT', 'garry_sezona' );
define( 'GARRY_SEZ_LOG', 'garry_sezona_log' );

function garry_sez_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_sez_lang_idx() { $l = garry_sez_lang(); return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 ); }

/* Výchozí štítky obsazenosti (editovatelné na kartě „Štítky obsazenosti").
 * legacy = chování widgetu: free (rezervace) / few (poslední) / full (čekací list) */
function garry_sez_default_states() {
	return array(
		array( 'key'=>'volne',    'cz'=>'Volné pokoje',    'en'=>'Rooms available', 'de'=>'Freie Zimmer',
			'cta_cz'=>'Rezervovat →', 'cta_en'=>'Book now →', 'cta_de'=>'Buchen →',    'color'=>'#2ecc71', 'legacy'=>'free' ),
		array( 'key'=>'posledni', 'cz'=>'Poslední pokoje', 'en'=>'Last rooms',      'de'=>'Letzte Zimmer',
			'cta_cz'=>'Rezervovat →', 'cta_en'=>'Book now →', 'cta_de'=>'Buchen →',    'color'=>'#caa75f', 'legacy'=>'few' ),
		array( 'key'=>'cekaci',   'cz'=>'Čekací list',     'en'=>'Waiting list',    'de'=>'Warteliste',
			'cta_cz'=>'Zapsat se →',  'cta_en'=>'Sign up →',  'cta_de'=>'Eintragen →', 'color'=>'#FF5A50', 'legacy'=>'full' ),
		array( 'key'=>'plne',     'cz'=>'Plně obsazeno',   'en'=>'Fully booked',    'de'=>'Ausgebucht',
			'cta_cz'=>'Zapsat se →',  'cta_en'=>'Sign up →',  'cta_de'=>'Eintragen →', 'color'=>'#8F8E90', 'legacy'=>'full' ),
	);
}
function garry_sez_default_events() {
	/* Reálný kalendář závodů — automotodrombrno.cz/kalendar-akci/zavody/ (načteno 2026-07-22) */
	return array(
		array( 'od'=>'2026-09-11','do'=>'2026-09-13','cz'=>'Masaryk Racing Days 2026','en'=>'Masaryk Racing Days 2026','de'=>'Masaryk Racing Days 2026',
			'pcz'=>'Mezinárodní závodní víkend okruhových šampionátů','pen'=>'International circuit racing weekend','pde'=>'Internationales Rundstrecken-Rennwochenende',
			'dcz'=>'Nenechte si ujít vrchol automobilové sezony na Masarykově okruhu. Víkend Masaryk Racing Days nabídne všechno, po čem motoristický fanoušek touží. Rychlé supersporty GT, tvrdé souboje v cestovních vozech, závodnické naděje ve formuli 4 i bohatě obsazené pohárové šampionáty.','den'=>'Don\'t miss the highlight of the car racing season at the Masaryk Circuit. The Masaryk Racing Days weekend offers everything a motorsport fan could wish for: fast GT supercars, hard-fought touring car battles, rising talents in Formula 4 and richly filled cup championships.','dde'=>'Verpassen Sie nicht den Höhepunkt der Automobilsaison am Masaryk-Ring. Das Wochenende der Masaryk Racing Days bietet alles, was sich ein Motorsportfan wünscht: schnelle GT-Supersportwagen, harte Duelle der Tourenwagen, Nachwuchstalente in der Formel 4 und stark besetzte Pokal-Meisterschaften.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/masaryk-racing-days-2026/' ),
		array( 'od'=>'2026-09-18','do'=>'2026-09-20','cz'=>'Velká cena Bohumila Staši','en'=>'Bohumil Staša Grand Prix','de'=>'Großer Preis von Bohumil Staša',
			'pcz'=>'Tradiční motocyklové závody na Masarykově okruhu','pen'=>'Traditional motorcycle races at the Masaryk Circuit','pde'=>'Traditionelle Motorradrennen am Masaryk-Ring',
			'dcz'=>'Tuzemská špička motocyklových závodníků se v polovině září představí na Masarykově okruhu. Tradiční podnik nese jméno po legendě československého motocyklového sportu.','den'=>'The best of Czech motorcycle racing takes to the Masaryk Circuit in mid-September. The traditional event bears the name of a legend of Czechoslovak motorcycle sport.','dde'=>'Die tschechische Motorrad-Elite tritt Mitte September am Masaryk-Ring an. Die traditionsreiche Veranstaltung trägt den Namen einer Legende des tschechoslowakischen Motorradsports.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/velka-cena-bohumila-stasi-2026/' ),
		array( 'od'=>'2026-09-24','do'=>'2026-09-27','cz'=>'Porsche Sprint Challenge Central Europe','en'=>'Porsche Sprint Challenge Central Europe','de'=>'Porsche Sprint Challenge Central Europe',
			'pcz'=>'Značkový pohár vozů Porsche','pen'=>'Porsche one-make cup racing','pde'=>'Porsche-Markenpokal',
			'dcz'=>'Porsche se vrací na Masarykův okruh. Na vlastní oči uvidíte legendární model 911 v té nejčistší závodní podobě. Porsche Sprint Challenge je šampionát pohárových vozů 911 GT3 Cup, to znamená, že o vítězství a prohře rozhodne jen a pouze umění a dravost závodníka.','den'=>'Porsche returns to the Masaryk Circuit. See the legendary 911 in its purest racing form with your own eyes. The Porsche Sprint Challenge is a one-make championship of 911 GT3 Cup cars — victory and defeat are decided purely by the skill and daring of the driver.','dde'=>'Porsche kehrt an den Masaryk-Ring zurück. Erleben Sie den legendären 911 in seiner reinsten Rennform. Die Porsche Sprint Challenge ist ein Markenpokal der 911 GT3 Cup — über Sieg und Niederlage entscheiden allein Können und Mut der Fahrer.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/porsche-sprint-challenge-central-europe-2026/' ),
		array( 'od'=>'2026-10-10','do'=>'2026-10-11','cz'=>'Race Car Show – MM závodů automobilů do vrchu','en'=>'Race Car Show – Hill Climb Championship','de'=>'Race Car Show – Bergrenn-Meisterschaft',
			'pcz'=>'Mezinárodní mistrovství závodů automobilů do vrchu','pen'=>'International hill climb championship','pde'=>'Internationale Bergrenn-Meisterschaft',
			'dcz'=>'Přijďte si vychutnat další závod do vrchu na Masarykově okruhu. V tradičním podzimním termínu se můžete těšit na pestrou přehlídku rychlosti a preciznosti. Akci organizuje Maverick Rescue z.s.','den'=>'Enjoy another hill climb race at the Masaryk Circuit. In the traditional autumn date you can look forward to a varied showcase of speed and precision. The event is organised by Maverick Rescue.','dde'=>'Genießen Sie ein weiteres Bergrennen am Masaryk-Ring. Zum traditionellen Herbsttermin erwartet Sie eine abwechslungsreiche Schau von Geschwindigkeit und Präzision. Veranstalter ist Maverick Rescue.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/mezinarodni-mistrovstvi-zavodu-automobilu-do-vrchu-2026-2/' ),
		array( 'od'=>'2026-10-17','do'=>'2026-10-17','cz'=>'Tuning Show Brno','en'=>'Tuning Show Brno','de'=>'Tuning Show Brno',
			'pcz'=>'Přehlídka upravených vozů na okruhu','pen'=>'Tuned car show at the circuit','pde'=>'Tuning-Schau an der Rennstrecke',
			'dcz'=>'Milovníci tuningu se mohou těšit na závěrečnou show roku, která nabídne vše, co k pořádné akci patří: od tuningového srazu, výstavy supersportů a volných jízd po dráze až po závod do vrchu.','den'=>'Tuning fans can look forward to the closing show of the year, offering everything a proper event needs: a tuning meet, a supercar exhibition, free track sessions and a hill climb race.','dde'=>'Tuning-Fans können sich auf die Abschluss-Show des Jahres freuen, die alles bietet, was zu einem richtigen Event gehört: Tuning-Treffen, Supersportwagen-Ausstellung, freie Fahrten auf der Strecke und ein Bergrennen.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/tuning-show-brno-2026/' ),
		array( 'od'=>'2026-10-18','do'=>'2026-10-18','cz'=>'8h Le Brno','en'=>'8h Le Brno','de'=>'8h Le Brno',
			'pcz'=>'Osmihodinový vytrvalostní závod','pen'=>'Eight-hour endurance race','pde'=>'Acht-Stunden-Langstreckenrennen',
			'dcz'=>'Závod amatérské vytrvalostní série ARC Endurance pořádané společností Auto Rallye Cross, která funguje jako promotér již od roku 2001.','den'=>'A race of the amateur endurance series ARC Endurance, organised by Auto Rallye Cross, a promoter active since 2001.','dde'=>'Ein Rennen der Amateur-Langstreckenserie ARC Endurance, veranstaltet von Auto Rallye Cross, das seit 2001 als Promoter aktiv ist.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/le-brno-8h-2026/' ),
	);
}
function garry_sez_get() {
	$o = get_option( GARRY_SEZ_OPT, null );
	if ( ! is_array( $o ) ) $o = array( 'events' => garry_sez_default_events() );
	$o = wp_parse_args( $o, array( 'events' => array(), 'states' => array(), 'email' => '' ) );
	if ( empty( $o['states'] ) ) $o['states'] = garry_sez_default_states();
	return $o;
}
/* Štítky jako mapa key => data (pořadí zachováno) */
function garry_sez_states() {
	$out = array();
	foreach ( garry_sez_get()['states'] as $st ) {
		if ( empty( $st['key'] ) ) continue;
		$out[ $st['key'] ] = $st;
	}
	return $out;
}
function garry_sez_state_label( $st, $li ) { return $st[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $st['cz']; }
function garry_sez_state_cta( $st, $li )   { return $st[ array( 'cta_cz', 'cta_en', 'cta_de' )[ $li ] ] ?: $st['cta_cz']; }

/* ---------- registrace do menu (GARRY + GRID Nastavení) ---------- */
Garry_Promotion_Registry::register( array(
	'slug' => 'garry-sezona-cekaci-list', 'title' => 'Sezóna & čekací list',
	'callback' => 'garry_sez_admin_page', 'plugin_file' => __FILE__,
	'doc' => '<p><code>[grid_season_events limit="5" rezim="seznam"]</code> — seznam akcí + čekací list (titulní stránka: 5 nejbližších). <code>[grid_season_events limit="0" rezim="karty"]</code> — všechny budoucí akce jako karty (stránka Sezóna). <code>[grid_voucher_form]</code> — objednávka dárkového poukazu (Zážitky).</p><p>Čekací list i poukazy jedou přes <strong>Fluent Forms</strong> (mapa formulářů v option <code>grid_ff_forms</code>): select akce se plní dynamicky budoucími akcemi, odeslání se zapisuje do logu pluginu a posílá na e-mail z nastavení. Anti-spam lze napojit filtrem <code>garry_sez_verify_request</code>.</p><p>Akce, štítky a log spravuje personál v <strong>GRID Nastavení → Sezóna & čekací list</strong>.</p>',
	'grid_slug' => 'garry-sezona-grid',
	'dashicon' => 'dashicons-flag', 'position' => 25,
) );
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return;
	if ( class_exists( 'Garry_Promotion_Registry' ) && ! Garry_Promotion_Registry::grid_visible( 'garry-sezona-cekaci-list' ) ) return;
	add_submenu_page( 'grid-options', 'Sezóna & čekací list', 'Sezóna & čekací list',
		Garry_Promotion_Registry::STAFF_CAPABILITY, 'garry-sezona-grid', 'garry_sez_admin_page' );
}, 100 );

add_action( 'admin_init', function () { register_setting( 'garry_sez_group', GARRY_SEZ_OPT, 'garry_sez_sanitize' ); } );
/* personál (Editor) smí ukládat přes options.php */
add_filter( 'option_page_capability_garry_sez_group', function () { return Garry_Promotion_Registry::STAFF_CAPABILITY; } );
function garry_sez_sanitize( $in ) {
	$out = array( 'events' => array(), 'states' => array(), 'email' => '' );
	if ( ! is_array( $in ) ) return $out;
	$out['email'] = sanitize_email( $in['email'] ?? '' );

	/* štítky */
	$st = $in['states'] ?? array();
	$n = count( $st['key'] ?? array() );
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $st['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $st['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$legacy = in_array( $st['legacy'][ $i ] ?? '', array( 'free', 'few', 'full' ), true ) ? $st['legacy'][ $i ] : 'free';
		$color  = preg_match( '/^#[0-9a-fA-F]{6}$/', $st['color'][ $i ] ?? '' ) ? $st['color'][ $i ] : '#B9B7B9';
		$out['states'][] = array(
			'key' => $key, 'cz' => $cz,
			'en' => sanitize_text_field( $st['en'][ $i ] ?? '' ),
			'de' => sanitize_text_field( $st['de'][ $i ] ?? '' ),
			'cta_cz' => sanitize_text_field( $st['cta_cz'][ $i ] ?? '' ),
			'cta_en' => sanitize_text_field( $st['cta_en'][ $i ] ?? '' ),
			'cta_de' => sanitize_text_field( $st['cta_de'][ $i ] ?? '' ),
			'color' => $color, 'legacy' => $legacy,
		);
	}
	if ( ! $out['states'] ) $out['states'] = garry_sez_default_states();
	$state_keys = wp_list_pluck( $out['states'], 'key' );

	/* akce */
	$e = $in['events'] ?? array();
	$n = max( count( $e['cz'] ?? array() ), count( $e['od'] ?? array() ) );
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'od'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $e['od'][ $i ] ?? '' ) ? $e['od'][ $i ] : '',
			'do'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $e['do'][ $i ] ?? '' ) ? $e['do'][ $i ] : '',
			'cz'   => sanitize_text_field( $e['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $e['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $e['de'][ $i ] ?? '' ),
			'pcz'  => sanitize_text_field( $e['pcz'][ $i ] ?? '' ),
			'pen'  => sanitize_text_field( $e['pen'][ $i ] ?? '' ),
			'pde'  => sanitize_text_field( $e['pde'][ $i ] ?? '' ),
			'stav' => in_array( $e['stav'][ $i ] ?? '', $state_keys, true ) ? $e['stav'][ $i ] : ( $state_keys[0] ?? 'volne' ),
			'url'  => esc_url_raw( $e['url'][ $i ] ?? '' ),
			'dcz'  => sanitize_textarea_field( $e['dcz'][ $i ] ?? '' ),
			'den'  => sanitize_textarea_field( $e['den'][ $i ] ?? '' ),
			'dde'  => sanitize_textarea_field( $e['dde'][ $i ] ?? '' ),
		);
		if ( $row['cz'] === '' && $row['od'] === '' ) continue;
		$out['events'][] = $row;
	}
	usort( $out['events'], function ( $a, $b ) { return strcmp( $a['od'], $b['od'] ); } );
	return $out;
}

/* smazání logu */
add_action( 'admin_post_garry_sez_clear_log', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'garry_sez_clear_log' ) ) wp_die( 'Nedostatečná oprávnění.' );
	delete_option( GARRY_SEZ_LOG );
	wp_safe_redirect( add_query_arg( array( 'page' => $_REQUEST['back'] ?? 'garry-sezona-cekaci-list', 'tab' => 'log', 'cleared' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ============================================================================
 * Administrace — karty: Akce | Štítky obsazenosti | Poptávky
 * ============================================================================ */
function garry_sez_admin_page() {
	if ( ! current_user_can( Garry_Promotion_Registry::STAFF_CAPABILITY ) ) return;
	$s = garry_sez_get();
	$states = garry_sez_states();
	$events = $s['events'];
	if ( ! $events ) $events = array( array( 'od'=>'','do'=>'','cz'=>'','en'=>'','de'=>'','pcz'=>'','pen'=>'','pde'=>'','stav'=>array_key_first( $states ) ) );
	$log = get_option( GARRY_SEZ_LOG, array() ); if ( ! is_array( $log ) ) $log = array();
	$O = GARRY_SEZ_OPT;
	$page_slug = $_GET['page'] ?? 'garry-sezona-cekaci-list';
	$active = $_GET['tab'] ?? 'akce';
	if ( ! in_array( $active, array( 'akce', 'stitky', 'log' ), true ) ) $active = 'akce';
	?>
	<div class="wrap"><h1>Sezóna & čekací list</h1>
	<?php if ( isset( $_GET['cleared'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Log poptávek byl smazán.</p></div>'; ?>
	<h2 class="nav-tab-wrapper" id="sez-tabs">
	  <a href="#" class="nav-tab" data-tab="akce">Akce sezóny</a>
	  <a href="#" class="nav-tab" data-tab="stitky">Štítky obsazenosti</a>
	  <a href="#" class="nav-tab" data-tab="log">Poptávky <?php if ( $log ) printf( '<span class="awaiting-mod count-%1$d"><span>%1$d</span></span>', count( $log ) ); ?></a>
	</h2>

	<form method="post" action="options.php">
	<?php settings_fields( 'garry_sez_group' ); ?>

	<!-- ================== KARTA: AKCE ================== -->
	<div class="sez-tab" data-tab="akce">
	  <div style="display:flex;gap:26px;flex-wrap:wrap;align-items:flex-start;margin-top:16px">
	    <div style="flex:1 1 620px;min-width:560px" id="sez-events">
	      <?php foreach ( $events as $ev ) : ?>
	      <details class="sez-event" style="background:#fff;border:1px solid #c3c4c7;border-radius:8px;padding:10px 16px;margin-bottom:14px">
	        <summary style="cursor:pointer;display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:4px 0;list-style-position:outside">
	          <strong class="sez-sum-name"><?php echo esc_html( $ev['cz'] ?: 'Nová akce' ); ?></strong>
	          <span class="sez-sum-meta description"><?php echo esc_html( trim( ( $ev['od'] ?: '' ) . ( $ev['do'] && $ev['do'] !== $ev['od'] ? ' – ' . $ev['do'] : '' ) ) ); ?></span>
	        </summary>
	        <div class="sez-event-body" style="padding-top:10px;border-top:1px solid #eee;margin-top:8px">
	        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
	          <label>Od <input type="date" name="<?php echo $O; ?>[events][od][]" value="<?php echo esc_attr( $ev['od'] ); ?>"></label>
	          <label>Do <input type="date" name="<?php echo $O; ?>[events][do][]" value="<?php echo esc_attr( $ev['do'] ); ?>"></label>
	          <label>Obsazenost <select name="<?php echo $O; ?>[events][stav][]" class="sez-ev-stav">
	            <?php foreach ( $states as $k => $st ) printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $ev['stav'], $k, false ), esc_html( $st['cz'] ) ); ?>
	          </select></label>
	          <label style="flex:1;min-width:260px">Detail akce (URL na autodrom) <input type="url" style="width:100%" name="<?php echo $O; ?>[events][url][]" value="<?php echo esc_attr( $ev['url'] ?? '' ); ?>" placeholder="https://www.automotodrombrno.cz/…"></label>
	          <button type="button" class="button-link sez-ev-del" style="color:#b32d2e;margin-left:auto">Smazat akci ×</button>
	        </div>
	        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:8px">
	          <label>Název CZ<input type="text" style="width:100%" name="<?php echo $O; ?>[events][cz][]" value="<?php echo esc_attr( $ev['cz'] ); ?>"></label>
	          <label>Název EN<input type="text" style="width:100%" name="<?php echo $O; ?>[events][en][]" value="<?php echo esc_attr( $ev['en'] ); ?>"></label>
	          <label>Název DE<input type="text" style="width:100%" name="<?php echo $O; ?>[events][de][]" value="<?php echo esc_attr( $ev['de'] ); ?>"></label>
	        </div>
	        <div style="display:grid;grid-template-columns:1fr;gap:8px">
	          <label>Perex CZ<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pcz][]" value="<?php echo esc_attr( $ev['pcz'] ); ?>" placeholder="Krátká věta do seznamu akcí"></label>
	          <label>Perex EN<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pen][]" value="<?php echo esc_attr( $ev['pen'] ); ?>"></label>
	          <label>Perex DE<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pde][]" value="<?php echo esc_attr( $ev['pde'] ); ?>"></label>
	        </div>
	        <p class="description" style="margin:8px 0 4px">Detailní popis („O akci") — zobrazuje se v kartách akcí na stránce Sezóna:</p>
	        <div style="display:grid;grid-template-columns:1fr;gap:6px">
	          <label>O akci CZ<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][dcz][]"><?php echo esc_textarea( $ev['dcz'] ?? '' ); ?></textarea></label>
	          <label>O akci EN<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][den][]"><?php echo esc_textarea( $ev['den'] ?? '' ); ?></textarea></label>
	          <label>O akci DE<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][dde][]"><?php echo esc_textarea( $ev['dde'] ?? '' ); ?></textarea></label>
	        </div>
	        </div>
	      </details>
	      <?php endforeach; ?>
	      <p style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="button" id="sez-ev-add">+ Přidat akci</button>
	      <button type="button" class="button" id="sez-expand">Rozbalit vše</button>
	      <button type="button" class="button" id="sez-collapse">Sbalit vše</button></p>
	      <p><label><strong>E-mail pro poptávky z formuláře:</strong>
	        <input type="email" name="<?php echo $O; ?>[email]" value="<?php echo esc_attr( $s['email'] ); ?>" placeholder="reservations@gridhotel.cz" class="regular-text"></label><br>
	        <span class="description">Sem chodí žádosti o rezervaci / zápis na čekací list. Prázdné = e-mail správce webu. Odeslání formuláře lze chránit Google reCAPTCHA pluginem (hook <code>garry_sez_verify_request</code>).</span></p>
	    </div>
	    <div style="flex:0 0 380px">
	      <p style="font-weight:600;margin:0 0 8px">Náhled widgetu na stránce</p>
	      <div id="sez-preview" style="background:#0d0f12;border:1px solid #ccd0d4;border-radius:8px;padding:18px;min-height:280px;color:#e6e4e2;font-family:sans-serif"></div>
	      <p class="description" style="margin-top:8px">Živý náhled — 5 nejbližších akcí podle data, proběhlé se nezobrazují.</p>
	    </div>
	  </div>
	</div>

	<!-- ================== KARTA: ŠTÍTKY ================== -->
	<div class="sez-tab" data-tab="stitky" style="display:none">
	  <p style="margin-top:16px">Vlastní typy obsazenosti. <strong>Chování</strong> určuje, co formulář nabídne
	  (Rezervace = zelený režim, Poslední pokoje, Čekací list = zápis do čekací listiny).</p>
	  <table class="widefat striped" id="sez-states" style="max-width:1200px">
	    <thead><tr>
	      <th style="width:110px">Klíč</th><th>Štítek CZ</th><th>Štítek EN</th><th>Štítek DE</th>
	      <th>CTA CZ</th><th>CTA EN</th><th>CTA DE</th>
	      <th style="width:70px">Barva</th><th style="width:150px">Chování</th><th style="width:32px"></th>
	    </tr></thead><tbody>
	    <?php foreach ( $states as $k => $st ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo $O; ?>[states][key][]" value="<?php echo esc_attr( $k ); ?>"></td>
	        <?php foreach ( array( 'cz','en','de','cta_cz','cta_en','cta_de' ) as $f ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo $O; ?>[states][<?php echo $f; ?>][]" value="<?php echo esc_attr( $st[ $f ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td><input type="color" name="<?php echo $O; ?>[states][color][]" value="<?php echo esc_attr( $st['color'] ); ?>"></td>
	        <td><select name="<?php echo $O; ?>[states][legacy][]">
	          <option value="free" <?php selected( $st['legacy'], 'free' ); ?>>Rezervace (volno)</option>
	          <option value="few" <?php selected( $st['legacy'], 'few' ); ?>>Poslední pokoje</option>
	          <option value="full" <?php selected( $st['legacy'], 'full' ); ?>>Čekací list</option>
	        </select></td>
	        <td><button type="button" class="button-link sez-st-del" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><button type="button" class="button" id="sez-st-add">+ Přidat štítek</button></p>
	</div>

	<!-- ================== KARTA: LOG ================== -->
	<div class="sez-tab" data-tab="log" style="display:none">
	  <p style="margin-top:16px">Poptávky odeslané z formuláře na webu (nejnovější nahoře). Kopie chodí na nastavený e-mail.</p>
	  <?php if ( ! $log ) : ?>
	    <p><em>Zatím žádné poptávky.</em></p>
	  <?php else : ?>
	  <table class="widefat striped" style="max-width:1100px">
	    <thead><tr><th>Datum a čas</th><th>Akce / termín</th><th>Typ pokoje</th><th>Jméno</th><th>E-mail</th><th>Jazyk</th><th>IP</th></tr></thead><tbody>
	    <?php foreach ( array_reverse( $log ) as $r ) : ?>
	      <tr>
	        <td><?php echo esc_html( $r['cas'] ?? '' ); ?></td>
	        <td><strong><?php echo esc_html( $r['akce'] ?? '' ); ?></strong></td>
	        <td><?php echo esc_html( $r['pokoj'] ?? '' ); ?></td>
	        <td><?php echo esc_html( $r['jmeno'] ?? '' ); ?></td>
	        <td><a href="mailto:<?php echo esc_attr( $r['email'] ?? '' ); ?>"><?php echo esc_html( $r['email'] ?? '' ); ?></a></td>
	        <td><?php echo esc_html( strtoupper( $r['jazyk'] ?? '' ) ); ?></td>
	        <td><?php echo esc_html( $r['ip'] ?? '' ); ?></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=garry_sez_clear_log&back=' . urlencode( $page_slug ) ), 'garry_sez_clear_log' ) ); ?>"
	    onclick="return confirm('Opravdu smazat celý log poptávek?');">Smazat log</a></p>
	  <?php endif; ?>
	</div>

	<?php submit_button( 'Uložit' ); ?>
	</form></div>

	<script>
	(function(){
	  /* přepínání karet */
	  var tabs=document.querySelectorAll('#sez-tabs .nav-tab');
	  function activate(name){
	    tabs.forEach(function(t){ t.classList.toggle('nav-tab-active', t.getAttribute('data-tab')===name); });
	    document.querySelectorAll('.sez-tab').forEach(function(d){ d.style.display = d.getAttribute('data-tab')===name ? '' : 'none'; });
	  }
	  tabs.forEach(function(t){ t.addEventListener('click', function(e){ e.preventDefault(); activate(t.getAttribute('data-tab')); }); });
	  activate(<?php echo wp_json_encode( $active ); ?>);

	  /* akce: přidat/smazat kartu */
	  var evWrap=document.getElementById('sez-events');
	  document.getElementById('sez-ev-add').addEventListener('click', function(){
	    var cards=evWrap.querySelectorAll('.sez-event');
	    var c=cards[cards.length-1].cloneNode(true);
	    c.querySelectorAll('input,textarea').forEach(function(i){ i.value=''; });
	    c.querySelector('select').selectedIndex=0;
	    c.setAttribute('open','');
	    var sn=c.querySelector('.sez-sum-name'); if(sn) sn.textContent='Nová akce';
	    var sm=c.querySelector('.sez-sum-meta'); if(sm) sm.textContent='';
	    evWrap.insertBefore(c, cards[cards.length-1].nextSibling);
	    preview();
	  });
	  document.getElementById('sez-expand').addEventListener('click', function(){ evWrap.querySelectorAll('.sez-event').forEach(function(d){ d.setAttribute('open',''); }); });
	  document.getElementById('sez-collapse').addEventListener('click', function(){ evWrap.querySelectorAll('.sez-event').forEach(function(d){ d.removeAttribute('open'); }); });
	  /* souhrn karty se aktualizuje při psaní */
	  evWrap.addEventListener('input', function(e){
	    var card=e.target.closest('.sez-event'); if(!card) return;
	    var q=function(sel){ var el=card.querySelector(sel); return el?el.value:''; };
	    var sn=card.querySelector('.sez-sum-name'); if(sn) sn.textContent=q('input[name$="[events][cz][]"]')||'Nová akce';
	    var od=q('input[name$="[events][od][]"]'), dd=q('input[name$="[events][do][]"]');
	    var sm=card.querySelector('.sez-sum-meta'); if(sm) sm.textContent=od+(dd&&dd!==od?' – '+dd:'');
	  });
	  document.addEventListener('click', function(e){
	    if(e.target.classList.contains('sez-ev-del')){
	      var cards=evWrap.querySelectorAll('.sez-event');
	      if(cards.length>1) e.target.closest('.sez-event').remove();
	      else e.target.closest('.sez-event').querySelectorAll('input').forEach(function(i){ i.value=''; });
	      preview();
	    }
	    if(e.target.classList.contains('sez-st-del')){
	      var tb=e.target.closest('tbody');
	      if(tb.rows.length>1) e.target.closest('tr').remove();
	      preview();
	    }
	  });
	  /* štítky: přidat řádek */
	  document.getElementById('sez-st-add').addEventListener('click', function(){
	    var tb=document.querySelector('#sez-states tbody');
	    var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input[type=text]').forEach(function(i){ i.value=''; });
	    tb.appendChild(tr);
	  });

	  /* živý náhled widgetu */
	  function stateData(){
	    var out={}; var tb=document.querySelector('#sez-states tbody');
	    [].forEach.call(tb.rows, function(tr){
	      var i=tr.querySelectorAll('input,select');
	      var key=i[0].value.trim(); if(!key) return;
	      out[key]={label:i[1].value||key,color:i[7].value||'#B9B7B9'};
	    });
	    return out;
	  }
	  function fmtDate(od,dodate){
	    if(!od) return '';
	    function cz(d){ return d.getDate()+'. '+(d.getMonth()+1)+'.'; }
	    var a=new Date(od+'T12:00:00');
	    if(!dodate||od===dodate) return cz(a)+' '+a.getFullYear();
	    var b=new Date(dodate+'T12:00:00');
	    if(a.getMonth()===b.getMonth()&&a.getFullYear()===b.getFullYear()) return a.getDate()+'.–'+cz(b)+' '+b.getFullYear();
	    return cz(a)+' – '+cz(b)+' '+b.getFullYear();
	  }
	  function preview(){
	    var box=document.getElementById('sez-preview'); if(!box) return;
	    var st=stateData(); var today=new Date().toISOString().slice(0,10);
	    var items=[];
	    evWrap.querySelectorAll('.sez-event').forEach(function(card){
	      var q=function(sel){ var el=card.querySelector(sel); return el?el.value:''; };
	      var od=q('input[name$="[events][od][]"]'), dd=q('input[name$="[events][do][]"]');
	      var name=q('input[name$="[events][cz][]"]'), per=q('input[name$="[events][pcz][]"]');
	      var stav=card.querySelector('.sez-ev-stav').value;
	      if(!name&&!od) return;
	      if((dd||od) && (dd||od)<today) return;   // proběhlé
	      items.push({od:od,dd:dd,name:name,per:per,stav:stav});
	    });
	    items.sort(function(a,b){ return (a.od||'9999').localeCompare(b.od||'9999'); });
	    items=items.slice(0,5);
	    var h='<div style="font-family:monospace;font-size:10px;letter-spacing:.16em;color:#caa75f;text-transform:uppercase;margin-bottom:12px">T6 · Sezóna · čekací list</div>';
	    if(!items.length) h+='<em style="color:#8F8E90">Žádné nadcházející akce — sekce se na webu skryje.</em>';
	    items.forEach(function(it){
	      var s=st[it.stav]||{label:it.stav,color:'#B9B7B9'};
	      h+='<div style="border-bottom:1px solid rgba(255,255,255,.12);padding:10px 0">'+
	        '<div style="display:flex;justify-content:space-between;gap:10px;align-items:center">'+
	        '<span style="font-family:monospace;font-size:11px;color:#FF5A50;white-space:nowrap">'+fmtDate(it.od,it.dd)+'</span>'+
	        '<span style="font-size:10px;font-family:monospace;letter-spacing:.08em;text-transform:uppercase;border:1px solid '+s.color+'99;color:'+s.color+';padding:3px 7px;border-radius:2px;white-space:nowrap">'+s.label+'</span></div>'+
	        '<div style="font-weight:700;text-transform:uppercase;margin-top:4px">'+(it.name||'—')+'</div>'+
	        (it.per?'<div style="font-size:11.5px;color:#B9B7B9">'+it.per+'</div>':'')+
	        '</div>';
	    });
	    box.innerHTML=h;
	  }
	  document.addEventListener('input', function(e){ if(e.target.closest('.sez-tab')) preview(); });
	  preview();
	})();
	</script>
	<?php
}

/* ============================================================================
 * Frontend — [grid_season_events limit="5"] + funkční formulář
 * ============================================================================ */
function garry_sez_fmt_range( $od, $do ) {
	if ( ! $od ) return '';
	try { $a = new DateTime( $od ); } catch ( Exception $e ) { return ''; }
	$b = null;
	if ( $do ) { try { $b = new DateTime( $do ); } catch ( Exception $e ) { $b = null; } }
	if ( ! $b || $od === $do ) return $a->format( 'j. n. Y' );
	if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) return $a->format( 'j.' ) . '–' . $b->format( 'j. n. Y' );
	return $a->format( 'j. n.' ) . ' – ' . $b->format( 'j. n. Y' );
}
function garry_sez_render( $atts = array() ) {
	$a = shortcode_atts( array( 'limit' => 5, 'karty' => 0, 'rezim' => '' ), $atts );
	$rezim = $a['rezim'] ?: 'vse';
	$show_cards = ( $rezim === 'karty' ) || ( $rezim === 'vse' && ! empty( $a['karty'] ) );
	$show_list  = ( $rezim === 'seznam' || $rezim === 'vse' );
	$s = garry_sez_get(); $STATES = garry_sez_states(); $li = garry_sez_lang_idx();
	$today = current_time( 'Y-m-d' );
	$events = array_values( array_filter( $s['events'], function ( $e ) use ( $today ) {
		$konec = $e['do'] ?: $e['od'];
		return $konec === '' || $konec >= $today;
	} ) );
	usort( $events, function ( $x, $y ) { return strcmp( $x['od'], $y['od'] ); } );
	if ( (int) $a['limit'] > 0 ) $events = array_slice( $events, 0, (int) $a['limit'] );
	if ( ! $events ) return '<style>#sezona{display:none}</style>';

	$T = array(
		array( 'Rezervace &amp; čekací list', 'Vyberte akci sezóny', 'Klikněte na termín vlevo, nebo vyberte akci níže. U vyprodaných termínů vás zapíšeme na čekací list.',
			'Akce / termín', 'Typ pokoje', 'Jméno a příjmení', 'Jan Novák', 'E-mail', 'vas@email.cz', 'Zapsat na čekací list',
			'✓ Hotovo! Ozveme se, jakmile se pro vybraný termín uvolní pokoj.', '// Termíny sezóny jsou orientační.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terasa)', 'Apartmá' ),
			'Odesílám…', 'Odeslání se nepovedlo — zkuste to prosím znovu, nebo nám napište na e-mail.' ),
		array( 'Booking &amp; waiting list', 'Choose your season event', "Click a date on the left or choose an event below. For sold-out dates we'll put you on the waiting list.",
			'Event / date', 'Room type', 'Full name', 'John Smith', 'E-mail', 'your@email.com', 'Join the waiting list',
			"✓ Done! We'll be in touch as soon as a room opens up for your chosen dates.", '// Season dates are indicative.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terrace)', 'Apartment' ),
			'Sending…', 'Sending failed — please try again or contact us by e-mail.' ),
		array( 'Buchung &amp; Warteliste', 'Wählen Sie ein Saison-Event', 'Klicken Sie links auf einen Termin oder wählen Sie unten ein Event. Bei ausverkauften Terminen tragen wir Sie in die Warteliste ein.',
			'Event / Termin', 'Zimmertyp', 'Vor- und Nachname', 'Max Mustermann', 'E-Mail', 'ihre@email.de', 'In die Warteliste eintragen',
			'✓ Fertig! Wir melden uns, sobald für den gewählten Termin ein Zimmer frei wird.', '// Die Saisontermine sind unverbindlich.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (Terrasse)', 'Appartement' ),
			'Wird gesendet…', 'Senden fehlgeschlagen — bitte erneut versuchen oder per E-Mail kontaktieren.' ),
	);
	$t = $T[ $li ];

	$css = '';
	foreach ( $STATES as $k => $st ) {
		$css .= '.ev-status.' . sanitize_html_class( $k ) . '{color:' . $st['color'] . ' !important;border-color:' . $st['color'] . '99 !important}';
	}

	ob_start();
	echo '<style>' . $css . '</style>';
	?>
	<?php if ( $show_cards ) :
		$tx = array(
			array( 'Detail akce', 'Rezervace & čekací list' ),
			array( 'Event details', 'Booking & waiting list' ),
			array( 'Event-Details', 'Buchung & Warteliste' ),
		)[ $li ]; ?>
	<div class="sez-cards">
	  <?php foreach ( $events as $e ) :
			$name  = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
			$perex = $e[ array( 'pcz', 'pen', 'pde' )[ $li ] ] ?: $e['pcz'];
			$st = $STATES[ $e['stav'] ] ?? null; if ( ! $st ) continue; ?>
	  <div class="sez-card">
	    <div class="sez-card-top"><span class="ev-date"><?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></span>
	    <span class="ev-status <?php echo esc_attr( sanitize_html_class( $e['stav'] ) . ' ' . $st['legacy'] ); ?>"><?php echo esc_html( garry_sez_state_label( $st, $li ) ); ?></span></div>
	    <h3><?php echo esc_html( $name ); ?></h3>
	    <?php $detail = $e[ array( 'dcz', 'den', 'dde' )[ $li ] ] ?? ''; if ( $detail === '' ) $detail = ( $e['dcz'] ?? '' ) ?: $perex; ?>
	    <p><?php echo esc_html( $detail ); ?></p>
	    <div class="sez-card-links">
	      <?php if ( ! empty( $e['url'] ) ) : ?><a class="sec-more" href="<?php echo esc_url( $e['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $tx[0] ); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
	      <a class="sec-more" href="#cekaci-list"><?php echo esc_html( $tx[1] ); ?> <span aria-hidden="true">↓</span></a>
	    </div>
	  </div>
	  <?php endforeach; ?>
	</div>
	<?php endif; ?>
	<?php if ( $show_list ) : ?>
	<div class="season"<?php echo $rezim === 'vse' ? ' id="cekaci-list"' : ''; ?>>
	  <div class="ev-list reveal d1 in" id="evList">
	    <?php foreach ( $events as $e ) :
			$name  = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
			$perex = $e[ array( 'pcz', 'pen', 'pde' )[ $li ] ] ?: $e['pcz'];
			$st = $STATES[ $e['stav'] ] ?? null; if ( ! $st ) continue; ?>
	    <div class="ev-row" role="button" tabindex="0" data-ev="<?php echo esc_attr( $name ); ?>">
	      <span class="ev-date"><?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></span>
	      <span class="ev-name"><?php echo esc_html( $name ); ?><small><?php echo esc_html( $perex ); ?><?php if ( ! empty( $e['url'] ) ) : ?> <a class="ev-ext" href="<?php echo esc_url( $e['url'] ); ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">↗</a><?php endif; ?></small></span>
	      <span class="ev-meta"><span class="ev-status <?php echo esc_attr( sanitize_html_class( $e['stav'] ) . ' ' . $st['legacy'] ); ?>"><?php echo esc_html( garry_sez_state_label( $st, $li ) ); ?></span>
	      <span class="ev-cta"><?php echo esc_html( garry_sez_state_cta( $st, $li ) ); ?></span></span>
	    </div>
	    <?php endforeach; ?>
	  </div>
	  <div class="waitbox reveal d2 in">
	    <span class="kicker"><?php echo $t[0]; ?></span>
	    <h3 id="wbTitle"><?php echo esc_html( $t[1] ); ?></h3>
	    <p class="wb-sub" id="wbSub"><?php echo esc_html( $t[2] ); ?></p>
	    <?php $wb_ff = garry_sez_ff_id( 'cekaci' ); if ( $wb_ff ) : ?>
	    <div class="wb-ff"><?php echo do_shortcode( '[fluentform id=' . $wb_ff . ']' ); ?></div>
	    <?php else : ?>
	    <form id="wbForm">
	      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'garry_sez_request' ) ); ?>">
	      <input type="text" name="web" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
	      <div class="wb-field"><label for="wb-ev"><?php echo esc_html( $t[3] ); ?></label><select id="wb-ev" name="akce">
	        <?php foreach ( $events as $e ) :
				$name = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz']; ?>
	        <option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?> · <?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></option>
	        <?php endforeach; ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-room"><?php echo esc_html( $t[4] ); ?></label><select id="wb-room" name="pokoj">
	        <?php foreach ( $t[12] as $i => $room ) printf( '<option %s>%s</option>', $i === 1 ? 'selected' : '', esc_html( $room ) ); ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-name"><?php echo esc_html( $t[5] ); ?></label><input type="text" id="wb-name" name="jmeno" placeholder="<?php echo esc_attr( $t[6] ); ?>" required></div>
	      <div class="wb-field"><label for="wb-email"><?php echo esc_html( $t[7] ); ?></label><input type="email" id="wb-email" name="email" placeholder="<?php echo esc_attr( $t[8] ); ?>" required></div>
	      <button type="submit" class="btn" style="width:100%;text-align:center" id="wbBtn"><?php echo esc_html( $t[9] ); ?></button>
	      <div class="wb-ok" id="wbOk"><?php echo esc_html( $t[10] ); ?></div>
	    </form>
	    <?php endif; ?>
	    <p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:14px"><?php echo esc_html( $t[11] ); ?></p>
	  </div>
	</div>
	<?php endif; ?>
	<script>
	(function(){
	  var f=document.getElementById('wbForm'); if(!f) return;
	  var ok=document.getElementById('wbOk'), btn=document.getElementById('wbBtn');
	  var msg={sending:<?php echo wp_json_encode( $t[13] ); ?>, fail:<?php echo wp_json_encode( $t[14] ); ?>, done:<?php echo wp_json_encode( $t[10] ); ?>, btn:btn.textContent, lang:<?php echo wp_json_encode( garry_sez_lang() ); ?>};
	  f.addEventListener('submit', function(e){
	    e.preventDefault(); e.stopImmediatePropagation();   /* přebít fallback v grid.js */
	    if(!f.reportValidity()) return;
	    btn.disabled=true; btn.textContent=msg.sending;
	    var data=new FormData(f);
	    data.append('action','garry_sez_request');
	    data.append('jazyk', msg.lang);
	    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {method:'POST', body:data, credentials:'same-origin'})
	      .then(function(r){ return r.json(); })
	      .then(function(j){
	        btn.disabled=false; btn.textContent=msg.btn;
	        ok.textContent = j && j.success ? msg.done : (j && j.data && j.data.message ? j.data.message : msg.fail);
	        ok.classList.add('show');
	        if(j && j.success) f.reset();
	      })
	      .catch(function(){ btn.disabled=false; btn.textContent=msg.btn; ok.textContent=msg.fail; ok.classList.add('show'); });
	  }, true);
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_season_events', 'garry_sez_render' ); }, 5 );

/* ---------- Objednávka dárkového poukazu — [grid_voucher_form] ---------- */
function garry_voucher_kinds( $li ) {
	$T = array(
		array( 'Pokoj Superior — 1 osoba (3 750 Kč)', 'Pokoj Superior Plus — 1 osoba (4 250 Kč)', 'Apartmán — 1 osoba (6 625 Kč)',
			'Pokoj Superior — 2 osoby (4 500 Kč)', 'Pokoj Superior Plus — 2 osoby (5 000 Kč)', 'Apartmán — 2 osoby (6 875 Kč)' ),
		array( 'Superior room — 1 person (CZK 3,750)', 'Superior Plus room — 1 person (CZK 4,250)', 'Apartment — 1 person (CZK 6,625)',
			'Superior room — 2 persons (CZK 4,500)', 'Superior Plus room — 2 persons (CZK 5,000)', 'Apartment — 2 persons (CZK 6,875)' ),
		array( 'Zimmer Superior — 1 Person (3 750 CZK)', 'Zimmer Superior Plus — 1 Person (4 250 CZK)', 'Appartement — 1 Person (6 625 CZK)',
			'Zimmer Superior — 2 Personen (4 500 CZK)', 'Zimmer Superior Plus — 2 Personen (5 000 CZK)', 'Appartement — 2 Personen (6 875 CZK)' ),
	);
	return $T[ $li ];
}
function garry_voucher_form() {
	$li = garry_sez_lang_idx();
	$T = array(
		array( 'Objednat dárkový poukaz', 'Jméno', 'Příjmení', 'Druh poukazu', 'E-mail', 'Odeslat objednávku',
			'✓ Děkujeme! Objednávku poukazu jsme přijali — ozveme se se zálohovou fakturou.', 'Odesílám…', 'Odeslání se nepovedlo — zkuste to prosím znovu.' ),
		array( 'Order a gift voucher', 'First name', 'Last name', 'Voucher type', 'E-mail', 'Send order',
			"✓ Thank you! We've received your voucher order — we'll follow up with a proforma invoice.", 'Sending…', 'Sending failed — please try again.' ),
		array( 'Gutschein bestellen', 'Vorname', 'Nachname', 'Gutschein-Art', 'E-Mail', 'Bestellung senden',
			'✓ Vielen Dank! Wir haben Ihre Gutschein-Bestellung erhalten und melden uns mit der Vorausrechnung.', 'Wird gesendet…', 'Senden fehlgeschlagen — bitte erneut versuchen.' ),
	);
	$t = $T[ $li ];
	ob_start(); ?>
	<div class="vou-form">
	  <h3 style="margin:30px 0 12px;color:var(--gold)"><?php echo esc_html( $t[0] ); ?></h3>
	  <?php $vou_ff = garry_sez_ff_id( 'voucher' ); if ( $vou_ff ) : ?>
	  <div class="vou-ff"><?php echo do_shortcode( '[fluentform id=' . $vou_ff . ']' ); ?></div>
	  <?php else : ?>
	  <form id="vouForm" class="form-grid">
	    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'garry_sez_request' ) ); ?>">
	    <input type="text" name="web" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
	    <div><label for="vou-jmeno"><?php echo esc_html( $t[1] ); ?></label><input id="vou-jmeno" type="text" name="jmeno" required></div>
	    <div><label for="vou-prijmeni"><?php echo esc_html( $t[2] ); ?></label><input id="vou-prijmeni" type="text" name="prijmeni" required></div>
	    <div class="full"><label for="vou-druh"><?php echo esc_html( $t[3] ); ?></label><select id="vou-druh" name="druh">
	      <?php foreach ( garry_voucher_kinds( $li ) as $k ) printf( '<option>%s</option>', esc_html( $k ) ); ?>
	    </select></div>
	    <div class="full"><label for="vou-email"><?php echo esc_html( $t[4] ); ?></label><input id="vou-email" type="email" name="email" required></div>
	    <div class="full"><button type="submit" class="btn" id="vouBtn"><?php echo esc_html( $t[5] ); ?></button></div>
	    <div class="full"><div class="wb-ok" id="vouOk"><?php echo esc_html( $t[6] ); ?></div></div>
	  </form>
	  <?php endif; ?>
	</div>
	<script>
	(function(){
	  var f=document.getElementById('vouForm'); if(!f) return;
	  var ok=document.getElementById('vouOk'), btn=document.getElementById('vouBtn');
	  var msg={sending:<?php echo wp_json_encode( $t[7] ); ?>, fail:<?php echo wp_json_encode( $t[8] ); ?>, done:<?php echo wp_json_encode( $t[6] ); ?>, btn:btn.textContent, lang:<?php echo wp_json_encode( garry_sez_lang() ); ?>};
	  f.addEventListener('submit', function(e){
	    e.preventDefault(); e.stopImmediatePropagation();
	    if(!f.reportValidity()) return;
	    btn.disabled=true; btn.textContent=msg.sending;
	    var data=new FormData(f);
	    data.append('action','garry_voucher_request');
	    data.append('jazyk', msg.lang);
	    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {method:'POST', body:data, credentials:'same-origin'})
	      .then(function(r){ return r.json(); })
	      .then(function(j){ btn.disabled=false; btn.textContent=msg.btn;
	        ok.textContent = j && j.success ? msg.done : (j && j.data && j.data.message ? j.data.message : msg.fail);
	        ok.classList.add('show'); if(j && j.success) f.reset(); })
	      .catch(function(){ btn.disabled=false; btn.textContent=msg.btn; ok.textContent=msg.fail; ok.classList.add('show'); });
	  }, true);
	})();
	</script>
	<?php return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_voucher_form', 'garry_voucher_form' ); }, 5 );

function garry_voucher_handle() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'garry_sez_request' ) ) {
		wp_send_json_error( array( 'message' => 'Neplatný požadavek — obnovte prosím stránku.' ), 400 );
	}
	if ( ! empty( $_POST['web'] ) ) wp_send_json_success();
	$verified = apply_filters( 'garry_sez_verify_request', true, wp_unslash( $_POST ) );
	if ( is_wp_error( $verified ) ) wp_send_json_error( array( 'message' => $verified->get_error_message() ), 400 );
	if ( ! $verified ) wp_send_json_error( array( 'message' => 'Ověření proti spamu se nepovedlo.' ), 400 );

	$z = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => 'Dárkový poukaz',
		'pokoj' => sanitize_text_field( wp_unslash( $_POST['druh'] ?? '' ) ),
		'jmeno' => trim( sanitize_text_field( wp_unslash( $_POST['jmeno'] ?? '' ) ) . ' ' . sanitize_text_field( wp_unslash( $_POST['prijmeni'] ?? '' ) ) ),
		'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'jazyk' => sanitize_key( $_POST['jazyk'] ?? '' ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( trim( $z['jmeno'] ) === '' || ! is_email( $z['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Vyplňte prosím jméno a platný e-mail.' ), 400 );
	}
	$log = get_option( GARRY_SEZ_LOG, array() ); if ( ! is_array( $log ) ) $log = array();
	$log[] = $z; if ( count( $log ) > 300 ) $log = array_slice( $log, -300 );
	update_option( GARRY_SEZ_LOG, $log, false );
	$to = garry_sez_get()['email']; if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	$body = "Nová objednávka dárkového poukazu z webu:\n\n"
		. 'Druh poukazu: ' . $z['pokoj'] . "\n" . 'Jméno: ' . $z['jmeno'] . "\n"
		. 'E-mail: ' . $z['email'] . "\n" . 'Jazyk webu: ' . strtoupper( $z['jazyk'] ) . "\n"
		. 'Čas: ' . $z['cas'] . "\n" . 'IP: ' . $z['ip'] . "\n";
	wp_mail( $to, 'Objednávka dárkového poukazu — ' . $z['pokoj'], $body, array( 'Reply-To: ' . $z['jmeno'] . ' <' . $z['email'] . '>' ) );
	wp_send_json_success();
}
add_action( 'wp_ajax_garry_voucher_request', 'garry_voucher_handle' );
add_action( 'wp_ajax_nopriv_garry_voucher_request', 'garry_voucher_handle' );

/* ------------------------------------------------------------------
 * Fluent Forms integrace — čekací list + dárkový poukaz
 * Mapa formulářů je v option grid_ff_forms (typ => jazyk => form_id).
 * - select „akce" se plní dynamicky z akcí pluginu (jen budoucí)
 * - odeslání se zapisuje do logu pluginu a posílá na e-mail z nastavení
 * ------------------------------------------------------------------ */
function garry_sez_ff_map() {
	return (array) get_option( 'grid_ff_forms', array() );
}
/* form_id => array( typ, jazyk ) | null */
function garry_sez_ff_lang_of( $form_id ) {
	foreach ( array( 'cekaci', 'voucher' ) as $typ ) {
		foreach ( (array) ( garry_sez_ff_map()[ $typ ] ?? array() ) as $lang => $id ) {
			if ( (int) $id === (int) $form_id ) return array( $typ, $lang );
		}
	}
	return null;
}
/* FF formulář pro aktuální jazyk (0 = FF není / není namapováno) */
function garry_sez_ff_id( $typ ) {
	if ( ! shortcode_exists( 'fluentform' ) ) return 0;
	$m = garry_sez_ff_map();
	return (int) ( $m[ $typ ][ garry_sez_lang() ] ?? ( $m[ $typ ]['cs'] ?? 0 ) );
}
/* dynamické možnosti selectu „akce" (jen budoucí akce, jazyk dle formuláře) */
add_filter( 'fluentform/rendering_field_data_select', function ( $data, $form ) {
	$hit = garry_sez_ff_lang_of( $form->id ?? 0 );
	if ( ! $hit || $hit[0] !== 'cekaci' ) return $data;
	if ( ( $data['attributes']['name'] ?? '' ) !== 'akce' ) return $data;
	$li = array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ $hit[1] ] ?? 0;
	$today = current_time( 'Y-m-d' );
	$opts = array();
	foreach ( garry_sez_get()['events'] as $e ) {
		if ( ( ( $e['do'] ?: $e['od'] ) ) < $today ) continue;
		$name = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
		if ( $name === '' ) continue;
		$opts[] = array(
			'label'      => $name . ' · ' . garry_sez_fmt_range( $e['od'], $e['do'] ),
			'value'      => $name,
			'calc_value' => '',
		);
	}
	if ( $opts ) $data['settings']['advanced_options'] = $opts;
	return $data;
}, 10, 2 );
/* odeslání FF → log pluginu + e-mail z nastavení */
add_action( 'fluentform/submission_inserted', function ( $entry_id, $form_data, $form ) {
	$hit = garry_sez_ff_lang_of( $form->id ?? 0 );
	if ( ! $hit ) return;
	list( $typ, $lang ) = $hit;
	$z = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => $typ === 'voucher' ? 'Dárkový poukaz' : sanitize_text_field( $form_data['akce'] ?? '' ),
		'pokoj' => sanitize_text_field( $typ === 'voucher' ? ( $form_data['druh'] ?? '' ) : ( $form_data['pokoj'] ?? '' ) ),
		'jmeno' => trim( sanitize_text_field( $form_data['jmeno'] ?? '' ) . ' ' . sanitize_text_field( $form_data['prijmeni'] ?? '' ) ),
		'email' => sanitize_email( $form_data['email'] ?? '' ),
		'jazyk' => sanitize_key( $lang ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( $z['jmeno'] === '' || ! is_email( $z['email'] ) ) return;
	$log = get_option( GARRY_SEZ_LOG, array() ); if ( ! is_array( $log ) ) $log = array();
	$log[] = $z; if ( count( $log ) > 300 ) $log = array_slice( $log, -300 );
	update_option( GARRY_SEZ_LOG, $log, false );
	$to = garry_sez_get()['email']; if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	if ( $typ === 'voucher' ) {
		$subject = 'Objednávka dárkového poukazu — ' . $z['pokoj'];
		$body = "Nová objednávka dárkového poukazu z webu (Fluent Forms):\n\n"
			. 'Druh poukazu: ' . $z['pokoj'] . "\n" . 'Jméno: ' . $z['jmeno'] . "\n"
			. 'E-mail: ' . $z['email'] . "\n" . 'Jazyk webu: ' . strtoupper( $z['jazyk'] ) . "\n"
			. 'Čas: ' . $z['cas'] . "\n" . 'IP: ' . $z['ip'] . "\n";
	} else {
		$subject = 'Poptávka z čekacího listu — ' . $z['akce'];
		$body = "Nová poptávka z webu (sekce Sezóna & čekací list, Fluent Forms):\n\n"
			. 'Akce / termín: ' . $z['akce'] . "\n" . 'Typ pokoje:    ' . $z['pokoj'] . "\n"
			. 'Jméno:         ' . $z['jmeno'] . "\n" . 'E-mail:        ' . $z['email'] . "\n"
			. 'Jazyk webu:    ' . strtoupper( $z['jazyk'] ) . "\n" . 'Čas:           ' . $z['cas'] . "\n"
			. 'IP:            ' . $z['ip'] . "\n";
	}
	wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $z['jmeno'] . ' <' . $z['email'] . '>' ) );
}, 10, 3 );

/* ---------- AJAX příjem poptávky (přihlášení i nepřihlášení) ---------- */
function garry_sez_handle_request() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'garry_sez_request' ) ) {
		wp_send_json_error( array( 'message' => 'Neplatný požadavek — obnovte prosím stránku.' ), 400 );
	}
	if ( ! empty( $_POST['web'] ) ) wp_send_json_success(); // honeypot — tiše zahodit

	/* Integrace anti-spamu (Google reCAPTCHA plugin apod.):
	 * add_filter( 'garry_sez_verify_request', fn( $ok, $post ) => ..., 10, 2 ); */
	$verified = apply_filters( 'garry_sez_verify_request', true, wp_unslash( $_POST ) );
	if ( is_wp_error( $verified ) ) wp_send_json_error( array( 'message' => $verified->get_error_message() ), 400 );
	if ( ! $verified ) wp_send_json_error( array( 'message' => 'Ověření proti spamu se nepovedlo.' ), 400 );

	$zaznam = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => sanitize_text_field( wp_unslash( $_POST['akce'] ?? '' ) ),
		'pokoj' => sanitize_text_field( wp_unslash( $_POST['pokoj'] ?? '' ) ),
		'jmeno' => sanitize_text_field( wp_unslash( $_POST['jmeno'] ?? '' ) ),
		'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'jazyk' => sanitize_key( $_POST['jazyk'] ?? '' ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( $zaznam['jmeno'] === '' || ! is_email( $zaznam['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Vyplňte prosím jméno a platný e-mail.' ), 400 );
	}

	/* log (posledních 300) */
	$log = get_option( GARRY_SEZ_LOG, array() ); if ( ! is_array( $log ) ) $log = array();
	$log[] = $zaznam;
	if ( count( $log ) > 300 ) $log = array_slice( $log, -300 );
	update_option( GARRY_SEZ_LOG, $log, false );

	/* e-mail */
	$to = garry_sez_get()['email'];
	if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	$subject = 'Poptávka z čekacího listu — ' . $zaznam['akce'];
	$body = "Nová poptávka z webu (sekce Sezóna & čekací list):\n\n"
		. 'Akce / termín: ' . $zaznam['akce'] . "\n"
		. 'Typ pokoje:    ' . $zaznam['pokoj'] . "\n"
		. 'Jméno:         ' . $zaznam['jmeno'] . "\n"
		. 'E-mail:        ' . $zaznam['email'] . "\n"
		. 'Jazyk webu:    ' . strtoupper( $zaznam['jazyk'] ) . "\n"
		. 'Čas:           ' . $zaznam['cas'] . "\n"
		. 'IP:            ' . $zaznam['ip'] . "\n";
	wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $zaznam['jmeno'] . ' <' . $zaznam['email'] . '>' ) );

	wp_send_json_success();
}
add_action( 'wp_ajax_garry_sez_request', 'garry_sez_handle_request' );
add_action( 'wp_ajax_nopriv_garry_sez_request', 'garry_sez_handle_request' );
