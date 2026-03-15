/**
 * Inline Venue Creation – Modal zum schnellen Anlegen eines Venue
 * direkt aus dem Termin-Formular heraus.
 *
 * @package forcal
 * @license MIT
 */

$(function () {
    forcal_venue_inline_init();

    $(document).on('rex:ready', function () {
        forcal_venue_inline_init();
    });
});

function forcal_venue_inline_init() {
    var btn = $('#forcal-add-venue-btn');
    if (!btn.length) return;

    btn.off('click.forcalVenue').on('click.forcalVenue', function (e) {
        e.preventDefault();
        forcal_venue_show_modal();
    });
}

/**
 * Erzeugt und zeigt das Bootstrap-Modal zum Anlegen eines neuen Venue.
 */
function forcal_venue_show_modal() {
    // Falls bereits geöffnet, nur anzeigen
    var existing = $('#forcal-venue-modal');
    if (existing.length) {
        existing.modal('show');
        return;
    }

    // Sprach-Felder aus data-Attribut lesen (vom PHP gesetzt)
    var langData = $('#forcal-add-venue-btn').data('langs');
    var langs = [];
    if (typeof langData === 'object') {
        langs = langData;
    }

    // Modal-HTML aufbauen
    var nameFields = '';

    if (langs.length > 1) {
        // Mehrsprachig: Bootstrap-Tabs
        var tabNav = '<ul class="nav nav-tabs forcal-venue-lang-tabs">';
        var tabContent = '<div class="tab-content">';
        for (var i = 0; i < langs.length; i++) {
            var lang = langs[i];
            var active = i === 0 ? ' active' : '';
            var required = i === 0 ? ' required' : '';
            var requiredStar = i === 0 ? ' *' : '';

            tabNav += '<li class="' + active + '">' +
                '<a href="#forcal-venue-lang-' + lang.id + '" data-toggle="tab">' + lang.code + '</a></li>';

            tabContent += '<div class="tab-pane' + active + '" id="forcal-venue-lang-' + lang.id + '">' +
                '<div class="form-group" style="margin-top:12px">' +
                '<label for="forcal-venue-name-' + lang.id + '">' + lang.label + requiredStar + '</label>' +
                '<input type="text" class="form-control" id="forcal-venue-name-' + lang.id + '"' +
                ' data-clang="' + lang.id + '"' + required + ' placeholder="' + lang.label + '">' +
                '</div></div>';
        }
        tabNav += '</ul>';
        tabContent += '</div>';
        nameFields = tabNav + tabContent;
    } else {
        // Einsprachig: einfaches Feld ohne Tabs
        var lang = langs[0];
        nameFields = '<div class="form-group">' +
            '<label for="forcal-venue-name-' + lang.id + '">' + lang.label + ' *</label>' +
            '<input type="text" class="form-control" id="forcal-venue-name-' + lang.id + '"' +
            ' data-clang="' + lang.id + '" required>' +
            '</div>';
    }

    var html = '<div class="modal fade" id="forcal-venue-modal" tabindex="-1" role="dialog">' +
        '<div class="modal-dialog" role="document">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
        '<h4 class="modal-title"><i class="rex-icon fa-map-marker"></i> ' + rex.forcal_venue_modal_title + '</h4>' +
        '</div>' +
        '<div class="modal-body">' +
        '<div id="forcal-venue-modal-alert" style="display:none"></div>' +
        nameFields +
        '<hr>' +
        '<div class="row">' +
        '<div class="col-sm-8"><div class="form-group">' +
        '<label for="forcal-venue-street">' + rex.forcal_venue_lbl_street + '</label>' +
        '<input type="text" class="form-control" id="forcal-venue-street">' +
        '</div></div>' +
        '<div class="col-sm-4"><div class="form-group">' +
        '<label for="forcal-venue-housenumber">' + rex.forcal_venue_lbl_housenumber + '</label>' +
        '<input type="text" class="form-control" id="forcal-venue-housenumber">' +
        '</div></div>' +
        '</div>' +
        '<div class="row">' +
        '<div class="col-sm-4"><div class="form-group">' +
        '<label for="forcal-venue-zip">' + rex.forcal_venue_lbl_zip + '</label>' +
        '<input type="text" class="form-control" id="forcal-venue-zip">' +
        '</div></div>' +
        '<div class="col-sm-8"><div class="form-group">' +
        '<label for="forcal-venue-city">' + rex.forcal_venue_lbl_city + '</label>' +
        '<input type="text" class="form-control" id="forcal-venue-city">' +
        '</div></div>' +
        '</div>' +
        '<div class="form-group">' +
        '<label for="forcal-venue-country">' + rex.forcal_venue_lbl_country + '</label>' +
        '<input type="text" class="form-control" id="forcal-venue-country">' +
        '</div>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-default" data-dismiss="modal">' + rex.forcal_venue_btn_cancel + '</button>' +
        '<button type="button" class="btn btn-apply" id="forcal-venue-modal-save">' +
        '<i class="rex-icon fa-plus"></i> ' + rex.forcal_venue_btn_save + '</button>' +
        '</div>' +
        '</div></div></div>';

    $('body').append(html);

    var modal = $('#forcal-venue-modal');
    modal.modal('show');

    // Nach Schließen aufräumen
    modal.on('hidden.bs.modal', function () {
        modal.remove();
    });

    // Enter-Taste zum Speichern
    modal.on('keydown', 'input', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#forcal-venue-modal-save').trigger('click');
        }
    });

    // Speichern-Button
    $('#forcal-venue-modal-save').off('click').on('click', function () {
        forcal_venue_save(modal);
    });
}

/**
 * Sendet die Daten per AJAX an den API-Endpoint und aktualisiert das Select.
 */
function forcal_venue_save(modal) {
    var alertBox = modal.find('#forcal-venue-modal-alert');
    alertBox.hide();

    // Daten sammeln
    var postData = {};
    modal.find('input[data-clang]').each(function () {
        postData['name_' + $(this).data('clang')] = $(this).val();
    });
    postData.street = modal.find('#forcal-venue-street').val() || '';
    postData.housenumber = modal.find('#forcal-venue-housenumber').val() || '';
    postData.zip = modal.find('#forcal-venue-zip').val() || '';
    postData.city = modal.find('#forcal-venue-city').val() || '';
    postData.country = modal.find('#forcal-venue-country').val() || '';

    // Validierung: Erstes Name-Feld prüfen
    var firstInput = modal.find('input[data-clang]:first');
    if (!firstInput.val() || !firstInput.val().trim()) {
        alertBox.html('<div class="alert alert-danger">' + rex.forcal_venue_name_required + '</div>').show();
        firstInput.focus();
        return;
    }

    var saveBtn = modal.find('#forcal-venue-modal-save');
    saveBtn.prop('disabled', true).find('i').removeClass('fa-plus').addClass('fa-spinner fa-spin');

    $.ajax({
        url: rex.forcal_venue_create_url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // Das originale <select>-Element finden (rex_form setzt die Klasse direkt darauf)
                var select = $('select.forcal_venue_select');

                // Falls nicht gefunden, Fallback über den Feldnamen
                if (!select.length) {
                    select = $('select[name*="[venue]"]');
                }

                // Neue Option einfügen
                var newOption = $('<option></option>')
                    .attr('value', response.id)
                    .text(response.name);
                select.append(newOption);

                // Wert auf die neue ID setzen
                select.val(String(response.id));

                // Selectpicker aktualisieren (Bootstrap Select)
                // REDAXO initialisiert Bootstrap Select automatisch (z.B. via data-live-search),
                // daher prüfen ob das Plugin auf dem Element aktiv ist, nicht nur die Klasse.
                if ($.fn.selectpicker && select.data('selectpicker')) {
                    select.selectpicker('refresh');
                    select.selectpicker('val', String(response.id));
                }

                modal.modal('hide');
            } else {
                alertBox.html('<div class="alert alert-danger">' + (response.error || 'Error') + '</div>').show();
            }
        },
        error: function (xhr) {
            var msg = 'Fehler beim Speichern';
            try {
                var json = JSON.parse(xhr.responseText);
                if (json && json.error) msg = json.error;
            } catch (e) {
                if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
            }
            console.error('forcal venue create error', xhr.status, xhr.responseText);
            alertBox.html('<div class="alert alert-danger">' + msg + '</div>').show();
        },
        complete: function () {
            saveBtn.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-plus');
        }
    });
}
