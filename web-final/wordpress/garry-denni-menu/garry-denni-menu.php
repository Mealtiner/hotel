<?php
/**
 * Plugin Name:       GARRY – Denní menu
 * Plugin URI:        https://www.garry.cz
 * Description:       Jednoduchá správa týdenního jídelníčku pro personál gastra: dny Po–Ne + celotýdenní nabídka, názvy jídel ve 3 jazycích (CZ/EN/DE), výběr kalendářního týdne. Na webu se vykreslí přes [grid_menu_tydne] jen vyplněné dny.
 * Version:           1.3.1
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-denni-menu
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
 * GARRY – Denní menu (týdenní jídelníček, CZ/EN/DE)
 * ============================================================================ */

define( 'GARRY_MENU_OPT', 'garry_menu' );

/* Dny: klíč => [CZ, EN, DE] */
function garry_menu_days() {
	return array(
		'po'    => array( 'Pondělí',             'Monday',        'Montag' ),
		'ut'    => array( 'Úterý',               'Tuesday',       'Dienstag' ),
		'st'    => array( 'Středa',              'Wednesday',     'Mittwoch' ),
		'ct'    => array( 'Čtvrtek',             'Thursday',      'Donnerstag' ),
		'pa'    => array( 'Pátek',               'Friday',        'Freitag' ),
		'so'    => array( 'Sobota',              'Saturday',      'Samstag' ),
		'ne'    => array( 'Neděle',              'Sunday',        'Sonntag' ),
		'tyden' => array( 'Celotýdenní nabídka', 'All-week menu', 'Wochenkarte' ),
	);
}
/* Typy chodů: klíč => [CZ, EN, DE] */
function garry_menu_types() {
	return array(
		'polevka' => array( 'Polévka',     'Soup',        'Suppe' ),
		'hlavni'  => array( 'Hlavní chod', 'Main course', 'Hauptgericht' ),
		'dezert'  => array( 'Dezert',      'Dessert',     'Dessert' ),
	);
}
/* Kategorie stálé nabídky: klíč => [CZ, EN, DE] (výchozí; vlastní lze přidat v adminu) */
function garry_menu_stala_default_typy() {
	return array(
		array( 'key' => 'predkrm', 'cz' => 'Předkrmy',    'en' => 'Starters',     'de' => 'Vorspeisen' ),
		array( 'key' => 'polevka', 'cz' => 'Polévky',     'en' => 'Soups',        'de' => 'Suppen' ),
		array( 'key' => 'hlavni',  'cz' => 'Hlavní chody','en' => 'Main courses', 'de' => 'Hauptgerichte' ),
		array( 'key' => 'priloha', 'cz' => 'Přílohy',     'en' => 'Side dishes',  'de' => 'Beilagen' ),
		array( 'key' => 'dezert',  'cz' => 'Dezerty',     'en' => 'Desserts',     'de' => 'Desserts' ),
	);
}
function garry_menu_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_menu_lang_idx() {
	$l = garry_menu_lang();
	return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 );
}
function garry_menu_get() {
	$o = get_option( GARRY_MENU_OPT, array() );
	if ( ! is_array( $o ) ) $o = array();
	$o = wp_parse_args( $o, array( 'week' => '', 'days' => array(), 'stala' => array(), 'typy' => array() ) );
	if ( empty( $o['typy'] ) ) $o['typy'] = garry_menu_stala_default_typy();
	return $o;
}
/* Týden z hodnoty: datum YYYY-MM-DD (kalendářní výběr — libovolný den v týdnu)
 * nebo legacy ISO 2026-W31 → [DateTime pondělí, DateTime neděle] */
function garry_menu_week_range( $week ) {
	$week = (string) $week;
	try {
		if ( preg_match( '/^(\d{4})-W(\d{2})$/', $week, $m ) ) {
			$po = new DateTime(); $po->setISODate( (int) $m[1], (int) $m[2] );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $week ) ) {
			$po = new DateTime( $week );
			$po->modify( 'monday this week' );
		} else {
			return null;
		}
		$ne = clone $po; $ne->modify( '+6 days' );
		return array( $po, $ne );
	} catch ( Exception $e ) { return null; }
}

Garry_Promotion_Registry::register( array(
	'slug' => 'garry-denni-menu', 'title' => 'Denní menu',
	'callback' => 'garry_menu_admin_page', 'plugin_file' => __FILE__,
	'dashicon' => 'dashicons-food', 'position' => 30,
) );

add_action( 'admin_init', function () { register_setting( 'garry_menu_group', GARRY_MENU_OPT, 'garry_menu_sanitize' ); } );

/* Druhý vstup: „Denní menu" i jako podpoložka menu „GRID Nastavení" (ACF options
 * page 'grid-options' — tam, kde personál spravuje ostatní obsah hotelu). */
add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return; // GRID Nastavení neexistuje
	add_submenu_page(
		'grid-options',
		'Jídelní lístek',
		'Jídelní lístek',
		'manage_options',
		'garry-denni-menu-grid',
		'garry_menu_admin_page'
	);
}, 100 );
function garry_menu_sanitize( $in ) {
	$out = array( 'week' => '', 'days' => array(), 'stala' => array(), 'typy' => array() );
	if ( ! is_array( $in ) ) return $out;
	$w = (string) ( $in['week'] ?? '' );
	$out['week'] = preg_match( '/^(\d{4}-\d{2}-\d{2}|\d{4}-W\d{2})$/', $w ) ? $w : '';
	$types = array_keys( garry_menu_types() );
	foreach ( array_keys( garry_menu_days() ) as $day ) {
		$rows = $in['days'][ $day ] ?? null;
		if ( ! is_array( $rows ) ) continue;
		$clean = array();
		// řádky přijdou jako pole sloupců typ[]/cz[]/en[]/de[]/cena[]
		$n = max( count( $rows['typ'] ?? array() ), count( $rows['cz'] ?? array() ) );
		for ( $i = 0; $i < $n; $i++ ) {
			$typ = sanitize_key( $rows['typ'][ $i ] ?? 'hlavni' );
			if ( ! in_array( $typ, $types, true ) ) $typ = 'hlavni';
			$row = array(
				'typ'  => $typ,
				'cz'   => sanitize_text_field( $rows['cz'][ $i ] ?? '' ),
				'en'   => sanitize_text_field( $rows['en'][ $i ] ?? '' ),
				'de'   => sanitize_text_field( $rows['de'][ $i ] ?? '' ),
				'cena' => sanitize_text_field( $rows['cena'][ $i ] ?? '' ),
			);
			if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue; // prázdný řádek
			$clean[] = $row;
		}
		if ( $clean ) $out['days'][ $day ] = $clean;
	}
	/* kategorie stálé nabídky (editovatelné, možno přidat vlastní) */
	$ty = $in['typy'] ?? array();
	$n = count( $ty['key'] ?? array() );
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $ty['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $ty['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$out['typy'][] = array(
			'key' => $key, 'cz' => $cz,
			'en'  => sanitize_text_field( $ty['en'][ $i ] ?? '' ),
			'de'  => sanitize_text_field( $ty['de'][ $i ] ?? '' ),
		);
	}
	if ( ! $out['typy'] ) $out['typy'] = garry_menu_stala_default_typy();
	$typ_keys = wp_list_pluck( $out['typy'], 'key' );
	/* položky stálé nabídky */
	$st = $in['stala'] ?? array();
	$n = count( $st['cz'] ?? array() );
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'typ'  => in_array( $st['typ'][ $i ] ?? '', $typ_keys, true ) ? $st['typ'][ $i ] : ( $typ_keys[0] ?? 'hlavni' ),
			'cz'   => sanitize_text_field( $st['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $st['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $st['de'][ $i ] ?? '' ),
			'cena' => sanitize_text_field( $st['cena'][ $i ] ?? '' ),
		);
		if ( $row['cz'] === '' && $row['en'] === '' && $row['de'] === '' ) continue;
		$out['stala'][] = $row;
	}
	return $out;
}

function garry_menu_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$s = garry_menu_get(); $DAYS = garry_menu_days(); $TYPES = garry_menu_types();
	$range = garry_menu_week_range( $s['week'] );
	?>
	<div class="wrap"><h1>Jídelní lístek — denní menu a stálá nabídka</h1>
	<p>Vyplňte jídla pro jednotlivé dny (CZ/EN/DE). Nevyplněný den se na webu nezobrazí.
	V základu je 1× polévka, 2× hlavní chod a 1× dezert — další řádky lze přidat tlačítkem.</p>
	<form method="post" action="options.php" id="garry-menu-form">
	<?php settings_fields( 'garry_menu_group' ); ?>
	<?php
	$week_val = $s['week'];
	if ( $range && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $week_val ) ) $week_val = $range[0]->format( 'Y-m-d' );
	?>
	<p style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
	  <label><strong>Platí pro týden:</strong>
	    <input type="date" id="gm-week" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[week]" value="<?php echo esc_attr( $week_val ); ?>">
	  </label>
	  <span class="description" id="gm-week-range"><?php if ( $range ) printf( 'týden %s – %s',
		esc_html( $range[0]->format( 'j. n. Y' ) ), esc_html( $range[1]->format( 'j. n. Y' ) ) );
		else echo 'vyberte v kalendáři libovolný den — týden Po–Ne se dopočítá sám'; ?></span>
	</p>
	<h2 class="nav-tab-wrapper" id="gm-tabs" style="margin-bottom:0">
	  <?php $first = true; foreach ( $DAYS as $key => $names ) :
		$filled = ! empty( $s['days'][ $key ] ); ?>
	    <a href="#" class="nav-tab <?php echo $first ? 'nav-tab-active' : ''; ?>" data-day="<?php echo esc_attr( $key ); ?>">
	      <?php echo esc_html( $names[0] ); ?><?php if ( $filled ) echo ' <span style="color:#2ecc71">●</span>'; ?>
	    </a>
	  <?php $first = false; endforeach; ?>
	  <a href="#" class="nav-tab" data-day="stala">Stálá nabídka<?php if ( ! empty( $s['stala'] ) ) echo ' <span style="color:#2ecc71">●</span>'; ?></a>
	</h2>
	<?php $first = true; foreach ( $DAYS as $key => $names ) :
		$rows = $s['days'][ $key ] ?? array();
		if ( ! $rows ) $rows = array(
			array( 'typ' => 'polevka', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'hlavni',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'hlavni',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
			array( 'typ' => 'dezert',  'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ),
		); ?>
	  <div class="gm-day" data-day="<?php echo esc_attr( $key ); ?>" style="<?php echo $first ? '' : 'display:none'; ?>;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
	    <table class="widefat striped gm-table" data-day="<?php echo esc_attr( $key ); ?>">
	      <thead><tr>
	        <th style="width:130px">Chod</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th>
	      </tr></thead>
	      <tbody>
	      <?php foreach ( $rows as $r ) : ?>
	        <tr>
	          <td><select name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][typ][]">
	            <?php foreach ( $TYPES as $tk => $tn ) printf( '<option value="%s" %s>%s</option>', esc_attr( $tk ), selected( $r['typ'], $tk, false ), esc_html( $tn[0] ) ); ?>
	          </select></td>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[days][<?php echo esc_attr( $key ); ?>][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="145 Kč"></td>
	          <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-del" title="Smazat řádek" style="color:#b32d2e">×</button></td>
	        </tr>
	      <?php endforeach; ?>
	      </tbody>
	    </table>
	    <p><button type="button" class="button gm-add" data-day="<?php echo esc_attr( $key ); ?>">+ Přidat řádek</button></p>
	  </div>
	<?php $first = false; endforeach; ?>
	<div class="gm-day" data-day="stala" style="display:none;background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px 18px">
	  <p class="description">Stálá nabídka (jídelní lístek) — na webu se vypisuje <strong>pod kartami denního menu</strong> ve 4 sloupcích, položky seskupené podle kategorií. Nevyplněný jazyk = použije se čeština, prázdná cena se nezobrazí.</p>
	  <h3 style="margin:14px 0 6px">Kategorie</h3>
	  <p class="description">Pořadí kategorií zde určuje pořadí na webu. Vlastní kategorii přidáte tlačítkem — klíč se doplní sám z českého názvu.</p>
	  <table class="widefat striped" id="gm-typy" style="max-width:860px">
	    <thead><tr><th style="width:130px">Klíč</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php foreach ( $s['typy'] as $ty ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[typy][key][]" value="<?php echo esc_attr( $ty['key'] ); ?>"></td>
	        <?php foreach ( array( 'cz', 'en', 'de' ) as $l ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[typy][<?php echo $l; ?>][]" value="<?php echo esc_attr( $ty[ $l ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-typ-del" title="Smazat kategorii" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-typ-add">+ Přidat kategorii</button></p>
	  <h3 style="margin:18px 0 6px">Položky</h3>
	  <table class="widefat striped" id="gm-stala">
	    <thead><tr><th style="width:160px">Kategorie</th><th>Česky</th><th>English</th><th>Deutsch</th><th style="width:90px">Cena</th><th style="width:96px"></th></tr></thead>
	    <tbody>
	    <?php $stala_rows = $s['stala'] ?: array( array( 'typ' => $s['typy'][0]['key'] ?? 'hlavni', 'cz' => '', 'en' => '', 'de' => '', 'cena' => '' ) );
	    foreach ( $stala_rows as $r ) : ?>
	      <tr>
	        <td><select style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][typ][]">
	          <?php foreach ( $s['typy'] as $ty ) printf( '<option value="%s" %s>%s</option>', esc_attr( $ty['key'] ), selected( $r['typ'], $ty['key'], false ), esc_html( $ty['cz'] ) ); ?>
	        </select></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][cz][]" value="<?php echo esc_attr( $r['cz'] ); ?>" placeholder="Název česky"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][en][]" value="<?php echo esc_attr( $r['en'] ); ?>" placeholder="English name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][de][]" value="<?php echo esc_attr( $r['de'] ); ?>" placeholder="Deutscher Name"></td>
	        <td><input type="text" style="width:100%" name="<?php echo esc_attr( GARRY_MENU_OPT ); ?>[stala][cena][]" value="<?php echo esc_attr( $r['cena'] ); ?>" placeholder="145 Kč"></td>
	        <td style="white-space:nowrap"><button type="button" class="button-link gm-up" title="Posunout výš">▲</button> <button type="button" class="button-link gm-down" title="Posunout níž">▼</button> <button type="button" class="button-link gm-stala-del" title="Smazat položku" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody>
	  </table>
	  <p><button type="button" class="button" id="gm-stala-add">+ Přidat položku</button></p>
	</div>
	<?php submit_button( 'Uložit menu' ); ?>
	<h2 style="margin-top:8px">Náhled na webu (česky)</h2>
	<p class="description">Živý náhled matice — zobrazují se jen dny s alespoň jedním vyplněným jídlem.</p>
	<div id="gm-preview" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;max-width:1400px;background:#F4F2F0;border:1px solid #c3c4c7;border-radius:8px;padding:16px"></div>
	</form></div>
	<script>
	(function(){
	  var wk=document.getElementById('gm-week'), wr=document.getElementById('gm-week-range');
	  if(wk&&wr){ wk.addEventListener('change', function(){
	    if(!wk.value) return;
	    var d=new Date(wk.value+'T12:00:00');
	    var day=(d.getDay()+6)%7;
	    var po=new Date(d); po.setDate(d.getDate()-day);
	    var ne=new Date(po); ne.setDate(po.getDate()+6);
	    var f=function(x){ return x.getDate()+'. '+(x.getMonth()+1)+'.'; };
	    wr.textContent='týden '+f(po)+' – '+f(ne)+' '+ne.getFullYear();
	  }); }
	  var tabs=document.querySelectorAll('#gm-tabs .nav-tab');
	  tabs.forEach(function(t){ t.addEventListener('click', function(e){
	    e.preventDefault();
	    tabs.forEach(function(x){ x.classList.remove('nav-tab-active'); });
	    t.classList.add('nav-tab-active');
	    document.querySelectorAll('.gm-day').forEach(function(d){ d.style.display = d.getAttribute('data-day')===t.getAttribute('data-day') ? '' : 'none'; });
	  }); });
	  document.querySelectorAll('.gm-add').forEach(function(btn){ btn.addEventListener('click', function(){
	    var tbl=document.querySelector('.gm-table[data-day="'+btn.getAttribute('data-day')+'"] tbody');
	    var tr=tbl.rows[tbl.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; });
	    tr.querySelector('select').value='hlavni';
	    tbl.appendChild(tr);
	  }); });
	  document.getElementById('gm-typ-add').addEventListener('click', function(){
	    var tb=document.querySelector('#gm-typy tbody'); var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; }); tb.appendChild(tr);
	  });
	  document.getElementById('gm-stala-add').addEventListener('click', function(){
	    var tb=document.querySelector('#gm-stala tbody'); var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input').forEach(function(i){ i.value=''; }); tb.appendChild(tr);
	  });
	  document.addEventListener('click', function(e){
	    if(e.target.classList.contains('gm-up')||e.target.classList.contains('gm-down')){
	      var mtr=e.target.closest('tr'), mtb=mtr.parentNode;
	      if(e.target.classList.contains('gm-up') && mtr.previousElementSibling) mtb.insertBefore(mtr, mtr.previousElementSibling);
	      if(e.target.classList.contains('gm-down') && mtr.nextElementSibling) mtb.insertBefore(mtr.nextElementSibling, mtr);
	      if(mtr.closest('.gm-table')) preview();
	      return;
	    }
	    if(e.target.classList.contains('gm-typ-del')||e.target.classList.contains('gm-stala-del')){
	      var tb2=e.target.closest('tbody');
	      if(tb2.rows.length>1) e.target.closest('tr').remove();
	      else tb2.querySelectorAll('input').forEach(function(i){ i.value=''; });
	      return;
	    }
	    if(!e.target.classList.contains('gm-del')) return;
	    var tb=e.target.closest('tbody');
	    if(tb.rows.length>1) e.target.closest('tr').remove();
	    else tb.querySelectorAll('input').forEach(function(i){ i.value=''; });
	    preview();
	  });

	  /* ---- živý náhled matice 4×2 (česky) ---- */
	  var DAYS=<?php echo wp_json_encode( array_map( function ( $n ) { return $n[0]; }, garry_menu_days() ) ); ?>;
	  var TYPES=<?php echo wp_json_encode( array_map( function ( $n ) { return $n[0]; }, garry_menu_types() ) ); ?>;
	  function preview(){
	    var box=document.getElementById('gm-preview'); if(!box) return;
	    var h='';
	    Object.keys(DAYS).forEach(function(day){
	      var tbl=document.querySelector('.gm-table[data-day="'+day+'"] tbody'); if(!tbl) return;
	      var groups={};
	      [].forEach.call(tbl.rows, function(tr){
	        var typ=tr.querySelector('select').value;
	        var cz=tr.querySelector('input[name*="[cz]"]').value.trim();
	        var cena=tr.querySelector('input[name*="[cena]"]').value.trim();
	        if(!cz) return;
	        (groups[typ]=groups[typ]||[]).push({n:cz,c:cena});
	      });
	      if(!Object.keys(groups).length) return;
	      var card='<div style="background:#fff;border:1px solid rgba(20,22,25,.12);border-radius:6px;padding:12px 14px">'+
	        '<div style="font-weight:700;color:#16181B;border-bottom:2px solid #C20E1A;padding-bottom:6px;margin-bottom:8px">'+DAYS[day]+'</div>';
	      Object.keys(TYPES).forEach(function(tk){
	        if(!groups[tk]) return;
	        card+='<div style="font-family:monospace;font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#8F8E90;margin:8px 0 3px">'+TYPES[tk]+'</div>';
	        groups[tk].forEach(function(it){
	          card+='<div style="display:flex;justify-content:space-between;gap:8px;font-size:12.5px;color:#16181B;padding:2px 0">'+
	            '<span>'+it.n+'</span>'+(it.c?'<b style="white-space:nowrap">'+it.c+'</b>':'')+'</div>';
	        });
	      });
	      h+=card+'</div>';
	    });
	    box.innerHTML=h||'<em style="color:#8F8E90">Zatím nic vyplněno — karta se na webu skryje.</em>';
	  }
	  document.addEventListener('input', function(e){ if(e.target.closest('.gm-day')) preview(); });
	  document.addEventListener('change', function(e){ if(e.target.closest('.gm-day')) preview(); });
	  preview();
	})();
	</script>
	<?php
}

/* ---------- Frontend: [grid_menu_tydne] — vykreslí jen vyplněné dny ---------- */
function garry_menu_render() {
	$s = garry_menu_get(); $DAYS = garry_menu_days(); $TYPES = garry_menu_types();
	$li = garry_menu_lang_idx();
	$filled = array();
	foreach ( $DAYS as $key => $names ) {
		if ( ! empty( $s['days'][ $key ] ) ) $filled[ $key ] = $s['days'][ $key ];
	}
	if ( ! $filled && empty( $s['stala'] ) ) {
		// nic vyplněno → skrýt celou sekci jídelníčku
		return '<style>#jidelnicek{display:none}</style>';
	}
	$weekline = '';
	$range = garry_menu_week_range( $s['week'] );
	if ( $range ) {
		$fmt = array( 'Platí pro týden %s – %s', 'Valid for the week of %s – %s', 'Gültig für die Woche %s – %s' );
		$weekline = sprintf( $fmt[ $li ], $range[0]->format( 'j. n.' ), $range[1]->format( 'j. n. Y' ) );
	}
	ob_start();
	if ( $filled ) {
	if ( $weekline ) echo '<p style="color:var(--muted);font-family:var(--f-mono);font-size:.82rem;margin-bottom:24px">' . esc_html( $weekline ) . '</p>';
	echo '<div class="menu-week menu-week--8">';
	foreach ( $filled as $key => $rows ) {
		echo '<div class="menu-day"><h3>' . esc_html( $DAYS[ $key ][ $li ] ) . '</h3>';
		foreach ( $TYPES as $tk => $tn ) {
			$items = array_values( array_filter( $rows, function ( $r ) use ( $tk ) { return ( $r['typ'] ?? '' ) === $tk; } ) );
			if ( ! $items ) continue;
			echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $tn[ $li ] ) . '</span>';
			foreach ( $items as $r ) {
				$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
				if ( $name === '' ) continue;
				echo '<div class="menu-item"><span class="menu-n">' . esc_html( $name ) . '</span>';
				if ( ! empty( $r['cena'] ) ) echo '<span class="menu-c">' . esc_html( $r['cena'] ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';
	}

	/* Stálá nabídka — jídelní lístek pod kartami, 4 vyrovnané sloupce */
	if ( ! empty( $s['stala'] ) ) {
		$groups = array();
		foreach ( $s['stala'] as $r ) {
			$name = $r[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $r['cz'];
			if ( $name === '' ) continue;
			$groups[ $r['typ'] ][] = array( $name, (string) ( $r['cena'] ?? '' ) );
		}
		if ( $groups ) {
			$KICK = array( 'Stálá nabídka', 'À la carte menu', 'Ständiges Angebot' );
			echo '<h3 class="menu-stala-h">' . esc_html( $KICK[ $li ] ) . '</h3>';
			echo '<div class="menu-stala">';
			foreach ( $s['typy'] as $ty ) {
				if ( empty( $groups[ $ty['key'] ] ) ) continue;
				$lbl = $ty[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $ty['cz'];
				echo '<div class="menu-grp"><span class="menu-grp-l">' . esc_html( $lbl ) . '</span>';
				foreach ( $groups[ $ty['key'] ] as $it ) {
					echo '<div class="menu-item"><span class="menu-n">' . esc_html( $it[0] ) . '</span>';
					if ( $it[1] !== '' ) echo '<span class="menu-c">' . esc_html( $it[1] ) . '</span>';
					echo '</div>';
				}
				echo '</div>';
			}
			echo '</div>';
		}
	}
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_menu_tydne', 'garry_menu_render' ); }, 5 );
