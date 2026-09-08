/* Administrace pluginu Turistické cíle.
 *
 * Záložky, sbalitelné karty, přidávání a mazání řádků a řazení cílů.
 * Pořadí se neukládá číslem — rozhoduje pořadí karet ve formuláři, takže
 * přesunutí karty stačí a pole se odešlou ve správném sledu.
 *
 * Přetahování myší doplňují šipky ↑ ↓ v hlavičce karty: drag & drop se
 * z klávesnice ovládat nedá a řazení by jinak bylo nedostupné.
 */
(function () {
  var TAZENA = null;

  /* ---- záložky ---- */
  document.addEventListener('click', function (e) {
    var zalozka = e.target.closest('#tc-tabs .nav-tab');
    if (!zalozka) { return; }
    e.preventDefault();
    document.querySelectorAll('#tc-tabs .nav-tab').forEach(function (t) { t.classList.remove('nav-tab-active'); });
    zalozka.classList.add('nav-tab-active');
    document.querySelectorAll('.tc-tab').forEach(function (d) {
      d.style.display = d.getAttribute('data-tab') === zalozka.getAttribute('data-tab') ? '' : 'none';
    });
  });

  /* ---- souhrn v hlavičce karty drží krok s poli ---- */
  function obnovSouhrn(karta) {
    if (!karta) { return; }
    var nazev = karta.querySelector('.tc-nazev-pole');
    var stitek = karta.querySelector('.tc-stitek');
    var vzdalenost = karta.querySelector('.tc-vzdalenost');
    var zobrazit = karta.querySelector('.tc-zobrazit');
    var titulek = karta.querySelector('.tc-nazev');
    var souhrn = karta.querySelector('.tc-souhrn');
    if (titulek && nazev) { titulek.textContent = nazev.value || 'Nový cíl'; }
    if (souhrn) {
      var casti = [];
      if (vzdalenost && vzdalenost.value) { casti.push(vzdalenost.value); }
      if (stitek && stitek.selectedIndex >= 0) { casti.push(stitek.options[stitek.selectedIndex].textContent); }
      souhrn.textContent = casti.join(' · ');
    }
    var znacka = karta.querySelector('.tc-skryto');
    if (zobrazit) {
      if (!zobrazit.checked && !znacka) {
        znacka = document.createElement('span');
        znacka.className = 'tc-skryto';
        znacka.textContent = 'skrytý';
        karta.querySelector('summary').insertBefore(znacka, karta.querySelector('.tc-ovladani'));
      } else if (zobrazit.checked && znacka) {
        znacka.remove();
      }
    }
  }
  document.addEventListener('input', function (e) {
    if (e.target.closest('.tc-misto')) { obnovSouhrn(e.target.closest('.tc-misto')); }
  });
  document.addEventListener('change', function (e) {
    if (e.target.closest('.tc-misto')) { obnovSouhrn(e.target.closest('.tc-misto')); }
  });

  /* ---- přidání, mazání, posun ---- */
  function vycisti(uzel) {
    uzel.querySelectorAll('input, textarea').forEach(function (pole) {
      if (pole.type === 'checkbox') { pole.checked = true; return; }
      if (pole.type === 'hidden') { return; }
      pole.value = '';
    });
    var znacka = uzel.querySelector('.tc-skryto');
    if (znacka) { znacka.remove(); }
    return uzel;
  }

  document.addEventListener('click', function (e) {
    var pridat = e.target.closest('[data-tc-pridat]');
    if (pridat) {
      var obal = document.getElementById(pridat.getAttribute('data-tc-pridat'));
      if (!obal) { return; }
      var telo = obal.tagName === 'TABLE' ? obal.querySelector('tbody') : obal;
      var posledni = telo.lastElementChild;
      if (!posledni) { return; }
      var novy = vycisti(posledni.cloneNode(true));
      telo.appendChild(novy);
      if (novy.tagName === 'DETAILS') { novy.open = true; obnovSouhrn(novy); }
      return;
    }

    var karta = e.target.closest('.tc-misto');
    var obal2 = document.getElementById('tc-mista');
    if (karta && obal2) {
      if (e.target.closest('.tc-nahoru') && karta.previousElementSibling) {
        obal2.insertBefore(karta, karta.previousElementSibling);
        e.target.focus();
        return;
      }
      if (e.target.closest('.tc-dolu') && karta.nextElementSibling) {
        obal2.insertBefore(karta.nextElementSibling, karta);
        e.target.focus();
        return;
      }
    }

    var smazat = e.target.closest('.tc-smazat');
    if (smazat) {
      var radek = smazat.closest('.tc-misto') || smazat.closest('tr');
      var rodic = radek && radek.parentElement;
      if (radek && rodic && rodic.children.length > 1) { radek.remove(); }
    }
  });

  /* Šipky a mazání jsou uvnitř <summary>; bez tohohle by kliknutí navíc
     rozbalilo nebo sbalilo kartu. */
  document.addEventListener('click', function (e) {
    if (e.target.closest('.tc-ovladani')) { e.preventDefault(); }
  });

  /* ---- přetahování ---- */
  document.addEventListener('mousedown', function (e) {
    var uchyt = e.target.closest('.tc-uchyt');
    var karta = uchyt && uchyt.closest('.tc-misto');
    if (karta) { karta.setAttribute('draggable', 'true'); }
  });
  document.addEventListener('mouseup', function () {
    document.querySelectorAll('.tc-misto[draggable]').forEach(function (k) { k.removeAttribute('draggable'); });
  });

  document.addEventListener('dragstart', function (e) {
    TAZENA = e.target.closest('.tc-misto');
    if (!TAZENA) { return; }
    TAZENA.classList.add('tc-tazena');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', '');
  });

  document.addEventListener('dragover', function (e) {
    if (!TAZENA) { return; }
    var cil = e.target.closest('.tc-misto');
    if (!cil || cil === TAZENA) { return; }
    e.preventDefault();
    var r = cil.getBoundingClientRect();
    var pod = ( e.clientY - r.top ) > r.height / 2;
    cil.parentElement.insertBefore(TAZENA, pod ? cil.nextElementSibling : cil);
  });

  document.addEventListener('dragend', function () {
    if (TAZENA) { TAZENA.classList.remove('tc-tazena'); TAZENA.removeAttribute('draggable'); }
    TAZENA = null;
  });
})();
