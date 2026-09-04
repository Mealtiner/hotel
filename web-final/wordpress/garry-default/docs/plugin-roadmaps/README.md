# Roadmapy rozvoje rodiny GARRY pluginů

Tato složka obsahuje samostatné roadmapy pro každý GARRY plugin. Jsou návrhem, nikoli oprávněním k automatické úpravě zdrojového kódu či produkčního webu.

## Společný cílový kontrakt

Každý obsahový plugin má po dokončení poskytovat jednu builderově nezávislou renderovací a datovou vrstvu a nad ní jen tenké adaptéry:

1. nativní WordPress použití;
2. skutečný shortcode;
3. dynamický Gutenberg blok;
4. nativní Divi modul;
5. Elementor widget.

Divi modul znamená implementaci přes podporované Divi API, ne pouze CSS selektor. Elementor widget znamená registraci přes Elementor API. Všechny adaptéry používají stejnou sanitizaci, capability, renderování, assety a data.

Každý plugin zůstává samostatný: nesmí vyžadovat GARRY Default ani jiný GARRY plugin. Obsahuje Embedded Framework 2.3, lokální Přehled, Info, Log, ikonu a manifest. GARRY Default je bezpečnostní výjimka: nesmí vytvářet veřejný shortcode ani veřejné builder widgety se stavem zabezpečení.

## Povinná QA matice

Pro každý release ověřit čistý WordPress, deklarovanou minimální a aktuální testovanou verzi Divi 4, Divi 5 a Elementoru, editor/preview/frontend, cache a deaktivaci ostatních GARRY pluginů. Dokud nejsou čísla zapsána do manifestu a registry instalací, kompatibilita je N/O – neověřeno.

