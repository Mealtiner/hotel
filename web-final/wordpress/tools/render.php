<?php
/**
 * Harness: vyrenderuje GRID shortcody mimo WordPress (stub WP funkcí)
 * a uloží HTML jednotlivých stránek do out/*.html.
 * Fallbacková data v shortcodes.php = finální obsah webu.
 */
error_reporting(E_ALL & ~E_DEPRECATED);

define('ABSPATH', __DIR__ . '/');

$GLOBALS['GRID_FRONT'] = false;
$GLOBALS['GRID_SHORTCODES'] = [];

/* Stránky, které na cílovém webu existují (dle DEPLOY-checklist) */
const GRID_PAGES = [
    'ubytovani','zazitky','gastronomie','sezona-2026','firemni-akce-svatby',
    'kontakt','jak-se-k-nam-dostanete','dotaznik-spokojenosti','ubytovaci-a-reklamacni-rad',
    'ochrana-osobnich-udaju-gdpr','cookies','o-nas','kariera','casosber-video-stavby',
    'galerie','rezervace','disclaimer','privacy-statement','vseobecne-obchodni-podminky',
];
/* aliasy návrhu → skutečné slugy na webu */
const GRID_SLUG_ALIASES = [
    'doprava'                => 'jak-se-k-nam-dostanete',
    'podminky'               => 'ubytovaci-a-reklamacni-rad',
    'ochrana-osobnich-udaju' => 'ochrana-osobnich-udaju-gdpr',
    'video'                  => 'casosber-video-stavby',
];

/* ---------- WP stuby ---------- */
function grid_field($name, $default = '', $id = 'option') { return $default; }
function esc_html($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8', false); }
function esc_attr($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8', false); }
function esc_url($u)  { return (string)$u; }
function wp_kses_post($t) { return (string)$t; }
function add_shortcode($tag, $cb) { $GLOBALS['GRID_SHORTCODES'][$tag] = $cb; }
function do_shortcode($c) { return (string)$c; }
function shortcode_atts($defaults, $atts) {
    $atts = (array)$atts; $out = [];
    foreach ($defaults as $k => $v) { $out[$k] = array_key_exists($k, $atts) ? $atts[$k] : $v; }
    return $out;
}
function wpautop($t) {
    $t = trim((string)$t);
    if ($t === '') return '';
    $paras = preg_split('/\n\s*\n/', $t);
    return implode('', array_map(fn($p) => '<p>' . str_replace("\n", '<br>', trim($p)) . '</p>', $paras));
}
function home_url($path = '') {
    $path = (string)$path;
    if (preg_match('~^/([a-z0-9-]+)/$~', $path, $m)) return '/' . grid_real_slug($m[1]) . '/';
    return $path;
}
function get_stylesheet_directory_uri() { return '/wp-content/themes/grid-divi5-child'; }
function is_front_page() { return $GLOBALS['GRID_FRONT']; }
function grid_real_slug($slug) { return GRID_SLUG_ALIASES[$slug] ?? $slug; }
function get_page_by_path($slug) { $s = grid_real_slug($slug); return in_array($s, GRID_PAGES, true) ? (object)['slug' => $s] : null; }
function get_permalink($p) { return is_object($p) && isset($p->slug) ? '/' . $p->slug . '/' : '/'; }
function post_type_exists($pt) { return false; }
function taxonomy_exists($tx) { return false; }
class WP_Query { public $posts = []; public function __construct($a = null) {} }
function get_the_title($p) { return ''; }
function get_the_post_thumbnail_url($id, $size = '') { return false; }
function get_post_meta($id, $k, $single = false) { return ''; }
function wp_get_attachment_image_url($id, $size = '') { return false; }
function get_term_meta($id, $k, $single = false) { return ''; }
function get_terms($a = []) { return []; }
function get_term_link($t) { return ''; }
function wp_get_post_terms($id, $tx) { return []; }
function is_wp_error($x) { return false; }
if (!function_exists('mb_strtolower')) { function mb_strtolower($s) { return strtolower($s); } }

require __DIR__ . '/../grid-divi5-child/inc/shortcodes.php';

/* ---------- Mapa stránek: soubor => [front?, sekvence shortcodů] ---------- */
$sc = $GLOBALS['GRID_SHORTCODES'];
$call = fn($tag) => call_user_func($sc[$tag]);

$pages = [
    'page-domov'          => [true,  ['grid_hero','grid_booking','grid_vstupy','grid_pribeh','grid_rooms','grid_zazitky','grid_gastro','grid_season','grid_firemni','grid_reference','grid_final']],
    'page-pokoje'         => [false, ['grid_rooms','grid_final']],
    'page-zazitky'        => [false, ['grid_zazitky','grid_final']],
    'page-gastronomie'    => [false, ['grid_gastro','grid_final']],
    'page-sezona-2026'    => [false, ['grid_season','grid_final']],
    'page-firemni-svatby' => [false, ['grid_firemni','grid_final']],
    'page-kontakt'        => [false, ['grid_kontakt']],
    'page-doprava'        => [false, ['grid_doprava']],
    'page-dotaznik'       => [false, ['grid_form_dotaznik']],
    'page-podminky'       => [false, ['grid_podminky']],
    'page-ochrana-udaju'  => [false, ['grid_gdpr']],
    'page-o-nas'          => [false, ['grid_onas']],
    'page-kariera'        => [false, ['grid_kariera']],
    'page-video'          => [false, ['grid_video']],
    'page-rezervace'      => [false, ['grid_rezervace']],
    'header-global'       => [false, ['grid_header']],
    'footer-global'       => [false, ['grid_footer']],
];

@mkdir(__DIR__ . '/out', 0777, true);

foreach ($pages as $file => [$front, $tags]) {
    $GLOBALS['GRID_FRONT'] = $front;
    $html = '';
    foreach ($tags as $t) {
        if (!isset($sc[$t])) { fwrite(STDERR, "CHYBI shortcode: $t\n"); exit(1); }
        $html .= "\n<!--GRID-SC:$t-->\n" . $call($t);
    }
    file_put_contents(__DIR__ . "/out/$file.html", $html);
    echo "$file.html: " . strlen($html) . " B\n";
}

/* Legal stránky — grid_legal obal + Complianz shortcode uvnitř */
$GLOBALS['GRID_FRONT'] = false;
$legal = [
    'page-cookies'           => ['Cookies',        'Prohlášení o cookies',              '[cmplz-document type="cookie-statement" region="eu"]'],
    'page-disclaimer'        => ['Disclaimer',     'Prohlášení / Disclaimer',           '[cmplz-document type="disclaimer" region="eu"]'],
    'page-privacy-statement' => ['Ochrana údajů',  'Zásady ochrany osobních údajů',     '[cmplz-document type="privacy-statement" region="eu"]'],
];
foreach ($legal as $file => [$kicker, $nadpis, $inner]) {
    $html = "\n<!--GRID-SC:grid_legal-->\n" . grid_sc_legal(['kicker' => $kicker, 'nadpis' => $nadpis], $inner);
    file_put_contents(__DIR__ . "/out/$file.html", $html);
    echo "$file.html: " . strlen($html) . " B\n";
}
echo "HOTOVO\n";
