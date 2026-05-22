/**
 * ForCal v7 - Datums-/Uhrzeitpicker (air-datepicker)
 *
 * Schreibt immer MySQL-DATETIME-Format (YYYY-MM-DD HH:MM:SS) in die
 * versteckten rex_form-Felder. Enddatum kann nicht vor Startdatum liegen.
 */
(function () {
  'use strict';

  // --- Locales ----------------------------------------------------------------
  var localeDE = {
    days:        ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
    daysShort:   ['Son','Mon','Die','Mit','Don','Fre','Sam'],
    daysMin:     ['So','Mo','Di','Mi','Do','Fr','Sa'],
    months:      ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
    monthsShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
    today: 'Heute', clear: 'Löschen', dateFormat: 'dd.MM.yyyy', timeFormat: 'HH:mm', firstDay: 1,
  };
  var localeEN = {
    days:        ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
    daysShort:   ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
    daysMin:     ['Su','Mo','Tu','We','Th','Fr','Sa'],
    months:      ['January','February','March','April','May','June','July','August','September','October','November','December'],
    monthsShort: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    today: 'Today', clear: 'Clear', dateFormat: 'MM/dd/yyyy', timeFormat: 'hh:mm aa', firstDay: 0,
  };

  // --- Helpers ----------------------------------------------------------------

  function getLocale() {
    var lang = (document.documentElement.lang || 'de').toLowerCase().substring(0, 2);
    return lang === 'de' ? localeDE : localeEN;
  }

  /** Date -> MySQL DATETIME 'YYYY-MM-DD HH:MM:SS' */
  function toDbDatetime(date) {
    var p = function (n) { return String(n).padStart(2, '0'); };
    return date.getFullYear() + '-' + p(date.getMonth() + 1) + '-' + p(date.getDate())
      + ' ' + p(date.getHours()) + ':' + p(date.getMinutes()) + ':00';
  }

  /**
   * MySQL-DATETIME oder ISO-String -> Date | null.
   * Lehnt ungueltige Daten ab (0000-00-00, negative Jahre).
   */
  function fromDbValue(str) {
    if (!str) return null;
    var s = str.replace('T', ' ');
    if (s.substring(0, 4) === '0000' || s.charAt(0) === '-') return null;
    var d = new Date(s);
    if (isNaN(d.getTime()) || d.getFullYear() < 1900) return null;
    return d;
  }

  // --- Picker init ------------------------------------------------------------

  function initPickers() {
    var startUI  = document.getElementById('forcal_dtstart_ui');
    var endUI    = document.getElementById('forcal_dtend_ui');
    var startH   = document.getElementById('forcal_dtstart');
    var endH     = document.getElementById('forcal_dtend');
    var alldayCb = document.getElementById('forcal_allday_ui');
    var alldayH  = document.getElementById('forcal_full_time');

    if (!startUI || !endUI || !startH || !endH) return;
    if (typeof AirDatepicker === 'undefined') {
      console.warn('forcal-datepicker: AirDatepicker nicht geladen.');
      return;
    }

    var locale   = getLocale();
    var isAllDay = alldayCb ? alldayCb.checked : false;
    var endPicker = null, startPicker = null;

    var baseOpts = {
      locale: locale,
      timepicker: !isAllDay,
      dateFormat: locale.dateFormat,
      timeFormat: locale.timeFormat,
      isMobile: false,
      autoClose: true,
      position: 'bottom left',
    };

    startPicker = new AirDatepicker(startUI, Object.assign({}, baseOpts, {
      onSelect: function (opts) {
        var date = Array.isArray(opts.date) ? opts.date[0] : opts.date;
        if (!date) return;
        startH.value = toDbDatetime(date);
        if (endPicker) endPicker.update({ minDate: date });
        // Enddatum darf nicht vor Startdatum liegen
        var endDate = endPicker && endPicker.selectedDates[0];
        if (endDate && endDate < date) {
          endPicker.selectDate(date, { silent: false });
        }
      },
    }));

    endPicker = new AirDatepicker(endUI, Object.assign({}, baseOpts, {
      onSelect: function (opts) {
        var date = Array.isArray(opts.date) ? opts.date[0] : opts.date;
        if (!date) return;
        // Enddatum darf nicht vor Startdatum liegen
        var startDate = startPicker && startPicker.selectedDates[0];
        if (startDate && date < startDate) {
          date = new Date(startDate.getTime());
          endPicker.selectDate(date, { silent: true });
        }
        endH.value = toDbDatetime(date);
      },
    }));

    // Initialwerte setzen und SOFORT als MySQL-Format in hidden fields schreiben
    var initStart = fromDbValue(startH.value);
    var initEnd   = fromDbValue(endH.value);

    if (initStart) {
      startH.value = toDbDatetime(initStart);  // Immer MySQL-Format
      startPicker.selectDate(initStart, { silent: true });
      endPicker.update({ minDate: initStart });
    } else {
      startH.value = '';  // Garbage-Werte (0000-00-00) leeren
    }

    if (initEnd) {
      if (initStart && initEnd < initStart) {
        initEnd = new Date(initStart.getTime());
      }
      endH.value = toDbDatetime(initEnd);  // Immer MySQL-Format
      endPicker.selectDate(initEnd, { silent: true });
    } else {
      endH.value = '';  // Garbage-Werte leeren
    }

    // Click-Fallback (falls Focus-Event nicht zuverlaessig feuert)
    startUI.addEventListener('click', function () { startPicker.show(); });
    endUI.addEventListener('click',   function () { endPicker.show(); });

    // Form-Submit-Validierung: Startdatum ist Pflichtfeld
    var theForm = startUI.closest('form');
    if (theForm) {
      theForm.addEventListener('submit', function (e) {
        var val = startH.value;
        if (!val || val === '' || val.indexOf('0000') === 0) {
          e.preventDefault();
          e.stopPropagation();
          startPicker.show();
          startUI.classList.add('forcal-dt-required');
          var col = startUI.closest('.forcal-dt-col');
          if (col && !col.querySelector('.forcal-dt-err')) {
            var err = document.createElement('span');
            err.className = 'forcal-dt-err help-block text-danger';
            err.textContent = 'Bitte Startdatum und -uhrzeit wählen';
            col.appendChild(err);
          }
        } else {
          startUI.classList.remove('forcal-dt-required');
          var errEl = startUI.closest('.forcal-dt-col');
          var prevErr = errEl && errEl.querySelector('.forcal-dt-err');
          if (prevErr) prevErr.remove();
          // Enddatum fehlt: Startdatum als Enddatum übernehmen
          var eVal = endH.value;
          if (!eVal || eVal === '' || eVal.indexOf('0000') === 0) {
            endH.value = startH.value;
            var startD = startPicker.selectedDates[0];
            if (startD) { endPicker.selectDate(new Date(startD.getTime()), { silent: true }); }
          }
        }
      }, true);
    }

    // Ganztaegig-Toggle
    if (alldayCb) {
      alldayCb.addEventListener('change', function () {
        var checked = this.checked;
        if (alldayH) alldayH.value = checked ? '1' : '0';
        startPicker.update({ timepicker: !checked });
        endPicker.update({ timepicker: !checked });
        if (checked) {
          var sDate = startPicker.selectedDates[0];
          var eDate = endPicker.selectedDates[0];
          if (sDate) {
            sDate.setHours(0, 0, 0, 0);
            startH.value = toDbDatetime(sDate);
            startPicker.selectDate(sDate, { silent: true });
          }
          if (eDate) {
            eDate.setHours(0, 0, 0, 0);
            endH.value = toDbDatetime(eDate);
            endPicker.selectDate(eDate, { silent: true });
          }
        }
      });
    }

    window.forCalPickers = { start: startPicker, end: endPicker };
  }

  // --- Boot -------------------------------------------------------------------

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPickers);
  } else {
    initPickers();
  }

  window.forCalDatepicker = { init: initPickers };
})();
