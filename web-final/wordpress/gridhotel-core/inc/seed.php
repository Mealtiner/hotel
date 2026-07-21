<?php
/**
 * GRID Hotel Core — jednoklikové naplnění obsahem.
 * Nástroje → „GRID: Naplnit obsahem".
 *  - Vytvoří 4 KATEGORIE pokojů (typy) s popisy, krátkými popisy a štítky.
 *  - Vytvoří výchozí zážitky, akce sezóny, gastro a reference.
 *  - Jednotlivé pokoje NEvytváří — ty přidáváš ručně a přiřadíš ke kategorii.
 * Naplní jen typy/položky, které jsou zatím prázdné (neduplikuje).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---- Kategorie pokojů (taxonomie grid_room_cat) ---- */
function gridcore_seed_terms() {
	return array(
		array(
			'name' => 'Standard', 'kod' => '3.1 / STANDARD', 'poradi' => 10, 'pocet' => 30, 'kapacita' => '1–2',
			'kratky_popis' => 'Komfortní pokoje evropského standardu **** s postelí TWIN/DOUBLE, klimatizací, TV 40" HDMI, Wi-Fi a pracovním stolem.',
			'velikost' => '24', 'postel' => 'Manželská nebo oddělené postele (TWIN/DOUBLE)',
			'koupelna' => 'Sprcha|Fén|Župan|Pantofle|Ručníky|Toaletní potřeby zdarma|Toaleta|Toaletní papír',
			'zarizeni' => 'Klimatizace|Trezor|Minibar|TV s plochou obrazovkou (satelit)|Telefon|Psací stůl|Posezení|Povlečení|Koberec|Skříň / šatna|Služba buzení|Zásuvka u postele|Věšák na oblečení|Výtah do vyšších pater',
			'stitky' => '24 m²|TWIN / DOUBLE|klimatizace|TV 40" HDMI|Wi-Fi',
			'popis' =>
				'<p><strong>Všechny pokoje splňují veškeré parametry evropského standardu ****.</strong></p>' .
				'<p>Vedle komfortního vybavení, postele TWIN nebo DOUBLE, je k dispozici individuálně nastavitelná klimatizace, TV 40" s HDMI vstupem, Wi-Fi připojení a pracovní stůl.</p>' .
				'<p>Pokoj má bezpečnostní kartový zámkový systém, pokojový trezor, možnost plného zatemnění, telefon s přímou předvolbou, minibar, sprchový kout, kosmetické zrcátko, vysoušeč vlasů. K dispozici je hostům kvalitní hotelová kosmetika, pomůcky na čištění obuvi a šití.</p>' .
				'<p>Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>' .
				'<p>Za poplatek je hostům k dispozici čajový a kávový set, hotelový župan a pantofle, hygienické potřeby.</p>',
		),
		array(
			'name' => 'Superior', 'kod' => '3.2 / SUPERIOR', 'poradi' => 20, 'pocet' => 20, 'kapacita' => '2',
			'kratky_popis' => 'Orientované výhledem na centrum dění brněnského okruhu i okolní lesy. Denně kávový/čajový set, župan, pantofle a minerální voda.',
			'velikost' => '24', 'postel' => 'Manželská nebo oddělené postele (TWIN/DOUBLE)',
			'koupelna' => 'Sprcha|Fén|Župan|Pantofle|Ručníky|Toaletní potřeby zdarma|Toaleta|Toaletní papír',
			'zarizeni' => 'Výhled na okruh|Klimatizace|Rychlovarná konvice|Trezor|Minibar|TV s plochou obrazovkou (satelit)|Telefon|Psací stůl|Posezení|Povlečení|Koberec|Skříň / šatna|Služba buzení|Věšák na oblečení',
			'stitky' => '24 m²|track view|rychlovarná konvice|župan & pantofle|minerální voda',
			'popis' =>
				'<p>Pokoje Superior jsou orientovány výhledem na centrum dění brněnského okruhu i okolní lesy.</p>' .
				'<p>Hostům je zdarma k dispozici denně doplňovaný kávový a čajový set, hotelový župan, pantofle a minerální voda na pokoji.</p>' .
				'<p>Vedle komfortního vybavení, postele TWIN nebo DOUBLE, je k dispozici individuálně nastavitelná klimatizace, TV 40" s HDMI vstupem, Wi-Fi připojení a pracovní stůl.</p>' .
				'<p>Pokoj má bezpečnostní kartový zámkový systém, pokojový trezor, možnost plného zatemnění, telefon s přímou předvolbou, minibar, sprchový kout, kosmetické zrcátko, vysoušeč vlasů. K dispozici je hostům kvalitní hotelová kosmetika, pomůcky na čištění obuvi a šití.</p>' .
				'<p>Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>',
		),
		array(
			'name' => 'Superior Plus', 'kod' => '3.3 / SUPERIOR PLUS', 'poradi' => 30, 'pocet' => 10, 'kapacita' => '2–3',
			'kratky_popis' => 'Vše ze Superior — navíc terasa s posezením a výhledem na centrum okruhu i okolí.',
			'velikost' => '24', 'postel' => 'Manželská nebo oddělené postele (TWIN/DOUBLE)',
			'koupelna' => 'Sprcha|Fén|Župan|Pantofle|Ručníky|Toaletní potřeby zdarma|Toaleta|Toaletní papír',
			'zarizeni' => 'Terasa|Výhled na okruh|Klimatizace|Rychlovarná konvice|Trezor|Minibar|TV s plochou obrazovkou (satelit)|Telefon|Psací stůl|Posezení|Povlečení|Koberec|Skříň / šatna|Věšák na oblečení',
			'stitky' => '24 m²|terasa|track view|rychlovarná konvice|minerální voda',
			'popis' =>
				'<p>Pokoje disponující i terasou s posezením a výhledem na centrum okruhu i okolí.</p>' .
				'<p>Hostům je zdarma k dispozici denně doplňovaný kávový a čajový set, hotelový župan, pantofle a minerální voda na pokoji.</p>' .
				'<p>Vedle komfortního vybavení, postele TWIN nebo DOUBLE, je k dispozici individuálně nastavitelná klimatizace, TV 40" s HDMI vstupem, Wi-Fi připojení a pracovní stůl.</p>' .
				'<p>Pokoj má bezpečnostní kartový zámkový systém, pokojový trezor, možnost plného zatemnění, telefon s přímou předvolbou, minibar, sprchový kout, kosmetické zrcátko, vysoušeč vlasů. K dispozici je hostům kvalitní hotelová kosmetika, pomůcky na čištění obuvi a šití.</p>' .
				'<p>Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>',
		),
		array(
			'name' => 'Apartmá a Apartmá Plus', 'kod' => '3.4 / APARTMÁ', 'poradi' => 40, 'pocet' => 4, 'kapacita' => '2–4',
			'kratky_popis' => 'Nadstandardní apartmá 47–59 m² s King Size ložnicí, obývacím pokojem a terasou až 47 m². Nejlepší výhled na okruh a paddock.',
			'velikost' => '47–59', 'postel' => 'Extra velká manželská postel (King Size)',
			'koupelna' => 'Vana|Sprcha|Fén|Župan|Pantofle|Ručníky|Toaletní potřeby zdarma|Toaleta|Toaletní papír',
			'zarizeni' => 'Obývací pokoj|Terasa (až 47 m²)|Výhled na okruh / paddock|Klimatizace|Kávovar|Trezor|Minibar|2× TV 43" s plochou obrazovkou|2× telefon|Psací stůl|Pohovka|Povlečení|Koberec|Skříň / šatna|Služba buzení|Věšák na oblečení',
			'stitky' => '47–59 m²|terasa až 47 m²|King Size|vana|kávovar|obývací pokoj',
			'popis' =>
				'<p>Nadstandardní ubytování s nejlepším výhledem na město, okruh či paddock (parkoviště závodních strojů).</p>' .
				'<p>Hotelová apartmá mají ložnici s dvoulůžkem King Size, obývací pokoj a celkovou velikost od 47 do 59 m². Jejich součástí jsou i nadstandardně řešené terasy o velikosti až 47 m².</p>' .
				'<p>Každé je vybaveno dvěma TV 43" s HDMI vstupem a dvěma telefony s přímou předvolbou.</p>' .
				'<p>Hostům je samozřejmě zdarma k dispozici denně doplňovaný kávový a čajový set, hotelový župan, pantofle a minerální voda na pokoji. Vedle komfortního vybavení je k dispozici individuálně nastavitelná klimatizace, Wi-Fi připojení a pracovní stůl.</p>' .
				'<p>Apartmán má bezpečnostní kartový zámkový systém, pokojový trezor, možnost plného zatemnění, minibar, sprchový kout, kosmetické zrcátko, vysoušeč vlasů. K dispozici je hostům kvalitní hotelová kosmetika, pomůcky na čištění obuvi a šití.</p>' .
				'<p>Na vyžádání zdarma polštář navíc (nebo zdravotní polštář), žehlicí prkno s žehličkou.</p>',
		),
	);
}

/* ---- Ostatní typy obsahu (CPT příspěvky) ---- */
function gridcore_seed_data() {
	return array(
		'grid_experience' => array(
			array( 'Simulátor okruhu', array( 'num'=>'4.1', 'text'=>'Profesionální dynamický simulátor s reálnou geometrií Masarykova okruhu. Ideální rozjížďka před ostrým výjezdem na trať — pro začátečníky i závodníky.', 'cta'=>'Vyzkoušet →' ) ),
			array( 'Motokáry & pitbike', array( 'num'=>'4.2', 'text'=>'Usedněte do silné motokáry nebo na obratnou pitbike a zajezděte si pár metrů od velkého okruhu na speciální motokárové dráze. Měření časů a souboj o nejlepší kolo.', 'cta'=>'Rezervovat →' ) ),
			array( 'Škola smyku — Polygon Brno', array( 'num'=>'4.3', 'text'=>'Moderní tréninkové centrum bezpečné jízdy. Úrovně Compact, Intensiv, Intensiv+, Advanced a Dynamic — od základů po pokročilou techniku ovládání vozu.', 'cta'=>'Vybrat úroveň →' ) ),
			array( 'Drift & Gangster kurz', array( 'num'=>'4.4', 'text'=>'Zážitkové kurzy Polygonu Brno pro ty, kdo chtějí víc adrenalinu — řízený drift a speciální program za volantem.', 'cta'=>'Termíny →' ) ),
			array( 'Odpočet trestných bodů', array( 'num'=>'4.5', 'text'=>'Akreditovaný kurz bezpečné jízdy pro odečet trestných bodů. Vhodné i jako firemní školení řidičů na míru.', 'cta'=>'Více →' ) ),
			array( 'Dárkové poukazy', array( 'num'=>'4.6', 'text'=>'Zážitek u okruhu jako dárek — pobyt, simulátor, motokáry nebo kurz Polygonu v libovolné hodnotě. Pošleme i elektronicky.', 'cta'=>'Koupit poukaz →' ) ),
		),
		'grid_event' => array(
			array( 'Track Day Open', array( 'date'=>'17.–19. 4. 2026', 'desc'=>'Volné jízdy pro veřejnost na okruhu', 'status'=>'free' ) ),
			array( 'Endurance 8h Brno', array( 'date'=>'22.–24. 5. 2026', 'desc'=>'Vytrvalostní závod — den i noc na trati', 'status'=>'few' ) ),
			array( 'MotoGP víkend', array( 'date'=>'7.–9. 8. 2026', 'desc'=>'Hlavní událost sezóny — vrchol roku', 'status'=>'full' ) ),
			array( 'FIA WTCR', array( 'date'=>'11.–13. 9. 2026', 'desc'=>'Cestovní vozy na Masarykově okruhu', 'status'=>'few' ) ),
			array( 'Classic & Historic', array( 'date'=>'2.–4. 10. 2026', 'desc'=>'Přehlídka historických závodních strojů', 'status'=>'free' ) ),
		),
		'grid_gastro' => array(
			array( 'Hotelová restaurace', array( 'hours'=>'Snídaně · Oběd · Večeře', 'text'=>'Začněte den bohatou snídaní formou studeného i teplého bufetu v moderně zařízené hotelové restauraci. Přes den denní menu, večer à la carte s výhledem na trať.', 'list'=>"Snídaně=7:00–10:00|Obědy (denní menu)=12:00–15:00|Večeře (à la carte)=18:00–21:30" ) ),
			array( 'GRID Club', array( 'hours'=>'Otevřeno 12:00–24:00', 'text'=>'Stylové prostory nedaleko recepce — ideální na pracovní i obchodní schůzky i k relaxaci. Široká nabídka nápojů a lehkého občerstvení a pohodlné posezení na letní terase s výhledem do centra okruhu.', 'list'=>"Koktejlový bar=|Terasa s výhledem=|Afterparty & race víkendy=" ) ),
			array( 'PADDOCK Restaurant', array( 'hours'=>'Přímo v areálu okruhu', 'text'=>'Přímo v areálu Masarykova okruhu, komfortní posezení až pro 80 hostů. Samoobslužný bufet z národní i mezinárodní kuchyně, salátový bar a dezerty. Venkovní terasa s částečným výhledem do paddocku — ideální i pro společenské události.', 'list'=>"Kapacita=až 80 hostů|Bufet & salátový bar=|Celodenní stravování=" ) ),
		),
		'grid_testimonial' => array(
			array( 'Petr H.', array( 'text'=>'Probudit se a vidět z okna cílovou rovinku Masarykova okruhu je nepopsatelné. Servis i snídaně na úrovni.', 'who'=>'Petr H. · MotoGP víkend' ) ),
			array( 'Lucie K.', array( 'text'=>'Uspořádali jsme tu firemní akci pro 120 lidí. Catering, bar i motokáry — vše na jednom místě bez transferů.', 'who'=>'Lucie K. · HR manažerka' ) ),
			array( 'Tomáš & Eva R.', array( 'text'=>'Apartmá s terasou a výhledem na trať. Klid, prémiová úroveň a pár minut do Brna. Vrátíme se.', 'who'=>'Tomáš & Eva R. · Víkendový pobyt' ) ),
		),
	);
}

/* Nástroje → GRID: Naplnit obsahem */
add_action( 'admin_menu', function () {
	add_management_page( 'GRID: Naplnit obsahem', 'GRID: Naplnit obsahem', 'manage_options', 'gridcore-seed', 'gridcore_seed_page' );
} );

function gridcore_seed_page() {
	echo '<div class="wrap"><h1>GRID Hotel — naplnění obsahem</h1>';
	echo '<p>Vytvoří kategorie pokojů (typy) a výchozí zážitky, akce, gastro a reference — jen tam, kde ještě nic není. Jednotlivé pokoje přidáš ručně v <strong>GRID: Pokoje</strong> a přiřadíš ke kategorii. Fotky doplníš jako galerii.</p>';

	echo '<table class="widefat" style="max-width:560px;margin:16px 0"><thead><tr><th>Typ obsahu</th><th>Aktuálně</th><th>K vytvoření</th></tr></thead><tbody>';
	$catCount = (int) wp_count_terms( array( 'taxonomy' => 'grid_room_cat', 'hide_empty' => false ) );
	echo '<tr><td>Kategorie pokojů</td><td>' . $catCount . '</td><td>' . ( $catCount > 0 ? '— (přeskočí se)' : count( gridcore_seed_terms() ) ) . '</td></tr>';
	foreach ( gridcore_seed_data() as $pt => $items ) {
		$count = (int) wp_count_posts( $pt )->publish + (int) wp_count_posts( $pt )->draft;
		$obj = get_post_type_object( $pt );
		echo '<tr><td>' . esc_html( $obj->labels->name ) . '</td><td>' . $count . '</td><td>' . ( $count > 0 ? '— (přeskočí se)' : count( $items ) ) . '</td></tr>';
	}
	echo '</tbody></table>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="gridcore_seed">';
	wp_nonce_field( 'gridcore_seed' );
	submit_button( 'Naplnit obsahem' );
	echo '</form></div>';
}

add_action( 'admin_post_gridcore_seed', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'gridcore_seed' ) ) {
		wp_die( 'Nedostatečná oprávnění.' );
	}
	$created = 0;

	// 1) Kategorie pokojů
	$catCount = (int) wp_count_terms( array( 'taxonomy' => 'grid_room_cat', 'hide_empty' => false ) );
	if ( taxonomy_exists( 'grid_room_cat' ) && $catCount === 0 ) {
		foreach ( gridcore_seed_terms() as $t ) {
			$res = wp_insert_term( $t['name'], 'grid_room_cat' );
			if ( ! is_wp_error( $res ) && isset( $res['term_id'] ) ) {
				$ref = 'term_' . $res['term_id'];
				foreach ( array( 'kod', 'poradi', 'kratky_popis', 'popis', 'stitky', 'pocet', 'kapacita', 'velikost', 'postel', 'koupelna', 'zarizeni' ) as $f ) {
					if ( ! isset( $t[ $f ] ) ) continue;
					if ( function_exists( 'update_field' ) ) update_field( $f, $t[ $f ], $ref );
					else update_term_meta( $res['term_id'], $f, $t[ $f ] );
				}
				$created++;
			}
		}
	}

	// 2) Ostatní CPT
	foreach ( gridcore_seed_data() as $pt => $items ) {
		$existing = (int) wp_count_posts( $pt )->publish + (int) wp_count_posts( $pt )->draft;
		if ( $existing > 0 ) continue;
		$order = 1;
		foreach ( $items as $row ) {
			list( $title, $fields ) = $row;
			$id = wp_insert_post( array( 'post_type' => $pt, 'post_title' => $title, 'post_status' => 'publish', 'menu_order' => $order ) );
			if ( $id && ! is_wp_error( $id ) ) {
				foreach ( $fields as $name => $val ) {
					if ( function_exists( 'update_field' ) ) update_field( $name, $val, $id );
					else update_post_meta( $id, $name, $val );
				}
				$created++;
			}
			$order++;
		}
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'gridcore-seed', 'seeded' => $created ), admin_url( 'tools.php' ) ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( isset( $_GET['seeded'] ) && isset( $_GET['page'] ) && $_GET['page'] === 'gridcore-seed' ) {
		echo '<div class="notice notice-success is-dismissible"><p>Vytvořeno položek: <strong>' . (int) $_GET['seeded'] . '</strong>.</p></div>';
	}
} );
