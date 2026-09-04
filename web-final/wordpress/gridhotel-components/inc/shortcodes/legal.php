<?php
/**
 * GRID Hotel Components — právní texty (GRID-SUITE-02 §4). Obsah je 1:1
 * převzat z child theme (žádná úprava právního textu bez zadání klienta).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* [grid_legal]OBSAH[/grid_legal] — stylovaný obal pro právní texty (enclosing shortcode). */
function gridc_sc_legal( $atts, $content = '' ) {
	$a = shortcode_atts( array( 'nadpis' => '', 'kicker' => 'Právní informace' ), $atts );
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal grid-component grid-component--legal" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker"><?php echo esc_html( $a['kicker'] ); ?></span>
	    <?php if ( $a['nadpis'] ) : ?><h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 20px"><?php echo esc_html( $a['nadpis'] ); ?></h1><?php endif; ?>
	    <div class="legal-body"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_legal', 'gridc_sc_legal' );

/* [grid_podminky] — Ubytovací a reklamační řád (text 1:1 z gridhotel.cz). */
function gridc_podminky_rows() {
	return array(
		array( 'h2', 'Ubytovací řád GRID hotelu' ),
		array( 'h3', 'Podmínky a způsob ubytování' ),
		array( 'p', '1. GRID HOTEL ****, (dále jen „ubytovatel“) je oprávněn ubytovat jen hosta, který se řádně přihlásí. Za tímto účelem předloží k nahlédnutí zaměstnanci na recepci ihned po příchodu svůj občanský průkaz nebo jiný platný průkaz totožnosti, cestovní pas nebo jiný cestovní doklad ve smyslu zákona o pobytu cizinců na území ČR.' ),
		array( 'p', '2. Každý host, který není státním občanem ČR (cizinec), je povinný ve smyslu zákona o pobytu cizinců na území ČR v platném znění vyplnit a odevzdat na recepci úřední doklad o hlášení pobytu, všechny požadované údaje je host povinen uvést pravdivě a úplně.' ),
		array( 'p', '3. Na základě objednaného a ubytovatelem písemně potvrzeného ubytování se host v den příjezdu může ubytovat v době od 14:00 h. do 24:00 h. Do této doby ubytovatel pro hosta pokoj rezervuje, pokud v objednávce nebyl jiný požadavek a ubytovatel jej potvrdil.' ),
		array( 'p', '4. Host, který se ubytuje před 06:00 h, resp. trvá na ubytování před 10:00 h., je povinen zaplatit plnou cenu i za předcházející noc, pokud se dopředu nedohodl ubytovatel s hostem jinak.' ),
		array( 'p', '5. Host ubytovaný v GRID HOTELU odhlásí svůj pobyt do 10.00 h. Do této doby pokoj uvolní, pokud nebylo individuálně a dopředu s ubytovatelem dohodnuto jinak. Pokud host neuvolní pokoj do stanoveného času, může mu ubytovatel účtovat pobyt za celý následující den, pokud nebylo předem dohodnuto jinak. Pokoj se považuje za uvolněný potom, co host vynese z pokoje všechny své věci, odevzdá klíč pověřenému zaměstnanci ubytovacího zařízení a oznámí, že se odhlašuje z pobytu. Ubytovatel si vyhrazuje právo na kontrolu inventáře pokoje (nábytek, spotřebiče, zapomenuté věci) a úhrady a spotřeby hosta, a to do 1 hodiny od uvolnění pokoje. Hotel nezodpovídá za movité věci vnesené hostem do pokoje poté, co host uvolní pokoj či poté co skončí ubytovací vztah mezi hotelem a hostem. Pokud host neuvolní pokoj, vyhrazuje si hotel právo zamezit hostu přístup do pokoje a pro případ neuhrazené platby za pobyt či jiných pohledávek za klientem si hotel vyhrazuje možnost využít zadržovací právo k movitým věcem vneseným hostem do pokoje.' ),
		array( 'p', '6. V případě, že host požádá o prodloužení ubytování, může mu ubytovatel nabídnout i jiný pokoj v jiné cenové relaci, než byl ten původní. V tomto případě host nemá nárok na ubytování v pokoji, ve kterém byl původně ubytovaný, a ani na ubytování v jiném pokoji, pokud to z kapacitních nebo provozních důvodů není možné.' ),
		array( 'p', '7. Ubytovatel si vyhrazuje právo ve výjimečných případech nabídnout hostovi jiné ubytování, než bylo původně dohodnuté, pokud se podstatně neliší od potvrzené objednávky.' ),
		array( 'p', '8. Ubytovatel poskytuje svým hostům služby v rozsahu, v jakém byly vzájemně dohodnuty a v rozsahu, v jakém to určuje příslušný platný právní předpis. Host je povinen uhradit platbu za ubytování a poskytnuté služby v souladu s platným ceníkem ubytovatele nejpozději v den skončení pobytu. Tímto jsou platební podmínky nedotknutelné na základě smluv o ubytování. Ceník služeb za přechodné ubytování a další služby je k nahlédnutí na recepci hotelu.' ),
		array( 'p', '9. Host je povinen přizpůsobit pobyt v hotelu svému aktuálnímu zdravotnímu stavu a fyzickým i psychickým schopnostem.' ),
		array( 'p', '10. Hotel si vyhrazuje právo hosta neubytovat, pokud oděv či chování hosta neodpovídá dobrým mravům, host je zjevně pod vlivem alkoholu či psychotropních látek nebo je host či jeho oděv či zavazadla nadměrně znečištěn.' ),
		array( 'h3', 'Platba za poskytnuté ubytování a storno poplatky' ),
		array( 'p', '11. Za ubytování a poskytnuté služby je host povinen uhradit cenu v souladu s platným ceníkem, nejpozději však v den ukončení pobytu na základě předložení účtu, spolu s vyúčtováním poskytnutých záloh ze strany hosta.' ),
		array( 'p', '12. Ubytovatel si vyhrazuje právo požadovat od hosta při rezervaci zálohu 50 % až 100 % z ceny ubytování či za tímto účelem vyžadovat sdělení údajů platební karty hosta. Rezervace se pro ubytovací zařízení stává závaznou až po obdržení úhrady zálohy na účet ubytovatele, není-li dohodnuto jinak.' ),
		array( 'p', '13. V případě zkrácení pobytu nebo jiné změny hostem, má ubytovatel právo hostovi vyúčtovat plnou výši (100 %) dohodnuté ceny za celou délku pobytu.' ),
		array( 'p', '14. Ubytovatel je oprávněný účtovat storno poplatky, a na jejich zaplacení použít i složenou zálohu, v případě, že host zruší svou rezervaci pobytu písemně, elektronicky nebo telefonicky dle následujících podmínek:' ),
		array( 'p', '– V případě nevratné rezervace není možné tuto rezervaci bezplatně zrušit a host nemá nárok na vrácení zaplacené zálohy. Ubytovatel si u této rezervace za účelem garance ubytování vyhrazuje právo požadovat sdělení údajů platební karty hosta pro provedení zálohové platby v plné výši ceny ubytování.' ),
		array( 'p', '– V případě zrušení větší (5 a více pokojů) rezervace v době více než 30 dnů před prvním dnem pobytu hostů je toto zrušení zdarma.' ),
		array( 'p', '– V případě zrušení větší rezervace v době mezi 30. dnem a 72 hodinami před prvním dnem pobytu hostů činí storno poplatek 50 % z celkové ceny rezervace.' ),
		array( 'p', '– V případě zrušení větší rezervace méně jak 72 hodin před prvním dnem pobytu hostů činí storno poplatek 100 % z celkové ceny rezervace.' ),
		array( 'p', '– V případě zrušení menší (4 a méně pokojů) rezervace do 72 hodin před jejím začátkem je toto zrušení zdarma.' ),
		array( 'p', '– V případě zrušení menší rezervace méně jak 72 hodin před jejím začátkem činí storno poplatek 100 % z celkové ceny rezervace.' ),
		array( 'p', 'Nebo pokud se dopředu nedohodl ubytovatel s hostem jinak.' ),
		array( 'h3', 'Zodpovědnost ubytovatele a hosta' ),
		array( 'p', '15. Ubytovatel zodpovídá za škodu způsobenou na věcech vnesených a odložených hostem v ubytovací části zařízení podle obecně závazných předpisů.' ),
		array( 'p', '16. Ubytovatel poskytuje hostům bezpečnostní trezory na pokoji, do kterých doporučuje uložit cennosti. Uložení věcí v trezoru v pokoji není možné považovat za převzetí věcí ubytovatelem do úschovy.' ),
		array( 'p', '17. Za škody způsobené na zařízení, resp. inventáři ubytovacího zařízení zodpovídá host podle příslušných platných právních předpisů. V případě poškození nebo zničení majetku ubytovatele má ubytovatel právo na náhradu škody. Je v zájmu hosta informovat se na hodnotu inventáře v případě znehodnocení nebo poškození zařízení v pokoji. Host jako zákonný zástupce zodpovídá za škody způsobené neplnoletými osobami, za které je zodpovědný, jako i za škody způsobené osobami nebo zvířaty, které se nacházejí v prostorách ubytovacího zařízení, a pobyt jim tam umožnil host.' ),
		array( 'p', '18. V případě škody na majetku ubytovatele způsobené hostem je host povinen uhradit náhradu způsobené škody nejpozději v den skončení pobytu hosta nebo na základě faktury vystavené do 14 dní ode dne skončení pobytu hosta, splatné do 10 dní od doručení hostovi za předpokladu, že ubytovatel rozhodne o takovém způsobu úhrady škody. Hotel je oprávněn provést blokaci a stržení takto vyúčtovaných částek na platební kartě hosta.' ),
		array( 'p', '19. Praní prádla hostů. Ubytovatel si vyhrazuje odmítnout ošetřit prádlo, jež je nadměrně znečištěno nebo je poškozeno. Čistírna nenese odpovědnost za narušené vybarvení (ekologické barvy), knoflíky či ozdobné spony poškozené během čistícího procesu. Náhrady škody či ztráty vzniklé vinou čistírny může dosáhnout maximálně pětinásobku ceny za čištění nebo praní uvedeného prádla.' ),
		array( 'p', '20. Ubytovatel nezodpovídá za odcizení, případně za poškození motorových vozidel ponechaných na parkovišti ubytovatele. Ubytovatel doporučuje hostům, aby se přesvědčili o řádném uzamčení a zabezpečení auta. Také doporučuje nenechávat v autě volně položené osobní věci. Ubytovatel nenese odpovědnost za škody způsobené hostem na parkovišti třetím osobám. Ubytovatel si vyhrazuje právo požadovat a vyúčtovat škodu, jež vznikne na majetku zařízení vozidlem hosta.' ),
		array( 'p', '21. Host je povinen chovat se tak, aby předcházel škodám na zdraví, na majetku, na přírodě a životním prostředí. Před odchodem z pokoje host je povinen uzavřít okna, vodovodní kohoutky, vypnout elektrické přístroje a uzamknout pokoj.' ),
		array( 'p', '22. Ubytovatel nezodpovídá za jakékoliv škody způsobené mimo vyhrazený areál hotelu.' ),
		array( 'h3', 'Stravování a prodej alkoholických nápojů' ),
		array( 'p', '23. V prostorách ubytovacího zařízení je povolena konzumace alkoholu osobám starším 18 let, a to výhradně v rámci nápojového lístku nebo vinné karty ubytovatele.' ),
		array( 'p', '24. Host není oprávněn vnášet do pokojů alkoholické nápoje nebo jakékoliv jiné potraviny zakoupené jinde než v prostorách ubytovatele.' ),
		array( 'p', '25. Host je povinen seznámit personál ubytovacího zařízení s jakýmikoliv závažnými zdravotními omezeními, příp. stravovacími omezeními a tyto omezení nahlásí na recepci.' ),
		array( 'p', '26. Personál je oprávněn odmítnout podat alkoholický nápoj osobám mladším 18 let a osobám zjevně již pod vlivem alkoholu.' ),
		array( 'p', '27. Ubytovatel poskytuje snídaně, obědy a večeře v restauraci GRID BUFFET v časovém rozmezí určeném dle provozu.' ),
		array( 'p', '28. Při příchodu na snídaně je host povinen mít na zápěstí připnutý identifikační pásek. Personál je oprávněn provádět kontrolu jeho nošení. V případě přetržení nebo jiného poškození tohoto pásku host výměnou za zničený kus obdrží od hotelové recepce pásek nový.' ),
		array( 'p', '29. Součástí všech pokojů hotelu jsou minibary, jež může dle svého uvážení host využít. Ceny a služby jsou určeny v ceníku určeném pro minibar. Minibary jsou pokojovou službou denně doplňovány. Každá spotřebovaná nebo doplněná položka, jež je součástí sortimentu minibaru, je zaznamenána na kontrolním lístku pokojovou službou.' ),
		array( 'p', '30. Kontrolní lístek minibaru vyplněný nebo i prázdný v případě, že host z minibaru nekonzumoval, musí host podepsaný odevzdat na hotelové recepci při odjezdu. Jinak nemůže být účet hosta uzavřen. Host svým podpisem na kontrolním lístku potvrzuje množství konzumace. Ubytovatel není povinen kontrolovat při odjezdu hosta stav a počet položek v minibaru.' ),
		array( 'p', '31. V případě nesrovnalostí v konzumaci minibaru bude hostu naúčtována dlužná částka k zaplacení. Na dodatečné reklamace k výši konzumace nebude brán zřetel. Hotel je oprávněn provést blokaci a stržení takto vyúčtovaných částek na platební kartě hosta.' ),
		array( 'h3', 'Všeobecně platné ustanovení' ),
		array( 'p', '32. Pro přijímání návštěv ubytovaných hostů jsou vyhrazeny prostory přízemí hotelu, případně jiné společenské prostory hotelu. V pokoji, kde je host ubytovaný, smí přijímat návštěvy jen se souhlasem zodpovědného zaměstnance nebo vedení hotelu, po zaevidování v čase od 08:00 h. do 22:00 h. Zaměstnanec hotelu není oprávněn podávat jakékoliv informace o ubytovaných hostech třetím osobám (s výjimkou příslušníků policie po jejich legitimování se a prokázání opodstatněnosti požadovat tyto údaje) ani povolit návštěvu třetí osoby hosta bez jeho souhlasu.' ),
		array( 'p', '33. V pokoji a společenských prostorách nesmí host bez souhlasu zodpovědného pracovníka nebo vedení přemisťovat interiérové zařízení, provádět jakékoliv změny a úpravy na zařízení, vykonávat zásahy do elektrické sítě nebo jiné instalace.' ),
		array( 'p', '34. V pokoji není hostům dovoleno používat vlastní elektrické spotřebiče. Toto nařízení se netýká elektrických spotřebičů osobní hygieny (holicí strojek, masážní strojek, fén atd.)' ),
		array( 'p', '35. Hostům není dovoleno vnášet do pokojů věci pro úschovu, kterým nejsou vyčleněna místa, např. sportovní potřeby, kočárky, kola, vozíky apod. Na úschovu těchto věcí se host informuje na recepci. Za poškození majetku ubytovatele způsobené i přes tento zákaz bude hostovi účtována náhrada škody v plné výši. V případě porušení tohoto zákazu je ubytovatel oprávněn účtovat hostovi smluvní pokutu ve výši 1 000 Kč za každé porušení. V případě, že bude způsobená škoda vyšší, ubytovatel si vyhrazuje právo účtovat škodu v plné výši.' ),
		array( 'p', '36. Kouření je ve všech vnitřních prostorách hotelu striktně zakázáno! Povoleno je jen ve vyhrazených venkovních prostorách ubytovatele. V pokojích, platí přísný zákaz kouření. V případě porušení tohoto zákazu je ubytovatel oprávněn účtovat hostovi smluvní pokutu ve výši 5 000 Kč za každé porušení. V případě, že bude způsobená škoda vyšší, ubytovatel si vyhrazuje právo účtovat škodu v plné výši.' ),
		array( 'p', '37. V hotelu je přísný zákaz užívání jakýchkoliv omamných a psychotropních látek. Ubytovatel je oprávněn informovat Policii ČR a okamžitě zrušit ubytování hosta, jenž tento zákaz porušil, bez náhrady.' ),
		array( 'p', '38. Psi a jiná zvířata se mohou pohybovat v prostorách ubytovacího zařízení jen se souhlasem zodpovědného zaměstnance nebo na základě předcházející dohody hosta za předpokladu, že majitel prokáže jejich zdravotní způsobilost. Cena za ubytování zvířete se účtuje podle platného ceníku. Na ubytování psů a jiných zvířat se vztahují následující opatření:' ),
		array( 'p', 'Psům a jiným zvířatům je zakázaný vstup a pobyt v těch prostorách, ve kterých jsou skladované a připravované potraviny nebo se podávají jídla a nápoje.' ),
		array( 'p', 'Vstup do ubytovací části mají pouze malá plemena psů.' ),
		array( 'p', 'Ve všech veřejných prostorách musí být každý pes na vodítku a mít náhubek.' ),
		array( 'p', 'Psi a jiná zvířata se nesmí nechat odpočívat/ležet na lůžku nebo jiném zařízení, které slouží k odpočinku hosta.' ),
		array( 'p', 'Na krmení psů a jiných zvířat nesmí být použitý inventář, který slouží na přípravu nebo podávání jídla hostům.' ),
		array( 'p', 'V případě jakéhokoliv poškození zařízení zvířetem je host povinen zaplatit škodu v plné výši. Za zvíře zodpovídá v plném rozsahu majitel zvířete a host, který zvířeti pobyt v pokoji umožnil.' ),
		array( 'p', 'Za výše uvedené porušení pravidel a opatření, vyjma přímého poškození majetku, které je účtováno hostu v plné výši, bude hostovi účtována za dodatečný úklid pokoje či prostor znečištěný zvířetem částka až ve výši 5 000 Kč. Ubytovatel si vyhrazuje právo vyúčtovat případně i přímé náklady za čištění, jež budou převyšovat výše uvedenou částku, a to v plné výši. Ubytovatel si také vyhrazuje právo k úhradě zaplacení nových lůžkovin, jež byly použity pro odpočinek zvířat. Tyto lůžkoviny budou hostovi vyúčtovány v plné výši.' ),
		array( 'p', 'Úklid, kontrola pokoje a opravy na pokojích, kde je host ubytován i se zvířetem, musí být umožněny tak, aby nedošlo k ohrožení personálu či jiných hostů. Kontrola musí být umožněna, alespoň jednou denně pro případné zjištění škod či nadměrného znečištění. Personál není povinen provést úklid nebo opravy na pokoji v případě, že se cítí být ohrožen psem nebo jiným zvířetem na pokoji.' ),
		array( 'p', '39. Před odchodem je host povinen odevzdat kartu od pokoje při odhlašování z pobytu.' ),
		array( 'p', '40. Za ztrátu či znehodnocení karty účtuje ubytovatel částku 100 Kč za kus.' ),
		array( 'p', '41. Odpadky jsou hosti povinni dávat výlučné do určených nádob na vyhrazených místech.' ),
		array( 'p', '42. Ubytovatel doporučuje z bezpečnostních důvodů neponechávat děti do 12 roků bez dozoru dospělých ani v pokoji ani v ostatních společenských prostorách.' ),
		array( 'p', '43. V čase od 22:00 h. do 07:00 h. je host povinen dodržovat noční klid. Se souhlasem provozovatele (vedoucího, resp. zástupce) se mohou organizovat v prostorách zařízení společenské akce i po 22:00 h., a to v prostorách k tomu určených.' ),
		array( 'p', '44. Host v prostorách ubytovacího zařízení nesmí nosit střelnou zbraň, střelivo či jiné zbraně, nebo je jakýmkoli způsobem přechovávat ve stavu umožňujícím jejich okamžité použití.' ),
		array( 'p', '45. Stížnosti hostů a případné návrhy na zlepšení činnosti přijímá vedení hotelu. Dotazník je k dispozici na hotelových pokojích nebo na recepci.' ),
		array( 'p', '46. Spory, které vzniknou z této smlouvy, budou řešeny prostřednictvím soudů v České republice. Ve sporech o náhradu škody, ve kterých žalovanou osobou bude osoba mající bydliště v některém z členských států EU, je daná příslušnost soudu místa, kde ke škodě došlo, podle čl. 5, bod 3 Nařízení rady (ES) č. 44/2001 ze dne 22. 12. 2000 o příslušnosti a uznávání a výkonu soudních rozhodnutí v občanských a obchodních věcech.' ),
		array( 'p', '47. Host je povinen dodržovat ustanovení tohoto ubytovacího řádu. V případě, že host nebude dodržovat ubytovací řád, má ubytovatel právo odstoupit od poskytování ubytovacích služeb a odstoupit od ubytovací smlouvy před uplynutím dohodnutého času. Ubytovatel má v takovém případě právo na plnou úhradu ceny za ubytování. Host musí následně bezodkladně opustit hotel. Host je povinen obeznámit se s provozními a bezpečnostními pravidly ubytovatele, včetně všech jeho zařízení a důsledně je dodržovat.' ),
		array( 'p', '48. Host svým podpisem registrační karty odsouhlasil, že se obeznámil s provozním řádem ubytovatele. Ubytování hostů se řídí českým právním řádem, na základě českého práva a tímto ubytovacím řádem. Ubytováním host přijímá ubytovací řád jako smluvní podmínky ubytování a je povinen dodržovat jeho ustanovení. Host je povinen se s tímto ubytovacím řádem seznámit, na jeho neznalost nebude brán zřetel.' ),
		array( 'p', '49. Host poskytující ubytovateli při vzniku ubytovací služby své osobní údaje ze svých dokladů souhlasí se zpracováním a uchováním svých osobních údajů ve společnosti GRH s.r.o. ve smyslu zák. č. 101/2000 Sb. v platném znění' ),
		array( 'h3', 'Ochrana spotřebitele' ),
		array( 'p', 'Poskytujeme Vám tímto veškeré informace dle ustanovení § 1811 a § 1820 zákona č. 89/2012 Sb., občanský zákoník, v platném znění (dále jen „občanský zákoník“).' ),
		array( 'p', 'Ubytovatel poskytuje ubytovaným hostům následující informace:' ),
		array( 'p', 'a) Totožnost a kontaktní údaje ubytovatele: GRH s.r.o., Ostrovačická 936/65, 641 00 Brno, DIČ CZ04996364, společnost zapsaná v obchodním rejstříku vedeném u Krajského soudu v Brně, oddíl C, vložka 92997;' ),
		array( 'p', 'b) hlavní předmět podnikání ubytovatele: poskytování ubytovacích služeb;' ),
		array( 'p', 'c) označení služby: ubytovatel obstarává pro ubytované hosty ubytování a služby související s ubytováním na základě podmínek uvedených v potvrzení rezervace,' ),
		array( 'p', 'd) náklady na prostředky komunikace na dálku: náklady na prostředky komunikace na dálku určují subjekty poskytující služby prostředků komunikace na dálku a tyto náklady se neliší od základní sazby;' ),
		array( 'p', 'e) údaj o existenci, způsobu a podmínkách mimosoudního vyřizování stížností spotřebitelů včetně údaje, zda se lze obrátit na orgán dohledu; ubytovaný host má právo podat návrh na mimosoudní řešení takového sporu určenému subjektu mimosoudního řešení spotřebitelských sporů, kterým je:' ),
		array( 'p', 'Česká obchodní inspekce' ),
		array( 'p', 'Ústřední inspektorát – oddělení ADR Štěpánská 15, 120 00 Praha 2' ),
		array( 'p', 'Email: adr@coi.cz / Web: adr.coi.cz.' ),
		array( 'p', 'Česká obchodní inspekce je dozorovým orgánem vykonávajícím dohled nad ochranou spotřebitele, postupující podle zákona č. 64/1986 Sb., o České obchodní inspekci, ve znění pozdějších předpisů, a dalších právních předpisů. Internetová stránka České obchodní inspekce je www.coi.cz ;' ),
		array( 'p', 'f) v souladu s ustanovením § 1837 písmeno j) občanského zákoníku ubytovaným hostům jako spotřebitelům nevzniká právo na odstoupení od smlouvy o ubytování, pokud ubytovatel poskytuje plnění v určeném termínu;' ),
		array( 'p', 'g) označení členského státu nebo členských států Evropské unie, jejichž právními předpisy se bude řídit vztah mezi ubytovaným hostem a ubytovatelem založený na základě potvrzení rezervace: Česká republika;' ),
		array( 'p', 'h) údaj o jazyku, ve kterém bude ubytovaný host s ubytovatelem jednat po dobu pobytu a ve kterém poskytne ubytovaným hostům smluvní podmínky a další údaje: český jazyk.' ),
		array( 'p', 'Ubytovací řád je platný od 1. 6. 2017 a byl aktualizovaný 14. 11. 2023' ),
		array( 'h2', 'Reklamační řád GRID HOTELU' ),
		array( 'p', 'GRID HOTEL je provozovaný společností GRH s.r.o., se sídlem Ostrovačická 936/65, 641 00 Brno – Žebětín, IČ: 04996364, DIČ: CZ04996364, zapsaná v OR Krajského soudu v Brně, oddíl C, vl. 92997, zastoupena jednatelem Ing. Karlem Hubáčkem (dále jen „hotel“).' ),
		array( 'h3', '1. Předmět' ),
		array( 'p', '1.1. Tento reklamační řád upravuje v souladu s platnými právními předpisy, zejména zákonem č. 89/2012 Sb., občanský zákoník, ve znění pozdějších předpisů (dále jen „občanský zákoník“), a zákonem č. 634/1992 Sb., o ochraně spotřebitele, ve znění pozdějších předpisů (dále jen „zákon o ochraně spotřebitele“), rozsah, podmínky a způsob uplatňování práv zákazníka z vadného plnění vyplývajícího z odpovědnosti hotelu za vady pobytu, poskytnuté jednotlivé služby nebo prodaného zboží a jejich vyřizování (dále také jen „reklamace“). Reklamační řád je k dispozici také na webových stránkách společnosti hotelu – www.gridhotel.cz' ),
		array( 'h3', '2. Uplatňování reklamací' ),
		array( 'p', '2.1. V případě vadně poskytnutých služeb nebo služeb, které byly prokazatelně objednány a potvrzeny, avšak neposkytnuty, vzniká zákazníkovi právo reklamace. Práva z vadného plnění zákazník uplatňuje v kterékoliv provozovně společnosti v sídle nebo u zprostředkovatele služeb hotelu, kde reklamované služby či zboží zakoupil, případně v místě poskytované služby u pověřeného zástupce hotelu.' ),
		array( 'p', '2.2. Zákazník je povinen vytknout vadu poskytovaných služeb včas, bez zbytečného odkladu, pokud možno na místě poskytnutí služby. Nevytkne-li zákazník vadu poskytovaných služeb bez zbytečného odkladu, nemůže mu být reklamace uznána. Právo z odpovědnosti za vady jednotlivé služby zakoupené na základě smlouvy o poskytnutí jednotlivé služby je zákazník povinen vytknout bez zbytečného odkladu po jejím zjištění, nejpozději však do 6 měsíců od okamžiku, kdy mu byla služba poskytnuta. Neprodlené vytknutí vady (uplatnění reklamace) na místě samém umožní odstranění vady okamžitě, zatímco s odstupem času se ztěžuje průkaznost i objektivnost posouzení a tím i možnost řádného vyřízení reklamace.' ),
		array( 'p', '2.3. Práva z odpovědnosti za vady prodaného zboží zaniknou, nebyla-li uplatněna do 24 měsíců ode dne převzetí.' ),
		array( 'p', '2.4. Zákazník je při uplatňování reklamace povinen uvést jméno, příjmení, adresu, co je obsahem reklamace, svou reklamaci zdůvodnit a podle možností i předmět reklamace průkazně skutkově doložit; současně je doporučeno předložit doklad o poskytnuté službě, stejnopis objednávky, fakturu, potvrzení o platbě apod., čímž se usnadní vyřizování reklamace. V případě zakoupeného zboží je zákazník povinen jej při reklamaci předložit.' ),
		array( 'p', '2.5. Reklamaci může zákazník uplatnit jakoukoliv formou s uvedením data, předmětu reklamace a požadovaného způsobu vyřízení reklamace. V případě ústního podání reklamace je hotelem pověřený zástupce povinen sepsat se zákazníkem reklamační protokol, resp. vydat písemné potvrzení o přijetí reklamace. V protokolu uvede osobní údaje zákazníka, kdy zákazník reklamaci uplatnil, co je obsahem reklamace, jaký způsob vyřízení reklamace zákazník vyžaduje a dále datum a požadovaný způsob vyřízení reklamace. Protokol, resp. potvrzení o přijetí reklamace podepíše sepisující zástupce hotelu i zákazník, který podpisem vyslovuje souhlas s jeho obsahem.' ),
		array( 'p', '2.6. Jestliže zákazník zároveň předá hotelu nebo zprostředkovateli služeb hotelu písemnosti, popř. jiné podklady týkající se reklamace, popř. reklamované zboží musí být tato skutečnost v protokolu výslovně uvedena.' ),
		array( 'h3', '3. Vyřizování reklamací' ),
		array( 'p', '3.1. Hotel je povinen zákazníkovi vydat písemné potvrzení o tom, kdy zákazník reklamaci uplatnil, co je obsahem reklamace, jaký způsob vyřízení reklamace zákazník požaduje a dále potvrzení o datu a způsobu vyřízení reklamace a v případě reklamovaného zboží, včetně potvrzení o provedení opravy a době jejího trvání, případně písemné odůvodnění zamítnutí reklamace.' ),
		array( 'p', '3.2. Uplatní-li zákazník právo z vadného plnění související se službami, které mu jsou poskytovány, nebo které mu již byly poskytnuty, vedoucí provozovny poskytující předmětné služby nebo jiný hotelem pověřený zástupce je povinen po potřebném prozkoumání skutkových a právních okolností rozhodnout o reklamaci ihned, ve složitých případech do tří pracovních dnů. Do této doby se nezapočítává doba potřebná k odbornému posouzení vady. Reklamace musí být vyřízena bez zbytečného odkladu, nejpozději do 30 dnů od uplatnění reklamace zákazníkem, pokud se zákazníkem není dohodnuta lhůta delší.' ),
		array( 'p', '3.3. V případě písemných reklamačních podání platí pro jejich obsah přiměřeně ustanovení odstavce 3.1. reklamačního řádu.' ),
		array( 'h3', '4. Součinnost zákazníka při vyřizování reklamací' ),
		array( 'p', '4.1. Zákazník je povinen poskytnout potřebnou součinnost k vyřízení reklamace, zejména podat informace, předložit doklady prokazující skutkový stav, předložit reklamované zboží, specifikovat své požadavky co do důvodu a výše apod. Vyžaduje-li to povaha věci, musí zákazník umožnit pověřenému zástupci hotelu, jakož i zástupcům dodavatele služby přístup do prostoru, který mu byl poskytnut k ubytování apod., aby se mohli přesvědčit o oprávněnosti reklamace.' ),
		array( 'p', '4.2. V případech, kdy zákazník čerpá služby bez přítomnosti zástupce hotelu a poskytnutá služba má vady, doporučuje hotel, aby zákazník dbal též o včasné a řádné uplatnění nároků vůči dodavatelům služeb.' ),
		array( 'h3', '5. Způsoby vyřízení reklamace' ),
		array( 'p', '5.1. V případech, kdy je reklamace posouzena jako zcela nebo z části důvodná, spočívá vyřízení reklamace v bezplatném odstranění vady služby nebo reklamovaného zboží, nebo v případech, kdy je to možné i k poskytnutí náhradní služby či výměny zboží. V závislosti na rozsahu a trvání vady má zákazník právo na přiměřenou slevu z ceny. Tím není dotčeno právo zákazníka domáhat se v zákonem stanovených případech odstoupení od smlouvy. V případech, kdy je reklamace posouzena jako nedůvodná, je zákazník písemně informován o důvodech zamítnutí reklamace.' ),
		array( 'p', '5.2. Nastanou-li okolnosti, jejichž vznik, průběh a příp. následek není závislý na vůli, činnosti a postupu hotelu (vis maior) nebo okolnosti, které jsou na straně zákazníka, na jejichž základě zákazník zcela nebo zčásti nevyužije objednané, zaplacené a hotelem zabezpečené služby, nevzniká zákazníkovi nárok na vrácení zaplacené ceny nebo na slevu z ceny.' ),
		array( 'h3', '6. Ostatní ustanovení' ),
		array( 'p', '6.1. V ostatním platí ustanovení obecně závazných právních předpisů, zejména občanského zákoníku a zákona o ochraně spotřebitele.' ),
		array( 'p', '6.2. V souladu s ustanovením § 14 zákona č. 634/1992 Sb., o ochraně spotřebitele, ve znění pozdějších předpisů má zákazník možnost řešit případné spory vyplývající ze smluv uzavřených s hotelem prostřednictvím subjektu mimosoudního řešení spotřebitelských sporů, kterým je Česká obchodní inspekce, se sídlem Štěpánská 567/15, Praha 2, PSČ 120 00, internetová adresa www.coi.cz.' ),
		array( 'h3', '7. Závěrečná ustanovení' ),
		array( 'p', '7.1. Tento Reklamační řád vstupuje v platnost a účinnost dnem 1. 6. 2017 a byl aktualizovaný dne 14. 11. 2023.' ),
		array( 'p', '7.2. Tento reklamační řád bude vyvěšen na vhodném a veřejně přístupném místě v hotelu a také na internetových stránkách hotelu www.gridhotel.cz.' ),
		array( 'p', 'Ing. Karel Hubáček, jednatel společnosti' ),
		array( 'p', 'V Brně dne 14. 11. 2023' ),
	);
}
function gridc_sc_podminky() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal grid-component grid-component--podminky" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:820px">
	    <span class="kicker">Právní informace</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 8px">Ubytovací a reklamační řád</h1>
	    <p style="color:var(--muted);margin:0 0 24px">Všeobecné obchodní podmínky GRID HOTELU. Ubytováním host přijímá ubytovací řád jako smluvní podmínky ubytování.</p>
	    <div class="legal-body">
	      <?php foreach ( gridc_podminky_rows() as $r ) :
	        list( $t, $txt ) = $r;
	        if ( 'h2' === $t ) {
	          echo '<h2 style="font-size:clamp(1.5rem,3.4vw,2.1rem);margin:34px 0 10px">' . esc_html( $txt ) . '</h2>';
	        } elseif ( 'h3' === $t ) {
	          echo '<h3 style="font-size:clamp(1.1rem,2.4vw,1.35rem);margin:24px 0 8px;color:var(--gold,#CAA75F)">' . esc_html( $txt ) . '</h3>';
	        } else {
	          echo '<p>' . esc_html( $txt ) . '</p>';
	        }
	      endforeach; ?>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_podminky', 'gridc_sc_podminky' );

/* [grid_gdpr] — prohlášení o ochraně osobních údajů (text 1:1). */
function gridc_sc_gdpr() {
	ob_start(); ?>
	<section class="sec sec-light sec-pad grid-legal grid-component grid-component--gdpr" style="padding-top:clamp(120px,16vh,180px)">
	  <div class="wrap" style="max-width:840px">
	    <span class="kicker">GDPR</span>
	    <h1 style="font-size:clamp(2.2rem,5vw,3.6rem);margin:14px 0 20px">Prohlášení ke zpracování osobních údajů</h1>
	    <div class="legal-body">
	      <p>Společnost GRH s. r. o, se sídlem Ostrovačická 936/65, Žebětín, 621 00 Brno, zapsaná v obchodním rejstříku Krajského soudu v Brně, oddíl C, vložka 92997, IČ: 04996364, jakožto správce osobních údajů (dále jen Společnost) v souvislosti s poskytováním svých služeb a nabízením svých produktů zpracovává osobní údaje Subjektů osobních údajů (dále jen Klienti).</p>
	      <p>Smyslem Prohlášení ke zpracování osobních údajů je poskytnout informace zejména o tom, jaké osobní údaje Společnost shromažďuje, jak s nimi nakládá, z jakých zdrojů je získává, k jakým účelům je využívá, komu je smí poskytnout, kde může získat informace o osobních údajích, které zpracovává, a jaká jsou individuální práva Klienta v oblasti ochrany osobních údajů.</p>
	      <h2>I. Obecné informace</h2>
	      <p>Osobní údaje Klienta zpracovává Společnost v minimálním rozsahu nezbytném pro účely nabízení svých služeb a produktů.</p>
	      <p>Při zpracování osobních údajů Společnost ctí a respektuje nejvyšší standardy ochrany osobních údajů a dodržuje zejména následující zásady:</p>
	      <p>(a) osobní údaje jsou vždy zpracovávány pro jasně a srozumitelně stanovený účel, stanovenými prostředky, stanoveným způsobem, a pouze po dobu, která je nezbytná vzhledem k účelům jejich zpracování; zpracovávány jsou pouze přesné osobní údaje, jejichž zpracování odpovídá stanovenému účelu a je nezbytné pro naplnění tohoto účelu;</p>
	      <p>(b) osobní údaje jsou zpracovávány způsobem, který zajišťuje nejvyšší možnou bezpečnost těchto údajů a který zabraňuje jakémukoliv neoprávněnému nebo nahodilému přístupu, k jejich změně, zničení či ztrátě, neoprávněným přenosům, k jejich jinému neoprávněnému zpracování, jakož i k jinému zneužití;</p>
	      <p>(c) Společnost vždy srozumitelně informuje o zpracování osobních údajů a o nárocích na přesné a úplné informace, o okolnostech jejich zpracování, jakož i o dalších souvisejících právech;</p>
	      <p>(d) Společnost dodržuje odpovídající technická a organizační opatření, aby byla zajištěna úroveň zabezpečení odpovídající všem možným rizikům; veškeré osoby, které přicházejí do styku s osobními údaji, mají povinnost dodržovat mlčenlivost o informacích získaných v souvislosti se zpracováváním těchto údajů.</p>
	      <h2>II. Informace o zpracování osobních údajů</h2>
	      <h3>a) Informace o Správci</h3>
	      <p>GRH s. r. o, se sídlem Ostrovačická 936/65, Žebětín, 621 00 Brno, zapsaná v obchodním rejstříku Krajského soudu v Brně, oddíl C, vložka 92997, IČ: 04996364, e-mail: info@gridhotel.cz, telefon: +420 775 877 817</p>
	      <h3>b) Účely zpracovávání a právní základ pro zpracování</h3>
	      <p>Na základě dobrovolného souhlasu zpracovává Společnost osobní údaje za účelem řádného plnění smlouvy a výkonu práv z ní plynoucích, nabízení produktů a služeb Společnosti: jde zejména o šíření informací, nabízení produktů a služeb Společnosti a to různými kanály, např. poštou, elektronickými prostředky (včetně elektronické pošty a zpráv zaslaných na mobilní zařízení prostřednictvím telefonního čísla) či telefonickým hovorem, prostřednictvím webových stránek.</p>
	      <h3>c) Rozsah zpracovávaných osobních údajů</h3>
	      <p>Společnost zpracovává osobní údaje v rozsahu nezbytném pro naplnění výše uvedeného cíle. Zpracovává tyto identifikační údaje: Jméno a příjmení, narození, telefonní číslo, e mailovou adresu, adresu trvalého bydliště/sídla, číslo občanského průkazu a číslo řidičského průkazu dále údaje ze vzájemné komunikace smluvních stran a dále údaje vzniknuvší v důsledku plnění smlouvy.</p>
	      <h3>č) Způsob zpracování osobních údajů</h3>
	      <p>Zpracování osobních údajů zahrnuje manuální i automatizované zpracování v informačních systémech Společnosti. Údaje zpracovávají pouze zaměstnanci Společnosti.</p>
	      <h3>d) Příjemci osobních údajů</h3>
	      <p>Osobní údaje jsou zpřístupněny pouze zaměstnancům Společnosti v souvislosti s plněním jejich pracovních povinností, při kterých je nutné nakládat s osobními údaji, pouze však v rozsahu, který je v tom kterém případě nezbytný a při dodržení veškerých bezpečnostních opatření.</p>
	      <h3>e) Předávání osobních údajů do zahraničí</h3>
	      <p>Osobní údaje jsou zpracovávány pouze na území České republiky.</p>
	      <h3>f) Doba zpracování osobních údajů</h3>
	      <p>Osobní údaje zpracovává Společnost pouze po dobu, která je nezbytná vzhledem k účelu jejich zpracování. Průběžně posuzuje, jestli nadále trvá potřeba zpracovávat určité osobní údaje potřebné pro určitý účel. Pokud zjistí, že již nejsou potřebné pro účel, pro který byly zpracovávány, údaje zlikviduje.</p>
	      <h3>g) Právo odvolat souhlas</h3>
	      <p>Souhlas se zpracováním osobních údajů není Subjekt osobních údajů povinen udělit a zároveň je oprávněn tento souhlas kdykoliv odvolat.</p>
	      <h3>h) Právo na přístup k osobním údajům</h3>
	      <p>Klient má právo získat od Společnosti potvrzení, zda osobní údaje, které se ho týkají, jsou či nejsou zpracovány, a pokud tomu tak je, má právo získat přístup k těmto osobním údajům a dalším informacím dle čl. 15 nařízení GDPR.</p>
	      <h3>Ch) Právo na opravu</h3>
	      <p>Dle čl. 16 nařízení GDPR má Klient právo na to, aby Společnost bez zbytečného podkladu opravila nepřesné osobní údaje, které se ho týkají a doplnil osobní údaje neúplné.</p>
	      <h3>i) Právo na výmaz</h3>
	      <p>Klient má právo na to, aby Společnost bez zbytečného odkladu vymazal osobní údaje, které se daného subjektů týkají, a to za podmínek dle čl. 17 nařízení GDPR, zejména tedy pokud již nejsou potřebné pro účely, pro které byly shromážděny. Toto právo rovněž naplňuje tzv. právo být zapomenut.</p>
	      <h3>j) Právo na omezení zpracování</h3>
	      <p>Klient má právo na to, aby Společnost omezila zpracování jeho osobních údajů, a to v kterémkoliv z případů uvedených v čl. 18 nařízení GDPR.</p>
	      <h3>k) Právo na přenositelnost údajů</h3>
	      <p>Klient má ve smyslu a za podmínek uvedených v čl. 20 nařízení GDPR právo od Společnosti získat osobní údaje, které se ho týkají, a to ve strukturovaném běžně používaném a strojově čitelném formátu, a právo předat tyto údaje jinému správci, aniž by tomuto původní AMD bránil.</p>
	      <h3>l) Právo podat stížnost</h3>
	      <p>Klient je oprávněn v souvislosti se zpracováním svých osobních údajů podat stížnost ve smyslu čl. 77 nařízení GDPR u dozorového úřadu. Dozor nad dodržováním povinností při zpracováním osobních údajů vykonává Úřad pro ochranu osobních údajů se sídlem Pplk. Sochora 27, 170 00 Praha. Více informací o právech subjektů údajů je k dispozici na internetových stránkách Úřadu pro ochranu osobních údajů (<a href="https://www.uoou.cz/6-prava-subjektu-udaj/d-27276" target="_blank" rel="noopener">https://www.uoou.cz/6-prava-subjektu-udaj/d-27276</a>).</p>
	      <p><em>Toto Prohlášení je platné a účinné ke dni 25. 5. 2018. Aktuální znění Prohlášení je uveřejněno na webových stránkách www.gridhotel.cz</em></p>
	    </div>
	  </div>
	</section>
	<?php return ob_get_clean();
}
gridc_register_shortcode( 'grid_gdpr', 'gridc_sc_gdpr' );
