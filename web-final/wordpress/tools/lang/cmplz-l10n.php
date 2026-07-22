<?php
if ( ! class_exists( 'PLL_MO' ) ) { echo "PLL_MO chybí\n"; exit(1); }
$cs = array(
	'Spravovat souhlas' => array( 'Manage consent', 'Zustimmung verwalten' ),
	'Spravovat Souhlas' => array( 'Manage Consent', 'Zustimmung verwalten' ),
	'Odmítnout' => array( 'Deny', 'Ablehnen' ),
	'Uložit předvolby' => array( 'Save preferences', 'Einstellungen speichern' ),
	'Zobrazit předvolby' => array( 'View preferences', 'Einstellungen ansehen' ),
	'Funkční' => array( 'Functional', 'Funktional' ),
	'Statistiky' => array( 'Statistics', 'Statistiken' ),
	'Předvolby' => array( 'Preferences', 'Präferenzen' ),
	'Přijmout' => array( 'Accept', 'Akzeptieren' ),
	'Abychom poskytli co nejlepší služby, používáme k ukládání a/nebo přístupu k informacím o zařízení, technologie jako jsou soubory cookies. Souhlas s těmito technologiemi nám umožní zpracovávat údaje, jako je chování při procházení nebo jedinečná ID na tomto webu. Nesouhlas nebo odvolání souhlasu může nepříznivě ovlivnit určité vlastnosti a funkce.'
		=> array( 'To provide the best experiences, we use technologies like cookies to store and/or access device information. Consenting to these technologies will allow us to process data such as browsing behaviour or unique IDs on this site. Not consenting or withdrawing consent may adversely affect certain features and functions.',
			'Um Ihnen ein optimales Erlebnis zu bieten, verwenden wir Technologien wie Cookies, um Geräteinformationen zu speichern und/oder darauf zuzugreifen. Wenn Sie diesen Technologien zustimmen, können wir Daten wie das Surfverhalten oder eindeutige IDs auf dieser Website verarbeiten. Wenn Sie Ihre Zustimmung nicht erteilen oder zurückziehen, können bestimmte Merkmale und Funktionen beeinträchtigt werden.' ),
	'Technické uložení nebo přístup je nezbytně nutný pro legitimní účel umožnění použití konkrétní služby, kterou si odběratel nebo uživatel výslovně vyžádal, nebo pouze za účelem provedení přenosu sdělení prostřednictvím sítě elektronických komunikací.'
		=> array( 'The technical storage or access is strictly necessary for the legitimate purpose of enabling the use of a specific service explicitly requested by the subscriber or user, or for the sole purpose of carrying out the transmission of a communication over an electronic communications network.',
			'Die technische Speicherung oder der Zugang ist unbedingt erforderlich für den rechtmäßigen Zweck, die Nutzung eines bestimmten Dienstes zu ermöglichen, der vom Teilnehmer oder Nutzer ausdrücklich gewünscht wird, oder für den alleinigen Zweck, die Übertragung einer Nachricht über ein elektronisches Kommunikationsnetz durchzuführen.' ),
	'Technické uložení nebo přístup, který se používá výhradně pro statistické účely.'
		=> array( 'The technical storage or access that is used exclusively for statistical purposes.',
			'Die technische Speicherung oder der Zugriff, der ausschließlich zu statistischen Zwecken erfolgt.' ),
	'Technické uložení nebo přístup, který se používá výhradně pro anonymní statistické účely. Bez předvolání, dobrovolného plnění ze strany vašeho Poskytovatele internetových služeb nebo dalších záznamů od třetí strany nelze informace, uložené nebo získané pouze pro tento účel, obvykle použít k vaší identifikaci.'
		=> array( 'The technical storage or access that is used exclusively for anonymous statistical purposes. Without a subpoena, voluntary compliance on the part of your Internet Service Provider, or additional records from a third party, information stored or retrieved for this purpose alone cannot usually be used to identify you.',
			'Die technische Speicherung oder der Zugriff, der ausschließlich zu anonymen statistischen Zwecken verwendet wird. Ohne eine Vorladung, die freiwillige Zustimmung Ihres Internetdienstanbieters oder zusätzliche Aufzeichnungen von Dritten können die zu diesem Zweck gespeicherten oder abgerufenen Informationen in der Regel nicht dazu verwendet werden, Sie zu identifizieren.' ),
	'Technické uložení nebo přístup je nezbytný pro legitimní účel ukládání preferencí, které nejsou požadovány odběratelem nebo uživatelem.'
		=> array( 'The technical storage or access is necessary for the legitimate purpose of storing preferences that are not requested by the subscriber or user.',
			'Die technische Speicherung oder der Zugriff ist für den rechtmäßigen Zweck der Speicherung von Präferenzen erforderlich, die nicht vom Abonnenten oder Benutzer angefordert wurden.' ),
	'Technické uložení nebo přístup je nutný k vytvoření uživatelských profilů za účelem zasílání reklamy nebo sledování uživatele na webových stránkách nebo několika webových stránkách pro podobné marketingové účely.'
		=> array( 'The technical storage or access is required to create user profiles to send advertising, or to track the user on a website or across several websites for similar marketing purposes.',
			'Die technische Speicherung oder der Zugriff ist erforderlich, um Nutzerprofile zu erstellen, um Werbung zu versenden oder um den Nutzer auf einer Website oder über mehrere Websites hinweg zu ähnlichen Marketingzwecken zu verfolgen.' ),
);
foreach ( array( 'en' => 0, 'de' => 1 ) as $slug => $idx ) {
	$lang = PLL()->model->get_language( $slug );
	if ( ! $lang ) { echo "jazyk $slug nenalezen\n"; continue; }
	$mo = new PLL_MO();
	$mo->import_from_db( $lang );
	$n = 0;
	foreach ( $cs as $src => $tr ) { $mo->add_entry( $mo->make_entry( $src, $tr[ $idx ] ) ); $n++; }
	$mo->export_to_db( $lang );
	echo "$slug: $n překladů uloženo\n";
}
