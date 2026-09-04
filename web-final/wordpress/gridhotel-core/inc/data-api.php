<?php
/**
 * GRID Hotel Core — stabilní, jen-ke-čtení datové API (verze 2.0.0).
 *
 * Components a GARRY pluginy mají číst hotelová data VÝHRADNĚ přes tyhle
 * funkce, ne přímo přes WP_Query/get_field() nad CPT `grid_room` apod. —
 * návratový tvar je vždy prosté pole/DTO (nikdy syrový WP_Post), aby
 * konzument nezávisel na tom, jak přesně jsou data uložená (ACF vs. term
 * meta vs. cokoli budoucího). Každá funkce běží filtr `gridhotel_*_data` po
 * načtení hodnot, ale před vrácením volajícímu — tam se dá bezpečně doplnit
 * vlastní pole, aniž by se muselo sahat do samotného dotazu.
 *
 * Fáze 1 zatím nezavádí perzistentní cache vrstvu (viz GRID-SUITE-01 §10) –
 * každé volání je čerstvý dotaz. Cache/invalidace je vyhrazená pro
 * GRID Hotel Components (fáze 2), kde skutečně vzniká opakované volání
 * ve stejném requestu.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @param array $args { limit?:int, category?:string (slug grid_room_cat) }
 * @return array<int,array>
 */
function gridhotel_get_rooms( array $args = array() ) {
	$args = wp_parse_args( $args, array( 'limit' => -1, 'category' => '' ) );

	$query_args = array(
		'post_type'      => 'grid_room',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( ! empty( $args['category'] ) ) {
		$query_args['tax_query'] = array( array(
			'taxonomy' => 'grid_room_cat',
			'field'    => 'slug',
			'terms'    => (string) $args['category'],
		) );
	}

	$out = array();
	foreach ( get_posts( $query_args ) as $post ) {
		$terms = wp_get_post_terms( $post->ID, 'grid_room_cat', array( 'fields' => 'slugs' ) );
		$out[] = apply_filters( 'gridhotel_room_data', array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'category' => ( is_array( $terms ) && ! empty( $terms ) ) ? $terms[0] : '',
			'gallery'  => gridcore_get_field_safe( 'galerie', $post->ID, array() ),
			'note'     => (string) gridcore_get_field_safe( 'pozn', $post->ID, '' ),
			'url'      => get_permalink( $post ),
		), $post, $args );
	}
	return $out;
}

/**
 * @param array $args { hide_empty?:bool }
 * @return array<int,array> Seřazeno podle 'poradi'. Pole jsou CZ-only – Core
 *         zatím nemá pro kategorie žádné _en/_de term-meta varianty
 *         (lokalizovaný obsah k porovnání dnes vlastní plugin Kategorie
 *         pokojů, ne Core term meta), takže parametr locale tu zatím nemá co
 *         přepínat a záměrně chybí.
 */
function gridhotel_get_room_categories( array $args = array() ) {
	$args  = wp_parse_args( $args, array( 'hide_empty' => false ) );
	$terms = get_terms( array( 'taxonomy' => 'grid_room_cat', 'hide_empty' => (bool) $args['hide_empty'] ) );
	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	$out = array();
	foreach ( $terms as $term ) {
		$selector = 'grid_room_cat_' . $term->term_id;
		$get      = function ( $field, $default = '' ) use ( $term, $selector ) {
			return gridcore_get_term_field_safe( $field, $term->term_id, $selector, $default );
		};
		$link = get_term_link( $term );

		$out[] = apply_filters( 'gridhotel_room_category_data', array(
			'id'                => $term->term_id,
			'slug'              => $term->slug,
			'name'              => $term->name,
			'code'              => (string) $get( 'kod' ),
			'order'             => (int) $get( 'poradi', 0 ),
			'short_description' => (string) $get( 'kratky_popis' ),
			'description'       => (string) $get( 'popis' ),
			'tags'              => gridcore_pipe_list( (string) $get( 'stitky' ) ),
			'count'             => $get( 'pocet' ),
			'capacity'          => (string) $get( 'kapacita' ),
			'size'              => (string) $get( 'velikost' ),
			'bed'               => (string) $get( 'postel' ),
			'bathroom'          => gridcore_pipe_list( (string) $get( 'koupelna' ) ),
			'amenities'         => gridcore_pipe_list( (string) $get( 'zarizeni' ) ),
			'image'             => (string) $get( 'nahled' ),
			'gallery'           => gridcore_get_field_safe( 'galerie', $selector, array() ),
			'url'               => is_wp_error( $link ) ? '' : $link,
		), $term, $args );
	}

	usort( $out, function ( $a, $b ) {
		return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
	} );
	return $out;
}

/**
 * @param array $args { limit?:int, locale?:string (Polylang jazykový slug, je-li Polylang aktivní) }
 * @return array<int,array>
 */
function gridhotel_get_experiences( array $args = array() ) {
	$args = wp_parse_args( $args, array( 'limit' => -1, 'locale' => null ) );

	$query_args = array(
		'post_type'      => 'grid_experience',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( ! empty( $args['locale'] ) && function_exists( 'pll_get_post' ) ) {
		$query_args['lang'] = $args['locale'];
	}

	$out = array();
	foreach ( get_posts( $query_args ) as $post ) {
		$out[] = apply_filters( 'gridhotel_experience_data', array(
			'id'          => $post->ID,
			'title'       => get_the_title( $post ),
			'featured'    => (bool) gridcore_get_field_safe( 'doporuceno', $post->ID, false ),
			'number'      => (string) gridcore_get_field_safe( 'num', $post->ID, '' ),
			'text'        => (string) gridcore_get_field_safe( 'text', $post->ID, '' ),
			'cta'         => (string) gridcore_get_field_safe( 'cta', $post->ID, '' ),
			'link'        => (string) gridcore_get_field_safe( 'odkaz', $post->ID, '' ),
			'description' => (string) gridcore_get_field_safe( 'popis', $post->ID, '' ),
			'perex'       => (string) gridcore_get_field_safe( 'perex', $post->ID, '' ),
			'gallery'     => gridcore_get_field_safe( 'galerie', $post->ID, array() ),
			'url'         => get_permalink( $post ),
		), $post, $args );
	}
	return $out;
}

/**
 * @param array $args { limit?:int }
 * @return array<int,array>
 */
function gridhotel_get_gastro_locations( array $args = array() ) {
	$args  = wp_parse_args( $args, array( 'limit' => -1 ) );
	$posts = get_posts( array(
		'post_type'      => 'grid_gastro',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	$out = array();
	foreach ( $posts as $post ) {
		$out[] = apply_filters( 'gridhotel_gastro_data', array(
			'id'    => $post->ID,
			'title' => get_the_title( $post ),
			'hours' => (string) gridcore_get_field_safe( 'hours', $post->ID, '' ),
			'text'  => (string) gridcore_get_field_safe( 'text', $post->ID, '' ),
			'list'  => gridcore_pipe_list( (string) gridcore_get_field_safe( 'list', $post->ID, '' ) ),
			'url'   => get_permalink( $post ),
		), $post, $args );
	}
	return $out;
}

/**
 * @param array $args { limit?:int }
 * @return array<int,array> Doplněno při stavbě GRID Hotel Components (fáze 2)
 *         — GRID-SUITE-02 §8 vyžaduje číst reference přes Core API, ne přímo
 *         přes CPT dotaz, stejně jako pokoje/zážitky/gastro výše.
 */
function gridhotel_get_testimonials( array $args = array() ) {
	$args  = wp_parse_args( $args, array( 'limit' => -1 ) );
	$posts = get_posts( array(
		'post_type'      => 'grid_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	$out = array();
	foreach ( $posts as $post ) {
		$out[] = apply_filters( 'gridhotel_testimonial_data', array(
			'id'   => $post->ID,
			'text' => (string) gridcore_get_field_safe( 'text', $post->ID, '' ),
			'who'  => (string) gridcore_get_field_safe( 'who', $post->ID, '' ),
		), $post, $args );
	}
	return $out;
}

/**
 * @param array $args { limit?:int, locale?:string }
 * @return array<int,array> Doplněno při stavbě GRID Hotel Components (fáze 2)
 *         — GRID-SUITE-02 §8 vyžaduje číst kariéru přes Core API.
 */
function gridhotel_get_careers( array $args = array() ) {
	$args = wp_parse_args( $args, array( 'limit' => -1, 'locale' => null ) );

	$query_args = array(
		'post_type'      => 'grid_job',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $args['limit'],
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	);
	if ( ! empty( $args['locale'] ) && function_exists( 'pll_get_post' ) ) {
		$query_args['lang'] = $args['locale'];
	}

	$out = array();
	foreach ( get_posts( $query_args ) as $post ) {
		$out[] = apply_filters( 'gridhotel_career_data', array(
			'id'         => $post->ID,
			'title'      => get_the_title( $post ),
			'contract'   => (string) gridcore_get_field_safe( 'uvazek', $post->ID, '' ),
			'location'   => (string) gridcore_get_field_safe( 'misto', $post->ID, '' ),
			'salary'     => (string) gridcore_get_field_safe( 'mzda', $post->ID, '' ),
			'description'=> (string) gridcore_get_field_safe( 'popis', $post->ID, '' ),
			'email'      => (string) gridcore_get_field_safe( 'email', $post->ID, 'info@gridhotel.cz' ),
		), $post, $args );
	}
	return $out;
}

/**
 * @return array<int,array{name:string,images:array}> Bloky galerie z group_grid_options
 *         (repeater `galerie_bloky`) — Doplněno pro GRID Hotel Components,
 *         aby [grid_galerie] nečetlo have_rows()/get_sub_field() přímo (spec
 *         GRID-SUITE-02 §8). Bez ACF vrací prázdné pole (žádná gallery-UI
 *         náhrada bez Media Library integrace, viz GRID-SUITE-01 §6.2).
 */
function gridhotel_get_gallery_blocks() {
	if ( ! function_exists( 'have_rows' ) || ! have_rows( 'galerie_bloky', 'option' ) ) {
		return array();
	}
	$blocks = array();
	while ( have_rows( 'galerie_bloky', 'option' ) ) {
		the_row();
		$name   = (string) get_sub_field( 'nazev' );
		$fotky  = get_sub_field( 'fotky' );
		$images = array();
		if ( is_array( $fotky ) ) {
			foreach ( $fotky as $img ) {
				if ( is_array( $img ) ) {
					$full  = ! empty( $img['url'] ) ? $img['url'] : '';
					$thumb = ! empty( $img['sizes']['large'] ) ? $img['sizes']['large'] : $full;
				} elseif ( is_numeric( $img ) ) {
					$full  = wp_get_attachment_image_url( $img, 'full' );
					$thumb = wp_get_attachment_image_url( $img, 'large' );
				} else {
					$full = $thumb = '';
				}
				if ( $full ) {
					$images[] = array( 'full' => $full, 'thumb' => $thumb ?: $full );
				}
			}
		}
		$blocks[] = apply_filters( 'gridhotel_gallery_block_data', array(
			'name'   => $name ?: 'Galerie',
			'images' => $images,
		) );
	}
	return $blocks;
}

/** ACF-optional čtení pole u příspěvku — bez ACF vrací $default místo fatal/false. */
function gridcore_get_field_safe( $field, $post_id, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $field, $post_id );
	return ( false === $value || null === $value ) ? $default : $value;
}

/** ACF-optional čtení term meta — ACF selector, je-li ACF dostupné, jinak přímo get_term_meta(). */
function gridcore_get_term_field_safe( $field, $term_id, $acf_selector, $default = null ) {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $field, $acf_selector );
		return ( false === $value || null === $value || '' === $value ) ? $default : $value;
	}
	$value = get_term_meta( $term_id, $field, true );
	return ( '' === $value ) ? $default : $value;
}

/** Rozdělí interní formát "a|b|c" na pole, ořízne prázdné položky. */
function gridcore_pipe_list( $raw ) {
	if ( '' === $raw ) {
		return array();
	}
	return array_values( array_filter( array_map( 'trim', explode( '|', $raw ) ) ) );
}
