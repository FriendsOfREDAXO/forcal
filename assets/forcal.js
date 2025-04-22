/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

$(function () {
    forcal_init();

    $(document).on('pjax:end', function () {
        forcal_init();
    });
    
    // Das rex:ready-Event entfernen, da es zu doppelten Initialisierungen führt
    // Der ColorPicker wird bereits über forcal_init() initialisiert
});

function forcal_init() {
    forcal_colorpalette_init();
    forcal_fullcalendar_init();
    forcal_flatpickr_init();
    forcal_format_type();
    forcal_fulltime();
    forcal_repeat();
    forcal_submit();
}

function forcal_submit() {
    let forcal_form = $('#rex-page-forcal-entries form'),
        forcal_clock = forcal_form.find('.forcalclock input'),
        forcal_check = $('.forcal_fulltime_master_check'),
        forcal_date = forcal_form.find('.forcaldate input');

    if (forcal_form.length) {
        forcal_form.on('submit', function () {
            let go = true;

            forcal_clockEmptyNull();

            // TODO later with https://sweetalert.js.org
            // forcal_date.each(function(){
            //     if ($(this).val() == '0000-00-00' || !$(this).val()) {
            //         go = false;
            //
            //         return false;
            //     }
            // });
            //
            return go;
        });

        forcal_save_init(forcal_form);
    }
}

function forcal_format_type() {
    let checkbtn = $('.check-btn').parent(),
        radiobtn = $('.radio-btn').parent();

    if (radiobtn.length) {
        let parentradio = radiobtn.parents('.forcal-form-radioboxes-inline > dl'),
            radioelement = radiobtn.addClass('btn').addClass('btn-default').detach();

        parentradio.addClass('btn-group');
        parentradio.append(radioelement);
        parentradio.find('dt').remove();
        parentradio.find('dd').remove();

    }
    if (checkbtn.length) {
        let parentcheck = checkbtn.parents('.forcal-form-checkboxes-inline > dl'),
            checkelement = checkbtn.addClass('btn').addClass('btn-default').detach();

        parentcheck.addClass('btn-group');
        parentcheck.append(checkelement);
        parentcheck.find('dt').remove();
        parentcheck.find('dd').remove();
    }
}

function GetQueryStringParams(sParam) {
    let sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&');

    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}


function forcal_clockEmptyNull() {
    let forcal_clock = $('td.forcalclock input'),
        forcal_check = $('.forcal_fulltime_master_check'),
        count = 0,
        func = GetQueryStringParams("func");

    forcal_clock.each(function () {
        if ($(this).val() == '00:00:00' || (!$(this).val() && func == 'edit')) {
            count++;
        }
    });

    if (count >= 2 && func != 'add') {
        forcal_check.attr('checked', 'checked');
        forcal_fulltime_trigger(1);
    }
}

function forcal_fulltime() {
    let forcal_check = $('.forcal_fulltime_master_check');

    if (forcal_check.length) {
        forcal_clockEmptyNull();

        forcal_check.each(function () {
            if ($(this).is(':checked')) {
                forcal_fulltime_trigger(1);
            }
        });
        forcal_check.on('change', function () {
            if ($(this).is(':checked')) {
                forcal_fulltime_trigger(1);
            } else {
                forcal_fulltime_trigger(0);
            }
        });
    }
}

function forcal_fulltime_trigger(type) {
    let forcal_clock = $('td.forcalclock'),
        forcal_date = $('td.forcaldate');

    if (type == 1) {
        forcal_clock.hide();
        forcal_clock.find('input').val('00:00:00');
        forcal_date.addClass('only');
    } else {
        forcal_clock.show();
        forcal_date.removeClass('only');
    }
}

function forcal_repeat() {
    let select = $('.forcal_repeat_select'),
        radio = $('.forcal_repeat_master_radio');

    // master radio
    if (radio.length) {
        radio.each(function () {
            if ($(this).is(':checked')) {
                if ($(this).val() == 'repeat') {
                    forcal_repeat_radio('show');
                } else {
                    forcal_repeat_radio($(this).val());
                }
            }
        });
        radio.on('change', function () {
            forcal_repeat_radio($(this).val());
        });
    }

    if (select.length) {
        // weekly daily yearly select
        select.find('option').each(function () {
            if ($(this).is(':selected')) {
                forcal_repeat_select($(this).val());
            }
        });
        select.on('hidden.bs.select', function () {
            if ($(this).val() != '') {
                forcal_repeat_select($(this).val());
            }
        });
    }
}

function forcal_repeat_radio(type) {
    let panel = $('.forcal_repeats_show');

    if (type == 'show') {
        panel.addClass('in');
    }
    if (type == 'repeat') {
        panel.collapse("show");
    }
    if (type == 'one_time') {
        panel.collapse("hide");
    }
}

function forcal_repeat_select(type) {
    let panel = $('.forcal_repeat_show'),
        viewelements = $('.forcal_repeat_view_element');

    if (type == 'chose') {
        // panel.collapse("hide");
        panel.hide();
    }
    if (type == 'weekly' || type == 'yearly' || type == 'monthly' || type == 'monthly-week') {
        // panel.collapse("show");
        panel.show();
        viewelements.addClass('hidden');
        $('.view-' + type).removeClass('hidden');
    }
}

function forcal_fullcalendar_init() {
    let forcal = $('#forcal');

    if (forcal.length) {
        // CSS-Variablen als globale Farbdefinitionen hinzufügen
        document.documentElement.style.setProperty('--forcal-primary-color', '#3788d8');
        document.documentElement.style.setProperty('--forcal-primary-light', 'rgba(55, 136, 216, 0.2)');
        document.documentElement.style.setProperty('--forcal-primary-dark', '#1c62b3');
        document.documentElement.style.setProperty('--forcal-text-light', '#ffffff');
        document.documentElement.style.setProperty('--forcal-text-dark', '#111111'); // Dunkler gemacht (war #333333)
        
        // CSS-Variable zum Speichern der Event-Farbe als RGB-Wert hinzufügen
        document.addEventListener('DOMContentLoaded', function() {
            // Diese Funktion konvertiert einen HEX-Farbcode in RGB-Format
            function hexToRgb(hex) {
                // Kürze #fff zu #ffffff
                if (hex.length === 4) {
                    hex = hex.replace(/[^#]/g, function(match) {
                        return match + match;
                    });
                }
                
                // Extrahiere die RGB-Komponenten
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                
                return [r, g, b].join(', ');
            }
            
            // Dark Mode Erkennung
            function isDarkModeEnabled() {
                return document.body.classList.contains('rex-theme-dark') || 
                      (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            }
            
            // Aktualisiere die Farbwerte basierend auf dem aktuellen Modus
            function updateThemeColors() {
                const darkMode = isDarkModeEnabled();
                document.documentElement.style.setProperty('--forcal-bg-color', darkMode ? '#1e1e1e' : '#ffffff');
                document.documentElement.style.setProperty('--forcal-text-color', darkMode ? '#f0f0f0' : '#111111'); // Dunkler gemacht (war #333333)
            }
            
            // Initial setzen
            updateThemeColors();
            
            // Bei Änderung des Modus (falls der Benutzer während der Sitzung wechselt)
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateThemeColors);
            
            // Füge Event-Listener für alle Termine hinzu, um RGB-Farben zu extrahieren
            document.addEventListener('mouseover', function(e) {
                let target = e.target;
                
                // Finde das nächstliegende Event-Element
                while (target && !target.classList.contains('fc-event')) {
                    target = target.parentNode;
                    if (!target || target === document) return;
                }
                
                // Extrahiere die Hintergrund- oder Rahmenfarbe
                let eventColor = window.getComputedStyle(target).borderColor || 
                                window.getComputedStyle(target).backgroundColor || 
                                '#3788d8'; // Standard blau als Fallback
                
                // Konvertiere die Farbe zu RGB und setze sie als CSS-Variable
                if (eventColor) {
                    // Wenn die Farbe als RGB oder RGBA kommt, extrahiere die RGB-Komponenten
                    let rgbMatch = eventColor.match(/rgba?\(([^)]+)\)/);
                    if (rgbMatch && rgbMatch[1]) {
                        // RGB-Werte bereits im Format "r, g, b"
                        let rgbValues = rgbMatch[1].split(',').slice(0, 3).join(',');
                        target.style.setProperty('--event-color-rgb', rgbValues);
                    } else if (eventColor.startsWith('#')) {
                        // HEX-Farbe zu RGB konvertieren
                        target.style.setProperty('--event-color-rgb', hexToRgb(eventColor));
                    }
                }
            }, true);
        });
        
        forcal_fullcalendar(forcal);
    }
}

function forcal_flatpickr_init() {
    let dpd1 = $('#dpd1');
    let dpd2 = $('#dpd2');
    let dpd2b = $('#dpd2b');
    let tpd1 = $('#tpd1');
    let tpd2 = $('#tpd2');

    // Benötigte Localisierung für flatpickr (Deutsch)
    const German = {
        weekdays: {
            shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
            longhand: [
                "Sonntag",
                "Montag",
                "Dienstag",
                "Mittwoch",
                "Donnerstag",
                "Freitag",
                "Samstag",
            ],
        },
        months: {
            shorthand: [
                "Jan",
                "Feb",
                "Mär",
                "Apr",
                "Mai",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Okt",
                "Nov",
                "Dez",
            ],
            longhand: [
                "Januar",
                "Februar",
                "März",
                "April",
                "Mai",
                "Juni",
                "Juli",
                "August",
                "September",
                "Oktober",
                "November",
                "Dezember",
            ],
        },
        firstDayOfWeek: 1,
        weekAbbreviation: "KW",
        rangeSeparator: " bis ",
        scrollTitle: "Zum Ändern scrollen",
        toggleTitle: "Zum Umschalten klicken",
        time_24hr: true,
    };

    // Sprache basierend auf der REDAXO-Sprache
    const currentLang = $('html').attr('lang') || 'de';
    const localeToUse = currentLang === 'de' ? German : null;

    // Funktion zur Validierung des Zeitraums
    function validateDateTime() {
        const fullTimeChecked = $('.forcal_fulltime_master_check').is(':checked');
        if (fullTimeChecked) {
            // Bei ganztägigen Terminen nur die Daten vergleichen
            return true;
        }

        const startDate = dpd1.val();
        const endDate = dpd2.val();
        const startTime = tpd1.val();
        const endTime = tpd2.val();

        // Wenn eines der Felder leer ist, können wir nicht validieren
        if (!startDate || !endDate || !startTime || !endTime) {
            return true;
        }

        // Erstellen von DateTime-Objekten für Start und Ende
        const startDateTime = new Date(`${startDate}T${startTime}`);
        const endDateTime = new Date(`${endDate}T${endTime}`);

        // Validierung: Endzeit muss nach Startzeit sein
        return endDateTime >= startDateTime;
    }

    // Fehlermeldung für ungültige Zeitangaben
    function showTimeValidationError() {
        const errorMessage = currentLang === 'de' 
            ? 'Die Endzeit muss nach der Startzeit liegen!' 
            : 'End time must be after start time!';
            
        // Füge die Fehlermeldung hinzu, wenn sie noch nicht existiert
        if ($('#time-validation-error').length === 0) {
            $('<div id="time-validation-error" class="alert alert-danger">' + errorMessage + '</div>')
                .insertBefore('#forcal-submit-btn');
        }
    }

    // Entfernen der Fehlermeldung
    function removeTimeValidationError() {
        $('#time-validation-error').remove();
    }

    // Validierungsprüfung bei Formularabsendung
    $('#rex-page-forcal-entries form').on('submit', function(e) {
        if (!validateDateTime()) {
            e.preventDefault();
            showTimeValidationError();
            return false;
        }
        removeTimeValidationError();
        return true;
    });

    // Datum-Picker für Start- und Enddatum
    let startPicker, endPicker, timepicker1, timepicker2;
    
    if (dpd1.length && dpd2.length) {
        // Startdatum-Picker
        startPicker = flatpickr(dpd1[0], {
            dateFormat: "Y-m-d",
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Entferne Validierungsfehler bei Änderungen
                removeTimeValidationError();
                
                // Stellen Sie sicher, dass das Enddatum nicht vor dem Startdatum liegt
                if (selectedDates[0]) {
                    endPicker.set('minDate', selectedDates[0]);
                    
                    // Wenn das Enddatum vor dem neuen Startdatum liegt, setze es auf das Startdatum
                    if (endPicker.selectedDates[0] && selectedDates[0] > endPicker.selectedDates[0]) {
                        endPicker.setDate(selectedDates[0]);
                    }
                    
                    // Validiere die Zeiten, wenn Start- und Enddatum gleich sind
                    if (dpd2.val() === dateStr) {
                        validateAndUpdateTimes();
                    }
                }
            }
        });

        // Enddatum-Picker
        endPicker = flatpickr(dpd2[0], {
            dateFormat: "Y-m-d",
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Entferne Validierungsfehler bei Änderungen
                removeTimeValidationError();
                
                // Wenn Start- und Enddatum gleich sind, validiere die Zeiten
                if (dpd1.val() === dateStr) {
                    validateAndUpdateTimes();
                }
            }
        });

        // Initialisierung der aktuellen Werte
        if (dpd1.val()) {
            startPicker.setDate(dpd1.val());
        }
        
        if (dpd2.val()) {
            endPicker.setDate(dpd2.val());
        }
    }

    // Datum-Picker für Wiederholungs-Enddatum
    if (dpd2b.length) {
        flatpickr(dpd2b[0], {
            dateFormat: "Y-m-d",
            locale: localeToUse,
            allowInput: true
        });
    }

    // Funktion zur Validierung und Aktualisierung der Zeiten
    function validateAndUpdateTimes() {
        const fullTimeChecked = $('.forcal_fulltime_master_check').is(':checked');
        if (fullTimeChecked) {
            // Bei ganztägigen Terminen nichts tun
            return;
        }

        const startTime = tpd1.val();
        const endTime = tpd2.val();
        
        // Wenn eines der Felder leer ist, können wir nicht validieren
        if (!startTime || !endTime) {
            return;
        }
        
        // Vergleichen der Zeiten, wenn Start- und Enddatum identisch sind
        if (dpd1.val() === dpd2.val() && startTime >= endTime) {
            // Berechne eine neue Endzeit (eine Stunde nach Startzeit)
            const startTimeParts = startTime.split(':');
            let newHour = parseInt(startTimeParts[0]) + 1;
            if (newHour >= 24) {
                newHour = 23;
                startTimeParts[1] = "59";
            }
            const newEndTime = `${newHour.toString().padStart(2, '0')}:${startTimeParts[1]}:${startTimeParts[2] || '00'}`;
            
            // Setze die neue Endzeit
            if (timepicker2) {
                timepicker2.setDate(newEndTime);
            }
        }
    }

    // Zeit-Picker für Startzeit
    if (tpd1.length) {
        timepicker1 = flatpickr(tpd1[0], {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i:S",
            time_24hr: true,
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Entferne Validierungsfehler bei Änderungen
                removeTimeValidationError();
                
                // Überprüfen ob Start- und Enddatum identisch sind
                if (dpd1.val() === dpd2.val()) {
                    validateAndUpdateTimes();
                }
            }
        });
    }

    // Zeit-Picker für Endzeit
    if (tpd2.length) {
        timepicker2 = flatpickr(tpd2[0], {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i:S",
            time_24hr: true,
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Entferne Validierungsfehler bei Änderungen
                removeTimeValidationError();
                
                // Validiere die Zeitangaben bei manueller Eingabe
                if (dpd1.val() === dpd2.val()) {
                    const startTime = tpd1.val();
                    if (startTime && dateStr && startTime >= dateStr) {
                        // Entferne den Fokus, um die onBlur-Validierung anzustoßen
                        setTimeout(() => {
                            validateAndUpdateTimes();
                        }, 100);
                    }
                }
            }
        });
    }

    // Bindet die Datepicker-Trigger an
    $('.forcal-date-input').off('click').on('click', function() {
        // Findet das nächste Input-Feld und triggert den Flatpickr
        const inputField = $(this).closest('.input-group').find('input');
        if (inputField.length) {
            inputField[0]._flatpickr.open();
        }
    });

    // Bindet die Timepicker-Trigger an
    $('.forcal-time-input').off('click').on('click', function() {
        // Findet das nächste Input-Feld und triggert den Flatpickr
        const inputField = $(this).closest('.input-group').find('input');
        if (inputField.length) {
            inputField[0]._flatpickr.open();
        }
    });
    
    // Event-Handler für den ganztägigen Events-Checker
    $('.forcal_fulltime_master_check').on('change', function() {
        // Entferne Validierungsfehler bei Änderungen
        removeTimeValidationError();
    });
}

function forcal_colorpalette_init() {
    let input = $('.forcal_colorpalette');

    if (input.length) {
        // Überprüfen, ob das Element bereits einen ColorPicker hat, um doppelte Instanzen zu vermeiden
        input.each(function() {
            // Entferne vorhandene ColorPicker-Instanzen (falls vorhanden)
            const parent = $(this).parent();
            if (parent.hasClass('forcal-colorpicker-wrapper') || parent.hasClass('palette-color-picker-button')) {
                return; // Überspringen, wenn bereits initialisiert
            }
            
            // Initialisiere mit dem jQuery-Adapter
            $(this).paletteColorPicker({
                clear_btn: 'last'
            });
        });
    }
}

function forcal_fullcalendar(forcal) {
    let forcal_locale = forcal.data('locale'),
        forcal_date = forcal.data('date'),
        csrf_token = forcal.data('csrf'),
        calendarEl = document.getElementById(forcal.attr('id'));

    // Parameter aus der URL auslesen, um die Filter zu erfassen
    function getUrlParameters() {
        let params = {};
        let queryString = window.location.search.substring(1);
        let pairs = queryString.split('&');
        
        for(let i = 0; i < pairs.length; i++) {
            if (!pairs[i]) continue;
            
            let pair = pairs[i].split('=');
            if (pair.length < 2) continue;
            
            let key = decodeURIComponent(pair[0]);
            let value = decodeURIComponent(pair[1]);
            
            // Die Kategorie-Filter sind als Array gespeichert ('user_filter[]')
            if (key === 'user_filter[]') {
                if (!params['category']) {
                    params['category'] = [];
                }
                params['category'].push(value);
            } else if (key === 'show_all') {
                params[key] = value;
            }
        }
        
        return params;
    }
    
    // URL-Parameter abrufen
    let urlParams = getUrlParameters();

    // FullCalendar 6.x-Kompatibilität
    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth', // FullCalendar 6.x verwendet initialView statt defaultView
        headerToolbar: { // FullCalendar 6.x verwendet headerToolbar statt header
            left: 'prev,next today',
            center: 'title',
            right: 'newEvent dayGridMonth,timeGridWeek,timeGridDay'
        },
        customButtons: {
            newEvent: {
                text: '+',
                click: function() {
                    // Bei Klick zur Termin-hinzufügen-Seite mit aktuellem Datum navigieren
                    const currentDate = new Date().toISOString().slice(0, 10); // Format: YYYY-MM-DD
                    window.location.href = 'index.php?page=forcal/entries&func=add&itemdate=' + currentDate;
                }
            }
        },
        locale: forcal_locale,
        weekNumbers: true,
        weekNumbersWithinDays: true,
        dragScroll: true,
        initialDate: forcal_date, // FullCalendar 6.x verwendet initialDate statt defaultDate
        dayMaxEvents: true, // FullCalendar 6.x verwendet dayMaxEvents statt eventLimit
        dateClick: function(info) {
            // Beim Klick auf einen Tag/Zelle in eine neue Termin-Seite navigieren
            window.location.href = 'index.php?page=forcal/entries&func=add&itemdate=' + info.dateStr;
        },
        eventClick: function (info) {
            window.location.replace('index.php?page=forcal/entries&func=edit&id=' + info.event.id);
        },
        // In der Wochen- und Tagesansicht können Slots geklickt werden, um neue Termine anzulegen
        slotLabelClick: function(info) {
            // Extrahiere das Datum aus dem aktuell angezeigten Tag
            const date = calendar.getDate().toISOString().slice(0, 10);
            // Extrahiere die Uhrzeit aus dem geklickten Slot
            const time = info.date.toTimeString().slice(0, 8);
            window.location.href = 'index.php?page=forcal/entries&func=add&itemdate=' + date + '&itemtime=' + time;
        },
        eventDidMount: function (info) { // FullCalendar 6.x verwendet eventDidMount statt eventRender
            // Event wurde gerendert
            // Wenn das Event eine Kategoriefarbe hat, diese verwenden
            const eventColor = info.event.backgroundColor || info.event.extendedProps.color;
            if (eventColor) {
                // Farbe aus Event-Daten verwenden
                const isAllDay = info.event.allDay;
                
                // Utility-Funktionen
                // Funktion zur Verarbeitung eines Farbwerts in RGB-Komponenten
                function extractRGB(colorValue) {
                    let r = 55, g = 136, b = 216; // Standardwerte falls Extraktion fehlschlägt
                    
                    if (typeof colorValue === 'string') {
                        // RGBA-Format prüfen und extrahieren
                        const rgbaMatch = colorValue.match(/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,?\s*[0-9.]*\s*\)/i);
                        if (rgbaMatch) {
                            r = parseInt(rgbaMatch[1], 10);
                            g = parseInt(rgbaMatch[2], 10);
                            b = parseInt(rgbaMatch[3], 10);
                        } else if (colorValue.startsWith('#')) {
                            // HEX-Format zu RGB konvertieren
                            let cleanHex = colorValue;
                            if (cleanHex.length === 4) {
                                cleanHex = '#' + cleanHex[1] + cleanHex[1] + cleanHex[2] + cleanHex[2] + cleanHex[3] + cleanHex[3];
                            }
                            
                            r = parseInt(cleanHex.slice(1, 3), 16) || 0;
                            g = parseInt(cleanHex.slice(3, 5), 16) || 0;
                            b = parseInt(cleanHex.slice(5, 7), 16) || 0;
                        }
                    }
                    
                    // Begrenze auf gültige RGB-Werte
                    return {
                        r: Math.min(255, Math.max(0, r)),
                        g: Math.min(255, Math.max(0, g)),
                        b: Math.min(255, Math.max(0, b))
                    };
                }
                
                // Funktion zur Erzeugung einer transparenten Pastellversion der Farbe
                function createPastelColor(colorValue, opacity = 0.2) {
                    const rgb = extractRGB(colorValue);
                    
                    // Erstelle hellere Pastellversion (mische mit Weiß)
                    const r = Math.floor(rgb.r + (255 - rgb.r) * 0.5);
                    const g = Math.floor(rgb.g + (255 - rgb.g) * 0.5);
                    const b = Math.floor(rgb.b + (255 - rgb.b) * 0.5); // Korrigiert: rgb.b statt b
                    
                    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
                }
                
                // Funktion zur Erzeugung einer dunkleren Version der Farbe für Dark Mode
                function createDarkerColor(colorValue) {
                    const rgb = extractRGB(colorValue);
                    
                    // Mache die Farbe dunkler (reduziere Helligkeit)
                    const r = Math.floor(rgb.r * 0.7);
                    const g = Math.floor(rgb.g * 0.7);
                    const b = Math.floor(rgb.b * 0.7);
                    
                    // Konvertiere zurück zu HEX
                    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
                }
                
                // Dark Mode Erkennung
                const isDarkMode = document.body.classList.contains('rex-theme-dark');
                const isPreferDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const shouldUseDarkMode = isDarkMode || isPreferDark;
                
                // RGB-Komponenten für CSS-Variablen extrahieren
                const rgb = extractRGB(eventColor);
                
                // Setze die RGB-CSS-Variablen am Event-Element
                // Diese können dann in CSS für verschiedene Eigenschaften verwendet werden
                info.el.style.setProperty('--event-color-r', rgb.r);
                info.el.style.setProperty('--event-color-g', rgb.g);
                info.el.style.setProperty('--event-color-b', rgb.b);
                info.el.style.setProperty('--event-color-rgb', `${rgb.r}, ${rgb.g}, ${rgb.b}`);
                
                // Hintergrund- und Textfarbe basierend auf Modus und Eventtyp
                let bgColor, textColor, dotColor;
                
                if (isAllDay) {
                    // Für ganztägige Termine
                    dotColor = eventColor; // Original Eventfarbe für den Punktindikator
                    
                    // Im Dark Mode stärkere Pastellfarben für bessere Sichtbarkeit
                    bgColor = shouldUseDarkMode ? 
                              createPastelColor(eventColor, 0.5) : // Höhere Deckkraft im Dark Mode
                              createPastelColor(eventColor, 0.4);  // Normale Deckkraft im Light Mode
                              
                    // Text-Farbe abhängig vom Modus
                    textColor = shouldUseDarkMode ? "#ffffff" : "#333333";
                } else {
                    // Für normale Termine
                    dotColor = eventColor; // Original Eventfarbe für den Punktindikator
                    
                    if (info.el.classList.contains('fc-timegrid-event')) {
                        // Termine in der Wochen-/Tagesansicht
                        bgColor = shouldUseDarkMode ? 
                                  createPastelColor(eventColor, 0.25) : // Dezentere Farbe im Dark Mode
                                  createPastelColor(eventColor, 0.15);  // Sehr dezent im Light Mode
                    } else {
                        // Termine in der Monatsansicht 
                        bgColor = shouldUseDarkMode ? 
                                  createPastelColor(eventColor, 0.3) : // Etwas stärker im Dark Mode
                                  createPastelColor(eventColor, 0.2);  // Dezent im Light Mode
                    }
                    
                    // Text-Farbe abhängig vom Modus
                    textColor = shouldUseDarkMode ? "#ffffff" : "#333333";
                }
                
                // Wende die Stile auf das Event-Element an
                info.el.style.backgroundColor = bgColor;
                info.el.style.borderColor = 'transparent';
                info.el.style.boxShadow = 'none';
                
                // Punktindikator für die Kategorie hinzufügen oder aktualisieren
                const titleEl = info.el.querySelector('.fc-event-title');
                if (titleEl) {
                    titleEl.style.color = textColor;
                    
                    // Füge den Kategorie-Punktindikator hinzu, wenn er noch nicht existiert
                    if (!titleEl.querySelector('.forcal-category-dot')) {
                        const dot = document.createElement('span');
                        dot.className = 'forcal-category-dot';
                        dot.style.backgroundColor = dotColor;
                        dot.style.display = 'inline-block';
                        dot.style.width = '8px';
                        dot.style.height = '8px';
                        dot.style.borderRadius = '50%';
                        dot.style.marginRight = '5px';
                        dot.style.marginBottom = '1px';
                        titleEl.insertBefore(dot, titleEl.firstChild);
                    }
                }
                
                // Zeitanzeige anpassen
                const timeEl = info.el.querySelector('.fc-event-time');
                if (timeEl) {
                    timeEl.style.color = textColor;
                }
                
                // Stelle sicher, dass alle sonstigen Text-Elemente die korrekte Farbe haben
                const allTextElements = info.el.querySelectorAll('.fc-event-title-container, .fc-event-main, .fc-event-title, .fc-sticky');
                allTextElements.forEach(element => {
                    element.style.color = textColor;
                });
                
                // Behandlung von Punkt-Events (dot events) in der Monatsansicht
                const dotEventEl = info.el.querySelector('.fc-daygrid-event-dot');
                if (dotEventEl) {
                    dotEventEl.style.borderColor = dotColor;
                }
            }
        },
        datesSet: function (info) { // FullCalendar 6.x verwendet datesSet statt datesRender
            if (info.view.type === 'dayGridMonth') {
                addAddIconMonth(forcal);
            }
            if (info.view.type === 'timeGridWeek') {
                addAddIconWeek(forcal);
            }
            if (info.view.type === 'timeGridDay') {
                addAddIconDay(forcal);
            }
        },
        viewDidMount: function (info) { // FullCalendar 6.x verwendet viewDidMount statt viewSkeletonRender
            // View wurde gemountet
        },
        events: {
           url: rex.forcal_events_api_url,
           extraParams: function() {
                let params = {};
                
                // Wenn Kategorien ausgewählt sind, übergeben wir sie als kommagetrennte Liste
                if (urlParams.category && urlParams.category.length > 0) {
                    params.category = urlParams.category.join(',');
                }
                
                return params;
            },
            failure: function (xhr, type, exception) { // FullCalendar 6.x verwendet failure statt error
                console.error("Kalender-API Fehler:", xhr, type, exception);
            }
        },
    });

    // Nach dem Rendern des Kalenders Event-Handler für Klicks hinzufügen
    calendar.render();
    
    // Füge Klick-Handler für Zeitslots in der Wochenansicht hinzu
    setTimeout(function() {
        // Für Zeitslots in der Wochen- und Tagesansicht
        forcal.find('.fc-timegrid-slot').css('cursor', 'pointer').on('click', function() {
            // Finde das Datum der entsprechenden Spalte
            const columnDate = $(this).closest('.fc-timegrid-col').attr('data-date');
            // Extrahiere die Uhrzeit aus dem Slot
            const slotTime = $(this).attr('data-time');
            
            if (columnDate && slotTime) {
                window.location.href = 'index.php?page=forcal/entries&func=add&itemdate=' + columnDate + '&itemtime=' + slotTime;
            }
        });
        
        // Für Datumszellen in der Monatsansicht (falls dateClick nicht funktioniert)
        forcal.find('.fc-daygrid-day').css('cursor', 'pointer');
    }, 500);
}

function addAddIconDay(forcal) {
    return addAddIconWeek(forcal);
}

function addAddIconWeek(forcal) {
    // FullCalendar 6.x verwendet .fc-col-header-cell statt .fc-day-header
    if (forcal.find('.fc-col-header-cell').length) {
        forcal.find('.fa-plus-circle').remove();
        forcal.find('.fc-col-header-cell').prepend('<i class="fa fa-plus-circle add"></i>');
        forcal.find('.fc-col-header-cell .fa-plus-circle').each(function () {
            let parent = $(this).parent();
            $(this).unbind().bind('click', function () {
                addEntryHandler(parent);
                return false;
            });
        });
    }
}

function addAddIconMonth(forcal) {
    // FullCalendar 6.x verwendet .fc-daygrid-day statt .fc-day-top
    if (forcal.find('.fc-daygrid-day').length) {
        forcal.find('.fa-plus-circle').remove();
        forcal.find('.fc-daygrid-day-top').prepend('<i class="fa fa-plus-circle add"></i>');
        forcal.find('.fc-daygrid-day-top .fa-plus-circle').each(function () {
            let parent = $(this).closest('.fc-daygrid-day');
            $(this).unbind().bind('click', function () {
                addEntryHandler(parent);
                return false;
            });
        });
    }
}

function addEntryHandler(item) {
    // Bei FullCalendar 6.x wird das Datum anders gespeichert
    let date;
    if (item.data('date')) {
        // Altes Format
        date = item.data('date');
    } else {
        // Neues Format in FullCalendar 6.x
        date = item.attr('data-date');
    }
    window.location.replace('index.php?page=forcal/entries&func=add&itemdate=' + date);
}

function forcal_save_init(forcal_form) {
    if (rex.forcal_shortcut_save) {
        $(window).bind('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && String.fromCharCode(event.which).toLowerCase() === 's') {
                if (forcal_form[0].checkValidity()) {
                    forcal_form.find('button[type=submit]').trigger('click');
                } else {
                    forcal_form[0].reportValidity();
                }
            }
        });
    }
}
