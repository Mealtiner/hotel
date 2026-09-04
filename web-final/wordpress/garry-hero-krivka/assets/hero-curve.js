/**
 * GARRY – Hero křivka: skutečné vykreslení/animace (GRID-SUITE-07 §5).
 * Nahrazuje dřívější závislost na child theme čtoucím window.gridHeroCurve.
 * Vanilla JS, žádná závislost na jQuery, idempotentní per-element inicializace,
 * respektuje prefers-reduced-motion, pozastaví offscreen instance.
 */
(function () {
	'use strict';
	if (window.__garryHeroCurveInit) { return; }
	window.__garryHeroCurveInit = true;

	var reduceMotionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

	function isReducedMotion() {
		return !!(reduceMotionQuery && reduceMotionQuery.matches);
	}

	function drawStatic(path) {
		path.style.strokeDasharray = 'none';
		path.style.strokeDashoffset = '0';
	}

	function animate(el) {
		if (el.dataset.garryHcDone === '1') { return; }
		var path = el.querySelector('.garry-hero-curve-path');
		if (!path || typeof path.getTotalLength !== 'function') { return; }

		if (isReducedMotion()) {
			drawStatic(path);
			el.dataset.garryHcDone = '1';
			return;
		}

		var len = path.getTotalLength();
		var speed = parseFloat(el.dataset.speed) || 2.6;
		if (speed <= 0) {
			drawStatic(path);
			el.dataset.garryHcDone = '1';
			return;
		}
		path.style.strokeDasharray = len;
		path.style.strokeDashoffset = len;
		path.style.transition = 'stroke-dashoffset ' + speed + 's cubic-bezier(.16,1,.3,1)';
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				path.style.strokeDashoffset = '0';
				el.dataset.garryHcDone = '1';
			});
		});
	}

	function init() {
		var els = document.querySelectorAll('.garry-hero-curve');
		if (!els.length) { return; }

		if ('IntersectionObserver' in window) {
			var obs = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						animate(entry.target);
						obs.unobserve(entry.target);
					}
				});
			}, { threshold: 0.1 });
			els.forEach(function (el) { obs.observe(el); });
		} else {
			els.forEach(animate);
		}

		if (reduceMotionQuery && typeof reduceMotionQuery.addEventListener === 'function') {
			reduceMotionQuery.addEventListener('change', function () {
				if (isReducedMotion()) {
					els.forEach(function (el) {
						var path = el.querySelector('.garry-hero-curve-path');
						if (path) { drawStatic(path); }
					});
				}
			});
		}
	}

	if (document.readyState !== 'loading') { init(); }
	else { document.addEventListener('DOMContentLoaded', init); }
})();
