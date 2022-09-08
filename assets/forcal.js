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
    forcal_daterange_init();
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

function forcal_daterange_init() {
    let forcal_datePicker = $('.forcaldatepicker'),
        forcal_tabs = $('.forcal_clangtabs');

    if (forcal_datePicker.length) {
        forcal_datepicker_init(forcal_datePicker);
    }
    if (forcal_tabs.length) {
        forcal_tabs_init(forcal_tabs);
    }
}

function forcal_tabs_init(element) {
    element.click(function (e) {
        e.preventDefault();
        $(this).tab('show')
    });
}

function forcal_datepicker_init(element) {
    let checkinElement = $('#dpd1'),
        checkoutElement = $('#dpd2'),
        stopElement = $('#dpd2b'),
        checkinClockElement = $('#tpd1').parent(),
        checkoutClockElement = $('#tpd2').parent();

    if (element.hasClass('lang_de')) {
        moment.locale('de', {
                months: "Januar,Februar,März,April,May,Juni,Juli,August,September,Oktober,November,Dezember".split(","),
                monthsShort: "Jan_Feb_Mrz_Apr_May_Jun_Jul_Aug_Sep_Okt_Nov_Dez".split("_"),
                weekdays: "Domingo,Lunes,Martes,Miercoles,Jueves,Viernes,Sabado".split(","),
                weekdaysShort: "dom._lun._mar._mie._jue._vie._sab.".split("_"),
                weekdaysMin: "So_Mo_Di_Mi_Do_Fr_Sa".split("_")
            }
        );
        var $applyLabel = "Anwenden",
            $cancelLabel = "Abrechen",
            $fromLabel = "Von",
            $toLabel = "bis",
            $endbefore = "Das Ende des Termins darf nicht vor dessen Begin liegen.";
    } else {
        var $applyLabel = "Apply",
            $cancelLabel = "Cancel",
            $fromLabel = "From",
            $toLabel = "To",
            $endbefore = "Das Ende des Termins darf nicht vor dessen Begin liegen.";
    }

    if (stopElement.length) {
        stopElement.daterangepicker({
            autoUpdateInput: true,
            singleDatePicker: true,
            showDropdowns: true,
            locale: {
                format: "YYYY-MM-DD",
                applyLabel: $applyLabel,
                cancelLabel: $cancelLabel,
                fromLabel: $fromLabel,
                toLabel: $toLabel,
                firstDay: 1,
                direction: 'forcal',
            }
        });
    }

    if (checkinElement.length && checkoutElement.length) {
        checkoutClockElement.data('reopend', false);
        checkinElement.daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: "YYYY-MM-DD",
                applyLabel: $applyLabel,
                cancelLabel: $cancelLabel,
                fromLabel: $fromLabel,
                toLabel: $toLabel,
                firstDay: 1,
                direction: 'forcal',
            }
        });
        if (element.data('only-checkin-range') != 1) {
            checkoutElement.daterangepicker({
                autoUpdateInput: false,
                locale: {
                    format: "YYYY-MM-DD",
                    applyLabel: $applyLabel,
                    cancelLabel: $cancelLabel,
                    fromLabel: $fromLabel,
                    toLabel: $toLabel,
                    firstDay: 1,
                    direction: 'forcal',
                }
            });
        }
        if (checkinElement.val() != '') {
            checkinElement.data('daterangepicker').setStartDate(checkinElement.val());
            if (element.data('only-checkin-range') != 1) {
                checkoutElement.data('daterangepicker').setStartDate(checkinElement.val());

            }
        }
        if (checkoutElement.val() != '') {
            checkinElement.data('daterangepicker').setEndDate(checkoutElement.val());
            if (element.data('only-checkin-range') != 1) {
                checkoutElement.data('daterangepicker').setEndDate(checkoutElement.val());
            }
        }

        checkinClockElement.clockpicker({
            donetext: $applyLabel,
            afterDone: function () {
                if (checkinElement.val() == '') {
                    checkinElement.val(element.data('today'));
                }
                if (checkoutElement.val() == '') {
                    checkoutElement.val(element.data('today'));
                }

                checkinClockElement.find('input').val(checkinClockElement.find('input').val() + ':00');

                if (checkinElement.val() == checkoutElement.val()) {
                    if (checkoutClockElement.find('input').val() == '') {
                        checkoutClockElement.find('input').val('00:00:00');
                    }

                    var startDate = new Date(checkinElement.val() + 'T' + checkinClockElement.find('input').val());
                    var endDate = new Date(checkoutElement.val() + 'T' + checkoutClockElement.find('input').val());

                    if (startDate.getTime() > endDate.getTime()) {
                        checkoutClockElement.find('input').val(checkinClockElement.find('input').val());
                    }
                }

                window.setTimeout(function () {
                    if (checkoutClockElement.data('reopend') === false || startDate.getTime() == endDate.getTime()) {
                        checkoutClockElement.clockpicker('show');
                    } else {
                        checkoutClockElement.data('reopend', false);
                    }
                }, 1);
            }
        });

        checkoutClockElement.clockpicker({
            donetext: $applyLabel,
            afterDone: function () {
                if (checkinElement.val() == '') {
                    checkinElement.val(element.data('today'));
                }
                if (checkoutElement.val() == '') {
                    checkoutElement.val(element.data('today'));
                }

                checkoutClockElement.find('input').val(checkoutClockElement.find('input').val() + ':00');

                if (checkinElement.val() == checkoutElement.val()) {
                    if (checkinClockElement.find('input').val() == '') {
                        checkinClockElement.find('input').val('00:00:00');

                        window.setTimeout(function () {
                            checkoutClockElement.data('reopend', true);
                            checkinClockElement.clockpicker('show');
                        }, 1);
                    }
                }

                startDate = new Date(checkinElement.val() + 'T' + checkinClockElement.find('input').val());
                endDate = new Date(checkoutElement.val() + 'T' + checkoutClockElement.find('input').val());

                if (startDate.getTime() > endDate.getTime()) {
                    checkoutClockElement.find('input').val(checkinClockElement.find('input').val());
                    alert($endbefore);
                }
            }
        });

        checkinElement.parent().parent().parent().parent().find('.input-group-addon.forcal-date-input').unbind().bind('click', function () {
            checkinElement.focus();
        });
        stopElement.parent().parent().parent().parent().find('.input-group-addon.forcal-date-input').unbind().bind('click', function () {
            stopElement.focus();
        });
        checkoutElement.parent().parent().parent().parent().find('.input-group-addon.forcal-date-input').unbind().bind('click', function () {
            if (element.data('only-checkin-range') == 1) {
                checkinElement.focus();
            } else {
                checkoutElement.focus();
            }
        });
        if (element.data('only-checkin-range') == 1) {
            checkoutElement.unbind().bind('click', function () {
                checkinElement.focus();
            });
        } else {
            checkoutElement.on('apply.daterangepicker', function (ev, picker) {
                daterangepick(element, checkinElement, checkoutElement, checkinClockElement, checkoutClockElement, picker, $endbefore);
            });
        }
        checkinElement.on('apply.daterangepicker', function (ev, picker) {
            daterangepick(element, checkinElement, checkoutElement, checkinClockElement, checkoutClockElement, picker, $endbefore);
        });
    }
}

function daterangepick(element, checkinElement, checkoutElement, checkinClockElement, checkoutClockElement, picker, $endbefore) {
    if (checkinClockElement.find('input').val() == '') {
        checkinClockElement.find('input').val('00:00:00');
    }
    if (checkoutClockElement.find('input').val() == '') {
        checkoutClockElement.find('input').val('00:00:00');
    }

    let startDate = new Date(picker.startDate.format('YYYY-MM-DD') + 'T' + checkinClockElement.find('input').val()),
        endDate = new Date(picker.endDate.format('YYYY-MM-DD') + 'T' + checkoutClockElement.find('input').val());

    if (startDate.getTime() > endDate.getTime()) {
        alert($endbefore);
    } else {

        checkinElement.val(picker.startDate.format('YYYY-MM-DD'));
        checkoutElement.val(picker.endDate.format('YYYY-MM-DD'));

        if (element.data('only-checkin-range') != 1) {
            checkoutElement.data('daterangepicker').setStartDate(picker.startDate.format('YYYY-MM-DD'));
            checkoutElement.data('daterangepicker').setEndDate(picker.endDate.format('YYYY-MM-DD'));
            checkinElement.data('daterangepicker').setStartDate(picker.startDate.format('YYYY-MM-DD'));
            checkinElement.data('daterangepicker').setEndDate(picker.endDate.format('YYYY-MM-DD'));
        }
    }
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
        // lazyFetching: false,
        // selectable: true,
        // editable: true,
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
           url:  rex.forcal_events_api_url,
            cache: true,
            error: function (xhr, type, exception) {
                // todo later show warning field
                // $('#script-warning').show();
                alert("Error: " + exception);
            },
            success: function (doc) {
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

