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

		/* Otevřené menu je modální dialog přes celou obrazovku: čtečka i
		   klávesnice v něm musí zůstat, dokud ho uživatel nezavře (WCAG 2.4.3,
		   2.1.2). Bez toho Tab odchází do obsahu schovaného pod menu. */
		menu.setAttribute('role', 'dialog');
		menu.setAttribute('aria-modal', 'true');
		if (!menu.getAttribute('aria-label')) {
			menu.setAttribute('aria-label', hamburger.getAttribute('aria-label') || 'Menu');
		}

		var fokusovatelne = function () {
			return Array.prototype.filter.call(
				menu.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'),
				function (el) { return el.offsetParent !== null || el === document.activeElement; }
			);
		};

		var open = function () {
			menu.classList.add('is-open');
			hamburger.setAttribute('aria-expanded', 'true');
			document.body.classList.add('menu-otevrene');
			var prvni = fokusovatelne()[0];
			if (prvni) { prvni.focus(); }
		};
		var shut = function () {
			menu.classList.remove('is-open');
			hamburger.setAttribute('aria-expanded', 'false');
			document.body.classList.remove('menu-otevrene');
			hamburger.focus();
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
			if (!menu.classList.contains('is-open')) { return; }
			if (e.key === 'Escape') { shut(); return; }
			if (e.key !== 'Tab') { return; }
			/* past na fokus — z posledního prvku zpět na první a naopak */
			var prvky = fokusovatelne();
			if (!prvky.length) { return; }
			var prvni = prvky[0], posledni = prvky[prvky.length - 1];
			if (!menu.contains(document.activeElement)) {
				e.preventDefault();
				(e.shiftKey ? posledni : prvni).focus();
			} else if (e.shiftKey && document.activeElement === prvni) {
				e.preventDefault();
				posledni.focus();
			} else if (!e.shiftKey && document.activeElement === posledni) {
				e.preventDefault();
				prvni.focus();
			}
		});
	}

	function initGalleryFilter() {
		var buttons = document.querySelectorAll('.gal-fbtn');
		var items = document.querySelectorAll('.gal-item');
		if (!buttons.length || !items.length) { return; }

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var filter = btn.getAttribute('data-filter');
				buttons.forEach(function (b) {
					var active = b === btn;
					b.classList.toggle('active', active);
					b.setAttribute('aria-pressed', active ? 'true' : 'false');
				});
				items.forEach(function (it) {
					var show = filter === 'all' || it.getAttribute('data-cat') === filter;
					it.style.display = show ? '' : 'none';
				});
			});
		});
	}

	function initGastroMenuFilters() {
		var groups = document.querySelectorAll('.gastro-menu-filter');
		groups.forEach(function (group) {
			var root = group.closest('.grid-component--weekly-menu');
			if (!root) { return; }
			var buttons = group.querySelectorAll('[data-gastro-menu-target]');
			var panels = root.querySelectorAll('[data-gastro-menu-panel]');
			if (!buttons.length || !panels.length) { return; }
			var setActive = function (target, updateHash) {
				buttons.forEach(function (button) {
					var active = button.getAttribute('data-gastro-menu-target') === target;
					button.classList.toggle('active', active);
					button.setAttribute('aria-pressed', active ? 'true' : 'false');
				});
				panels.forEach(function (panel) {
					panel.hidden = target !== 'all' && panel.getAttribute('data-gastro-menu-panel') !== target;
				});
				if (updateHash && window.history && window.history.replaceState) {
					var hash = target === 'all' ? '#jidelnicek' : '#jidelnicek-' + target;
					window.history.replaceState(null, '', window.location.pathname + window.location.search + hash);
				}
			};
			buttons.forEach(function (button) { button.addEventListener('click', function () { setActive(button.getAttribute('data-gastro-menu-target'), true); }); });
			var match = window.location.hash.match(/^#jidelnicek-(hotel|paddock|bar)$/);
			setActive(match ? match[1] : 'all', false);
		});
	}

	ready(function () {
		if (cfg.mobileMenu !== false) { initMobileMenu(); }
		if (cfg.gallery !== false) { initGalleryFilter(); }
		if (cfg.gastroMenu !== false) { initGastroMenuFilters(); }
	});
})();
