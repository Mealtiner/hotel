<?php
/**
 * GRID Hotel Core — verzovaný, idempotentní upgrader (verze 2.0.0).
 *
 * Odděluje verzi PLUGINU (GRIDCORE_VER, hlavní soubor pluginu) od verze
 * SCHÉMATU (option `gridhotel_core_db_version`, tento soubor). Kroky se
 * spouští na `plugins_loaded` (ne jen v aktivačním hooku), takže se
 * administrátor dostane k novým právům/datům i po pouhém přepsání souborů
 * pluginu (FTP/lokální sync) beze změny stavu aktivace – stejný důvod, proč
 * existoval dřívější `admin_init` self-heal v gridhotel-core.php (teď je
 * nahrazen tímhle obecnějším mechanismem, krok 1 dělá totéž).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GRIDCORE_DB_VERSION', 4 );
define( 'GRIDCORE_DB_VERSION_OPTION', 'gridhotel_core_db_version' );
define( 'GRIDCORE_MIGRATION_LOG_OPTION', 'gridhotel_core_migration_log' );
define( 'GRIDCORE_UPGRADE_LOCK', 'gridcore_upgrade_lock' );

/**
 * Vstupní bod. Volatelný opakovaně a souběžně bezpečně (krátký transient
 * zámek) – z `plugins_loaded` i přímo z aktivačního hooku.
 */
function gridcore_run_upgrade() {
	if ( get_transient( GRIDCORE_UPGRADE_LOCK ) ) {
		return;
	}
	set_transient( GRIDCORE_UPGRADE_LOCK, 1, 60 );

	$current = (int) get_option( GRIDCORE_DB_VERSION_OPTION, 0 );
	$target  = GRIDCORE_DB_VERSION;

	if ( $current < $target ) {
		$steps = gridcore_migration_steps();
		for ( $v = $current + 1; $v <= $target; $v++ ) {
			if ( ! isset( $steps[ $v ] ) ) {
				continue;
			}
			try {
				call_user_func( $steps[ $v ] );
				update_option( GRIDCORE_DB_VERSION_OPTION, $v, true );
				gridcore_log_migration( $v, 'ok', '' );
			} catch ( \Throwable $e ) {
				// Schema verze zůstává na poslední úspěšné – další request to zkusí znovu.
				gridcore_log_migration( $v, 'error', $e->getMessage() );
				break;
			}
		}
	}

	delete_transient( GRIDCORE_UPGRADE_LOCK );
}
add_action( 'plugins_loaded', 'gridcore_run_upgrade', 20 );

/**
 * @return array<int,callable>
 */
function gridcore_migration_steps() {
	return array(
		1 => 'gridcore_migrate_step_1_capabilities',
		2 => 'gridcore_migrate_step_2_options_page',
		3 => 'gridcore_migrate_step_3_framework_log',
		4 => 'gridcore_migrate_step_4_stop_event_seeding',
	);
}

/** Krok 1: doménové capabilities (grid_manage_*) + reaffirm legacy 2 caps administrátorovi. */
function gridcore_migrate_step_1_capabilities() {
	gridcore_grant_domain_capabilities_to_admin();
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( array( GRIDCORE_CAP_ROOMS_GALLERY, GRIDCORE_CAP_CAREERS ) as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}
}

/**
 * Krok 2: vlastnictví `grid-options`. Samotná registrace (acf_add_options_page
 * + 2 field groups) běží bez podmínky na schema verzi v inc/options-page.php –
 * je to normální, vždy-zapnutá registrace stejně jako CPT v inc/cpt.php, ne
 * jednorázová migrace dat (žádná uložená hodnota nemění option/field name).
 * Tenhle krok existuje jen jako auditovatelný záznam OKAMŽIKU přechodu
 * vlastnictví pro diagnostickou stránku.
 */
function gridcore_migrate_step_2_options_page() {
	// Záměrně beze změny dat – viz komentář výše.
}

/**
 * Krok 3: přejmenování option s logem Frameworku.
 *
 * Log doposud používal option `garry_denni_menu_framework_log` – doslovně
 * stejný název, jaký pro SVŮJ VLASTNÍ log používá i plugin garry-denni-menu
 * (ověřeno v jeho bootstrap.php). Nejde tedy jen o kosmeticky špatné
 * pojmenování – oba pluginy do stejné option řádky WEEKS/MĚSÍCE reálně
 * zapisovaly a četly ZE SPOLEČNÉHO úložiště. LocalLog::log() ukládá jen
 * {time, event, data} bez jakéhokoli identifikátoru pluginu (viz
 * includes/framework-v23/LocalLog.php), takže zpětně NELZE spolehlivě
 * rozlišit, který záznam patří kterému pluginu.
 *
 * Bezpečná oprava proto není "zkopírovat vlastní záznamy" (nejde to určit),
 * ale: přestat do sdílené option zapisovat (nové zápisy Core míří od teď na
 * `gridhotel_core_framework_log`, prázdný start) a starou option nechat beze
 * změny – zůstává plně k dispozici garry-denni-menu, který ji dál používá
 * jako svůj vlastní, nekolidující log.
 */
function gridcore_migrate_step_3_framework_log() {
	// Záměrně žádný přenos dat – viz komentář výše. Nová option
	// `gridhotel_core_framework_log` vznikne přirozeně prvním zápisem
	// Frameworku (bootstrap.php teď předává tenhle nový název).
}

/**
 * Krok 4: přestat sázet nová `grid_event` (GRID-SUITE-01 §12) – vlastnictví
 * sezónních záznamů přechází na plugin „Sezóna & čekací list". Existující
 * `grid_event` příspěvky se nemažou ani neupravují, jen se přestávají
 * generovat nové (viz inc/seed.php – grid_event už není v seed datové sadě).
 */
function gridcore_migrate_step_4_stop_event_seeding() {
	// Záměrně beze změny dat – reálná změna je v inc/seed.php (typová sada).
}

/**
 * Anonymizovaný migrační log pro stránku Diagnostika – žádná osobní data,
 * jen krok/výsledek/čas/chybová zpráva (technická, ne uživatelský vstup).
 */
function gridcore_log_migration( $step, $status, $message ) {
	$log   = get_option( GRIDCORE_MIGRATION_LOG_OPTION, array() );
	$log[] = array(
		'step'    => (int) $step,
		'status'  => (string) $status,
		'message' => substr( (string) $message, 0, 200 ),
		'time'    => time(),
	);
	if ( count( $log ) > 50 ) {
		$log = array_slice( $log, -50 );
	}
	update_option( GRIDCORE_MIGRATION_LOG_OPTION, $log, false );
}
