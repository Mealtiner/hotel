/**
 * Administrace pluginu GARRY – Foto lightbox: výběr barev, výběr loga
 * z knihovny médií, skrývání nepoužitých polí a živý náhled.
 */
jQuery(function ($) {
	'use strict';

	var form = $('.gflb-form');
	if (!form.length) { return; }

	var pole = function (klic) { return form.find('[name$="[' + klic + ']"]'); };
	var hodnota = function (klic) {
		var el = pole(klic);
		if (!el.length) { return ''; }
		return el.attr('type') === 'checkbox' ? (el.is(':checked') ? 1 : 0) : el.val();
	};

	/* ---------- barvy ---------- */
	$('.gflb-barva').wpColorPicker({
		change: function () { setTimeout(prekresli, 30); },
		clear: function () { setTimeout(prekresli, 30); }
	});

	/* ---------- logo z knihovny médií ---------- */
	var ramecek = null;
	$('#gflb-vybrat-logo').on('click', function (e) {
		e.preventDefault();
		if (!ramecek) {
			ramecek = wp.media({ title: 'Vyberte logo', multiple: false, library: { type: 'image' } });
			ramecek.on('select', function () {
				var a = ramecek.state().get('selection').first().toJSON();
				$('#gflb-logo-priloha').val(a.id);
				var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
				$('#gflb-nahled-loga').html($('<img>').attr('src', url));
				prekresli();
			});
		}
		ramecek.open();
	});
	$('#gflb-zrusit-logo').on('click', function (e) {
		e.preventDefault();
		$('#gflb-logo-priloha').val(0);
		$('#gflb-nahled-loga').empty();
		prekresli();
	});

	/* ---------- skrývání nepoužitých polí ---------- */
	function prepniPole() {
		var typ = hodnota('pozadi_typ');
		$('.gflb-jen-prechod').toggle(typ !== 'rohy');
		$('.gflb-jen-uhel').toggle(typ === 'linear' || typ === 'conic');
		$('.gflb-jen-stred').toggle(typ === 'radial' || typ === 'conic');
		$('.gflb-jen-rohy').toggle(typ === 'rohy');
		var zdroj = hodnota('logo_zdroj');
		$('.gflb-jen-priloha').toggle(zdroj === 'priloha');
		$('.gflb-jen-url').toggle(zdroj === 'url');
	}

	/* ---------- pozadí (stejná logika jako gflb_pozadi_css v PHP) ---------- */
	function pozadiCss() {
		var b1 = hodnota('pozadi_barva1') || '#000';
		var b2 = hodnota('pozadi_barva2') || '#000';
		var b3 = hodnota('pozadi_barva3');
		var zlom = parseInt(hodnota('pozadi_zlom'), 10) || 34;
		var stops = b3 ? (b1 + ' 0%, ' + b3 + ' ' + zlom + '%, ' + b2 + ' 100%') : (b1 + ' 0%, ' + b2 + ' 100%');
		var uhel = parseInt(hodnota('pozadi_uhel'), 10) || 0;
		var stred = hodnota('pozadi_stred') || 'center';
		switch (hodnota('pozadi_typ')) {
			case 'solid':  return b1;
			case 'radial': return 'radial-gradient(circle at ' + stred + ', ' + stops + ')';
			case 'conic':  return 'conic-gradient(from ' + uhel + 'deg at ' + stred + ', ' + stops + ')';
			case 'rohy':
				return 'radial-gradient(circle at 100% 0%, ' + hodnota('pozadi_roh_ph') + ' 0%, transparent 62%),'
					+ 'radial-gradient(circle at 0% 0%, ' + hodnota('pozadi_roh_lh') + ' 0%, transparent 62%),'
					+ 'radial-gradient(circle at 100% 100%, ' + hodnota('pozadi_roh_pd') + ' 0%, transparent 62%),'
					+ 'radial-gradient(circle at 0% 100%, ' + hodnota('pozadi_roh_ld') + ' 0%, transparent 62%),'
					+ hodnota('pozadi_roh_ld');
			default:       return 'linear-gradient(' + uhel + 'deg, ' + stops + ')';
		}
	}

	/* ---------- živý náhled ---------- */
	var nahled = $('#gflb-nahled');
	var UKAZKA = 6, AKTIVNI = 2;

	function prekresli() {
		prepniPole();
		if (!nahled.length) { return; }

		nahled.find('.gflb-nahled-pozadi').css({
			background: pozadiCss(),
			opacity: (parseInt(hodnota('pozadi_kryti'), 10) || 0) / 100
		});

		var logo = nahled.find('.gflb-nahled-logo');
		var zdrojLoga = hodnota('logo_zdroj');
		var mameLogo = hodnota('logo_zobrazit') &&
			(zdrojLoga !== 'url' || hodnota('logo_url')) &&
			(zdrojLoga !== 'priloha' || $('#gflb-nahled-loga img').length);
		if (zdrojLoga === 'url' && hodnota('logo_url')) { logo.attr('src', hodnota('logo_url')); }
		if (zdrojLoga === 'priloha' && $('#gflb-nahled-loga img').length) {
			logo.attr('src', $('#gflb-nahled-loga img').attr('src'));
		}
		logo.toggle(!!mameLogo && !!logo.attr('src'))
			.css({ height: hodnota('logo_vyska') + 'px', opacity: (parseInt(hodnota('logo_kryti'), 10) || 0) / 100 });
		nahled.attr('data-pozice-loga', hodnota('logo_pozice'));

		var text = String(hodnota('nadpis_sablona') || '')
			.replace(/\{web\}/g, nahled.data('web') || 'Web')
			.replace(/\{galerie\}/g, 'Název galerie')
			.replace(/\{index\}/g, String(AKTIVNI + 1))
			.replace(/\{celkem\}/g, String(UKAZKA))
			.replace(/\{titulek\}|\{popisek\}|\{popis\}|\{alt\}/g, 'text');
		nahled.find('.gflb-nahled-nadpis').text(text).toggle(!!hodnota('nadpis_zobrazit')).css({
			color: hodnota('nadpis_barva'),
			fontSize: hodnota('nadpis_velikost') + 'px',
			textAlign: hodnota('nadpis_zarovnani')
		});

		nahled.find('.gflb-nahled-popisek').toggle(!!hodnota('popisek_zobrazit')).css({
			color: hodnota('popisek_barva'),
			fontSize: hodnota('popisek_velikost') + 'px'
		});

		var ukazatel = nahled.find('.gflb-nahled-ukazatel');
		ukazatel.toggle(!!hodnota('ukazatel_zobrazit'));
		ukazatel.find('.gflb-nahled-cara').css('background', hodnota('ukazatel_barva_cary'));
		ukazatel.find('.gflb-nahled-vypln').css({
			background: hodnota('ukazatel_barva_vypln'),
			width: (AKTIVNI / (UKAZKA - 1) * 100) + '%'
		});
		var ol = ukazatel.find('ol').empty();
		for (var i = 0; i < UKAZKA; i++) {
			var barvaBodu = i === AKTIVNI ? hodnota('ukazatel_barva_aktiv')
				: (i < AKTIVNI ? hodnota('ukazatel_barva_vypln') : 'transparent');
			var okraj = i === AKTIVNI ? hodnota('ukazatel_barva_aktiv')
				: (i < AKTIVNI ? hodnota('ukazatel_barva_vypln') : hodnota('ukazatel_barva_bod'));
			var li = $('<li>').append($('<span class="gflb-nahled-bod">').css({ background: barvaBodu, borderColor: okraj }));
			if (hodnota('ukazatel_cisla')) {
				li.append($('<span class="gflb-nahled-cislo">').text(i + 1).css('color',
					i === AKTIVNI ? hodnota('ukazatel_barva_aktiv') : hodnota('ukazatel_barva_cislo')));
			}
			ol.append(li);
		}
	}

	form.on('change input', 'input, select, textarea', prekresli);
	prekresli();
});
