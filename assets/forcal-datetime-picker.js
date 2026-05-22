/**
 * ForCal v7 – DateTime Picker Initialisierung
 *
 * Initialisiert Flatpickr auf allen forcal-DateTime-Feldern.
 * Der Picker wird zentral ueber das REDAXO-Addon flatpickr bereitgestellt
 * (a11y_datetime mit flatpickr-Kompatibilitaetsalias).
 *
 * Felder:
 *   [data-forcal-picker="datetime"] – Datum + Uhrzeit
 *   [data-forcal-picker="date"]     – nur Datum
 *   [data-forcal-picker="time"]     – nur Zeit
 *   [data-forcal-picker="range"]    – Datumsbereich (2 getrennte Felder)
 */

(function () {
  'use strict';

  function getPickerFactory() {
    if (typeof a11y_datetime === 'function') {
      return a11y_datetime;
    }
    if (typeof flatpickr === 'function') {
      return flatpickr;
    }
    return null;
  }

  // Sprache aus <html lang="..."> ableiten
  function getLocale() {
    const lang = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
    return lang;
  }

  // Gemeinsame Flatpickr-Optionen
  const BASE_CONFIG = {
    allowInput:   true,
    time_24hr:    true,
    disableMobile: false,
    monthSelectorType: 'static',
  };

  function initPickers() {
    const pickerFactory = getPickerFactory();
    if (!pickerFactory) {
      console.warn('forcal-datetime-picker: Kein DateTime-Picker verfuegbar. Bitte das flatpickr-Addon aktivieren.');
      return;
    }

    const locale = getLocale();

    // -------------------------------------------------------------------------
    // DateTime-Felder (dtstart / dtend) — nur auf [data-forcal-picker="datetime"]
    // Nicht auf type="hidden" initialisieren; die UI-Inputs haben data-forcal-target
    // auf das jeweilige hidden-Feld gesetzt.
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-forcal-picker="datetime"]:not([type="hidden"])').forEach(function (el) {
      if (el._flatpickr) return; // bereits initialisiert
      pickerFactory(el, Object.assign({}, BASE_CONFIG, {
        enableTime:  true,
        dateFormat:  'Y-m-dTH:i',   // ISO-Format im Hidden-Feld
        altInput:    true,
        altFormat:   'd.m.Y H:i',
        defaultDate: el.value || null,
        locale:      locale,
        onChange: function (selectedDates, dateStr) {
          // Hidden-Feld mit dem maschinenlesbaren Wert befüllen
          const targetId = el.dataset.forcalTarget;
          if (targetId) {
            const hidden = document.getElementById(targetId);
            if (hidden) hidden.value = dateStr;
          }
          syncRangeConstraint(el);
        },
      }));
    });

    // -------------------------------------------------------------------------
    // Date-only Felder
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-forcal-picker="date"]').forEach(function (el) {
      if (el._flatpickr) return;
      pickerFactory(el, Object.assign({}, BASE_CONFIG, {
        enableTime:  false,
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   'd.m.Y',
        locale:      locale,
      }));
    });

    // -------------------------------------------------------------------------
    // Time-only Felder
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-forcal-picker="time"]').forEach(function (el) {
      if (el._flatpickr) return;
      pickerFactory(el, Object.assign({}, BASE_CONFIG, {
        enableTime:  true,
        noCalendar:  true,
        dateFormat:  'H:i',
        locale:      locale,
      }));
    });

    // -------------------------------------------------------------------------
    // RRULE UNTIL Felder (Datumsformat Y-m-d)
    // -------------------------------------------------------------------------
    document.querySelectorAll('[data-forcal-picker="rrule-until"]').forEach(function (el) {
      if (el._flatpickr) return;
      pickerFactory(el, Object.assign({}, BASE_CONFIG, {
        enableTime:  false,
        dateFormat:  'Y-m-d',
        altInput:    true,
        altFormat:   'd.m.Y',
        locale:      locale,
      }));
    });
  }

  /**
   * Synchronisiert minDate des DTEND-Pickers wenn DTSTART geändert wird.
   * El muss data-forcal-linked-end="<id-des-end-inputs>" haben.
   */
  function syncRangeConstraint(el) {
    const endId = el.dataset.forcalLinkedEnd;
    if (!endId) return;

    const endEl = document.getElementById(endId);
    if (!endEl || !endEl._flatpickr) return;

    const startInstance = el._flatpickr;
    const selectedDate = startInstance && startInstance.selectedDates[0];
    if (selectedDate) {
      endEl._flatpickr.set('minDate', selectedDate);
      const endSelected = endEl._flatpickr.selectedDates[0];
      if (endSelected && endSelected < selectedDate) {
        endEl._flatpickr.setDate(selectedDate, true);
      }
    }
  }

  // -------------------------------------------------------------------------
  // Ganztags-Checkbox: Zeit-Sektionen ein-/ausblenden
  // -------------------------------------------------------------------------
  function initAllDayToggle() {
    const allDayCheckboxes = document.querySelectorAll('[data-forcal-allday]');
    allDayCheckboxes.forEach(function (cb) {
      function applyState() {
        const timeSections = document.querySelectorAll('[data-forcal-time-section]');
        timeSections.forEach(function (sec) {
          sec.style.display = cb.checked ? 'none' : '';
        });

        // Flatpickr: Zeit deaktivieren wenn ganztags
        document.querySelectorAll('[data-forcal-picker="datetime"]').forEach(function (el) {
          if (!el._flatpickr) return;
          if (cb.checked) {
            el._flatpickr.set('enableTime', false);
            el._flatpickr.set('dateFormat', 'Y-m-d');
            el._flatpickr.set('altFormat', 'd.m.Y');
          } else {
            el._flatpickr.set('enableTime', true);
            el._flatpickr.set('dateFormat', 'Y-m-d H:i:S');
            el._flatpickr.set('altFormat', 'd.m.Y H:i');
          }
        });
      }

      cb.addEventListener('change', applyState);
      applyState(); // Initialzustand setzen
    });
  }

  // -------------------------------------------------------------------------
  // Boot
  // -------------------------------------------------------------------------
  function boot() {
    initPickers();
    initAllDayToggle();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
