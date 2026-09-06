<?php
/**
 * Vzorová data jídelníčku pro Paddock Restaurant a nápojového lístku pro GRID CLUB.
 *
 * Slouží k tomu, aby stránka Gastronomie nezůstala prázdná, než dodá provoz
 * skutečnou nabídku. Položky jsou realistické, ale smyšlené — ceny i složení
 * je potřeba nahradit před spuštěním webu.
 */
$o = get_option( GARRY_MENU_OPT, array() );
if ( ! is_array( $o ) ) $o = array();
if ( empty( $o['venues'] ) || ! is_array( $o['venues'] ) ) $o['venues'] = array();

function vz( $typ, $cz, $en, $de, $cena ) {
	return array( 'typ' => $typ, 'cz' => $cz, 'en' => $en, 'de' => $de, 'cena' => $cena );
}
function vzn( $tag, $cz, $en, $de, $cena ) {
	return array( 'tag' => $tag, 'cz' => $cz, 'en' => $en, 'de' => $de, 'cena' => $cena );
}

/* ---------------- PADDOCK RESTAURANT: rychlejší kuchyně u paddocku ---------------- */
$paddock_stala = array(
	vz( 'predkrm', 'Nakládaný hermelín s pečivem', 'Marinated camembert with bread', 'Eingelegter Camembert mit Brot', '145 Kč' ),
	vz( 'predkrm', 'Bramborové placky s česnekem a slaninou', 'Potato pancakes with garlic and bacon', 'Kartoffelpuffer mit Knoblauch und Speck', '135 Kč' ),
	vz( 'polevka', 'Hovězí vývar s masem a nudlemi', 'Beef broth with meat and noodles', 'Rinderbrühe mit Fleisch und Nudeln', '95 Kč' ),
	vz( 'polevka', 'Česnečka se sýrem a krutony', 'Garlic soup with cheese and croutons', 'Knoblauchsuppe mit Käse und Croûtons', '95 Kč' ),
	vz( 'hlavni', 'Paddock burger — hovězí 200 g, čedar, slanina, hranolky', 'Paddock burger — 200 g beef, cheddar, bacon, fries', 'Paddock-Burger — 200 g Rind, Cheddar, Speck, Pommes', '295 Kč' ),
	vz( 'hlavni', 'Smažený řízek z vepřové kotlety, bramborový salát', 'Breaded pork schnitzel with potato salad', 'Paniertes Schweineschnitzel mit Kartoffelsalat', '265 Kč' ),
	vz( 'hlavni', 'Kuřecí steak s grilovanou zeleninou a bylinkovým máslem', 'Chicken steak with grilled vegetables and herb butter', 'Hähnchensteak mit Grillgemüse und Kräuterbutter', '275 Kč' ),
	vz( 'hlavni', 'Trhané vepřové v bulce, coleslaw, batátové hranolky', 'Pulled pork bun, coleslaw, sweet potato fries', 'Pulled-Pork-Brötchen, Coleslaw, Süßkartoffel-Pommes', '285 Kč' ),
	vz( 'hlavni', 'Zeleninové kari s jasmínovou rýží', 'Vegetable curry with jasmine rice', 'Gemüsecurry mit Jasminreis', '245 Kč' ),
	vz( 'priloha', 'Hranolky / americké brambory', 'Fries / potato wedges', 'Pommes / Kartoffelspalten', '60 Kč' ),
	vz( 'priloha', 'Grilovaná zelenina', 'Grilled vegetables', 'Grillgemüse', '75 Kč' ),
	vz( 'dezert', 'Domácí jablečný závin se šlehačkou', 'Homemade apple strudel with whipped cream', 'Hausgemachter Apfelstrudel mit Sahne', '110 Kč' ),
);
$paddock_vecerni = array(
	vz( 'predkrm', 'Tataráček z lososa s avokádem a limetkou', 'Salmon tartare with avocado and lime', 'Lachstatar mit Avocado und Limette', '265 Kč' ),
	vz( 'polevka', 'Dýňový krém s praženými semínky', 'Pumpkin cream soup with roasted seeds', 'Kürbiscremesuppe mit gerösteten Kernen', '115 Kč' ),
	vz( 'hlavni', 'Rib eye steak 250 g, pepřová omáčka, grilované brambory', 'Rib eye steak 250 g, pepper sauce, grilled potatoes', 'Rib-Eye-Steak 250 g, Pfeffersauce, Grillkartoffeln', '590 Kč' ),
	vz( 'hlavni', 'Konfitovaná kachní stehna, červené zelí, bramborový knedlík', 'Confit duck legs, red cabbage, potato dumplings', 'Confierte Entenkeulen, Rotkohl, Kartoffelknödel', '395 Kč' ),
	vz( 'dezert', 'Čokoládový fondant s malinovou omáčkou', 'Chocolate fondant with raspberry sauce', 'Schokoladenfondant mit Himbeersauce', '145 Kč' ),
);
$paddock_napoje = array(
	vzn( 'piva', 'Pilsner Urquell 12° (0,5 l)', 'Pilsner Urquell 12° (0.5 l)', 'Pilsner Urquell 12° (0,5 l)', '65 Kč' ),
	vzn( 'piva', 'Birell nealkoholický (0,5 l)', 'Birell non-alcoholic (0.5 l)', 'Birell alkoholfrei (0,5 l)', '55 Kč' ),
	vzn( 'vina-bila', 'Veltlínské zelené, Morava (0,15 l)', 'Grüner Veltliner, Moravia (0.15 l)', 'Grüner Veltliner, Mähren (0,15 l)', '75 Kč' ),
	vzn( 'vina-cervena', 'Frankovka, Morava (0,15 l)', 'Blaufränkisch, Moravia (0.15 l)', 'Blaufränkisch, Mähren (0,15 l)', '75 Kč' ),
	vzn( 'nealko', 'Domácí limonáda (0,4 l)', 'Homemade lemonade (0.4 l)', 'Hausgemachte Limonade (0,4 l)', '75 Kč' ),
	vzn( 'nealko', 'Minerální voda (0,33 l)', 'Mineral water (0.33 l)', 'Mineralwasser (0,33 l)', '45 Kč' ),
	vzn( 'horke-napoje', 'Espresso / Cappuccino', 'Espresso / Cappuccino', 'Espresso / Cappuccino', '60 / 75 Kč' ),
);

/* ---------------- GRID CLUB: barová nabídka ---------------- */
$club_napoje = array(
	vzn( 'koktejly', 'Pole Position — gin, bezinkový sirup, limetka, tonic', 'Pole Position — gin, elderflower, lime, tonic', 'Pole Position — Gin, Holunder, Limette, Tonic', '215 Kč' ),
	vzn( 'koktejly', 'Chicane — rum, ananas, limetka, angostura', 'Chicane — rum, pineapple, lime, angostura', 'Chicane — Rum, Ananas, Limette, Angostura', '215 Kč' ),
	vzn( 'koktejly', 'Apex Negroni — gin, vermut, campari', 'Apex Negroni — gin, vermouth, Campari', 'Apex Negroni — Gin, Wermut, Campari', '225 Kč' ),
	vzn( 'koktejly', 'Box Box (nealko) — grep, rozmarýn, tonic', 'Box Box (alcohol-free) — grapefruit, rosemary, tonic', 'Box Box (alkoholfrei) — Grapefruit, Rosmarin, Tonic', '155 Kč' ),
	vzn( 'whisky', 'Jameson (0,04 l)', 'Jameson (0.04 l)', 'Jameson (0,04 l)', '95 Kč' ),
	vzn( 'whisky', 'Glenfiddich 12 (0,04 l)', 'Glenfiddich 12 (0.04 l)', 'Glenfiddich 12 (0,04 l)', '165 Kč' ),
	vzn( 'gin', 'Hendrick’s (0,04 l)', 'Hendrick’s (0.04 l)', 'Hendrick’s (0,04 l)', '145 Kč' ),
	vzn( 'rum', 'Diplomático Reserva (0,04 l)', 'Diplomático Reserva (0.04 l)', 'Diplomático Reserva (0,04 l)', '155 Kč' ),
	vzn( 'sekty', 'Prosecco (0,1 l)', 'Prosecco (0.1 l)', 'Prosecco (0,1 l)', '95 Kč' ),
	vzn( 'piva', 'Pilsner Urquell 12° (0,5 l)', 'Pilsner Urquell 12° (0.5 l)', 'Pilsner Urquell 12° (0,5 l)', '65 Kč' ),
	vzn( 'nealko', 'Domácí limonáda (0,4 l)', 'Homemade lemonade (0.4 l)', 'Hausgemachte Limonade (0,4 l)', '75 Kč' ),
	vzn( 'horke-napoje', 'Espresso / Cappuccino', 'Espresso / Cappuccino', 'Espresso / Cappuccino', '60 / 75 Kč' ),
);

$zmeny = array(
	'paddock-restaurant' => array( 'stala' => $paddock_stala, 'vecerni' => $paddock_vecerni, 'napoje' => $paddock_napoje ),
	'grid-club'          => array( 'napoje' => $club_napoje ),
);

foreach ( $zmeny as $slug => $data ) {
	$v = garry_menu_get_venue( $slug );
	if ( ! $v ) { WP_CLI::warning( "provoz $slug neexistuje" ); continue; }
	foreach ( $data as $klic => $polozky ) {
		if ( $klic === 'napoje' ) {
			/* Skupiny (tags) uz provoz ma — doplnujeme jen polozky. */
			if ( ! empty( $v['napoje']['items'] ) ) { WP_CLI::log( "  $slug/napoje uz ma data, preskoceno" ); continue; }
			$v['napoje']['items'] = $polozky;
		} else {
			if ( ! empty( $v[ $klic ] ) ) { WP_CLI::log( "  $slug/$klic uz ma data, preskoceno" ); continue; }
			$v[ $klic ] = $polozky;
		}
		WP_CLI::log( sprintf( '  %-20s %-8s doplneno %d polozek', $slug, $klic, count( $polozky ) ) );
	}
	/* GRID CLUB nabízí jen nápoje — jídelní části necháváme vypnuté. */
	$o['venues'][ $slug ] = $v;
}
update_option( GARRY_MENU_OPT, $o, false );
WP_CLI::success( 'vzorova data ulozena' );
