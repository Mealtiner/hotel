/* ==========================================================================
   GARRY – Typografie · skript administrace
   Živý náhled pro světlé i zelené pozadí, výběr barev, nahrávání souborů
   fontů, hromadné akce a test vykreslení české diakritiky.
   ========================================================================== */

(function ($) {
    'use strict';

    var data = window.garryTypoData || { fontMap: {}, recommended: {} };

    /* ------------------------------------------------------ pomocné čtení -- */

    function fieldValue(elementKey, prop) {
        var $field = $('[data-el="' + elementKey + '"][data-prop="' + prop + '"]');

        return $field.length ? $.trim(String($field.val() || '')) : '';
    }

    function isElementOn(elementKey) {
        return $('.garry-typo-element[data-element="' + elementKey + '"]')
            .find('.garry-typo-element-toggle')
            .is(':checked');
    }

    function fontStack(fontKey) {
        if (!fontKey) {
            return '';
        }

        return data.fontMap[fontKey] || '';
    }

    /* ------------------------------------------------------- živý náhled -- */

    var RESET = {
        fontFamily: '',
        fontWeight: '',
        fontSize: '',
        lineHeight: '',
        letterSpacing: '',
        textTransform: '',
        marginBottom: '',
        padding: '',
        textAlign: '',
        textDecoration: '',
        color: ''
    };

    /**
     * Sestaví styl jedné úrovně pro dané prostředí.
     * context = 'light' pro bílé pozadí, 'dark' pro zelené.
     */
    function styleFor(elementKey, context) {
        if (!isElementOn(elementKey)) {
            return null;
        }

        var colorProp = context === 'dark' ? 'color_dark' : 'color';

        // Vlastní rozpal úrovně má přednost; jinak platí společný rozpal,
        // ale jen u úrovní psaných zvoleným fontem.
        var fontKey = fieldValue(elementKey, 'font');
        var spacing = fieldValue(elementKey, 'ls');

        if (spacing === '' && fontKey !== '' && fontKey === $.trim(String($('#garry-typo-ls-global-font').val() || ''))) {
            spacing = $.trim(String($('#garry-typo-ls-global').val() || ''));
        }

        return $.extend({}, RESET, {
            fontFamily: fontStack(fieldValue(elementKey, 'font')),
            fontWeight: fieldValue(elementKey, 'weight'),
            fontSize: fieldValue(elementKey, 'size'),
            lineHeight: fieldValue(elementKey, 'lh'),
            letterSpacing: spacing,
            textTransform: fieldValue(elementKey, 'tt'),
            marginBottom: fieldValue(elementKey, 'mb'),
            padding: fieldValue(elementKey, 'pad'),
            textAlign: fieldValue(elementKey, 'align'),
            textDecoration: fieldValue(elementKey, 'deco'),
            color: fieldValue(elementKey, colorProp)
        });
    }

    function applyStyle($nodes, style) {
        $nodes.css(style || RESET);
    }

    function updatePreview() {
        $('.garry-typo-preview').each(function () {
            var $box = $(this);
            var context = $box.data('context') === 'dark' ? 'dark' : 'light';

            // Základní text nastavíme na celý rámeček, zbytek ho dědí.
            applyStyle($box, styleFor('body', context));

            $box.find('[data-preview]').each(function () {
                var $node = $(this);
                applyStyle($node, styleFor($node.data('preview'), context));
            });
        });
    }

    /* ---------------------------------------------------- stavy formuláře -- */

    function updateSwitchLabel() {
        var enabled = $('.garry-typo-switch input[type="checkbox"]').is(':checked');

        $('#garry-typo-state-label').text(
            enabled
                ? 'Plugin vypisuje na web vlastní styly písem podle nastavení níže.'
                : 'Plugin na web nic nevypisuje. Písma řídí motiv Divi a případné vlastní snippety.'
        );
    }

    function updateElementStates() {
        $('.garry-typo-element').each(function () {
            var $element = $(this);
            $element.toggleClass('is-on', $element.find('.garry-typo-element-toggle').is(':checked'));
        });
    }

    function updateFontModeBlocks() {
        var mode = $('#garry-typo-font-mode').val() || 'faces';

        $('.garry-typo-mode-block').each(function () {
            var $block = $(this);
            $block.toggle($block.data('mode') === mode);
        });
    }

    function applyRecommended() {
        var recommended = data.recommended || {};
        var props = ['font', 'weight', 'size', 'size_t', 'size_m', 'lh', 'ls', 'tt',
                     'color', 'color_dark', 'color_hover', 'color_hover_dark',
                     'mb', 'pad', 'align', 'deco'];

        $('.garry-typo-element').each(function () {
            var $element = $(this);
            var key = $element.data('element');
            var values = recommended[key];

            if (!values) {
                return;
            }

            $element.find('.garry-typo-element-toggle').prop('checked', values.on === '1');

            $.each(props, function (index, prop) {
                var $field = $element.find('[name$="[elements][' + key + '][' + prop + ']"]');

                if ($field.length) {
                    $field.val(typeof values[prop] === 'undefined' ? '' : values[prop]);
                }
            });
        });

        syncAllColorPickers();
        updateElementStates();
        updatePreview();
    }

    /* --------------------------------------------------------- barvy -- */

    function syncColorPicker($textInput) {
        var value = $.trim(String($textInput.val() || ''));
        var $picker = $textInput.closest('.garry-typo-color-row').find('.garry-typo-color-pick');

        if (/^#[0-9a-fA-F]{6}$/.test(value)) {
            $picker.val(value).css('opacity', 1);
        } else {
            $picker.css('opacity', .35);
        }
    }

    function syncAllColorPickers() {
        $('.garry-typo-color-text').each(function () {
            syncColorPicker($(this));
        });
    }

    /* ------------------------------------------------ nahrávání fontů -- */

    function openMediaPicker($targetInput) {
        if (!window.wp || !window.wp.media) {
            window.alert('Knihovna médií se nenačetla. Vložte prosím adresu souboru ručně.');
            return;
        }

        var frame = window.wp.media({
            title: 'Vyberte nebo nahrajte soubor fontu',
            button: { text: 'Použít tento soubor' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            if (attachment && attachment.url) {
                // Uložíme relativní cestu, ať přesun webu na jinou doménu nic nerozbije.
                var url = attachment.url.replace(/^https?:\/\/[^/]+/, '');
                $targetInput.val(url).trigger('change');
            }
        });

        frame.open();
    }

    function addFaceRow() {
        var $rows = $('#garry-typo-faces .garry-typo-face-row');

        if (!$rows.length) {
            return;
        }

        var $clone = $rows.last().clone();
        var index = $rows.length;

        $clone.find('input, select').each(function () {
            var $field = $(this);
            var name = $field.attr('name');

            if (name) {
                $field.attr('name', name.replace(/\[custom_font_faces\]\[\d+\]/, '[custom_font_faces][' + index + ']'));
            }

            if ($field.attr('type') === 'text') {
                $field.val('');
            }
        });

        $clone.find('.garry-typo-ok, .garry-typo-bad').remove();
        $clone.appendTo('#garry-typo-faces');
    }

    /* ------------------------------------------------- test diakritiky -- */

    var measureContext = null;

    function getContext() {
        if (measureContext === null) {
            var canvas = document.createElement('canvas');
            measureContext = canvas.getContext ? canvas.getContext('2d') : false;
        }

        return measureContext;
    }

    function measureWidth(text, stack, weight) {
        var context = getContext();

        if (!context) {
            return 0;
        }

        context.font = weight + ' 48px ' + stack;

        return context.measureText(text).width;
    }

    function firstFamily(stack) {
        return $.trim(String(stack || '').split(',')[0]);
    }

    /**
     * Porovná šířku textu vykresleného testovaným fontem se šířkou v záložních
     * fontech. Pokud se ani jednou neliší, testovaný font se na daný text
     * nepoužil a prohlížeč sáhl po náhradě.
     */
    function isRendered(text, family) {
        var baselines = ['monospace', 'serif', 'sans-serif'];
        var applied = false;

        $.each(baselines, function (index, baseline) {
            var withFont = measureWidth(text, family + ', ' + baseline, '400');
            var withoutFont = measureWidth(text, baseline, '400');

            if (Math.abs(withFont - withoutFont) > 0.5) {
                applied = true;
                return false;
            }

            return true;
        });

        return applied;
    }

    function setVerdict(state, html) {
        $('#garry-typo-test-verdict')
            .removeClass('is-ok is-warn is-bad')
            .addClass(state)
            .html(html);
    }

    function runDiacriticsTest() {
        var $select = $('#garry-typo-test-font');

        if (!$select.length) {
            return;
        }

        var family = firstFamily(fontStack($select.val()));

        if (!family) {
            setVerdict('', 'Pro tento výběr není co testovat.');
            return;
        }

        var quoted = family.charAt(0) === '"' ? family : '"' + family.replace(/"/g, '') + '"';

        // Ukázky vykreslíme jen testovaným fontem s náhradou monospace, aby
        // případné nahrazení jednotlivých znaků bylo vidět i pouhým okem.
        $('#garry-typo-test-samples .garry-typo-test-text').each(function () {
            var $node = $(this);

            $node.css({
                fontFamily: quoted + ', monospace',
                fontWeight: $node.data('weight')
            });
        });

        var finish = function () {
            var ascii = isRendered('AVOKADO PRAHA', quoted);

            // Čárkované samohlásky patří do Latin-1, znaky s háčkem a kroužkem
            // až do Latin Extended-A. Ořezané webfonty mívají jen tu první
            // skupinu, což se projeví přesně jako závada hlášená klientem.
            var latin1 = isRendered('ÁÉÍÓÚÝ áéíóúý', quoted);
            var extendedA = isRendered('ČĎĚŇŘŠŤŮŽ čďěňřšťůž', quoted);
            var czech = latin1 && extendedA;

            if (ascii && latin1 && !extendedA) {
                setVerdict(
                    'is-bad',
                    '<strong>Font neobsahuje znaky s háčky a kroužky.</strong> Čárkované samohlásky (á, é, í, ó, ú, ý) ' +
                    'vykresluje správně, ale č, ě, ř, š, ž, ů, ď, ť, ň bere z náhradního písma. ' +
                    'Jde o ořezaný webfont bez znakové sady Latin Extended-A – přesně tak vypadá závada, ' +
                    'kterou hlásil klient. Nahrajte úplnou verzi souboru fontu.'
                );
                return;
            }

            if (!ascii) {
                setVerdict(
                    'is-bad',
                    '<strong>Font se nepoužil vůbec.</strong> Prohlížeč vykresluje testovaný text náhradním fontem. ' +
                    'Zkontrolujte název fontu a jeho registraci (@font-face).'
                );
                return;
            }

            if (!czech) {
                setVerdict(
                    'is-warn',
                    '<strong>Diakritika padá do náhradního fontu.</strong> Font se načetl, ale znaky s háčky a čárkami ' +
                    'vykresluje prohlížeč jiným písmem. Přesně tak vypadá závada, kterou hlásil klient. ' +
                    'Řešením je nahrát soubor fontu, který české znaky obsahuje.'
                );
                return;
            }

            setVerdict(
                'is-ok',
                '<strong>V pořádku.</strong> Testovaný font vykresluje běžný text i českou diakritiku. ' +
                'Ukázky níže jsou schválně doplněné náhradou monospace – pokud by některý znak vypadal jako ze psacího stroje, ' +
                'font by ho neobsahoval.'
            );
        };

        if (document.fonts && document.fonts.load) {
            var jobs = ['100', '400', '700'].map(function (weight) {
                return document.fonts.load(weight + ' 20px ' + quoted);
            });

            Promise.all(jobs).then(finish, finish);
            return;
        }

        finish();
    }

    /* ------------------------------------------------------------ start -- */

    $(function () {
        updateSwitchLabel();
        updateElementStates();
        updateFontModeBlocks();
        syncAllColorPickers();
        updatePreview();
        runDiacriticsTest();

        $('.garry-typo-switch input[type="checkbox"]').on('change', updateSwitchLabel);

        $('.garry-typo-element-toggle').on('change', function () {
            updateElementStates();
            updatePreview();
        });

        // Každá změna kteréhokoli atributu se hned promítne do náhledu.
        $(document).on('input change', '.garry-typo-input', updatePreview);

        $('#garry-typo-font-mode').on('change', updateFontModeBlocks);

        $(document).on('input change', '.garry-typo-color-text', function () {
            syncColorPicker($(this));
        });

        $(document).on('input change', '.garry-typo-color-pick', function () {
            var $picker = $(this);
            $picker.closest('.garry-typo-color-row')
                .find('.garry-typo-color-text')
                .val($picker.val())
                .trigger('change');
        });

        $(document).on('click', '.garry-typo-swatch', function () {
            var color = $(this).data('color');

            $(this).closest('.garry-typo-field')
                .find('.garry-typo-color-text')
                .val(color)
                .trigger('change');
        });

        $(document).on('click', '.garry-typo-color-clear', function () {
            $(this).closest('.garry-typo-color-row')
                .find('.garry-typo-color-text')
                .val('')
                .trigger('change');
        });

        $(document).on('click', '.garry-typo-media-pick', function () {
            openMediaPicker($(this).closest('.garry-typo-file-picker').find('input[type="text"]'));
        });

        $('#garry-typo-face-add').on('click', addFaceRow);

        $('#garry-typo-face-bundled').on('click', function () {
            var bundled = data.bundledFaces || [];
            var $rows = $('#garry-typo-faces .garry-typo-face-row');

            // Doplníme řádky tak, aby jich bylo aspoň tolik jako dodaných řezů.
            while ($('#garry-typo-faces .garry-typo-face-row').length < bundled.length) {
                addFaceRow();
            }

            $rows = $('#garry-typo-faces .garry-typo-face-row');

            $.each(bundled, function (index, face) {
                var $row = $rows.eq(index);

                $row.find('select[name*="[weight]"]').val(face.weight);
                $row.find('select[name*="[style]"]').val(face.style);
                $row.find('input[name*="[url]"]').val(face.url);
                $row.find('input[name*="[url_fallback]"]').val(face.url_fallback);
            });

            $('input[name*="[custom_font_enabled]"]').prop('checked', true);
            $('#garry-typo-font-mode').val('faces');
            updateFontModeBlocks();
        });

        $(document).on('click', '.garry-typo-face-remove', function () {
            var $rows = $('#garry-typo-faces .garry-typo-face-row');

            if ($rows.length <= 1) {
                $(this).closest('.garry-typo-face-row').find('input[type="text"]').val('');
                return;
            }

            $(this).closest('.garry-typo-face-row').remove();
        });

        $('.garry-typo-selector-toggle').on('click', function () {
            var $button = $(this);
            var $panel = $button.closest('.garry-typo-element').find('.garry-typo-selectors');
            var hidden = $panel.prop('hidden');

            $panel.prop('hidden', !hidden);
            $button.attr('aria-expanded', hidden ? 'true' : 'false');
            $button.text(hidden ? 'Skrýt rozšířené' : 'Rozšířené nastavení');
        });

        $('#garry-typo-reset-tracking').on('click', function () {
            $('.garry-typo-letterspacing').val('normal');
            updatePreview();
        });

        $('#garry-typo-test-font').on('change', runDiacriticsTest);
    });
})(jQuery);
