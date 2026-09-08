/* Karty dárkových poukazů jako ovládací prvek objednávkového formuláře.
 *
 * Karta ukazuje cenu a typ pokoje, tedy přesně to, co se pak vybírá v poli
 * „Druh poukazu". Kliknutí na kartu tuhle volbu předvyplní a posune stránku
 * k formuláři — návštěvník nemusí párovat ceny očima.
 *
 * Karta se ve formuláři páruje podle číslic v ceně (3 750 → „…(3 750 Kč)"),
 * takže to funguje ve všech jazykových verzích bez další konfigurace.
 * Bez JavaScriptu zůstává karta obyčejným textem a formulář se vyplní ručně.
 */
(function () {
  var karty  = document.querySelectorAll('.vou-card');
  var vyberu = document.querySelector('.vou-form select[name="druh"], #poukazy select[name="druh"]');
  if (!karty.length || !vyberu) { return; }

  var cislice = function (t) { return (t || '').replace(/[^0-9]/g, ''); };

  var popisy = {
    cs: 'Vybrat tento poukaz',
    en: 'Choose this voucher',
    de: 'Diesen Gutschein wählen'
  };
  var lang = (document.documentElement.lang || 'cs').slice(0, 2).toLowerCase();
  var popis = popisy[lang] || popisy.cs;

  Array.prototype.forEach.call(karty, function (karta) {
    var cena = karta.querySelector('.vou-price');
    if (!cena) { return; }
    var hledej = cislice(cena.textContent);
    if (!hledej) { return; }

    var index = -1;
    Array.prototype.forEach.call(vyberu.options, function (opt, i) {
      if (index === -1 && cislice(opt.textContent).indexOf(hledej) !== -1) { index = i; }
    });
    if (index === -1) { return; }

    var nazev = karta.querySelector('h3, h4, h5');
    karta.setAttribute('role', 'button');
    karta.setAttribute('tabindex', '0');
    karta.setAttribute('aria-controls', vyberu.id || (vyberu.id = 'vou-druh'));
    karta.setAttribute('aria-label', popis + ' — ' + cena.textContent.trim() +
      (nazev ? ', ' + nazev.textContent.trim() : ''));

    var vyber = function () {
      vyberu.selectedIndex = index;
      vyberu.dispatchEvent(new Event('change', { bubbles: true }));
      Array.prototype.forEach.call(karty, function (k) { k.classList.remove('je-vybrana'); });
      karta.classList.add('je-vybrana');
      var formular = vyberu.closest('.vou-form') || vyberu.closest('form');
      if (formular) { formular.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
      /* Fokus patří na ovládací prvek pole — vlastní komponenta má tlačítko,
         u nativního výběru je to samotný <select>. */
      var cil = document.getElementById(vyberu.id + '-tlacitko') || vyberu;
      window.setTimeout(function () { cil.focus({ preventScroll: true }); }, 350);
    };

    karta.addEventListener('click', vyber);
    karta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); vyber(); }
    });
  });
})();
