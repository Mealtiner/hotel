<?php
/**
 * GRID Hotel Components — odinstalace.
 *
 * Plugin nevlastní žádná perzistentní data (žádné vlastní options, žádné
 * CPT, žádné capabilities) — jen shortcode registrace a render logiku,
 * které existují pouze za běhu. Odinstalace proto nemá co mazat.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
