/* Sazba dlouhých jídelníčků do sloupců (stálá nabídka, večerní menu, nápoje).
 *
 * CSS samo zvládne rozdělit menu na 4 / 3 / 2 / 1 sloupec a vyrovnat je na
 * stejnou výšku. Dvě věci ale musí dopočítat skript:
 *
 * 1) Počet sloupců podle množství položek — na sloupec se počítá pět položek,
 *    zbytek přeteče do dalšího. Nabídka do pěti položek tak zůstane v jednom
 *    sloupci, delší se dělí až do počtu, který dovolí šířka okna.
 *
 * 2) Zarovnání vrchu sloupců. Sloupec, který začíná klíčem skupiny, má první
 *    položku o výšku klíče níž než sloupec, který navazuje položkou. Sloupcům
 *    bez klíče se proto doplní odsazení, aby položky začínaly ve stejné výšce.
 *
 * 3) Osamocený klíč. Když se zlom trefí přesně za klíč skupiny, zůstane klíč
 *    viset na konci sloupce a jeho položky pokračují až ve vedlejším. CSS na to
 *    má `break-after:avoid`, jenže ve sloupcové sazbě ho prohlížeče spolehlivě
 *    nedodržují — klíč se proto přesune na začátek dalšího sloupce ručně.
 *
 * Bez JavaScriptu zůstane sazba na CSS: sloupce podle šířky okna, jen bez
 * pravidla o pěti položkách a bez dorovnání vrchu.
 */
(function () {
  var MIN_POLOZEK = 5;
  var menus = document.querySelectorAll('.menu-stala');
  if (!menus.length) { return; }

  function maxSloupcu() {
    var w = window.innerWidth;
    if (w >= 1280) { return 4; }
    if (w >= 960)  { return 3; }
    if (w >= 641)  { return 2; }
    return 1;
  }

  /* Kde začíná text první položky ve sloupci (měří se text, ne rámeček položky —
     ten má vlastní odsazení a o to by se výpočet minul). */
  function vrchTextu(polozka) {
    var text = polozka.querySelector('dt') || polozka;
    return text.getBoundingClientRect().top;
  }

  /* První prvek v každém sloupci (podle vodorovné pozice a nejmenšího odsazení). */
  function zacatkySloupcu(menu) {
    var prvky = menu.querySelectorAll('.menu-grp-l, .menu-item');
    var sloupce = {};
    Array.prototype.forEach.call(prvky, function (el) {
      var r = el.getBoundingClientRect();
      var x = Math.round(r.left);
      if (!sloupce[x] || r.top < sloupce[x].top) { sloupce[x] = { top: r.top, el: el }; }
    });
    return Object.keys(sloupce)
      .sort(function (a, b) { return a - b; })
      .map(function (x) { return sloupce[x].el; });
  }

  function uprav(menu) {
    var polozky = menu.querySelectorAll('.menu-item');
    if (!polozky.length) { return; }

    Array.prototype.forEach.call(menu.querySelectorAll('[data-odsunuty]'), function (el) {
      el.style.breakBefore = '';
      el.removeAttribute('data-odsunuty');
    });

    var pocet = Math.ceil(polozky.length / MIN_POLOZEK);
    menu.style.columnCount = String(Math.max(1, Math.min(maxSloupcu(), pocet)));

    /* Dorovnání vrchu a odsun osamocených klíčů. Obojí mění rozvržení, takže se
       výsledek přepočítá — po pár průchodech se ustálí; strop drží běh konečný. */
    var predchozi = '';
    for (var pokus = 0; pokus < 5; pokus++) {
      Array.prototype.forEach.call(menu.querySelectorAll('[data-zacatek]'), function (el) {
        el.style.paddingTop = '';
        el.removeAttribute('data-zacatek');
      });

      /* Klíč, jehož první položka skončila v jiném sloupci, patří k ní. */
      var osamocene = 0;
      Array.prototype.forEach.call(menu.querySelectorAll('.menu-grp'), function (grp) {
        var klic = grp.querySelector('.menu-grp-l');
        var prvni = grp.querySelector('.menu-item');
        if (!klic || !prvni) { return; }
        if (Math.round(klic.getBoundingClientRect().left) !== Math.round(prvni.getBoundingClientRect().left)) {
          klic.style.breakBefore = 'column';
          klic.setAttribute('data-odsunuty', '1');
          osamocene++;
        }
      });

      var zacatky = zacatkySloupcu(menu);

      /* Referenční je sloupec, který začíná klíčem: tam je první položka
         přirozeně o výšku klíče níž a na tuhle úroveň se srovnají ostatní.
         Když klíčem nezačíná žádný sloupec, není se k čemu vztahovat. */
      var mr = menu.getBoundingClientRect();
      var referencni = null;
      zacatky.forEach(function (el) {
        if (referencni !== null || !el.classList.contains('menu-grp-l')) { return; }
        var grp = el.closest('.menu-grp');
        var prvni = grp && grp.querySelector('.menu-item');
        if (prvni) { referencni = vrchTextu(prvni) - mr.top; }
      });

      /* Odsazení se přidává v každém průchodu, i v tom posledním — kdyby se
         cyklus ukončil hned po porovnání, zůstaly by sloupce vynulované.
         Musí to být padding, ne margin: horní margin na začátku sloupce
         prohlížeč zahazuje (totéž pravidlo, které jinde mezeru odstraňuje). */
      if (referencni !== null) {
        zacatky.forEach(function (el) {
          if (el.classList.contains('menu-grp-l')) { return; }
          var rozdil = referencni - ( vrchTextu(el) - mr.top );
          if (rozdil <= 0.5) { return; }
          var stav = parseFloat(getComputedStyle(el).paddingTop) || 0;
          el.style.paddingTop = ( stav + rozdil ) + 'px';
          el.setAttribute('data-zacatek', '1');
        });
      }

      /* Přidané odsazení posune obsah, takže se položka, které jsme ho dali,
         může ze začátku sloupce vysunout — a odsazení by pak zůstalo viset
         uprostřed sloupce jako prázdné místo. Po aplikaci se proto rozvržení
         přeměří a takové odsazení se zase odebere. */
      var poAplikaci = zacatkySloupcu(menu);
      Array.prototype.forEach.call(menu.querySelectorAll('[data-zacatek]'), function (el) {
        if (poAplikaci.indexOf(el) === -1) {
          el.style.paddingTop = '';
          el.removeAttribute('data-zacatek');
        }
      });

      var podpis = osamocene + '#' + poAplikaci.map(function (el) {
        return (el.className || '') + ':' + el.textContent.slice(0, 12);
      }).join('|');
      if (podpis === predchozi) { break; }
      predchozi = podpis;
    }
  }

  function vse() { Array.prototype.forEach.call(menus, uprav); }

  vse();
  /* Písma dorazí až po prvním vykreslení a posunou výšky. */
  if (document.fonts && document.fonts.ready) { document.fonts.ready.then(vse); }

  var casovac = null;
  window.addEventListener('resize', function () {
    window.clearTimeout(casovac);
    casovac = window.setTimeout(vse, 150);
  });
})();
