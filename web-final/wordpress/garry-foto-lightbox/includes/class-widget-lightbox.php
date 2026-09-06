<?php
/**
 * Elementor widget „GARRY – Foto lightbox".
 *
 * Widget nevykresluje vlastní obsah galerie povinně — jeho hlavní úkol je
 * aktivovat lightbox na stránce a případně přenastavit jeho vzhled jen tady.
 * Volitelně umí vykreslit i vlastní mřížku náhledů (stejnou jako shortcode
 * [garry_lightbox_galerie]), aby šel použít samostatně.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class GFLB_Widget_Lightbox extends \Elementor\Widget_Base {

	public function get_name() { return 'garry-foto-lightbox'; }
	public function get_title() { return __( 'GARRY – Foto lightbox', 'garry-foto-lightbox' ); }
	public function get_icon() { return 'eicon-lightbox'; }
	public function get_categories() { return array( 'general' ); }
	public function get_keywords() { return array( 'lightbox', 'galerie', 'foto', 'garry' ); }
	public function get_style_depends() { return array( 'garry-foto-lightbox' ); }
	public function get_script_depends() { return array( 'garry-foto-lightbox' ); }

	protected function register_controls() {

		$this->start_controls_section( 'obsah', array(
			'label' => __( 'Galerie', 'garry-foto-lightbox' ),
		) );

		$this->add_control( 'rezim', array(
			'label'   => __( 'Režim', 'garry-foto-lightbox' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'aktivace',
			'options' => array(
				'aktivace' => __( 'Jen aktivovat lightbox na stránce', 'garry-foto-lightbox' ),
				'galerie'  => __( 'Vykreslit mřížku náhledů', 'garry-foto-lightbox' ),
			),
			'description' => __( 'V režimu aktivace widget nic nevykreslí — lightbox jen převezme odkazy na obrázky v celé stránce.', 'garry-foto-lightbox' ),
		) );

		$this->add_control( 'obrazky', array(
			'label'     => __( 'Obrázky', 'garry-foto-lightbox' ),
			'type'      => \Elementor\Controls_Manager::GALLERY,
			'default'   => array(),
			'condition' => array( 'rezim' => 'galerie' ),
		) );

		$this->add_responsive_control( 'sloupce', array(
			'label'     => __( 'Sloupce', 'garry-foto-lightbox' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'min'       => 1,
			'max'       => 8,
			'default'   => 3,
			'condition' => array( 'rezim' => 'galerie' ),
		) );

		$this->add_responsive_control( 'mezera', array(
			'label'     => __( 'Mezera (px)', 'garry-foto-lightbox' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'min'       => 0,
			'max'       => 80,
			'default'   => 12,
			'condition' => array( 'rezim' => 'galerie' ),
		) );

		$this->end_controls_section();

		/* --- přenastavení vzhledu jen pro tuto stránku --- */
		$this->start_controls_section( 'vzhled', array(
			'label' => __( 'Vzhled lightboxu (jen tato stránka)', 'garry-foto-lightbox' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		) );

		$this->add_control( 'prepsat', array(
			'label'        => __( 'Přenastavit globální vzhled', 'garry-foto-lightbox' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'default'      => '',
			'description'  => __( 'Bez zapnutí platí nastavení z GARRY nastavení → Foto lightbox.', 'garry-foto-lightbox' ),
		) );

		$this->add_control( 'pozadi_typ', array(
			'label'     => __( 'Typ pozadí', 'garry-foto-lightbox' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'linear',
			'options'   => array(
				'solid'  => __( 'Plná barva', 'garry-foto-lightbox' ),
				'linear' => __( 'Lineární přechod', 'garry-foto-lightbox' ),
				'radial' => __( 'Radiální přechod', 'garry-foto-lightbox' ),
				'conic'  => __( 'Kónický přechod', 'garry-foto-lightbox' ),
				'rohy'   => __( 'Barva v každém rohu', 'garry-foto-lightbox' ),
			),
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'pozadi_barva1', array(
			'label' => __( 'Barva 1', 'garry-foto-lightbox' ),
			'type'  => \Elementor\Controls_Manager::COLOR,
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'pozadi_barva2', array(
			'label' => __( 'Barva 2', 'garry-foto-lightbox' ),
			'type'  => \Elementor\Controls_Manager::COLOR,
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'pozadi_uhel', array(
			'label'   => __( 'Úhel přechodu', 'garry-foto-lightbox' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'min'     => 0, 'max' => 360, 'default' => 225,
			'condition' => array( 'prepsat' => 'yes', 'pozadi_typ' => array( 'linear', 'conic' ) ),
		) );
		$this->add_control( 'logo_zobrazit', array(
			'label'   => __( 'Logo webu', 'garry-foto-lightbox' ),
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'nadpis_sablona', array(
			'label'       => __( 'Text nad snímkem', 'garry-foto-lightbox' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => '{web} — {galerie} — {index}/{celkem}',
			'description' => __( 'Značky: {web}, {galerie}, {index}, {celkem}, {titulek}, {popisek}, {popis}, {alt}.', 'garry-foto-lightbox' ),
			'condition'   => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'ukazatel_zobrazit', array(
			'label'   => __( 'Ukazatel pořadí', 'garry-foto-lightbox' ),
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'ukazatel_barva_vypln', array(
			'label' => __( 'Barva projité části', 'garry-foto-lightbox' ),
			'type'  => \Elementor\Controls_Manager::COLOR,
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'sipky_zobrazit', array(
			'label'   => __( 'Šipky', 'garry-foto-lightbox' ),
			'type'    => \Elementor\Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => array( 'prepsat' => 'yes' ),
		) );
		$this->add_control( 'sipky_barva', array(
			'label' => __( 'Barva šipek', 'garry-foto-lightbox' ),
			'type'  => \Elementor\Controls_Manager::COLOR,
			'condition' => array( 'prepsat' => 'yes' ),
		) );

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		if ( ! empty( $s['prepsat'] ) && $s['prepsat'] === 'yes' ) {
			$prepis = &gflb_prepis();
			foreach ( array( 'pozadi_typ', 'pozadi_barva1', 'pozadi_barva2', 'pozadi_uhel',
				'nadpis_sablona', 'ukazatel_barva_vypln', 'sipky_barva' ) as $klic ) {
				if ( isset( $s[ $klic ] ) && $s[ $klic ] !== '' ) $prepis[ $klic ] = $s[ $klic ];
			}
			foreach ( array( 'logo_zobrazit', 'ukazatel_zobrazit', 'sipky_zobrazit' ) as $klic ) {
				if ( isset( $s[ $klic ] ) ) $prepis[ $klic ] = ( $s[ $klic ] === 'yes' ) ? 1 : 0;
			}
		}

		if ( ( $s['rezim'] ?? 'aktivace' ) !== 'galerie' ) {
			// Kontejner vykreslí wp_footer; widget sám nic nevypisuje.
			return;
		}

		$ids = array();
		foreach ( (array) ( $s['obrazky'] ?? array() ) as $obr ) {
			if ( ! empty( $obr['id'] ) ) $ids[] = (int) $obr['id'];
		}
		if ( ! $ids ) return;

		echo gflb_sc_galerie( array( // phpcs:ignore -- shortcode escapuje vlastní výstup
			'ids'     => implode( ',', $ids ),
			'sloupce' => (int) ( $s['sloupce'] ?? 3 ),
			'mezera'  => (int) ( $s['mezera'] ?? 12 ),
		) );
	}
}
