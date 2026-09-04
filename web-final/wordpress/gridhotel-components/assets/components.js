/**
 * GRID Hotel Components — minimální interaktivní chování vlastní markupu
 * (GRID-SUITE-02 §11): mobilní menu [grid_header] a filtr/lightbox
 * [grid_galerie]. Idempotentní inicializace, bez jQuery, žádné síťové
 * requesty. Enqueue jen když se komponenta reálně vykreslila — viz
 * inc/assets.php.
 */
(function () {
	'use strict';
	if (window.__gridComponentsInit) { return; }
	window.__gridComponentsInit = true;

	var cfg = window.gridComponentsConfig || {};

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); }
		else { document.addEventListener('DOMContentLoaded', fn); }
	}

	function initMobileMenu() {
		var hamburger = document.getElementById('hamburger');
		var menu = document.getElementById('mobileMenu');
		var close = document.getElementById('mmClose');
		if (!hamburger || !menu) { return; }

		var open = function () {
			menu.classList.add('is-open');
			hamburger.setAttribute('aria-expanded', 'true');
		};
		var shut = function () {
			menu.classList.remove('is-open');
			hamburger.setAttribute('aria-expanded', 'false');
		};

		hamburger.addEventListener('click', function () {
			var expanded = hamburger.getAttribute('aria-expanded') === 'true';
			if (expanded) { shut(); } else { open(); }
		});
		if (close) { close.addEventListener('click', shut); }
		menu.addEventListener('click', function (e) {
			if (e.target.tagName === 'A') { shut(); }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && menu.classList.contains('is-open')) { shut(); }
		});
	}

	function initGalleryFilter() {
		var buttons = document.querySelectorAll('.gal-fbtn');
		var items = document.querySelectorAll('.gal-item');
		if (!buttons.length || !items.length) { return; }

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var filter = btn.getAttribute('data-filter');
				buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
				items.forEach(function (it) {
					var show = filter === 'all' || it.getAttribute('data-cat') === filter;
					it.style.display = show ? '' : 'none';
				});
			});
		});
	}

	ready(function () {
		if (cfg.mobileMenu !== false) { initMobileMenu(); }
		if (cfg.gallery !== false) { initGalleryFilter(); }
	});
})();
