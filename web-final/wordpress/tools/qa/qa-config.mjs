/**
 * GRID Hotel — sdílená konfigurace QA nástrojů.
 *
 * JEDINÝ zdroj pravdy pro: seznam stránek, jejich stabilní ID, URL ve třech
 * jazycích, testovací viewporty a selektory koridoru/mřížek.
 *
 * ID stránky je stabilní a shodné s číslem specifikace v GPT/ — díky tomu
 * jde napříč nástroji, reporty i opravami mluvit o téže stránce jedním jménem.
 */

/* Web běží v aplikaci Local. Ta má vlastní router na portu 80, který ale
   startuje jen s Localem. Mapování hostitele na port stránky umožní testovat
   i tehdy, když router neběží — WordPress přitom vidí normální URL. */
export const ORIGIN = 'http://gridhotel.local';
export const SITE_PORT = 10018;
export const RESOLVER_RULE = `MAP gridhotel.local:80 127.0.0.1:${SITE_PORT}, MAP www.gridhotel.local:80 127.0.0.1:${SITE_PORT}`;

/* Závazné režimy z GRID-RESPONSIVE-02 + hraniční testy z §8.
   `mode` říká, která pravidla matice pro daný viewport platí. */
export const VIEWPORTS = [
  { w: 1600, h: 1000, mode: 'desktop',  ref: true },
  { w: 1280, h: 900,  mode: 'desktop'  },
  { w: 1279, h: 900,  mode: 'tablet-l' },
  { w: 1024, h: 768,  mode: 'tablet-l', ref: true },
  { w: 960,  h: 800,  mode: 'tablet-l' },
  { w: 959,  h: 800,  mode: 'tablet-p' },
  { w: 768,  h: 1024, mode: 'tablet-p', ref: true },
  { w: 641,  h: 900,  mode: 'tablet-p' },
  { w: 640,  h: 900,  mode: 'mobil'    },
  { w: 390,  h: 844,  mode: 'mobil',    ref: true },
  { w: 320,  h: 700,  mode: 'mobil'    },
];

/* Kontejnery, které podle GRID-RESPONSIVE-04 musí sdílet TENTÝŽ koridor.
   Když se jejich levé/pravé okraje rozejdou, je to chyba koridoru. */
export const CORRIDOR = ['.wrap', '.entries', '.reviews', '.exp', '.gastro', '.rooms', '.sp-content', '.k-grid', '.form-grid'];

/* Podmnožina, která musí mít SHODNOU levou i pravou hranu — to jsou hlavní
   obsahové kontejnery sekcí. `.sp-content` (textová půlka dělené sekce),
   `.k-grid` a `.form-grid` jsou vnořené nebo záměrně odsazené, ty se
   porovnávat nemají. */
export const CORRIDOR_ANCHORED = ['.wrap', '.entries', '.reviews', '.exp', '.gastro', '.rooms'];

/* Mřížky, u kterých se hlídá počet sloupců podle matice GRID-RESPONSIVE-02 §6. */
export const GRIDS = ['.entries', '.rooms', '.exp', '.gastro', '.split', '.reviews', '.season', '.b2b-grid', '.foot-top', '.gal-grid', '.sez-cards'];

/* Očekávaný počet sloupců podle režimu (GRID-RESPONSIVE-02 §6).
   null = matice pro daný prvek nic nepředepisuje. */
export const EXPECTED_COLS = {
  '.entries': { desktop: 4, 'tablet-l': 2, 'tablet-p': 2, mobil: 1 },  // T1 vstupy
  '.split':   { desktop: 2, 'tablet-l': 2, 'tablet-p': 1, mobil: 1 },  // T2/T7 dělená sekce
  '.rooms':   { desktop: 2, 'tablet-l': 2, 'tablet-p': 2, mobil: 1 },  // T3 pokoje
  '.exp':     { desktop: 3, 'tablet-l': 2, 'tablet-p': 2, mobil: 1 },  // T4 zážitky
  '.gastro':  { desktop: 3, 'tablet-l': 1, 'tablet-p': 1, mobil: 1 },  // T5 gastro (tablet = vodorovné řádky)
  '.season':  { desktop: 2, 'tablet-l': 1, 'tablet-p': 1, mobil: 1 },  // T6 sezóna 3:2
  '.reviews': { desktop: 3, 'tablet-l': 1, 'tablet-p': 1, mobil: 1 },  // T8 recenze
  '.foot-top':{ desktop: 4, 'tablet-l': 2, 'tablet-p': 2, mobil: 1 },  // patička
  '.gal-grid':{ desktop: 4, 'tablet-l': 2, 'tablet-p': 2, mobil: 2 },  // galerie
  '.sez-cards':{ desktop: 3, 'tablet-l': 2, 'tablet-p': 2, mobil: 1 }, // akce sezóny
};

/* Bezpečné hranice koridoru podle GRID-RESPONSIVE-02 §2.
   Tolerance ±2 px dle GRID-RESPONSIVE-04. */
export const SAFE = {
  1600: { left: 290, right: 1340 },
  1024: { left: 284, right: 764 },
  768:  { left: 245, right: 737 },
  390:  { left: 20,  right: 370 },
};

/* Pravá sekční navigace: viditelná od 960 px výš, jinak skrytá. */
export const RAIL_VISIBLE_FROM = 960;

const p = (id, spec, cs, en, de) => ({ id, spec, url: { cs, en, de } });

export const PAGES = [
  p('domov',        10, '/',                              '/en/home-en/',                                   '/de/startseite/'),
  p('o-nas',        11, '/o-nas/',                        '/en/about-the-hotel/',                           '/de/ueber-uns/'),
  p('ubytovani',    12, '/ubytovani/',                    '/en/accommodation/',                             '/de/unterkunft/'),
  p('pokoj-standard',      13, '/kategorie-pokoje/standard/',      '/en/kategorie-pokoje/standard-en/',      '/de/kategorie-pokoje/standard-de/'),
  p('pokoj-superior',      14, '/kategorie-pokoje/superior/',      '/en/kategorie-pokoje/superior-en/',      '/de/kategorie-pokoje/superior-de/'),
  p('pokoj-superior-plus', 15, '/kategorie-pokoje/superior-plus/', '/en/kategorie-pokoje/superior-plus-en/', '/de/kategorie-pokoje/superior-plus-de/'),
  p('pokoj-apartma',       16, '/kategorie-pokoje/apartma-a-apartma-plus/', '/en/kategorie-pokoje/apartma-a-apartma-plus-en/', '/de/kategorie-pokoje/apartma-a-apartma-plus-de/'),
  p('zazitky',      20, '/zazitky/',                      '/en/experiences/',                               '/de/erlebnisse/'),
  p('z-simulator',  21, '/zazitky/simulator-okruhu/',            '/en/zazitky/simulator-okruhu-en/',            '/de/zazitky/simulator-okruhu-de/'),
  p('z-motokary',   22, '/zazitky/motokary-pitbike/',            '/en/zazitky/motokary-pitbike-en/',            '/de/zazitky/motokary-pitbike-de/'),
  p('z-skola-smyku',23, '/zazitky/skola-smyku-polygon-brno/',    '/en/zazitky/skola-smyku-polygon-brno-en/',    '/de/zazitky/skola-smyku-polygon-brno-de/'),
  p('z-drift',      24, '/zazitky/drift-gangster-kurz/',         '/en/zazitky/drift-gangster-kurz-en/',         '/de/zazitky/drift-gangster-kurz-de/'),
  p('z-trestne-body',25,'/zazitky/odpocet-trestnych-bodu/',      '/en/zazitky/odpocet-trestnych-bodu-en/',      '/de/zazitky/odpocet-trestnych-bodu-de/'),
  p('poukazy',      26, '/zazitky/darkove-poukazy/',             '/en/zazitky/darkove-poukazy-en/',             '/de/zazitky/darkove-poukazy-de/'),
  p('gastronomie',  30, '/gastronomie/',                  '/en/gastronomy/',                                '/de/verpflegung/'),
  p('sezona',       31, '/sezona/',                       '/en/season/',                                    '/de/saison/'),
  p('firemni',      32, '/firemni-akce-svatby/',          '/en/corporate-events-weddings/',                 '/de/firmenevents-hochzeiten/'),
  p('kontakt',      33, '/kontakt/',                      '/en/contact/',                                   '/de/kontakt-de/'),
  p('doprava',      34, '/jak-se-k-nam-dostanete/',       '/en/getting-here/',                              '/de/wegbeschreibung/'),
  p('galerie',      35, '/galerie/',                      '/en/gallery/',                                   '/de/galerie-de/'),
  p('kariera',      36, '/kariera/',                      '/en/career/',                                    '/de/karriere/'),
  p('dotaznik',     37, '/dotaznik-spokojenosti/',        '/en/satisfaction-questionnaire/',                '/de/zufriedenheitsfragebogen/'),
  p('rezervace',    38, '/rezervace/',                    '/en/reservation/',                               '/de/reservierung/'),
  p('video',        39, '/casosber-video-stavby/',        '/en/time-lapse-video/',                          '/de/video-bau-des-hotels/'),
  p('gdpr',         40, '/ochrana-osobnich-udaju-gdpr/',  '/en/statement-for-processing-of-personal-data/', '/de/erklaerung-zur-verarbeitung-von-personenbezogenen-daten/'),
  p('cookies',      41, '/cookies/',                      '/en/cookie-policy/',                             '/de/cookie-richtlinie/'),
  p('podminky',     42, '/ubytovaci-a-reklamacni-rad/',   '/en/terms-and-conditions/',                      '/de/allgemeine-geschaeftsbedingungen/'),
  p('vop',          42, '/vseobecne-obchodni-podminky/',  null,                                             null),
  p('404',          43, '/tato-stranka-neexistuje-qa/',   '/en/this-page-does-not-exist-qa/',               '/de/diese-seite-existiert-nicht-qa/'),
];
