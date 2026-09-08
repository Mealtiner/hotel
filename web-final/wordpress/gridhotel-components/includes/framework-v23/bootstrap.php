<?php
/**
 * GARRY Embedded Framework 2.4 – bootstrap pro plugin gridhotel-components
 * ("GARRY – GRID Components"). Podle docs/GARRY-CORE-FRAMEWORK-2.3-SPEC.md.
 *
 * Druhý a poslední GRID-only plugin (viz GRID-SUITE-02) — přebírá funkční
 * shortcody z child theme, zatímco gridhotel-core zůstává datovým a
 * administračním základem. Vlastní PHP namespace vylučuje jakoukoli kolizi
 * s jiným GARRY pluginem.
 */

namespace Garry\Embedded\GridComponents\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/Protocol.php';
require_once __DIR__ . '/Manifest.php';
require_once __DIR__ . '/Election.php';
require_once __DIR__ . '/LocalLog.php';
require_once __DIR__ . '/Diagnostics.php';
require_once __DIR__ . '/LocalAdmin.php';
require_once __DIR__ . '/GroupAdmin.php';
require_once __DIR__ . '/FrameworkBridge.php';

/**
 * @param string        $plugin_file    __FILE__ hlavního souboru pluginu.
 * @param callable|null $own_callback   Existující vykreslovací funkce vlastní admin stránky pluginu (beze změny), nebo null.
 * @param string        $own_tab_label  Popisek záložky pro $own_callback (ignorováno, pokud je $own_callback null).
 */
function bootstrap( $plugin_file, $own_callback = null, $own_tab_label = 'Nastavení' ) {
	$descriptor = array(
		'slug'               => 'gridhotel-components',
		'name'               => 'GARRY – GRID Components',
		'plugin_version'     => '1.0.4',
		'framework_protocol' => Protocol::VERSION,
		'framework_major'    => Protocol::MAJOR,
		'framework_minimum'  => '2.4.0',
		'framework_priority' => 290,
		'local_menu_slug'    => 'gridhotel-components',
		'icon'               => 'dashicons-layout',
		'claims'             => array( 'root_menu', 'group_overview', 'group_info', 'group_assets' ),
		'manifest_path'      => plugin_dir_path( $plugin_file ) . 'garry-plugin-manifest.json',
		'grid_capability'    => null,
		'grid_capability_label' => null,
		'doc' => '<p>Přebírá funkční shortcody webu GRID Hotel z child theme (fáze 2 GRID Suite refaktoringu) — 30 z 32 původních tagů; <code>[grid_tracknav]</code> a <code>[grid_telemetry]</code> záměrně zůstávají u pluginů „Sekční navigace" a „Situace na trati", které je již dnes správně vlastní. Vyžaduje <strong>GARRY – GRID Core ≥ 2.0.0</strong>. Skládané sekce (pokoje, týdenní menu, sezóna, poukazy) používají <code>ModuleRenderer</code>: nejdřív zaregistrovaný modul, jinak neutrální fallback — nikdy syrový <code>[grid_…]</code> token na frontendu.</p>',
		'changelog' => array(
			array( 'version' => '1.13.0', 'date' => '2026-09-08', 'notes' => 'Formuláře se napojují na data webu z pluginu, ne z motivu: nový modul inc/forms.php přebral z child motivu plnění pole „Typ pokoje\' z kategorií pokojů (filtr fluentform/rendering_field_data_select), lokalizaci kalendáře podle jazyka stránky (fluentform/date_i18n) i značku formuláře poptávky firemních akcí pro vlastní rozbalovací seznam (fluentform/form_class). Formuláře jsou součástí sekcí, které vykresluje tenhle plugin, takže jejich chování patří sem — motiv drží jen vzhled. Zároveň zmizela duplicita: výběr typů pokojů podle jazyka byl napsaný dvakrát, jednou pro [grid_vyber_pokoje] a podruhé pro filtr Fluent Forms; obě cesty teď čtou gridc_typy_pokoju().' ),
			array( 'version' => '1.12.0', 'date' => '2026-09-08', 'notes' => 'Nový shortcode [grid_paticka_menu sloupec="hotel|informace"] — sloupce odkazů v patičce byly napsané natvrdo v šabloně zápatí a nedaly se editovat. Teď je vypisuje menu WordPressu (Vzhled → Menu, pozice „Patička — …“), pro každý jazyk vlastní. Bez přiřazeného menu shortcode nevrátí nic a v šabloně zůstane původní pevná varianta.' ),
			array( 'version' => '1.11.0', 'date' => '2026-09-08', 'notes' => 'Karty letišť mají odkaz na navigaci do hotelu a na web letiště; badge se vzdáleností se přesunul do pravého horního rohu karty.' ),
			array( 'version' => '1.10.0', 'date' => '2026-09-07', 'notes' => 'Opakující se odkazy v kartách („Detail“, „Možnosti“, „Partner“) dostaly skrytý dovětek s názvem zážitku, aby byly ve výpisu odkazů rozlišitelné (WCAG 2.4.4). Neviditelný překryvný odkaz karty je aria-hidden a mimo pořadí procházení, aby se cíl nenabízel dvakrát.' ),
			array( 'version' => '1.9.0', 'date' => '2026-09-07', 'notes' => 'Karta zážitku už nevypisuje číslo (4.0, 4.1…) — kartu o řádek prodlužovalo a návštěvníkovi nic neříkalo. Odlišení nese značka v pravém horním rohu: „Prime“ u vlajkové akce, „voucher“ u poukazů. Vlajková karta dostala obrys trati jako dekoraci (alt="").' ),
			array( 'version' => '1.8.0', 'date' => '2026-09-07', 'notes' => 'Nový shortcode [grid_vyber_pokoje] — pole „Typ pokoje“ v rezervační liště se plní z kategorií pokojů (taxonomie grid_room_cat) v jazyce stránky. Dřív byly možnosti napsané natvrdo v obsahu stránky a nesouhlasily se skutečnými typy: chyběl Superior s terasou i Apartmá Superior a naopak přebýval neexistující „Superior Plus“.' ),
			array( 'version' => '1.7.0', 'date' => '2026-09-07', 'notes' => 'Otevřené mobilní menu je modální dialog: role="dialog", aria-modal, past na fokus (Tab cykluje uvnitř), fokus po otevření na první prvek a po zavření zpět na hamburger (WCAG 2.4.3, 2.1.2).' ),
			array( 'version' => '1.6.0', 'date' => '2026-09-07', 'notes' => 'Vodoznak nad ukázkovým rezervačním widgetem je aria-hidden — je to dekorace, informaci nese text sekce.' ),
			array( 'version' => '1.5.0', 'date' => '2026-09-07', 'notes' => 'Rozbalovací tlačítko v hlavním menu má aria-controls svázané s podmenu, aby čtečka věděla, co otevírá (WCAG 4.1.2).' ),
			array( 'version' => '1.4.0', 'date' => '2026-09-07', 'notes' => 'Přepínač jazyků je <nav> místo <div> — aria-label na bezrolovém divu čtečky ignorují nebo hlásí nekonzistentně (WCAG 4.1.2).' ),
			array( 'version' => '1.3.0', 'date' => '2026-09-07', 'notes' => 'Kontaktní údaje provozovatele v patičce i na stránce Kontakt jsou v <address> místo <span>/<p>.' ),
			array( 'version' => '1.2.0', 'date' => '2026-09-07', 'notes' => 'Nadpisy karet (pokoje, zážitky, gastro, stránky) jsou <h2> místo <h3> — karty stojí přímo pod nadpisem sekce a úroveň h3 přeskakovala stupeň osnovy (WCAG 1.3.1).' ),
			array( 'version' => '1.1.0', 'date' => '2026-09-07', 'notes' => 'Údaj „0 m od trati“ opraven na „1 m“ v obou sekcích, kde se vypisuje.' ),
			array( 'version' => '1.0.4', 'date' => '2026-09-02', 'notes' => 'Skutečná oprava jazykového přepínače na živém webu: v1.0.1 opravila server-side URL výpočet uvnitř [grid_header] shortcode, ale živá hlavička webu vůbec [grid_header] nepoužívá — používá samostatnou Divi Theme Builder šablonu (post ID 11) se 3 staticky zkopírovanými bloky .grid-lang-cs/en/de, kde přepínač měl napevno zadrátované href="/", "/en/", "/de/" (vždy jazyková domovská stránka, ne překlad AKTUÁLNÍ stránky). Přepínač vytažen do vlastního [grid_lang_switch] shortcode (sdílí stejnou gridc_lang_switch_urls() logiku, vrací <div class="lang"> — stejný tag jako původní statický markup, aby se nezměnilo chování v .topnav flex layoutu) a nasazen do všech 6 výskytů (desktop+mobilní menu × 3 jazyky) v post ID 11. Zároveň lokalizovaný aria-label (Jazyk/Language/Sprache) a štítek CZ/EN/DE (dřív natvrdo česky/strtoupper z Polylang slugu "cs", což by dalo "CS" místo "CZ") podle aktuálního jazyka.' ),
			array( 'version' => '1.0.3', 'date' => '2026-09-01', 'notes' => 'Oprava regrese nahlášené z živého webu: gridc_render_hlavni_menu() používala wp_nav_menu(), která výstup vždy zabalí do <ul><li> — ale .topnav nav{display:flex} v theme CSS čeká ploché <a> tagy vedle sebe. Menu se tak vykreslovalo pod sebou místo v řádku. Vráceno ke stejnému plochému formátu jako theme fallback grid_render_hlavni_menu().' ),
			array( 'version' => '1.0.2', 'date' => '2026-09-01', 'notes' => 'Oprava regrese hlášené z živého webu: pět tlačítek/prvků (poptávkový formulář v Business, kontaktní formulář, dotazník spokojenosti — tlačítko i škálové radio přepínače, newsletter v patičce) bylo při portu z theme omylem převedeno z klikatelného placeholderu (onclick="return false", plně stylované .btn) na HTML atribut disabled — prohlížeč pak tlačítko vykresluje ve zjednodušené/zešedlé podobě bez hover efektu a bez jakékoli JS interakce. Vráceno k původnímu chování 1:1 (business.php, forms-contact.php, global.php).' ),
			array( 'version' => '1.0.1', 'date' => '2026-09-01', 'notes' => 'Oprava jazykového přepínače v [grid_header]: baseline (theme) fungoval jen díky JS, který za běhu přepisoval statické href="#" na reálné Polylang URL (window.gridLangUrls) — bez JS tedy přepínač vůbec nefungoval. Teď se URL počítají rovnou server-side (gridc_lang_switch_urls()), takže fungují i bez JS.' ),
			array( 'version' => '1.0.0', 'date' => '2026-09-01', 'notes' => 'První verze — fáze 2 GRID Suite refaktoringu. Převzato 30 shortcodů z child theme s opravou vnořeného skládání (ModuleRenderer), globální hodnoty čteny přes gridhotel_get_option()/datové API místo přímého ACF, žádné accessibility/security regrese vůči baseline.' ),
		),
	);

	$required = array( 'Protocol', 'Election', 'Manifest', 'LocalLog', 'Diagnostics', 'LocalAdmin', 'GroupAdmin', 'FrameworkBridge' );
	$missing  = array();
	foreach ( $required as $class ) {
		if ( ! class_exists( __NAMESPACE__ . '\\' . $class, false ) ) {
			$missing[] = $class;
		}
	}
	if ( ! empty( $missing ) ) {
		add_action( 'admin_notices', function () use ( $missing, $descriptor ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf(
					'GARRY Embedded Framework (%1$s): nekonzistentní instalace – chybí třída/y %2$s v očekávaném namespace. Sdílené GARRY menu je pro tento plugin dočasně vypnuté, aby nedošlo k chybě.',
					$descriptor['name'],
					implode( ', ', $missing )
				) )
			);
		} );
		if ( $own_callback ) {
			add_action( 'admin_menu', function () use ( $descriptor, $own_callback ) {
				add_menu_page(
					$descriptor['name'],
					$descriptor['name'],
					'manage_options',
					$descriptor['local_menu_slug'],
					$own_callback,
					$descriptor['icon'],
					81
				);
			} );
		}
		return;
	}

	$bridge = new FrameworkBridge(
		$descriptor,
		plugin_dir_path( $plugin_file ),
		plugin_dir_url( $plugin_file ),
		'manage_options',
		'gridhotel_components_framework_log',
		$own_callback,
		$own_tab_label
	);
	$bridge->boot();
}
