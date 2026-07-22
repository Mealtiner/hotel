/* GRID Hotel — front-end (Divi 5 child)
   Vše s null-guardy: funguje na homepage i na dílčích stránkách. */
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

  /* ---- Jazykový přepínač: odkazy na překlad AKTUÁLNÍ stránky (Polylang) ---- */
  (function(){
    var map = window.gridLangUrls || null; if(!map) return;
    var codes = { 'CZ':'cs', 'EN':'en', 'DE':'de' };
    document.querySelectorAll('.lang a').forEach(function(a){
      var l = codes[(a.textContent||'').trim().toUpperCase()];
      if(l && map[l]) a.setAttribute('href', map[l]);
    });
  })();

  /* ---- Fixní prvky přesunout přímo do <body> ----
     Divi obaluje obsah prvkem s transform/filter, což mění chování position:fixed
     (prvek se „ukotví" ke kontejneru, ne k oknu). Přesunem to napravíme. */
  function toBody(el){ if(el && el.parentNode !== document.body){ document.body.appendChild(el); } }
  toBody($('hud'));
  toBody($('hudReopen'));
  toBody(document.querySelector('.track-progress'));

  /* ---- Sticky header ---- */
  var header = $('topbar');
  if(header){
    var onScroll = function(){ header.classList.toggle('scrolled', window.scrollY > 40); };
    window.addEventListener('scroll', onScroll, {passive:true}); onScroll();
  }

  /* ---- Mobile menu ---- */
  var ham = $('hamburger'), mm = $('mobileMenu'), mmClose = $('mmClose');
  if(ham && mm){
    ham.addEventListener('click', function(){ mm.classList.add('open'); ham.setAttribute('aria-expanded','true'); });
    if(mmClose) mmClose.addEventListener('click', function(){ mm.classList.remove('open'); ham.setAttribute('aria-expanded','false'); });
    mm.querySelectorAll('a').forEach(function(a){ a.addEventListener('click', function(){ mm.classList.remove('open'); ham.setAttribute('aria-expanded','false'); }); });
  }

  /* ---- Hero parallax ---- */
  var heroBg = $('heroBg');
  if(heroBg){
    window.addEventListener('scroll', function(){
      var y = window.scrollY;
      if(y < window.innerHeight){ heroBg.style.transform = 'translateY(' + (y * 0.28) + 'px) scale(1.06)'; }
    }, {passive:true});
  }

  /* ---- Hero track line draw (parametry volitelně z GARRY pluginu window.gridHeroCurve) ---- */
  var line = $('trackLine');
  var hc = window.gridHeroCurve || {};
  if(hc.enabled === false){
    var ht = document.querySelector('.hero-track'); if(ht) ht.style.display = 'none';
  } else if(line && line.getTotalLength){
    var len = line.getTotalLength();
    if(hc.thickness) line.style.strokeWidth = hc.thickness;
    var dur = (typeof hc.speed === 'number' && hc.speed > 0) ? hc.speed : 2.6;
    line.style.strokeDasharray = len; line.style.strokeDashoffset = len;
    line.style.transition = 'stroke-dashoffset ' + dur + 's cubic-bezier(.16,1,.3,1)';
    requestAnimationFrame(function(){ requestAnimationFrame(function(){ line.style.strokeDashoffset = 0; }); });
  }

  /* ---- Scroll reveal ---- */
  /* V Divi Visual Builderu (nebo bez IntersectionObserveru) nic neskrýváme —
     obsah se zobrazí rovnou, jinak by sekce v editoru vypadaly prázdné. */
  var isBuilder = /[?&]et_fb=1/.test(location.search)
    || document.body.classList.contains('et-fb')
    || document.body.classList.contains('et-bfb')
    || document.documentElement.classList.contains('et-fb-preview');
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

  /* ---- Track progress: scroll-spy + click ---- */
  var points = Array.prototype.slice.call(document.querySelectorAll('.tp-point'));
  var fill = $('tpFill');
  if(points.length){
    var sections = points.map(function(p){ return $(p.dataset.target); });
    points.forEach(function(p){
      p.addEventListener('click', function(){ var t = $(p.dataset.target); if(t){ t.scrollIntoView({behavior:'smooth', block:'start'}); } });
    });
    if('IntersectionObserver' in window){
      var spyObs = new IntersectionObserver(function(entries){
        entries.forEach(function(e){ if(e.isIntersecting){ var id = e.target.id; points.forEach(function(p){ p.classList.toggle('active', p.dataset.target === id); }); } });
      }, {rootMargin:'-45% 0px -50% 0px', threshold:0});
      sections.forEach(function(s){ if(s) spyObs.observe(s); });
    }
    if(fill){
      var updateFill = function(){
        var max = document.documentElement.scrollHeight - window.innerHeight;
        var ratio = max > 0 ? (window.scrollY / max) : 0;
        fill.style.height = (Math.min(1, Math.max(0, ratio)) * 100) + '%';
      };
      window.addEventListener('scroll', updateFill, {passive:true});
      window.addEventListener('resize', updateFill); updateFill();
    }

    /* ---- Dynamický kontrast: body/label nad světlou sekcí dostanou tmavé barvy ---- */
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

  /* ---- Live telemetry widget: clock + weather ---- */
  var clockEl = $('hudClock');
  if(clockEl){
    var tick = function(){ clockEl.textContent = new Date().toLocaleTimeString('cs-CZ', {hour:'2-digit',minute:'2-digit',second:'2-digit'}); };
    tick(); setInterval(tick, 1000);
  }
  var hud = $('hud'), tempEl = $('hudTemp'), surfEl = $('hudSurface');
  if(hud && tempEl){
    var lat = hud.getAttribute('data-lat') || '49.20';
    var lon = hud.getAttribute('data-lon') || '16.44';
    try {
      fetch('https://api.open-meteo.com/v1/forecast?latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lon) + '&current=temperature_2m,precipitation')
        .then(function(r){ return r.json(); })
        .then(function(j){
          if(j && j.current){
            if(typeof j.current.temperature_2m === 'number') tempEl.textContent = Math.round(j.current.temperature_2m) + ' °C';
            if(surfEl && typeof j.current.precipitation === 'number') surfEl.textContent = j.current.precipitation > 0 ? 'MOKRO' : 'SUCHO';
          }
        }).catch(function(){});
    } catch(e){}
  }

  /* ---- HUD show/hide ---- */
  var hudX = $('hudX'), hudReopen = $('hudReopen');
  if(hud && hudX && hudReopen){
    hudX.addEventListener('click', function(){ hud.classList.add('hidden'); hudReopen.classList.add('show'); });
    hudReopen.addEventListener('click', function(){ hud.classList.remove('hidden'); hudReopen.classList.remove('show'); });
  }

  /* ---- Season 2026: event select -> waitlist form ---- */
  var evRows = document.querySelectorAll('.ev-row');
  var wbEv = $('wb-ev'), wbTitle = $('wbTitle'), wbSub = $('wbSub'), wbBtn = $('wbBtn'), wbForm = $('wbForm');
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

/* ---- Jednoduchý lightbox pro galerii detailu pokoje ---- */
(function(){
  var links = document.querySelectorAll('[data-lightbox]');
  if(!links.length) return;
  var box = document.createElement('div'); box.className='grid-lightbox';
  box.innerHTML = '<button class="glb-close" aria-label="Zavřít">&times;</button><img alt="">';
  document.body.appendChild(box);
  var img = box.querySelector('img');
  function open(src){ img.src=src; box.classList.add('open'); }
  function close(){ box.classList.remove('open'); img.src=''; }
  links.forEach(function(a){ a.addEventListener('click', function(e){ e.preventDefault(); open(a.getAttribute('href')); }); });
  box.addEventListener('click', function(e){ if(e.target===box || e.target.classList.contains('glb-close')) close(); });
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') close(); });
})();

/* ---- Galerie: filtr kategorií ---- */
(function(){
  var btns = document.querySelectorAll('.gal-fbtn'); if(!btns.length) return;
  var items = document.querySelectorAll('.gal-item');
  btns.forEach(function(b){ b.addEventListener('click', function(){
    btns.forEach(function(x){ x.classList.remove('active'); }); b.classList.add('active');
    var f = b.getAttribute('data-filter');
    items.forEach(function(it){ it.classList.toggle('hide', f !== 'all' && it.getAttribute('data-cat') !== f); });
  }); });
})();
