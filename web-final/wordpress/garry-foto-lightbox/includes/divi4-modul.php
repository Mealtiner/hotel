<?php
/**
 * Divi Builder modul „GARRY – Foto lightbox".
 *
 * Načítá se JEN na Divi 4 — strážní funkce garry_divi4_legacy_builder()
 * v hlavním souboru. Na Divi 5 by registrace legacy modulu vynutila načtení
 * celého Divi 4 frameworku a přepnula stránky do kompatibilního režimu.
 * Na Divi 5 se lightbox používá shortcodem v modulu Text nebo Kód — a hlavně
 * se aktivuje sám nad odkazy na obrázky, takže vkládat nemusíte vůbec nic.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'ET_Builder_Module' ) ) return;
if ( class_exists( 'GFLB_Divi_Lightbox_Module' ) ) return;

class GFLB_Divi_Lightbox_Module extends ET_Builder_Module {

	public $slug       = 'gflb_lightbox';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://www.garry.cz',
		'author'     => 'GARRY Promotion',
		'author_uri' => 'https://www.garry.cz',
	);

	public function init() {
		$this->name = esc_html__( 'GARRY – Foto lightbox', 'garry-foto-lightbox' );
	}

	public function get_fields() {
		return array(
			'rezim' => array(
				'label'           => esc_html__( 'Režim', 'garry-foto-lightbox' ),
				'type'            => 'select',
				'option_category' => 'basic_option',
				'options'         => array(
					'aktivace' => esc_html__( 'Jen aktivovat lightbox na stránce', 'garry-foto-lightbox' ),
					'galerie'  => esc_html__( 'Vykreslit mřížku náhledů', 'garry-foto-lightbox' ),
				),
				'default'     => 'aktivace',
				'toggle_slug' => 'main_content',
				'description' => esc_html__( 'V režimu aktivace modul nic nevykreslí — lightbox převezme odkazy na obrázky v celé stránce.', 'garry-foto-lightbox' ),
			),
			'ids' => array(
				'label'           => esc_html__( 'ID obrázků', 'garry-foto-lightbox' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'main_content',
				'show_if'         => array( 'rezim' => 'galerie' ),
				'description'     => esc_html__( 'Čísla příloh oddělená čárkou, například 12,13,14.', 'garry-foto-lightbox' ),
			),
			'sloupce' => array(
				'label'           => esc_html__( 'Sloupce', 'garry-foto-lightbox' ),
				'type'            => 'range',
				'option_category' => 'layout',
				'range_settings'  => array( 'min' => 1, 'max' => 8, 'step' => 1 ),
				'default'         => '3',
				'unitless'        => true,
				'toggle_slug'     => 'main_content',
				'show_if'         => array( 'rezim' => 'galerie' ),
			),
			'nadpis_sablona' => array(
				'label'           => esc_html__( 'Text nad snímkem', 'garry-foto-lightbox' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'default'         => '',
				'toggle_slug'     => 'main_content',
				'description'     => esc_html__( 'Nepovinné. Značky: {web}, {galerie}, {index}, {celkem}. Prázdné = platí globální nastavení.', 'garry-foto-lightbox' ),
			),
		);
	}

	public function render( $unprocessed_props, $content, $render_slug ) {
		$sablona = trim( (string) $this->props['nadpis_sablona'] );
		if ( $sablona !== '' ) {
			$prepis = &gflb_prepis();
			$prepis['nadpis_sablona'] = $sablona;
		}
		if ( ( $this->props['rezim'] ?? 'aktivace' ) !== 'galerie' ) {
			gflb_render_kontejner();
			return '';
		}
		return gflb_sc_galerie( array(
			'ids'     => (string) $this->props['ids'],
			'sloupce' => (int) $this->props['sloupce'],
		) );
	}
}

new GFLB_Divi_Lightbox_Module();
