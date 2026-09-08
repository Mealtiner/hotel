<?php
/**
 * Napojení formulářů Fluent Forms na data webu.
 *
 * Formuláře jsou součástí sekcí, které vykresluje tenhle plugin (rezervační
 * lišta, čekací list, poukazy, poptávka firemních akcí), takže jejich chování
 * patří sem, ne do motivu. Motiv drží jen vzhled.
 *
 * Do 1.13.0 tohle bydlelo v child motivu a logika výběru typů pokojů byla
 * zdvojená — jednou pro shortcode [grid_vyber_pokoje], podruhé pro filtr
 * Fluent Forms. Obě cesty teď čtou stejnou funkci.
 *
 * @package GRID Hotel Components
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kategorie pokojů v jazyce aktuální stránky.
 *
 * Polylang filtruje get_terms() sám, ale jen když je aktivní a taxonomie je
 * přeložená; pojistka je pro případ, že by prošly všechny jazyky najednou.
 *
 * @return array Seznam kategorií tak, jak je vrací gridhotel_get_room_categories().
 */
function gridc_typy_pokoju() {
	if ( ! function_exists( 'gridhotel_get_room_categories' ) ) {
		return array();
	}

	$kategorie = gridhotel_get_room_categories( array( 'hide_empty' => false ) );
	if ( ! $kategorie ) {
		return array();
	}

	if ( function_exists( 'pll_get_term_language' ) && function_exists( 'pll_current_language' ) ) {
		$jazyk = pll_current_language( 'slug' );
		if ( $jazyk ) {
			$filtr = array();
			foreach ( $kategorie as $k ) {
				if ( pll_get_term_language( (int) $k['id'], 'slug' ) === $jazyk ) {
					$filtr[] = $k;
				}
			}
			if ( $filtr ) {
				$kategorie = $filtr;
			}
		}
	}

	return $kategorie;
}

/**
 * Pole „Typ pokoje" v čekacím listu se plní z kategorií pokojů.
 *
 * Volby byly ve formuláři napsané natvrdo a nesouhlasily se skutečnými typy.
 * Bereme je proto ze stejného zdroje jako karty pokojů i rezervační lišta.
 */
add_filter( 'fluentform/rendering_field_data_select', function ( $data, $form ) {
	$nazev = $data['attributes']['name'] ?? '';
	if ( 'pokoj' !== $nazev ) {
		return $data;
	}

	$kategorie = gridc_typy_pokoju();
	if ( ! $kategorie ) {
		return $data;
	}

	$volby = array();
	foreach ( $kategorie as $k ) {
		$volby[] = array( 'label' => $k['name'], 'value' => $k['name'], 'calc_value' => '' );
	}
	$data['settings']['advanced_options'] = $volby;

	return $data;
}, 10, 2 );

/**
 * Názvy měsíců a dnů v kalendáři formulářů podle jazyka stránky.
 *
 * Fluent Forms skládá řetězce pro flatpickr ze svojí vlastní sady překladů,
 * která je česky jen částečně. Bereme je proto z $wp_locale, který podle
 * jazyka stránky přepíná Polylang, takže se picker přizpůsobí sám.
 *
 * Používá to i rezervační lišta na titulní straně (motiv si tuhle funkci volá
 * při zařazení flatpickru), aby byl kalendář všude stejný.
 *
 * @param array $i18n Výchozí sada řetězců.
 * @return array
 */
function gridc_kalendar_i18n( $i18n = array() ) {
	global $wp_locale;
	if ( ! $wp_locale instanceof WP_Locale ) {
		return $i18n;
	}

	$mesice_dlouhe = array();
	$mesice_kratke = array();
	for ( $m = 1; $m <= 12; $m++ ) {
		$nazev           = $wp_locale->get_month( zeroise( $m, 2 ) );
		$mesice_dlouhe[] = $nazev;
		$mesice_kratke[] = $wp_locale->get_month_abbrev( $nazev );
	}

	/* flatpickr očekává týden začínající nedělí; první zobrazený den řídí
	   zvlášť firstDayOfWeek, který se bere z nastavení WordPressu. */
	$dny_dlouhe = array();
	$dny_kratke = array();
	for ( $d = 0; $d <= 6; $d++ ) {
		$nazev        = $wp_locale->get_weekday( $d );
		$dny_dlouhe[] = $nazev;
		$dny_kratke[] = $wp_locale->get_weekday_abbrev( $nazev );
	}

	$i18n['months']['longhand']    = $mesice_dlouhe;
	$i18n['months']['shorthand']   = $mesice_kratke;
	$i18n['weekdays']['longhand']  = $dny_dlouhe;
	$i18n['weekdays']['shorthand'] = $dny_kratke;

	/* Ovládací popisky kalendáře plugin nepřekládá vůbec a WordPress je nemá —
	   drží se tu proto přímo, ve stejné trojici jazyků jako zbytek webu. */
	$jazyk  = substr( (string) determine_locale(), 0, 2 );
	$popisy = array(
		'cs' => array( 'Předchozí měsíc', 'Následující měsíc', ' až ', 'Týd.', 'Rok' ),
		'en' => array( 'Previous month', 'Next month', ' to ', 'Wk', 'Year' ),
		'de' => array( 'Vorheriger Monat', 'Nächster Monat', ' bis ', 'KW', 'Jahr' ),
	);
	$p = $popisy[ $jazyk ] ?? $popisy['cs'];

	$i18n['previousMonth']    = $p[0];
	$i18n['nextMonth']        = $p[1];
	$i18n['rangeSeparator']   = $p[2];
	$i18n['weekAbbreviation'] = $p[3];
	$i18n['yearAriaLabel']    = $p[4];
	$i18n['firstDayOfWeek']   = (int) get_option( 'start_of_week' );

	return $i18n;
}
add_filter( 'fluentform/date_i18n', 'gridc_kalendar_i18n' );

/**
 * Poptávka firemních akcí dostane třídu pro vlastní rozbalovací seznam.
 *
 * Pole „Typ akce" zůstávalo jako jediné nativní — rozbalený seznam kreslí
 * operační systém a s ostatními formuláři webu se rozcházel. Značíme přes
 * oficiální filtr, ne zásahem do definice formuláře, a podle názvu: poptávka
 * má tři jazykové mutace s různými ID.
 */
add_filter( 'fluentform/form_class', function ( $tridy, $form ) {
	$nazev = isset( $form->title ) ? $form->title : '';
	if ( false !== strpos( $nazev, 'Firemní poptávka' ) ) {
		$tridy .= ' grid-gsel';
	}
	return $tridy;
}, 10, 2 );
