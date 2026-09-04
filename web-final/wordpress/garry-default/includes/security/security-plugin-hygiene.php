<?php
/**
 * GARRY Security – neaktivní ("opuštěné") pluginy na disku (verze 2.2,
 * P1 "Neaktivní a opuštěné pluginy").
 *
 * Nainstalovaný, ale neaktivní plugin pořád zabírá místo na disku a je
 * to kód, který se dřív nebo později může stát terčem (i neaktivní plugin
 * jde v některých případech zneužít, pokud obsahuje přímo volatelný
 * soubor bez ABSPATH guardu). GARRY tu nehodnotí známé zranitelnosti ani
 * licence – to je capabilita `vulnerability_intelligence` (Patchstack),
 * kterou GARRY nemá jak nahradit bez placené databáze. Tahle kontrola je
 * čistě hygienická: "je nainstalované a nepoužívané, zvažte odstranění."
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * PLG-001: nainstalované, ale neaktivní pluginy (mimo GARRY katalog
 * a mimo doporučené providery – ty se hodnotí zvlášť přes ladder_state()
 * a nemá smysl je hlásit dvakrát).
 */
function garry_security_check_inactive_plugins() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$registry_files = wp_list_pluck( garry_security_provider_registry(), 'entry_file' );
	$all_plugins    = get_plugins();
	$mu_plugin_self = plugin_basename( GARRY_DEFAULT_FILE );

	$inactive = array();
	foreach ( $all_plugins as $plugin_file => $data ) {
		if ( is_plugin_active( $plugin_file ) || $plugin_file === $mu_plugin_self ) {
			continue;
		}
		if ( in_array( $plugin_file, $registry_files, true ) ) {
			continue; // Doporučené providery řeší vlastní ladder_state()/Volitelná rozšíření, ne tahle obecná kontrola.
		}
		$folder = garry_security_plugin_folder( $plugin_file );
		if ( 0 === strpos( $folder, 'garry-' ) || 0 === strpos( $folder, 'cit-' ) ) {
			continue; // Vlastní ekosystém řeší GARRY-001..012, ne tahle obecná kontrola.
		}
		$inactive[] = ! empty( $data['Name'] ) ? $data['Name'] : $folder;
	}

	if ( empty( $inactive ) ) {
		return array(
			'state'    => 'pass',
			'severity' => 'info',
			'message'  => 'Žádné neaktivní pluginy mimo GARRY katalog nebyly nalezeny.',
			'evidence' => array(),
		);
	}

	return array(
		'state'    => 'warning',
		'severity' => 'low',
		'message'  => sprintf(
			'%1$d neaktivních pluginů zabírá místo na webu: %2$s. Nejde o doporučení je aktivovat – u nepoužívaného pluginu je bezpečnější ho úplně odstranit, pokud pro něj není důvod.',
			count( $inactive ),
			implode( ', ', $inactive )
		),
		'evidence' => array( 'plugins' => $inactive ),
	);
}

function garry_security_collect_plugin_hygiene() {
	return array( 'PLG-001' => garry_security_check_inactive_plugins() );
}

function garry_security_plugin_hygiene_catalog_entries() {
	return array(
		'PLG-001' => array( 'title' => 'Neaktivní pluginy na disku', 'category' => 'Pluginy a aktualizace' ),
	);
}
