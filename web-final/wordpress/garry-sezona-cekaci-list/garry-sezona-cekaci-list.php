<?php
/**
 * Plugin Name:       GARRY – Sezónní nabídka a čekací list
 * Plugin URI:        https://www.garry.cz
 * Description:       Spravuje sezónní akce, štítky dostupnosti a čekací formulář s lokálním logem poptávek. Nabídku a voucherový formulář vloží shortcody grid_season_events a grid_voucher_form; původně vytvořeno pro GRID Hotel. Pro odesílání je nutné správně nastavit WordPress e-mail a případně CAPTCHA.
 * Version:           2.7.2
 * Author:            GARRY Promotion
 * Author URI:        https://www.garry.cz
 * License:           Proprietary — Copyright © GARRY Promotion
 * Text Domain:       garry-sezona-cekaci-list
 * Update URI:        https://www.garry.cz
 * Requires at least: 6.4
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================================
 * GARRY – Sezóna & čekací list v2 — data
 * ============================================================================ */

define( 'GARRY_SEZ_VER', '2.7.2' );
define( 'GARRY_SEZ_OPT', 'garry_sezona' );
define( 'GARRY_SEZ_LOG', 'garry_sezona_log' );
/**
 * Release blockers z GRID-SUITE-03 §2, opraveno ve verzi 2.6.0:
 *  - retence byla v kódu jen "posledních 300 záznamů" bez časové platnosti,
 *    zatímco manifest sliboval 90 dní — teď je to jedna skutečná hodnota,
 *    vynucená denním cronem (garry_sez_prune_log_by_age), ne jen deklarace.
 *  - veřejný AJAX neměl žádný rate limit (nonce sama o sobě proti zneužití
 *    nechrání) — teď má krátký transient limiter na IP+bucket.
 */
define( 'GARRY_SEZ_LOG_RETENTION_DAYS', 90 );
define( 'GARRY_SEZ_RATE_LIMIT_MAX', 5 );
define( 'GARRY_SEZ_RATE_LIMIT_WINDOW', 600 );

function garry_sez_lang() {
	if ( function_exists( 'pll_current_language' ) ) { $l = pll_current_language(); if ( $l ) return $l; }
	return substr( (string) get_locale(), 0, 2 );
}
function garry_sez_lang_idx() { $l = garry_sez_lang(); return $l === 'en' ? 1 : ( $l === 'de' ? 2 : 0 ); }

/* Výchozí štítky obsazenosti (editovatelné na kartě „Štítky obsazenosti").
 * legacy = chování widgetu: free (rezervace) / few (poslední) / full (čekací list) */
function garry_sez_default_states() {
	return array(
		array( 'key'=>'volne',    'cz'=>'Volné pokoje',    'en'=>'Rooms available', 'de'=>'Freie Zimmer',
			'cta_cz'=>'Rezervovat →', 'cta_en'=>'Book now →', 'cta_de'=>'Buchen →',    'color'=>'#2ecc71', 'legacy'=>'free' ),
		array( 'key'=>'posledni', 'cz'=>'Poslední pokoje', 'en'=>'Last rooms',      'de'=>'Letzte Zimmer',
			'cta_cz'=>'Rezervovat →', 'cta_en'=>'Book now →', 'cta_de'=>'Buchen →',    'color'=>'#caa75f', 'legacy'=>'few' ),
		array( 'key'=>'cekaci',   'cz'=>'Čekací list',     'en'=>'Waiting list',    'de'=>'Warteliste',
			'cta_cz'=>'Zapsat se →',  'cta_en'=>'Sign up →',  'cta_de'=>'Eintragen →', 'color'=>'#FF5A50', 'legacy'=>'full' ),
		array( 'key'=>'plne',     'cz'=>'Plně obsazeno',   'en'=>'Fully booked',    'de'=>'Ausgebucht',
			'cta_cz'=>'Zapsat se →',  'cta_en'=>'Sign up →',  'cta_de'=>'Eintragen →', 'color'=>'#8F8E90', 'legacy'=>'full' ),
	);
}
function garry_sez_default_events() {
	/* Reálný kalendář závodů — automotodrombrno.cz/kalendar-akci/zavody/ (načteno 2026-07-22) */
	return array(
		array( 'od'=>'2026-09-11','do'=>'2026-09-13','cz'=>'Masaryk Racing Days 2026','en'=>'Masaryk Racing Days 2026','de'=>'Masaryk Racing Days 2026',
			'pcz'=>'Mezinárodní závodní víkend okruhových šampionátů','pen'=>'International circuit racing weekend','pde'=>'Internationales Rundstrecken-Rennwochenende',
			'dcz'=>'Nenechte si ujít vrchol automobilové sezony na Masarykově okruhu. Víkend Masaryk Racing Days nabídne všechno, po čem motoristický fanoušek touží. Rychlé supersporty GT, tvrdé souboje v cestovních vozech, závodnické naděje ve formuli 4 i bohatě obsazené pohárové šampionáty.','den'=>'Don\'t miss the highlight of the car racing season at the Masaryk Circuit. The Masaryk Racing Days weekend offers everything a motorsport fan could wish for: fast GT supercars, hard-fought touring car battles, rising talents in Formula 4 and richly filled cup championships.','dde'=>'Verpassen Sie nicht den Höhepunkt der Automobilsaison am Masaryk-Ring. Das Wochenende der Masaryk Racing Days bietet alles, was sich ein Motorsportfan wünscht: schnelle GT-Supersportwagen, harte Duelle der Tourenwagen, Nachwuchstalente in der Formel 4 und stark besetzte Pokal-Meisterschaften.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/masaryk-racing-days-2026/' ),
		array( 'od'=>'2026-09-18','do'=>'2026-09-20','cz'=>'Velká cena Bohumila Staši','en'=>'Bohumil Staša Grand Prix','de'=>'Großer Preis von Bohumil Staša',
			'pcz'=>'Tradiční motocyklové závody na Masarykově okruhu','pen'=>'Traditional motorcycle races at the Masaryk Circuit','pde'=>'Traditionelle Motorradrennen am Masaryk-Ring',
			'dcz'=>'Tuzemská špička motocyklových závodníků se v polovině září představí na Masarykově okruhu. Tradiční podnik nese jméno po legendě československého motocyklového sportu.','den'=>'The best of Czech motorcycle racing takes to the Masaryk Circuit in mid-September. The traditional event bears the name of a legend of Czechoslovak motorcycle sport.','dde'=>'Die tschechische Motorrad-Elite tritt Mitte September am Masaryk-Ring an. Die traditionsreiche Veranstaltung trägt den Namen einer Legende des tschechoslowakischen Motorradsports.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/velka-cena-bohumila-stasi-2026/' ),
		array( 'od'=>'2026-09-24','do'=>'2026-09-27','cz'=>'Porsche Sprint Challenge Central Europe','en'=>'Porsche Sprint Challenge Central Europe','de'=>'Porsche Sprint Challenge Central Europe',
			'pcz'=>'Značkový pohár vozů Porsche','pen'=>'Porsche one-make cup racing','pde'=>'Porsche-Markenpokal',
			'dcz'=>'Porsche se vrací na Masarykův okruh. Na vlastní oči uvidíte legendární model 911 v té nejčistší závodní podobě. Porsche Sprint Challenge je šampionát pohárových vozů 911 GT3 Cup, to znamená, že o vítězství a prohře rozhodne jen a pouze umění a dravost závodníka.','den'=>'Porsche returns to the Masaryk Circuit. See the legendary 911 in its purest racing form with your own eyes. The Porsche Sprint Challenge is a one-make championship of 911 GT3 Cup cars — victory and defeat are decided purely by the skill and daring of the driver.','dde'=>'Porsche kehrt an den Masaryk-Ring zurück. Erleben Sie den legendären 911 in seiner reinsten Rennform. Die Porsche Sprint Challenge ist ein Markenpokal der 911 GT3 Cup — über Sieg und Niederlage entscheiden allein Können und Mut der Fahrer.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/porsche-sprint-challenge-central-europe-2026/' ),
		array( 'od'=>'2026-10-10','do'=>'2026-10-11','cz'=>'Race Car Show – MM závodů automobilů do vrchu','en'=>'Race Car Show – Hill Climb Championship','de'=>'Race Car Show – Bergrenn-Meisterschaft',
			'pcz'=>'Mezinárodní mistrovství závodů automobilů do vrchu','pen'=>'International hill climb championship','pde'=>'Internationale Bergrenn-Meisterschaft',
			'dcz'=>'Přijďte si vychutnat další závod do vrchu na Masarykově okruhu. V tradičním podzimním termínu se můžete těšit na pestrou přehlídku rychlosti a preciznosti. Akci organizuje Maverick Rescue z.s.','den'=>'Enjoy another hill climb race at the Masaryk Circuit. In the traditional autumn date you can look forward to a varied showcase of speed and precision. The event is organised by Maverick Rescue.','dde'=>'Genießen Sie ein weiteres Bergrennen am Masaryk-Ring. Zum traditionellen Herbsttermin erwartet Sie eine abwechslungsreiche Schau von Geschwindigkeit und Präzision. Veranstalter ist Maverick Rescue.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/mezinarodni-mistrovstvi-zavodu-automobilu-do-vrchu-2026-2/' ),
		array( 'od'=>'2026-10-17','do'=>'2026-10-17','cz'=>'Tuning Show Brno','en'=>'Tuning Show Brno','de'=>'Tuning Show Brno',
			'pcz'=>'Přehlídka upravených vozů na okruhu','pen'=>'Tuned car show at the circuit','pde'=>'Tuning-Schau an der Rennstrecke',
			'dcz'=>'Milovníci tuningu se mohou těšit na závěrečnou show roku, která nabídne vše, co k pořádné akci patří: od tuningového srazu, výstavy supersportů a volných jízd po dráze až po závod do vrchu.','den'=>'Tuning fans can look forward to the closing show of the year, offering everything a proper event needs: a tuning meet, a supercar exhibition, free track sessions and a hill climb race.','dde'=>'Tuning-Fans können sich auf die Abschluss-Show des Jahres freuen, die alles bietet, was zu einem richtigen Event gehört: Tuning-Treffen, Supersportwagen-Ausstellung, freie Fahrten auf der Strecke und ein Bergrennen.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/tuning-show-brno-2026/' ),
		array( 'od'=>'2026-10-18','do'=>'2026-10-18','cz'=>'8h Le Brno','en'=>'8h Le Brno','de'=>'8h Le Brno',
			'pcz'=>'Osmihodinový vytrvalostní závod','pen'=>'Eight-hour endurance race','pde'=>'Acht-Stunden-Langstreckenrennen',
			'dcz'=>'Závod amatérské vytrvalostní série ARC Endurance pořádané společností Auto Rallye Cross, která funguje jako promotér již od roku 2001.','den'=>'A race of the amateur endurance series ARC Endurance, organised by Auto Rallye Cross, a promoter active since 2001.','dde'=>'Ein Rennen der Amateur-Langstreckenserie ARC Endurance, veranstaltet von Auto Rallye Cross, das seit 2001 als Promoter aktiv ist.',
			'stav'=>'volne','url'=>'https://www.automotodrombrno.cz/le-brno-8h-2026/' ),
	);
}
function garry_sez_get() {
	$o = get_option( GARRY_SEZ_OPT, null );
	if ( ! is_array( $o ) ) $o = array( 'events' => garry_sez_default_events() );
	$o = wp_parse_args( $o, array(
		'events' => array(), 'states' => array(), 'email' => '',
		/* Kolik připravovaných akcí se nejvýš vypíše na webu. Dřív to řídil jen
		   atribut shortcodu, takže se to nedalo změnit bez zásahu do stránky. */
		'max_pripravovanych' => 5,
		'import_aktivni'  => 1,
		'import_perioda'  => 'weekly',
		'import_posledni' => '',
		'import_stav'     => '',
		'import_rucne'    => 0,
	) );
	if ( empty( $o['states'] ) ) $o['states'] = garry_sez_default_states();

	/* Akce uložené před verzí 2.7.0 nové klíče nemají. Doplníme je při čtení,
	   ne migrací volby — ta by se musela hlídat a spouštět jen jednou, kdežto
	   takhle je stav konzistentní vždycky. Ručně zadané akce se považují za
	   publikované, jinak by po aktualizaci pluginu zmizely z webu. */
	foreach ( $o['events'] as $i => $e ) {
		if ( ! is_array( $e ) ) { unset( $o['events'][ $i ] ); continue; }
		$o['events'][ $i ] = wp_parse_args( $e, array(
			'url_en' => '', 'publikovano' => 1, 'nova' => 0, 'zdroj' => 'rucne', 'nacteno' => '',
		) );
	}
	$o['events'] = array_values( $o['events'] );
	return $o;
}

/** Akce rozdělené na připravované a uplynulé podle dnešního data. */
function garry_sez_rozdel_akce( array $akce ) {
	$dnes = current_time( 'Y-m-d' );
	$pripravovane = $uplynule = array();
	foreach ( $akce as $i => $e ) {
		$konec = ( $e['do'] ?? '' ) ?: ( $e['od'] ?? '' );
		if ( $konec === '' || $konec >= $dnes ) $pripravovane[ $i ] = $e;
		else $uplynule[ $i ] = $e;
	}
	uasort( $pripravovane, function ( $x, $y ) { return strcmp( $x['od'] ?? '', $y['od'] ?? '' ); } );
	uasort( $uplynule, function ( $x, $y ) { return strcmp( $y['od'] ?? '', $x['od'] ?? '' ); } );
	return array( $pripravovane, $uplynule );
}

/**
 * Odkaz na detail akce na webu Automotodromu podle jazyka. Zdroj má jen českou
 * a anglickou mutaci, německý návštěvník proto dostane anglickou — je to
 * srozumitelnější než čeština a lepší než odkaz vynechat.
 */
function garry_sez_url_akce( array $e, $li ) {
	$en = trim( (string) ( $e['url_en'] ?? '' ) );
	if ( $li > 0 && $en !== '' ) return $en;
	return (string) ( $e['url'] ?? '' );
}
/* Štítky jako mapa key => data (pořadí zachováno) */
function garry_sez_states() {
	$out = array();
	foreach ( garry_sez_get()['states'] as $st ) {
		if ( empty( $st['key'] ) ) continue;
		$out[ $st['key'] ] = $st;
	}
	return $out;
}
function garry_sez_state_label( $st, $li ) { return $st[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $st['cz']; }
function garry_sez_state_cta( $st, $li )   { return $st[ array( 'cta_cz', 'cta_en', 'cta_de' )[ $li ] ] ?: $st['cta_cz']; }

/**
 * Lokální náhrada bývalých Garry_Promotion_Registry::STAFF_CAPABILITY a
 * grid_visible() – viz stejné vysvětlení v garry-denni-menu.php. Čte
 * stejnou option 'garry_grid_visibility', dřívější nastavení platí dál.
 *
 * Granulární capability na kartu (viz GARRY – GRID Core) místo dřívějšího
 * plošného edit_others_posts – definováno PŘED bootstrap() níže, protože
 * descriptor frameworku ho čte v okamžiku volání.
 */
define( 'GARRY_SEZ_STAFF_CAP', 'garry_grid_manage_sezona_cekaci_list' );

/* ---------- registrace do menu (GARRY + GRID Nastavení) ---------- */
require_once __DIR__ . '/includes/import.php';

require_once __DIR__ . '/includes/framework-v23/bootstrap.php';
\Garry\Embedded\SezonaCekaciList\V23\bootstrap( __FILE__, 'garry_sez_admin_page', 'Sezóna a čekací list' );

register_activation_hook( __FILE__, function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_SEZ_STAFF_CAP ) ) {
		$role->add_cap( GARRY_SEZ_STAFF_CAP );
	}
	if ( ! wp_next_scheduled( 'garry_sez_daily_retention' ) ) {
		wp_schedule_event( time(), 'daily', 'garry_sez_daily_retention' );
	}
} );
/**
 * Deaktivace cron zruší, opětovná aktivace jej obnoví (GRID-SUITE-03 §9) —
 * bez tohohle by při dočasně vypnutém pluginu zůstal viset naplánovaný
 * event, který se po reaktivaci zdvojí.
 */
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'garry_sez_daily_retention' );
} );

/**
 * Self-healing doplněk: register_activation_hook() se spustí jen při
 * skutečném přechodu neaktivní → aktivní, ne při pouhém přepsání souborů
 * pluginu beze změny stavu aktivace – bez tohohle by administrátor novou
 * capabilitu nikdy nedostal a „Sezóna & čekací list" by mu zmizela z GRID
 * Nastavení i z GARRY Nastavení (viz current_user_can() výše).
 */
add_action( 'admin_init', function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( GARRY_SEZ_STAFF_CAP ) ) {
		$role->add_cap( GARRY_SEZ_STAFF_CAP );
	}
	if ( ! wp_next_scheduled( 'garry_sez_daily_retention' ) ) {
		wp_schedule_event( time(), 'daily', 'garry_sez_daily_retention' );
	}
} );

/**
 * Přidání/uložení jednoho záznamu do logu poptávek — JEDINÉ místo v pluginu,
 * kudy log prochází (nahrazuje 3 dřívější kopie stejného $log[]=...;
 * update_option(...) bloku). Ukládá strojově čitelný 'ts' navíc k
 * lidsky čitelnému 'cas' – bez něj by denní retence (níže) neměla podle
 * čeho mazat staré záznamy, protože 'cas' je jen formátovaný český string.
 */
function garry_sez_log_add( array $entry ) {
	$entry['ts'] = current_time( 'timestamp' );
	$log = get_option( GARRY_SEZ_LOG, array() );
	if ( ! is_array( $log ) ) $log = array();
	$log[] = $entry;
	if ( count( $log ) > 300 ) $log = array_slice( $log, -300 );
	update_option( GARRY_SEZ_LOG, $log, false );
	return $entry;
}

/**
 * Denní retence (GRID-SUITE-03 §9) — jediná deklarovaná hodnota
 * GARRY_SEZ_LOG_RETENTION_DAYS, skutečně vynucená, ne jen v manifestu.
 * Starší záznamy BEZ 'ts' (existující log před touhle verzí) se při prvním
 * běhu ochrání – dostanou 'ts' = teď, ne okamžité smazání – aby update
 * pluginu jednorázově nesmazal legitimní čerstvá data jen proto, že jim
 * chyběl strojový časový údaj (master plán: migrace nesmí tiše mazat
 * klientský obsah).
 */
function garry_sez_prune_log_by_age() {
	$log = get_option( GARRY_SEZ_LOG, array() );
	if ( ! is_array( $log ) || ! $log ) return;
	$cutoff  = current_time( 'timestamp' ) - GARRY_SEZ_LOG_RETENTION_DAYS * DAY_IN_SECONDS;
	$changed = false;
	foreach ( $log as &$entry ) {
		if ( ! isset( $entry['ts'] ) ) {
			$entry['ts'] = current_time( 'timestamp' );
			$changed = true;
		}
	}
	unset( $entry );
	$kept = array_values( array_filter( $log, function ( $e ) use ( $cutoff ) { return $e['ts'] >= $cutoff; } ) );
	if ( $changed || count( $kept ) !== count( $log ) ) {
		update_option( GARRY_SEZ_LOG, $kept, false );
	}
}
add_action( 'garry_sez_daily_retention', 'garry_sez_prune_log_by_age' );

/**
 * Rate limit (GRID-SUITE-03 §2/§8) — public nonce sama o sobě proti zneužití
 * nechrání (kdokoli může nonce načíst a odeslat request opakovaně). Krátký
 * transient limiter na hash IP+bucket, WordPress saltem – IP se nikde
 * neukládá v čitelné podobě jen kvůli limitu.
 *
 * @param string $bucket Rozlišuje formulář (např. 'sez_request', 'voucher').
 * @return bool True, pokud je požadavek nad limitem (má se odmítnout).
 */
function garry_sez_is_rate_limited( $bucket ) {
	$ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
	$key = 'garry_sez_rl_' . substr( hash_hmac( 'sha256', $bucket . '|' . $ip, wp_salt() ), 0, 40 );
	$count = (int) get_transient( $key );
	if ( $count >= GARRY_SEZ_RATE_LIMIT_MAX ) {
		return true;
	}
	set_transient( $key, $count + 1, GARRY_SEZ_RATE_LIMIT_WINDOW );
	return false;
}

function garry_sez_grid_visible() {
	$v = get_option( 'garry_grid_visibility', array() );
	if ( ! is_array( $v ) || ! array_key_exists( 'garry-sezona-cekaci-list', $v ) ) {
		return true;
	}
	return ! empty( $v['garry-sezona-cekaci-list'] );
}

function garry_sez_save_grid_visibility() {
	if ( ! isset( $_POST['garry_sez_grid_visibility_nonce'] )
		|| ! wp_verify_nonce( $_POST['garry_sez_grid_visibility_nonce'], 'garry_sez_grid_visibility' )
		|| ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$v = get_option( 'garry_grid_visibility', array() );
	if ( ! is_array( $v ) ) $v = array();
	$v['garry-sezona-cekaci-list'] = empty( $_POST['garry_sez_grid_visible'] ) ? 0 : 1;
	update_option( 'garry_grid_visibility', $v, false );
	echo '<div class="notice notice-success is-dismissible"><p>Viditelnost v GRID Nastavení uložena.</p></div>';
}

add_action( 'admin_menu', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) return;
	if ( ! garry_sez_grid_visible() ) return;
	add_submenu_page( 'grid-options', 'Sezóna & čekací list', 'Sezóna & čekací list',
		GARRY_SEZ_STAFF_CAP, 'garry-sezona-grid', 'garry_sez_admin_page' );
}, 100 );

add_action( 'admin_init', function () { register_setting( 'garry_sez_group', GARRY_SEZ_OPT, 'garry_sez_sanitize' ); } );
/* personál (Editor) smí ukládat přes options.php */
add_filter( 'option_page_capability_garry_sez_group', function () { return GARRY_SEZ_STAFF_CAP; } );
function garry_sez_sanitize( $in ) {
	$puvodni = get_option( GARRY_SEZ_OPT, array() );
	$out = array( 'events' => array(), 'states' => array(), 'email' => '' );
	if ( ! is_array( $in ) ) return $out;
	$out['email'] = sanitize_email( $in['email'] ?? '' );

	$out['max_pripravovanych'] = max( 1, min( 50, (int) ( $in['max_pripravovanych'] ?? 5 ) ) );
	$out['import_aktivni'] = empty( $in['import_aktivni'] ) ? 0 : 1;
	$periody = function_exists( 'garry_sez_periody' ) ? array_keys( garry_sez_periody() ) : array( 'weekly' );
	$out['import_perioda'] = in_array( $in['import_perioda'] ?? '', $periody, true ) ? $in['import_perioda'] : 'weekly';
	/* Stav posledního běhu zapisuje import, ne formulář — kdyby se přenášel
	   skrytým polem, přepsal by ho každé uložení nastavení. */
	foreach ( array( 'import_posledni', 'import_stav', 'import_rucne' ) as $k ) {
		$out[ $k ] = is_array( $puvodni ) ? ( $puvodni[ $k ] ?? '' ) : '';
	}

	/* štítky */
	$st = $in['states'] ?? array();
	$n = count( $st['key'] ?? array() );
	for ( $i = 0; $i < $n; $i++ ) {
		$key = sanitize_key( $st['key'][ $i ] ?? '' );
		$cz  = sanitize_text_field( $st['cz'][ $i ] ?? '' );
		if ( $key === '' && $cz !== '' ) $key = sanitize_key( sanitize_title( $cz ) );
		if ( $key === '' ) continue;
		$legacy = in_array( $st['legacy'][ $i ] ?? '', array( 'free', 'few', 'full' ), true ) ? $st['legacy'][ $i ] : 'free';
		$color  = preg_match( '/^#[0-9a-fA-F]{6}$/', $st['color'][ $i ] ?? '' ) ? $st['color'][ $i ] : '#B9B7B9';
		$out['states'][] = array(
			'key' => $key, 'cz' => $cz,
			'en' => sanitize_text_field( $st['en'][ $i ] ?? '' ),
			'de' => sanitize_text_field( $st['de'][ $i ] ?? '' ),
			'cta_cz' => sanitize_text_field( $st['cta_cz'][ $i ] ?? '' ),
			'cta_en' => sanitize_text_field( $st['cta_en'][ $i ] ?? '' ),
			'cta_de' => sanitize_text_field( $st['cta_de'][ $i ] ?? '' ),
			'color' => $color, 'legacy' => $legacy,
		);
	}
	if ( ! $out['states'] ) $out['states'] = garry_sez_default_states();
	$state_keys = wp_list_pluck( $out['states'], 'key' );

	/* akce */
	$e = $in['events'] ?? array();
	$n = max( count( $e['cz'] ?? array() ), count( $e['od'] ?? array() ) );
	for ( $i = 0; $i < $n; $i++ ) {
		$row = array(
			'od'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $e['od'][ $i ] ?? '' ) ? $e['od'][ $i ] : '',
			'do'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $e['do'][ $i ] ?? '' ) ? $e['do'][ $i ] : '',
			'cz'   => sanitize_text_field( $e['cz'][ $i ] ?? '' ),
			'en'   => sanitize_text_field( $e['en'][ $i ] ?? '' ),
			'de'   => sanitize_text_field( $e['de'][ $i ] ?? '' ),
			'pcz'  => sanitize_text_field( $e['pcz'][ $i ] ?? '' ),
			'pen'  => sanitize_text_field( $e['pen'][ $i ] ?? '' ),
			'pde'  => sanitize_text_field( $e['pde'][ $i ] ?? '' ),
			'stav' => in_array( $e['stav'][ $i ] ?? '', $state_keys, true ) ? $e['stav'][ $i ] : ( $state_keys[0] ?? 'volne' ),
			'url'  => esc_url_raw( $e['url'][ $i ] ?? '' ),
			'dcz'  => sanitize_textarea_field( $e['dcz'][ $i ] ?? '' ),
			'den'  => sanitize_textarea_field( $e['den'][ $i ] ?? '' ),
			'dde'  => sanitize_textarea_field( $e['dde'][ $i ] ?? '' ),
			'url_en' => esc_url_raw( $e['url_en'][ $i ] ?? '' ),
			'publikovano' => empty( $e['publikovano'][ $i ] ) ? 0 : 1,
			/* Příznak „nová akce" zhasne v okamžiku publikace — dokud akce visí
			   nepublikovaná, zůstává v upozornění na nástěnce. */
			'zdroj'   => sanitize_key( $e['zdroj'][ $i ] ?? 'rucne' ),
			'nacteno' => sanitize_text_field( $e['nacteno'][ $i ] ?? '' ),
		);
		$row['nova'] = ( ! empty( $e['nova'][ $i ] ) && ! $row['publikovano'] ) ? 1 : 0;
		if ( $row['cz'] === '' && $row['od'] === '' ) continue;
		$out['events'][] = $row;
	}
	usort( $out['events'], function ( $a, $b ) { return strcmp( $a['od'], $b['od'] ); } );
	return $out;
}

/* smazání logu */
add_action( 'admin_post_garry_sez_clear_log', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'garry_sez_clear_log' ) ) wp_die( 'Nedostatečná oprávnění.' );
	delete_option( GARRY_SEZ_LOG );
	wp_safe_redirect( add_query_arg( array( 'page' => $_REQUEST['back'] ?? 'garry-sezona-cekaci-list', 'tab' => 'log', 'cleared' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
} );

/* ============================================================================
 * Administrace — karty: Akce | Štítky obsazenosti | Poptávky
 * ============================================================================ */
function garry_sez_admin_page() {
	if ( ! current_user_can( GARRY_SEZ_STAFF_CAP ) ) return;
	$s = garry_sez_get();
	$states = garry_sez_states();
	$events = $s['events'];
	if ( ! $events ) $events = array( array( 'od'=>'','do'=>'','cz'=>'','en'=>'','de'=>'','pcz'=>'','pen'=>'','pde'=>'','stav'=>array_key_first( $states ) ) );
	$log = get_option( GARRY_SEZ_LOG, array() ); if ( ! is_array( $log ) ) $log = array();
	$O = GARRY_SEZ_OPT;
	$page_slug = $_GET['page'] ?? 'garry-sezona-cekaci-list';
	$active = $_GET['tab'] ?? 'akce';
	if ( ! in_array( $active, array( 'akce', 'stitky', 'log' ), true ) ) $active = 'akce';
	?>
	<style>
	  /* Stav akce pozná redakce i se zavřeným detailem: barva proužku vlevo,
	     tečka a odznak. Nová akce je jantarová, publikovaná zelená, ostatní šedé. */
	  .sez-event{background:#fff;border:1px solid #c3c4c7;border-left:4px solid #c3c4c7;border-radius:8px;padding:10px 16px;margin-bottom:14px}
	  .sez-event.je-nova{border-left-color:#dba617;background:#fffaf0}
	  .sez-event.je-na-webu{border-left-color:#68C020}
	  .sez-event.je-publikovana:not(.je-na-webu){border-left-color:#8c8f94}
	  .sez-event > summary{cursor:pointer;display:flex;gap:12px;align-items:center;flex-wrap:wrap;padding:4px 0;list-style-position:outside}
	  .sez-event .sez-znacka{flex:none;width:9px;height:9px;border-radius:50%;background:#c3c4c7}
	  .sez-event.je-nova .sez-znacka{background:#dba617}
	  .sez-event.je-na-webu .sez-znacka{background:#68C020}
	  .sez-odznak{font-size:11px;letter-spacing:.04em;padding:2px 8px;border-radius:10px;white-space:nowrap}
	  .sez-odznak--nova{background:#dba617;color:#fff}
	  .sez-odznak--chybi{background:#fcf0f1;color:#b32d2e;border:1px solid #f0c5c7}
	  .sez-odznak--web{background:#edf7e6;color:#3a6b12;border:1px solid #c6e3ad}
	  .sez-odznak--nad-limit,.sez-odznak--skryta{background:#f0f0f1;color:#646970;border:1px solid #dcdcde}
	  .sez-event .sez-sum-meta{margin-right:auto}
	  .sez-hlaska{margin:8px 0 0;padding:8px 12px;background:#fcf0f1;border-left:3px solid #b32d2e;color:#8a1f1f;font-size:13px}
	  .sez-publikace{display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin:10px 0 0;padding:8px 12px;background:#f6f7f7;border-radius:4px}
	  /* Prázdné pole, které je potřeba doplnit před publikací. */
	  .sez-pole.sez-chybi input,.sez-pole.sez-chybi textarea{background:#fcf0f1;border-color:#e0a3a6}
	  .sez-pole.sez-chybi{color:#8a1f1f}
	  .sez-skupina{margin:22px 0 10px;font-size:15px}
	  .sez-uplynule > summary{cursor:pointer;list-style-position:outside}
	  .sez-uplynule > summary h3{display:inline-block;margin:0}
	  .sez-uplynule .sez-event{opacity:.72}
	  .sez-event.je-probehla{border-left-color:#c3c4c7}
	  .sez-event.je-probehla .sez-znacka{background:#c3c4c7}
	  .sez-panel{margin:16px 0 6px;padding:14px 18px;background:#fff;border:1px solid #c3c4c7;border-radius:8px}
	  .sez-panel-stav{color:#50575e;font-size:13px}
	</style>
	<div class="wrap"><h1>Sezóna & čekací list</h1>
	<?php if ( isset( $_GET['cleared'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Log poptávek byl smazán.</p></div>'; ?>
	<h2 class="nav-tab-wrapper" id="sez-tabs">
	  <a href="#" class="nav-tab" data-tab="akce">Akce sezóny</a>
	  <a href="#" class="nav-tab" data-tab="stitky">Štítky obsazenosti</a>
	  <a href="#" class="nav-tab" data-tab="log">Poptávky <?php if ( $log ) printf( '<span class="awaiting-mod count-%1$d"><span>%1$d</span></span>', count( $log ) ); ?></a>
	</h2>

	<form method="post" action="options.php">
	<?php settings_fields( 'garry_sez_group' ); ?>

	<!-- ================== KARTA: AKCE ================== -->
	<div class="sez-tab" data-tab="akce">
	  <div style="display:flex;gap:26px;flex-wrap:wrap;align-items:flex-start;margin-top:16px">
	    <div style="flex:1 1 620px;min-width:560px" id="sez-events">
	      <?php if ( isset( $_GET['import'] ) ) : ?>
	        <div class="notice notice-<?php echo $_GET['import'] === 'chyba' ? 'error' : 'success'; ?> is-dismissible"><p>
	          <?php echo $_GET['import'] === 'chyba'
	            ? esc_html( 'Import se nepovedl: ' . ( $s['import_stav'] ?? '' ) )
	            : esc_html( sprintf( 'Import hotov — nových akcí: %d.', (int) $_GET['import'] ) ); ?>
	        </p></div>
	      <?php endif; ?>

	      <div class="sez-panel">
	        <h3 style="margin-top:0">Automatický import z kalendáře Automotodromu</h3>
	        <p class="description" style="max-width:760px">
	          Ve zvoleném intervalu se načte <a href="https://www.automotodrombrno.cz/kalendar-akci/zavody/" target="_blank" rel="noopener">výpis závodů</a>
	          a nové termíny se sem zapíšou jako <strong>nepublikované</strong>. Zdroj neumí němčinu a angličtinu má jen
	          na detailech akcí, takže překlady je potřeba doplnit ručně — proto se nic nezveřejní samo.
	        </p>
	        <p>
	          <label><input type="checkbox" name="<?php echo $O; ?>[import_aktivni]" value="1" <?php checked( ! empty( $s['import_aktivni'] ) ); ?>> Kontrolovat kalendář automaticky</label>
	          &nbsp;
	          <label>Jak často
	            <select name="<?php echo $O; ?>[import_perioda]">
	              <?php foreach ( garry_sez_periody() as $klic => $perioda ) : ?>
	                <option value="<?php echo esc_attr( $klic ); ?>" <?php selected( garry_sez_perioda(), $klic ); ?>>
	                  <?php echo esc_html( $perioda['popisek'] ); ?>
	                </option>
	              <?php endforeach; ?>
	            </select>
	          </label>
	          <span class="description">Vždy ve 3:20 ráno. Změna periody se projeví po uložení.</span>
	        </p>
	        <p>
	          <label>Nejvíc připravovaných akcí na webu
	            <input type="number" min="1" max="50" style="width:80px" name="<?php echo $O; ?>[max_pripravovanych]" value="<?php echo esc_attr( (string) ( $s['max_pripravovanych'] ?? 5 ) ); ?>">
	          </label>
	          <span class="description">Publikované akce nad tímto počtem zůstanou uložené, ale na web se nedostanou.</span>
	        </p>
	        <p class="sez-panel-stav">
	          <?php if ( ! empty( $s['import_posledni'] ) ) : ?>
	            Poslední běh: <strong><?php echo esc_html( $s['import_posledni'] ); ?></strong> — <?php echo esc_html( (string) ( $s['import_stav'] ?? '' ) ); ?>
	          <?php else : ?>
	            Import zatím neproběhl.
	          <?php endif; ?>
	          <?php $dalsi = wp_next_scheduled( 'garry_sez_tydenni_import' ); ?>
	          <?php if ( $dalsi ) :
	            $periody = garry_sez_periody();
	            $bezici = wp_get_schedule( 'garry_sez_tydenni_import' ); ?>
	            <br>Další běh: <?php echo esc_html( wp_date( 'j. n. Y H:i', $dalsi ) ); ?>
	            <?php if ( isset( $periody[ $bezici ] ) ) : ?>
	              (<?php echo esc_html( mb_strtolower( $periody[ $bezici ]['popisek'] ) ); ?>)
	            <?php endif; ?>
	          <?php endif; ?>
	        </p>
	        <p>
	          <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=garry_sez_import_ted&back=' . urlencode( $page_slug ) ), 'garry_sez_import_ted' ) ); ?>">Načíst kalendář teď</a>
	          <span class="description">Změny v tomhle panelu uložte tlačítkem dole; ruční načtení běží zvlášť.</span>
	        </p>
	      </div>

	      <?php
	      list( $pripravovane, $uplynule ) = garry_sez_rozdel_akce( $events );
	      $max_zobrazenych = (int) ( $s['max_pripravovanych'] ?? 5 );
	      $poradi_publikovanych = 0;

	      /* Vykreslení jedné akce. Pořadí polí v odeslaném formuláři musí sedět
	         napříč oběma skupinami, proto se obě vypisují do stejné fronty. */
	      $poradi_pole = 0;
	      $vykresli = function ( $ev, $pripravovana = true ) use ( $O, $states, &$poradi_publikovanych, &$poradi_pole, $max_zobrazenych ) {
	        $chybi = garry_sez_chybi( $ev );
	        $je_nova = ! empty( $ev['nova'] );
	        $je_publ = ! empty( $ev['publikovano'] );
	        /* Do limitu se počítají jen připravované akce — uplynulá se na web
	           nedostane, ať je publikovaná nebo ne, a odznak „nad limit" by u ní
	           mátl. */
	        $na_webu = false;
	        if ( $je_publ && $pripravovana ) {
	          $poradi_publikovanych++;
	          $na_webu = $poradi_publikovanych <= $max_zobrazenych;
	        }
	        $tridy = 'sez-event' . ( $je_nova ? ' je-nova' : '' ) . ( $je_publ ? ' je-publikovana' : ' je-skryta' ) . ( $na_webu ? ' je-na-webu' : '' ) . ( $pripravovana ? '' : ' je-probehla' );
	        $pole = function ( $klic ) use ( $chybi ) { return isset( $chybi[ $klic ] ) ? ' sez-chybi' : ''; };
	        ?>
	      <details class="<?php echo esc_attr( $tridy ); ?>" <?php echo $je_nova ? 'open' : ''; ?>>
	        <summary>
	          <span class="sez-znacka" aria-hidden="true"></span>
	          <strong class="sez-sum-name"><?php echo esc_html( $ev['cz'] ?: 'Nová akce' ); ?></strong>
	          <span class="sez-sum-meta description"><?php echo esc_html( trim( ( $ev['od'] ?: '' ) . ( $ev['do'] && $ev['do'] !== $ev['od'] ? ' – ' . $ev['do'] : '' ) ) ); ?></span>
	          <?php if ( $je_nova ) : ?>
	            <span class="sez-odznak sez-odznak--nova">Nová akce</span>
	          <?php endif; ?>
	          <?php if ( $chybi ) : ?>
	            <span class="sez-odznak sez-odznak--chybi"><?php printf( 'Chybí %d %s', count( $chybi ), count( $chybi ) === 1 ? 'položka' : ( count( $chybi ) < 5 ? 'položky' : 'položek' ) ); ?></span>
	          <?php endif; ?>
	          <?php if ( ! $pripravovana ) : ?>
	            <span class="sez-odznak sez-odznak--skryta" title="Termín už proběhl, na web se nevypisuje">Proběhla</span>
	          <?php elseif ( $na_webu ) : ?>
	            <span class="sez-odznak sez-odznak--web" title="Zobrazuje se na webu">● Na webu</span>
	          <?php elseif ( $je_publ ) : ?>
	            <span class="sez-odznak sez-odznak--nad-limit" title="Publikovaná, ale nad nastaveným limitem">Publikovaná (nad limit)</span>
	          <?php else : ?>
	            <span class="sez-odznak sez-odznak--skryta">Nepublikovaná</span>
	          <?php endif; ?>
	        </summary>
	        <?php if ( $chybi ) : ?>
	          <p class="sez-hlaska">Před publikací doplňte: <strong><?php echo esc_html( implode( ', ', $chybi ) ); ?></strong>. Červeně podbarvená pole jsou prázdná.</p>
	        <?php endif; ?>
	        <div class="sez-publikace">
	          <label><input type="checkbox" name="<?php echo $O; ?>[events][publikovano][<?php echo (int) $poradi_pole; ?>]" value="1" <?php checked( $je_publ ); ?> class="sez-publ"> <strong>Publikovat na webu</strong></label>
	          <input type="hidden" name="<?php echo $O; ?>[events][nova][<?php echo (int) $poradi_pole; ?>]" value="<?php echo $je_nova ? 1 : 0; ?>">
	          <input type="hidden" name="<?php echo $O; ?>[events][zdroj][<?php echo (int) $poradi_pole; ?>]" value="<?php echo esc_attr( $ev['zdroj'] ?? 'rucne' ); ?>">
	          <input type="hidden" name="<?php echo $O; ?>[events][nacteno][<?php echo (int) $poradi_pole; ?>]" value="<?php echo esc_attr( $ev['nacteno'] ?? '' ); ?>">
	          <?php if ( ! empty( $ev['nacteno'] ) ) : ?>
	            <span class="description">Načteno z kalendáře Automotodromu <?php echo esc_html( $ev['nacteno'] ); ?></span>
	          <?php endif; ?>
	        </div>
	        <div class="sez-event-body" style="padding-top:10px;border-top:1px solid #eee;margin-top:8px">
	        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:10px">
	          <label>Od <input type="date" name="<?php echo $O; ?>[events][od][]" value="<?php echo esc_attr( $ev['od'] ); ?>"></label>
	          <label>Do <input type="date" name="<?php echo $O; ?>[events][do][]" value="<?php echo esc_attr( $ev['do'] ); ?>"></label>
	          <label>Obsazenost <select name="<?php echo $O; ?>[events][stav][]" class="sez-ev-stav">
	            <?php foreach ( $states as $k => $st ) printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $ev['stav'], $k, false ), esc_html( $st['cz'] ) ); ?>
	          </select></label>
	          <label style="flex:1;min-width:240px">Detail akce — CZ <input type="url" style="width:100%" name="<?php echo $O; ?>[events][url][]" value="<?php echo esc_attr( $ev['url'] ?? '' ); ?>" placeholder="https://www.automotodrombrno.cz/…"></label>
	          <label style="flex:1;min-width:240px">Detail akce — EN <input type="url" style="width:100%" name="<?php echo $O; ?>[events][url_en][]" value="<?php echo esc_attr( $ev['url_en'] ?? '' ); ?>" placeholder="https://www.automotodrombrno.cz/en/…"><span class="description">Použije se pro anglickou i německou verzi webu — zdroj němčinu nemá.</span></label>
	          <button type="button" class="button-link sez-ev-del" style="color:#b32d2e;margin-left:auto">Smazat akci ×</button>
	        </div>
	        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:8px">
	          <label>Název CZ<input type="text" style="width:100%" name="<?php echo $O; ?>[events][cz][]" value="<?php echo esc_attr( $ev['cz'] ); ?>"></label>
	          <label class="sez-pole<?php echo $pole( 'en' ); ?>">Název EN<input type="text" style="width:100%" name="<?php echo $O; ?>[events][en][]" value="<?php echo esc_attr( $ev['en'] ); ?>"></label>
	          <label class="sez-pole<?php echo $pole( 'de' ); ?>">Název DE<input type="text" style="width:100%" name="<?php echo $O; ?>[events][de][]" value="<?php echo esc_attr( $ev['de'] ); ?>"></label>
	        </div>
	        <div style="display:grid;grid-template-columns:1fr;gap:8px">
	          <label class="sez-pole<?php echo $pole( 'pcz' ); ?>">Perex CZ<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pcz][]" value="<?php echo esc_attr( $ev['pcz'] ); ?>" placeholder="Krátká věta do seznamu akcí"></label>
	          <label class="sez-pole<?php echo $pole( 'pen' ); ?>">Perex EN<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pen][]" value="<?php echo esc_attr( $ev['pen'] ); ?>"></label>
	          <label class="sez-pole<?php echo $pole( 'pde' ); ?>">Perex DE<input type="text" style="width:100%" name="<?php echo $O; ?>[events][pde][]" value="<?php echo esc_attr( $ev['pde'] ); ?>"></label>
	        </div>
	        <p class="description" style="margin:8px 0 4px">Detailní popis („O akci") — zobrazuje se v kartách akcí na stránce Sezóna:</p>
	        <div style="display:grid;grid-template-columns:1fr;gap:6px">
	          <label class="sez-pole<?php echo $pole( 'dcz' ); ?>">O akci CZ<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][dcz][]"><?php echo esc_textarea( $ev['dcz'] ?? '' ); ?></textarea></label>
	          <label class="sez-pole<?php echo $pole( 'den' ); ?>">O akci EN<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][den][]"><?php echo esc_textarea( $ev['den'] ?? '' ); ?></textarea></label>
	          <label class="sez-pole<?php echo $pole( 'dde' ); ?>">O akci DE<textarea style="width:100%" rows="2" name="<?php echo $O; ?>[events][dde][]"><?php echo esc_textarea( $ev['dde'] ?? '' ); ?></textarea></label>
	        </div>
	        </div>
	      
	      </details>
	        <?php
	      };
	      ?>

	      <h3 class="sez-skupina">Připravované akce <span class="description">(<?php echo count( $pripravovane ); ?>)</span></h3>
	      <p class="description" style="margin-top:-6px">Na webu se vypíše nejvýš <strong><?php echo (int) $max_zobrazenych; ?></strong> publikovaných, seřazených podle data.</p>
	      <?php foreach ( $pripravovane as $ev ) { $vykresli( $ev ); $poradi_pole++; } ?>
	      <?php if ( ! $pripravovane ) : ?><p class="description">Žádná připravovaná akce.</p><?php endif; ?>

	      <?php if ( $uplynule ) : ?>
	        <details class="sez-uplynule">
	          <summary><h3 class="sez-skupina">Uplynulé akce <span class="description">(<?php echo count( $uplynule ); ?>)</span></h3></summary>
	          <p class="description">Na web se nedostanou, ať jsou publikované, nebo ne. Zůstávají tu kvůli historii a pro případ, že se termín opakuje.</p>
	          <?php foreach ( $uplynule as $ev ) { $vykresli( $ev, false ); $poradi_pole++; } ?>
	        </details>
	      <?php endif; ?>
	      <p style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="button" id="sez-ev-add">+ Přidat akci</button>
	      <button type="button" class="button" id="sez-expand">Rozbalit vše</button>
	      <button type="button" class="button" id="sez-collapse">Sbalit vše</button></p>
	      <p><label><strong>E-mail pro poptávky z formuláře:</strong>
	        <input type="email" name="<?php echo $O; ?>[email]" value="<?php echo esc_attr( $s['email'] ); ?>" placeholder="reservations@gridhotel.cz" class="regular-text"></label><br>
	        <span class="description">Sem chodí žádosti o rezervaci / zápis na čekací list. Prázdné = e-mail správce webu. Odeslání formuláře lze chránit Google reCAPTCHA pluginem (hook <code>garry_sez_verify_request</code>).</span></p>
	    </div>
	    <div style="flex:0 0 380px">
	      <p style="font-weight:600;margin:0 0 8px">Náhled widgetu na stránce</p>
	      <div id="sez-preview" style="background:#0d0f12;border:1px solid #ccd0d4;border-radius:8px;padding:18px;min-height:280px;color:#e6e4e2;font-family:sans-serif"></div>
	      <p class="description" style="margin-top:8px">Živý náhled — 5 nejbližších akcí podle data, proběhlé se nezobrazují.</p>
	    </div>
	  </div>
	</div>

	<!-- ================== KARTA: ŠTÍTKY ================== -->
	<div class="sez-tab" data-tab="stitky" style="display:none">
	  <p style="margin-top:16px">Vlastní typy obsazenosti. <strong>Chování</strong> určuje, co formulář nabídne
	  (Rezervace = zelený režim, Poslední pokoje, Čekací list = zápis do čekací listiny).</p>
	  <table class="widefat striped" id="sez-states" style="max-width:1200px">
	    <thead><tr>
	      <th style="width:110px">Klíč</th><th>Štítek CZ</th><th>Štítek EN</th><th>Štítek DE</th>
	      <th>CTA CZ</th><th>CTA EN</th><th>CTA DE</th>
	      <th style="width:70px">Barva</th><th style="width:150px">Chování</th><th style="width:32px"></th>
	    </tr></thead><tbody>
	    <?php foreach ( $states as $k => $st ) : ?>
	      <tr>
	        <td><input type="text" style="width:100%" name="<?php echo $O; ?>[states][key][]" value="<?php echo esc_attr( $k ); ?>"></td>
	        <?php foreach ( array( 'cz','en','de','cta_cz','cta_en','cta_de' ) as $f ) : ?>
	          <td><input type="text" style="width:100%" name="<?php echo $O; ?>[states][<?php echo $f; ?>][]" value="<?php echo esc_attr( $st[ $f ] ); ?>"></td>
	        <?php endforeach; ?>
	        <td><input type="color" name="<?php echo $O; ?>[states][color][]" value="<?php echo esc_attr( $st['color'] ); ?>"></td>
	        <td><select name="<?php echo $O; ?>[states][legacy][]">
	          <option value="free" <?php selected( $st['legacy'], 'free' ); ?>>Rezervace (volno)</option>
	          <option value="few" <?php selected( $st['legacy'], 'few' ); ?>>Poslední pokoje</option>
	          <option value="full" <?php selected( $st['legacy'], 'full' ); ?>>Čekací list</option>
	        </select></td>
	        <td><button type="button" class="button-link sez-st-del" style="color:#b32d2e">×</button></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><button type="button" class="button" id="sez-st-add">+ Přidat štítek</button></p>
	</div>

	<!-- ================== KARTA: LOG ================== -->
	<div class="sez-tab" data-tab="log" style="display:none">
	  <p style="margin-top:16px">Poptávky odeslané z formuláře na webu (nejnovější nahoře). Kopie chodí na nastavený e-mail.</p>
	  <?php if ( ! $log ) : ?>
	    <p><em>Zatím žádné poptávky.</em></p>
	  <?php else : ?>
	  <table class="widefat striped" style="max-width:1100px">
	    <thead><tr><th>Datum a čas</th><th>Akce / termín</th><th>Typ pokoje</th><th>Jméno</th><th>E-mail</th><th>Jazyk</th><th>IP</th></tr></thead><tbody>
	    <?php foreach ( array_reverse( $log ) as $r ) : ?>
	      <tr>
	        <td><?php echo esc_html( $r['cas'] ?? '' ); ?></td>
	        <td><strong><?php echo esc_html( $r['akce'] ?? '' ); ?></strong></td>
	        <td><?php echo esc_html( $r['pokoj'] ?? '' ); ?></td>
	        <td><?php echo esc_html( $r['jmeno'] ?? '' ); ?></td>
	        <td><a href="mailto:<?php echo esc_attr( $r['email'] ?? '' ); ?>"><?php echo esc_html( $r['email'] ?? '' ); ?></a></td>
	        <td><?php echo esc_html( strtoupper( $r['jazyk'] ?? '' ) ); ?></td>
	        <td><?php echo esc_html( $r['ip'] ?? '' ); ?></td>
	      </tr>
	    <?php endforeach; ?>
	    </tbody></table>
	  <p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=garry_sez_clear_log&back=' . urlencode( $page_slug ) ), 'garry_sez_clear_log' ) ); ?>"
	    onclick="return confirm('Opravdu smazat celý log poptávek?');">Smazat log</a></p>
	  <?php endif; ?>
	</div>

	<?php submit_button( 'Uložit' ); ?>
	</form></div>

	<script>
	(function(){
	  /* přepínání karet */
	  var tabs=document.querySelectorAll('#sez-tabs .nav-tab');
	  function activate(name){
	    tabs.forEach(function(t){ t.classList.toggle('nav-tab-active', t.getAttribute('data-tab')===name); });
	    document.querySelectorAll('.sez-tab').forEach(function(d){ d.style.display = d.getAttribute('data-tab')===name ? '' : 'none'; });
	  }
	  tabs.forEach(function(t){ t.addEventListener('click', function(e){ e.preventDefault(); activate(t.getAttribute('data-tab')); }); });
	  activate(<?php echo wp_json_encode( $active ); ?>);

	  /* akce: přidat/smazat kartu */
	  var evWrap=document.getElementById('sez-events');
	  document.getElementById('sez-ev-add').addEventListener('click', function(){
	    var cards=evWrap.querySelectorAll('.sez-event');
	    var c=cards[cards.length-1].cloneNode(true);
	    c.querySelectorAll('input,textarea').forEach(function(i){ i.value=''; });
	    c.querySelector('select').selectedIndex=0;
	    c.setAttribute('open','');
	    var sn=c.querySelector('.sez-sum-name'); if(sn) sn.textContent='Nová akce';
	    var sm=c.querySelector('.sez-sum-meta'); if(sm) sm.textContent='';
	    evWrap.insertBefore(c, cards[cards.length-1].nextSibling);
	    preindexuj();
	    preview();
	  });
	  /* Zaškrtávátko „publikovat" a skrytá pole mají pevný index, protože
	     nezaškrtnutý checkbox se vůbec neodešle a pořadí by se rozešlo se
	     zbytkem formuláře. Po přidání i smazání karty se proto přečíslují. */
	  function preindexuj(){
	    evWrap.querySelectorAll('.sez-event').forEach(function(karta,i){
	      karta.querySelectorAll('input[name*="[events]["]').forEach(function(pole){
	        pole.name = pole.name.replace(/\[(publikovano|nova|zdroj|nacteno)\]\[\d*\]/, '[$1][' + i + ']');
	      });
	    });
	  }
	  document.getElementById('sez-expand').addEventListener('click', function(){ evWrap.querySelectorAll('.sez-event').forEach(function(d){ d.setAttribute('open',''); }); });
	  document.getElementById('sez-collapse').addEventListener('click', function(){ evWrap.querySelectorAll('.sez-event').forEach(function(d){ d.removeAttribute('open'); }); });
	  /* souhrn karty se aktualizuje při psaní */
	  evWrap.addEventListener('input', function(e){
	    var card=e.target.closest('.sez-event'); if(!card) return;
	    var q=function(sel){ var el=card.querySelector(sel); return el?el.value:''; };
	    var sn=card.querySelector('.sez-sum-name'); if(sn) sn.textContent=q('input[name$="[events][cz][]"]')||'Nová akce';
	    var od=q('input[name$="[events][od][]"]'), dd=q('input[name$="[events][do][]"]');
	    var sm=card.querySelector('.sez-sum-meta'); if(sm) sm.textContent=od+(dd&&dd!==od?' – '+dd:'');
	  });
	  document.addEventListener('click', function(e){
	    if(e.target.classList.contains('sez-ev-del')){
	      var cards=evWrap.querySelectorAll('.sez-event');
	      if(cards.length>1) e.target.closest('.sez-event').remove();
	      else e.target.closest('.sez-event').querySelectorAll('input').forEach(function(i){ i.value=''; });
	      preindexuj();
	      preview();
	    }
	    if(e.target.classList.contains('sez-st-del')){
	      var tb=e.target.closest('tbody');
	      if(tb.rows.length>1) e.target.closest('tr').remove();
	      preview();
	    }
	  });
	  /* štítky: přidat řádek */
	  document.getElementById('sez-st-add').addEventListener('click', function(){
	    var tb=document.querySelector('#sez-states tbody');
	    var tr=tb.rows[tb.rows.length-1].cloneNode(true);
	    tr.querySelectorAll('input[type=text]').forEach(function(i){ i.value=''; });
	    tb.appendChild(tr);
	  });

	  /* živý náhled widgetu */
	  function stateData(){
	    var out={}; var tb=document.querySelector('#sez-states tbody');
	    [].forEach.call(tb.rows, function(tr){
	      var i=tr.querySelectorAll('input,select');
	      var key=i[0].value.trim(); if(!key) return;
	      out[key]={label:i[1].value||key,color:i[7].value||'#B9B7B9'};
	    });
	    return out;
	  }
	  function fmtDate(od,dodate){
	    if(!od) return '';
	    function cz(d){ return d.getDate()+'. '+(d.getMonth()+1)+'.'; }
	    var a=new Date(od+'T12:00:00');
	    if(!dodate||od===dodate) return cz(a)+' '+a.getFullYear();
	    var b=new Date(dodate+'T12:00:00');
	    if(a.getMonth()===b.getMonth()&&a.getFullYear()===b.getFullYear()) return a.getDate()+'.–'+cz(b)+' '+b.getFullYear();
	    return cz(a)+' – '+cz(b)+' '+b.getFullYear();
	  }
	  function preview(){
	    var box=document.getElementById('sez-preview'); if(!box) return;
	    var st=stateData(); var today=new Date().toISOString().slice(0,10);
	    var items=[];
	    evWrap.querySelectorAll('.sez-event').forEach(function(card){
	      var q=function(sel){ var el=card.querySelector(sel); return el?el.value:''; };
	      var od=q('input[name$="[events][od][]"]'), dd=q('input[name$="[events][do][]"]');
	      var name=q('input[name$="[events][cz][]"]'), per=q('input[name$="[events][pcz][]"]');
	      var stav=card.querySelector('.sez-ev-stav').value;
	      if(!name&&!od) return;
	      if((dd||od) && (dd||od)<today) return;   // proběhlé
	      items.push({od:od,dd:dd,name:name,per:per,stav:stav});
	    });
	    items.sort(function(a,b){ return (a.od||'9999').localeCompare(b.od||'9999'); });
	    items=items.slice(0,5);
	    var h='<div style="font-family:monospace;font-size:10px;letter-spacing:.16em;color:#caa75f;text-transform:uppercase;margin-bottom:12px">T6 · Sezóna · čekací list</div>';
	    if(!items.length) h+='<em style="color:#8F8E90">Žádné nadcházející akce — sekce se na webu skryje.</em>';
	    items.forEach(function(it){
	      var s=st[it.stav]||{label:it.stav,color:'#B9B7B9'};
	      h+='<div style="border-bottom:1px solid rgba(255,255,255,.12);padding:10px 0">'+
	        '<div style="display:flex;justify-content:space-between;gap:10px;align-items:center">'+
	        '<span style="font-family:monospace;font-size:11px;color:#FF5A50;white-space:nowrap">'+fmtDate(it.od,it.dd)+'</span>'+
	        '<span style="font-size:10px;font-family:monospace;letter-spacing:.08em;text-transform:uppercase;border:1px solid '+s.color+'99;color:'+s.color+';padding:3px 7px;border-radius:2px;white-space:nowrap">'+s.label+'</span></div>'+
	        '<div style="font-weight:700;text-transform:uppercase;margin-top:4px">'+(it.name||'—')+'</div>'+
	        (it.per?'<div style="font-size:11.5px;color:#B9B7B9">'+it.per+'</div>':'')+
	        '</div>';
	    });
	    box.innerHTML=h;
	  }
	  document.addEventListener('input', function(e){ if(e.target.closest('.sez-tab')) preview(); });
	  preview();
	})();
	</script>
	<?php
}

/* ============================================================================
 * Frontend — [grid_season_events limit="5"] + funkční formulář
 * ============================================================================ */
function garry_sez_fmt_range( $od, $do ) {
	if ( ! $od ) return '';
	try { $a = new DateTime( $od ); } catch ( Exception $e ) { return ''; }
	$b = null;
	if ( $do ) { try { $b = new DateTime( $do ); } catch ( Exception $e ) { $b = null; } }
	if ( ! $b || $od === $do ) return $a->format( 'j. n. Y' );
	if ( $a->format( 'Y-m' ) === $b->format( 'Y-m' ) ) return $a->format( 'j.' ) . '–' . $b->format( 'j. n. Y' );
	return $a->format( 'j. n.' ) . ' – ' . $b->format( 'j. n. Y' );
}
function garry_sez_render( $atts = array() ) {
	$a = shortcode_atts( array( 'limit' => 5, 'karty' => 0, 'rezim' => '' ), $atts );
	$rezim = $a['rezim'] ?: 'vse';
	$show_cards = ( $rezim === 'karty' ) || ( $rezim === 'vse' && ! empty( $a['karty'] ) );
	$show_list  = ( $rezim === 'seznam' || $rezim === 'vse' );
	$s = garry_sez_get(); $STATES = garry_sez_states(); $li = garry_sez_lang_idx();
	$today = current_time( 'Y-m-d' );
	/* Na web jdou jen publikované a jen připravované — uplynulé zůstávají
	   v administraci kvůli historii, návštěvníkovi by jen zabíraly místo. */
	$events = array_values( array_filter( $s['events'], function ( $e ) use ( $today ) {
		if ( empty( $e['publikovano'] ) ) return false;
		$konec = $e['do'] ?: $e['od'];
		return $konec === '' || $konec >= $today;
	} ) );
	usort( $events, function ( $x, $y ) { return strcmp( $x['od'], $y['od'] ); } );
	/* Počet řídí nastavení pluginu. Atribut shortcodu ho přebije jen kladnou
	   hodnotou — stránky mají v obsahu limit="0", což dřív znamenalo „bez
	   omezení"; teď to znamená „použij nastavení", jinak by se počet nedal
	   změnit bez zásahu do Divi obsahu. */
	$limit = (int) $a['limit'] > 0 ? (int) $a['limit'] : (int) ( $s['max_pripravovanych'] ?? 5 );
	if ( $limit > 0 ) $events = array_slice( $events, 0, $limit );
	if ( ! $events ) return '<style>#sezona{display:none}</style>';

	$T = array(
		array( 'Rezervace &amp; čekací list', 'Vyberte akci sezóny', 'Klikněte na termín vlevo, nebo vyberte akci níže. U vyprodaných termínů vás zapíšeme na čekací list.',
			'Akce / termín', 'Typ pokoje', 'Jméno a příjmení', 'Jan Novák', 'E-mail', 'vas@email.cz', 'Zapsat na čekací list',
			'✓ Hotovo! Ozveme se, jakmile se pro vybraný termín uvolní pokoj.', '// Termíny sezóny jsou orientační.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terasa)', 'Apartmá' ),
			'Odesílám…', 'Odeslání se nepovedlo — zkuste to prosím znovu, nebo nám napište na e-mail.' ),
		array( 'Booking &amp; waiting list', 'Choose your season event', "Click a date on the left or choose an event below. For sold-out dates we'll put you on the waiting list.",
			'Event / date', 'Room type', 'Full name', 'John Smith', 'E-mail', 'your@email.com', 'Join the waiting list',
			"✓ Done! We'll be in touch as soon as a room opens up for your chosen dates.", '// Season dates are indicative.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (terrace)', 'Apartment' ),
			'Sending…', 'Sending failed — please try again or contact us by e-mail.' ),
		array( 'Buchung &amp; Warteliste', 'Wählen Sie ein Saison-Event', 'Klicken Sie links auf einen Termin oder wählen Sie unten ein Event. Bei ausverkauften Terminen tragen wir Sie in die Warteliste ein.',
			'Event / Termin', 'Zimmertyp', 'Vor- und Nachname', 'Max Mustermann', 'E-Mail', 'ihre@email.de', 'In die Warteliste eintragen',
			'✓ Fertig! Wir melden uns, sobald für den gewählten Termin ein Zimmer frei wird.', '// Die Saisontermine sind unverbindlich.',
			array( 'Standard', 'Superior (track view)', 'Superior Plus (Terrasse)', 'Appartement' ),
			'Wird gesendet…', 'Senden fehlgeschlagen — bitte erneut versuchen oder per E-Mail kontaktieren.' ),
	);
	$t = $T[ $li ];

	$css = '';
	foreach ( $STATES as $k => $st ) {
		$css .= '.ev-status.' . sanitize_html_class( $k ) . '{color:' . $st['color'] . ' !important;border-color:' . $st['color'] . '99 !important}';
	}

	ob_start();
	echo '<style>' . $css . '</style>';
	?>
	<?php if ( $show_cards ) :
		$tx = array(
			array( 'Detail akce', 'Rezervace & čekací list' ),
			array( 'Event details', 'Booking & waiting list' ),
			array( 'Event-Details', 'Buchung & Warteliste' ),
		)[ $li ]; ?>
	<div class="sez-cards">
	  <?php foreach ( $events as $e ) :
			$name  = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
			$perex = $e[ array( 'pcz', 'pen', 'pde' )[ $li ] ] ?: $e['pcz'];
			$st = $STATES[ $e['stav'] ] ?? null; if ( ! $st ) continue; ?>
	  <div class="sez-card">
	    <div class="sez-card-top"><span class="ev-date"><?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></span>
	    <span class="ev-status <?php echo esc_attr( sanitize_html_class( $e['stav'] ) . ' ' . $st['legacy'] ); ?>"><?php echo esc_html( garry_sez_state_label( $st, $li ) ); ?></span></div>
	    <h3><?php echo esc_html( $name ); ?></h3>
	    <?php $detail = $e[ array( 'dcz', 'den', 'dde' )[ $li ] ] ?? ''; if ( $detail === '' ) $detail = ( $e['dcz'] ?? '' ) ?: $perex; ?>
	    <p><?php echo esc_html( $detail ); ?></p>
	    <div class="sez-card-links">
	      <?php $odkaz_akce = garry_sez_url_akce( $e, $li ); ?><?php if ( $odkaz_akce ) : ?><a class="sec-more" href="<?php echo esc_url( $odkaz_akce ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $tx[0] ); ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
	      <a class="sec-more" href="#cekaci-list"><?php echo esc_html( $tx[1] ); ?> <span aria-hidden="true">↓</span></a>
	    </div>
	  </div>
	  <?php endforeach; ?>
	</div>
	<?php endif; ?>
	<?php if ( $show_list ) : ?>
	<div class="season"<?php echo $rezim === 'vse' ? ' id="cekaci-list"' : ''; ?>>
	  <div class="ev-list reveal d1 in" id="evList">
	    <?php foreach ( $events as $e ) :
			$name  = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
			$perex = $e[ array( 'pcz', 'pen', 'pde' )[ $li ] ] ?: $e['pcz'];
			$st = $STATES[ $e['stav'] ] ?? null; if ( ! $st ) continue; ?>
	    <div class="ev-row" role="button" tabindex="0" data-ev="<?php echo esc_attr( $name ); ?>">
	      <span class="ev-date"><?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></span>
	      <span class="ev-name"><?php echo esc_html( $name ); ?><small><?php echo esc_html( $perex ); ?><?php $odkaz_akce = garry_sez_url_akce( $e, $li ); ?><?php if ( $odkaz_akce ) : ?> <a class="ev-ext" href="<?php echo esc_url( $odkaz_akce ); ?>" target="_blank" rel="noopener" onclick="event.stopPropagation()">↗</a><?php endif; ?></small></span>
	      <span class="ev-meta"><span class="ev-status <?php echo esc_attr( sanitize_html_class( $e['stav'] ) . ' ' . $st['legacy'] ); ?>"><?php echo esc_html( garry_sez_state_label( $st, $li ) ); ?></span>
	      <span class="ev-cta"><?php echo esc_html( garry_sez_state_cta( $st, $li ) ); ?></span></span>
	    </div>
	    <?php endforeach; ?>
	  </div>
	  <div class="waitbox reveal d2 in">
	    <span class="kicker"><?php echo $t[0]; ?></span>
	    <h3 id="wbTitle"><?php echo esc_html( $t[1] ); ?></h3>
	    <p class="wb-sub" id="wbSub"><?php echo esc_html( $t[2] ); ?></p>
	    <?php $wb_ff = garry_sez_ff_id( 'cekaci' ); if ( $wb_ff ) : ?>
	    <div class="wb-ff"><?php echo do_shortcode( '[fluentform id=' . $wb_ff . ']' ); ?></div>
	    <?php else : ?>
	    <form id="wbForm">
	      <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'garry_sez_request' ) ); ?>">
	      <input type="text" name="web" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
	      <div class="wb-field"><label for="wb-ev"><?php echo esc_html( $t[3] ); ?></label><select id="wb-ev" name="akce">
	        <?php foreach ( $events as $e ) :
				$name = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz']; ?>
	        <option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?> · <?php echo esc_html( garry_sez_fmt_range( $e['od'], $e['do'] ) ); ?></option>
	        <?php endforeach; ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-room"><?php echo esc_html( $t[4] ); ?></label><select id="wb-room" name="pokoj">
	        <?php foreach ( $t[12] as $i => $room ) printf( '<option %s>%s</option>', $i === 1 ? 'selected' : '', esc_html( $room ) ); ?>
	      </select></div>
	      <div class="wb-field"><label for="wb-name"><?php echo esc_html( $t[5] ); ?></label><input type="text" id="wb-name" name="jmeno" placeholder="<?php echo esc_attr( $t[6] ); ?>" required></div>
	      <div class="wb-field"><label for="wb-email"><?php echo esc_html( $t[7] ); ?></label><input type="email" id="wb-email" name="email" placeholder="<?php echo esc_attr( $t[8] ); ?>" required></div>
	      <button type="submit" class="btn" style="width:100%;text-align:center" id="wbBtn"><?php echo esc_html( $t[9] ); ?></button>
	      <div class="wb-ok" id="wbOk"><?php echo esc_html( $t[10] ); ?></div>
	    </form>
	    <?php endif; ?>
	    <p style="font-family:var(--f-mono);font-size:.66rem;color:var(--muted);margin-top:14px"><?php echo esc_html( $t[11] ); ?></p>
	  </div>
	</div>
	<?php endif; ?>
	<script>
	(function(){
	  var f=document.getElementById('wbForm'); if(!f) return;
	  var ok=document.getElementById('wbOk'), btn=document.getElementById('wbBtn');
	  var msg={sending:<?php echo wp_json_encode( $t[13] ); ?>, fail:<?php echo wp_json_encode( $t[14] ); ?>, done:<?php echo wp_json_encode( $t[10] ); ?>, btn:btn.textContent, lang:<?php echo wp_json_encode( garry_sez_lang() ); ?>};
	  f.addEventListener('submit', function(e){
	    e.preventDefault(); e.stopImmediatePropagation();   /* přebít fallback v grid.js */
	    if(!f.reportValidity()) return;
	    btn.disabled=true; btn.textContent=msg.sending;
	    var data=new FormData(f);
	    data.append('action','garry_sez_request');
	    data.append('jazyk', msg.lang);
	    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {method:'POST', body:data, credentials:'same-origin'})
	      .then(function(r){ return r.json(); })
	      .then(function(j){
	        btn.disabled=false; btn.textContent=msg.btn;
	        ok.textContent = j && j.success ? msg.done : (j && j.data && j.data.message ? j.data.message : msg.fail);
	        ok.classList.add('show');
	        if(j && j.success) f.reset();
	      })
	      .catch(function(){ btn.disabled=false; btn.textContent=msg.btn; ok.textContent=msg.fail; ok.classList.add('show'); });
	  }, true);
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_season_events', 'garry_sez_render' ); }, 5 );

/* ---------- Objednávka dárkového poukazu — [grid_voucher_form] ---------- */
function garry_voucher_kinds( $li ) {
	$T = array(
		array( 'Pokoj Superior — 1 osoba (3 750 Kč)', 'Pokoj Superior Plus — 1 osoba (4 250 Kč)', 'Apartmán — 1 osoba (6 625 Kč)',
			'Pokoj Superior — 2 osoby (4 500 Kč)', 'Pokoj Superior Plus — 2 osoby (5 000 Kč)', 'Apartmán — 2 osoby (6 875 Kč)' ),
		array( 'Superior room — 1 person (CZK 3,750)', 'Superior Plus room — 1 person (CZK 4,250)', 'Apartment — 1 person (CZK 6,625)',
			'Superior room — 2 persons (CZK 4,500)', 'Superior Plus room — 2 persons (CZK 5,000)', 'Apartment — 2 persons (CZK 6,875)' ),
		array( 'Zimmer Superior — 1 Person (3 750 CZK)', 'Zimmer Superior Plus — 1 Person (4 250 CZK)', 'Appartement — 1 Person (6 625 CZK)',
			'Zimmer Superior — 2 Personen (4 500 CZK)', 'Zimmer Superior Plus — 2 Personen (5 000 CZK)', 'Appartement — 2 Personen (6 875 CZK)' ),
	);
	return $T[ $li ];
}
function garry_voucher_form() {
	$li = garry_sez_lang_idx();
	$T = array(
		array( 'Objednat dárkový poukaz', 'Jméno', 'Příjmení', 'Druh poukazu', 'E-mail', 'Odeslat objednávku',
			'✓ Děkujeme! Objednávku poukazu jsme přijali — ozveme se se zálohovou fakturou.', 'Odesílám…', 'Odeslání se nepovedlo — zkuste to prosím znovu.' ),
		array( 'Order a gift voucher', 'First name', 'Last name', 'Voucher type', 'E-mail', 'Send order',
			"✓ Thank you! We've received your voucher order — we'll follow up with a proforma invoice.", 'Sending…', 'Sending failed — please try again.' ),
		array( 'Gutschein bestellen', 'Vorname', 'Nachname', 'Gutschein-Art', 'E-Mail', 'Bestellung senden',
			'✓ Vielen Dank! Wir haben Ihre Gutschein-Bestellung erhalten und melden uns mit der Vorausrechnung.', 'Wird gesendet…', 'Senden fehlgeschlagen — bitte erneut versuchen.' ),
	);
	$t = $T[ $li ];
	ob_start(); ?>
	<div class="vou-form">
	  <h3 style="margin:30px 0 12px;color:var(--gold)"><?php echo esc_html( $t[0] ); ?></h3>
	  <?php $vou_ff = garry_sez_ff_id( 'voucher' ); if ( $vou_ff ) : ?>
	  <div class="vou-ff"><?php echo do_shortcode( '[fluentform id=' . $vou_ff . ']' ); ?></div>
	  <?php else : ?>
	  <form id="vouForm" class="form-grid">
	    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'garry_sez_request' ) ); ?>">
	    <input type="text" name="web" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
	    <div><label for="vou-jmeno"><?php echo esc_html( $t[1] ); ?></label><input id="vou-jmeno" type="text" name="jmeno" required></div>
	    <div><label for="vou-prijmeni"><?php echo esc_html( $t[2] ); ?></label><input id="vou-prijmeni" type="text" name="prijmeni" required></div>
	    <div class="full"><label for="vou-druh"><?php echo esc_html( $t[3] ); ?></label><select id="vou-druh" name="druh">
	      <?php foreach ( garry_voucher_kinds( $li ) as $k ) printf( '<option>%s</option>', esc_html( $k ) ); ?>
	    </select></div>
	    <div class="full"><label for="vou-email"><?php echo esc_html( $t[4] ); ?></label><input id="vou-email" type="email" name="email" required></div>
	    <div class="full"><button type="submit" class="btn" id="vouBtn"><?php echo esc_html( $t[5] ); ?></button></div>
	    <div class="full"><div class="wb-ok" id="vouOk"><?php echo esc_html( $t[6] ); ?></div></div>
	  </form>
	  <?php endif; ?>
	</div>
	<script>
	(function(){
	  var f=document.getElementById('vouForm'); if(!f) return;
	  var ok=document.getElementById('vouOk'), btn=document.getElementById('vouBtn');
	  var msg={sending:<?php echo wp_json_encode( $t[7] ); ?>, fail:<?php echo wp_json_encode( $t[8] ); ?>, done:<?php echo wp_json_encode( $t[6] ); ?>, btn:btn.textContent, lang:<?php echo wp_json_encode( garry_sez_lang() ); ?>};
	  f.addEventListener('submit', function(e){
	    e.preventDefault(); e.stopImmediatePropagation();
	    if(!f.reportValidity()) return;
	    btn.disabled=true; btn.textContent=msg.sending;
	    var data=new FormData(f);
	    data.append('action','garry_voucher_request');
	    data.append('jazyk', msg.lang);
	    fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {method:'POST', body:data, credentials:'same-origin'})
	      .then(function(r){ return r.json(); })
	      .then(function(j){ btn.disabled=false; btn.textContent=msg.btn;
	        ok.textContent = j && j.success ? msg.done : (j && j.data && j.data.message ? j.data.message : msg.fail);
	        ok.classList.add('show'); if(j && j.success) f.reset(); })
	      .catch(function(){ btn.disabled=false; btn.textContent=msg.btn; ok.textContent=msg.fail; ok.classList.add('show'); });
	  }, true);
	})();
	</script>
	<?php return ob_get_clean();
}
add_action( 'init', function () { add_shortcode( 'grid_voucher_form', 'garry_voucher_form' ); }, 5 );

function garry_voucher_handle() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'garry_sez_request' ) ) {
		wp_send_json_error( array( 'message' => 'Neplatný požadavek — obnovte prosím stránku.' ), 400 );
	}
	if ( ! empty( $_POST['web'] ) ) wp_send_json_success(); // honeypot — tiše zahodit
	if ( garry_sez_is_rate_limited( 'voucher' ) ) {
		wp_send_json_error( array( 'message' => 'Příliš mnoho požadavků, zkuste to prosím později.', 'status' => 'throttled' ), 429 );
	}
	$verified = apply_filters( 'garry_sez_verify_request', true, wp_unslash( $_POST ) );
	if ( is_wp_error( $verified ) ) wp_send_json_error( array( 'message' => $verified->get_error_message() ), 400 );
	if ( ! $verified ) wp_send_json_error( array( 'message' => 'Ověření proti spamu se nepovedlo.' ), 400 );

	$z = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => 'Dárkový poukaz',
		'pokoj' => sanitize_text_field( wp_unslash( $_POST['druh'] ?? '' ) ),
		'jmeno' => trim( sanitize_text_field( wp_unslash( $_POST['jmeno'] ?? '' ) ) . ' ' . sanitize_text_field( wp_unslash( $_POST['prijmeni'] ?? '' ) ) ),
		'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'jazyk' => sanitize_key( $_POST['jazyk'] ?? '' ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( trim( $z['jmeno'] ) === '' || ! is_email( $z['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Vyplňte prosím jméno a platný e-mail.' ), 400 );
	}
	$to = garry_sez_get()['email']; if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	$body = "Nová objednávka dárkového poukazu z webu:\n\n"
		. 'Druh poukazu: ' . $z['pokoj'] . "\n" . 'Jméno: ' . $z['jmeno'] . "\n"
		. 'E-mail: ' . $z['email'] . "\n" . 'Jazyk webu: ' . strtoupper( $z['jazyk'] ) . "\n"
		. 'Čas: ' . $z['cas'] . "\n" . 'IP: ' . $z['ip'] . "\n";
	$mail_sent = wp_mail( $to, 'Objednávka dárkového poukazu — ' . $z['pokoj'], $body, array( 'Reply-To: ' . $z['jmeno'] . ' <' . $z['email'] . '>' ) );
	if ( ! $mail_sent ) $z['mail_failed'] = true;
	garry_sez_log_add( $z );
	/**
	 * Poptávka je bezpečně uložená v logu bez ohledu na výsledek e-mailu –
	 * hostovi proto potvrzujeme přijetí poptávky (accepted), ne odeslání
	 * e-mailu. Selhání se nezobrazuje jako úspěšné DORUČENÍ, jen jako
	 * úspěšné PŘIJETÍ (GRID-SUITE-03 §10) – správce uvidí mail_failed v logu.
	 */
	wp_send_json_success( array( 'status' => 'accepted', 'mail_sent' => (bool) $mail_sent ) );
}
add_action( 'wp_ajax_garry_voucher_request', 'garry_voucher_handle' );
add_action( 'wp_ajax_nopriv_garry_voucher_request', 'garry_voucher_handle' );

/* ------------------------------------------------------------------
 * Fluent Forms integrace — čekací list + dárkový poukaz
 * Mapa formulářů je v option grid_ff_forms (typ => jazyk => form_id).
 * - select „akce" se plní dynamicky z akcí pluginu (jen budoucí)
 * - odeslání se zapisuje do logu pluginu a posílá na e-mail z nastavení
 * ------------------------------------------------------------------ */
function garry_sez_ff_map() {
	return (array) get_option( 'grid_ff_forms', array() );
}
/* form_id => array( typ, jazyk ) | null */
function garry_sez_ff_lang_of( $form_id ) {
	foreach ( array( 'cekaci', 'voucher' ) as $typ ) {
		foreach ( (array) ( garry_sez_ff_map()[ $typ ] ?? array() ) as $lang => $id ) {
			if ( (int) $id === (int) $form_id ) return array( $typ, $lang );
		}
	}
	return null;
}
/* FF formulář pro aktuální jazyk (0 = FF není / není namapováno) */
function garry_sez_ff_id( $typ ) {
	if ( ! shortcode_exists( 'fluentform' ) ) return 0;
	$m = garry_sez_ff_map();
	return (int) ( $m[ $typ ][ garry_sez_lang() ] ?? ( $m[ $typ ]['cs'] ?? 0 ) );
}
/* dynamické možnosti selectu „akce" (jen budoucí akce, jazyk dle formuláře) */
add_filter( 'fluentform/rendering_field_data_select', function ( $data, $form ) {
	$hit = garry_sez_ff_lang_of( $form->id ?? 0 );
	if ( ! $hit || $hit[0] !== 'cekaci' ) return $data;
	if ( ( $data['attributes']['name'] ?? '' ) !== 'akce' ) return $data;
	$li = array( 'cs' => 0, 'en' => 1, 'de' => 2 )[ $hit[1] ] ?? 0;
	$today = current_time( 'Y-m-d' );
	$opts = array();
	foreach ( garry_sez_get()['events'] as $e ) {
		/* Nepublikovaná akce se nesmí nabízet ani tady — čekací list by přijímal
		   přihlášky na termín, který na webu ještě není. */
		if ( empty( $e['publikovano'] ) ) continue;
		if ( ( ( $e['do'] ?: $e['od'] ) ) < $today ) continue;
		$name = $e[ array( 'cz', 'en', 'de' )[ $li ] ] ?: $e['cz'];
		if ( $name === '' ) continue;
		$opts[] = array(
			'label'      => $name . ' · ' . garry_sez_fmt_range( $e['od'], $e['do'] ),
			'value'      => $name,
			'calc_value' => '',
		);
	}
	if ( $opts ) $data['settings']['advanced_options'] = $opts;
	return $data;
}, 10, 2 );
/* odeslání FF → log pluginu + e-mail z nastavení */
add_action( 'fluentform/submission_inserted', function ( $entry_id, $form_data, $form ) {
	$hit = garry_sez_ff_lang_of( $form->id ?? 0 );
	if ( ! $hit ) return;
	list( $typ, $lang ) = $hit;
	$z = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => $typ === 'voucher' ? 'Dárkový poukaz' : sanitize_text_field( $form_data['akce'] ?? '' ),
		'pokoj' => sanitize_text_field( $typ === 'voucher' ? ( $form_data['druh'] ?? '' ) : ( $form_data['pokoj'] ?? '' ) ),
		'jmeno' => trim( sanitize_text_field( $form_data['jmeno'] ?? '' ) . ' ' . sanitize_text_field( $form_data['prijmeni'] ?? '' ) ),
		'email' => sanitize_email( $form_data['email'] ?? '' ),
		'jazyk' => sanitize_key( $lang ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( $z['jmeno'] === '' || ! is_email( $z['email'] ) ) return;
	$to = garry_sez_get()['email']; if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	if ( $typ === 'voucher' ) {
		$subject = 'Objednávka dárkového poukazu — ' . $z['pokoj'];
		$body = "Nová objednávka dárkového poukazu z webu (Fluent Forms):\n\n"
			. 'Druh poukazu: ' . $z['pokoj'] . "\n" . 'Jméno: ' . $z['jmeno'] . "\n"
			. 'E-mail: ' . $z['email'] . "\n" . 'Jazyk webu: ' . strtoupper( $z['jazyk'] ) . "\n"
			. 'Čas: ' . $z['cas'] . "\n" . 'IP: ' . $z['ip'] . "\n";
	} else {
		$subject = 'Poptávka z čekacího listu — ' . $z['akce'];
		$body = "Nová poptávka z webu (sekce Sezóna & čekací list, Fluent Forms):\n\n"
			. 'Akce / termín: ' . $z['akce'] . "\n" . 'Typ pokoje:    ' . $z['pokoj'] . "\n"
			. 'Jméno:         ' . $z['jmeno'] . "\n" . 'E-mail:        ' . $z['email'] . "\n"
			. 'Jazyk webu:    ' . strtoupper( $z['jazyk'] ) . "\n" . 'Čas:           ' . $z['cas'] . "\n"
			. 'IP:            ' . $z['ip'] . "\n";
	}
	$mail_sent = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $z['jmeno'] . ' <' . $z['email'] . '>' ) );
	if ( ! $mail_sent ) $z['mail_failed'] = true;
	garry_sez_log_add( $z );
}, 10, 3 );

/* ---------- AJAX příjem poptávky (přihlášení i nepřihlášení) ---------- */
function garry_sez_handle_request() {
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'garry_sez_request' ) ) {
		wp_send_json_error( array( 'message' => 'Neplatný požadavek — obnovte prosím stránku.' ), 400 );
	}
	if ( ! empty( $_POST['web'] ) ) wp_send_json_success(); // honeypot — tiše zahodit
	if ( garry_sez_is_rate_limited( 'sez_request' ) ) {
		wp_send_json_error( array( 'message' => 'Příliš mnoho požadavků, zkuste to prosím později.', 'status' => 'throttled' ), 429 );
	}

	/* Integrace anti-spamu (Google reCAPTCHA plugin apod.):
	 * add_filter( 'garry_sez_verify_request', fn( $ok, $post ) => ..., 10, 2 ); */
	$verified = apply_filters( 'garry_sez_verify_request', true, wp_unslash( $_POST ) );
	if ( is_wp_error( $verified ) ) wp_send_json_error( array( 'message' => $verified->get_error_message() ), 400 );
	if ( ! $verified ) wp_send_json_error( array( 'message' => 'Ověření proti spamu se nepovedlo.' ), 400 );

	$zaznam = array(
		'cas'   => current_time( 'j. n. Y H:i:s' ),
		'akce'  => sanitize_text_field( wp_unslash( $_POST['akce'] ?? '' ) ),
		'pokoj' => sanitize_text_field( wp_unslash( $_POST['pokoj'] ?? '' ) ),
		'jmeno' => sanitize_text_field( wp_unslash( $_POST['jmeno'] ?? '' ) ),
		'email' => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		'jazyk' => sanitize_key( $_POST['jazyk'] ?? '' ),
		'ip'    => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
	);
	if ( $zaznam['jmeno'] === '' || ! is_email( $zaznam['email'] ) ) {
		wp_send_json_error( array( 'message' => 'Vyplňte prosím jméno a platný e-mail.' ), 400 );
	}

	/* e-mail */
	$to = garry_sez_get()['email'];
	if ( ! is_email( $to ) ) $to = get_option( 'admin_email' );
	$subject = 'Poptávka z čekacího listu — ' . $zaznam['akce'];
	$body = "Nová poptávka z webu (sekce Sezóna & čekací list):\n\n"
		. 'Akce / termín: ' . $zaznam['akce'] . "\n"
		. 'Typ pokoje:    ' . $zaznam['pokoj'] . "\n"
		. 'Jméno:         ' . $zaznam['jmeno'] . "\n"
		. 'E-mail:        ' . $zaznam['email'] . "\n"
		. 'Jazyk webu:    ' . strtoupper( $zaznam['jazyk'] ) . "\n"
		. 'Čas:           ' . $zaznam['cas'] . "\n"
		. 'IP:            ' . $zaznam['ip'] . "\n";
	$mail_sent = wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $zaznam['jmeno'] . ' <' . $zaznam['email'] . '>' ) );
	if ( ! $mail_sent ) $zaznam['mail_failed'] = true;
	garry_sez_log_add( $zaznam );

	wp_send_json_success( array( 'status' => 'accepted', 'mail_sent' => (bool) $mail_sent ) );
}
add_action( 'wp_ajax_garry_sez_request', 'garry_sez_handle_request' );
add_action( 'wp_ajax_nopriv_garry_sez_request', 'garry_sez_handle_request' );

/**
 * WordPress Privacy API — exporter/eraser (GRID-SUITE-03 §9, dosud chybělo
 * úplně). Log poptávek/poukazů obsahuje jméno/e-mail/IP; hledá se podle
 * normalizovaného e-mailu, stejně jako spec vyžaduje.
 */
add_filter( 'wp_privacy_personal_data_exporters', function ( $exporters ) {
	$exporters['garry-sezona-cekaci-list'] = array(
		'exporter_friendly_name' => 'GARRY – Sezóna & čekací list',
		'callback'               => 'garry_sez_privacy_exporter',
	);
	return $exporters;
} );
function garry_sez_privacy_exporter( $email_address, $page = 1 ) {
	$email = strtolower( trim( (string) $email_address ) );
	$log   = get_option( GARRY_SEZ_LOG, array() );
	if ( ! is_array( $log ) ) $log = array();

	$items = array();
	foreach ( $log as $i => $entry ) {
		if ( strtolower( trim( (string) ( $entry['email'] ?? '' ) ) ) !== $email ) continue;
		$items[] = array(
			'group_id'    => 'garry-sezona-cekaci-list',
			'group_label' => 'Poptávky — Sezóna a čekací list',
			'item_id'     => 'garry-sez-log-' . $i,
			'data'        => array(
				array( 'name' => 'Jméno', 'value' => $entry['jmeno'] ?? '' ),
				array( 'name' => 'E-mail', 'value' => $entry['email'] ?? '' ),
				array( 'name' => 'Akce/pokoj', 'value' => trim( ( $entry['akce'] ?? '' ) . ' ' . ( $entry['pokoj'] ?? '' ) ) ),
				array( 'name' => 'Čas', 'value' => $entry['cas'] ?? '' ),
			),
		);
	}
	return array( 'data' => $items, 'done' => true );
}

add_filter( 'wp_privacy_personal_data_erasers', function ( $erasers ) {
	$erasers['garry-sezona-cekaci-list'] = array(
		'eraser_friendly_name' => 'GARRY – Sezóna & čekací list',
		'callback'              => 'garry_sez_privacy_eraser',
	);
	return $erasers;
} );
function garry_sez_privacy_eraser( $email_address, $page = 1 ) {
	$email = strtolower( trim( (string) $email_address ) );
	$log   = get_option( GARRY_SEZ_LOG, array() );
	if ( ! is_array( $log ) ) $log = array();

	$removed = 0;
	$kept = array();
	foreach ( $log as $entry ) {
		if ( strtolower( trim( (string) ( $entry['email'] ?? '' ) ) ) === $email ) {
			$removed++;
			continue; // odstranit celý záznam (jméno/e-mail/IP) — nejde anonymizovat na užitečné agregáty, jde jen o pár poptávek, ne statistiku
		}
		$kept[] = $entry;
	}
	if ( $removed > 0 ) {
		update_option( GARRY_SEZ_LOG, $kept, false );
	}
	return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
}

/**
 * Registrace do GARRY – GRID Core modul registru (GRID-SUITE-00 „Integrační
 * API GRID") — BEZ 'render_callback': grid_season_events a grid_voucher_form
 * jsou DVĚ různé funkce (garry_sez_render/garry_voucher_form), stejný důvod
 * jako u Kategorie pokojů — ModuleRenderer spadne na krok 2 (shortcode_exists
 * + do_shortcode nad přesným tagem), který dispatchuje správně, protože oba
 * tagy jsou samostatně zaregistrované.
 */
add_action( 'gridhotel_register_modules', function () {
	if ( ! function_exists( 'gridhotel_register_module' ) ) {
		return;
	}
	gridhotel_register_module( array(
		'id'         => 'garry-sezona-cekaci-list',
		'name'       => 'Sezóna & čekací list',
		'version'    => defined( 'GARRY_SEZ_VER' ) ? GARRY_SEZ_VER : '2.6.0',
		'admin_slug' => 'garry-sezona-grid',
		'capability' => defined( 'GARRY_SEZ_STAFF_CAP' ) ? GARRY_SEZ_STAFF_CAP : 'manage_options',
		'shortcodes' => array( 'grid_season_events', 'grid_voucher_form' ),
		'features'   => array( 'season_events' ),
	) );
} );
