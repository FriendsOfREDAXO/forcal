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

    // Datum-Picker für Start- und Enddatum
    if (dpd1.length && dpd2.length) {
        // Startdatum-Picker
        const startPicker = flatpickr(dpd1[0], {
            dateFormat: "Y-m-d",
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Stellen Sie sicher, dass das Enddatum nicht vor dem Startdatum liegt
                if (selectedDates[0]) {
                    endPicker.set('minDate', selectedDates[0]);
                    
                    // Wenn das Enddatum vor dem neuen Startdatum liegt, setze es auf das Startdatum
                    if (endPicker.selectedDates[0] && selectedDates[0] > endPicker.selectedDates[0]) {
                        endPicker.setDate(selectedDates[0]);
                    }
                }
            }
        });

        // Enddatum-Picker
        const endPicker = flatpickr(dpd2[0], {
            dateFormat: "Y-m-d",
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Optional: Stellen Sie sicher, dass das Startdatum nicht nach dem Enddatum liegt
                if (selectedDates[0]) {
                    startPicker.set('maxDate', selectedDates[0]);
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

    // Zeit-Picker für Start- und Endzeit
    if (tpd1.length) {
        flatpickr(tpd1[0], {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i:S",
            time_24hr: true,
            locale: localeToUse,
            allowInput: true,
            onChange: function(selectedDates, dateStr, instance) {
                // Überprüfen auf ganztägig-Status
                const fullTimeChecked = $('.forcal_fulltime_master_check').is(':checked');
                if (!fullTimeChecked && tpd2.length && dpd1.val() === dpd2.val()) {
                    // Wenn Start- und Enddatum identisch sind, stelle sicher, dass Endzeit nicht vor Startzeit liegt
                    const startTime = dateStr;
                    const endTime = tpd2.val() || '00:00:00';
                    
                    if (startTime > endTime) {
                        tpd2FlatpickrInstance.setDate(startTime);
                    }
                }
            }
        });
    }

    // Zeit-Picker für Endzeit
    let tpd2FlatpickrInstance;
    if (tpd2.length) {
        tpd2FlatpickrInstance = flatpickr(tpd2[0], {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i:S",
            time_24hr: true,
            locale: localeToUse,
            allowInput: true
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
}

function forcal_colorpalette_init() {
    let input = $('.forcal_colorpalette');

    if (input.length) {
        input.paletteColorPicker({
            clear_btn: 'last'
        }).unbind().bind('click', function () {
            $(this).parent().first();
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

    let calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: ['interaction', 'dayGrid', 'timeGrid'],
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: forcal_locale,
        weekNumbers: true,
        weekNumbersWithinDays: true,
        dragScroll: true,
        defaultDate: forcal_date,
        eventLimit: true,
        eventClick: function (info) {
            window.location.replace('index.php?page=forcal/entries&func=edit&id=' + info.event.id);
        },
        eventRender: function (info) {
        },
        datesRender: function (info) {
            if (info.view.viewSpec.type === 'dayGridMonth') {
                addAddIconMonth(forcal);
            }
            if (info.view.viewSpec.type === 'timeGridWeek') {
                addAddIconWeek(forcal);
            }
            if (info.view.viewSpec.type === 'timeGridDay') {
                addAddIconDay(forcal);
            }
        },
        viewSkeletonRender: function (info) {
        },
        events: {
           url: rex.forcal_events_api_url,
           extraParams: function() {
                let params = {};
                
                // Einfache Lösung: Wenn Kategorien ausgewählt sind, übergeben wir sie als kommagetrennte Liste
                if (urlParams.category && urlParams.category.length > 0) {
                    params.category = urlParams.category.join(',');
                }
                
                return params;
            },
            cache: false, // Cache deaktivieren, um sicherzustellen, dass Änderungen sofort wirken
            error: function (xhr, type, exception) {
                console.error("Kalender-API Fehler:", xhr, type, exception);
            }
        },
    });

    calendar.render();
}

function addAddIconDay(forcal) {
    return addAddIconWeek(forcal);
}

function addAddIconWeek(forcal) {
    if (forcal.find('.fc-day-header').length) {
        forcal.find('.fa-plus-circle').remove();
        forcal.find('.fc-day-header').prepend('<i class="fa fa-plus-circle add"></i>');
        forcal.find('.fc-day-header .fa-plus-circle').each(function () {
            let parent = $(this).parent();
            $(this).unbind().bind('click', function () {
                addEntryHandler(parent);
                return false;
            });
        });
    }
}

function addAddIconMonth(forcal) {
    if (forcal.find('.fc-day-top').length) {
        forcal.find('.fa-plus-circle').remove();
        forcal.find('.fc-day-top').prepend('<i class="fa fa-plus-circle add"></i>');
        forcal.find('.fc-day-top .fa-plus-circle').each(function () {
            $(this).parent().unbind().bind('click', function () {
                addEntryHandler($(this));
                return false;
            });
        });
    }
}

function addEntryHandler(item) {
    window.location.replace('index.php?page=forcal/entries&func=add&itemdate=' + item.data('date'));
}


function forcal_save_init(forcal_form) {
    if (rex.forcal_shortcut_save) {
        $(window).bind('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && String.fromCharCode(event.which).toLowerCase() === 's') {
                event.preventDefault();
                event.stopImmediatePropagation();

                if (forcal_form[0].checkValidity()) {
                    forcal_form.find('button[type=submit]').trigger('click');
                } else {
                    forcal_form[0].reportValidity();
                }
            }
        });
    }
}
