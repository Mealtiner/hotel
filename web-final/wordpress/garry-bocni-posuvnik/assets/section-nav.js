/**
 * GARRY – Sekční navigace: skutečné scroll-spy/aktivní stav/smooth-scroll
 * (GRID-SUITE-08 §6). Progressive enhancement nad funkčními <a href="#id">
 * odkazy — bez JS zůstávají odkazy plně funkční (prohlížeč sám skočí na
 * kotvu). Nahrazuje dřívější závislost na child theme grid.js.
 */
(function () {
	'use strict';
	if (window.__garrySectionNavInit) { return; }
	window.__garrySectionNavInit = true;

	var reduceMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
	function prefersReducedMotion() { return !!(reduceMotionQuery && reduceMotionQuery.matches); }

	function initNav(nav) {
		if (nav.dataset.garryScrInit === '1') { return; }
		nav.dataset.garryScrInit = '1';

		var points = Array.prototype.slice.call(nav.querySelectorAll('.tp-point'));
		if (!points.length) { return; }
		var fill = nav.querySelector('.tp-fill');
		var targets = points.map(function (p) {
			var id = p.getAttribute('data-target');
			return id ? document.getElementById(id) : null;
		});

		function setActive(id) {
			points.forEach(function (p) {
				var isActive = p.getAttribute('data-target') === id;
				if (isActive) { p.setAttribute('aria-current', 'location'); p.classList.add('active'); }
				else { p.removeAttribute('aria-current'); p.classList.remove('active'); }
			});
		}

		/* Aktivní sekci určuje jediný výpočet — ten samý, ze kterého se počítá
		   výška červené čáry. Dřív puntíky řídil IntersectionObserver a čára
		   scrollY zvlášť, takže se na hranicích sekcí rozcházely o jeden bod. */
		function aktivniSekce() {
			var stred = window.scrollY + window.innerHeight / 2;
			var idx = -1, podil = 0, podilShora = 0;
			for (var i = 0; i < targets.length; i++) {
				var t = targets[i];
				if (!t) { continue; }
				/* Poloha se bere vůči dokumentu, ne přes offsetTop — ten se počítá
				   od nejbližšího umístěného předka a sekce Divi je mají různé,
				   takže výpočet ujížděl o jednu sekci. */
				var r   = t.getBoundingClientRect();
				var top = r.top + window.scrollY;
				if (stred < top) { continue; }
				idx   = i;
				podil = Math.min(1, Math.max(0, ( stred - top ) / ( r.height || 1 ) ));
				podilShora = Math.min(1, Math.max(0, ( window.scrollY - top ) / ( r.height || 1 ) ));
			}
			return { idx: idx, podil: podil, podilShora: podilShora };
		}

		if (fill) {
			/* Výplň se řídí body lišty, ne procentem sjetí stránky.
			 *
			 * Dřív se počítala jako scrollY / výška stránky, jenže body jsou na
			 * liště rozmístěné rovnoměrně, kdežto sekce mají různou výšku —
			 * čára pak ukazovala jinam než rozsvícený puntík (na titulní straně
			 * až o dva body). Teď se hledá sekce, ve které je střed obrazovky,
			 * a čára doběhne k jejímu bodu plus poměrná část k následujícímu
			 * podle toho, jak hluboko v sekci jsme. Ve vysoké sekci se proto
			 * pohybuje pomalu, v nízké rychle — odráží skutečnou délku sekce.
			 */
			var stredyBodu = [];
			var zacatekVyplne = 0;

			var zmerListu = function () {
				var navTop = nav.getBoundingClientRect().top;
				stredyBodu = points.map(function (p) {
					var dot = p.querySelector('.tp-dot') || p;
					var r = dot.getBoundingClientRect();
					return ( r.top + r.height / 2 ) - navTop;
				});
				/* Výplň začíná tam, kde začíná lišta (dáno stylem), a roste dolů.
				   Prázdná nahoře je díky tomu, že se uvnitř sekce počítá podle
				   horní hrany okna — na začátku stránky doběhne přesně k prvnímu
				   bodu a dál se nehne, dokud se neroluje. */
				zacatekVyplne = fill.getBoundingClientRect().top - navTop;
			};

			var updateFill = function () {
				if (!stredyBodu.length) { return; }
				var stav = aktivniSekce();

				if (stav.idx === -1) {
					fill.style.height = '0px';
					setActive(null);
					return;
				}
				setActive(points[stav.idx].getAttribute('data-target'));

				var od = stredyBodu[stav.idx];
				var k  = stredyBodu[stav.idx + 1] !== undefined ? stredyBodu[stav.idx + 1] : od;
				/* Uvnitř sekce se čára posouvá podle horní hrany okna, ne podle
				   jeho středu — jinak by hned po načtení stránky byla už kus za
				   prvním bodem, i když návštěvník ještě nikam neodroloval. */
				fill.style.height = Math.max(0, ( od + stav.podilShora * ( k - od ) ) - zacatekVyplne) + 'px';
			};

			var prepocitej = function () { zmerListu(); updateFill(); };
			window.addEventListener('scroll', updateFill, { passive: true });
			window.addEventListener('resize', prepocitej);
			if (document.fonts && document.fonts.ready) { document.fonts.ready.then(prepocitej); }
			prepocitej();
		}

		if (!fill) {
			var jenPuntiky = function () {
				var stav = aktivniSekce();
				setActive(stav.idx === -1 ? null : points[stav.idx].getAttribute('data-target'));
			};
			window.addEventListener('scroll', jenPuntiky, { passive: true });
			jenPuntiky();
		}

		points.forEach(function (p) {
			p.addEventListener('click', function (e) {
				var id = p.getAttribute('data-target');
				var target = id ? document.getElementById(id) : null;
				if (!target) { return; } // no JS-found target -> let the browser follow the real href normally
				e.preventDefault();
				target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
				setActive(id);
				if (history.pushState) { history.pushState(null, '', '#' + id); }
			});
		});
	}

	function init() {
		document.querySelectorAll('.track-progress').forEach(initNav);
	}

	if (document.readyState !== 'loading') { init(); }
	else { document.addEventListener('DOMContentLoaded', init); }
})();
