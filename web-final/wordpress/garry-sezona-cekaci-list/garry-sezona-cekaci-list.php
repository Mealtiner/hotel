<?php
/**
 * Plugin Name:       GARRY – Sezóna & čekací list
 * Plugin URI:        https://www.garry.cz
 * Description:       Správa akcí sezóny pro sekci T6: datum od–do, název a perex ve 3 jazycích (CZ/EN/DE), obsazenost pokojů (4 stavy s barvami). Web zobrazuje 5 nejbližších akcí přes [grid_season_events] včetně bočního formuláře čekacího listu.
 * Version:           1.0.0
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

        const FRAMEWORK_VERSION = '2.0.0';
        const MENU_SLUG         = 'garry-nastaveni';
        const INFO_SLUG         = 'garry-info';
        const CAPABILITY        = 'manage_options';
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
 * GARRY – Sezóna & čekací list
 * ============================================================================ */

define( 'GARRY_SEZ_OPT', 'garry_sezona' );

/* Mapa nových stavů na legacy třídy (grid.js waitbox rozlišuje free/few/full) */
function garry_sez_legacy_class( $stav ) {
	$m = array( 'volne' => 'free', 'posledni' => 'few', 'cekaci' => 'full', 'plne' => 'full' );
	return isset( $m[ $stav ] ) ? $m[ $stav ] : 'free';
}
/* Stavy obsazenosti: klíč => [CZ label, EN, DE, CZ CTA, EN CTA, DE CTA, výchozí barva] */
function garry_sez_states() {
	return array(
		'volne'    => array( 'Volné pokoje',   'Rooms available', 'Freie Zimmer',  'Rezervovat →', 'Book now →', 'Buchen →',    '#2ecc71' ),
		'posledni' => array( 'Poslední pokoje','Last rooms',      'Letzte Zimmer', 'Rezervovat →', 'Book now →', 'Buchen →',    '#caa75f' ),
		'cekaci'   => array( 'Čekací list',    'Waiting list',    'Warteliste',    'Zapsat se →',  'Sign up →',  'Eintragen →', '#FF5A50' ),
		'plne'     => array( 'Plně obsazeno',  'Fully booked',    'Ausgebucht',    'Zapsat se →',  'Sign up →',  'Eintragen →', '#8F8E90' ),
	);
}
function garry_sez_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_sez_lang_idx() { $l = garry_sez_lang(); return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 ); }

/* Výchozí akce (dokud personál neuloží vlastní) — aktuální program 2026 */
function garry_sez_default_events() {
	return array(
		array( 'od'=>'2026-04-17','do'=>'2026-04-19','cz'=>'Track Day Open','en'=>'Track Day Open','de'=>'Track Day Open',
			'pcz'=>'Volné jízdy pro veřejnost na okruhu','pen'=>'Open track sessions for the public','pde'=>'Freies Fahren für die Öffentlichkeit','stav'=>'volne' ),
		array( 'od'=>'2026-05-22','do'=>'2026-05-24','cz'=>'Endurance 8h Brno','en'=>'Endurance 8h Brno','de'=>'Endurance 8h Brno',
			'pcz'=>'Vytrvalostní závod — den i noc na trati','pen'=>'Endurance race — day and night on track','pde'=>'Langstreckenrennen — Tag und Nacht auf der Strecke','stav'=>'posledni' ),
		array( 'od'=>'2026-08-07','do'=>'2026-08-09','cz'=>'MotoGP víkend','en'=>'MotoGP weekend','de'=>'MotoGP-Wochenende',
			'pcz'=>'Hlavní událost sezóny — vrchol roku','pen'=>'The main event of the season','pde'=>'Das Hauptevent der Saison','stav'=>'cekaci' ),
		array( 'od'=>'2026-09-11','do'=>'2026-09-13','cz'=>'FIA WTCR','en'=>'FIA WTCR','de'=>'FIA WTCR',
			'pcz'=>'Cestovní vozy na Masarykově okruhu','pen'=>'Touring cars at the Masaryk Circuit','pde'=>'Tourenwagen am Masaryk-Ring','stav'=>'posledni' ),
		array( 'od'=>'2026-10-02','do'=>'2026-10-04','cz'=>'Classic & Historic','en'=>'Classic & Historic','de'=>'Classic & Historic',
			'pcz'=>'Přehlídka historických závodních strojů','pen'=>'A showcase of historic racing machines','pde'=>'Schau historischer Rennmaschinen','stav'=>'volne' ),
	);
}
function garry_sez_get() {
	$o = get_option( GARRY_SEZ_OPT, null );
	if ( ! is_array( $o ) ) $o = array( 'events' => garry_sez_default_events(), 'colors' => array() );
	$o = wp_parse_args( $o, array( 'events' => array(), 'colors' => array() ) );
	return $o;
}

Garry_Promotion_Registry::register( array(
	'slug' => 'garry-sezona-cekaci-list', 'title' => 'Sezóna & čekací list',
	'callback' => 'garry_sez_admin_page', 'plugin_file' => __FILE__,
	'dashicon' => 'dashicons-flag', 'position' => 25,
) );

add_action( 'admin_init', function () { register_setting( 'garry_sez_group', GARRY_SEZ_OPT, 'garry_sez_sanitize' ); } );
function garry_sez_sanitize( $in ) {
	$out = array( 'events' => array(), 'colors' => array() );
	if ( ! is_array( $in ) ) return $out;
	$states = array_keys( garry_sez_states() );
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
			'stav' => in_array( $e['stav'][ $i ] ?? '', $states, true ) ? $e['stav'][ $i ] : 'volne',
		);
		if ( $row['cz'] === '' && $row['od'] === '' ) continue;
		$out['events'][] = $row;
	}
	usort( $out['events'], function ( $a, $b ) { return strcmp( $a['od'], $b['od'] ); } );
	foreach ( $states as $s ) {
		$c = $in['colors'][ $s ] ?? '';
		$out['colors'][ $s ] = preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? $c : '';
	}
	return $out;
}

function garry_sez_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$s = garry_sez_get(); $STATES = garry_sez_states();
	$rows = $s['events'];
	if ( ! $rows ) $rows = array( array( 'od'=>'','do'=>'','cz'=>'','en'=>'','de'=>'','pcz'=>'','pen'=>'','pde'=>'','stav'=>'volne' ) );
	$O = GARRY_SEZ_OPT;
	?>
	<div class="wrap"><h1>Sezóna & čekací list</h1>
	<p>Akce sezóny pro sekci „Sezóna 2026" na webu. Zobrazuje se <strong>5 nejbližších</strong> akcí podle data konání
	(akce po skončení zmizí samy). Průběžně stačí přepínat <strong>obsazenost</strong> — barvy stavů lze upravit níže.</p>
	<form method="post" action="options.php">
	<?php settings_fields( 'garry_sez_group' ); ?>
	<table class="widefat striped" id="sez-table" style="max-width:1400px">
	  <thead><tr>
	    <th style="width:110px">Od</th><th style="width:110px">Do</th>
	    <th>Název CZ</th><th>Název EN</th><th>Název DE</th>
	    <th>Perex CZ</th><th>Perex EN</th><th>Perex DE</th>
	    <th style="width:150px">Obsazenost</th><th style="width:36px"></th>
	  </tr></thead><tbody>
	  <?php foreach ( $rows as $r ) : ?>
	    <tr>
	      <td><input type="date" name="<?php echo $O; ?>[events][od][]" value="<?php echo esc_attr( $r['od'] ); ?>"></td>
	      <td><input type="date" name="<?php echo $O; ?>[events][do][]" value="<?php echo esc_attr( $r['do'] ); ?>"></td>
	      <?php foreach ( array( 'cz','en','de','pcz','pen','pde' ) as $f ) : ?>
	        <td><input type="text" style="width:100%" name="<?php echo $O; ?>[events][<?php echo $f; ?>][]" value="<?php echo esc_attr( $r[ $f ] ); ?>"></td>
	      <?php endforeach; ?>
	      <td><select name="<?php echo $O; ?>[events][stav][]">
	        <?php foreach ( $STATES as $k => $st ) printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $r['stav'], $k, false ), esc_html( $st[0] ) ); ?>
	      </select></td>
	      <td><button type="button" class="button-link sez-del" style="color:#b32d2e" title="Smazat akci">×</button></td>
	    </tr>
	  <?php endforeach; ?>
	  </tbody></table>
	<p><button type="button" class="button" id="sez-add">+ Přidat akci</button></p>
	<h2>Barvy stavů obsazenosti</h2>
	<table class="form-table" style="max-width:560px"><tbody>
	  <?php foreach ( $STATES as $k => $st ) : $c = ( $s['colors'][ $k ] ?? '' ) ?: $st[6]; ?>
	    <tr><th><?php echo esc_html( $st[0] ); ?></th>
	    <td><input type="color" name="<?php echo $O; ?>[colors][<?php echo $k; ?>]" value="<?php echo esc_attr( $c ); ?>">
	    <span class="description">výchozí <?php echo esc_html( $st[6] ); ?></span></td></tr>
	  <?php endforeach; ?>
	</tbody></table>
	<?php submit_button( 'Uložit sezónu' ); ?>
	</form></div>
	<script>
	(function(){
	  var tb=document.querySelector('#sez-table tbody');
	  document.getElementById('sez-add').addEventListener('click', function(){
	    var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; });
	    tr.querySelector('select').value='volne';
	    tb.appendChild(tr);
	  });
	  document.addEventListener('click', function(e){
	    if(!e.target.classList.contains('sez-del')) return;
	    if(tb.rows.length>1) e.target.closest('tr').remove();
	    else tb.rows[0].querySelectorAll('input').forEach(function(i){ i.value=''; });
	  });
	})();
	</script>
	<?php
}

/* ---------- Frontend: [grid_season_events limit="5"] ---------- */
function garry_sez_fmt_range( $od, $do, $li ) {
	if ( ! $od ) return '';
	try { $a = new DateTime( $od ); } catch ( Exception $e ) { return ''; }
	$b = null;
	if ( $do ) { try { $b = new DateTime( $do ); } catch ( Exception $e ) { $b = null; } }
	if ( ! $b || $od === $do ) return $a->format( 'j. n. Y' );
	if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) return $a->format( 'j.' ) . '–' . $b->format( 'j. n. Y' );
	return $a->format( 'j. n.' ) . ' – ' . $b->format( 'j. n. Y' );
}
function garry_sez_render( $atts = array() ) {
	$a = shortcode_atts( array( 'limit' => 5 ), $atts );
	$s = garry_sez_get(); $STATES = garry_sez_states(); $li = garry_sez_lang_idx();
	$today = current_time( 'Y-m-d' );
	$events = array_values( array_filter( $s['events'], function ( $e ) use ( $today ) {
		$konec = $e['do'] ?: $e['od'];
		return $konec === '' || $konec >= $today;
	} ) );
	usort( $events, function ( $x, $y ) { return strcmp( $x['od'], $y['od'] ); } );
	if ( (int) $a['limit'] > 0 ) $events = array_slice( $events, 0, (int) $a['limit'] );
	if ( ! $events ) return '<style>#sezona{display:none}</style>';

	/* lokalizované texty bočního formuláře */
	$T = array(
		array( 'Rezervace &amp; čekací list', 'Vyberte akci sezóny', 'Klikněte na termín vlevo, nebo vyberte akci níže. U vyprodaných termínů vás zapíšeme na čekací list.',
			'Akce / termín', 'Typ pokoje', 'Jméno a příjmení', 'Jan Novák', 'E-mail', 'vas@email.cz', 'Zapsat na čekací list',
			'✓ Hotovo! Ozveme se, jakmile se pro vybraný termín uvolní pokoj.', '// Termíny sezóny jsou orientační.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terasa)', 'Apartmá' ) ),
		array( 'Booking &amp; waiting list', 'Choose your season event', "Click a date on the left or choose an event below. For sold-out dates we'll put you on the waiting list.",
			'Event / date', 'Room type', 'Full name', 'John Smith', 'E-mail', 'your@email.com', 'Join the waiting list',
			"✓ Done! We'll be in touch as soon as a room opens up for your chosen dates.", '// Season dates are indicative.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terrace)', 'Apartment' ) ),
		array( 'Buchung &amp; Warteliste', 'Wählen Sie ein Saison-Event', 'Klicken Sie links auf einen Termin oder wählen Sie unten ein Event. Bei ausverkauften Terminen tragen wir Sie in die Warteliste ein.',
			'Event / Termin', 'Zimmertyp', 'Vor- und Nachname', 'Max Mustermann', 'E-Mail', 'ihre@email.de', 'In die Warteliste eintragen',
			'✓ Fertig! Wir melden uns, sobald für den gewählten Termin ein Zimmer frei wird.', '// Die Saisontermine sind unverbindlich.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (Terrasse)', 'Appartement' ) ),
	);
	$t = $T[ $li ];

	/* barvy stavů (výchozí / z nastavení) */
	$css = '';
	foreach ( $STATES as $k => $st ) {
		$c = ( $s['colors'][ $k ] ?? '' ) ?: $st[6];
		$css .= '.ev-status.' . $k . '{color:' . $c . ' !important;border-color:' . $c . '99 !important}';
	}

	ob_start();
	echo '<style>' . $css . '</style>';
	?>
	<div class="season">
	  <div class="ev-list reveal d1 in" id="evList">
	    <?php foreach ( $events as $e ) :
			$name  = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
			$perex = $e[ array( 'pcz', 'pen', 'pde' )[ $li ] ] ?: $e['pcz'];
			$st = $STATES[ $e['stav'] ]; ?>
	    <button type="button" class="ev-row" data-ev="<?php echo esc_attr( $name ); ?>">
	      <span class="ev-date"><?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'], $li ) ); ?></span>
	      <span class="ev-name"><?php echo esc_html( $name ); ?><small><?php echo esc_html( $perex ); ?></small></span>
	      <span class="ev-meta"><span class="ev-status <?php echo esc_attr( $e['stav'] . ' ' . garry_sez_legacy_class( $e['stav'] ) ); ?>"><?php echo esc_html( $st[ $li ] ); ?></span>
	      <span class="ev-cta"><?php echo esc_html( $st[ 3 + $li ] ); ?></span></span>
	    </button>
	    <?php endforeach; ?>
	  </div>
	  <div class="waitbox reveal d2 in">
	    <span class="kicker"><?php echo $t[0]; ?></span>
	    <h3 id="wbTitle"><?php echo esc_html( $t[1] ); ?></h3>
	    <p class="wb-sub" id="wbSub"><?php echo esc_html( $t[2] ); ?></p>
	    <form id="wbForm" onsubmit="return false">
	      <div class="wb-field"><label for="wb-ev"><?php echo esc_html( $t[3] ); ?></label><select id="wb-ev">
	        <?php foreach ( $events as $e ) :
				$name = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz']; ?>
	        <option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?> · <?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'], $li ) ); ?></option>
	        <?php endforeach; ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-room"><?php echo esc_html( $t[4] ); ?></label><select id="wb-room">
	        <?php foreach ( $t[12] as $i => $room ) printf( '<option %s>%s</option>', $i === 1 ? 'selected' : '', esc_html( $room ) ); ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-name"><?php echo esc_html( $t[5] ); ?></label><input type="text" id="wb-name" placeholder="<?php echo esc_attr( $t[6] ); ?>"></div>
	      <div class="wb-field"><label for="wb-email"><?php echo esc_html( $t[7] ); ?></label><input type="email" id="wb-email" placeholder="<?php echo esc_attr( $t[8] ); ?>"></div>
	      <button type="submit" class="btn" style="width:100%;text-align:center" id="wbBtn"><?php echo esc_html( $t[9] ); ?></button>
	      <div class="wb-ok" id="wbOk"><?php echo esc_html( $t[10] ); ?></div>
	    </form>
	    <p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:14px"><?php echo esc_html( $t[11] ); ?></p>
	  </div>
	</div>
	<?php
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_season_events', 'garry_sez_render' ); }, 5 );
