<?php
/**
 * GARRY Security – robots.txt (přes nativní WordPress robots_txt filtr,
 * nikdy zápis do souboru) a llms.txt (vlastní, GARRY-spravovaný virtuální
 * výstup – pro tenhle formát neexistuje formální standard ani jádrová
 * podpora WordPressu, viz komentář níže).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const GARRY_SECURITY_ROBOTS_OPTION = 'garry_security_robots_settings';
const GARRY_SECURITY_LLMS_OPTION   = 'garry_security_llms_settings';

/** === robots.txt ========================================================= */

/**
 * Existuje fyzický soubor robots.txt v kořeni webu? Pokud ano, WordPress
 * svůj dynamický výstup (a tedy i tenhle filtr) vůbec nepoužije – server
 * takový soubor obslouží přímo, request se ke WordPressu nedostane.
 */
function garry_security_robots_physical_file_exists() {
	return file_exists( ABSPATH . 'robots.txt' );
}

function garry_security_robots_settings() {
	$defaults = array(
		'block_ai_crawlers'    => false,
		'add_sitemap_reference' => false,
		'disallow_search'      => false,
		'disallow_rest_api'    => false,
	);
	$stored = get_option( GARRY_SECURITY_ROBOTS_OPTION, array() );
	return is_array( $stored ) ? array_merge( $defaults, $stored ) : $defaults;
}

/**
 * Seznam User-agent řetězců známých AI/LLM crawlerů pro trénink modelů
 * (ne běžné vyhledávací roboty jako Googlebot – ty blokovat nechceme).
 * Dodržování robots.txt je u těchto crawlerů dobrovolné z jejich strany,
 * stejně jako u klasického robots.txt obecně – tohle je žádost, ne
 * technická blokace.
 */
function garry_security_ai_crawler_user_agents() {
	return array( 'GPTBot', 'ChatGPT-User', 'CCBot', 'ClaudeBot', 'anthropic-ai', 'Google-Extended', 'Bytespider', 'PerplexityBot' );
}

/**
 * Připojené na konec WordPressem vygenerovaného robots.txt – nikdy ho
 * nenahrazuje, jen doplňuje o volitelné, výslovně zapnuté řádky.
 */
function garry_security_robots_txt_filter( $output, $public ) {
	if ( garry_security_robots_physical_file_exists() ) {
		return $output; // sem se stejně nikdy nedostane – server obslouží fyzický soubor dřív.
	}
	$settings = garry_security_robots_settings();
	$extra    = '';

	if ( ! empty( $settings['block_ai_crawlers'] ) ) {
		foreach ( garry_security_ai_crawler_user_agents() as $ua ) {
			$extra .= "\nUser-agent: {$ua}\nDisallow: /\n";
		}
	}
	if ( ! empty( $settings['disallow_search'] ) ) {
		$extra .= "\nDisallow: /?s=\n";
	}
	if ( ! empty( $settings['disallow_rest_api'] ) ) {
		$extra .= "\nDisallow: /wp-json/\n";
	}
	if ( ! empty( $settings['add_sitemap_reference'] ) ) {
		$extra .= "\nSitemap: " . esc_url_raw( home_url( '/wp-sitemap.xml' ) ) . "\n";
	}

	return $output . $extra;
}
add_filter( 'robots_txt', 'garry_security_robots_txt_filter', 20, 2 );

/** Náhled aktuálního výstupu pro admin kartu – stejná logika jako do_robots(), bez echo/exit. */
function garry_security_robots_preview() {
	if ( garry_security_robots_physical_file_exists() ) {
		$content = @file_get_contents( ABSPATH . 'robots.txt' );
		return false !== $content ? $content : '';
	}
	$public = get_option( 'blog_public' );
	$output = "User-agent: *\n";
	if ( '0' == $public ) {
		$output .= "Disallow: /\n";
	} else {
		$site_url = wp_parse_url( site_url() );
		$path     = ! empty( $site_url['path'] ) ? $site_url['path'] : '';
		$output  .= "Disallow: {$path}/wp-admin/\n";
		$output  .= "Allow: {$path}/wp-admin/admin-ajax.php\n";
	}
	return apply_filters( 'robots_txt', $output, $public );
}

/** === llms.txt ============================================================
 * Neformální, rychle se vyvíjející konvence (obdoba robots.txt cílená na
 * AI/LLM systémy) – WordPress pro ni nemá jádrovou podporu ani jeden
 * ustálený formát. Tahle implementace je rozumný best-effort výklad
 * (Markdown: nadpis, krátký popis, kontakt), ne tvrzení o shodě s
 * jakýmkoli oficiálním standardem – ten zatím neexistuje. Stejně jako u
 * robots.txt jde o žádost adresovanou crawlerům, ne o technické vynucení.
 * ========================================================================= */

function garry_security_llms_physical_file_exists() {
	return file_exists( ABSPATH . 'llms.txt' );
}

function garry_security_llms_settings() {
	$defaults = array(
		'include_site_info'      => true,
		'allow_ai_training'      => false,
		'include_contact'        => true,
		'include_content_summary' => true,
	);
	$stored = get_option( GARRY_SECURITY_LLMS_OPTION, array() );
	return is_array( $stored ) ? array_merge( $defaults, $stored ) : $defaults;
}

function garry_security_llms_build_content() {
	if ( garry_security_llms_physical_file_exists() ) {
		$content = @file_get_contents( ABSPATH . 'llms.txt' );
		return false !== $content ? $content : '';
	}

	$settings = garry_security_llms_settings();
	$lines    = array();

	if ( ! empty( $settings['include_site_info'] ) ) {
		$lines[] = '# ' . wp_strip_all_tags( get_bloginfo( 'name' ) );
		$tagline = wp_strip_all_tags( get_bloginfo( 'description' ) );
		if ( '' !== $tagline ) {
			$lines[] = '> ' . $tagline;
		}
		$lines[] = '';
		$lines[] = 'URL: ' . home_url( '/' );
	}

	if ( ! empty( $settings['include_content_summary'] ) ) {
		$lines[] = '';
		$lines[] = '## Obsah webu';
		$lines[] = 'Web provozovaný na WordPressu. Podrobný obsah viz mapa webu: ' . home_url( '/wp-sitemap.xml' );
	}

	$lines[] = '';
	$lines[] = '## AI trénink';
	$lines[] = ! empty( $settings['allow_ai_training'] )
		? 'AI-Training: allow'
		: 'AI-Training: disallow';

	if ( ! empty( $settings['include_contact'] ) ) {
		$admin_email = sanitize_email( get_option( 'admin_email' ) );
		if ( $admin_email ) {
			$lines[] = '';
			$lines[] = '## Kontakt';
			$lines[] = $admin_email;
		}
	}

	return implode( "\n", $lines ) . "\n";
}

/**
 * Virtuální výstup na /llms.txt – jen pokud web nemá vlastní fyzický
 * soubor (ten by server obsloužil přímo, sem by se request nedostal, ale
 * kontrola navíc nikdy neškodí). Registrováno na template_redirect, aby
 * neběžel žádný zbytečný WP_Query/šablonový cyklus.
 */
function garry_security_maybe_serve_llms_txt() {
	if ( is_admin() ) {
		return;
	}
	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$home_path    = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$expected     = rtrim( (string) $home_path, '/' ) . '/llms.txt';

	if ( $request_path !== $expected ) {
		return;
	}

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo garry_security_llms_build_content(); // phpcs:ignore -- vlastní generovaný text, ne uživatelský vstup.
	exit;
}
add_action( 'template_redirect', 'garry_security_maybe_serve_llms_txt' );

/** === admin-post handler pro uložení nastavení robots.txt / llms.txt === */
function garry_security_handle_save_robots_llms() {
	if ( ! current_user_can( GARRY_SECURITY_CAP_MANAGE_PROVIDERS ) || ! check_admin_referer( 'garry_security_save_robots_llms' ) ) {
		wp_die( esc_html__( 'Nedostatečná oprávnění.', 'garry-default' ) );
	}

	$robots = array(
		'block_ai_crawlers'     => ! empty( $_POST['robots_block_ai_crawlers'] ),
		'add_sitemap_reference' => ! empty( $_POST['robots_add_sitemap_reference'] ),
		'disallow_search'       => ! empty( $_POST['robots_disallow_search'] ),
		'disallow_rest_api'     => ! empty( $_POST['robots_disallow_rest_api'] ),
	);
	update_option( GARRY_SECURITY_ROBOTS_OPTION, $robots, false );

	$llms = array(
		'include_site_info'       => ! empty( $_POST['llms_include_site_info'] ),
		'allow_ai_training'       => ! empty( $_POST['llms_allow_ai_training'] ),
		'include_contact'         => ! empty( $_POST['llms_include_contact'] ),
		'include_content_summary' => ! empty( $_POST['llms_include_content_summary'] ),
	);
	update_option( GARRY_SECURITY_LLMS_OPTION, $llms, false );

	wp_safe_redirect( add_query_arg(
		array( 'page' => 'garry-default', 'robots_llms_saved' => 1 ),
		admin_url( 'admin.php' )
	) );
	exit;
}
