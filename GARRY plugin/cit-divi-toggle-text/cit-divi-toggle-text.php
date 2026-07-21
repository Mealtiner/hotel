<?php
/**
 * Plugin Name: GARRY - Toggle Text
 * Description: Zakázkový mikroplugin pro rozbalovací textové bloky ve WordPressu / Divi.
 * Version: 1.2.1
 * Author: GARRY Promotion
 * Author URI: https://garry.cz/
 * Text Domain: cit-divi-toggle-text
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CIT_DIVI_TOGGLE_OPTION', 'cit_divi_toggle_text_settings');
define('CIT_DIVI_TOGGLE_VERSION', '1.2.1');
define('CIT_DIVI_TOGGLE_PUBLISHED', '28. 5. 2026');

define('CIT_DIVI_TOGGLE_SWITCH_ON_COLOR', '#68C020');
define('CIT_DIVI_TOGGLE_SWITCH_OFF_COLOR', '#E02B20');




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
 * Helper funkce pro Toggle Text plugin – výběr fontu, váhy a sanitizace CSS hodnot.
 * (Tyto funkce byly omylem odstraněny ve verzi 1.1.3, doplněno zpět ve v1.2.0.)
 */
function cit_divi_toggle_font_choices() {
    return array(
        'inherit' => 'Výchozí font webu',
        'nagel' => 'Nagel / Arial',
        'system' => 'Systémový font',
        'arial' => 'Arial',
        'georgia' => 'Georgia',
    );
}

function cit_divi_toggle_css_font_value($font_key) {
    $map = array(
        'inherit' => 'inherit',
        'nagel' => '"Nagel", "nagel", Arial, sans-serif',
        'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'arial' => 'Arial, sans-serif',
        'georgia' => 'Georgia, serif',
    );

    return isset($map[$font_key]) ? $map[$font_key] : $map['inherit'];
}

function cit_divi_toggle_font_weight_choices() {
    return array(
        '400' => '400 – normální',
        '500' => '500 – střední',
        '600' => '600 – polotučné',
        '700' => '700 – tučné',
        '800' => '800 – velmi tučné',
    );
}

function cit_divi_toggle_sanitize_css_size($value, $fallback) {
    $value = trim((string) $value);

    if ($value === '') {
        return $fallback;
    }

    if (strlen($value) > 40) {
        return $fallback;
    }

    if (preg_match('/[;{}<>]/', $value)) {
        return $fallback;
    }

    if (preg_match('/^[0-9a-zA-Z\s\.\,\-\+\*\/\(\)%]+$/', $value)) {
        return $value;
    }

    return $fallback;
}

function cit_divi_toggle_defaults() {
    return array(
        'enabled' => '1',

        'light_class' => 'divi-toggle-text',
        'dark_class' => 'divi-toggle-text2',

        'expand_text' => 'Přečíst více.',
        'collapse_text' => 'Schovat text.',

        'button_font_family' => 'inherit',
        'button_font_size' => '1em',
        'button_font_weight' => '700',
        'button_letter_spacing' => '0em',

        'expand_icon' => '3',
        'collapse_icon' => '2',

        'light_display_mode' => 'lines',
        'dark_display_mode' => 'lines',

        'light_value' => 5,
        'dark_value' => 5,

        'light_link_color' => '#003F00',
        'light_hover_color' => '#68C020',
        'light_active_color' => '#003F00',

        'dark_link_color' => '#FFFFFF',
        'dark_hover_color' => '#68C020',
        'dark_active_color' => '#B8F28A',

        /**
         * Barvy pozadí pouze pro administrační náhled a karty nastavení barev.
         * Nezasahují do frontendu, kde pozadí určuje samotný design stránky.
         */
        'light_background_color' => '#FFFFFF',
        'dark_background_color' => '#12350E',

        'fade_height_px' => 90,
        'extra_safety_px' => 8,
        'expanded_max_height_px' => 5000,
    );
}

function cit_divi_toggle_get_settings() {
    $saved = get_option(CIT_DIVI_TOGGLE_OPTION, array());

    if (!is_array($saved)) {
        $saved = array();
    }

    return array_merge(cit_divi_toggle_defaults(), $saved);
}

function cit_divi_toggle_activate() {
    if (get_option(CIT_DIVI_TOGGLE_OPTION, null) === null) {
        add_option(CIT_DIVI_TOGGLE_OPTION, cit_divi_toggle_defaults(), '', false);
    }
}
register_activation_hook(__FILE__, 'cit_divi_toggle_activate');

function cit_divi_toggle_sanitize_class($value, $fallback) {
    $value = trim((string) $value);
    $value = ltrim($value, '.');
    $value = preg_replace('/[^a-zA-Z0-9_-]/', '', $value);

    return $value !== '' ? $value : $fallback;
}

function cit_divi_toggle_sanitize_settings($input) {
    $defaults = cit_divi_toggle_defaults();

    if (!is_array($input)) {
        return $defaults;
    }

    $output = array();

    $output['enabled'] = !empty($input['enabled']) ? '1' : '0';

    $output['light_class'] = cit_divi_toggle_sanitize_class($input['light_class'] ?? '', $defaults['light_class']);
    $output['dark_class'] = cit_divi_toggle_sanitize_class($input['dark_class'] ?? '', $defaults['dark_class']);

    $output['expand_text'] = isset($input['expand_text']) ? sanitize_text_field($input['expand_text']) : $defaults['expand_text'];
    $output['collapse_text'] = isset($input['collapse_text']) ? sanitize_text_field($input['collapse_text']) : $defaults['collapse_text'];

    $font_choices = array_keys(cit_divi_toggle_font_choices());
    $output['button_font_family'] = isset($input['button_font_family']) && in_array($input['button_font_family'], $font_choices, true)
        ? $input['button_font_family']
        : $defaults['button_font_family'];

    $output['button_font_size'] = cit_divi_toggle_sanitize_css_size(
        isset($input['button_font_size']) ? $input['button_font_size'] : '',
        $defaults['button_font_size']
    );

    $weight_choices = array_keys(cit_divi_toggle_font_weight_choices());
    $output['button_font_weight'] = isset($input['button_font_weight']) && in_array((string) $input['button_font_weight'], $weight_choices, true)
        ? (string) $input['button_font_weight']
        : $defaults['button_font_weight'];

    $output['button_letter_spacing'] = cit_divi_toggle_sanitize_css_size(
        isset($input['button_letter_spacing']) ? $input['button_letter_spacing'] : '',
        $defaults['button_letter_spacing']
    );

    $output['expand_icon'] = isset($input['expand_icon']) ? sanitize_text_field($input['expand_icon']) : $defaults['expand_icon'];
    $output['collapse_icon'] = isset($input['collapse_icon']) ? sanitize_text_field($input['collapse_icon']) : $defaults['collapse_icon'];

    $allowed_modes = array('lines', 'characters', 'pixels');

    foreach (array('light', 'dark') as $variant) {
        $mode_key = $variant . '_display_mode';
        $value_key = $variant . '_value';

        $output[$mode_key] = isset($input[$mode_key]) && in_array($input[$mode_key], $allowed_modes, true)
            ? $input[$mode_key]
            : $defaults[$mode_key];

        $value = isset($input[$value_key]) ? absint($input[$value_key]) : $defaults[$value_key];

        if ($output[$mode_key] === 'lines') {
            $value = max(1, min(30, $value));
        } elseif ($output[$mode_key] === 'characters') {
            $value = max(40, min(5000, $value));
        } else {
            $value = max(40, min(3000, $value));
        }

        $output[$value_key] = $value;

        foreach (array('link_color', 'hover_color', 'active_color') as $color_key) {
            $full_key = $variant . '_' . $color_key;
            $color = isset($input[$full_key]) ? sanitize_hex_color($input[$full_key]) : '';
            $output[$full_key] = $color ? $color : $defaults[$full_key];
        }
    }

    $light_background_color = isset($input['light_background_color']) ? sanitize_hex_color($input['light_background_color']) : '';
    $dark_background_color = isset($input['dark_background_color']) ? sanitize_hex_color($input['dark_background_color']) : '';

    $output['light_background_color'] = $light_background_color ? $light_background_color : $defaults['light_background_color'];
    $output['dark_background_color'] = $dark_background_color ? $dark_background_color : $defaults['dark_background_color'];

    $output['fade_height_px'] = isset($input['fade_height_px']) ? max(10, absint($input['fade_height_px'])) : $defaults['fade_height_px'];
    $output['extra_safety_px'] = isset($input['extra_safety_px']) ? max(0, absint($input['extra_safety_px'])) : $defaults['extra_safety_px'];
    $output['expanded_max_height_px'] = isset($input['expanded_max_height_px']) ? max(500, absint($input['expanded_max_height_px'])) : $defaults['expanded_max_height_px'];

    return $output;
}

function cit_divi_toggle_register_settings() {
    register_setting(
        'cit_divi_toggle_settings_group',
        CIT_DIVI_TOGGLE_OPTION,
        array(
            'type' => 'array',
            'sanitize_callback' => 'cit_divi_toggle_sanitize_settings',
            'default' => cit_divi_toggle_defaults(),
        )
    );
}
add_action('admin_init', 'cit_divi_toggle_register_settings');

/**
 * Registrace pluginu ve sdíleném GARRY frameworku.
 * Probíhá na úrovni načtení pluginu, tedy před tím, než framework
 * sestavuje hlavní menu v admin_menu hooku.
 */
Garry_Promotion_Registry::register(array(
    'slug'        => 'cit-divi-toggle-text',
    'title'       => 'Rozbalovací texty',
    'callback'    => 'cit_divi_toggle_render_admin_page',
    'plugin_file' => __FILE__,
    'dashicon'    => 'dashicons-editor-expand',
    'position'    => 10,
));

function cit_divi_toggle_add_color(&$colors, $hex, $label = 'Barva') {
    $hex = sanitize_hex_color($hex);

    if (!$hex) {
        return;
    }

    $key = strtolower($hex);

    if (!isset($colors[$key])) {
        $colors[$key] = $label . ' ' . strtoupper($hex);
    }
}

function cit_divi_toggle_collect_hex_colors($value, &$colors, $label = 'Divi barva') {
    if (is_string($value)) {
        if (preg_match_all('/#[0-9a-fA-F]{6}\b/', $value, $matches)) {
            foreach ($matches[0] as $hex) {
                cit_divi_toggle_add_color($colors, $hex, $label);
            }
        }

        return;
    }

    if (is_array($value) || is_object($value)) {
        foreach ((array) $value as $sub_value) {
            cit_divi_toggle_collect_hex_colors($sub_value, $colors, $label);
        }
    }
}

function cit_divi_toggle_get_divi_colors() {
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
            cit_divi_toggle_collect_hex_colors($option_value, $colors, 'Divi barva');
        }
    }

    cit_divi_toggle_add_color($colors, '#003F00', 'CIT zelená');
    cit_divi_toggle_add_color($colors, '#68C020', 'CIT světle zelená');
    cit_divi_toggle_add_color($colors, '#FFFFFF', 'Bílá');
    cit_divi_toggle_add_color($colors, '#222222', 'Tmavý text');
    cit_divi_toggle_add_color($colors, '#B8F28A', 'Světlá zelená');

    return $colors;
}

function cit_divi_toggle_readable_text_color($hex) {
    $hex = sanitize_hex_color($hex);

    if (!$hex) {
        return '#222222';
    }

    $hex = ltrim($hex, '#');

    if (strlen($hex) !== 6) {
        return '#222222';
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b);

    return $luma < 145 ? '#ffffff' : '#222222';
}

function cit_divi_toggle_render_background_color_field($option_name, $field_name, $label, $value) {
    $target_id = 'cit-divi-toggle-' . str_replace('_', '-', $field_name);
    ?>
    <div class="cit-toggle-field">
        <label for="<?php echo esc_attr($target_id); ?>"><?php echo esc_html($label); ?></label>
        <input
            id="<?php echo esc_attr($target_id); ?>"
            class="cit-toggle-settings-input cit-toggle-background-color-input"
            type="text"
            name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field_name); ?>]"
            value="<?php echo esc_attr($value); ?>"
            placeholder="#ffffff"
        >
        <p class="description">
            Slouží jen pro náhled v administraci. Pozadí na webu určuje samotný design stránky.
        </p>
    </div>
    <?php
}

function cit_divi_toggle_render_color_control($option_name, $field_name, $label, $value, $colors) {
    $target_id = 'cit-divi-toggle-' . str_replace('_', '-', $field_name);
    ?>
    <div class="cit-toggle-color-control">
        <label for="<?php echo esc_attr($target_id); ?>"><?php echo esc_html($label); ?></label>

        <div class="cit-toggle-selected-color">
            <span class="cit-toggle-selected-color__swatch" data-preview-for="#<?php echo esc_attr($target_id); ?>" style="background: <?php echo esc_attr($value); ?>"></span>
            <code data-value-for="#<?php echo esc_attr($target_id); ?>"><?php echo esc_html(strtoupper($value)); ?></code>
        </div>

        <input
            id="<?php echo esc_attr($target_id); ?>"
            type="text"
            class="cit-toggle-color-input cit-toggle-settings-input"
            name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field_name); ?>]"
            value="<?php echo esc_attr($value); ?>"
        >

        <div class="cit-toggle-color-row">
            <?php foreach ($colors as $hex => $color_label) : ?>
                <button
                    type="button"
                    class="cit-toggle-color-preset"
                    data-target="#<?php echo esc_attr($target_id); ?>"
                    data-color="<?php echo esc_attr($hex); ?>"
                    title="<?php echo esc_attr($color_label); ?>"
                >
                    <span class="cit-toggle-color-swatch" style="background: <?php echo esc_attr($hex); ?>"></span>
                    <span><?php echo esc_html(strtoupper($hex)); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function cit_divi_toggle_admin_assets($hook) {
    if (!in_array($hook, array('toplevel_page_cit-divi-toggle-text', 'garry-nastaveni_page_cit-divi-toggle-text'), true)) {
        return;
    }

    wp_enqueue_script('jquery');

    $js = <<<JS
(function($) {
    var citToggleFontMap = {
        inherit: 'inherit',
        nagel: '\"Nagel\", \"nagel\", Arial, sans-serif',
        system: 'system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif',
        arial: 'Arial, sans-serif',
        georgia: 'Georgia, serif'
    };

    function updateSelectedColor(input) {
        var inputEl = $(input);
        var value = inputEl.val();
        var target = '#' + inputEl.attr('id');

        $('[data-preview-for="' + target + '"]').css('background', value);
        $('[data-value-for="' + target + '"]').text(String(value).toUpperCase());

        $('.cit-toggle-color-preset[data-target="' + target + '"]').removeClass('is-selected');
        $('.cit-toggle-color-preset[data-target="' + target + '"][data-color="' + String(value).toLowerCase() + '"]').addClass('is-selected');
        $('.cit-toggle-color-preset[data-target="' + target + '"][data-color="' + String(value).toUpperCase() + '"]').addClass('is-selected');
    }

    function previewHeight(prefix) {
        var mode = $('#cit-divi-toggle-' + prefix + '-display-mode').val();
        var value = parseInt($('#cit-divi-toggle-' + prefix + '-value').val(), 10) || 5;

        if (mode === 'pixels') {
            return value + 'px';
        }

        if (mode === 'characters') {
            return Math.max(74, Math.min(230, Math.ceil(value / 2.8))) + 'px';
        }

        return 'calc(' + value + ' * 1.55em)';
    }

    function readableTextColor(hex) {
        hex = String(hex || '').replace('#', '');

        if (hex.length !== 6) {
            return '#222222';
        }

        var r = parseInt(hex.substring(0, 2), 16);
        var g = parseInt(hex.substring(2, 4), 16);
        var b = parseInt(hex.substring(4, 6), 16);

        var luma = (0.299 * r + 0.587 * g + 0.114 * b);

        return luma < 145 ? '#ffffff' : '#222222';
    }

    function getBackgroundColor(prefix) {
        return $('#cit-divi-toggle-' + prefix + '-background-color').val() || (prefix === 'dark' ? '#12350E' : '#FFFFFF');
    }

    function getVariantColors(prefix) {
        return {
            link: $('#cit-divi-toggle-' + prefix + '-link-color').val() || (prefix === 'dark' ? '#FFFFFF' : '#003F00'),
            hover: $('#cit-divi-toggle-' + prefix + '-hover-color').val() || '#68C020',
            active: $('#cit-divi-toggle-' + prefix + '-active-color').val() || (prefix === 'dark' ? '#B8F28A' : '#003F00')
        };
    }

    function updateVariantBackground(prefix) {
        var bg = getBackgroundColor(prefix);
        var textColor = readableTextColor(bg);

        $('#cit-toggle-preview-' + prefix + '-box').css({
            background: bg,
            color: textColor
        });

        $('#cit-toggle-preview-' + prefix + '-box .description').css('color', textColor);
        $('#cit-toggle-preview-' + prefix + '-box code').css('color', textColor);

        $('.cit-toggle-color-settings-card--' + prefix).css({
            background: bg,
            color: textColor
        });

        $('.cit-toggle-color-settings-card--' + prefix + ' label, .cit-toggle-color-settings-card--' + prefix + ' h3, .cit-toggle-color-settings-card--' + prefix + ' .description').css('color', textColor);
    }

    function updateOnePreview(prefix) {
        var expandText = $('#cit-divi-toggle-expand-text').val() || 'Přečíst více.';
        var collapseText = $('#cit-divi-toggle-collapse-text').val() || 'Schovat text.';
        var box = $('#cit-toggle-preview-' + prefix + '-box');
        var text = $('#cit-toggle-preview-' + prefix + '-text');
        var button = $('#cit-toggle-preview-' + prefix + '-button');
        var expanded = box.hasClass('is-expanded');
        var colors = getVariantColors(prefix);

        updateVariantBackground(prefix);

        text.css('max-height', expanded ? 'none' : previewHeight(prefix));

        button
            .text(expanded ? collapseText : expandText)
            .attr('aria-expanded', expanded ? 'true' : 'false')
            .css('color', expanded ? colors.active : colors.link)
            .data('hoverColor', colors.hover)
            .data('restColor', expanded ? colors.active : colors.link);

        $('#cit-toggle-preview-' + prefix + '-class').text($('.cit-toggle-' + prefix + '-class-input').val() || (prefix === 'dark' ? 'divi-toggle-text2' : 'divi-toggle-text'));
    }

    function updatePreview() {
        var buttonFont = citToggleFontMap[$('#cit-divi-toggle-button-font-family').val()] || 'inherit';
        var buttonSize = $('#cit-divi-toggle-button-font-size').val() || '1em';
        var buttonWeight = $('#cit-divi-toggle-button-font-weight').val() || '700';
        var buttonLetterSpacing = $('#cit-divi-toggle-button-letter-spacing').val() || '0em';

        $('.cit-toggle-preview-toggle-button').css({
            fontFamily: buttonFont,
            fontSize: buttonSize,
            fontWeight: buttonWeight,
            letterSpacing: buttonLetterSpacing
        });

        updateOnePreview('light');
        updateOnePreview('dark');
    }

    function updateCurrentStateText() {
        var enabled = $('.cit-toggle-switch input[type="checkbox"]').is(':checked');

        $('#cit-toggle-current-state-label').text(
            enabled
                ? 'Rozbalovací texty jsou zapnuté. Vybrané textové bloky se zkracují podle nastavení.'
                : 'Rozbalovací texty jsou vypnuté. Texty se na webu vypisují v plné délce.'
        );
    }

    $(function() {
        $('.cit-toggle-color-input').each(function() {
            updateSelectedColor(this);
        });

        updatePreview();
        updateCurrentStateText();

        $('.cit-toggle-color-preset').on('click', function(e) {
            e.preventDefault();

            var target = $(this).data('target');
            var color = $(this).data('color');

            if (target && color) {
                $(target).val(color).trigger('input');
            }
        });

        $('.cit-toggle-color-input').on('input change', function() {
            updateSelectedColor(this);
            updatePreview();
        });

        $('.cit-toggle-preview-toggle-button').on('mouseenter focus', function() {
            var hoverColor = $(this).data('hoverColor');
            if (hoverColor) {
                $(this).css('color', hoverColor);
            }
        });

        $('.cit-toggle-preview-toggle-button').on('mouseleave blur', function() {
            var restColor = $(this).data('restColor');
            if (restColor) {
                $(this).css('color', restColor);
            }
        });

        $('.cit-toggle-preview-toggle-button').on('click', function(e) {
            e.preventDefault();

            var prefix = $(this).data('variant');
            var box = $('#cit-toggle-preview-' + prefix + '-box');

            box.toggleClass('is-expanded');
            updateOnePreview(prefix);
        });

        $('.cit-toggle-settings-input').on('input change', function() {
            updatePreview();
        });

        $('.cit-toggle-switch input[type="checkbox"]').on('change', function() {
            updateCurrentStateText();
        });
    });
})(jQuery);
JS;

    wp_add_inline_script('jquery', $js);

    $on = CIT_DIVI_TOGGLE_SWITCH_ON_COLOR;
    $off = CIT_DIVI_TOGGLE_SWITCH_OFF_COLOR;

    $css = <<<CSS
:root {
    --cit-toggle-admin-green: #003f00;
    --cit-toggle-admin-green-soft: #eff8ec;
    --cit-toggle-admin-border: #dcdcde;
    --cit-toggle-admin-muted: #646970;
    --cit-toggle-switch-on: {$on};
    --cit-toggle-switch-off: {$off};
}

.cit-toggle-admin-wrap {
    max-width: 1540px;
}

.cit-toggle-version-pill {
    display: inline-flex;
    align-items: center;
    background: var(--cit-toggle-admin-green-soft);
    color: var(--cit-toggle-admin-green);
    border: 1px solid rgba(0,63,0,.18);
    border-radius: 999px;
    padding: 5px 12px;
    font-weight: 700;
    margin: 4px 0 14px;
}

.cit-toggle-admin-card {
    background: #fff;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 14px;
    padding: 22px 24px;
    margin: 18px 0;
    box-shadow: 0 1px 2px rgba(0,0,0,.035);
}

.cit-toggle-admin-card h2 {
    margin: 0 0 14px;
    font-size: 20px;
    line-height: 1.25;
}

.cit-toggle-admin-card h3 {
    margin: 0 0 12px;
    font-size: 15px;
}

.cit-toggle-admin-help {
    color: var(--cit-toggle-admin-muted);
    max-width: 880px;
    font-size: 14px;
    line-height: 1.65;
}

.cit-toggle-intro {
    border-left: 5px solid var(--cit-toggle-admin-green);
}

.cit-toggle-intro p {
    max-width: 930px;
    font-size: 14px;
    line-height: 1.7;
}

.cit-toggle-admin-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 410px;
    gap: 20px;
    align-items: start;
}

.cit-toggle-admin-main {
    min-width: 0;
}

.cit-toggle-preview-panel {
    position: sticky;
    top: 42px;
    min-width: 0;
}

.cit-toggle-preview-card {
    background: #fff;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,.035);
}

.cit-toggle-preview-card h2 {
    margin: 0 0 10px;
    font-size: 19px;
}

.cit-toggle-switch-row {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.cit-toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    width: 270px;
    height: 58px;
    border-radius: 999px;
    padding: 5px;
    background: var(--cit-toggle-switch-off);
    box-shadow: inset 0 0 0 2px rgba(0,0,0,.08);
    cursor: pointer;
    transition: background-color .2s ease, box-shadow .2s ease;
}

.cit-toggle-switch input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.cit-toggle-switch__knob {
    position: absolute;
    left: 6px;
    width: 46px;
    height: 46px;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.22);
    transition: transform .22s ease;
}

.cit-toggle-switch__text {
    width: 100%;
    text-align: center;
    color: #fff;
    font-weight: 800;
    font-size: 12px;
    padding-left: 42px;
}

.cit-toggle-switch__text-on {
    display: none;
}

.cit-toggle-switch:has(input:checked) {
    background: var(--cit-toggle-switch-on);
}

.cit-toggle-switch:has(input:checked) .cit-toggle-switch__knob {
    transform: translateX(210px);
}

.cit-toggle-switch:has(input:checked) .cit-toggle-switch__text {
    padding-left: 8px;
    padding-right: 42px;
}

.cit-toggle-switch:has(input:checked) .cit-toggle-switch__text-on {
    display: inline;
}

.cit-toggle-switch:has(input:checked) .cit-toggle-switch__text-off {
    display: none;
}

.cit-toggle-state {
    background: #f6f7f7;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 12px;
    padding: 12px 14px;
    min-width: 360px;
    max-width: 760px;
    line-height: 1.55;
}

.cit-toggle-form-grid {
    display: grid;
    grid-template-columns: minmax(180px, 260px) 1fr;
    gap: 14px 24px;
    align-items: start;
}

.cit-toggle-form-grid > label {
    font-weight: 700;
    padding-top: 6px;
}

.cit-toggle-form-grid input[type="text"],
.cit-toggle-form-grid input[type="number"],
.cit-toggle-form-grid select {
    width: 100%;
    max-width: 640px;
}

.cit-toggle-two-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.cit-toggle-inner-card {
    background: #fbfbfb;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    padding: 16px;
}

.cit-toggle-button-text-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.cit-toggle-button-subcard {
    background: #fbfbfb;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    padding: 16px;
}

.cit-toggle-inner-card--light {
    background: #ffffff;
}

.cit-toggle-inner-card--dark {
    background: #12350e;
    color: #ffffff;
}

.cit-toggle-color-settings-card {
    transition: background-color .2s ease, color .2s ease;
}

.cit-toggle-background-preview-card {
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    padding: 16px;
}

.cit-toggle-background-preview-card--dark,
.cit-toggle-background-preview-card--dark h3,
.cit-toggle-background-preview-card--dark label,
.cit-toggle-background-preview-card--dark .description {
    color: #ffffff !important;
}

.cit-toggle-inner-card--dark label,
.cit-toggle-inner-card--dark h3,
.cit-toggle-inner-card--dark .description {
    color: #ffffff;
}

.cit-toggle-field {
    margin-bottom: 15px;
}

.cit-toggle-field label {
    display: block;
    font-weight: 700;
    margin-bottom: 6px;
}

.cit-toggle-field input,
.cit-toggle-field select {
    width: 100%;
}

.cit-toggle-code-box {
    background: #f6f7f7;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 12px;
    padding: 12px;
    font-family: Consolas, Monaco, monospace;
    font-size: 14px;
}

.cit-toggle-code-box code {
    font-size: 15px;
    font-weight: 700;
}

.cit-toggle-color-control {
    margin-bottom: 17px;
}

.cit-toggle-color-control > label {
    display: block;
    font-weight: 700;
    margin-bottom: 8px;
}

.cit-toggle-selected-color {
    display: flex;
    align-items: center;
    gap: 9px;
    background: #fff;
    color: #1d2327;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 12px;
    padding: 9px;
    margin-bottom: 8px;
}

.cit-toggle-selected-color__swatch {
    width: 34px;
    height: 34px;
    border-radius: 999px;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px rgba(0,0,0,.22);
    flex: 0 0 auto;
}

.cit-toggle-color-row {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 10px;
}

.cit-toggle-color-preset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 999px;
    padding: 5px 9px;
    background: #fff;
    cursor: pointer;
    color: #1d2327;
}

.cit-toggle-color-preset.is-selected {
    border-color: var(--cit-toggle-admin-green);
    box-shadow: 0 0 0 2px rgba(0,63,0,.14);
    font-weight: 800;
}

.cit-toggle-color-swatch {
    width: 17px;
    height: 17px;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,.2);
    display: inline-block;
}

.cit-toggle-preview-box {
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 14px;
    padding: 16px;
    margin-top: 14px;
}

.cit-toggle-preview-box--light {
    background: #ffffff;
    color: #222222;
}

.cit-toggle-preview-box--dark {
    background: linear-gradient(135deg, #003f00, #526534);
    color: #ffffff;
}

.cit-toggle-preview-box--dark .description {
    color: rgba(255,255,255,.88) !important;
}

.cit-toggle-preview-box--dark code {
    color: #ffffff !important;
    background: rgba(255,255,255,.16);
    border-radius: 4px;
    padding: 2px 5px;
}

.cit-toggle-preview-box--light code {
    color: #1d2327;
    background: #eeeeee;
    border-radius: 4px;
    padding: 2px 5px;
}

.cit-toggle-preview-title {
    font-weight: 800;
    margin-bottom: 8px;
}

.cit-toggle-preview-text {
    position: relative;
    overflow: hidden;
    line-height: 1.55;
    margin-bottom: 10px;
    -webkit-mask-image: linear-gradient(to bottom, #000 0%, #000 calc(100% - 42px), transparent 100%);
    mask-image: linear-gradient(to bottom, #000 0%, #000 calc(100% - 42px), transparent 100%);
}

.cit-toggle-preview-box.is-expanded .cit-toggle-preview-text {
    max-height: none !important;
    -webkit-mask-image: none !important;
    mask-image: none !important;
}

.cit-toggle-preview-toggle-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-top: 4px;
    border: 0;
    background: transparent;
    padding: 0.45em 0;
    cursor: pointer;
    font-weight: 800;
    text-align: left;
}

.cit-toggle-preview-toggle-button:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 4px;
}

.cit-toggle-author-box {
    font-size: 13px;
    color: #50575e;
    border-left: 4px solid var(--cit-toggle-admin-green);
    padding-left: 12px;
}

.cit-toggle-license-box {
    background: #f6f7f7;
    border: 1px solid var(--cit-toggle-admin-border);
    border-radius: 10px;
    padding: 12px;
    margin: 14px 0;
}

@media (max-width: 1200px) {
    .cit-toggle-admin-layout {
        grid-template-columns: 1fr;
    }

    .cit-toggle-preview-panel {
        position: static;
    }
}

@media (max-width: 900px) {
    .cit-toggle-form-grid,
    .cit-toggle-two-grid,
    .cit-toggle-button-text-grid {
        grid-template-columns: 1fr;
    }

    .cit-toggle-switch {
        width: 220px;
    }

    .cit-toggle-switch:has(input:checked) .cit-toggle-switch__knob {
        transform: translateX(160px);
    }
}
CSS;

    wp_register_style('cit-divi-toggle-admin-style', false, array(), CIT_DIVI_TOGGLE_VERSION);
    wp_enqueue_style('cit-divi-toggle-admin-style');
    wp_add_inline_style('cit-divi-toggle-admin-style', $css);
}
add_action('admin_enqueue_scripts', 'cit_divi_toggle_admin_assets');

function cit_divi_toggle_render_variant_column($variant, $title, $settings, $option_name, $colors) {
    $background_key = $variant . '_background_color';
    $background = isset($settings[$background_key]) ? $settings[$background_key] : ($variant === 'dark' ? '#12350E' : '#FFFFFF');
    $text_color = cit_divi_toggle_readable_text_color($background);
    $card_class = 'cit-toggle-inner-card cit-toggle-color-settings-card cit-toggle-color-settings-card--' . $variant;

    ?>
    <div class="<?php echo esc_attr($card_class); ?>" style="background: <?php echo esc_attr($background); ?>; color: <?php echo esc_attr($text_color); ?>;">
        <h3 style="color: <?php echo esc_attr($text_color); ?>;"><?php echo esc_html($title); ?></h3>

        <?php
        cit_divi_toggle_render_color_control($option_name, $variant . '_link_color', 'Barva odkazu', $settings[$variant . '_link_color'], $colors);
        cit_divi_toggle_render_color_control($option_name, $variant . '_hover_color', 'Barva odkazu při hover / focus', $settings[$variant . '_hover_color'], $colors);
        cit_divi_toggle_render_color_control($option_name, $variant . '_active_color', 'Barva aktivního stavu', $settings[$variant . '_active_color'], $colors);
        ?>
    </div>
    <?php
}


function cit_divi_toggle_render_preview_panel($settings) {
    ?>
    <aside class="cit-toggle-preview-panel">
        <div class="cit-toggle-preview-card">
            <h2>Náhled rozbalovacího textu</h2>
            <p class="cit-toggle-admin-help">
                Ukázka pro světlou a tmavou variantu. Náhled se mění podle aktuálně vyplněných hodnot.
            </p>

            <div class="cit-toggle-preview-box cit-toggle-preview-box--light" id="cit-toggle-preview-light-box" style="background: <?php echo esc_attr($settings['light_background_color']); ?>; color: <?php echo esc_attr(cit_divi_toggle_readable_text_color($settings['light_background_color'])); ?>;">
                <div class="cit-toggle-preview-title">Světlé pozadí</div>
                <div class="cit-toggle-preview-text" id="cit-toggle-preview-light-text">
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum luctus, lacus sed dignissim porta, neque mi luctus arcu, vitae elementum velit sem a mi. Integer sed neque ut lectus congue fermentum. Praesent vitae eros non sapien tincidunt accumsan. Morbi commodo, justo vel interdum pretium, sapien neque feugiat arcu, vitae cursus lorem elit non arcu.
                    <br><br>
                    Curabitur gravida, magna non consequat tincidunt, nibh lectus efficitur lorem, at gravida justo mi sed neque. Donec vitae justo nec purus vehicula dignissim. Suspendisse potenti. Aliquam erat volutpat. Phasellus sed mauris vitae nisl ultrices finibus.

                </div>
                <button type="button" class="cit-toggle-preview-toggle-button" id="cit-toggle-preview-light-button" data-variant="light" aria-expanded="false">
                    <?php echo esc_html($settings['expand_text']); ?>
                </button>
                <p class="description">CSS třída: <code id="cit-toggle-preview-light-class"><?php echo esc_html($settings['light_class']); ?></code></p>
            </div>

            <div class="cit-toggle-preview-box cit-toggle-preview-box--dark" id="cit-toggle-preview-dark-box" style="background: <?php echo esc_attr($settings['dark_background_color']); ?>; color: <?php echo esc_attr(cit_divi_toggle_readable_text_color($settings['dark_background_color'])); ?>;">
                <div class="cit-toggle-preview-title">Tmavé / zelené pozadí</div>
                <div class="cit-toggle-preview-text" id="cit-toggle-preview-dark-text">
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum luctus, lacus sed dignissim porta, neque mi luctus arcu, vitae elementum velit sem a mi. Integer sed neque ut lectus congue fermentum. Praesent vitae eros non sapien tincidunt accumsan. Morbi commodo, justo vel interdum pretium, sapien neque feugiat arcu, vitae cursus lorem elit non arcu.
                    <br><br>
                    Curabitur gravida, magna non consequat tincidunt, nibh lectus efficitur lorem, at gravida justo mi sed neque. Donec vitae justo nec purus vehicula dignissim. Suspendisse potenti. Aliquam erat volutpat. Phasellus sed mauris vitae nisl ultrices finibus.

                </div>
                <button type="button" class="cit-toggle-preview-toggle-button" id="cit-toggle-preview-dark-button" data-variant="dark" aria-expanded="false">
                    <?php echo esc_html($settings['expand_text']); ?>
                </button>
                <p class="description" style="color:#ffffff;">CSS třída: <code id="cit-toggle-preview-dark-class"><?php echo esc_html($settings['dark_class']); ?></code></p>
            </div>
        </div>
    </aside>
    <?php
}

function cit_divi_toggle_render_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = cit_divi_toggle_get_settings();
    $option_name = CIT_DIVI_TOGGLE_OPTION;
    $colors = cit_divi_toggle_get_divi_colors();
    $font_choices = cit_divi_toggle_font_choices();
    $font_weight_choices = cit_divi_toggle_font_weight_choices();

    ?>
    <div class="wrap cit-toggle-admin-wrap">
        <h1>Rozbalovací texty</h1>

        <div class="cit-toggle-version-pill">
            Aktuálně načtená verze pluginu v administraci: <?php echo esc_html(CIT_DIVI_TOGGLE_VERSION); ?>
        </div>

        <div class="cit-toggle-admin-card cit-toggle-intro">
            <h2>Rozbalovací texty pro web Centra inovativní terapie</h2>

            <p>
                Tento zakázkově vyvinutý plugin slouží pro potřeby <strong>Centra inovativní terapie Kliniky Podané ruce</strong>.
                Umožňuje správci webu jednoduše zkracovat delší textové bloky ve stránkách postavených ve WordPressu a Divi.
            </p>

            <p>
                Plugin se používá tak, že se do vybraného Text modulu v Divi vloží příslušná CSS třída.
                Návštěvník webu pak vidí zkrácený text a může si ho rozbalit pomocí odkazu / tlačítka.
            </p>

            <p>
                Níže lze nastavit texty tlačítka, barvy pro světlou i tmavou variantu, způsob zkrácení podle řádků, znaků nebo pixelů
                a CSS třídy, které se mají v Divi používat.
            </p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('cit_divi_toggle_settings_group'); ?>

            <div class="cit-toggle-admin-layout">
                <div class="cit-toggle-admin-main">

                    <div class="cit-toggle-admin-card">
                        <h2>Zapnutí a vypnutí</h2>

                        <div class="cit-toggle-switch-row">
                            <label class="cit-toggle-switch">
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr($option_name); ?>[enabled]"
                                    value="1"
                                    <?php checked($settings['enabled'], '1'); ?>
                                >
                                <span class="cit-toggle-switch__knob"></span>
                                <span class="cit-toggle-switch__text">
                                    <span class="cit-toggle-switch__text-on">rozbalování je zapnuté</span>
                                    <span class="cit-toggle-switch__text-off">rozbalování je vypnuté</span>
                                </span>
                            </label>

                            <div class="cit-toggle-state">
                                <strong>Aktuální stav</strong>
                                <span id="cit-toggle-current-state-label">
                                    <?php echo $settings['enabled'] === '1'
                                        ? 'Rozbalovací texty jsou zapnuté. Vybrané textové bloky se zkracují podle nastavení.'
                                        : 'Rozbalovací texty jsou vypnuté. Texty se na webu vypisují v plné délce.'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>Texty tlačítka</h2>

                        <div class="cit-toggle-button-text-grid">
                            <div class="cit-toggle-button-subcard">
                                <h3>Vlastní text tlačítka</h3>

                                <div class="cit-toggle-form-grid">
                                    <label for="cit-divi-toggle-expand-text">Text pro rozbalení</label>
                                    <div>
                                        <input
                                            id="cit-divi-toggle-expand-text"
                                            class="cit-toggle-settings-input"
                                            type="text"
                                            name="<?php echo esc_attr($option_name); ?>[expand_text]"
                                            value="<?php echo esc_attr($settings['expand_text']); ?>"
                                        >
                                    </div>

                                    <label for="cit-divi-toggle-collapse-text">Text pro sbalení</label>
                                    <div>
                                        <input
                                            id="cit-divi-toggle-collapse-text"
                                            class="cit-toggle-settings-input"
                                            type="text"
                                            name="<?php echo esc_attr($option_name); ?>[collapse_text]"
                                            value="<?php echo esc_attr($settings['collapse_text']); ?>"
                                        >
                                    </div>
                                </div>
                            </div>

                            <div class="cit-toggle-button-subcard">
                                <h3>Nastavení vzhledu textu</h3>

                                <div class="cit-toggle-form-grid">
                                    <label for="cit-divi-toggle-button-font-family">Font textu tlačítka</label>
                                    <div>
                                        <select
                                            id="cit-divi-toggle-button-font-family"
                                            class="cit-toggle-settings-input"
                                            name="<?php echo esc_attr($option_name); ?>[button_font_family]"
                                        >
                                            <?php foreach ($font_choices as $font_key => $font_label) : ?>
                                                <option value="<?php echo esc_attr($font_key); ?>" <?php selected($settings['button_font_family'], $font_key); ?>>
                                                    <?php echo esc_html($font_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <label for="cit-divi-toggle-button-font-size">Velikost textu tlačítka</label>
                                    <div>
                                        <input
                                            id="cit-divi-toggle-button-font-size"
                                            class="cit-toggle-settings-input"
                                            type="text"
                                            name="<?php echo esc_attr($option_name); ?>[button_font_size]"
                                            value="<?php echo esc_attr($settings['button_font_size']); ?>"
                                            placeholder="1em"
                                        >
                                        <p class="description">Například <code>1em</code>, <code>16px</code> nebo <code>18px</code>.</p>
                                    </div>

                                    <label for="cit-divi-toggle-button-font-weight">Váha / tučnost textu tlačítka</label>
                                    <div>
                                        <select
                                            id="cit-divi-toggle-button-font-weight"
                                            class="cit-toggle-settings-input"
                                            name="<?php echo esc_attr($option_name); ?>[button_font_weight]"
                                        >
                                            <?php foreach ($font_weight_choices as $weight_key => $weight_label) : ?>
                                                <option value="<?php echo esc_attr($weight_key); ?>" <?php selected($settings['button_font_weight'], $weight_key); ?>>
                                                    <?php echo esc_html($weight_label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <label for="cit-divi-toggle-button-letter-spacing">Rozpal písmen</label>
                                    <div>
                                        <input
                                            id="cit-divi-toggle-button-letter-spacing"
                                            class="cit-toggle-settings-input"
                                            type="text"
                                            name="<?php echo esc_attr($option_name); ?>[button_letter_spacing]"
                                            value="<?php echo esc_attr($settings['button_letter_spacing']); ?>"
                                            placeholder="0em"
                                        >
                                        <p class="description">Například <code>0em</code>, <code>0.02em</code>, <code>0.5px</code> nebo <code>1px</code>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>Zkrácení</h2>

                        <p class="cit-toggle-admin-help">
                            Nastav, jak se mají texty zkracovat. Hodnota znamená podle zvoleného režimu počet řádků, počet znaků nebo výšku v pixelech.
                        </p>

                        <div class="cit-toggle-two-grid">
                            <div class="cit-toggle-inner-card">
                                <h3>Světlé pozadí</h3>

                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-light-display-mode">Způsob zkrácení</label>
                                    <select
                                        id="cit-divi-toggle-light-display-mode"
                                        class="cit-toggle-settings-input"
                                        name="<?php echo esc_attr($option_name); ?>[light_display_mode]"
                                    >
                                        <option value="lines" <?php selected($settings['light_display_mode'], 'lines'); ?>>Podle počtu řádků</option>
                                        <option value="characters" <?php selected($settings['light_display_mode'], 'characters'); ?>>Podle počtu znaků</option>
                                        <option value="pixels" <?php selected($settings['light_display_mode'], 'pixels'); ?>>Podle výšky v pixelech</option>
                                    </select>
                                </div>

                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-light-value">Hodnota</label>
                                    <input
                                        id="cit-divi-toggle-light-value"
                                        class="cit-toggle-settings-input"
                                        type="number"
                                        min="1"
                                        step="1"
                                        name="<?php echo esc_attr($option_name); ?>[light_value]"
                                        value="<?php echo esc_attr((int) $settings['light_value']); ?>"
                                    >
                                </div>
                            </div>

                            <div class="cit-toggle-inner-card">
                                <h3>Tmavé / zelené pozadí</h3>

                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-dark-display-mode">Způsob zkrácení</label>
                                    <select
                                        id="cit-divi-toggle-dark-display-mode"
                                        class="cit-toggle-settings-input"
                                        name="<?php echo esc_attr($option_name); ?>[dark_display_mode]"
                                    >
                                        <option value="lines" <?php selected($settings['dark_display_mode'], 'lines'); ?>>Podle počtu řádků</option>
                                        <option value="characters" <?php selected($settings['dark_display_mode'], 'characters'); ?>>Podle počtu znaků</option>
                                        <option value="pixels" <?php selected($settings['dark_display_mode'], 'pixels'); ?>>Podle výšky v pixelech</option>
                                    </select>
                                </div>

                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-dark-value">Hodnota</label>
                                    <input
                                        id="cit-divi-toggle-dark-value"
                                        class="cit-toggle-settings-input"
                                        type="number"
                                        min="1"
                                        step="1"
                                        name="<?php echo esc_attr($option_name); ?>[dark_value]"
                                        value="<?php echo esc_attr((int) $settings['dark_value']); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>CSS třídy pro použití v Divi</h2>

                        <p class="cit-toggle-admin-help">
                            Třídu vlož do Divi Text modulu do pole <strong>Pokročilé → CSS ID a třídy → CSS třída</strong>.
                            Do pole se píše pouze název třídy bez tečky.
                        </p>

                        <div class="cit-toggle-two-grid">
                            <div class="cit-toggle-inner-card">
                                <h3>Světlé / běžné pozadí</h3>
                                <div class="cit-toggle-code-box">
                                    <code><?php echo esc_html($settings['light_class']); ?></code>
                                </div>
                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-light-class">Upravit CSS třídu</label>
                                    <input
                                        id="cit-divi-toggle-light-class"
                                        class="cit-toggle-settings-input cit-toggle-class-input cit-toggle-light-class-input"
                                        type="text"
                                        name="<?php echo esc_attr($option_name); ?>[light_class]"
                                        value="<?php echo esc_attr($settings['light_class']); ?>"
                                    >
                                </div>
                            </div>

                            <div class="cit-toggle-inner-card">
                                <h3>Tmavé / zelené / gradientní pozadí</h3>
                                <div class="cit-toggle-code-box">
                                    <code><?php echo esc_html($settings['dark_class']); ?></code>
                                </div>
                                <div class="cit-toggle-field">
                                    <label for="cit-divi-toggle-dark-class">Upravit CSS třídu</label>
                                    <input
                                        id="cit-divi-toggle-dark-class"
                                        class="cit-toggle-settings-input cit-toggle-class-input cit-toggle-dark-class-input"
                                        type="text"
                                        name="<?php echo esc_attr($option_name); ?>[dark_class]"
                                        value="<?php echo esc_attr($settings['dark_class']); ?>"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>Barvy pozadí pro náhled</h2>

                        <p class="cit-toggle-admin-help">
                            Zde lze zadat vlastní HEX barvy pozadí pro světlou a tmavou variantu náhledu.
                            Slouží pouze pro vyzkoušení vzhledu v administraci — pozadí na webu určuje konkrétní sekce stránky.
                        </p>

                        <div class="cit-toggle-two-grid">
                            <div class="cit-toggle-background-preview-card" style="background: <?php echo esc_attr($settings['light_background_color']); ?>; color: <?php echo esc_attr(cit_divi_toggle_readable_text_color($settings['light_background_color'])); ?>;">
                                <h3>Světlé pozadí</h3>
                                <?php cit_divi_toggle_render_background_color_field($option_name, 'light_background_color', 'HEX barva světlého pozadí', $settings['light_background_color']); ?>
                            </div>

                            <div class="cit-toggle-background-preview-card cit-toggle-background-preview-card--dark" style="background: <?php echo esc_attr($settings['dark_background_color']); ?>; color: <?php echo esc_attr(cit_divi_toggle_readable_text_color($settings['dark_background_color'])); ?>;">
                                <h3 style="color: #ffffff;">Tmavé pozadí</h3>
                                <?php cit_divi_toggle_render_background_color_field($option_name, 'dark_background_color', 'HEX barva tmavého pozadí', $settings['dark_background_color']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>Nastavení zkrácení a barev</h2>

                        <p class="cit-toggle-admin-help">
                            Nastavení barev odkazu je rozdělené na dvě varianty. Světlá varianta je pro běžné bílé / světlé pozadí.
                            Tmavá varianta je pro zelené, tmavé nebo gradientní sekce. Barva karet odpovídá nastavení v předchozím rámci.
                        </p>

                        <div class="cit-toggle-two-grid">
                            <?php
                            cit_divi_toggle_render_variant_column('light', 'Světlé pozadí', $settings, $option_name, $colors);
                            cit_divi_toggle_render_variant_column('dark', 'Tmavé / zelené pozadí', $settings, $option_name, $colors);
                            ?>
                        </div>
                    </div>

                    <div class="cit-toggle-admin-card">
                        <h2>Informace o pluginu, autorovi a licenci</h2>

                        <div class="cit-toggle-author-box">
                            <p>
                                <strong>CIT – Divi Toggle Text</strong><br>
                                Aktuálně načtená verze pluginu: <strong><?php echo esc_html(CIT_DIVI_TOGGLE_VERSION); ?></strong><br>
                                Datum publikace této verze: <strong><?php echo esc_html(CIT_DIVI_TOGGLE_PUBLISHED); ?></strong>
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

                        <div class="cit-toggle-license-box">
                            <strong>Licenční poznámka:</strong><br>
                            Doprovodné texty, administrační popisy a dokumentace pluginu jsou poskytovány za podmínek licence
                            <strong>Creative Commons Attribution / Uveďte původ</strong>. Při dalším použití nebo úpravách těchto textů
                            uveďte autora: <strong>GARRY Promotion / Michal Truhlář</strong>.
                            Zdrojový kód pluginu je určen pro zakázkové použití v rámci tohoto webu.
                        </div>

                        <h3>Historie verzí</h3>
                        <ul>
                            <li><strong>1.2.1</strong> – přejmenování v instalaci na „GARRY - Toggle Text" a oprava zarovnání seznamu služeb v Info stránce.</li>
                            <li><strong>1.2.0</strong> – přepracovaný GARRY framework: dynamický registr pluginů, nová stránka „Info", oprava závažné chyby z verze 1.1.3.</li>
                            <li><strong>1.1.3</strong> – barevné logo GARRY v hlavním menu a ikonky podstránek ve společném menu (verze obsahovala závažnou chybu, ve v1.2.0 opraveno).</li>
                            <li><strong>1.1.2</strong> – přesun administrace pod společné menu GARRY nastavení.</li>
                            <li><strong>1.1.1</strong> – sjednocení délky náhledových textů a lepší čitelnost tmavého pozadí v nastavení barev pozadí.</li>
                            <li><strong>1.1.0</strong> – oddělení zkrácení do samostatného rámce a doplnění barev pozadí pro administrační náhled.</li>
                            <li><strong>1.0.3</strong> – doplněn rozpal písmen tlačítka, oddělení textu a typografie, delší náhledové texty a čitelnější CSS třída v tmavém náhledu.</li>
                            <li><strong>1.0.2</strong> – opraven boční náhled, nyní jde skutečně rozbalit a sbalit ukázkový text.</li>
                            <li><strong>1.0.1</strong> – doplněno nastavení fontu, velikosti a váhy textu tlačítka.</li>
                            <li><strong>1.0.0</strong> – první verze mikropluginu pro rozbalovací texty v Divi.</li>
                        </ul>

                        <p class="description">
                            Plugin ukládá nastavení do jedné položky ve WordPress databázi:
                            <code><?php echo esc_html(CIT_DIVI_TOGGLE_OPTION); ?></code>.
                            Při odinstalaci plugin tuto položku odstraní.
                        </p>
                    </div>

                    <?php submit_button('Uložit nastavení'); ?>
                </div>

                <?php cit_divi_toggle_render_preview_panel($settings); ?>
            </div>
        </form>
    </div>
    <?php
}

function cit_divi_toggle_render_frontend() {
    if (is_admin()) {
        return;
    }

    if (isset($_GET['et_fb']) && $_GET['et_fb'] === '1') {
        return;
    }

    $settings = cit_divi_toggle_get_settings();

    if ($settings['enabled'] !== '1') {
        return;
    }

    $light_class = cit_divi_toggle_sanitize_class($settings['light_class'], 'divi-toggle-text');
    $dark_class = cit_divi_toggle_sanitize_class($settings['dark_class'], 'divi-toggle-text2');

    $button_font_family = cit_divi_toggle_css_font_value($settings['button_font_family']);
    $button_font_size = cit_divi_toggle_sanitize_css_size($settings['button_font_size'], '1em');
    $button_letter_spacing = cit_divi_toggle_sanitize_css_size($settings['button_letter_spacing'], '0em');
    $button_font_weight = preg_replace('/[^0-9]/', '', (string) $settings['button_font_weight']);

    if ($button_font_weight === '') {
        $button_font_weight = '700';
    }

    $js_settings = array(
        'lightClass' => $light_class,
        'darkClass' => $dark_class,

        'lightDisplayMode' => $settings['light_display_mode'],
        'darkDisplayMode' => $settings['dark_display_mode'],

        'lightValue' => (int) $settings['light_value'],
        'darkValue' => (int) $settings['dark_value'],

        'fadeHeightPx' => (int) $settings['fade_height_px'],
        'extraSafetyPx' => (int) $settings['extra_safety_px'],
        'expandedMaxHeightPx' => (int) $settings['expanded_max_height_px'],

        'expandText' => $settings['expand_text'],
        'collapseText' => $settings['collapse_text'],
        'expandIcon' => $settings['expand_icon'],
        'collapseIcon' => $settings['collapse_icon'],
    );

    ?>
    <style id="cit-divi-toggle-text-css">
    .<?php echo esc_html($light_class); ?> .et_pb_text_inner,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner {
      max-height: var(--cit-toggle-collapsed-height, 200px) !important;
      transition: max-height 0.3s ease-out;
      overflow: hidden;
      position: relative;
      -webkit-mask-image: linear-gradient(to bottom, #000000 0%, #000000 calc(100% - var(--cit-toggle-fade-height, 90px)), transparent 100%);
      mask-image: linear-gradient(to bottom, #000000 0%, #000000 calc(100% - var(--cit-toggle-fade-height, 90px)), transparent 100%);
      -webkit-mask-size: 100% 100%;
      mask-size: 100% 100%;
      -webkit-mask-repeat: no-repeat;
      mask-repeat: no-repeat;
    }

    .<?php echo esc_html($light_class); ?> .et_pb_text_inner::after,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner::after {
      content: none !important;
      display: none !important;
      background: none !important;
    }

    .<?php echo esc_html($light_class); ?>.cit-toggle-expanded .et_pb_text_inner,
    .<?php echo esc_html($dark_class); ?>.cit-toggle-expanded .et_pb_text_inner {
      max-height: var(--cit-toggle-expanded-height, 5000px) !important;
      transition: max-height 0.3s ease-in;
      -webkit-mask-image: none !important;
      mask-image: none !important;
    }

    .<?php echo esc_html($light_class); ?>.cit-toggle-not-needed .et_pb_text_inner,
    .<?php echo esc_html($dark_class); ?>.cit-toggle-not-needed .et_pb_text_inner {
      max-height: none !important;
      -webkit-mask-image: none !important;
      mask-image: none !important;
    }

    .<?php echo esc_html($light_class); ?>.cit-toggle-not-needed .divi-text-expand-button,
    .<?php echo esc_html($dark_class); ?>.cit-toggle-not-needed .divi-text-expand-button {
      display: none !important;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button,
    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button {
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
      gap: 0.35em !important;
      width: 100% !important;
      margin: 0 !important;
      border: 0 !important;
      background: transparent !important;
      box-shadow: none !important;
      appearance: none !important;
      -webkit-appearance: none !important;
      font: inherit !important;
      font-family: <?php echo esc_html($button_font_family); ?> !important;
      font-size: <?php echo esc_html($button_font_size); ?> !important;
      font-weight: <?php echo esc_html($button_font_weight); ?> !important;
      letter-spacing: <?php echo esc_html($button_letter_spacing); ?> !important;
      line-height: 1.3 !important;
      cursor: pointer !important;
      user-select: none !important;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button *,
    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button * {
      pointer-events: none !important;
      cursor: pointer !important;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button:focus-visible,
    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button:focus-visible {
      outline: 2px solid currentColor !important;
      outline-offset: 4px !important;
    }

    .<?php echo esc_html($light_class); ?> .et_pb_text_inner a {
      color: <?php echo esc_html($settings['light_link_color']); ?>;
    }

    .<?php echo esc_html($light_class); ?> .et_pb_text_inner a:hover,
    .<?php echo esc_html($light_class); ?> .et_pb_text_inner a:focus {
      color: <?php echo esc_html($settings['light_hover_color']); ?>;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button {
      padding: 0.5em;
      text-align: center;
      color: <?php echo esc_html($settings['light_link_color']); ?> !important;
      font-weight: <?php echo esc_html($button_font_weight); ?> !important;
      transition: color 0.3s ease;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button .divi-text-toggle-icon {
      font-family: ETMODULES, "sans-serif";
      color: currentColor !important;
      transition: color 0.3s ease;
    }

    .<?php echo esc_html($light_class); ?> .divi-text-expand-button:hover,
    .<?php echo esc_html($light_class); ?> .divi-text-expand-button:focus {
      color: <?php echo esc_html($settings['light_hover_color']); ?> !important;
    }

    .<?php echo esc_html($light_class); ?>.cit-toggle-expanded .divi-text-expand-button {
      color: <?php echo esc_html($settings['light_active_color']); ?> !important;
    }

    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner {
      color: #ffffff;
    }

    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner a {
      color: <?php echo esc_html($settings['dark_link_color']); ?>;
      text-decoration: underline;
      text-underline-offset: 3px;
    }

    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner a:hover,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner a:focus {
      color: <?php echo esc_html($settings['dark_hover_color']); ?>;
    }

    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h1,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h2,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h3,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h4,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h5,
    .<?php echo esc_html($dark_class); ?> .et_pb_text_inner h6 {
      color: #ffffff;
    }

    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button {
      padding: 0.7em 0.5em;
      text-align: center;
      color: <?php echo esc_html($settings['dark_link_color']); ?> !important;
      font-weight: <?php echo esc_html($button_font_weight); ?> !important;
      transition: color 0.3s ease, opacity 0.3s ease;
    }

    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button .divi-text-toggle-icon {
      font-family: ETMODULES, "sans-serif";
      color: currentColor !important;
      transition: color 0.3s ease;
    }

    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button:hover,
    .<?php echo esc_html($dark_class); ?> .divi-text-expand-button:focus {
      color: <?php echo esc_html($settings['dark_hover_color']); ?> !important;
    }

    .<?php echo esc_html($dark_class); ?>.cit-toggle-expanded .divi-text-expand-button {
      color: <?php echo esc_html($settings['dark_active_color']); ?> !important;
    }
    </style>

    <script id="cit-divi-toggle-text-js">
    (function () {
      var CIT_TOGGLE_SETTINGS = <?php echo wp_json_encode($js_settings); ?>;

      if (window.CITDiviToggleTextPluginInitialized === true) {
        return;
      }

      window.CITDiviToggleTextPluginInitialized = true;

      function getToggleSelector() {
        return '.' + CIT_TOGGLE_SETTINGS.lightClass + ', .' + CIT_TOGGLE_SETTINGS.darkClass;
      }

      function getToggleBlocks() {
        return Array.prototype.slice.call(document.querySelectorAll(getToggleSelector()));
      }

      function getDirectTextInner(block) {
        if (!block || !block.children) {
          return null;
        }

        var children = Array.prototype.slice.call(block.children);

        for (var i = 0; i < children.length; i++) {
          if (children[i].classList && children[i].classList.contains('et_pb_text_inner')) {
            return children[i];
          }
        }

        return block.querySelector('.et_pb_text_inner');
      }

      function getDirectButton(block) {
        if (!block || !block.children) {
          return null;
        }

        var children = Array.prototype.slice.call(block.children);

        for (var i = 0; i < children.length; i++) {
          if (children[i].classList && children[i].classList.contains('divi-text-expand-button')) {
            return children[i];
          }
        }

        return null;
      }

      function removeOldButtons(block) {
        if (!block) {
          return;
        }

        var buttons = block.querySelectorAll('.divi-text-expand-button');

        buttons.forEach(function (button) {
          if (button && button.parentNode) {
            button.parentNode.removeChild(button);
          }
        });
      }

      function isDarkBlock(block) {
        return block && block.classList.contains(CIT_TOGGLE_SETTINGS.darkClass);
      }

      function getLineHeightPx(element) {
        var styles = window.getComputedStyle(element);
        var lineHeight = parseFloat(styles.lineHeight);

        if (!isNaN(lineHeight) && lineHeight > 0) {
          return lineHeight;
        }

        var fontSize = parseFloat(styles.fontSize);

        if (!isNaN(fontSize) && fontSize > 0) {
          return fontSize * 1.7;
        }

        return 27;
      }

      function getTextNodes(root) {
        var nodes = [];

        if (!root) {
          return nodes;
        }

        var walker = document.createTreeWalker(
          root,
          NodeFilter.SHOW_TEXT,
          {
            acceptNode: function (node) {
              if (!node || !node.nodeValue) {
                return NodeFilter.FILTER_REJECT;
              }

              if (node.nodeValue.trim().length === 0) {
                return NodeFilter.FILTER_SKIP;
              }

              return NodeFilter.FILTER_ACCEPT;
            }
          }
        );

        var currentNode;

        while ((currentNode = walker.nextNode())) {
          nodes.push(currentNode);
        }

        return nodes;
      }

      function getHeightByCharacters(textInner, characterLimit) {
        var nodes = getTextNodes(textInner);

        if (!nodes.length) {
          return 0;
        }

        var remaining = characterLimit;
        var targetNode = null;
        var targetOffset = 0;

        for (var i = 0; i < nodes.length; i++) {
          var node = nodes[i];
          var length = node.nodeValue.length;

          if (remaining <= length) {
            targetNode = node;
            targetOffset = Math.max(1, Math.min(length, remaining));
            break;
          }

          remaining -= length;
        }

        if (!targetNode) {
          return textInner.scrollHeight;
        }

        try {
          var range = document.createRange();

          range.setStart(textInner, 0);
          range.setEnd(targetNode, targetOffset);

          var rects = range.getClientRects();

          if (!rects || !rects.length) {
            range.detach();
            return textInner.scrollHeight;
          }

          var lastRect = rects[rects.length - 1];
          var containerRect = textInner.getBoundingClientRect();

          range.detach();

          return Math.ceil(lastRect.bottom - containerRect.top + CIT_TOGGLE_SETTINGS.extraSafetyPx);
        } catch (error) {
          return textInner.scrollHeight;
        }
      }

      function getCollapsedHeight(block, textInner) {
        var dark = isDarkBlock(block);
        var mode = dark ? String(CIT_TOGGLE_SETTINGS.darkDisplayMode || 'lines').toLowerCase() : String(CIT_TOGGLE_SETTINGS.lightDisplayMode || 'lines').toLowerCase();
        var value = dark ? CIT_TOGGLE_SETTINGS.darkValue : CIT_TOGGLE_SETTINGS.lightValue;

        if (mode === 'characters') {
          return getHeightByCharacters(textInner, value);
        }

        if (mode === 'pixels') {
          return value;
        }

        return Math.ceil(getLineHeightPx(textInner) * value + CIT_TOGGLE_SETTINGS.extraSafetyPx);
      }

      function createButton() {
        var button = document.createElement('button');

        button.type = 'button';
        button.className = 'divi-text-expand-button';
        button.setAttribute('aria-expanded', 'false');

        button.innerHTML =
          '<span class="divi-text-collapse-button">' +
            CIT_TOGGLE_SETTINGS.expandText +
            ' <span class="divi-text-toggle-icon">' +
              CIT_TOGGLE_SETTINGS.expandIcon +
            '</span>' +
          '</span>';

        return button;
      }

      function updateButton(button, isExpanded) {
        if (!button) {
          return;
        }

        button.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');

        var label = button.querySelector('.divi-text-collapse-button');

        if (!label) {
          return;
        }

        label.innerHTML =
          (isExpanded ? CIT_TOGGLE_SETTINGS.collapseText : CIT_TOGGLE_SETTINGS.expandText) +
          ' <span class="divi-text-toggle-icon">' +
            (isExpanded ? CIT_TOGGLE_SETTINGS.collapseIcon : CIT_TOGGLE_SETTINGS.expandIcon) +
          '</span>';
      }

      function setupBlock(block) {
        var textInner = getDirectTextInner(block);

        if (!textInner) {
          return;
        }

        block.style.setProperty('--cit-toggle-fade-height', CIT_TOGGLE_SETTINGS.fadeHeightPx + 'px');
        block.style.setProperty('--cit-toggle-expanded-height', CIT_TOGGLE_SETTINGS.expandedMaxHeightPx + 'px');

        var wasExpanded = block.classList.contains('cit-toggle-expanded');

        block.classList.remove('cit-toggle-expanded');

        var collapsedHeight = getCollapsedHeight(block, textInner);

        block.style.setProperty('--cit-toggle-collapsed-height', collapsedHeight + 'px');

        var naturalHeight = textInner.scrollHeight;

        if (naturalHeight <= collapsedHeight + 6) {
          block.classList.add('cit-toggle-not-needed');
          block.classList.remove('cit-toggle-expanded');
          removeOldButtons(block);
          return;
        }

        block.classList.remove('cit-toggle-not-needed');

        removeOldButtons(block);

        var button = createButton();

        block.appendChild(button);

        if (wasExpanded) {
          block.classList.add('cit-toggle-expanded');
        }

        updateButton(button, block.classList.contains('cit-toggle-expanded'));
      }

      function setupAllBlocks() {
        getToggleBlocks().forEach(function (block) {
          setupBlock(block);
        });
      }

      function toggleBlock(block) {
        if (!block) {
          return;
        }

        block.classList.toggle('cit-toggle-expanded');

        var button = getDirectButton(block);

        updateButton(button, block.classList.contains('cit-toggle-expanded'));
      }

      document.addEventListener('click', function (event) {
        var button = event.target.closest('.divi-text-expand-button');

        if (!button) {
          return;
        }

        var block = button.closest(getToggleSelector());

        if (!block) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();

        toggleBlock(block);
      });

      document.addEventListener('keydown', function (event) {
        var button = event.target.closest('.divi-text-expand-button');

        if (!button) {
          return;
        }

        var block = button.closest(getToggleSelector());

        if (!block) {
          return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          toggleBlock(block);
        }
      });

      var resizeTimer = null;

      window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);

        resizeTimer = window.setTimeout(function () {
          setupAllBlocks();
        }, 200);
      });

      function init() {
        setupAllBlocks();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }

      window.setTimeout(setupAllBlocks, 300);
      window.setTimeout(setupAllBlocks, 1000);
    })();
    </script>
    <?php
}
add_action('wp_footer', 'cit_divi_toggle_render_frontend', 99);
