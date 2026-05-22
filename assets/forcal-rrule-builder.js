/**
 * ForCal – RRule Builder Widget
 *
 * Eigenständiges Widget zur Pflege von RFC 5545 RRULE-Strings.
 * Keine externen Abhängigkeiten außer dem DOM.
 *
 * Interaktionspunkte:
 *   - Wrapper-ID:             .forcal-rrule-widget  (darf mehrfach vorkommen, aber nur einer wird erwartet)
 *   - Verstecktes Feld:       input#forcal_rrule_value  → enthält den RRULE-String beim Submit
 *   - Verstecktes type-Feld:  input#forcal_type_value   → 'one_time' / 'repeat'
 *   - Verstecktes end_repeat: input#forcal_end_repeat_value → Enddatum für Handler-SQL-Compat
 *
 * Sprachdaten werden über data-Attribute vom PHP übergeben (data-i18n="...JSON...").
 */
(function () {
  'use strict';

  // ── Mapping RRule-Wochentag → Kurzform im lokalen Array-Index ───────────────
  var WEEKDAYS = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];

  // ── Parse einen RRULE-String in ein Objekt ─────────────────────────────────
  function parseRRule(str) {
    if (!str || typeof str !== 'string') return null;
    str = str.trim();
    if (!str) return null;
    // "RRULE:" Präfix tolerieren
    if (str.indexOf('RRULE:') === 0) str = str.slice(6);

    var result = {};
    str.split(';').forEach(function (part) {
      var eqPos = part.indexOf('=');
      if (eqPos < 0) return;
      var key = part.slice(0, eqPos).toUpperCase();
      var val = part.slice(eqPos + 1);
      result[key] = val;
    });
    return result.FREQ ? result : null;
  }

  // ── Aus UNTIL-String (20261231T000000Z oder 20261231) ein Date-Objekt bauen ─
  function parseUntil(str) {
    if (!str) return null;
    var clean = str.replace(/[TZ\-:]/g, '');
    // YYYYMMDD
    var y = clean.slice(0, 4), m = clean.slice(4, 6), d = clean.slice(6, 8);
    if (!y || !m || !d) return null;
    return new Date(parseInt(y, 10), parseInt(m, 10) - 1, parseInt(d, 10));
  }

  // ── Date → UNTIL-String (YYYYMMDDТ000000Z) ─────────────────────────────────
  function formatUntil(d) {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '';
    var p = function (n) { return String(n).padStart(2, '0'); };
    return d.getFullYear() + '' + p(d.getMonth() + 1) + '' + p(d.getDate()) + 'T000000Z';
  }

  // ── Date → Anzeigeformat TT.MM.JJJJ ────────────────────────────────────────
  function formatDateDisplay(d) {
    if (!(d instanceof Date) || isNaN(d.getTime())) return '';
    var p = function (n) { return String(n).padStart(2, '0'); };
    return p(d.getDate()) + '.' + p(d.getMonth() + 1) + '.' + d.getFullYear();
  }

  // ── TT.MM.JJJJ oder JJJJ-MM-TT → Date ─────────────────────────────────────
  function parseDateDisplay(str) {
    if (!str) return null;
    str = str.trim();
    // ISO
    if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
      var parts = str.split('-');
      return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    }
    // TT.MM.JJJJ
    if (/^\d{2}\.\d{2}\.\d{4}$/.test(str)) {
      var parts = str.split('.');
      return new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0]));
    }
    return null;
  }

  // ── Berechne letztes Vorkommen für end_repeat_date-Compat ──────────────────
  function computeEndRepeatDate(rruleObj) {
    if (!rruleObj) return '';

    if (rruleObj.UNTIL) {
      var d = parseUntil(rruleObj.UNTIL);
      if (d) {
        var p = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
      }
    }
    if (rruleObj.COUNT) {
      // Approximation: bei COUNT ohne UNTIL → weit in die Zukunft (Handler-Compat)
      return '2099-12-31';
    }
    // Infinite (kein UNTIL, kein COUNT) → weit in die Zukunft
    return '2099-12-31';
  }

  // ── Menschenlesbare Beschreibung (Deutsch/Englisch) ─────────────────────────
  function buildHumanText(rruleObj, i18n) {
    if (!rruleObj || !rruleObj.FREQ) return '';

    var lang = (document.documentElement.lang || 'de').toLowerCase().slice(0, 2);
    var de = lang === 'de';

    var freq = rruleObj.FREQ;
    var interval = parseInt(rruleObj.INTERVAL || '1', 10);

    var freqLabels = {
      DAILY:   de ? (interval === 1 ? 'täglich' : 'alle ' + interval + ' Tage') : (interval === 1 ? 'daily' : 'every ' + interval + ' days'),
      WEEKLY:  de ? (interval === 1 ? 'wöchentlich' : 'alle ' + interval + ' Wochen') : (interval === 1 ? 'weekly' : 'every ' + interval + ' weeks'),
      MONTHLY: de ? (interval === 1 ? 'monatlich' : 'alle ' + interval + ' Monate') : (interval === 1 ? 'monthly' : 'every ' + interval + ' months'),
      YEARLY:  de ? (interval === 1 ? 'jährlich' : 'alle ' + interval + ' Jahre') : (interval === 1 ? 'yearly' : 'every ' + interval + ' years'),
    };

    var parts = [de ? 'Wiederholt sich ' : 'Repeats '];
    parts.push(freqLabels[freq] || freq.toLowerCase());

    // BYDAY bei WEEKLY
    if (freq === 'WEEKLY' && rruleObj.BYDAY) {
      var dayNames = {
        de: { MO: 'Mo', TU: 'Di', WE: 'Mi', TH: 'Do', FR: 'Fr', SA: 'Sa', SU: 'So' },
        en: { MO: 'Mo', TU: 'Tu', WE: 'We', TH: 'Th', FR: 'Fr', SA: 'Sa', SU: 'Su' },
      };
      var names = rruleObj.BYDAY.split(',').map(function (d) {
        return (de ? dayNames.de : dayNames.en)[d] || d;
      });
      parts.push((de ? ' am ' : ' on ') + names.join(', '));
    }

    // BYMONTHDAY / BYDAY bei MONTHLY
    if (freq === 'MONTHLY') {
      if (rruleObj.BYMONTHDAY) {
        parts.push((de ? ' am ' : ' on the ') + rruleObj.BYMONTHDAY + (de ? '. des Monats' : '.' + (de ? '' : ' of the month')));
      } else if (rruleObj.BYDAY) {
        var m = rruleObj.BYDAY.match(/^(-?\d+)([A-Z]+)$/);
        if (m) {
          var ordDE = { '1': '1.', '2': '2.', '3': '3.', '4': '4.', '-1': 'letzten' };
          var ordEN = { '1': '1st', '2': '2nd', '3': '3rd', '4': '4th', '-1': 'last' };
          var dayNamesLong = {
            de: { MO: 'Montag', TU: 'Dienstag', WE: 'Mittwoch', TH: 'Donnerstag', FR: 'Freitag', SA: 'Samstag', SU: 'Sonntag' },
            en: { MO: 'Monday', TU: 'Tuesday', WE: 'Wednesday', TH: 'Thursday', FR: 'Friday', SA: 'Saturday', SU: 'Sunday' },
          };
          var ord = de ? (ordDE[m[1]] || m[1] + '.') : (ordEN[m[1]] || m[1] + '.');
          var dayName = (de ? dayNamesLong.de : dayNamesLong.en)[m[2]] || m[2];
          parts.push((de ? ' am ' : ' on the ') + ord + ' ' + dayName + (de ? ' des Monats' : ' of the month'));
        }
      }
    }

    // End condition
    if (rruleObj.UNTIL) {
      var untilDate = parseUntil(rruleObj.UNTIL);
      if (untilDate) {
        parts.push((de ? ', bis ' : ', until ') + formatDateDisplay(untilDate));
      }
    } else if (rruleObj.COUNT) {
      parts.push((de ? ', ' + rruleObj.COUNT + '-mal' : ', ' + rruleObj.COUNT + ' time(s)'));
    } else {
      parts.push(de ? ', ohne Enddatum' : ', indefinitely');
    }

    return parts.join('');
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // Widget-Initialisierung
  // ═══════════════════════════════════════════════════════════════════════════

  function initWidget(wrapper) {
    // Felder aus dem DOM lesen
    var enableCb    = wrapper.querySelector('.forcal-rrule-enable');
    var builderArea = wrapper.querySelector('.forcal-rrule-body');
    var freqSel     = wrapper.querySelector('.rrule-freq');
    var intervalIn  = wrapper.querySelector('.rrule-interval');
    var intervalSfx = wrapper.querySelector('.rrule-interval-suffix');
    var weeklyOpts  = wrapper.querySelector('.rrule-weekly-opts');
    var monthlyOpts = wrapper.querySelector('.rrule-monthly-opts');
    var previewEl   = wrapper.querySelector('.forcal-rrule-preview');

    // Externe Felder (außerhalb des Wrappers)
    var rruleHidden     = document.getElementById('forcal_rrule_value');
    var typeHidden      = document.getElementById('forcal_type_value');
    var endRepeatHidden = document.getElementById('forcal_end_repeat_value');

    if (!enableCb || !freqSel || !rruleHidden) return;

    // i18n-Daten per data-Attribut oder Fallback
    var i18n = {};
    try {
      i18n = JSON.parse(wrapper.getAttribute('data-i18n') || '{}');
    } catch (e) {}

    // ── RRULE-String aus den Widget-Feldern generieren ──────────────────────
    function buildRRuleString() {
      if (!enableCb.checked) return '';

      var freq     = freqSel.value;
      var interval = Math.max(1, parseInt(intervalIn.value, 10) || 1);
      var parts    = ['FREQ=' + freq, 'INTERVAL=' + interval];

      if (freq === 'WEEKLY') {
        var bydayChecked = Array.from(wrapper.querySelectorAll('.rrule-byday:checked'))
          .map(function (cb) { return cb.value; });
        if (bydayChecked.length > 0) {
          parts.push('BYDAY=' + bydayChecked.join(','));
        }
      }

      if (freq === 'MONTHLY') {
        var monthlyMode = wrapper.querySelector('input[name="rrule_monthly_mode"]:checked');
        if (monthlyMode && monthlyMode.value === 'bymonthday') {
          var dayNum = wrapper.querySelector('.rrule-bymonthday').value;
          parts.push('BYMONTHDAY=' + (parseInt(dayNum, 10) || 1));
        } else {
          var ordinalSel = wrapper.querySelector('.rrule-byday-ordinal');
          var daySel     = wrapper.querySelector('.rrule-byday-day');
          if (ordinalSel && daySel) {
            parts.push('BYDAY=' + ordinalSel.value + daySel.value);
          }
        }
      }

      // End-Bedingung
      var endType = wrapper.querySelector('input[name="rrule_end"]:checked');
      if (endType && endType.value === 'until') {
        var untilInput = wrapper.querySelector('.rrule-until');
        if (untilInput && untilInput.value) {
          var untilDate = parseDateDisplay(untilInput.value);
          if (untilDate) {
            parts.push('UNTIL=' + formatUntil(untilDate));
          }
        }
      } else if (endType && endType.value === 'count') {
        var countInput = wrapper.querySelector('.rrule-count');
        if (countInput && countInput.value) {
          parts.push('COUNT=' + (parseInt(countInput.value, 10) || 1));
        }
      }

      return parts.join(';');
    }

    // ── Externe Felder und Vorschau aktualisieren ───────────────────────────
    function updateHiddenAndPreview() {
      var rruleStr   = buildRRuleString();
      rruleHidden.value = rruleStr;

      if (typeHidden) {
        typeHidden.value = rruleStr ? 'repeat' : 'one_time';
      }
      if (endRepeatHidden) {
        var rruleObj = parseRRule(rruleStr);
        endRepeatHidden.value = rruleObj ? computeEndRepeatDate(rruleObj) : '';
      }

      if (previewEl) {
        var rruleObj = parseRRule(rruleStr);
        if (rruleObj) {
          previewEl.textContent = buildHumanText(rruleObj, i18n);
          previewEl.style.display = '';
        } else {
          previewEl.style.display = 'none';
        }
      }
    }

    // ── UI-Sichtbarkeit je nach Frequenz steuern ────────────────────────────
    function updateFreqVisibility() {
      var freq = freqSel.value;
      var suffixMap = {
        DAILY:   wrapper.getAttribute('data-suffix-daily')   || 'Tag(e)',
        WEEKLY:  wrapper.getAttribute('data-suffix-weekly')  || 'Woche(n)',
        MONTHLY: wrapper.getAttribute('data-suffix-monthly') || 'Monat(e)',
        YEARLY:  wrapper.getAttribute('data-suffix-yearly')  || 'Jahr(e)',
      };
      if (intervalSfx) intervalSfx.textContent = suffixMap[freq] || '';
      if (weeklyOpts)  weeklyOpts.style.display  = (freq === 'WEEKLY')  ? '' : 'none';
      if (monthlyOpts) monthlyOpts.style.display = (freq === 'MONTHLY') ? '' : 'none';
    }

    // ── Zeige/Verstecke End-Eingabefelder ──────────────────────────────────
    function updateEndVisibility() {
      var selected = wrapper.querySelector('input[name="rrule_end"]:checked');
      var untilGroup = wrapper.querySelector('.rrule-until-group');
      var countGroup = wrapper.querySelector('.rrule-count-group');
      if (untilGroup) untilGroup.style.display = (selected && selected.value === 'until') ? '' : 'none';
      if (countGroup) countGroup.style.display = (selected && selected.value === 'count') ? '' : 'none';
    }

    // ── Toggle Sichtbarkeit des Builder-Bereichs ────────────────────────────
    function toggleBuilder() {
      if (builderArea) {
        builderArea.style.display = enableCb.checked ? '' : 'none';
      }
      if (!enableCb.checked && typeHidden) {
        typeHidden.value = 'one_time';
      }
      updateHiddenAndPreview();
    }

    // ── Widget mit vorhandenem RRULE-Wert befüllen ──────────────────────────
    function populateFromRRule(rruleStr) {
      var parsed = parseRRule(rruleStr);
      if (!parsed) {
        enableCb.checked = false;
        toggleBuilder();
        return;
      }

      enableCb.checked = true;

      // Frequenz
      if (freqSel) freqSel.value = parsed.FREQ || 'WEEKLY';

      // Interval
      if (intervalIn) intervalIn.value = parseInt(parsed.INTERVAL || '1', 10);

      // Weekly: BYDAY
      if (parsed.FREQ === 'WEEKLY' && parsed.BYDAY) {
        var selectedDays = parsed.BYDAY.split(',');
        wrapper.querySelectorAll('.rrule-byday').forEach(function (cb) {
          cb.checked = selectedDays.indexOf(cb.value) >= 0;
          // Bootstrap btn-group active-Klasse setzen
          var lbl = cb.closest('label') || cb.parentElement;
          if (lbl) {
            if (cb.checked) lbl.classList.add('active');
            else lbl.classList.remove('active');
          }
        });
      }

      // Monthly
      if (parsed.FREQ === 'MONTHLY') {
        if (parsed.BYMONTHDAY) {
          var bymonthModeEl = wrapper.querySelector('input[name="rrule_monthly_mode"][value="bymonthday"]');
          if (bymonthModeEl) bymonthModeEl.checked = true;
          var byMD = wrapper.querySelector('.rrule-bymonthday');
          if (byMD) byMD.value = parsed.BYMONTHDAY;
        } else if (parsed.BYDAY) {
          var bydayModeEl = wrapper.querySelector('input[name="rrule_monthly_mode"][value="byday"]');
          if (bydayModeEl) bydayModeEl.checked = true;
          var m = parsed.BYDAY.match(/^(-?\d+)([A-Z]+)$/);
          if (m) {
            var ordEl = wrapper.querySelector('.rrule-byday-ordinal');
            var dayEl = wrapper.querySelector('.rrule-byday-day');
            if (ordEl) ordEl.value = m[1];
            if (dayEl) dayEl.value = m[2];
          }
        }
      }

      // End condition
      if (parsed.UNTIL) {
        var untilRadio = wrapper.querySelector('input[name="rrule_end"][value="until"]');
        if (untilRadio) untilRadio.checked = true;
        var d = parseUntil(parsed.UNTIL);
        if (d) {
          var untilIn = wrapper.querySelector('.rrule-until');
          if (untilIn) untilIn.value = formatDateDisplay(d);
        }
      } else if (parsed.COUNT) {
        var countRadio = wrapper.querySelector('input[name="rrule_end"][value="count"]');
        if (countRadio) countRadio.checked = true;
        var countIn = wrapper.querySelector('.rrule-count');
        if (countIn) countIn.value = parsed.COUNT;
      } else {
        var neverRadio = wrapper.querySelector('input[name="rrule_end"][value="never"]');
        if (neverRadio) neverRadio.checked = true;
      }

      updateFreqVisibility();
      updateEndVisibility();
      toggleBuilder();
    }

    // ── Flatpickr für Until-Feld initialisieren (falls flatpickr verfügbar) ─
    function initUntilPicker() {
      var untilInput = wrapper.querySelector('.rrule-until');
      if (!untilInput) return;

      var pickerFactory = null;
      if (typeof a11y_datetime === 'function') {
        pickerFactory = a11y_datetime;
      } else if (typeof flatpickr !== 'undefined') {
        pickerFactory = flatpickr;
      }

      if (typeof AirDatepicker !== 'undefined') {
        new AirDatepicker(untilInput, {
          locale: (document.documentElement.lang || 'de').toLowerCase().slice(0, 2) === 'de'
            ? {
                days: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
                daysShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
                daysMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
                months: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
                monthsShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
                today: 'Heute', clear: 'Löschen', dateFormat: 'dd.MM.yyyy', timeFormat: 'HH:mm', firstDay: 1,
              }
            : {
                days: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
                daysShort: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                daysMin: ['Su','Mo','Tu','We','Th','Fr','Sa'],
                months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
                monthsShort: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                today: 'Today', clear: 'Clear', dateFormat: 'MM/dd/yyyy', timeFormat: 'hh:mm aa', firstDay: 0,
              },
          dateFormat: 'dd.MM.yyyy',
          autoClose: true,
          onSelect: function () { updateHiddenAndPreview(); },
        });
      } else if (pickerFactory) {
        pickerFactory(untilInput, {
          dateFormat: 'd.m.Y',
          allowInput: true,
          onChange: function () { updateHiddenAndPreview(); },
        });
      }
    }

    // ── Event-Listener registrieren ─────────────────────────────────────────
    enableCb.addEventListener('change', toggleBuilder);

    freqSel.addEventListener('change', function () {
      updateFreqVisibility();
      updateHiddenAndPreview();
    });

    if (intervalIn) {
      intervalIn.addEventListener('input', updateHiddenAndPreview);
    }

    wrapper.querySelectorAll('.rrule-byday').forEach(function (cb) {
      cb.addEventListener('change', function () {
        // Bootstrap btn-group
        var lbl = cb.closest('label') || cb.parentElement;
        if (lbl) {
          if (cb.checked) lbl.classList.add('active');
          else lbl.classList.remove('active');
        }
        updateHiddenAndPreview();
      });
    });

    wrapper.querySelectorAll('input[name="rrule_monthly_mode"]').forEach(function (r) {
      r.addEventListener('change', updateHiddenAndPreview);
    });

    var byMD = wrapper.querySelector('.rrule-bymonthday');
    if (byMD) byMD.addEventListener('input', updateHiddenAndPreview);

    var ordEl = wrapper.querySelector('.rrule-byday-ordinal');
    if (ordEl) ordEl.addEventListener('change', updateHiddenAndPreview);

    var dayEl = wrapper.querySelector('.rrule-byday-day');
    if (dayEl) dayEl.addEventListener('change', updateHiddenAndPreview);

    wrapper.querySelectorAll('input[name="rrule_end"]').forEach(function (r) {
      r.addEventListener('change', function () {
        updateEndVisibility();
        updateHiddenAndPreview();
      });
    });

    var untilIn = wrapper.querySelector('.rrule-until');
    if (untilIn) {
      untilIn.addEventListener('change', updateHiddenAndPreview);
      untilIn.addEventListener('input', updateHiddenAndPreview);
    }

    var countIn = wrapper.querySelector('.rrule-count');
    if (countIn) countIn.addEventListener('input', updateHiddenAndPreview);

    // ── Init: Datepicker + vorhandenen RRULE laden ──────────────────────────
    initUntilPicker();
    populateFromRRule(rruleHidden ? rruleHidden.value : '');
  }

  // ── Alle Widgets auf der Seite initialisieren ───────────────────────────────
  function init() {
    document.querySelectorAll('.forcal-rrule-widget').forEach(initWidget);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // PJAX-Kompatibilität
  document.addEventListener('pjax:end', init);

  window.forCalRRuleBuilder = { init: init };
})();
