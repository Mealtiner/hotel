# -*- coding: utf-8 -*-
"""Prohlášení o přístupnosti (CZ/EN/DE).

Obsah vychází ze skutečného auditu ze 7. 9. 2026 — uvádí jen ty výjimky,
které na webu opravdu zůstávají. Struktura odpovídá zvyklostem prohlášení
podle EN 301 549 / zákona č. 424/2023 Sb. (Evropský akt o přístupnosti).
"""

TITULEK = {
    "cs": "Prohlášení o přístupnosti",
    "en": "Accessibility statement",
    "de": "Erklärung zur Barrierefreiheit",
}
SLUG = {"cs": "prohlaseni-o-pristupnosti", "en": "accessibility-statement", "de": "erklaerung-zur-barrierefreiheit"}
KICKER = {"cs": "Přístupnost", "en": "Accessibility", "de": "Barrierefreiheit"}

SEO = {
    "cs": ("Prohlášení o přístupnosti | GRID HOTEL",
           "Jak je web GRID HOTEL přístupný podle WCAG 2.2 AA a zákona č. 424/2023 Sb., co zatím přístupné není a kam nám napsat."),
    "en": ("Accessibility statement | GRID HOTEL",
           "How accessible the GRID HOTEL website is under WCAG 2.2 AA, what is not accessible yet and where to send feedback."),
    "de": ("Erklärung zur Barrierefreiheit | GRID HOTEL",
           "Wie barrierefrei die Website des GRID HOTEL nach WCAG 2.2 AA ist, was noch nicht barrierefrei ist und wohin Sie sich wenden können."),
}

TELEFON = "+420 775 877 721"
EMAIL = "info@gridhotel.cz"
DATUM = {"cs": "7. září 2026", "en": "7 September 2026", "de": "7. September 2026"}


def telo(l):
    if l == "cs":
        return f"""
<p>Společnost <strong>GRH s.r.o.</strong>, provozovatel hotelu GRID HOTEL, se zavazuje zpřístupnit své
internetové stránky v souladu se zákonem č. 424/2023 Sb., o požadavcích na přístupnost některých
výrobků a služeb (Evropský akt o přístupnosti), a s harmonizovanou normou EN&nbsp;301&nbsp;549,
která vychází z pravidel <a href="https://www.w3.org/TR/WCAG22/" target="_blank" rel="noopener">WCAG 2.2</a> na úrovni AA.</p>

<h2>Stav souladu</h2>
<p>Tyto internetové stránky jsou <strong>částečně v souladu</strong> s uvedenými požadavky. Odchylky
a výjimky jsou uvedené níže.</p>

<h2>Nepřístupný obsah</h2>
<p>Níže uvedený obsah zatím není plně přístupný:</p>
<ul>
<li><strong>Drobné prvky v brandové červené na tmavém pozadí</strong> (odkaz „Rezervovat" u termínu sezóny,
štítky vzdálenosti, ikony) mají kontrast nižší než 4,5&nbsp;:&nbsp;1 — kritérium 1.4.3 Kontrast (minimální).
Značka hotelu pracuje s jedinou červenou a její změna by narušila jednotnou vizuální identitu; tyto prvky
proto zůstávají. Informaci, kterou nesou, vždy doprovází i text v plném kontrastu.</li>
<li><strong>Časosběrné video</strong> na stránkách O nás a Časosběr nemá titulky ani textový přepis —
kritéria 1.2.1 až 1.2.3. Video nemá mluvený komentář, zachycuje stavbu hotelu v letech 2016–2017.</li>
<li><strong>Formuláře</strong> zobrazují chybová hlášení v angličtině a po neúspěšném odeslání nepřesouvají
fokus na první chybné pole — kritéria 3.1.2, 3.3.1 a 3.3.3. Na nápravě pracujeme.</li>
<li><strong>Rezervační systém Bookolo</strong>, do kterého web předává rezervaci pobytu, je službou třetí
strany a jeho přístupnost nemůžeme přímo ovlivnit. Řešíme ji s dodavatelem. Rezervaci vždy vyřídíme
i telefonicky nebo e-mailem, viz kontakty níže.</li>
</ul>

<h2>Vypracování tohoto prohlášení</h2>
<p>Prohlášení bylo vypracováno dne {DATUM['cs']}. Použitou metodou bylo <strong>vlastní posouzení</strong>
kombinující automatizovaný test (axe-core, pravidla WCAG 2.0/2.1/2.2 úrovně A a AA) a ruční kontrolu
ovládání klávesnicí, struktury nadpisů a orientačních bodů, popisků formulářů, velikosti ovládacích prvků
a chování na šířkách 320, 390 a 1440&nbsp;px.</p>

<h2>Zpětná vazba</h2>
<p>Narazili jste na stránku nebo prvek, který se vám nepodařilo použít? Napište nám — každý podnět
prověříme a odpovíme na něj.</p>
<ul>
<li>E-mail: <a href="mailto:{EMAIL}">{EMAIL}</a></li>
<li>Telefon: <a href="tel:+420775877721">{TELEFON}</a> (recepce, nepřetržitě)</li>
<li>Adresa: GRH s.r.o., Ostrovačická 936/65, 641 00 Brno-Žebětín</li>
</ul>

<h2>Postup pro prosazování práva</h2>
<p>Pokud vám na podnět neodpovíme do 30 dnů nebo s vyřízením nebudete spokojeni, můžete se obrátit na
Českou obchodní inspekci jako orgán dozoru nad dodržováním požadavků na přístupnost služeb
(<a href="https://www.coi.cz/" target="_blank" rel="noopener">coi.cz</a>).</p>
"""
    if l == "en":
        return f"""
<p><strong>GRH s.r.o.</strong>, the operator of GRID HOTEL, is committed to making its website accessible
in line with Act No. 424/2023 Coll. on accessibility requirements for products and services (the European
Accessibility Act) and the harmonised standard EN&nbsp;301&nbsp;549, which builds on
<a href="https://www.w3.org/TR/WCAG22/" target="_blank" rel="noopener">WCAG 2.2</a> at level AA.</p>

<h2>Compliance status</h2>
<p>This website is <strong>partially compliant</strong> with those requirements. The exceptions are listed below.</p>

<h2>Non-accessible content</h2>
<p>The following content is not yet fully accessible:</p>
<ul>
<li><strong>Small elements in the brand red on dark backgrounds</strong> (the “Book” link on season dates,
distance badges, icons) have a contrast ratio below 4.5&nbsp;:&nbsp;1 — success criterion 1.4.3 Contrast
(Minimum). The hotel brand uses a single red and changing it would break the visual identity, so these
elements remain. The information they carry is always repeated in text with full contrast.</li>
<li><strong>The time-lapse video</strong> on the About and Time-lapse pages has no captions or transcript —
criteria 1.2.1 to 1.2.3. The video has no spoken commentary; it documents the construction of the hotel
in 2016–2017.</li>
<li><strong>Forms</strong> show validation messages in English and do not move focus to the first invalid
field after a failed submission — criteria 3.1.2, 3.3.1 and 3.3.3. We are working on this.</li>
<li><strong>The Bookolo booking system</strong>, to which the website hands over room reservations, is a
third-party service whose accessibility we cannot directly control. We are addressing it with the supplier.
A reservation can always be made by phone or e-mail instead — see the contacts below.</li>
</ul>

<h2>Preparation of this statement</h2>
<p>This statement was prepared on {DATUM['en']}. The method used was a <strong>self-assessment</strong>
combining automated testing (axe-core, WCAG 2.0/2.1/2.2 level A and AA rules) with manual review of
keyboard operation, heading and landmark structure, form labelling, target sizes and behaviour at
320, 390 and 1440&nbsp;px.</p>

<h2>Feedback</h2>
<p>Did you find a page or a control you could not use? Please tell us — we review every report and reply.</p>
<ul>
<li>E-mail: <a href="mailto:{EMAIL}">{EMAIL}</a></li>
<li>Phone: <a href="tel:+420775877721">{TELEFON}</a> (reception, 24/7)</li>
<li>Address: GRH s.r.o., Ostrovačická 936/65, 641 00 Brno-Žebětín, Czech Republic</li>
</ul>

<h2>Enforcement procedure</h2>
<p>If we do not reply within 30 days or you are not satisfied with the outcome, you may contact the Czech
Trade Inspection Authority, the supervisory body for accessibility requirements
(<a href="https://www.coi.cz/en/" target="_blank" rel="noopener">coi.cz</a>).</p>
"""
    return f"""
<p>Die <strong>GRH s.r.o.</strong>, Betreiberin des GRID HOTEL, ist bestrebt, ihre Website im Einklang mit
dem Gesetz Nr. 424/2023 Slg. über Barrierefreiheitsanforderungen für Produkte und Dienstleistungen
(Europäischer Rechtsakt zur Barrierefreiheit) und der harmonisierten Norm EN&nbsp;301&nbsp;549
barrierefrei zu gestalten; diese beruht auf den Regeln
<a href="https://www.w3.org/TR/WCAG22/" target="_blank" rel="noopener">WCAG 2.2</a> der Stufe AA.</p>

<h2>Stand der Vereinbarkeit</h2>
<p>Diese Website ist mit den genannten Anforderungen <strong>teilweise vereinbar</strong>. Die Ausnahmen
sind nachstehend aufgeführt.</p>

<h2>Nicht barrierefreie Inhalte</h2>
<p>Folgende Inhalte sind noch nicht vollständig barrierefrei:</p>
<ul>
<li><strong>Kleine Elemente im Markenrot auf dunklem Hintergrund</strong> (Link „Buchen" beim Saisontermin,
Entfernungsangaben, Symbole) haben ein Kontrastverhältnis unter 4,5&nbsp;:&nbsp;1 — Erfolgskriterium
1.4.3 Kontrast (Minimum). Die Marke des Hotels arbeitet mit einem einzigen Rot; eine Änderung würde das
einheitliche Erscheinungsbild zerstören, daher bleiben diese Elemente bestehen. Die Information wird
stets zusätzlich in Text mit vollem Kontrast wiedergegeben.</li>
<li><strong>Das Zeitraffer-Video</strong> auf den Seiten Über uns und Zeitraffer hat weder Untertitel noch
Transkript — Kriterien 1.2.1 bis 1.2.3. Das Video enthält keinen gesprochenen Kommentar; es zeigt den Bau
des Hotels in den Jahren 2016–2017.</li>
<li><strong>Formulare</strong> zeigen Fehlermeldungen auf Englisch und setzen den Fokus nach einer
fehlgeschlagenen Übermittlung nicht auf das erste fehlerhafte Feld — Kriterien 3.1.2, 3.3.1 und 3.3.3.
Wir arbeiten daran.</li>
<li><strong>Das Buchungssystem Bookolo</strong>, an das die Website Zimmerbuchungen übergibt, ist ein Dienst
Dritter, dessen Barrierefreiheit wir nicht unmittelbar beeinflussen können. Wir klären das mit dem
Anbieter. Eine Buchung ist jederzeit auch telefonisch oder per E-Mail möglich — siehe Kontakte unten.</li>
</ul>

<h2>Erstellung dieser Erklärung</h2>
<p>Diese Erklärung wurde am {DATUM['de']} erstellt. Angewandte Methode war eine
<strong>Selbstbewertung</strong>, die automatisierte Tests (axe-core, Regeln der WCAG 2.0/2.1/2.2 Stufen A
und AA) mit einer manuellen Prüfung von Tastaturbedienung, Überschriften- und Landmark-Struktur,
Formularbeschriftungen, Zielgrößen und Verhalten bei 320, 390 und 1440&nbsp;px verbindet.</p>

<h2>Feedback</h2>
<p>Sind Sie auf eine Seite oder ein Bedienelement gestoßen, das Sie nicht nutzen konnten? Schreiben Sie
uns — wir prüfen jede Rückmeldung und antworten darauf.</p>
<ul>
<li>E-Mail: <a href="mailto:{EMAIL}">{EMAIL}</a></li>
<li>Telefon: <a href="tel:+420775877721">{TELEFON}</a> (Rezeption, rund um die Uhr)</li>
<li>Anschrift: GRH s.r.o., Ostrovačická 936/65, 641 00 Brünn-Žebětín, Tschechien</li>
</ul>

<h2>Durchsetzungsverfahren</h2>
<p>Sollten wir nicht innerhalb von 30 Tagen antworten oder sind Sie mit der Bearbeitung nicht zufrieden,
können Sie sich an die Tschechische Handelsinspektion als Aufsichtsbehörde wenden
(<a href="https://www.coi.cz/" target="_blank" rel="noopener">coi.cz</a>).</p>
"""
