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

		if ('IntersectionObserver' in window) {
			var obs = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) { setActive(entry.target.id); }
				});
			}, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });
			targets.forEach(function (t) { if (t) { obs.observe(t); } });
		}

		if (fill) {
			var updateFill = function () {
				var max = document.documentElement.scrollHeight - window.innerHeight;
				var ratio = max > 0 ? (window.scrollY / max) : 0;
				fill.style.height = (Math.min(1, Math.max(0, ratio)) * 100) + '%';
			};
			window.addEventListener('scroll', updateFill, { passive: true });
			window.addEventListener('resize', updateFill);
			updateFill();
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
