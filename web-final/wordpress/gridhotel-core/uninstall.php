<?php
/**
 * GRID Hotel Core — odinstalace.
 *
 * ZÁMĚRNĚ NEMAŽE OBSAH. Pokoje, akce sezóny, zážitky, gastro a reference (CPT)
 * i jejich ACF pole zůstávají v databázi i po odinstalaci pluginu, aby se
 * obsah klienta neztratil. Po případné reinstalaci je vše zpět.
 *
 * (Pokud bys chtěl data smazat kompletně, je nutné je odstranit ručně.)
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
// Bez destruktivních operací — data ponechána.
