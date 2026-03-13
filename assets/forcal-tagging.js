/**
 * Forcal Tagging Widget – jQuery
 * Eigenständig, keine Abhängigkeit zum fields-Addon.
 *
 * Speichert Tags als JSON: [{"text":"...","color":"#..."}]
 *
 * Kompatibel mit dem fields_tagging-Widget – verwendet dieselben CSS-Klassen.
 */
jQuery(function ($) {
    'use strict';

    // ─── WCAG-Kontrast-Helfer ────────────────────────────────────────────────
    function hexToLuminance(hex) {
        var s = hex.replace('#', '');
        var r = parseInt(s.slice(0, 2), 16) / 255;
        var g = parseInt(s.slice(2, 4), 16) / 255;
        var b = parseInt(s.slice(4, 6), 16) / 255;
        function lin(c) { return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); }
        return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    }
    function okForWhiteText(hex) {
        return (1.05 / (hexToLuminance(hex) + 0.05)) >= 3.0;
    }

    // ─── Widget initialisieren ───────────────────────────────────────────────
    function initWidget(el) {
        var $el          = $(el);
        var $chips       = $el.find('.fields-tagging-chips');
        var $panel       = $el.find('.fields-tagging-panel');
        var $input       = $el.find('.fields-tagging-input');
        var $addBtn      = $el.find('.fields-tagging-add-btn');
        var $openBtn     = $el.find('.fields-tagging-open-btn');
        var $closeBtn    = $el.find('.fields-tagging-close-btn');
        var $preview     = $el.find('.fields-tagging-color-preview');
        var $suggestions = $el.find('.fields-tagging-suggestions');
        var $hidden      = $el.find('.fields-tagging-value');
        var $counter     = $el.find('.fields-tagging-count');
        var $customColor  = $el.find('.fields-tagging-custom-color');
        var $contrastHint = $el.find('.fields-tagging-contrast-hint');

        var apiUrl      = $el.data('api-url');
        var srcTable    = $el.data('source-table');
        var srcField    = $el.data('source-field');
        var maxTags     = parseInt($el.data('max-tags'), 10) || 0;
        var colors      = $el.data('colors') || ['#2980b9'];
        var activeColor = colors[0];
        var suggestionsLoaded = false;

        // ─── Hilfsfunktionen ─────────────────────────────────────────────

        function currentTags() {
            var result = [];
            $chips.find('.fields-tagging-chip').each(function () {
                result.push({ text: $(this).data('text'), color: $(this).data('color') });
            });
            return result;
        }

        function syncHidden() {
            var tags = currentTags();
            $hidden.val(tags.length ? JSON.stringify(tags) : '');
            if ($counter.length) {
                $counter.text(tags.length);
            }
        }

        function textExists(text) {
            var lower = text.toLowerCase();
            return currentTags().some(function (t) {
                return t.text.toLowerCase() === lower;
            });
        }

        function addChip(text, color) {
            text = $.trim(text);
            if (!text) { return; }
            if (maxTags > 0 && currentTags().length >= maxTags) { return; }
            if (textExists(text)) { return; }

            var $chip = $('<span class="fields-tagging-chip">')
                .data('text', text)
                .data('color', color)
                .attr('data-text', text)
                .attr('data-color', color)
                .css('background', color)
                .text(text + '\u00a0');

            $('<button type="button" class="fields-tagging-chip-remove" aria-label="Entfernen">&times;</button>')
                .appendTo($chip);

            $openBtn.before($chip);
            syncHidden();
            updateSuggestionStates();
        }

        function removeChip($chip) {
            $chip.remove();
            syncHidden();
            updateSuggestionStates();
        }

        function setActiveColor(color) {
            activeColor = color;
            $preview.css('background', color);
            $el.find('.fields-tagging-color-btn').each(function () {
                $(this).toggleClass('active', $(this).data('color') === color);
            });
        }

        function updateSuggestionStates() {
            $suggestions.find('.fields-tagging-suggestion-chip').each(function () {
                $(this).toggleClass('is-selected', textExists($(this).data('text')));
            });
        }

        // ─── Vorschläge laden ────────────────────────────────────────────

        function loadSuggestions() {
            if (suggestionsLoaded) {
                updateSuggestionStates();
                return;
            }
            if (!apiUrl || !srcTable || !srcField) {
                $suggestions.html('<em class="text-muted" style="font-size:12px;">Keine Quelle konfiguriert.</em>');
                suggestionsLoaded = true;
                return;
            }

            $suggestions.html('<em class="text-muted" style="font-size:12px;">Wird geladen …</em>');

            $.get(apiUrl, { table: srcTable, field: srcField }, function (data) {
                $suggestions.empty();
                if (!data || !data.success || !data.tags || data.tags.length === 0) {
                    $suggestions.html('<em class="text-muted" style="font-size:12px;">Keine Vorschläge vorhanden.</em>');
                    suggestionsLoaded = true;
                    return;
                }
                $.each(data.tags, function (i, tag) {
                    $('<button type="button" class="fields-tagging-suggestion-chip">')
                        .data('text', tag.text)
                        .data('color', tag.color)
                        .css('background', tag.color)
                        .text(tag.text)
                        .appendTo($suggestions);
                });
                suggestionsLoaded = true;
                updateSuggestionStates();
            }).fail(function () {
                $suggestions.html('<em class="text-muted" style="font-size:12px;">Fehler beim Laden.</em>');
                suggestionsLoaded = true;
            });
        }

        // ─── Events ──────────────────────────────────────────────────────

        $openBtn.on('click', function () {
            $panel.slideDown(150);
            $openBtn.hide();
            loadSuggestions();
            $input.trigger('focus');
        });

        $closeBtn.on('click', function () {
            $panel.slideUp(150, function () { $openBtn.show(); });
        });

        $chips.on('click', '.fields-tagging-chip-remove', function (e) {
            e.preventDefault();
            removeChip($(this).closest('.fields-tagging-chip'));
        });

        $el.on('click', '.fields-tagging-color-btn', function (e) {
            e.preventDefault();
            setActiveColor($(this).data('color'));
            $customColor.val($(this).data('color'));
            $contrastHint.hide();
            $customColor.removeClass('fields-tagging-color-invalid');
        });

        $customColor.on('input change', function () {
            var hex = $(this).val();
            if (okForWhiteText(hex)) {
                $contrastHint.hide();
                $customColor.removeClass('fields-tagging-color-invalid');
                $el.find('.fields-tagging-color-btn').removeClass('active');
                setActiveColor(hex);
            } else {
                $contrastHint.show();
                $customColor.addClass('fields-tagging-color-invalid');
            }
        });

        $addBtn.on('click', function (e) {
            e.preventDefault();
            var text = $.trim($input.val());
            if (text) {
                addChip(text, activeColor);
                $input.val('').trigger('focus');
            }
        });

        $input.on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                var text = $.trim($input.val());
                if (text) {
                    addChip(text, activeColor);
                    $input.val('');
                }
            }
        });

        $suggestions.on('click', '.fields-tagging-suggestion-chip', function (e) {
            e.preventDefault();
            var $s = $(this);
            if ($s.hasClass('is-selected')) {
                $chips.find('.fields-tagging-chip').each(function () {
                    if ($(this).data('text').toLowerCase() === $s.data('text').toLowerCase()) {
                        removeChip($(this));
                    }
                });
            } else {
                var color = activeColor !== colors[0] ? activeColor : $s.data('color');
                addChip($s.data('text'), color);
            }
        });

        $el.closest('form').on('submit.forcal-tagging', function () {
            syncHidden();
        });

        setActiveColor(activeColor);

        // Chips aus hidden input vorbelegen (rex_form-Kompatibilität:
        // PHP rendert keine Chips, JS liest den gespeicherten JSON-Wert).
        if ($chips.find('.fields-tagging-chip').length === 0) {
            var storedVal = $hidden.val();
            if (storedVal) {
                try {
                    var initialTags = JSON.parse(storedVal);
                    if (Array.isArray(initialTags)) {
                        $.each(initialTags, function (i, tag) {
                            if (tag.text && tag.color) {
                                addChip(tag.text, tag.color);
                            }
                        });
                    }
                } catch (e) { /* kein gültiges JSON, ignorieren */ }
            }
        }
    }

    // ─── Alle Widgets initialisieren ────────────────────────────────────────
    function initAll(scope) {
        $(scope || document).find('.fields-tagging-widget').each(function () {
            if (!$(this).data('ft-init')) {
                $(this).data('ft-init', true);
                initWidget(this);
            }
        });
    }

    $(document).on('rex:ready', function (e, container) {
        initAll(container);
    });

    initAll(document);
});
