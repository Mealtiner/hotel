<?php
/**
 * GARRY Embedded Framework 2.4 – společné menu, Přehled a Info.
 */

namespace Garry\Embedded\HeroKrivka\V23;

if ( ! defined( 'ABSPATH' ) ) exit;

final class FrameworkBridge {

	/**
	 * Mapa dashicons CSS třídy na unicode glyf (WordPress u submenu položek
	 * dashicon ikonu sám nevykresluje, proto ::before pseudoprvek). Stejná
	 * mapa jako v původním Frameworku 2.0/2.1/2.2, doplněná o glyfy použité
	 * až po jeho rozdělení do jednotlivých pluginů.
	 */
	const DASHICON_GLYPHS = array(
		'dashicons-admin-generic'      => 'f111',
		'dashicons-admin-settings'     => 'f108',
		'dashicons-admin-tools'        => 'f107',
		'dashicons-admin-customizer'   => 'f540',
		'dashicons-admin-appearance'   => 'f100',
		'dashicons-admin-comments'     => 'f117',
		'dashicons-admin-multisite'    => 'f541',
		'dashicons-editor-textcolor'   => 'f215',
		'dashicons-editor-expand'      => 'f211',
		'dashicons-editor-contract'    => 'f506',
		'dashicons-editor-alignleft'   => 'f207',
		'dashicons-editor-paragraph'   => 'f476',
		'dashicons-editor-ol'          => 'f11d',
		'dashicons-text'               => 'f478',
		'dashicons-format-aside'       => 'f123',
		'dashicons-format-status'      => 'f130',
		'dashicons-format-chat'        => 'f125',
		'dashicons-format-gallery'     => 'f161',
		'dashicons-format-image'       => 'f128',
		'dashicons-images-alt2'        => 'f233',
		'dashicons-slides'             => 'f181',
		'dashicons-grid-view'          => 'f509',
		'dashicons-megaphone'          => 'f488',
		'dashicons-info'               => 'f348',
		'dashicons-info-outline'       => 'f14c',
		'dashicons-welcome-write-blog' => 'f119',
		'dashicons-lightbulb'          => 'f339',
		'dashicons-warning'            => 'f534',
		'dashicons-bell'               => 'f471',
		'dashicons-flag'               => 'f227',
		'dashicons-food'               => 'f18e',
		'dashicons-shield'             => 'f332',
		'dashicons-calendar-alt'       => 'f508',
		'dashicons-camera'             => 'f306',
		'dashicons-location'           => 'f230',
		'dashicons-list-view'          => 'f163',
		'dashicons-money'              => 'f524',
		'dashicons-tag'                => 'f323',
		'dashicons-video-alt3'         => 'f234',
	);

	private $slug;
	private $descriptor;
	private $plugin_dir;
	private $plugin_url;
	private $capability;
	private $log_option;
	private $own_callback;
	private $own_tab_label;

	public function __construct( array $descriptor, $plugin_dir, $plugin_url, $capability, $log_option, $own_callback, $own_tab_label ) {
		$this->slug = $descriptor['slug'];
		$this->descriptor = $descriptor;
		$this->plugin_dir = $plugin_dir;
		$this->plugin_url = $plugin_url;
		$this->capability = $capability;
		$this->log_option = $log_option;
		$this->own_callback = $own_callback;
		$this->own_tab_label = $own_tab_label;
	}

	public function boot() {
		$this->init_registry();
		$this->register_participant();
		add_action( 'plugins_loaded', array( $this, 'run_election' ), 999 );
		add_action( 'admin_menu', array( $this, 'add_shared_root_menu' ), 5 );
		add_action( 'admin_menu', array( $this, 'add_plugin_submenu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_shared_shell_icons' ) );
		$this->maybe_log_framework_boot();
	}

	/**
	 * framework_boot se zapisuje jen jednou za skutečnou verzi frameworku,
	 * ne při každém requestu (boot() běží na každém page loadu/AJAXu/cronu –
	 * bez tohohle strážce by 200položkový log rotoval během pár hodin a
	 * smazal by tak všechny skutečně užitečné události).
	 */
	private function maybe_log_framework_boot() {
		$marker_option = $this->log_option . '_boot_version';
		$last_version  = get_option( $marker_option, '' );

		if ( $last_version === Protocol::VERSION ) {
			return;
		}

		LocalLog::log( $this->log_option, 'framework_boot', array( 'framework_version' => Protocol::VERSION ) );
		update_option( $marker_option, Protocol::VERSION, false );
	}

	private function init_registry() {
		if ( ! isset( $GLOBALS[ Protocol::REGISTRY_KEY ] ) || ! is_array( $GLOBALS[ Protocol::REGISTRY_KEY ] ) ) {
			$GLOBALS[ Protocol::REGISTRY_KEY ] = array(
				'participants' => array(),
				'claims' => array(),
				'diagnostics' => array( 'legacy' => array(), 'rejected' => array() ),
			);
		}
	}

	private function register_participant() {
		if ( ! Protocol::is_valid_descriptor( $this->descriptor ) ) {
			$GLOBALS[ Protocol::REGISTRY_KEY ]['diagnostics']['rejected'][] = $this->slug . ':invalid_descriptor';
			return;
		}
		if ( isset( $GLOBALS[ Protocol::REGISTRY_KEY ]['participants'][ $this->slug ] ) ) {
			$GLOBALS[ Protocol::REGISTRY_KEY ]['diagnostics']['rejected'][] = $this->slug . ':duplicate';
			return;
		}
		$GLOBALS[ Protocol::REGISTRY_KEY ]['participants'][ $this->slug ] = $this->descriptor;
		if ( class_exists( 'Garry_Promotion_Registry', false )
			&& ! in_array( 'Garry_Promotion_Registry', $GLOBALS[ Protocol::REGISTRY_KEY ]['diagnostics']['legacy'], true ) ) {
			$GLOBALS[ Protocol::REGISTRY_KEY ]['diagnostics']['legacy'][] = 'Garry_Promotion_Registry';
		}
	}

	public function run_election() {
		$GLOBALS[ Protocol::REGISTRY_KEY ]['claims'] = Election::run( $GLOBALS[ Protocol::REGISTRY_KEY ]['participants'] );
	}

	private function is_root_owner() {
		return isset( $GLOBALS[ Protocol::REGISTRY_KEY ]['claims']['root_menu'] )
			&& $GLOBALS[ Protocol::REGISTRY_KEY ]['claims']['root_menu'] === $this->slug;
	}

	private function participants() {
		return isset( $GLOBALS[ Protocol::REGISTRY_KEY ]['participants'] ) && is_array( $GLOBALS[ Protocol::REGISTRY_KEY ]['participants'] )
			? $GLOBALS[ Protocol::REGISTRY_KEY ]['participants']
			: array();
	}

	private function page_config( $display_name ) {
		return array(
			'slug' => $this->slug,
			'name' => $this->descriptor['name'],
			'display_name' => $display_name,
			'menu_slug' => $this->descriptor['local_menu_slug'],
			'capability' => $this->capability,
			'own_callback' => $this->own_callback,
			'own_tab_label' => $this->own_tab_label,
			'log_option' => $this->log_option,
			'plugin_dir' => $this->plugin_dir,
			'plugin_version' => $this->descriptor['plugin_version'],
			/**
			 * Historie verzí pro Info tab (viz LocalAdmin::render_info()) –
			 * standing pozadavek: kazda uprava pluginu se loguje i v adminu,
			 * ne jen v README.md.
			 */
			'changelog' => isset( $this->descriptor['changelog'] ) && is_array( $this->descriptor['changelog'] ) ? $this->descriptor['changelog'] : array(),
		);
	}

	private function group_config() {
		return array(
			'capability' => 'manage_options',
			'logo_url' => trailingslashit( $this->plugin_url ) . 'assets/garry-logo.svg',
			'framework_version' => Protocol::VERSION,
		);
	}

	public function add_shared_root_menu() {
		if ( ! $this->is_root_owner() ) return;
		$config = $this->group_config();

		/**
		 * Sestaveno JEDNOU a stejná reference předaná do obou volání níže.
		 * add_menu_page() a add_submenu_page() se stejným slugem jako parent
		 * je standardní WP trik na přejmenování výchozí položky top-level
		 * menu na "Přehled" – obě volání interně registrují add_action() na
		 * STEJNÝ hookname. Pokud by dostaly každé svou vlastní closure
		 * (function() use (...) {} vytváří pokaždé nový objekt), WordPress
		 * nemá jak poznat, že jde o "stejný" callback, a zavolá OBĚ – stránka
		 * se pak vykreslí dvakrát pod sebou (přesně tohle nahlásila zpětná
		 * vazba po nasazení). Se stejnou closure instancí WP registraci
		 * správně sloučí do jedné.
		 */
		$render_overview = function () use ( $config ) { GroupAdmin::render_overview( $config ); };

		add_menu_page(
			'GARRY nastavení',
			'GARRY nastavení',
			$config['capability'],
			'garry-nastaveni',
			$render_overview,
			/**
			 * 'none' místo přímé URL loga: garry-logo.svg je navržené pro
			 * velké zobrazení (stránka Info), ne pro 20×20 ikonu v menu.
			 * Skutečné vykreslení řeší maybe_enqueue_shared_shell_icons()
			 * přes ::before s explicitní velikostí a background-size:contain
			 * (viz zpětná vazba po nasazení – přímá URL logo přetékala).
			 */
			'none',
			81
		);
		add_submenu_page(
			'garry-nastaveni', 'Přehled', 'Přehled', $config['capability'],
			'garry-nastaveni',
			$render_overview
		);
		add_submenu_page(
			'garry-nastaveni', 'Info – GARRY Promotion', 'Info', $config['capability'],
			'garry-nastaveni-info',
			function () use ( $config ) { GroupAdmin::render_info( $config ); }
		);
	}

	public function add_plugin_submenu() {
		$config = $this->page_config( $this->descriptor['name'] );
		$label  = GroupAdmin::display_name( $this->descriptor['name'] );
		if ( $this->is_root_owner() || ! empty( $GLOBALS[ Protocol::REGISTRY_KEY ]['claims']['root_menu'] ) ) {
			add_submenu_page(
				'garry-nastaveni',
				$this->descriptor['name'],
				$label,
				$this->capability,
				$this->descriptor['local_menu_slug'],
				function () use ( $config ) { LocalAdmin::render( $config ); }
			);
			return;
		}
		add_menu_page(
			$this->descriptor['name'],
			$this->descriptor['name'],
			$this->capability,
			$this->descriptor['local_menu_slug'],
			function () use ( $config ) { LocalAdmin::render( $config ); },
			$this->descriptor['icon'],
			81
		);
	}

	public function maybe_enqueue_assets( $hook_suffix ) {
		$is_local = false !== strpos( (string) $hook_suffix, $this->descriptor['local_menu_slug'] );
		$is_group = $this->is_root_owner() && in_array( (string) $hook_suffix, array( 'toplevel_page_garry-nastaveni', 'garry-nastaveni_page_garry-nastaveni-info' ), true );
		if ( ! $is_local && ! $is_group ) return;
		wp_enqueue_style(
			'garry-v24-' . $this->slug,
			trailingslashit( $this->plugin_url ) . 'assets/framework-v23-admin.css',
			array(),
			Protocol::VERSION
		);
	}

	/**
	 * Barevná ikona hlavní položky a tematické ikony podpoložek v levém
	 * menu – vykreslené jako dynamické inline CSS (přes registrovaný
	 * handle a wp_add_inline_style(), ne surové echo v admin_head).
	 * Sidebar se zvýrazněnou hlavní položkou a náhledem podnabídky se
	 * vykresluje na KAŽDÉ administrační obrazovce, ne jen na vlastních
	 * stránkách GARRY – proto to (na rozdíl od maybe_enqueue_assets výše)
	 * NENÍ scoped na konkrétní $hook_suffix. Provádí ho výhradně zvolený
	 * vlastník root_menu, takže na webu běží jen jednou (docs/
	 * GARRY-CORE-FRAMEWORK-2.3-SPEC.md, sekce 7: "sdílené CSS pro group
	 * overview smí enqueue pouze jeho vlastník").
	 */
	public function maybe_enqueue_shared_shell_icons( $hook_suffix ) {
		if ( ! $this->is_root_owner() ) return;

		$logo_url = trailingslashit( $this->plugin_url ) . 'assets/garry-logo.svg';

		$css  = '#adminmenu .toplevel_page_garry-nastaveni .wp-menu-image{display:flex!important;align-items:center!important;justify-content:center!important;opacity:1!important;filter:none!important}';
		$css .= '#adminmenu .toplevel_page_garry-nastaveni .wp-menu-image img{display:none!important}';
		$css .= '#adminmenu .toplevel_page_garry-nastaveni .wp-menu-image:before{content:""!important;display:block!important;width:20px!important;height:20px!important;margin:0!important;opacity:1!important;filter:none!important;background-repeat:no-repeat!important;background-position:center center!important;background-size:contain!important;background-image:url(' . "'" . esc_url_raw( $logo_url ) . "'" . ')!important}';

		foreach ( $this->participants() as $slug => $p ) {
			$menu_slug = isset( $p['local_menu_slug'] ) ? $p['local_menu_slug'] : $slug;
			$icon      = isset( $p['icon'] ) ? $p['icon'] : 'dashicons-admin-generic';
			$glyph     = isset( self::DASHICON_GLYPHS[ $icon ] ) ? self::DASHICON_GLYPHS[ $icon ] : 'f111';
			$css .= '#adminmenu .toplevel_page_garry-nastaveni .wp-submenu a[href*="page=' . sanitize_html_class( $menu_slug ) . '"]::before{font-family:dashicons;content:"\\' . $glyph . '";display:inline-block;width:18px;margin-right:6px;color:currentColor;font-size:15px;line-height:1;vertical-align:-2px}';
		}
		$css .= '#adminmenu .toplevel_page_garry-nastaveni .wp-submenu a[href*="page=garry-nastaveni-info"]::before{font-family:dashicons;content:"\\f348";display:inline-block;width:18px;margin-right:6px;color:currentColor;font-size:15px;line-height:1;vertical-align:-2px}';

		wp_register_style( 'garry-v24-shell-icons', false, array(), Protocol::VERSION );
		wp_enqueue_style( 'garry-v24-shell-icons' );
		wp_add_inline_style( 'garry-v24-shell-icons', $css );
	}
}
