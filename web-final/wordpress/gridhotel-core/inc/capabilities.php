<?php
/**
 * GRID Hotel Core — doménové capabilities (verze 2.0.0).
 *
 * Dvě oddělené rodiny capabilities existují vedle sebe a neduplikují se:
 *
 *  - GRIDCORE_CAP_ROOMS_GALLERY / GRIDCORE_CAP_CAREERS (garry_grid_manage_*,
 *    definované v gridhotel-core.php, beze změny od 1.6.0) — "vidí uživatel
 *    vůbec tuhle kartu v GRID Nastavení?", spravováno obrazovkou
 *    "Oprávnění personálu" (inc/staff-permissions.php), sdílí registr napříč
 *    všemi aktivními GARRY pluginy.
 *
 *  - GRIDCORE_DCAP_* (grid_manage_*, tento soubor) — "smí uživatel
 *    vytvářet/upravovat/mazat/publikovat obsah daného typu?", skutečné
 *    map_meta_cap capabilities na CPT/taxonomii, spravováno novou obrazovkou
 *    "Přístupy" (inc/access-page.php). Vlastní jen GRID Core.
 *
 * Obě rodiny se skládají, ne nahrazují: karta v menu se řídí tou první,
 * skutečná práva k objektu tou druhou.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_DCAP_SETTINGS',        'grid_manage_settings' );
define( 'GRIDCORE_DCAP_ROOMS',           'grid_manage_rooms' );
define( 'GRIDCORE_DCAP_ROOM_CATEGORIES', 'grid_manage_room_categories' );
define( 'GRIDCORE_DCAP_EXPERIENCES',     'grid_manage_experiences' );
define( 'GRIDCORE_DCAP_GASTRO',          'grid_manage_gastro' );
define( 'GRIDCORE_DCAP_TESTIMONIALS',    'grid_manage_testimonials' );
define( 'GRIDCORE_DCAP_CAREERS',         'grid_manage_careers' );
define( 'GRIDCORE_DCAP_FORMS',           'grid_manage_forms' );
define( 'GRIDCORE_DCAP_MODULES',         'grid_manage_modules' );
define( 'GRIDCORE_DCAP_ACCESS',          'grid_manage_access' );
define( 'GRIDCORE_DCAP_DIAGNOSTICS',     'grid_view_diagnostics' );

/**
 * @return array<string,string> capability => lidsky čitelný název, v pořadí
 *         zobrazení na obrazovce "Přístupy".
 */
function gridcore_domain_capability_catalog() {
	return array(
		GRIDCORE_DCAP_SETTINGS        => 'Obecné nastavení a obsah hotelu',
		GRIDCORE_DCAP_ROOMS           => 'Pokoje',
		GRIDCORE_DCAP_ROOM_CATEGORIES => 'Kategorie pokojů',
		GRIDCORE_DCAP_EXPERIENCES     => 'Zážitky',
		GRIDCORE_DCAP_GASTRO          => 'Gastro',
		GRIDCORE_DCAP_TESTIMONIALS    => 'Reference',
		GRIDCORE_DCAP_CAREERS         => 'Kariéra',
		GRIDCORE_DCAP_FORMS           => 'Formuláře a integrace',
		GRIDCORE_DCAP_MODULES         => 'Moduly',
		GRIDCORE_DCAP_ACCESS          => 'Přístupy',
		GRIDCORE_DCAP_DIAGNOSTICS     => 'Diagnostika',
	);
}

/**
 * Standardní `capabilities` pole pro register_post_type() s jedinou doménovou
 * capabilitou za celý typ obsahu (žádné dělení na role Author/Editor/Admin —
 * to spec nežádá, žádá jen "kdo smí spravovat tenhle typ obsahu vůbec").
 * `map_meta_cap => true` navíc vyžaduje, aby WP core uměl mapovat meta
 * capabilities (edit_post/delete_post/read_post) odvozené z singulárního
 * `capability_type` — proto se capability_type i tady nastavuje na dvojici
 * (jednotné, množné), i když primitivní práva níže všechna míří na jednu
 * sdílenou doménovou capabilitu.
 *
 * @param string $singular   Jednotné číslo capability_type, např. 'grid_room'.
 * @param string $plural     Množné číslo capability_type, např. 'grid_rooms'.
 * @param string $domain_cap Jedna z GRIDCORE_DCAP_* konstant výše.
 * @return array
 */
function gridcore_cpt_capabilities( $singular, $plural, $domain_cap ) {
	return array(
		'edit_post'              => 'edit_' . $singular,
		'read_post'               => 'read_' . $singular,
		'delete_post'             => 'delete_' . $singular,
		'edit_posts'              => $domain_cap,
		'edit_others_posts'       => $domain_cap,
		'publish_posts'           => $domain_cap,
		'read_private_posts'      => $domain_cap,
		'delete_posts'            => $domain_cap,
		'delete_private_posts'    => $domain_cap,
		'delete_published_posts'  => $domain_cap,
		'delete_others_posts'     => $domain_cap,
		'edit_private_posts'      => $domain_cap,
		'edit_published_posts'    => $domain_cap,
	);
}

/**
 * Totéž pro register_taxonomy(). `assign_terms` míří na stejnou doménovou
 * capabilitu jako správa kategorií samotných (ne na grid_manage_rooms) –
 * kdo smí přiřazovat kategorie pokojům, je stejná osoba, co je smí upravovat;
 * je to záměrné zjednodušení, ne opomenutí.
 */
function gridcore_taxonomy_capabilities( $domain_cap ) {
	return array(
		'manage_terms' => $domain_cap,
		'edit_terms'   => $domain_cap,
		'delete_terms' => $domain_cap,
		'assign_terms' => $domain_cap,
	);
}

/**
 * SEC-SELF-001 vzor (stejný jako u legacy GRIDCORE_CAP_ROOMS_GALLERY/CAREERS
 * v gridhotel-core.php a u ~5 dalších pluginů v tomhle repozitáři):
 * administrátor dostane všechny doménové capabilities vždy, ať šel plugin
 * přes aktivaci, nebo jen přepsání souborů beze změny stavu aktivace. Volá se
 * z aktivačního hooku i z upgraderu (inc/upgrader.php), takže je bezpečné ji
 * volat opakovaně (idempotentní – add_cap na capabilitu, kterou role už má,
 * je no-op).
 */
function gridcore_grant_domain_capabilities_to_admin() {
	$role = get_role( 'administrator' );
	if ( ! $role ) {
		return;
	}
	foreach ( array_keys( gridcore_domain_capability_catalog() ) as $cap ) {
		if ( ! $role->has_cap( $cap ) ) {
			$role->add_cap( $cap );
		}
	}
}
