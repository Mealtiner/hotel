# GRID Hotel — index implementačních specifikací responzivity

## Účel

Tato sada je závazné zadání pro úpravu `gridhotel.local` a zdrojů v `/Users/mealtiner/GIT/GARRY plugin/GRID/new/`. Cílem je vizuální shoda s referenčním webem při zachování atomické editovatelnosti v Divi 5.

Pořadí autority:

1. `GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md`
2. `GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md`
3. `GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md`
4. příslušná specifikace konkrétní stránky
5. `GRID-RESPONSIVE-04-QA-A-AKCEPTACE.md`

Při rozporu platí dokument výše v seznamu. Živý web je vizuální reference, nikoli zdroj nevhodné monolitické HTML struktury.

## Povinné globální dokumenty

- [Globální pravidla](./GRID-RESPONSIVE-01-GLOBALNI-PRAVIDLA.md)
- [Breakpointy a rozměry](./GRID-RESPONSIVE-02-BREAKPOINTY-A-ROZMERY.md)
- [Sekce a komponenty](./GRID-RESPONSIVE-03-SEKCE-A-KOMPONENTY.md)
- [QA a akceptace](./GRID-RESPONSIVE-04-QA-A-AKCEPTACE.md)

## Specifikace stránek

- [Úvodní stránka](./GRID-RESPONSIVE-10-UVODNI-STRANKA.md)
- [O nás](./GRID-RESPONSIVE-11-O-NAS.md)
- [Ubytování](./GRID-RESPONSIVE-12-UBYTOVANI.md)
- [Pokoj Standard](./GRID-RESPONSIVE-13-POKOJ-STANDARD.md)
- [Pokoj Superior](./GRID-RESPONSIVE-14-POKOJ-SUPERIOR.md)
- [Pokoj Superior Plus](./GRID-RESPONSIVE-15-POKOJ-SUPERIOR-PLUS.md)
- [Apartmá](./GRID-RESPONSIVE-16-POKOJ-APARTMA.md)
- [Zážitky](./GRID-RESPONSIVE-20-ZAZITKY.md)
- [Simulátor okruhu](./GRID-RESPONSIVE-21-ZAZITEK-SIMULATOR.md)
- [Motokáry a pitbike](./GRID-RESPONSIVE-22-ZAZITEK-MOTOKARY-PITBIKE.md)
- [Škola smyku](./GRID-RESPONSIVE-23-ZAZITEK-SKOLA-SMYKU.md)
- [Drift & Gangster kurz](./GRID-RESPONSIVE-24-ZAZITEK-DRIFT.md)
- [Odpočet trestných bodů](./GRID-RESPONSIVE-25-ZAZITEK-TRESTNE-BODY.md)
- [Dárkové poukazy](./GRID-RESPONSIVE-26-DARKOVE-POUKAZY.md)
- [Gastronomie](./GRID-RESPONSIVE-30-GASTRONOMIE.md)
- [Sezóna](./GRID-RESPONSIVE-31-SEZONA.md)
- [Firemní akce a svatby](./GRID-RESPONSIVE-32-FIREMNI-AKCE-A-SVATBY.md)
- [Kontakt](./GRID-RESPONSIVE-33-KONTAKT.md)
- [Doprava](./GRID-RESPONSIVE-34-DOPRAVA.md)
- [Galerie](./GRID-RESPONSIVE-35-GALERIE.md)
- [Kariéra](./GRID-RESPONSIVE-36-KARIERA.md)
- [Dotazník spokojenosti](./GRID-RESPONSIVE-37-DOTAZNIK-SPOKOJENOSTI.md)
- [Rezervace](./GRID-RESPONSIVE-38-REZERVACE.md)
- [Časosběrné video](./GRID-RESPONSIVE-39-VIDEO.md)
- [Ochrana osobních údajů](./GRID-RESPONSIVE-40-GDPR.md)
- [Cookies](./GRID-RESPONSIVE-41-COOKIES.md)
- [Obchodní a ubytovací podmínky](./GRID-RESPONSIVE-42-PODMINKY.md)
- [404](./GRID-RESPONSIVE-43-404.md)

## Implementační pořadí

1. Opravit globální proměnné, koridory a breakpointy.
2. Opravit specificitu responzivních pravidel Divi řádků.
3. Opravit globální hlavičku, HUD, pravou navigaci a patičku.
4. Opravit sdílené sekce T1–T8.
5. Opravit formuláře, tabulky a detailové šablony.
6. Ověřit každou stránku a každý jazyk podle QA dokumentu.

## Definice dokončení

Stránka není hotová pouze tím, že „vypadá přibližně správně“. Hotová je až tehdy, když:

- nemá horizontální overflow při žádném testovacím viewportu;
- obsah respektuje overlay koridory;
- správně se přeskupí ve všech čtyřech režimech;
- CZ, EN a DE obsah nerozbije šířku ani výšku prvků;
- každý obsahový prvek zůstává editovatelný přes správný Divi 5 modul;
- neexistuje monolitický Text/Code modul obsahující celou stránku;
- dynamické pluginové komponenty zůstávají ve Frameworku 2.4 a nepřebírají odpovědnost za obecný layout stránky.

