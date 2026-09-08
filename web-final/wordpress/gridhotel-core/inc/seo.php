<?php
/**
 * SEO doplňky, které Yoast ani Polylang samy nedodají.
 *
 * 1) hreflang pro stránky kategorií pokojů — Polylang je pro tuhle taxonomii
 *    nevypisuje, ačkoli jsou překlady termů propojené.
 * 2) Strukturovaná data typu LodgingBusiness — Yoast umí Organization,
 *    ale pro hotel je nejcennější typ s adresou, telefonem a check-inem.
 *
 * @package GRID Core
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** hreflang pro kategorie pokojů (WCAG/SEO — jazykové varianty stránky). */
add_action( 'wp_head', function () {
	if ( ! is_tax( 'grid_room_cat' ) || ! function_exists( 'pll_get_term' ) ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	$vypis = '';
	foreach ( array( 'cs', 'en', 'de' ) as $lang ) {
		$id = pll_get_term( $term->term_id, $lang );
		if ( ! $id ) {
			continue;
		}
		$url = get_term_link( (int) $id, 'grid_room_cat' );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$vypis .= sprintf(
			'<link rel="alternate" hreflang="%s" href="%s">' . "\n",
			esc_attr( $lang ),
			esc_url( $url )
		);
	}
	echo $vypis; // phpcs:ignore WordPress.Security.EscapeOutput -- sestaveno z esc_* výše
}, 5 );

/** Hotel jako LodgingBusiness — doplněk k Organization od Yoastu. */
add_action( 'wp_head', function () {
	if ( ! is_front_page() && ! is_page( array( 'kontakt', 'contact', 'kontakt-de' ) ) ) {
		return;
	}
	$telefon = gridhotel_get_option( 'telefon_recepce', '+420 775 877 721' );
	$email   = gridhotel_get_option( 'email_recepce', 'info@gridhotel.cz' );

	$schema = array(
		'@context'      => 'https://schema.org',
		'@type'         => 'LodgingBusiness',
		'@id'           => home_url( '/#hotel' ),
		'name'          => 'GRID HOTEL',
		'url'           => home_url( '/' ),
		'telephone'     => $telefon,
		'email'         => $email,
		'starRating'    => array( '@type' => 'Rating', 'ratingValue' => '4' ),
		'priceRange'    => '$$',
		'address'       => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'Ostrovačická 936/65',
			'addressLocality' => 'Brno-Žebětín',
			'postalCode'      => '641 00',
			'addressCountry'  => 'CZ',
		),
		'geo'           => array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 49.2043,
			'longitude' => 16.4471,
		),
		'checkinTime'   => '14:00',
		'checkoutTime'  => '10:00',
		'numberOfRooms' => 64,
		'amenityFeature' => array(
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Parkování zdarma', 'value' => true ),
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Wi-Fi zdarma', 'value' => true ),
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Restaurace', 'value' => true ),
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Bar', 'value' => true ),
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Bezbariérový přístup', 'value' => true ),
			array( '@type' => 'LocationFeatureSpecification', 'name' => 'Recepce 24/7', 'value' => true ),
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 6 );
