<?php
/**
 * GARRY Security – katalog nejnovějších známých verzí GARRY pluginů.
 *
 * WordPressí nativní `get_plugin_updates()` umí zjistit dostupnou aktualizaci
 * jen u pluginů s reálným update serverem (WordPress.org, nebo vlastní
 * update-check backend za Update URI) – žádný GARRY plugin takový backend
 * nemá (Update URI je jen garry.cz, bez skutečné update-check route), takže
 * `pending_update` v `garry_security_collect_ecosystem_modules()` je pro
 * celou GARRY rodinu vždy false, i když nasazená verze je zastaralá.
 *
 * Tenhle soubor je ruční, verzovaná náhrada: při každém vydání nové verze
 * libovolného GARRY pluginu se sem zapíše nové číslo (žádný jiný zdroj
 * pravdy o „nejnovější dostupné verzi" u uzavřených, mimo WordPress.org
 * distribuovaných pluginů neexistuje). GARRY Security pak porovná nasazenou
 * verzi (z hlavičky aktivního pluginu) proti tomuto katalogu a nahlásí
 * rozdíl – viz garry_security_collect_ecosystem_modules() v
 * security-ecosystem.php.
 *
 * Katalog se týká jen PLUGINŮ (stejný rozsah jako zbytek „Ekosystém GARRY" –
 * aktivní pluginy s prefixem garry-/cit-); child theme (grid-divi5-child)
 * není plugin a get_plugins() ho vůbec nevrátí, takže tady není.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @return array<string,string> plugin_folder => nejnovější známá verze.
 */
function garry_security_known_latest_versions() {
	return array(
		'garry-bocni-posuvnik'       => '1.4.0',
		'garry-default'              => '2.3.0',
		'garry-denni-menu'           => '1.6.0',
		'garry-divi-toggle-text'     => '1.2.2',
		'garry-formatovana-cena'     => '2.2.1',
		'garry-foto-carousel'        => '1.2.1',
		'garry-foto-galerie'         => '1.2.1',
		'garry-galerie'              => '2.2.2',
		'garry-hero-krivka'          => '1.3.0',
		'garry-informacni-bublina'   => '1.3.2',
		'garry-kategorie-pokoju'     => '1.4.0',
		'garry-mapa-katastr'         => '2.2.2',
		'garry-novinkovy-prehled'    => '1.2.3',
		'garry-prispevkova-matice'   => '1.2.3',
		'garry-prispevkovy-carousel' => '1.2.4',
		'garry-sezona-cekaci-list'   => '2.6.0',
		'garry-situace-na-trati'     => '1.4.0',
		'garry-stav-prodeje'         => '1.1.1',
		'garry-tabulka-nemovitosti'  => '2.2.2',
		'garry-typografie'           => '1.1.4',
		'garry-video-prohlidka'      => '2.2.2',
		'gridhotel-components'       => '1.0.2',
		'gridhotel-core'             => '2.0.2',
	);
}

/**
 * @param string $folder  Složka pluginu (klíč z garry_security_active_garry_plugin_files()).
 * @param string $installed_version  Verze z hlavičky nasazeného pluginu (get_plugins()['Version']).
 * @return array{latest:?string,is_outdated:bool} latest je null, když plugin v katalogu není (neznámý/starší než katalog).
 */
function garry_security_version_status( $folder, $installed_version ) {
	$catalog = garry_security_known_latest_versions();
	$latest  = isset( $catalog[ $folder ] ) ? $catalog[ $folder ] : null;

	if ( null === $latest || '' === (string) $installed_version ) {
		return array( 'latest' => $latest, 'is_outdated' => false );
	}

	return array(
		'latest'      => $latest,
		'is_outdated' => version_compare( (string) $installed_version, $latest, '<' ),
	);
}
