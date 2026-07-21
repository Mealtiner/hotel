"""Slug mapy a metadata jazykových mutací (EN/DE zčásti dle starého webu)."""

# page-key (název souboru z tools/out) -> (CZ post ID, {lang: (slug, title)})
PAGES = {
    "page-domov":          (99,  {"en": ("home-en",                                    "GRID HOTEL"),
                                  "de": ("startseite",                                 "GRID HOTEL")}),
    "page-pokoje":         (220, {"en": ("accommodation",                              "Accommodation"),
                                  "de": ("unterkunft",                                 "Unterkunft")}),
    "page-zazitky":        (337, {"en": ("experiences",                                "Experiences"),
                                  "de": ("erlebnisse",                                 "Erlebnisse")}),
    "page-gastronomie":    (226, {"en": ("gastronomy",                                 "Gastronomy"),
                                  "de": ("verpflegung",                                "Gastronomie")}),
    "page-sezona-2026":    (229, {"en": ("season-2026",                                "Season 2026"),
                                  "de": ("saison-2026",                                "Saison 2026")}),
    "page-firemni-svatby": (232, {"en": ("corporate-events-weddings",                  "Corporate events & weddings"),
                                  "de": ("firmenevents-hochzeiten",                    "Firmenevents & Hochzeiten")}),
    "page-kontakt":        (235, {"en": ("contact",                                    "Contact"),
                                  "de": ("kontakt-de",                                 "Kontakt")}),
    "page-doprava":        (238, {"en": ("getting-here",                               "Getting here"),
                                  "de": ("wegbeschreibung",                            "Anreise")}),
    "page-dotaznik":       (241, {"en": ("satisfaction-questionnaire",                 "Satisfaction questionnaire"),
                                  "de": ("zufriedenheitsfragebogen",                   "Zufriedenheitsfragebogen")}),
    "page-podminky":       (293, {"en": ("terms-and-conditions",                       "Terms and Conditions"),
                                  "de": ("allgemeine-geschaeftsbedingungen",           "Allgemeine Geschäftsbedingungen")}),
    "page-ochrana-udaju":  (258, {"en": ("statement-for-processing-of-personal-data",  "Statement on Personal Data Processing"),
                                  "de": ("erklaerung-zur-verarbeitung-von-personenbezogenen-daten", "Erklärung zur Verarbeitung personenbezogener Daten")}),
    "page-o-nas":          (261, {"en": ("about-the-hotel",                            "About the hotel"),
                                  "de": ("ueber-uns",                                  "Über uns")}),
    "page-kariera":        (264, {"en": ("career",                                     "Career"),
                                  "de": ("karriere",                                   "Karriere")}),
    "page-video":          (270, {"en": ("time-lapse-video",                           "Time-lapse video"),
                                  "de": ("video-bau-des-hotels",                       "Zeitraffer-Video")}),
    "page-rezervace":      (324, {"en": ("reservation",                                "Reservation"),
                                  "de": ("reservierung",                               "Reservierung")}),
    "page-cookies":        (244, {"en": ("cookie-policy",                              "Cookie policy"),
                                  "de": ("cookie-richtlinie",                          "Cookie-Richtlinie")}),
    "page-disclaimer":     (247, {"en": ("disclaimer-en",                              "Disclaimer"),
                                  "de": ("haftungsausschluss",                         "Haftungsausschluss")}),
    "page-privacy-statement": (250, {"en": ("privacy-statement-en",                    "Privacy statement"),
                                     "de": ("datenschutzhinweise",                     "Datenschutzhinweise")}),
    "page-galerie":        (267, {"en": ("gallery",                                    "Gallery"),
                                  "de": ("galerie-de",                                 "Galerie")}),
}

# CZ stránky, které mají jako první modul [grid_telemetry] (widget zůstává)
TELEMETRY_PAGES = {"page-pokoje", "page-gastronomie", "page-sezona-2026", "page-firemni-svatby"}

# Galerie: shortcode s přeloženými nadpisy (obsah řídí ACF)
GALERIE_SC = {
    "en": '[grid_galerie kicker="Gallery" nadpis="GRID HOTEL photo gallery"]',
    "de": '[grid_galerie kicker="Galerie" nadpis="Fotogalerie GRID HOTEL"]',
}

def url_map(lang: str) -> dict:
    """CZ URL -> jazyková URL (aplikuje se na přeložené HTML)."""
    cz2key = {
        "ubytovani": "page-pokoje", "zazitky": "page-zazitky", "gastronomie": "page-gastronomie",
        "sezona-2026": "page-sezona-2026", "firemni-akce-svatby": "page-firemni-svatby",
        "kontakt": "page-kontakt", "jak-se-k-nam-dostanete": "page-doprava",
        "dotaznik-spokojenosti": "page-dotaznik", "ubytovaci-a-reklamacni-rad": "page-podminky",
        "ochrana-osobnich-udaju-gdpr": "page-ochrana-udaju", "o-nas": "page-o-nas",
        "kariera": "page-kariera", "casosber-video-stavby": "page-video",
        "rezervace": "page-rezervace", "cookies": "page-cookies", "disclaimer": "page-disclaimer",
        "privacy-statement": "page-privacy-statement", "galerie": "page-galerie",
    }
    m = {}
    for cz, key in cz2key.items():
        slug = PAGES[key][1][lang][0]
        m[f'href="/{cz}/"'] = f'href="/{lang}/{slug}/"'
    return m
