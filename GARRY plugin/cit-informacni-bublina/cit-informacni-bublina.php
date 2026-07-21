<?php
/**
 * Plugin Name: GARRY - Informační bublina
 * Description: Zakázkový mikroplugin pro zobrazení informační bubliny na vybraných stránkách webu.
 * Version: 1.3.1
 * Author: GARRY Promotion
 * Author URI: https://garry.cz/
 * Text Domain: cit-informacni-bublina
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CIT_BUBBLE_OPTION', 'cit_bubble_settings');
define('CIT_BUBBLE_VERSION', '1.3.1');
define('CIT_BUBBLE_PUBLISHED', '27. 5. 2026');

define('CIT_BUBBLE_SWITCH_ON_COLOR', '#68C020');
define('CIT_BUBBLE_SWITCH_OFF_COLOR', '#E02B20');




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


/**
 * Výchozí stránky podle původního zadání.
 */
function cit_bubble_default_page_ids() {
    $default_slugs = array(
        'cit',
        'psychiatricka-pece',
        'psychologicka-pece',
    );

    $ids = array();

    foreach ($default_slugs as $slug) {
        $page = get_page_by_path($slug);

        if ($page && !is_wp_error($page) && $page->post_status === 'publish') {
            $ids[] = (int) $page->ID;
        }
    }

    return array_values(array_unique($ids));
}

/**
 * Dostupné fonty v administraci.
 */
function cit_bubble_font_choices() {
    return array(
        'nagel' => 'Nagel / Arial',
        'inherit' => 'Výchozí font webu',
        'system' => 'Systémový font',
        'arial' => 'Arial',
        'georgia' => 'Georgia',
    );
}

/**
 * Převedení uložené volby fontu na CSS hodnotu.
 */
function cit_bubble_css_font_value($font_key) {
    $map = array(
        'nagel' => '"Nagel", "nagel", Arial, sans-serif',
        'inherit' => 'inherit',
        'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'arial' => 'Arial, sans-serif',
        'georgia' => 'Georgia, serif',
    );

    return isset($map[$font_key]) ? $map[$font_key] : $map['inherit'];
}

/**
 * Výchozí nastavení informační bubliny.
 */
function cit_bubble_defaults() {
    return array(
        'enabled' => '1',

        'image_url' => '/wp-content/uploads/2025/08/bublina_jarni-zelena.svg',

        /**
         * Vzhled bubliny:
         * image = použít SVG obrázek na pozadí
         * frame = klasický rámeček / okno bez obrázku
         */
        'background_mode' => 'image',

        'page_ids' => cit_bubble_default_page_ids(),

        /**
         * Vynucené zarovnání na střed obrazovky.
         * 0 = používat nastavené pozice X/Y
         * 1 = ignorovat X/Y a zobrazit střed-střed
         */
        'center_mode' => '0',

        'position_x' => '20vw',
        'position_y' => '16vh',
        'mobile_position_x' => '14px',
        'mobile_position_y' => '92px',

        'start_delay_ms' => 250,
        'auto_close_delay_ms' => 10000,

        'title' => 'Důležité upozornění',
        'text' => 'Kapacity psychologické a psychiatrické ambulance jsou aktuálně plné.<br><br>V tuto chvíli proto nepřijímáme žádné nové klienty, a to ani do pořadníku.<br><br>Děkujeme za pochopení.',

        'title_color' => '#003f00',
        'text_color' => '#222222',

        'title_font' => 'nagel',
        'text_font' => 'inherit',

        'title_size_desktop' => 'clamp(22px, 2vw, 34px)',
        'title_size_mobile' => '20px',
        'text_size_desktop' => 'clamp(14px, 0.95vw, 17px)',
        'text_size_mobile' => '13px',

        'bubble_size_desktop' => 'clamp(320px, 34vw, 560px)',
        'bubble_size_mobile' => 'min(92vw, 430px)',

        'close_top_desktop' => '22%',
        'close_right_desktop' => '12%',
        'close_size_desktop' => '34px',

        'close_top_mobile' => '20%',
        'close_right_mobile' => '11%',
        'close_size_mobile' => '30px',

        /**
         * Animace zobrazení:
         * slide = příjezd z levého horního rohu
         * fade  = klasické fade-in / fade-out
         */
        'animation_mode' => 'slide',

        'display_mode' => 'always',
        'notice_version' => 'v3',
    );
}

/**
 * Načtení nastavení se sloučením s výchozími hodnotami.
 */
function cit_bubble_get_settings() {
    $saved = get_option(CIT_BUBBLE_OPTION, array());

    if (!is_array($saved)) {
        $saved = array();
    }

    $settings = array_merge(cit_bubble_defaults(), $saved);

    /**
     * Migrace výchozí barvy běžného textu z původní hodnoty na aktuální doporučený výchozí stav.
     * Pokud byla barva ručně změněna na jinou hodnotu, zůstane zachovaná.
     */
    if (!isset($saved['text_color']) || strtolower((string) $saved['text_color']) === '#102b00') {
        $settings['text_color'] = '#222222';
    }

    if (!isset($settings['page_ids']) || !is_array($settings['page_ids'])) {
        $settings['page_ids'] = array();
    }

    $settings['page_ids'] = array_values(array_unique(array_filter(array_map('absint', $settings['page_ids']))));

    return $settings;
}

/**
 * Aktivace pluginu.
 */
function cit_bubble_activate() {
    if (get_option(CIT_BUBBLE_OPTION, null) === null) {
        add_option(CIT_BUBBLE_OPTION, cit_bubble_defaults(), '', false);
    }
}
register_activation_hook(__FILE__, 'cit_bubble_activate');

/**
 * Bezpečná CSS hodnota pro velikosti, pozice, min(), max(), clamp() a calc().
 */
function cit_bubble_sanitize_css_value($value, $fallback) {
    $value = trim((string) $value);

    if ($value === '') {
        return $fallback;
    }

    if (strlen($value) > 90) {
        return $fallback;
    }

    /**
     * Zakázat znaky, které by mohly rozbít CSS deklaraci.
     */
    if (preg_match('/[;{}<>]/', $value)) {
        return $fallback;
    }

    /**
     * Povolit běžné CSS jednotky, čísla a jednoduché funkce typu clamp(), min(), max(), calc().
     */
    if (preg_match('/^[0-9a-zA-Z\s\.\,\-\+\*\/\(\)%]+$/', $value)) {
        return $value;
    }

    return $fallback;
}

/**
 * Sanitizace URL SVG obrázku.
 */
function cit_bubble_sanitize_svg_url($value, $fallback) {
    $value = trim((string) $value);
    $value = esc_url_raw($value);

    if ($value === '') {
        return $fallback;
    }

    $path = wp_parse_url($value, PHP_URL_PATH);
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

    if ($extension !== 'svg') {
        add_settings_error(
            CIT_BUBBLE_OPTION,
            'cit_bubble_svg_only',
            'Obrázek bubliny musí být SVG soubor. Původní hodnota byla zachována.',
            'error'
        );

        return $fallback;
    }

    return $value;
}

/**
 * Sanitizace nastavení.
 */
function cit_bubble_sanitize_settings($input) {
    $defaults = cit_bubble_defaults();
    $current = cit_bubble_get_settings();

    if (!is_array($input)) {
        return $current;
    }

    $output = array();

    $output['enabled'] = !empty($input['enabled']) ? '1' : '0';
    $output['center_mode'] = !empty($input['center_mode']) ? '1' : '0';

    $output['image_url'] = cit_bubble_sanitize_svg_url(
        isset($input['image_url']) ? $input['image_url'] : '',
        isset($current['image_url']) ? $current['image_url'] : $defaults['image_url']
    );

    $output['page_ids'] = array();

    if (!empty($input['page_ids']) && is_array($input['page_ids'])) {
        foreach ($input['page_ids'] as $page_id) {
            $page_id = absint($page_id);

            if ($page_id > 0 && get_post_status($page_id) === 'publish') {
                $output['page_ids'][] = $page_id;
            }
        }
    }

    $output['page_ids'] = array_values(array_unique($output['page_ids']));

    foreach (array(
        'position_x',
        'position_y',
        'mobile_position_x',
        'mobile_position_y',
        'title_size_desktop',
        'title_size_mobile',
        'text_size_desktop',
        'text_size_mobile',
        'bubble_size_desktop',
        'bubble_size_mobile',
        'close_top_desktop',
        'close_right_desktop',
        'close_size_desktop',
        'close_top_mobile',
        'close_right_mobile',
        'close_size_mobile',
    ) as $css_key) {
        $output[$css_key] = cit_bubble_sanitize_css_value(
            isset($input[$css_key]) ? $input[$css_key] : '',
            $defaults[$css_key]
        );
    }

    $output['start_delay_ms'] = isset($input['start_delay_ms']) ? absint($input['start_delay_ms']) : $defaults['start_delay_ms'];
    $output['auto_close_delay_ms'] = isset($input['auto_close_delay_ms']) ? absint($input['auto_close_delay_ms']) : $defaults['auto_close_delay_ms'];

    if ($output['auto_close_delay_ms'] < 1000) {
        $output['auto_close_delay_ms'] = $defaults['auto_close_delay_ms'];
    }

    $output['title'] = isset($input['title']) ? sanitize_text_field($input['title']) : $defaults['title'];

    $allowed_html = array(
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
        'span' => array(
            'class' => array(),
        ),
    );

    $output['text'] = isset($input['text']) ? wp_kses($input['text'], $allowed_html) : $defaults['text'];

    $title_color = isset($input['title_color']) ? sanitize_hex_color($input['title_color']) : '';
    $text_color = isset($input['text_color']) ? sanitize_hex_color($input['text_color']) : '';

    $output['title_color'] = $title_color ? $title_color : $defaults['title_color'];
    $output['text_color'] = $text_color ? $text_color : $defaults['text_color'];

    $font_choices = array_keys(cit_bubble_font_choices());

    $output['title_font'] = isset($input['title_font']) && in_array($input['title_font'], $font_choices, true)
        ? $input['title_font']
        : $defaults['title_font'];

    $output['text_font'] = isset($input['text_font']) && in_array($input['text_font'], $font_choices, true)
        ? $input['text_font']
        : $defaults['text_font'];

    $allowed_background_modes = array('image', 'frame');
    $output['background_mode'] = isset($input['background_mode']) && in_array($input['background_mode'], $allowed_background_modes, true)
        ? $input['background_mode']
        : $defaults['background_mode'];

    $allowed_animation_modes = array('slide', 'fade');
    $output['animation_mode'] = isset($input['animation_mode']) && in_array($input['animation_mode'], $allowed_animation_modes, true)
        ? $input['animation_mode']
        : $defaults['animation_mode'];

    $allowed_modes = array('once', 'always');
    $output['display_mode'] = isset($input['display_mode']) && in_array($input['display_mode'], $allowed_modes, true)
        ? $input['display_mode']
        : $defaults['display_mode'];

    $notice_version = isset($input['notice_version']) ? sanitize_text_field($input['notice_version']) : $defaults['notice_version'];
    $notice_version = preg_replace('/[^a-zA-Z0-9_\-]/', '', $notice_version);
    $output['notice_version'] = $notice_version !== '' ? $notice_version : $defaults['notice_version'];

    return $output;
}

/**
 * Registrace nastavení.
 */
function cit_bubble_register_settings() {
    register_setting(
        'cit_bubble_settings_group',
        CIT_BUBBLE_OPTION,
        array(
            'type' => 'array',
            'sanitize_callback' => 'cit_bubble_sanitize_settings',
            'default' => cit_bubble_defaults(),
        )
    );
}
add_action('admin_init', 'cit_bubble_register_settings');

/**
 * Menu v administraci.
 */
/**
 * Registrace pluginu ve sdíleném GARRY frameworku.
 * Probíhá na úrovni načtení pluginu, tedy před tím, než framework
 * sestavuje hlavní menu v admin_menu hooku.
 */
Garry_Promotion_Registry::register(array(
    'slug'        => 'cit-informacni-bublina',
    'title'       => 'Informační bublina',
    'callback'    => 'cit_bubble_render_admin_page',
    'plugin_file' => __FILE__,
    'dashicon'    => 'dashicons-format-status',
    'position'    => 20,
));

/**
 * Přidání HEX barvy do palety.
 */
function cit_bubble_add_color(&$colors, $hex, $label = 'Barva') {
    $hex = sanitize_hex_color($hex);

    if (!$hex) {
        return;
    }

    $key = strtolower($hex);

    if (!isset($colors[$key])) {
        $colors[$key] = $label . ' ' . strtoupper($hex);
    }
}

/**
 * Rekurzivní sběr HEX barev.
 */
function cit_bubble_collect_hex_colors($value, &$colors, $label = 'Divi barva') {
    if (is_string($value)) {
        if (preg_match_all('/#[0-9a-fA-F]{6}\b/', $value, $matches)) {
            foreach ($matches[0] as $hex) {
                cit_bubble_add_color($colors, $hex, $label);
            }
        }

        return;
    }

    if (is_array($value) || is_object($value)) {
        foreach ((array) $value as $sub_value) {
            cit_bubble_collect_hex_colors($sub_value, $colors, $label);
        }
    }
}

/**
 * Pokus o načtení Divi barev.
 */
function cit_bubble_get_divi_colors() {
    $colors = array();

    $option_names = array(
        'et_divi',
        'et_pb_color_palette',
        'et_pb_global_colors',
        'et_global_colors',
        'et_divi_global_colors',
        'et_divi_design_variables',
    );

    foreach ($option_names as $option_name) {
        $option_value = get_option($option_name);

        if ($option_value !== false && $option_value !== null && $option_value !== '') {
            cit_bubble_collect_hex_colors($option_value, $colors, 'Divi barva');
        }
    }

    cit_bubble_add_color($colors, '#003f00', 'CIT nadpis');
    cit_bubble_add_color($colors, '#222222', 'CIT text');
    cit_bubble_add_color($colors, CIT_BUBBLE_SWITCH_ON_COLOR, 'Přepínač zapnuto');
    cit_bubble_add_color($colors, CIT_BUBBLE_SWITCH_OFF_COLOR, 'Přepínač vypnuto');
    cit_bubble_add_color($colors, '#ffffff', 'Bílá');

    return $colors;
}

/**
 * Vykreslení palety barev.
 */
function cit_bubble_render_color_control($option_name, $field_name, $label, $description, $value, $colors) {
    $target_id = 'cit-bubble-' . str_replace('_', '-', $field_name);
    ?>
    <div class="cit-bubble-inner-card cit-bubble-color-control">
        <h3><?php echo esc_html($label); ?></h3>

        <div class="cit-bubble-selected-color">
            <span class="cit-bubble-selected-color__swatch" data-preview-for="#<?php echo esc_attr($target_id); ?>" style="background: <?php echo esc_attr($value); ?>"></span>
            <div>
                <strong>Aktuálně vybraná barva</strong>
                <div class="cit-bubble-selected-color__value" data-value-for="#<?php echo esc_attr($target_id); ?>"><?php echo esc_html(strtoupper($value)); ?></div>
            </div>
        </div>

        <input
            id="<?php echo esc_attr($target_id); ?>"
            type="text"
            class="cit-bubble-color-input"
            name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field_name); ?>]"
            value="<?php echo esc_attr($value); ?>"
            placeholder="#003f00"
        >

        <p class="description"><?php echo esc_html($description); ?></p>

        <div class="cit-bubble-color-row">
            <?php foreach ($colors as $hex => $color_label) : ?>
                <button
                    type="button"
                    class="cit-bubble-color-preset"
                    data-target="#<?php echo esc_attr($target_id); ?>"
                    data-color="<?php echo esc_attr($hex); ?>"
                    title="<?php echo esc_attr($color_label); ?>"
                >
                    <span class="cit-bubble-color-swatch" style="background: <?php echo esc_attr($hex); ?>"></span>
                    <span><?php echo esc_html(strtoupper($hex)); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Admin skripty a styly.
 */
function cit_bubble_admin_assets($hook) {
    if (!in_array($hook, array('toplevel_page_cit-informacni-bublina', 'garry-nastaveni_page_cit-informacni-bublina'), true)) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('jquery');

    $js = <<<JS
(function($) {
    var citBubbleFontMap = {
        nagel: '"Nagel", "nagel", Arial, sans-serif',
        inherit: 'inherit',
        system: 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        arial: 'Arial, sans-serif',
        georgia: 'Georgia, serif'
    };

    function updateSelectedColor(input) {
        var inputEl = $(input);
        var value = inputEl.val();
        var target = '#' + inputEl.attr('id');

        $('[data-preview-for="' + target + '"]').css('background', value);
        $('[data-value-for="' + target + '"]').text(String(value).toUpperCase());

        $('.cit-bubble-color-preset[data-target="' + target + '"]').removeClass('is-selected');
        $('.cit-bubble-color-preset[data-target="' + target + '"][data-color="' + String(value).toLowerCase() + '"]').addClass('is-selected');
        $('.cit-bubble-color-preset[data-target="' + target + '"][data-color="' + String(value).toUpperCase() + '"]').addClass('is-selected');
    }

    function adminPreviewFont(key) {
        return citBubbleFontMap[key] || citBubbleFontMap.inherit;
    }

    function normalizePreviewHtml(value) {
        return String(value || '')
            .replace(/<script[\\s\\S]*?>[\\s\\S]*?<\\/script>/gi, '')
            .replace(/<iframe[\\s\\S]*?>[\\s\\S]*?<\\/iframe>/gi, '');
    }

    function showAdminBubblePreview() {
        $('.cit-capacity-bubble-admin-preview').remove();

        var title = $('#cit-bubble-title').val() || '';
        var text = normalizePreviewHtml($('#cit-bubble-text').val() || '');
        var imageUrl = $('#cit-bubble-image-url').val() || '';
        var backgroundMode = $('input[name$="[background_mode]"]:checked').val() || 'image';
        var animationMode = $('input[name$="[animation_mode]"]:checked').val() || 'slide';
        var centerMode = $('input[name$="[center_mode]"]').is(':checked');

        var bubbleSize = $('#cit-bubble-bubble-size-desktop').val() || 'clamp(320px, 34vw, 560px)';
        var positionX = $('#cit-bubble-position-x').val() || '20vw';
        var positionY = $('#cit-bubble-position-y').val() || '16vh';
        var closeTop = $('#cit-bubble-close-top-desktop').val() || '22%';
        var closeRight = $('#cit-bubble-close-right-desktop').val() || '12%';
        var closeSize = $('#cit-bubble-close-size-desktop').val() || '34px';

        var titleColor = $('#cit-bubble-title-color').val() || '#003f00';
        var textColor = $('#cit-bubble-text-color').val() || '#222222';
        var titleFont = adminPreviewFont($('#cit-bubble-title-font').val());
        var textFont = adminPreviewFont($('#cit-bubble-text-font').val());
        var titleSize = $('#cit-bubble-title-size-desktop').val() || 'clamp(22px, 2vw, 34px)';
        var textSize = $('#cit-bubble-text-size-desktop').val() || 'clamp(14px, 0.95vw, 17px)';

        var bubble = $(
            '<div class="cit-capacity-bubble-admin-preview" aria-hidden="true">' +
                '<button type="button" class="cit-capacity-bubble-admin-preview__close" aria-label="Zavřít náhled">×</button>' +
                '<div class="cit-capacity-bubble-admin-preview__content">' +
                    '<div class="cit-capacity-bubble-admin-preview__title"></div>' +
                    '<div class="cit-capacity-bubble-admin-preview__text"></div>' +
                '</div>' +
            '</div>'
        );

        bubble
            .toggleClass('cit-capacity-bubble-admin-preview--frame', backgroundMode === 'frame')
            .toggleClass('cit-capacity-bubble-admin-preview--fade', animationMode === 'fade')
            .toggleClass('cit-capacity-bubble-admin-preview--slide', animationMode !== 'fade')
            .toggleClass('cit-capacity-bubble-admin-preview--center', centerMode);

        bubble.css({
            '--cit-admin-preview-final-x': positionX,
            '--cit-admin-preview-final-y': positionY,
            '--cit-admin-preview-width': bubbleSize,
            '--cit-admin-preview-close-top': closeTop,
            '--cit-admin-preview-close-right': closeRight,
            '--cit-admin-preview-close-size': closeSize,
            backgroundImage: backgroundMode === 'image' && imageUrl ? 'url("' + String(imageUrl).replace(/"/g, '%22') + '")' : 'none'
        });

        bubble.find('.cit-capacity-bubble-admin-preview__title')
            .text(title)
            .css({
                color: titleColor,
                fontFamily: titleFont,
                fontSize: titleSize
            });

        bubble.find('.cit-capacity-bubble-admin-preview__text')
            .html(text)
            .css({
                color: textColor,
                fontFamily: textFont,
                fontSize: textSize
            });

        $('body').append(bubble);

        bubble.find('.cit-capacity-bubble-admin-preview__close').on('click', function() {
            bubble.removeClass('is-visible').addClass('is-hiding');
            window.setTimeout(function() {
                bubble.remove();
            }, 900);
        });

        window.setTimeout(function() {
            bubble.attr('aria-hidden', 'false').addClass('is-visible');
        }, 30);
    }

    function updateCurrentStateText() {
        var enabled = $('.cit-bubble-switch input[type="checkbox"]').is(':checked');
        var pages = [];

        $('.cit-bubble-page-checkbox:checked').each(function() {
            var label = $(this).data('pageLabel');

            if (label) {
                pages.push(label);
            }
        });

        var pageText = pages.length ? pages.join(', ') : 'není vybrána žádná stránka';

        $('#cit-bubble-current-state-label').text(
            enabled
                ? 'Zobrazuje se na stránkách:'
                : 'Nezobrazuje se na webu. Po zapnutí se bude zobrazovat na stránkách:'
        );

        $('#cit-bubble-current-pages').text(pageText);
    }

    $(function() {
        var frame;

        $('.cit-bubble-color-input').each(function() {
            updateSelectedColor(this);
        });

        updateCurrentStateText();

        $('#cit-bubble-select-image').on('click', function(e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Vyber SVG obrázek bubliny',
                button: {
                    text: 'Použít tento SVG soubor'
                },
                library: {
                    type: 'image/svg+xml'
                },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();

                if (!attachment || !attachment.url) {
                    return;
                }

                if (!attachment.url.toLowerCase().match(/\\.svg($|\\?)/)) {
                    alert('Vyber prosím pouze SVG soubor.');
                    return;
                }

                $('#cit-bubble-image-url').val(attachment.url).trigger('input');
            });

            frame.open();
        });

        $('.cit-bubble-color-preset').on('click', function(e) {
            e.preventDefault();

            var target = $(this).data('target');
            var color = $(this).data('color');

            if (target && color) {
                $(target).val(color).trigger('input');
            }
        });

        $('.cit-bubble-color-input').on('input change', function() {
            updateSelectedColor(this);
        });

        $('.cit-bubble-switch input[type="checkbox"], .cit-bubble-page-checkbox').on('change', function() {
            updateCurrentStateText();
        });

        $('#cit-bubble-show-admin-preview').on('click', function(e) {
            e.preventDefault();
            showAdminBubblePreview();
        });
    });
})(jQuery);
JS;

    wp_add_inline_script('jquery', $js);

    $on = CIT_BUBBLE_SWITCH_ON_COLOR;
    $off = CIT_BUBBLE_SWITCH_OFF_COLOR;

    $css = <<<CSS
:root {
    --cit-admin-green: #003f00;
    --cit-admin-green-soft: #eff8ec;
    --cit-admin-border: #dcdcde;
    --cit-admin-muted: #646970;
    --cit-switch-on: {$on};
    --cit-switch-off: {$off};
}

.cit-bubble-admin-wrap {
    max-width: 1540px;
}

.cit-bubble-admin-wrap h1 {
    margin-bottom: 10px;
}

.cit-bubble-version-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--cit-admin-green-soft);
    color: var(--cit-admin-green);
    border: 1px solid rgba(0,63,0,.18);
    border-radius: 999px;
    padding: 5px 12px;
    font-weight: 700;
    margin: 4px 0 14px;
}

.cit-bubble-admin-card {
    background: #fff;
    border: 1px solid var(--cit-admin-border);
    border-radius: 14px;
    padding: 22px 24px;
    margin: 18px 0;
    box-shadow: 0 1px 2px rgba(0,0,0,.035);
}

.cit-bubble-admin-card h2 {
    margin: 0 0 14px;
    font-size: 20px;
    line-height: 1.25;
}

.cit-bubble-admin-card h3 {
    margin: 0 0 12px;
    font-size: 15px;
    line-height: 1.3;
}

.cit-bubble-admin-help {
    color: var(--cit-admin-muted);
    max-width: 880px;
    font-size: 14px;
    line-height: 1.65;
}

.cit-bubble-intro {
    border-left: 5px solid var(--cit-admin-green);
}

.cit-bubble-intro p {
    max-width: 930px;
    font-size: 14px;
    line-height: 1.7;
}

.cit-bubble-form-grid {
    display: grid;
    grid-template-columns: minmax(180px, 260px) 1fr;
    gap: 14px 24px;
    align-items: start;
}

.cit-bubble-form-grid > label {
    font-weight: 700;
    padding-top: 6px;
}

.cit-bubble-form-grid input[type="text"],
.cit-bubble-form-grid input[type="number"],
.cit-bubble-form-grid textarea,
.cit-bubble-form-grid select {
    width: 100%;
    max-width: 640px;
}

.cit-bubble-form-grid textarea {
    min-height: 132px;
}

.cit-bubble-inner-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.cit-bubble-three-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.cit-bubble-inner-card {
    background: #fbfbfb;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    padding: 16px;
}

.cit-bubble-inner-card .description {
    color: var(--cit-admin-muted);
}

.cit-bubble-toggle-row {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.cit-bubble-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 250px;
    height: 58px;
    border-radius: 999px;
    padding: 5px;
    background: var(--cit-switch-off);
    box-shadow: inset 0 0 0 2px rgba(0,0,0,.08);
    cursor: pointer;
    transition: background-color .2s ease, box-shadow .2s ease;
}

.cit-bubble-switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.cit-bubble-switch__knob {
    position: absolute;
    left: 6px;
    width: 46px;
    height: 46px;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.22);
    transition: transform .22s ease;
}

.cit-bubble-switch__text {
    width: 100%;
    text-align: center;
    color: #fff;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
    font-size: 13px;
    padding-left: 36px;
    padding-right: 8px;
}

.cit-bubble-switch:has(input:checked) {
    background: var(--cit-switch-on);
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__knob {
    transform: translateX(190px);
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__text {
    padding-left: 8px;
    padding-right: 42px;
}

.cit-bubble-toggle-state {
    background: #f6f7f7;
    border: 1px solid var(--cit-admin-border);
    border-radius: 12px;
    padding: 12px 14px;
    min-width: 260px;
}

.cit-bubble-toggle-state strong {
    display: block;
    margin-bottom: 4px;
}

.cit-bubble-fixed-colors {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.cit-bubble-fixed-color {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid var(--cit-admin-border);
    border-radius: 999px;
    padding: 6px 11px;
    font-weight: 700;
}

.cit-bubble-pages {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 10px;
    padding: 12px;
    border: 1px solid var(--cit-admin-border);
    border-radius: 12px;
    background: #f6f7f7;
}

.cit-bubble-page-item {
    display: grid;
    grid-template-columns: 24px 1fr;
    gap: 8px;
    align-items: start;
    padding: 10px;
    background: #fff;
    border: 1px solid #ececec;
    border-radius: 10px;
}

.cit-bubble-page-title {
    display: block;
    font-weight: 800;
    color: #1d2327;
}

.cit-bubble-page-slug-label {
    display: inline-block;
    margin-top: 4px;
    margin-right: 4px;
    color: var(--cit-admin-muted);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.cit-bubble-page-slug {
    display: inline-block;
    margin-top: 4px;
    color: var(--cit-admin-green);
    background: var(--cit-admin-green-soft);
    border-radius: 999px;
    padding: 2px 7px;
    font-family: Consolas, Monaco, monospace;
    font-size: 12px;
}

.cit-bubble-selected-color {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    border: 1px solid var(--cit-admin-border);
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: inset 0 0 0 1px rgba(0,0,0,.015);
}

.cit-bubble-selected-color__swatch {
    width: 46px;
    height: 46px;
    border-radius: 999px;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(0,0,0,.22), 0 4px 10px rgba(0,0,0,.08);
    flex: 0 0 auto;
}

.cit-bubble-selected-color__value {
    margin-top: 3px;
    font-family: Consolas, Monaco, monospace;
    font-weight: 800;
    color: #1d2327;
}

.cit-bubble-color-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.cit-bubble-color-preset {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--cit-admin-border);
    border-radius: 999px;
    padding: 5px 10px;
    background: #fff;
    cursor: pointer;
    text-decoration: none;
    color: #1d2327;
}

.cit-bubble-color-preset.is-selected {
    border-color: var(--cit-admin-green);
    box-shadow: 0 0 0 2px rgba(0,63,0,.14);
    font-weight: 800;
}

.cit-bubble-color-swatch {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,.2);
    display: inline-block;
}

.cit-bubble-author-box {
    font-size: 13px;
    color: #50575e;
    border-left: 4px solid var(--cit-admin-green);
    padding-left: 12px;
}

.cit-bubble-license-box {
    background: #f6f7f7;
    border: 1px solid var(--cit-admin-border);
    border-radius: 10px;
    padding: 12px;
    margin: 14px 0;
}

.cit-bubble-version-list {
    margin-left: 18px;
}

.cit-bubble-version-list li {
    margin-bottom: 5px;
}


.cit-bubble-admin-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 20px;
    align-items: start;
}

.cit-bubble-admin-main {
    min-width: 0;
}

.cit-bubble-preview-panel {
    position: sticky;
    top: 42px;
    min-width: 0;
}

.cit-bubble-preview-card {
    background: #fff;
    border: 1px solid var(--cit-admin-border);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,.035);
}

.cit-bubble-preview-card h2 {
    margin: 0 0 10px;
    font-size: 19px;
}

.cit-bubble-preview-button {
    width: 100%;
    justify-content: center;
    text-align: center;
    margin-top: 14px !important;
    min-height: 42px;
    font-weight: 700;
}

.cit-bubble-preview-note {
    margin-top: 12px;
}

.cit-capacity-bubble-admin-preview {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    z-index: 999999 !important;

    width: var(--cit-admin-preview-width) !important;
    aspect-ratio: 1 / 1 !important;

    background-repeat: no-repeat !important;
    background-position: center center !important;
    background-size: contain !important;

    transform: translate(-120%, -120%) !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;

    transition:
        transform 0.85s cubic-bezier(.22,.9,.28,1),
        opacity 0.3s ease,
        visibility 0.3s ease !important;
}

.cit-capacity-bubble-admin-preview.is-visible {
    transform: translate(var(--cit-admin-preview-final-x), var(--cit-admin-preview-final-y)) !important;
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

.cit-capacity-bubble-admin-preview.is-hiding {
    transform: translate(-120%, -120%) !important;
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

.cit-capacity-bubble-admin-preview--fade {
    transform: translate(var(--cit-admin-preview-final-x), var(--cit-admin-preview-final-y)) scale(.98) !important;
}

.cit-capacity-bubble-admin-preview--fade.is-visible {
    transform: translate(var(--cit-admin-preview-final-x), var(--cit-admin-preview-final-y)) scale(1) !important;
}

.cit-capacity-bubble-admin-preview--fade.is-hiding {
    transform: translate(var(--cit-admin-preview-final-x), var(--cit-admin-preview-final-y)) scale(.98) !important;
}

.cit-capacity-bubble-admin-preview--center {
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) scale(.98) !important;
}

.cit-capacity-bubble-admin-preview--center.is-visible {
    transform: translate(-50%, -50%) scale(1) !important;
}

.cit-capacity-bubble-admin-preview--center.is-hiding {
    transform: translate(-50%, -50%) scale(.98) !important;
}

.cit-capacity-bubble-admin-preview--frame {
    aspect-ratio: auto !important;
    min-height: 220px !important;
    max-width: min(92vw, 520px) !important;
    background: #ffffff !important;
    border: 1px solid rgba(0, 63, 0, 0.18) !important;
    border-radius: 20px !important;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18) !important;
}

.cit-capacity-bubble-admin-preview--frame .cit-capacity-bubble-admin-preview__content {
    position: static !important;
    transform: none !important;
    width: auto !important;
    max-width: none !important;
    padding: 42px 44px 38px !important;
}

.cit-capacity-bubble-admin-preview--frame .cit-capacity-bubble-admin-preview__close {
    top: 14px !important;
    right: 14px !important;
}

.cit-capacity-bubble-admin-preview__content {
    position: absolute !important;
    z-index: 2 !important;

    left: 50% !important;
    top: 52% !important;
    transform: translate(-50%, -50%) !important;

    width: 46% !important;
    max-width: 300px !important;

    box-sizing: border-box !important;
    text-align: left !important;
}

.cit-capacity-bubble-admin-preview__title {
    margin: 0 0 18px 0 !important;
    line-height: 1.05 !important;
    font-weight: 700 !important;
    letter-spacing: 0.02em !important;
    text-transform: uppercase !important;
}

.cit-capacity-bubble-admin-preview__text {
    line-height: 1.45 !important;
    font-weight: 500 !important;
}

.cit-capacity-bubble-admin-preview__close {
    position: absolute !important;
    top: var(--cit-admin-preview-close-top) !important;
    right: var(--cit-admin-preview-close-right) !important;
    z-index: 3 !important;

    width: var(--cit-admin-preview-close-size) !important;
    height: var(--cit-admin-preview-close-size) !important;
    border: 1px solid rgba(0, 63, 0, 0.55) !important;
    border-radius: 999px !important;

    display: flex !important;
    align-items: center !important;
    justify-content: center !important;

    background: rgba(0, 63, 0, 0.14) !important;
    color: #003f00 !important;

    font-size: calc(var(--cit-admin-preview-close-size) * 0.74) !important;
    line-height: 1 !important;
    font-weight: 300 !important;
    cursor: pointer !important;
    padding: 0 !important;
}

.cit-capacity-bubble-admin-preview__close:hover,
.cit-capacity-bubble-admin-preview__close:focus-visible {
    background: #003f00 !important;
    color: #ffffff !important;
    border-color: #003f00 !important;
}

.cit-bubble-content-stack {
    display: grid;
    gap: 22px;
}

.cit-bubble-content-main {
    display: grid;
    grid-template-columns: minmax(140px, 210px) 1fr;
    gap: 14px 24px;
    align-items: start;
}

.cit-bubble-content-main > label {
    font-weight: 700;
    padding-top: 6px;
}

.cit-bubble-content-main input[type="text"],
.cit-bubble-content-main textarea {
    width: 100%;
    max-width: none;
}

.cit-bubble-content-main textarea {
    min-height: 150px;
}

.cit-bubble-typography-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.cit-bubble-typography-field {
    background: #fbfbfb;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    padding: 14px;
}

.cit-bubble-typography-field label {
    display: block;
    font-weight: 700;
    margin-bottom: 8px;
}

.cit-bubble-typography-field input,
.cit-bubble-typography-field select {
    width: 100%;
}

.cit-bubble-switch {
    width: 270px;
}

.cit-bubble-switch__text {
    font-size: 12px;
    text-transform: none;
    padding-left: 42px;
}

.cit-bubble-switch__text-on {
    display: none;
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__knob {
    transform: translateX(210px);
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__text {
    padding-left: 8px;
    padding-right: 42px;
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__text-on {
    display: inline;
}

.cit-bubble-switch:has(input:checked) .cit-bubble-switch__text-off {
    display: none;
}

.cit-bubble-toggle-state {
    min-width: 360px;
    max-width: 760px;
    line-height: 1.55;
}

.cit-bubble-toggle-state__pages {
    margin-top: 6px;
    color: #1d2327;
    font-weight: 600;
}

@media (max-width: 1200px) {
    .cit-bubble-admin-layout {
        grid-template-columns: 1fr;
    }

    .cit-bubble-preview-panel {
        position: static;
    }
}

@media (max-width: 900px) {
    .cit-bubble-form-grid,
    .cit-bubble-inner-grid,
    .cit-bubble-three-grid,
    .cit-bubble-typography-grid,
    .cit-bubble-content-main {
        grid-template-columns: 1fr;
    }

    .cit-bubble-switch {
        width: 220px;
    }

    .cit-bubble-switch:has(input:checked) .cit-bubble-switch__knob {
        transform: translateX(160px);
    }
}
CSS;

    wp_register_style('cit-bubble-admin-style', false, array(), CIT_BUBBLE_VERSION);
    wp_enqueue_style('cit-bubble-admin-style');
    wp_add_inline_style('cit-bubble-admin-style', $css);
}
add_action('admin_enqueue_scripts', 'cit_bubble_admin_assets');


/**
 * Textový seznam stránek vybraných pro zobrazení bubliny.
 */
function cit_bubble_selected_pages_label($page_ids) {
    if (empty($page_ids) || !is_array($page_ids)) {
        return 'není vybrána žádná stránka';
    }

    $labels = array();

    foreach ($page_ids as $page_id) {
        $page_id = absint($page_id);
        $page = get_post($page_id);

        if (!$page || $page->post_status !== 'publish') {
            continue;
        }

        $labels[] = get_the_title($page) . ' (/' . $page->post_name . '/)';
    }

    if (empty($labels)) {
        return 'není vybrána žádná stránka';
    }

    return implode(', ', $labels);
}

/**
 * Živý náhled bubliny v pravém sloupci administrace.
 * Náhled ukazuje pouze grafiku bubliny a textový obsah, neřeší reálnou pozici na stránce ani animace.
 */
function cit_bubble_render_live_preview($settings) {
    ?>
    <aside class="cit-bubble-preview-panel">
        <div class="cit-bubble-preview-card">
            <h2>Živý náhled bubliny</h2>
            <p class="cit-bubble-admin-help">
                Kliknutím na tlačítko se bublina zobrazí přímo na této administrační stránce podle aktuálně vyplněných hodnot.
                Nastavení není potřeba před náhledem ukládat.
            </p>

            <button type="button" class="button button-primary cit-bubble-preview-button" id="cit-bubble-show-admin-preview">
                Zobrazit náhled bubliny
            </button>

            <p class="description cit-bubble-preview-note">
                Náhled slouží jen pro kontrolu vzhledu. Na webu se bublina bude zobrazovat podle uloženého nastavení a vybraných stránek.
            </p>
        </div>
    </aside>
    <?php
}

/**
 * Vykreslení administrační stránky.
 */
function cit_bubble_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = cit_bubble_get_settings();
    $option_name = CIT_BUBBLE_OPTION;

    $pages = get_pages(array(
        'sort_column' => 'post_title',
        'sort_order' => 'ASC',
        'post_status' => 'publish',
    ));

    $divi_colors = cit_bubble_get_divi_colors();
    $font_choices = cit_bubble_font_choices();

    ?>
    <div class="wrap cit-bubble-admin-wrap">
        <h1>Informační bublina</h1>

        <div class="cit-bubble-version-pill">
            Aktuálně načtená verze pluginu v administraci: <?php echo esc_html(CIT_BUBBLE_VERSION); ?>
        </div>

        <div class="cit-bubble-admin-card cit-bubble-intro">
            <h2>Informační bublina pro web Centra inovativní terapie</h2>

            <p>
                Tento zakázkově vyvinutý plugin slouží pro potřeby <strong>Centra inovativní terapie Kliniky Podané ruce</strong>.
                Umožňuje správci webu jednoduše zobrazit návštěvníkům vybraných stránek krátké, dobře viditelné a snadno upravitelné oznámení.
            </p>

            <p>
                Plugin lze využít například pro informace o <strong>aktuální kapacitě služeb, změnách provozu, důležitých novinkách,
                organizačních sděleních</strong> nebo dalších situacích, kdy je potřeba upozornění zvýraznit přímo na webu.
            </p>

            <p>
                Po načtení stránky bublina vyjede z rohu, zobrazí nastavený nadpis a text, návštěvník ji může zavřít křížkem
                a podle zvoleného časování se může automaticky zasunout. Níže lze upravit obsah, cílové stránky, pozadí, barvy,
                typografii, velikost, pozici i způsob opakovaného zobrazování.
            </p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('cit_bubble_settings_group'); ?>

            <div class="cit-bubble-admin-layout">
                <div class="cit-bubble-admin-main">

            <div class="cit-bubble-admin-card">
                <h2>Zapnutí a vypnutí</h2>

                <div class="cit-bubble-toggle-row">
                    <label class="cit-bubble-switch">
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($option_name); ?>[enabled]"
                            value="1"
                            <?php checked($settings['enabled'], '1'); ?>
                        >
                        <span class="cit-bubble-switch__knob"></span>
                        <span class="cit-bubble-switch__text">
                            <span class="cit-bubble-switch__text-on">zobrazuje se na webu</span>
                            <span class="cit-bubble-switch__text-off">nezobrazuje se na webu</span>
                        </span>
                    </label>

                    <div class="cit-bubble-toggle-state">
                        <strong>Aktuální stav</strong>
                        <span id="cit-bubble-current-state-label">
                            <?php echo $settings['enabled'] === '1' ? 'Zobrazuje se na stránkách:' : 'Nezobrazuje se na webu. Po zapnutí se bude zobrazovat na stránkách:'; ?>
                        </span>
                        <div id="cit-bubble-current-pages" class="cit-bubble-toggle-state__pages">
                            <?php echo esc_html(cit_bubble_selected_pages_label($settings['page_ids'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Režim zobrazování a verze oznámení</h2>

                <div class="cit-bubble-form-grid">
                    <label for="cit-bubble-display-mode">Režim zobrazování</label>
                    <div>
                        <select id="cit-bubble-display-mode" name="<?php echo esc_attr($option_name); ?>[display_mode]">
                            <option value="once" <?php selected($settings['display_mode'], 'once'); ?>>Zobrazit jen jednou pro danou stránku</option>
                            <option value="always" <?php selected($settings['display_mode'], 'always'); ?>>Zobrazit při každém načtení / reloadu stránky</option>
                        </select>
                        <p class="description">
                            Režim „jen jednou“ používá localStorage v prohlížeči návštěvníka. Režim „při každém načtení“ bublinu zobrazí opakovaně.
                        </p>
                    </div>

                    <label for="cit-bubble-notice-version">Verze oznámení</label>
                    <div>
                        <input
                            id="cit-bubble-notice-version"
                            type="text"
                            name="<?php echo esc_attr($option_name); ?>[notice_version]"
                            value="<?php echo esc_attr($settings['notice_version']); ?>"
                            placeholder="v3"
                        >
                        <p class="description">
                            Používá se hlavně v režimu „jen jednou“. Zvýšením verze například z v3 na v4 se oznámení znovu zobrazí i lidem, kteří ho už viděli.
                        </p>
                    </div>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Obsah bubliny</h2>

                <div class="cit-bubble-content-stack">
                    <div>
                        <h3>Textový obsah</h3>

                        <div class="cit-bubble-content-main">
                            <label for="cit-bubble-title">Nadpis</label>
                            <div>
                                <input
                                    id="cit-bubble-title"
                                    type="text"
                                    name="<?php echo esc_attr($option_name); ?>[title]"
                                    value="<?php echo esc_attr($settings['title']); ?>"
                                >
                                <p class="description">Krátký nadpis uvnitř bubliny.</p>
                            </div>

                            <label for="cit-bubble-text">Text</label>
                            <div>
                                <textarea
                                    id="cit-bubble-text"
                                    name="<?php echo esc_attr($option_name); ?>[text]"
                                    rows="7"
                                ><?php echo esc_textarea($settings['text']); ?></textarea>
                                <p class="description">
                                    Pro nový odstavec použij <code>&lt;br&gt;&lt;br&gt;</code>. Povolené jsou také jednoduché značky jako <code>&lt;strong&gt;</code> nebo <code>&lt;em&gt;</code>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3>Typografie</h3>

                        <div class="cit-bubble-typography-grid">
                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-title-font">Font nadpisu</label>
                                <select id="cit-bubble-title-font" name="<?php echo esc_attr($option_name); ?>[title_font]">
                                    <?php foreach ($font_choices as $font_key => $font_label) : ?>
                                        <option value="<?php echo esc_attr($font_key); ?>" <?php selected($settings['title_font'], $font_key); ?>>
                                            <?php echo esc_html($font_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-title-size-desktop">Velikost nadpisu – desktop</label>
                                <input
                                    id="cit-bubble-title-size-desktop"
                                    type="text"
                                    name="<?php echo esc_attr($option_name); ?>[title_size_desktop]"
                                    value="<?php echo esc_attr($settings['title_size_desktop']); ?>"
                                >
                                <p class="description">Například <code>34px</code> nebo <code>clamp(22px, 2vw, 34px)</code>.</p>
                            </div>

                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-title-size-mobile">Velikost nadpisu – mobil</label>
                                <input
                                    id="cit-bubble-title-size-mobile"
                                    type="text"
                                    name="<?php echo esc_attr($option_name); ?>[title_size_mobile]"
                                    value="<?php echo esc_attr($settings['title_size_mobile']); ?>"
                                >
                            </div>

                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-text-font">Font textu</label>
                                <select id="cit-bubble-text-font" name="<?php echo esc_attr($option_name); ?>[text_font]">
                                    <?php foreach ($font_choices as $font_key => $font_label) : ?>
                                        <option value="<?php echo esc_attr($font_key); ?>" <?php selected($settings['text_font'], $font_key); ?>>
                                            <?php echo esc_html($font_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-text-size-desktop">Velikost textu – desktop</label>
                                <input
                                    id="cit-bubble-text-size-desktop"
                                    type="text"
                                    name="<?php echo esc_attr($option_name); ?>[text_size_desktop]"
                                    value="<?php echo esc_attr($settings['text_size_desktop']); ?>"
                                >
                            </div>

                            <div class="cit-bubble-typography-field">
                                <label for="cit-bubble-text-size-mobile">Velikost textu – mobil</label>
                                <input
                                    id="cit-bubble-text-size-mobile"
                                    type="text"
                                    name="<?php echo esc_attr($option_name); ?>[text_size_mobile]"
                                    value="<?php echo esc_attr($settings['text_size_mobile']); ?>"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Kde se má bublina zobrazovat</h2>
                <p class="cit-bubble-admin-help">
                    Vyber publikované stránky. Plugin vybírá podle ID stránky, takže nastavení zůstane zachované i při pozdější změně URL slugu.
                </p>

                <div class="cit-bubble-pages">
                    <?php foreach ($pages as $page) : ?>
                        <label class="cit-bubble-page-item">
                            <input
                                type="checkbox"
                                class="cit-bubble-page-checkbox"
                                name="<?php echo esc_attr($option_name); ?>[page_ids][]"
                                value="<?php echo esc_attr($page->ID); ?>"
                                data-page-label="<?php echo esc_attr($page->post_title . ' (/' . $page->post_name . '/)'); ?>"
                                <?php checked(in_array((int) $page->ID, $settings['page_ids'], true)); ?>
                            >
                            <span>
                                <span class="cit-bubble-page-title"><?php echo esc_html($page->post_title); ?></span>
                                <span class="cit-bubble-page-slug-label">URL slug</span>
                                <span class="cit-bubble-page-slug">/<?php echo esc_html($page->post_name); ?>/</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Pozadí bubliny</h2>

                <div class="cit-bubble-form-grid">
                    <label>Typ pozadí</label>
                    <div>
                        <label>
                            <input
                                type="radio"
                                name="<?php echo esc_attr($option_name); ?>[background_mode]"
                                value="image"
                                <?php checked($settings['background_mode'], 'image'); ?>
                            >
                            Použít SVG obrázek na pozadí
                        </label>
                        <br>
                        <label>
                            <input
                                type="radio"
                                name="<?php echo esc_attr($option_name); ?>[background_mode]"
                                value="frame"
                                <?php checked($settings['background_mode'], 'frame'); ?>
                            >
                            Použít klasický rámeček / okno bez obrázku
                        </label>
                        <p class="description">
                            Klasický rámeček má bílé pozadí, jemný zelený okraj, zaoblené rohy 20 px a křížek v pravém horním rohu.
                        </p>
                    </div>

                    <label for="cit-bubble-image-url">SVG obrázek</label>
                    <div>
                        <input
                            id="cit-bubble-image-url"
                            type="text"
                            name="<?php echo esc_attr($option_name); ?>[image_url]"
                            value="<?php echo esc_attr($settings['image_url']); ?>"
                            placeholder="/wp-content/uploads/..."
                        >
                        <p>
                            <button type="button" class="button" id="cit-bubble-select-image">Vybrat SVG z médií</button>
                        </p>
                        <p class="description">
                            Plugin povoluje pouze SVG soubor. Pokud WordPress na webu nepovoluje nahrávání SVG, je potřeba použít způsob nahrávání SVG, který už na webu používáte.
                        </p>
                    </div>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Barvy textů</h2>
                <p class="cit-bubble-admin-help">
                    Níže je možné nastavit barvu nadpisu a běžného textu. Plugin se pokusí nabídnout barvy dostupné ve webu / Divi, zároveň lze zadat vlastní HEX hodnotu.
                </p>

                <div class="cit-bubble-inner-grid">
                    <?php
                    cit_bubble_render_color_control(
                        $option_name,
                        'title_color',
                        'Barva nadpisu',
                        'Výchozí doporučená hodnota: #003f00.',
                        $settings['title_color'],
                        $divi_colors
                    );

                    cit_bubble_render_color_control(
                        $option_name,
                        'text_color',
                        'Barva textu',
                        'Výchozí doporučená hodnota: #222222.',
                        $settings['text_color'],
                        $divi_colors
                    );
                    ?>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Pozice a časování</h2>

                <div class="cit-bubble-form-grid" style="margin-bottom: 16px;">
                    <label>Zarovnání na střed</label>
                    <div>
                        <label>
                            <input
                                type="checkbox"
                                name="<?php echo esc_attr($option_name); ?>[center_mode]"
                                value="1"
                                <?php checked($settings['center_mode'], '1'); ?>
                            >
                            Zobrazit bublinu uprostřed obrazovky a ignorovat pozice X/Y
                        </label>
                        <p class="description">
                            Platí pro SVG obrázek i klasický rámeček. Časování a zvolená forma animace zůstávají zachované.
                        </p>
                    </div>

                    <label>Forma zobrazení</label>
                    <div>
                        <label>
                            <input
                                type="radio"
                                name="<?php echo esc_attr($option_name); ?>[animation_mode]"
                                value="slide"
                                <?php checked($settings['animation_mode'], 'slide'); ?>
                            >
                            Příjezd z levého horního rohu
                        </label>
                        <br>
                        <label>
                            <input
                                type="radio"
                                name="<?php echo esc_attr($option_name); ?>[animation_mode]"
                                value="fade"
                                <?php checked($settings['animation_mode'], 'fade'); ?>
                            >
                            Klasické fade-in / fade-out
                        </label>
                    </div>
                </div>

                <div class="cit-bubble-three-grid">
                    <div class="cit-bubble-inner-card">
                        <h3>Čas</h3>

                        <p>
                            <label for="cit-bubble-start-delay"><strong>Spuštění po načtení</strong></label><br>
                            <input
                                id="cit-bubble-start-delay"
                                type="number"
                                min="0"
                                step="50"
                                name="<?php echo esc_attr($option_name); ?>[start_delay_ms]"
                                value="<?php echo esc_attr((int) $settings['start_delay_ms']); ?>"
                            >
                            <span class="description">ms</span>
                        </p>

                        <p>
                            <label for="cit-bubble-auto-close"><strong>Automatické zavření</strong></label><br>
                            <input
                                id="cit-bubble-auto-close"
                                type="number"
                                min="1000"
                                step="500"
                                name="<?php echo esc_attr($option_name); ?>[auto_close_delay_ms]"
                                value="<?php echo esc_attr((int) $settings['auto_close_delay_ms']); ?>"
                            >
                            <span class="description">ms</span>
                        </p>
                    </div>

                    <div class="cit-bubble-inner-card">
                        <h3>Pozice desktop</h3>

                        <p>
                            <label for="cit-bubble-position-x"><strong>Pozice X</strong></label><br>
                            <input
                                id="cit-bubble-position-x"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[position_x]"
                                value="<?php echo esc_attr($settings['position_x']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-position-y"><strong>Pozice Y</strong></label><br>
                            <input
                                id="cit-bubble-position-y"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[position_y]"
                                value="<?php echo esc_attr($settings['position_y']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-bubble-size-desktop"><strong>Velikost obrázku / bubliny</strong></label><br>
                            <input
                                id="cit-bubble-bubble-size-desktop"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[bubble_size_desktop]"
                                value="<?php echo esc_attr($settings['bubble_size_desktop']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-top-desktop"><strong>Křížek – shora</strong></label><br>
                            <input
                                id="cit-bubble-close-top-desktop"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_top_desktop]"
                                value="<?php echo esc_attr($settings['close_top_desktop']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-right-desktop"><strong>Křížek – zprava</strong></label><br>
                            <input
                                id="cit-bubble-close-right-desktop"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_right_desktop]"
                                value="<?php echo esc_attr($settings['close_right_desktop']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-size-desktop"><strong>Velikost křížku</strong></label><br>
                            <input
                                id="cit-bubble-close-size-desktop"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_size_desktop]"
                                value="<?php echo esc_attr($settings['close_size_desktop']); ?>"
                            >
                        </p>
                    </div>

                    <div class="cit-bubble-inner-card">
                        <h3>Pozice mobil</h3>

                        <p>
                            <label for="cit-bubble-mobile-position-x"><strong>Pozice X</strong></label><br>
                            <input
                                id="cit-bubble-mobile-position-x"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[mobile_position_x]"
                                value="<?php echo esc_attr($settings['mobile_position_x']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-mobile-position-y"><strong>Pozice Y</strong></label><br>
                            <input
                                id="cit-bubble-mobile-position-y"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[mobile_position_y]"
                                value="<?php echo esc_attr($settings['mobile_position_y']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-bubble-size-mobile"><strong>Velikost obrázku / bubliny</strong></label><br>
                            <input
                                id="cit-bubble-bubble-size-mobile"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[bubble_size_mobile]"
                                value="<?php echo esc_attr($settings['bubble_size_mobile']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-top-mobile"><strong>Křížek – shora</strong></label><br>
                            <input
                                id="cit-bubble-close-top-mobile"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_top_mobile]"
                                value="<?php echo esc_attr($settings['close_top_mobile']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-right-mobile"><strong>Křížek – zprava</strong></label><br>
                            <input
                                id="cit-bubble-close-right-mobile"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_right_mobile]"
                                value="<?php echo esc_attr($settings['close_right_mobile']); ?>"
                            >
                        </p>

                        <p>
                            <label for="cit-bubble-close-size-mobile"><strong>Velikost křížku</strong></label><br>
                            <input
                                id="cit-bubble-close-size-mobile"
                                type="text"
                                name="<?php echo esc_attr($option_name); ?>[close_size_mobile]"
                                value="<?php echo esc_attr($settings['close_size_mobile']); ?>"
                            >
                        </p>
                    </div>
                </div>
            </div>

            <div class="cit-bubble-admin-card">
                <h2>Informace o pluginu, autorovi a licenci</h2>

                <div class="cit-bubble-author-box">
                    <p>
                        <strong>CIT – Informační bublina</strong><br>
                        Aktuálně načtená verze pluginu: <strong><?php echo esc_html(CIT_BUBBLE_VERSION); ?></strong><br>
                        Datum publikace této verze: <strong><?php echo esc_html(CIT_BUBBLE_PUBLISHED); ?></strong>
                    </p>

                    <p>
                        Zakázkový plugin pro potřeby <strong>Centra inovativní terapie Kliniky Podané ruce</strong>.
                    </p>

                    <p>
                        Autor / dodavatel: <strong>GARRY Promotion</strong><br>
                        Web agentury: <a href="https://garry.cz/" target="_blank" rel="noopener noreferrer">https://garry.cz/</a><br>
                        Realizace: <strong>Michal Truhlář</strong>, <a href="mailto:michal@garry.eu">michal@garry.eu</a><br>
                        Technická podpora: <a href="mailto:podpora@garry.eu">podpora@garry.eu</a>
                    </p>
                </div>

                <div class="cit-bubble-license-box">
                    <strong>Licenční poznámka:</strong><br>
                    Doprovodné texty, administrační popisy a dokumentace pluginu jsou poskytovány za podmínek licence
                    <strong>Creative Commons Attribution / Uveďte původ</strong>. Při dalším použití nebo úpravách těchto textů
                    uveďte autora: <strong>GARRY Promotion / Michal Truhlář</strong>.
                    Zdrojový kód pluginu je určen pro zakázkové použití v rámci tohoto webu.
                </div>

                <h3>Historie verzí</h3>
                <ul class="cit-bubble-version-list">
                    <li><strong>1.3.1</strong> – přejmenování v instalaci na „GARRY - Informační bublina" a oprava zarovnání seznamu služeb v Info stránce.</li>
                            <li><strong>1.3.0</strong> – přepracovaný GARRY framework: dynamický registr pluginů, nová stránka „Info" se prezentací agentury.</li>
                            <li><strong>1.2.6</strong> – barevné logo GARRY v hlavním menu a ikonky podstránek ve společném menu.</li>
                    <li><strong>1.2.5</strong> – přesun administrace pod společné menu GARRY nastavení.</li>
                    <li><strong>1.2.4</strong> – přidán checkbox pro zobrazení střed-střed, který ignoruje pozice X/Y pro SVG bublinu i klasický rámeček.</li>
                    <li><strong>1.2.3</strong> – fade-in / fade-out nově respektuje nastavené pozice X/Y a časování i u klasického rámečku.</li>
                    <li><strong>1.2.2</strong> – přidána volba mezi SVG pozadím a klasickým rámečkem a volba animace mezi příjezdem z rohu a fade-in / fade-out.</li>
                    <li><strong>1.2.1</strong> – pravý panel náhledu nově spouští bublinu přímo na administrační stránce místo zmenšeného statického náhledu.</li>
                    <li><strong>1.2.0</strong> – živý náhled bubliny v administraci, přepracované rozložení obsahu, nový výchozí textový kontrast, upravený stav přepínače a výpis vybraných stránek.</li>
                    <li><strong>1.1.4</strong> – graficky sjednocená administrace, úvodní karta, rozdělení sekcí, pevné barvy přepínače, výraznější vybrané barvy, stránky bez posuvníku.</li>
                    <li><strong>1.1.3</strong> – kontrolní verze s kompletní administrací nastavení.</li>
                    <li><strong>1.1.2</strong> – ověřovací verze pro kontrolu načítání pluginu.</li>
                    <li><strong>1.1.1</strong> – doplnění informací o autorovi, licenci a publikaci.</li>
                    <li><strong>1.1.0</strong> – rozšíření administrace o typografii, velikosti, barvy a pozice.</li>
                    <li><strong>1.0.0</strong> – první verze informační bubliny pro vybrané stránky webu.</li>
                </ul>

                <p class="description">
                    Plugin ukládá nastavení do jedné položky ve WordPress databázi:
                    <code><?php echo esc_html(CIT_BUBBLE_OPTION); ?></code>.
                    Při odinstalaci plugin tuto položku odstraní.
                </p>
            </div>

            <?php submit_button('Uložit nastavení'); ?>

                </div>

                <?php cit_bubble_render_live_preview($settings); ?>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Frontend výstup bubliny.
 */
function cit_bubble_render_frontend() {
    if (is_admin()) {
        return;
    }

    if (isset($_GET['et_fb']) && $_GET['et_fb'] === '1') {
        return;
    }

    $settings = cit_bubble_get_settings();

    if ($settings['enabled'] !== '1') {
        return;
    }

    if (empty($settings['page_ids']) || !is_page($settings['page_ids'])) {
        return;
    }

    $title = isset($settings['title']) ? $settings['title'] : '';
    $text = isset($settings['text']) ? $settings['text'] : '';

    $allowed_html = array(
        'br' => array(),
        'strong' => array(),
        'b' => array(),
        'em' => array(),
        'i' => array(),
        'span' => array(
            'class' => array(),
        ),
    );

    $title_font = cit_bubble_css_font_value($settings['title_font']);
    $text_font = cit_bubble_css_font_value($settings['text_font']);

    $bubble_classes = array(
        'cit-capacity-bubble',
        $settings['background_mode'] === 'frame' ? 'cit-capacity-bubble--frame' : 'cit-capacity-bubble--image',
        $settings['animation_mode'] === 'fade' ? 'cit-capacity-bubble--fade' : 'cit-capacity-bubble--slide',
        $settings['center_mode'] === '1' ? 'cit-capacity-bubble--center' : '',
    );

    ?>
    <div id="cit-capacity-bubble" class="<?php echo esc_attr(implode(' ', $bubble_classes)); ?>" aria-hidden="true" aria-live="polite">
        <button type="button" class="cit-capacity-bubble__close" aria-label="Zavřít upozornění">×</button>

        <div class="cit-capacity-bubble__content">
            <div class="cit-capacity-bubble__title">
                <?php echo esc_html($title); ?>
            </div>

            <div class="cit-capacity-bubble__text">
                <?php echo wp_kses($text, $allowed_html); ?>
            </div>
        </div>
    </div>

    <style id="cit-capacity-bubble-css">
    :root {
      --cit-bubble-final-x: <?php echo esc_html($settings['position_x']); ?>;
      --cit-bubble-final-y: <?php echo esc_html($settings['position_y']); ?>;
      --cit-bubble-width: <?php echo esc_html($settings['bubble_size_desktop']); ?>;
      --cit-bubble-z: 999999;
      --cit-bubble-close-top: <?php echo esc_html($settings['close_top_desktop']); ?>;
      --cit-bubble-close-right: <?php echo esc_html($settings['close_right_desktop']); ?>;
      --cit-bubble-close-size: <?php echo esc_html($settings['close_size_desktop']); ?>;
    }

    .cit-capacity-bubble {
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      z-index: var(--cit-bubble-z) !important;

      width: var(--cit-bubble-width) !important;
      aspect-ratio: 1 / 1 !important;

      background-image: <?php echo $settings['background_mode'] === 'image' ? "url('" . esc_url($settings['image_url']) . "')" : 'none'; ?> !important;
      background-repeat: no-repeat !important;
      background-position: center center !important;
      background-size: contain !important;

      transform: translate(-120%, -120%) !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;

      transition:
        transform 0.85s cubic-bezier(.22,.9,.28,1),
        opacity 0.3s ease,
        visibility 0.3s ease !important;
    }

    .cit-capacity-bubble.is-visible {
      transform: translate(var(--cit-bubble-final-x), var(--cit-bubble-final-y)) !important;
      opacity: 1 !important;
      visibility: visible !important;
      pointer-events: auto !important;
    }

    .cit-capacity-bubble.is-hiding {
      transform: translate(-120%, -120%) !important;
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
    }

    .cit-capacity-bubble--fade {
      transform: translate(var(--cit-bubble-final-x), var(--cit-bubble-final-y)) scale(.98) !important;
    }

    .cit-capacity-bubble--fade.is-visible {
      transform: translate(var(--cit-bubble-final-x), var(--cit-bubble-final-y)) scale(1) !important;
    }

    .cit-capacity-bubble--fade.is-hiding {
      transform: translate(var(--cit-bubble-final-x), var(--cit-bubble-final-y)) scale(.98) !important;
    }

    .cit-capacity-bubble--center {
      top: 50% !important;
      left: 50% !important;
      transform: translate(-50%, -50%) scale(.98) !important;
    }

    .cit-capacity-bubble--center.is-visible {
      transform: translate(-50%, -50%) scale(1) !important;
    }

    .cit-capacity-bubble--center.is-hiding {
      transform: translate(-50%, -50%) scale(.98) !important;
    }

    .cit-capacity-bubble--frame {
      aspect-ratio: auto !important;
      min-height: 220px !important;
      max-width: min(92vw, 520px) !important;
      background: #ffffff !important;
      border: 1px solid rgba(0, 63, 0, 0.18) !important;
      border-radius: 20px !important;
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.18) !important;
    }

    .cit-capacity-bubble--frame .cit-capacity-bubble__content {
      position: static !important;
      transform: none !important;
      width: auto !important;
      max-width: none !important;
      padding: 42px 44px 38px !important;
    }

    .cit-capacity-bubble--frame .cit-capacity-bubble__close {
      top: 14px !important;
      right: 14px !important;
    }

    .cit-capacity-bubble__content {
      position: absolute !important;
      z-index: 2 !important;

      left: 50% !important;
      top: 52% !important;
      transform: translate(-50%, -50%) !important;

      width: 46% !important;
      max-width: 300px !important;

      box-sizing: border-box !important;
      text-align: left !important;
    }

    .cit-capacity-bubble__title {
      margin: 0 0 18px 0 !important;
      color: <?php echo esc_html($settings['title_color']); ?> !important;
      font-family: <?php echo esc_html($title_font); ?> !important;
      font-size: <?php echo esc_html($settings['title_size_desktop']); ?> !important;
      line-height: 1.05 !important;
      font-weight: 700 !important;
      letter-spacing: 0.02em !important;
      text-transform: uppercase !important;
    }

    .cit-capacity-bubble__text {
      color: <?php echo esc_html($settings['text_color']); ?> !important;
      font-family: <?php echo esc_html($text_font); ?> !important;
      font-size: <?php echo esc_html($settings['text_size_desktop']); ?> !important;
      line-height: 1.45 !important;
      font-weight: 500 !important;
    }

    .cit-capacity-bubble__close {
      position: absolute !important;
      top: var(--cit-bubble-close-top) !important;
      right: var(--cit-bubble-close-right) !important;
      z-index: 3 !important;

      width: var(--cit-bubble-close-size) !important;
      height: var(--cit-bubble-close-size) !important;
      border: 1px solid rgba(0, 63, 0, 0.55) !important;
      border-radius: 999px !important;

      display: flex !important;
      align-items: center !important;
      justify-content: center !important;

      background: rgba(0, 63, 0, 0.14) !important;
      color: #003f00 !important;

      font-size: calc(var(--cit-bubble-close-size) * 0.74) !important;
      line-height: 1 !important;
      font-weight: 300 !important;
      cursor: pointer !important;
      padding: 0 !important;

      transition:
        background-color 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease,
        transform 0.2s ease !important;
    }

    .cit-capacity-bubble__close:hover,
    .cit-capacity-bubble__close:focus-visible {
      background: #003f00 !important;
      color: #ffffff !important;
      border-color: #003f00 !important;
      transform: scale(1.05) !important;
    }

    .cit-capacity-bubble__close:focus-visible {
      outline: 2px solid #003f00 !important;
      outline-offset: 3px !important;
    }

    @media (max-width: 980px) {
      :root {
        --cit-bubble-final-x: <?php echo esc_html($settings['mobile_position_x']); ?>;
        --cit-bubble-final-y: <?php echo esc_html($settings['mobile_position_y']); ?>;
        --cit-bubble-width: <?php echo esc_html($settings['bubble_size_mobile']); ?>;
        --cit-bubble-close-top: <?php echo esc_html($settings['close_top_mobile']); ?>;
        --cit-bubble-close-right: <?php echo esc_html($settings['close_right_mobile']); ?>;
        --cit-bubble-close-size: <?php echo esc_html($settings['close_size_mobile']); ?>;
      }

      .cit-capacity-bubble__content {
        top: 53% !important;
        width: 55% !important;
        max-width: 270px !important;
      }


      .cit-capacity-bubble--frame .cit-capacity-bubble__content {
        position: static !important;
        transform: none !important;
        width: auto !important;
        max-width: none !important;
      }
    }

    @media (max-width: 420px) {
      .cit-capacity-bubble__content {
        width: 58% !important;
      }


      .cit-capacity-bubble--frame .cit-capacity-bubble__content {
        width: auto !important;
        padding: 38px 28px 30px !important;
      }

      .cit-capacity-bubble__title {
        font-size: <?php echo esc_html($settings['title_size_mobile']); ?> !important;
        margin-bottom: 12px !important;
      }

      .cit-capacity-bubble__text {
        font-size: <?php echo esc_html($settings['text_size_mobile']); ?> !important;
        line-height: 1.34 !important;
      }
    }
    </style>

    <script id="cit-capacity-bubble-js">
    (function () {
      if (
        document.body.classList.contains('et-fb') ||
        window.location.search.indexOf('et_fb=1') !== -1
      ) {
        return;
      }

      var bubble = document.getElementById('cit-capacity-bubble');

      if (!bubble) {
        return;
      }

      var closeBtn = bubble.querySelector('.cit-capacity-bubble__close');

      function normalizePath(path) {
        if (!path) {
          return '/';
        }

        path = path.split('?')[0].split('#')[0];

        if (path !== '/' && !path.endsWith('/')) {
          path += '/';
        }

        return path;
      }

      var currentPath = normalizePath(window.location.pathname);
      var displayMode = '<?php echo esc_js($settings['display_mode']); ?>';
      var storageKey = 'cit_capacity_bubble_<?php echo esc_js($settings['notice_version']); ?>_' + currentPath;

      if (displayMode !== 'always') {
        try {
          if (localStorage.getItem(storageKey) === '1') {
            return;
          }
        } catch (e) {}
      }

      var startDelayMs = <?php echo (int) $settings['start_delay_ms']; ?>;
      var autoCloseDelayMs = <?php echo (int) $settings['auto_close_delay_ms']; ?>;
      var hideTimer = null;

      function markShown() {
        if (displayMode === 'always') {
          return;
        }

        try {
          localStorage.setItem(storageKey, '1');
        } catch (e) {}
      }

      function showBubble() {
        bubble.setAttribute('aria-hidden', 'false');
        bubble.classList.remove('is-hiding');
        bubble.classList.add('is-visible');

        markShown();

        hideTimer = setTimeout(function () {
          hideBubble();
        }, autoCloseDelayMs);
      }

      function hideBubble() {
        if (hideTimer) {
          clearTimeout(hideTimer);
          hideTimer = null;
        }

        bubble.classList.remove('is-visible');
        bubble.classList.add('is-hiding');
        bubble.setAttribute('aria-hidden', 'true');
      }

      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          hideBubble();
        });
      }

      window.addEventListener('load', function () {
        setTimeout(function () {
          showBubble();
        }, startDelayMs);
      });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'cit_bubble_render_frontend', 99);
