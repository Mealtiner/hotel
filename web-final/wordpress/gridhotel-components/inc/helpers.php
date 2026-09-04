<?php
/**
 * GRID Hotel Components — sdílené helpery pro shortcody (verze 1.0.0).
 *
 * Vše prefixované `gridc_`, NIKDY `grid_` — child theme (dosud nezměněný,
 * viz GRID-SUITE-09, upravuje se až jako poslední krok celé sady) definuje
 * globální funkce se jmény `grid_img()`, `grid_lang()`, `grid_nav_url()` atd.
 * Stejné jméno funkce v aktivním pluginu i aktivním theme by byl PHP fatal
 * (cannot redeclare) — proto úplně jiný prefix, dokud theme nezmizí.
 *
 * Čtení globálních hodnot (kontakt, hero, sociální sítě, video) jde přes
 * `gridhotel_get_option()` (GARRY – GRID Core), nikdy přímo přes get_field()
 * — GRID-SUITE-02 §8.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Výchozí obrázky/loga — dočasně stále fyzicky v theme (viz GRID-SUITE-09 §12, migrace na Media Library je pozdější krok, filtrovatelné odsud). */
function gridc_fallback_image( $file ) {
	$base = apply_filters( 'gridhotel_components_fallback_image_base', function_exists( 'get_stylesheet_directory_uri' ) ? get_stylesheet_directory_uri() . '/assets/foto/' : '' );
	return $base . $file;
}
function gridc_fallback_logo( $file ) {
	$base = apply_filters( 'gridhotel_components_fallback_logo_base', function_exists( 'get_stylesheet_directory_uri' ) ? get_stylesheet_directory_uri() . '/assets/logo/' : '' );
	return $base . $file;
}

/** Aktuální jazyk (Polylang, jinak site locale). */
function gridc_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language();
		if ( $l ) {
			return $l;
		}
	}
	return substr( (string) get_locale(), 0, 2 );
}
function gridc_lang_index() {
	return array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ gridc_lang() ] ?? 0;
}

/**
 * Kotvy v menu/patičce: na homepage bare #kotva (plynulý scroll), na
 * podstránkách předsazeno home_url(). #kontakt vždy míří na podstránku
 * kontaktu, pokud existuje.
 */
function gridc_nav_url( $url ) {
	if ( ! is_string( $url ) || '' === $url || '#' !== $url[0] ) {
		return $url;
	}
	if ( '#kontakt' === $url ) {
		$k = gridc_detail_url( array( 'kontakt' ) );
		if ( $k ) {
			return $k;
		}
		return is_front_page() ? $url : home_url( '/' ) . $url;
	}
	if ( is_front_page() ) {
		return $url;
	}
	return home_url( '/' ) . $url;
}

/** Najde URL podstránky podle seznamu kandidátních slugů (první existující). */
function gridc_detail_url( $slugs ) {
	if ( ! function_exists( 'get_page_by_path' ) ) {
		return '';
	}
	foreach ( (array) $slugs as $s ) {
		$p = get_page_by_path( $s );
		if ( $p ) {
			return get_permalink( $p );
		}
	}
	return '';
}
/** Odkaz preferenčně na podstránku, jinak fallback na kotvu. */
function gridc_link_pref( $slugs, $anchor ) {
	$u = gridc_detail_url( $slugs );
	return $u ? $u : gridc_nav_url( $anchor );
}

/** Jemný CTA „na detailní stránku" — jen na titulní stránce a jen pokud podstránka existuje. */
function gridc_section_more( $slugs, $label = 'Zobrazit více' ) {
	if ( ! is_front_page() ) {
		return '';
	}
	$url = gridc_detail_url( $slugs );
	if ( ! $url ) {
		return '';
	}
	return '<a class="sec-more" href="' . esc_url( $url ) . '">' . esc_html( $label ) . ' <span aria-hidden="true">→</span></a>';
}

/** Cíl všech rezervačních CTA. */
function gridc_rezervace_url() {
	$u = gridc_detail_url( array( 'rezervace' ) );
	if ( $u && function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' ) ) {
		$p = get_page_by_path( 'rezervace' );
		if ( $p ) {
			$t = pll_get_post( $p->ID, pll_current_language() );
			if ( $t ) {
				return get_permalink( $t );
			}
		}
	}
	if ( $u ) {
		return $u;
	}
	$opt = function_exists( 'gridhotel_get_option' ) ? trim( (string) gridhotel_get_option( 'rezervace_url', '' ) ) : '';
	if ( $opt ) {
		return $opt;
	}
	return home_url( '/#booking' );
}

/**
 * YouTube/Vimeo → embed URL. Bezpečnostní allowlist — jen tyto dvě domény,
 * i když je zdrojové pole editovatelné jen administrátorem (žádný cizí
 * iframe/tracker).
 */
function gridc_video_embed( $url ) {
	$url = trim( (string) $url );
	if ( ! $url ) {
		return '';
	}
	if ( preg_match( '~youtu\.be/([\w-]+)~', $url, $m )
		|| preg_match( '~youtube\.com/watch\?v=([\w-]+)~', $url, $m )
		|| preg_match( '~youtube\.com/embed/([\w-]+)~', $url, $m ) ) {
		return 'https://www.youtube.com/embed/' . $m[1];
	}
	if ( preg_match( '~(?:vimeo\.com|player\.vimeo\.com/video)/(\d+)~', $url, $m ) ) {
		return 'https://player.vimeo.com/video/' . $m[1];
	}
	return '';
}

/** Odkazy na sociální sítě z Core → jen ty vyplněné. */
function gridc_social_links() {
	$map = array(
		'soc_facebook'  => array( 'Facebook', 'FB' ),
		'soc_instagram' => array( 'Instagram', 'IG' ),
		'soc_youtube'   => array( 'YouTube', 'YT' ),
		'soc_linkedin'  => array( 'LinkedIn', 'IN' ),
		'soc_tiktok'    => array( 'TikTok', 'TT' ),
		'soc_x'         => array( 'X / Twitter', 'X' ),
	);
	$out = array();
	foreach ( $map as $field => $meta ) {
		$u = function_exists( 'gridhotel_get_option' ) ? trim( (string) gridhotel_get_option( $field, '' ) ) : '';
		if ( $u ) {
			$out[] = array( 'url' => $u, 'label' => $meta[0], 'short' => $meta[1] );
		}
	}
	return $out;
}

/**
 * Odkazy jazykového přepínače (CZ/EN/DE) — GRID-SUITE-02 §7 vyžaduje, aby
 * shortcody fungovaly i bez JS. Baseline (theme) tohle řešilo opačně: server
 * jen vypsal statické href="#" a teprve JS (window.gridLangUrls z theme
 * wp_head) je za běhu přepsal na reálné URL — bez JS tedy přepínač
 * nefungoval vůbec. Tady se URL počítají rovnou v PHP (Polylang), takže
 * fungují i bez JS a odpadá zbytečná mezikrokem přes globální JS proměnnou.
 *
 * @return array<string,string> jazyk => URL (jen jazyky, které Polylang skutečně má).
 */
function gridc_lang_switch_urls() {
	if ( ! function_exists( 'pll_the_languages' ) && ! function_exists( 'pll_get_post' ) ) {
		return array();
	}
	$urls = array();
	foreach ( array( 'cs', 'en', 'de' ) as $l ) {
		$u = '';
		if ( is_singular() ) {
			$t = function_exists( 'pll_get_post' ) ? pll_get_post( get_queried_object_id(), $l ) : 0;
			if ( $t && 'publish' === get_post_status( $t ) ) {
				$u = get_permalink( $t );
			}
		} elseif ( is_tax() && function_exists( 'pll_get_term' ) ) {
			$t = pll_get_term( get_queried_object_id(), $l );
			if ( $t ) {
				$link = get_term_link( (int) $t );
				if ( ! is_wp_error( $link ) ) {
					$u = $link;
				}
			}
		}
		if ( ! $u && function_exists( 'pll_home_url' ) ) {
			$u = pll_home_url( $l );
		}
		if ( $u ) {
			$urls[ $l ] = $u;
		}
	}
	return $urls;
}

/** row_val — shodný tvar dat jako theme repeaters (asociativní pole s fallbackem). */
function gridc_row_val( $row, $key, $fallback = '' ) {
	if ( is_array( $row ) && isset( $row[ $key ] ) && '' !== $row[ $key ] && null !== $row[ $key ] ) {
		return $row[ $key ];
	}
	return $fallback;
}

/** Rozdělí interní formát "a|b|c" na pole. */
function gridc_pipe_list( $raw ) {
	if ( '' === (string) $raw ) {
		return array();
	}
	return array_values( array_filter( array_map( 'trim', explode( '|', (string) $raw ) ) ) );
}
