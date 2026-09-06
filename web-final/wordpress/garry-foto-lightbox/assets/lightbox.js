/**
 * GARRY – Foto lightbox.
 *
 * Nastavení přebírá z data-atributů kontejneru #garry-lightbox a přepisuje ho
 * do CSS custom properties až při otevření. Díky tomu plugin nevkládá do
 * stránky žádný inline <style> ani <script> a manifest může mít inline_assets
 * false/false.
 */
(function () {
	'use strict';
	start();

	function start() {

		var box = document.getElementById('garry-lightbox');
		if (!box) {
			// Pojistka pro případ, že se skript vytiskne dřív než kontejner
			// (jiné pořadí háčků ve footeru, cizí optimalizační plugin).
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', function () {
					if (document.getElementById('garry-lightbox')) { start(); }
				});
			}
			return;
		}

		var d = box.dataset;
		var ano = function (k) { return d[k] === '1'; };
		var cislo = function (k, vychozi) { var v = parseFloat(d[k]); return isNaN(v) ? vychozi : v; };

		var foto = box.querySelector('.glb-foto');
		var popisek = box.querySelector('.glb-popisek');
		var nadpis = box.querySelector('.glb-nadpis');
		var logo = box.querySelector('.glb-logo');
		var vypln = box.querySelector('.glb-vypln');
		var seznamBodu = box.querySelector('.glb-body');
		var sipkaVlevo = box.querySelector('.glb-sipka--vlevo');
		var sipkaVpravo = box.querySelector('.glb-sipka--vpravo');
		var tlacitkoZavrit = box.querySelector('.glb-zavrit');

		var polozky = [];      // aktuální skupina snímků
		var index = 0;
		var puvodniOhnisko = null;
		var casovacAutoplay = null;
		var puvodniHash = '';

		/* ---------- nastavení do CSS proměnných ---------- */
		function nastavStyl() {
			var s = box.style;
			s.setProperty('--glb-pozadi', d.pozadi || '#000');
			s.setProperty('--glb-kryti', String(cislo('kryti', 0.96)));
			s.setProperty('--glb-rozostreni', cislo('rozostreni', 0) + 'px');
			s.setProperty('--glb-logo-vyska', cislo('logoVyska', 44) + 'px');
			s.setProperty('--glb-logo-kryti', String(cislo('logoKryti', 1)));
			s.setProperty('--glb-nadpis-barva', d.nadpisBarva || '#fff');
			s.setProperty('--glb-nadpis-velikost', cislo('nadpisVelikost', 13) + 'px');
			s.setProperty('--glb-nadpis-zarovnani', d.nadpisZarovnani || 'center');
			s.setProperty('--glb-popisek-barva', d.popisekBarva || '#bbb');
			s.setProperty('--glb-popisek-velikost', cislo('popisekVelikost', 14) + 'px');
			s.setProperty('--glb-u-cara', d.uCara || 'rgba(255,255,255,.28)');
			s.setProperty('--glb-u-vypln', d.uVypln || '#c20e1a');
			s.setProperty('--glb-u-bod', d.uBod || 'rgba(255,255,255,.55)');
			s.setProperty('--glb-u-aktiv', d.uAktiv || '#ff5a50');
			s.setProperty('--glb-u-cislo', d.uCislo || 'rgba(255,255,255,.55)');
			s.setProperty('--glb-sipka-barva', d.sipkyBarva || '#fff');
			s.setProperty('--glb-sipka-hover', d.sipkyHover || '#ff5a50');
			s.setProperty('--glb-sipka-velikost', cislo('sipkyVelikost', 44) + 'px');
			s.setProperty('--glb-zavrit-barva', d.zavritBarva || '#fff');
			s.setProperty('--glb-zavrit-hover', d.zavritHover || '#ff5a50');
			box.setAttribute('data-styl-sipek', d.sipkyStyl || 'kruh');
			box.setAttribute('data-pozice-loga', d.logoPozice || 'vlevo-nahore');
			if (logo && !ano('logo') && !d.logo) { logo.hidden = true; }
		}

		/* ---------- sběr skupiny snímků ---------- */

		/** Skupina = hodnota data-lightbox, jinak nejbližší kontejner galerie. */
		function skupinaOdkazu(a) {
			var jmeno = a.getAttribute('data-lightbox') || a.getAttribute('data-glb-skupina');
			if (jmeno) { return 'jmeno:' + jmeno; }
			var rodic = a.closest('.glb-galerie, .wp-block-gallery, .gallery, .roomgallery, figure, .et_pb_gallery');
			return rodic || document.body;
		}

		function sourozenci(a) {
			var sel = d.selektory || '[data-lightbox]';
			var skupina = skupinaOdkazu(a);
			var vsechny;
			if (typeof skupina === 'string') {
				var jmeno = skupina.slice(6);
				vsechny = document.querySelectorAll(
					'[data-lightbox="' + CSS.escape(jmeno) + '"],[data-glb-skupina="' + CSS.escape(jmeno) + '"]'
				);
			} else {
				vsechny = skupina.querySelectorAll(sel);
			}
			var ven = [];
			Array.prototype.forEach.call(vsechny, function (el) {
				if (el.tagName === 'A' && el.getAttribute('href')) { ven.push(el); }
			});
			return ven.length ? ven : [a];
		}

		/** Doprovodná informace podle nastaveného zdroje; co není v DOM, se nevymýšlí. */
		function informace(a) {
			var img = a.querySelector('img');
			switch (d.popisekZdroj) {
				case 'description':
					return a.getAttribute('data-popis') || '';
				case 'alt':
					return img ? (img.getAttribute('alt') || '') : '';
				case 'title':
					return a.getAttribute('data-titulek') || a.getAttribute('title')
						|| (img ? img.getAttribute('title') || '' : '');
				case 'caption':
				default:
					if (a.getAttribute('data-popisek')) { return a.getAttribute('data-popisek'); }
					var fig = a.closest('figure');
					var cap = fig ? fig.querySelector('figcaption') : null;
					return cap ? cap.textContent.trim() : '';
			}
		}

		/** Název galerie pro token {galerie}: aria-label, nadpis sekce, nebo titulek stránky. */
		function nazevGalerie(a) {
			var skup = a.closest('[data-glb-nazev]');
			if (skup) { return skup.getAttribute('data-glb-nazev'); }
			var sekce = a.closest('section, .et_pb_section');
			var h = sekce ? sekce.querySelector('h1, h2, h3') : null;
			if (h) { return h.textContent.trim(); }
			var h1 = document.querySelector('h1');
			return h1 ? h1.textContent.trim() : document.title;
		}

		function vyplnSablonu(a, i, celkem) {
			var img = a.querySelector('img');
			return (d.sablona || '')
				.replace(/\{web\}/g, d.web || '')
				.replace(/\{galerie\}/g, nazevGalerie(a))
				.replace(/\{index\}/g, String(i + 1))
				.replace(/\{celkem\}/g, String(celkem))
				.replace(/\{titulek\}/g, a.getAttribute('data-titulek') || '')
				.replace(/\{popis\}/g, a.getAttribute('data-popis') || '')
				.replace(/\{popisek\}/g, informace(a))
				.replace(/\{alt\}/g, img ? img.getAttribute('alt') || '' : '')
				.trim();
		}

		/* ---------- ukazatel pořadí ---------- */
		function postavUkazatel() {
			seznamBodu.innerHTML = '';
			var ukaz = box.querySelector('.glb-ukazatel');
			if (!ano('ukazatel') || polozky.length < 2) { ukaz.hidden = true; return; }
			ukaz.hidden = false;
			var max = cislo('uMax', 24);
			// Nad nastavený počet by se body slily do nečitelné řady — zůstane jen čára.
			box.classList.toggle('glb-bez-bodu', polozky.length > max);
			if (polozky.length > max) { return; }
			polozky.forEach(function (_, i) {
				var li = document.createElement('li');
				var b = document.createElement('button');
				b.type = 'button';
				b.className = 'glb-bod';
				b.setAttribute('aria-label', String(i + 1));
				b.addEventListener('click', function () { jdi(i); });
				li.appendChild(b);
				if (ano('ukazatelCisla')) {
					var num = document.createElement('span');
					num.className = 'glb-cislo';
					num.setAttribute('aria-hidden', 'true');
					num.textContent = String(i + 1);
					li.appendChild(num);
				}
				seznamBodu.appendChild(li);
			});
		}

		function obnovUkazatel() {
			var pomer = polozky.length > 1 ? index / (polozky.length - 1) : 1;
			vypln.style.width = (pomer * 100) + '%';
			Array.prototype.forEach.call(seznamBodu.children, function (li, i) {
				li.classList.toggle('je-aktivni', i === index);
				li.classList.toggle('je-hotovy', i < index);
				var b = li.querySelector('.glb-bod');
				if (b) { b.setAttribute('aria-current', i === index ? 'true' : 'false'); }
			});
		}

		/* ---------- zobrazení snímku ---------- */
		function jdi(i) {
			if (!polozky.length) { return; }
			if (ano('smycka')) {
				index = (i + polozky.length) % polozky.length;
			} else {
				index = Math.max(0, Math.min(polozky.length - 1, i));
			}
			var a = polozky[index];
			var img = a.querySelector('img');

			box.classList.add('glb-nacita');
			foto.src = a.getAttribute('href');
			foto.alt = img ? (img.getAttribute('alt') || '') : '';

			if (ano('nadpis')) {
				nadpis.textContent = vyplnSablonu(a, index, polozky.length);
				nadpis.hidden = nadpis.textContent === '';
			} else {
				nadpis.hidden = true;
			}

			if (ano('popisek')) {
				popisek.textContent = informace(a);
				popisek.hidden = popisek.textContent === '';
			} else {
				popisek.hidden = true;
			}

			if (!ano('smycka')) {
				sipkaVlevo.disabled = index === 0;
				sipkaVpravo.disabled = index === polozky.length - 1;
			}

			obnovUkazatel();
			if (ano('predlozit')) { prednacti(index + 1); prednacti(index - 1); }
			if (ano('hash')) {
				try { history.replaceState(null, '', '#foto-' + (index + 1)); } catch (e) {}
			}
			restartAutoplay();
		}

		function prednacti(i) {
			if (i < 0 || i >= polozky.length) { return; }
			var url = polozky[i].getAttribute('href');
			if (!url) { return; }
			var p = new Image();
			p.src = url;
		}

		foto.addEventListener('load', function () { box.classList.remove('glb-nacita'); });
		foto.addEventListener('error', function () { box.classList.remove('glb-nacita'); });

		/* ---------- otevření a zavření ---------- */
		function otevri(a) {
			polozky = sourozenci(a);
			index = Math.max(0, polozky.indexOf(a));
			puvodniOhnisko = document.activeElement;
			puvodniHash = location.hash;
			nastavStyl();
			postavUkazatel();
			var sipky = ano('sipky') && polozky.length > 1;
			sipkaVlevo.hidden = !sipky;
			sipkaVpravo.hidden = !sipky;
			box.hidden = false;
			document.documentElement.classList.add('glb-otevreno');
			jdi(index);
			requestAnimationFrame(function () { box.classList.add('je-otevreno'); });
			tlacitkoZavrit.focus();
		}

		function zavri() {
			box.classList.remove('je-otevreno');
			document.documentElement.classList.remove('glb-otevreno');
			stopAutoplay();
			var dokonci = function () {
				box.hidden = true;
				foto.removeAttribute('src');
				box.removeEventListener('transitionend', dokonci);
			};
			box.addEventListener('transitionend', dokonci);
			// Pojistka, kdyby přechod neproběhl (prefers-reduced-motion, skryté okno).
			setTimeout(dokonci, 400);
			if (ano('hash')) {
				try { history.replaceState(null, '', puvodniHash || location.pathname + location.search); } catch (e) {}
			}
			if (puvodniOhnisko && puvodniOhnisko.focus) { puvodniOhnisko.focus(); }
		}

		/* ---------- automatické přehrávání ---------- */
		function restartAutoplay() {
			stopAutoplay();
			if (!ano('autoplay') || polozky.length < 2) { return; }
			casovacAutoplay = setTimeout(function () { jdi(index + 1); }, cislo('autoplayMs', 5000));
		}
		function stopAutoplay() {
			if (casovacAutoplay) { clearTimeout(casovacAutoplay); casovacAutoplay = null; }
		}

		/* ---------- ovládání ---------- */
		document.addEventListener('click', function (e) {
			var a = e.target.closest ? e.target.closest('a') : null;
			if (!a) { return; }
			var sel = d.selektory || '[data-lightbox]';
			if (!a.matches(sel)) { return; }
			var href = a.getAttribute('href') || '';
			if (!/\.(jpe?g|png|gif|webp|avif|bmp|svg)(\?.*)?$/i.test(href) && !a.hasAttribute('data-lightbox')) { return; }
			e.preventDefault();
			otevri(a);
		});

		tlacitkoZavrit.addEventListener('click', zavri);
		sipkaVlevo.addEventListener('click', function () { jdi(index - 1); });
		sipkaVpravo.addEventListener('click', function () { jdi(index + 1); });
		box.addEventListener('click', function (e) {
			if (e.target === box || e.target.classList.contains('glb-pozadi') || e.target.classList.contains('glb-telo')) { zavri(); }
		});

		document.addEventListener('keydown', function (e) {
			if (box.hidden || !ano('klavesnice')) { return; }
			if (e.key === 'Escape') { zavri(); }
			else if (e.key === 'ArrowLeft') { jdi(index - 1); }
			else if (e.key === 'ArrowRight') { jdi(index + 1); }
			else if (e.key === 'Home') { jdi(0); }
			else if (e.key === 'End') { jdi(polozky.length - 1); }
			else if (e.key === 'Tab') { drzOhnisko(e); }
		});

		/** Ohnisko nesmí utéct pod otevřený dialog — jinak uživatel klávesnice skončí na skryté stránce. */
		function drzOhnisko(e) {
			var cile = Array.prototype.filter.call(
				box.querySelectorAll('button:not([hidden]):not([disabled])'),
				function (el) { return el.offsetParent !== null; }
			);
			if (!cile.length) { return; }
			var prvni = cile[0], posledni = cile[cile.length - 1];
			if (e.shiftKey && document.activeElement === prvni) { e.preventDefault(); posledni.focus(); }
			else if (!e.shiftKey && document.activeElement === posledni) { e.preventDefault(); prvni.focus(); }
		}

		box.addEventListener('wheel', function (e) {
			if (box.hidden || !ano('kolecko')) { return; }
			e.preventDefault();
			jdi(index + (e.deltaY > 0 ? 1 : -1));
		}, { passive: false });

		/* dotyková gesta */
		var zacX = 0, zacY = 0;
		box.addEventListener('touchstart', function (e) {
			if (!ano('gesta') || !e.touches.length) { return; }
			zacX = e.touches[0].clientX; zacY = e.touches[0].clientY;
		}, { passive: true });
		box.addEventListener('touchend', function (e) {
			if (!ano('gesta') || !e.changedTouches.length) { return; }
			var dx = e.changedTouches[0].clientX - zacX;
			var dy = e.changedTouches[0].clientY - zacY;
			if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) { jdi(index + (dx < 0 ? 1 : -1)); }
			else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) { zavri(); }
		}, { passive: true });
	}
})();
