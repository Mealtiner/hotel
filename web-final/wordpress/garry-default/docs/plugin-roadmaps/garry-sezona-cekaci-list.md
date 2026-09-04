# GARRY Sezóna / čekací list – roadmap

## Cíl

Rozdělit sezonní karty, čekací list a voucher formulář do bezpečných znovupoužitelných komponent.

## Současný stav

- verze 2.5.0, Framework 2.3 a LocalLog;
- shortcody grid_season_events a grid_voucher_form;
- samostatný log poptávek; bez Gutenberg bloků a builder modulů.

## Roadmap

1. Oddělit renderer akcí, čekací list a voucher formulář do samostatných services.
2. Zachovat shortcody a přidat dva dynamické bloky, dva Divi 4/5 moduly a dva Elementor widgety.
3. Zavést server-side rate limiting, honeypot, CAPTCHA provider adapter a jednotnou validaci e-mailů.
4. Definovat retenci a mazání osobních údajů v logu poptávek; do Framework LocalLog nezapisovat PII.
5. Testovat odeslání, selhání mailu, consent, přístupnost, cache bypass a builder preview.

## Akceptace

Akce i oba formulářové výstupy fungují ve WP, Divi a Elementoru se stejnou ochranou proti spamu a stejnými pravidly PII.

