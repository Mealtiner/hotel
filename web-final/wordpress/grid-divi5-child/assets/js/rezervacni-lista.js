/* Rezervační lišta pod hero — kalendář místo nativního výběru data.
 *
 * Pole příjezdu/odjezdu jsou v obsahu stránky jako <input type="date">. Nativní
 * kalendář kreslí operační systém a nejde obarvit, takže se v tmavé liště tvářil
 * cize. Přebíráme je flatpickrem, který je stejný jako v poptávkových
 * formulářích a řídí se stejnými brandovými barvami.
 *
 * Bez JavaScriptu zůstane pole nativní a plně funkční — typ se přepíná až tady.
 */
(function () {
  var data = window.gridKalendar || {};
  var pole = document.querySelectorAll('#bk-in, #bk-out');
  if (!pole.length || typeof flatpickr === 'undefined') { return; }

  if (data.i18n) { flatpickr.localize(data.i18n); }

  Array.prototype.forEach.call(pole, function (el) {
    /* Původní hodnota je v ISO tvaru (2026-08-07). Předat ji flatpickru jako
       řetězec nejde — parsoval by ji podle zobrazovacího formátu a vyšlo by
       jiné datum. Proto se převádí na Date; poledne kvůli časovým pásmům. */
    var hodnota = el.value ? new Date(el.value + 'T12:00:00') : null;
    if (hodnota && isNaN(hodnota.getTime())) { hodnota = null; }
    /* type="text" musí přijít dřív než inicializace, jinak si prohlížeč
       nechá vlastní kalendář a otevřely by se dva přes sebe. */
    el.type = 'text';
    flatpickr(el, {
      dateFormat: data.format || 'Y-m-d',
      defaultDate: hodnota,
      /* Bez tohohle by flatpickr na dotykových zařízeních ustoupil nativnímu
         poli — jenže to schová původní input i s jeho id, na které míří popisek. */
      disableMobile: true,
      monthSelectorType: 'static'
    });
  });
})();
