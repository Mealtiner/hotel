<?php
/**
 * Převede kategorie pokojů na pojmenování z Booking.com (5 kategorií).
 * Přejmenuje Superior Plus -> Superior s terasou, rozdělí sloučené apartmá
 * na Apartmá (47 m²) a Apartmá Superior (58 m²), doplní výbavu a fotky.
 */
$SP = getenv('SP');
$data = json_decode(file_get_contents($SP . '/pokoje-nove.json'), true);
if (!$data) { WP_CLI::error('nelze nacist pokoje-nove.json'); }

/* --- fotky: mapa klic => pole ID priloh --- */
$media = array();
foreach (file($SP . '/media-map.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ln) {
	list($k, $ids) = explode(':', $ln, 2);
	$media[$k] = array_values(array_filter(array_map('intval', explode(',', $ids))));
}

/* --- 1) volba pluginu: doplnit obrazek z medialni knihovny --- */
foreach ($data['rooms'] as &$r) {
	$ids = $media[$r['key']] ?? array();
	if ($ids) {
		$u = wp_get_attachment_image_url($ids[0], 'full');
		if ($u) { $r['img'] = wp_make_link_relative($u); }
	}
}
unset($r);
update_option('garry_pokoje', $data, false);
WP_CLI::log('volba garry_pokoje ulozena: ' . count($data['rooms']) . ' pokoju, '
	. count($data['compare']) . ' radku srovnani, ' . count($data['labels']) . ' stitku');

/* --- 2) taxonomie: prejmenovani + rozdeleni --- */
$prejmenuj = array(
	20 => array('superior-s-terasou',    'Superior s terasou'),
	56 => array('superior-s-terasou-en', 'Superior with Terrace'),
	59 => array('superior-s-terasou-de', 'Superior mit Terrasse'),
	21 => array('apartma',    'Apartmá'),
	63 => array('apartma-en', 'Apartment'),
	66 => array('apartma-de', 'Appartement'),
);
$stare_slugy = array();
foreach ($prejmenuj as $tid => $nn) {
	$t = get_term($tid, 'grid_room_cat');
	if (!$t || is_wp_error($t)) { WP_CLI::warning("term $tid neexistuje"); continue; }
	$stare_slugy[$t->slug] = $nn[0];
	$res = wp_update_term($tid, 'grid_room_cat', array('slug' => $nn[0], 'name' => $nn[1]));
	WP_CLI::log(is_wp_error($res) ? "  CHYBA $tid: " . $res->get_error_message()
		: sprintf('  %d  %s -> %s / %s', $tid, $t->slug, $nn[0], $nn[1]));
}

/* --- 3) nova kategorie Apartma Superior ve trech jazycich --- */
$nove = array(
	'cs' => array('apartma-superior',    'Apartmá Superior'),
	'en' => array('apartma-superior-en', 'Superior Apartment'),
	'de' => array('apartma-superior-de', 'Superior-Appartement'),
);
$ids_nove = array();
foreach ($nove as $lang => $n) {
	$ex = get_term_by('slug', $n[0], 'grid_room_cat');
	if ($ex && !is_wp_error($ex)) { $ids_nove[$lang] = (int) $ex->term_id; WP_CLI::log("  jiz existuje: {$n[0]} #{$ex->term_id}"); continue; }
	$res = wp_insert_term($n[1], 'grid_room_cat', array('slug' => $n[0]));
	if (is_wp_error($res)) { WP_CLI::warning("  nelze vytvorit {$n[0]}: " . $res->get_error_message()); continue; }
	$ids_nove[$lang] = (int) $res['term_id'];
	WP_CLI::log("  vytvoren {$n[0]} #{$res['term_id']} ({$n[1]})");
	if (function_exists('pll_set_term_language')) { pll_set_term_language($res['term_id'], $lang); }
}
if (count($ids_nove) === 3 && function_exists('pll_save_term_translations')) {
	pll_save_term_translations($ids_nove);
	WP_CLI::log('  propojeni Polylang: ' . json_encode($ids_nove));
}

/* --- 4) termmeta ve vsech jazycich --- */
$term_id = function ($key, $lang) {
	$slug = $key . ($lang === 'cs' ? '' : '-' . $lang);
	$t = get_term_by('slug', $slug, 'grid_room_cat');
	return ($t && !is_wp_error($t)) ? (int) $t->term_id : 0;
};
$poradi = array('standard' => 10, 'superior' => 20, 'superior-s-terasou' => 30,
	'apartma' => 40, 'apartma-superior' => 50);
foreach ($data['rooms'] as $r) {
	$ids = $media[$r['key']] ?? array();
	foreach (array('cs' => 'cz', 'en' => 'en', 'de' => 'de') as $lang => $suf) {
		$tid = $term_id($r['key'], $lang);
		if (!$tid) { WP_CLI::warning("  chybi term {$r['key']} / $lang"); continue; }
		update_term_meta($tid, 'kod',          $r['kod_' . $suf]);
		update_term_meta($tid, 'poradi',       $poradi[$r['key']]);
		update_term_meta($tid, 'kratky_popis', $r['kratky_' . $suf]);
		update_term_meta($tid, 'popis',        $r['popis_' . $suf]);
		update_term_meta($tid, 'stitky',       $r['koupelna_' . $suf]);
		foreach (array('kod' => 'field_rc_kod', 'poradi' => 'field_rc_poradi',
			'kratky_popis' => 'field_rc_kratky', 'popis' => 'field_rc_popis',
			'stitky' => 'field_rc_stitky', 'nahled' => 'field_rc_nahled',
			'galerie' => 'field_rc_galerie') as $k => $fk) {
			update_term_meta($tid, '_' . $k, $fk);
		}
		if ($ids) {
			update_term_meta($tid, 'nahled',  $ids[0]);
			update_term_meta($tid, 'galerie', array_slice($ids, 1));
		}
	}
	WP_CLI::log(sprintf('  meta %-20s cs/en/de, nahled=%s, galerie=%d',
		$r['key'], $ids ? $ids[0] : '-', max(0, count($ids) - 1)));
}

/* --- 5) presmerovani ze starych URL --- */
update_option('grid_presmerovani_pokoju', $stare_slugy, true);
WP_CLI::log('presmerovani: ' . json_encode($stare_slugy, JSON_UNESCAPED_UNICODE));

flush_rewrite_rules(false);
WP_CLI::success('hotovo');
