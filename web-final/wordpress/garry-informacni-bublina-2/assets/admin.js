/** Administrace Informační bubliny: barvy, výběr fotky, náhled, skrývání polí. */
jQuery(function ($) {
	'use strict';

	$('.gbub-barva').wpColorPicker({
		change: function () { setTimeout(prekresli, 30); },
		clear: function () { setTimeout(prekresli, 30); }
	});

	/* ---------- výběr fotky z knihovny médií (obsahová stránka) ---------- */
	$(document).on('click', '.gbub-vybrat', function (e) {
		e.preventDefault();
		var blok = $(this).closest('.gbub-foto');
		var ramecek = wp.media({ title: 'Vyberte fotku', multiple: false, library: { type: 'image' } });
		ramecek.on('select', function () {
			var a = ramecek.state().get('selection').first().toJSON();
			blok.find('.gbub-obrazek-id').val(a.id);
			var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
			blok.find('.gbub-nahled-obrazku').html($('<img>').attr('src', url));
		});
		ramecek.open();
	});
	$(document).on('click', '.gbub-odebrat', function (e) {
		e.preventDefault();
		var blok = $(this).closest('.gbub-foto');
		blok.find('.gbub-obrazek-id').val(0);
		blok.find('.gbub-nahled-obrazku').empty();
	});

	/* Šablona „čtverec" fotku nemá — pole na ni se schová, ať nemate. */
	function prepniFoto() {
		$('.gbub-vyber-sablony').each(function () {
			$(this).closest('.gbub-radek').find('.gbub-foto')
				.toggleClass('je-skryta', $(this).val() === 'ctverec');
		});
	}
	$(document).on('change', '.gbub-vyber-sablony', prepniFoto);
	prepniFoto();

	/* Zapnutý řádek pozná redakce na první pohled i se zavřeným detailem. */
	function prepniStav() {
		$('.gbub-zapnout input').each(function () {
			$(this).closest('.gbub-stranka').toggleClass('je-zapnuta', this.checked);
		});
	}
	$(document).on('change', '.gbub-zapnout input', prepniStav);
	prepniStav();

	/* ---------- živý náhled (stránka vzhledu) ---------- */
	var nahled = $('#gbub-nahled');
	if (!nahled.length) { return; }
	var form = $('.gbub-form');
	var hodnota = function (k) {
		var el = form.find('[name$="[' + k + ']"]');
		if (!el.length) { return ''; }
		return el.attr('type') === 'checkbox' ? (el.is(':checked') ? 1 : 0) : el.val();
	};

	var SMERY = {
		'ctverec': 'sloupec',
		'foto-vlevo': 'radek',
		'foto-vpravo': 'radek-obracene',
		'foto-dole': 'sloupec-obraceny',
		'foto-nahore': 'sloupec'
	};

	function prekresli() {
		var sablona = $('#gbub-nahled-sablona').val() || 'ctverec';
		var ram = nahled.find('.gbub-nahled-ram');
		nahled.attr('data-sablona', sablona).attr('data-smer', SMERY[sablona]);
		nahled.find('.gbub-nahled-foto').toggle(sablona !== 'ctverec');

		ram.css({
			background: hodnota('barva_pozadi'),
			borderColor: hodnota('barva_ramecku'),
			borderRadius: hodnota('zaobleni') + 'px',
			boxShadow: '0 18px 40px ' + hodnota('barva_stinu')
		});
		nahled.find('.gbub-nahled-text').css('padding', Math.round(hodnota('odsazeni') * 0.7) + 'px');
		nahled.find('.gbub-nahled-foto').css('flex-basis', hodnota('podil_fotky') + '%');
		nahled.find('.gbub-nahled-nadpis').css('color', hodnota('barva_nadpisu'));
		nahled.find('.gbub-nahled-telo').css('color', hodnota('barva_textu'));
		nahled.find('.gbub-nahled-krizek').css('color', hodnota('barva_krizku'));
		nahled.css('background', hodnota('preklryt') ? hodnota('barva_prekryvu') : 'transparent');
	}

	form.on('change input', 'input, select, textarea', prekresli);
	$('#gbub-nahled-sablona').on('change', prekresli);
	prekresli();
});
