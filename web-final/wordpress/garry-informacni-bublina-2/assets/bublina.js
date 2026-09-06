/**
 * GARRY – Informační bublina 2.
 *
 * Nastavení přebírá z data-atributů prvku #garry-bublina a přepisuje ho do CSS
 * custom properties. Plugin tak nevkládá do stránky žádný inline <style> ani
 * <script> a manifest může mít inline_assets false/false.
 */
(function () {
	'use strict';
	start();

	function start() {
		var box = document.getElementById('garry-bublina');
		if (!box) {
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', function () {
					if (document.getElementById('garry-bublina')) { start(); }
				});
			}
			return;
		}

		var d = box.dataset;
		var cislo = function (k, v) { var x = parseInt(d[k], 10); return isNaN(x) ? v : x; };
		var ram = box.querySelector('.gbub-ram');
		var zavrit = box.querySelector('.gbub-zavrit');
		var prekryv = box.querySelector('.gbub-prekryv');
		var bezAnimace = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var casovacAuto = null;

		/* ---------- zapamatování zavření ---------- */
		// Otisk se mění s obsahem, takže po úpravě textu se bublina ukáže znovu
		// i tomu, kdo předchozí verzi zavřel.
		var klic = 'garry-bublina-' + (d.otisk || 'x');

		function precti(uloziste) {
			try { return uloziste.getItem(klic); } catch (e) { return null; }
		}
		function zapis(uloziste, hodnota) {
			try { uloziste.setItem(klic, hodnota); } catch (e) {}
		}

		function jizZavreno() {
			if (d.cetnost === 'vzdy') { return false; }
			if (d.cetnost === 'sezeni') { return precti(window.sessionStorage) === '1'; }
			var kdy = parseInt(precti(window.localStorage), 10);
			if (!kdy) { return false; }
			var dny = cislo('cetnostDny', 7);
			return (Date.now() - kdy) < dny * 86400000;
		}

		function zapamatuj() {
			if (d.cetnost === 'sezeni') { zapis(window.sessionStorage, '1'); }
			else if (d.cetnost === 'dny') { zapis(window.localStorage, String(Date.now())); }
		}

		/* ---------- styl podle administrace ---------- */
		function nastavStyl() {
			var s = box.style;
			s.setProperty('--gbub-sirka', d.sirka || 'clamp(320px,34vw,560px)');
			s.setProperty('--gbub-sirka-mobil', d.sirkaMobil || 'min(92vw,430px)');
			s.setProperty('--gbub-podil', cislo('podilFotky', 46) + '%');
			s.setProperty('--gbub-zaobleni', cislo('zaobleni', 6) + 'px');
			s.setProperty('--gbub-odsazeni', cislo('odsazeni', 28) + 'px');
			s.setProperty('--gbub-odstup', cislo('odstup', 26) + 'px');
			s.setProperty('--gbub-odstup-mobil', cislo('odstupMobil', 14) + 'px');
			s.setProperty('--gbub-pozadi', d.barvaPozadi || '#16181B');
			s.setProperty('--gbub-nadpis', d.barvaNadpisu || '#fff');
			s.setProperty('--gbub-text', d.barvaTextu || '#D8D6D4');
			s.setProperty('--gbub-ramecek', d.barvaRamecku || 'rgba(255,255,255,.12)');
			s.setProperty('--gbub-krizek', d.barvaKrizku || '#F4F2F0');
			s.setProperty('--gbub-krizek-hover', d.barvaKrizkuHover || '#FF5A50');
			s.setProperty('--gbub-stin', d.barvaStinu || 'rgba(0,0,0,.45)');
			s.setProperty('--gbub-prekryv', d.barvaPrekryvu || 'rgba(8,9,11,.55)');
			s.setProperty('--gbub-nadpis-velikost', d.velikostNadpisu || 'clamp(20px,1.8vw,28px)');
			s.setProperty('--gbub-nadpis-velikost-mobil', d.velikostNadpisuMobil || '19px');
			s.setProperty('--gbub-text-velikost', d.velikostTextu || 'clamp(14px,1vw,16px)');
			s.setProperty('--gbub-text-velikost-mobil', d.velikostTextuMobil || '14px');
		}

		/* ---------- otevření a zavření ---------- */
		function ukaz() {
			box.hidden = false;
			requestAnimationFrame(function () { box.classList.add('je-videt'); });
			var auto = cislo('autoZavrit', 0);
			if (auto > 0) { casovacAuto = setTimeout(schovej, auto); }
		}

		function schovej() {
			if (casovacAuto) { clearTimeout(casovacAuto); casovacAuto = null; }
			box.classList.remove('je-videt');
			zapamatuj();
			var dokonci = function () {
				box.hidden = true;
				box.removeEventListener('transitionend', dokonci);
			};
			box.addEventListener('transitionend', dokonci);
			// Pojistka, kdyby přechod neproběhl (vypnuté animace, skryté okno).
			setTimeout(dokonci, 500);
		}

		if (jizZavreno()) { return; }
		nastavStyl();

		var zpozdeni = bezAnimace ? 0 : cislo('zpozdeni', 900);
		setTimeout(ukaz, zpozdeni);

		zavrit.addEventListener('click', schovej);
		if (prekryv) { prekryv.addEventListener('click', schovej); }
		document.addEventListener('keydown', function (e) {
			if (!box.hidden && e.key === 'Escape') { schovej(); }
		});
		// Kliknutí uvnitř rámu bublinu nezavírá — jinak by nešel označit text.
		if (ram) { ram.addEventListener('click', function (e) { e.stopPropagation(); }); }
	}
})();
