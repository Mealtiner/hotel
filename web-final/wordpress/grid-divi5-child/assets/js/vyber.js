/* Vlastní rozbalovací seznam (rezervační lišta + čekací list).
 *
 * Rozbalený seznam nativního <select> kreslí operační systém a CSS se k němu
 * nedostane — v tmavé liště se otevíral světlý systémový panel s modrým
 * výběrem. Nahrazujeme ho vlastní komponentou podle vzoru combobox/listbox
 * (WAI-ARIA APG): tlačítko s aktuální hodnotou + seznam voleb.
 *
 * Původní <select> zůstává v DOMu a je zdrojem pravdy — jen se vizuálně
 * schová. Bez JavaScriptu tedy funguje dál nativní pole a beze změny zůstává
 * i pro čtečky, které by si s vlastní komponentou neporadily.
 */
(function () {
  /* Kde se komponenta nasazuje: rezervační lišta pod hero a formulář čekacího
     listu v sekci Sezóna, poukazy a formuláře označené třídou grid-gsel
     (poptávka firemních akcí — viz filtr fluentform/form_class ve
     functions.php). Ostatní formuláře zůstávají nativní — Fluent Forms u nich
     umí překreslovat pole podle podmínek a vlastní nadstavba by se rozešla se
     skutečným stavem; proto se komponenta nasazuje jen tam, kde podmínky
     nejsou. */
  var vyber = document.querySelectorAll('#bk-guests, #bk-pokoj, .waitbox .fluentform select:not([multiple]), .vou-form .fluentform select:not([multiple]), form.grid-gsel select:not([multiple])');
  if (!vyber.length) { return; }

  Array.prototype.forEach.call(vyber, function (sel) {
    if (!sel.id) { sel.id = 'gsel-' + Math.random().toString(36).slice(2, 9); }
    if (sel.dataset.gridSelect) { return; }
    sel.dataset.gridSelect = '1';

    var id      = sel.id;
    var popisek = document.querySelector('label[for="' + id + '"]');
    var obal    = document.createElement('div');
    obal.className = 'gsel';

    var tlacitko = document.createElement('button');
    tlacitko.type = 'button';
    tlacitko.className = 'gsel-tlacitko';
    tlacitko.id = id + '-tlacitko';
    tlacitko.setAttribute('role', 'combobox');
    tlacitko.setAttribute('aria-expanded', 'false');
    tlacitko.setAttribute('aria-haspopup', 'listbox');
    tlacitko.setAttribute('aria-controls', id + '-seznam');
    if (popisek) { tlacitko.setAttribute('aria-labelledby', (popisek.id || (popisek.id = id + '-popisek')) + ' ' + tlacitko.id); }
    if (sel.required || sel.getAttribute('aria-required') === 'true') { tlacitko.setAttribute('aria-required', 'true'); }

    var text = document.createElement('span');
    text.className = 'gsel-hodnota';
    tlacitko.appendChild(text);
    var sipka = document.createElement('span');
    sipka.className = 'gsel-sipka';
    sipka.setAttribute('aria-hidden', 'true');
    tlacitko.appendChild(sipka);

    var seznam = document.createElement('ul');
    seznam.className = 'gsel-seznam';
    seznam.id = id + '-seznam';
    seznam.setAttribute('role', 'listbox');
    seznam.hidden = true;

    var volby = [];
    Array.prototype.forEach.call(sel.options, function (opt, i) {
      var li = document.createElement('li');
      li.className = 'gsel-volba';
      li.id = id + '-volba-' + i;
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
      li.textContent = opt.textContent;
      li.dataset.index = String(i);
      seznam.appendChild(li);
      volby.push(li);
    });

    sel.classList.add('gsel-nativni');
    sel.parentNode.insertBefore(obal, sel);
    obal.appendChild(sel);
    obal.appendChild(tlacitko);
    obal.appendChild(seznam);

    var aktivni = sel.selectedIndex < 0 ? 0 : sel.selectedIndex;

    function vykresli() {
      text.textContent = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : '';
      volby.forEach(function (li, i) { li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false'); });
    }
    function zvyrazni(i) {
      aktivni = Math.max(0, Math.min(volby.length - 1, i));
      volby.forEach(function (li, k) { li.classList.toggle('je-aktivni', k === aktivni); });
      tlacitko.setAttribute('aria-activedescendant', volby[aktivni].id);
      volby[aktivni].scrollIntoView({ block: 'nearest' });
    }
    function otevri() {
      seznam.hidden = false;
      tlacitko.setAttribute('aria-expanded', 'true');
      zvyrazni(sel.selectedIndex < 0 ? 0 : sel.selectedIndex);
    }
    function zavri(vratFokus) {
      seznam.hidden = true;
      tlacitko.setAttribute('aria-expanded', 'false');
      tlacitko.removeAttribute('aria-activedescendant');
      volby.forEach(function (li) { li.classList.remove('je-aktivni'); });
      if (vratFokus) { tlacitko.focus(); }
    }
    function potvrd(i) {
      sel.selectedIndex = i;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      vykresli();
      zavri(true);
    }

    tlacitko.addEventListener('click', function () {
      if (seznam.hidden) { otevri(); } else { zavri(true); }
    });

    tlacitko.addEventListener('keydown', function (e) {
      var otevreno = !seznam.hidden;
      switch (e.key) {
        case 'ArrowDown': e.preventDefault(); otevreno ? zvyrazni(aktivni + 1) : otevri(); break;
        case 'ArrowUp':   e.preventDefault(); otevreno ? zvyrazni(aktivni - 1) : otevri(); break;
        case 'Home':      if (otevreno) { e.preventDefault(); zvyrazni(0); } break;
        case 'End':       if (otevreno) { e.preventDefault(); zvyrazni(volby.length - 1); } break;
        case 'Enter':
        case ' ':         e.preventDefault(); otevreno ? potvrd(aktivni) : otevri(); break;
        case 'Escape':    if (otevreno) { e.preventDefault(); zavri(true); } break;
        case 'Tab':       if (otevreno) { zavri(false); } break;
        default:
          /* psaní písmene skočí na první volbu, která jím začíná */
          if (e.key.length === 1 && !e.metaKey && !e.ctrlKey && !e.altKey) {
            var hledej = e.key.toLowerCase();
            for (var k = 1; k <= volby.length; k++) {
              var idx = ((otevreno ? aktivni : sel.selectedIndex) + k) % volby.length;
              if (volby[idx].textContent.trim().toLowerCase().indexOf(hledej) === 0) {
                otevreno ? zvyrazni(idx) : potvrd(idx);
                break;
              }
            }
          }
      }
    });

    volby.forEach(function (li, i) {
      li.addEventListener('click', function () { potvrd(i); });
      li.addEventListener('mousemove', function () { zvyrazni(i); });
    });

    document.addEventListener('click', function (e) {
      if (!seznam.hidden && !obal.contains(e.target)) { zavri(false); }
    });

    sel.addEventListener('change', vykresli);
    vykresli();
  });
})();
