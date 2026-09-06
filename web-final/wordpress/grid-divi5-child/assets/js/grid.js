/* GRID Hotel — front-end (Divi 5 child), verze 3.0.0 (GRID-SUITE-09 §7).
   Vše s null-guardy: funguje na homepage i na dílčích stránkách.

   Odstraněno oproti dřívější verzi (přesunuto do vlastníků, viz komentáře
   u jednotlivých bloků níže) — NE proto, že by přestalo fungovat, ale
   protože stejnou věc už dělá plugin a dvě kopie by vytvořily duplicitní
   event listenery / duplicitní síťové requesty:
     - jazykový přepínač (window.gridLangUrls) → gridhotel-components ≥ 1.0.1
     - mobilní menu (#hamburger/#mobileMenu)   → gridhotel-components ≥ 1.0.0
     - hero křivka (#trackLine)                → GARRY Hero křivka ≥ 1.2.0
     - track progress scroll-spy/klik/fill     → GARRY Sekční navigace ≥ 1.4.0
     - živá telemetrie (hodiny/počasí fetch)    → GARRY Situace na trati (vždy vlastnila, tohle byla DUPLICITA)
     - HUD show/hide (#hudX/#hudReopen)         → GARRY Situace na trati (vždy vlastnila, tohle byla DUPLICITA)
     - galerie: filtr kategorií (.gal-fbtn)     → gridhotel-components ≥ 1.0.0
*/
(function(){
  "use strict";
  var $ = function(id){ return document.getElementById(id); };

  /* ---- Jazykové varianty hlavičky/patičky (Polylang) ----
     Šablona nese CS+EN+DE markup vedle sebe (.grid-lang-*). Neaktivní varianty
     ODSTRANÍME ještě před bindováním (duplicitní id topbar/hamburger/kontakt),
     CSS je do té doby skrývá. */
  (function(){
    var lang = (document.documentElement.lang || 'cs').slice(0,2).toLowerCase();
    var variants = document.querySelectorAll('.grid-lang');
    if(!variants.length) return;
    var hasLang = document.querySelector('.grid-lang-' + lang);
    if(!hasLang) lang = 'cs';
    variants.forEach(function(el){
      if(!el.classList.contains('grid-lang-' + lang)) el.remove();
    });
  })();

  /* ---- Detekce Divi Visual/Backend Builderu (musí být PŘED přesunem prvků níže —
     v editoru nechceme sahat do DOM, jinak builder ztrácí přehled o pozici modulů) ---- */
  var isBuilder = /[?&]et_fb=1/.test(location.search)
    || document.body.classList.contains('et-fb')
    || document.body.classList.contains('et-bfb')
    || document.documentElement.classList.contains('et-fb-preview');

  /* ---- Fixní prvky přesunout přímo do <body> ----
     Divi obaluje obsah prvkem s transform/filter, což mění chování position:fixed
     (prvek se „ukotví" ke kontejneru, ne k oknu). Přesunem to napravíme — ale JEN na
     skutečném frontendu, v builderu by to rozbilo výběr/editaci modulů.
     #hud/#hudReopen renderuje GARRY Situace na trati, .track-progress GARRY Sekční
     navigace — tenhle Divi-kompatibilní přesun je čistě vizuální oprava, funguje
     nezávisle na tom, který plugin markup vykreslil. */
  function toBody(el){ if(el && el.parentNode !== document.body){ document.body.appendChild(el); } }
  if(!isBuilder){
    toBody($('hud'));
    toBody($('hudReopen'));
    toBody(document.querySelector('.track-progress'));
  }

  /* ---- Sticky header ---- */
  var header = $('topbar');
  if(header){
    var onScroll = function(){ header.classList.toggle('scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, {passive:true}); onScroll();
  }

  /* ---- Hero parallax (čistě vizuální posun pozadí, nesouvisí s hero křivkou) ---- */
  var heroBg = $('heroBg');
  if(heroBg){
    window.addEventListener('scroll', function(){
      var y = window.scrollY;
      if(y < window.innerHeight){ heroBg.style.transform = 'translateY(' + (y * 0.28) + 'px) scale(1.06)'; }
    }, {passive:true});
  }

  /* ---- Scroll reveal ---- */
  /* V Divi Visual Builderu (nebo bez IntersectionObserveru) nic neskrýváme —
     obsah se zobrazí rovnou, jinak by sekce v editoru vypadaly prázdné. */
  var revealAll = function(){ document.querySelectorAll('.reveal').forEach(function(el){ el.classList.add('in'); }); };
  if(isBuilder || !('IntersectionObserver' in window)){
    revealAll();
  } else {
    var revealObs = new IntersectionObserver(function(entries){
      entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('in'); revealObs.unobserve(e.target); } });
    }, {threshold:0.14});
    document.querySelectorAll('.reveal').forEach(function(el){ revealObs.observe(el); });
    /* pojistka: cokoliv, co po 2 s nedostalo „in" (např. nescrollovaný iframe), zobrazíme */
    setTimeout(function(){ document.querySelectorAll('.reveal:not(.in)').forEach(function(el){ var r=el.getBoundingClientRect(); if(r.top < window.innerHeight*1.5) el.classList.add('in'); }); }, 2000);
  }

  /* ---- Track progress: dynamický kontrast nad světlou sekcí ----
     Čistě vizuální doplněk (tmavé/světlé barvy podle pozadí pod bodem) — funkční
     scroll-spy/klik/fill teď vlastní GARRY Sekční navigace (assets/section-nav.js),
     tenhle blok jen dobarvuje JEJÍ .tp-point prvky, nic nebinduje duplicitně. */
  var points = Array.prototype.slice.call(document.querySelectorAll('.tp-point'));
  if(points.length){
    var lightSecs = Array.prototype.slice.call(document.querySelectorAll('.sec-light'));
    var tpLabel = document.querySelector('.track-progress .tp-label');
    var isOverLight = function(y){
      for(var i=0;i<lightSecs.length;i++){ var r = lightSecs[i].getBoundingClientRect(); if(y >= r.top && y <= r.bottom) return true; }
      return false;
    };
    var updateContrast = function(){
      points.forEach(function(p){ var r = p.getBoundingClientRect(); p.classList.toggle('tp-on-light', isOverLight(r.top + r.height/2)); });
      if(tpLabel){ var lr = tpLabel.getBoundingClientRect(); tpLabel.classList.toggle('tp-on-light', isOverLight(lr.top + lr.height/2)); }
    };
    window.addEventListener('scroll', updateContrast, {passive:true});
    window.addEventListener('resize', updateContrast); updateContrast();
  }

  /* ---- Sezóna: event select -> waitlist form (ponecháno — no-op guard,
     pokud tahle konkrétní stará markup varianta na stránce není přítomná) ---- */
  var evRows = document.querySelectorAll('.ev-row');
  var wbEv = $('wb-ev') || document.querySelector('.waitbox select[data-name="akce"], .waitbox select[name="akce"]');
  var wbTitle = $('wbTitle'), wbSub = $('wbSub'), wbForm = $('wbForm');
  var wbBtn = $('wbBtn') || document.querySelector('.waitbox .ff-btn-submit');
  if(evRows.length && wbEv && wbTitle){
    var syncWaitbox = function(name, status){
      wbTitle.innerHTML = name;
      if(status === 'full'){
        wbSub.textContent = 'Tento termín je vyprodaný. Zapište se na čekací list — ozveme se, jakmile se pokoj uvolní.';
        if(wbBtn) wbBtn.textContent = 'Zapsat na čekací list';
      } else if(status === 'few'){
        wbSub.textContent = 'Poslední volné pokoje pro tento termín. Rezervujte co nejdřív.';
        if(wbBtn) wbBtn.textContent = 'Rezervovat pokoj';
      } else {
        wbSub.textContent = 'Pro tento termín máme volné pokoje. Rezervujte svůj výhled na trať.';
        if(wbBtn) wbBtn.textContent = 'Rezervovat pokoj';
      }
    };
    var statusOf = function(row){ var st = row.querySelector('.ev-status'); return st && st.classList.contains('full') ? 'full' : (st && st.classList.contains('few') ? 'few' : 'free'); };
    evRows.forEach(function(row){
      row.addEventListener('click', function(){
        evRows.forEach(function(r){ r.classList.remove('sel'); });
        row.classList.add('sel');
        var name = row.getAttribute('data-ev');
        for(var i=0;i<wbEv.options.length;i++){ if(wbEv.options[i].value === name){ wbEv.selectedIndex = i; break; } }
        syncWaitbox(name, statusOf(row));
        var sec = $('sezona'); if(sec) sec.scrollIntoView({behavior:'smooth', block:'start'});
      });
    });
    wbEv.addEventListener('change', function(){
      var name = wbEv.value, match = null;
      evRows.forEach(function(r){ if(r.getAttribute('data-ev') === name) match = r; });
      syncWaitbox(name, match ? statusOf(match) : 'free');
    });
    if(wbForm){ wbForm.addEventListener('submit', function(){ var ok = $('wbOk'); if(ok) ok.classList.add('show'); }); }
    /* init podle prvního vyprodaného, jinak první */
    var initRow = null;
    evRows.forEach(function(r){ if(!initRow && statusOf(r) === 'full') initRow = r; });
    if(!initRow) initRow = evRows[0];
    if(initRow){ var nm = initRow.getAttribute('data-ev'); for(var k=0;k<wbEv.options.length;k++){ if(wbEv.options[k].value===nm){ wbEv.selectedIndex=k; break; } } syncWaitbox(nm, statusOf(initRow)); }
  }

})();

/* Lightbox galerie přešel do pluginu GARRY – Foto lightbox (2026-09-06).
   Původní zdejší implementace poslouchala na stejných odkazech [data-lightbox]
   a s pluginem by se otevíraly dva lightboxy přes sebe. Vzhled se nastavuje
   v GARRY nastavení → Foto lightbox, ne tady. */

/* ---- Filtr karet podle typu (sekce „Okolí hotelu" na stránce O nás) ----
   Obecný: funguje nad libovolnou dvojicí .okoli-filtr [data-filtr] + .okoli-grid
   [data-typ]. Karty se skrývají atributem hidden, ne inline stylem — child theme
   má pro [hidden] vlastní pravidlo a Divi tak nemá co přepsat. */
(function(){
  var filtry = document.querySelectorAll('.okoli-filtr');
  if(!filtry.length) return;

  filtry.forEach(function(panel){
    var sekce = panel.closest('.sec') || document;
    var mrizka = sekce.querySelector('.okoli-grid');
    if(!mrizka) return;
    var karty = mrizka.querySelectorAll('[data-typ]');
    var tlacitka = panel.querySelectorAll('[data-filtr]');

    function uplatni(typ){
      karty.forEach(function(k){
        k.hidden = !(typ === 'vse' || k.getAttribute('data-typ') === typ);
      });
      tlacitka.forEach(function(b){
        b.setAttribute('aria-pressed', b.getAttribute('data-filtr') === typ ? 'true' : 'false');
      });
    }

    tlacitka.forEach(function(b){
      b.addEventListener('click', function(){ uplatni(b.getAttribute('data-filtr')); });
    });
  });
})();

/* ---- Rozbalování podmenu v mobilním menu ----
   Na desktopu se podmenu otevírá hoverem přes CSS; na dotykovém zařízení
   hover není, takže ho otevírá tlačítko vedle odkazu. Odkaz samotný zůstává
   proklikatelný na rodičovskou sekci. */
(function () {
  var menu = document.getElementById('mobileMenu');
  if (!menu) return;

  menu.addEventListener('click', function (e) {
    var tlacitko = e.target.closest('.ma-toggle');
    if (!tlacitko || !menu.contains(tlacitko)) return;
    e.preventDefault();
    var polozka = tlacitko.closest('.ma-item');
    if (!polozka) return;
    var otevreno = polozka.classList.toggle('je-otevrena');
    tlacitko.setAttribute('aria-expanded', otevreno ? 'true' : 'false');
  });

  /* Po zavření celého menu sbalit i podmenu, ať se příště neotevře rozevřené. */
  var zavri = document.getElementById('mmClose');
  if (zavri) {
    zavri.addEventListener('click', function () {
      menu.querySelectorAll('.ma-item.je-otevrena').forEach(function (p) {
        p.classList.remove('je-otevrena');
        var t = p.querySelector('.ma-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      });
    });
  }
})();
