<?php
/**
 * Fragment für den visuellen Editor für Custom Fields
 */

$fields = $this->getVar('fields', []);
$type = $this->getVar('type', 'fields');
$table = $this->getVar('table', '');

// Feldtypen, die unterstützt werden
$fieldTypes = [
    'text' => 'Text',
    'textarea' => 'Textarea',
    'select' => 'Select',
    'media' => 'Media',
    'medialist' => 'Medialist',
    'checkbox' => 'Checkbox',
    'radio' => 'Radio',
    'link' => 'Link',
    'linklist' => 'Linklist'
];

// CSRF-Token
$csrf_token = rex_csrf_token::factory('forcal_custom_fields')->getHiddenField();

// JS für die Drag & Drop Funktionalität und Feldbearbeitung
$js = '
<script>
$(document).ready(function() {
    // Sortable für die Felder
    $(".forcal-fields-container").sortable({
        handle: ".field-move",
        update: function(event, ui) {
            updateFieldOrder();
        }
    });
    
    // Feldtyp Änderung überwachen
    $("#field-type").on("change", function() {
        var type = $(this).val();
        if (type === "select" || type === "radio" || type === "checkbox") {
            $("#field-options-container").show();
        } else {
            $("#field-options-container").hide();
        }
    });
    
    // Option hinzufügen
    $(".add-option").on("click", function() {
        var optionRow = $(".option-row").first().clone();
        optionRow.find("input").val("");
        $(".field-options").append(optionRow);
    });
    
    // Option entfernen
    $(document).on("click", ".remove-option", function() {
        if ($(".option-row").length > 1) {
            $(this).closest(".option-row").remove();
        } else {
            // Letzte Zeile nur leeren
            $(this).closest(".option-row").find("input").val("");
        }
    });
    
    // Feld bearbeiten
    $(".edit-field").on("click", function() {
        var index = $(this).data("index");
        var fieldData = fields[index];
        
        $("#field-name").val(fieldData.name);
        $("#field-type").val(fieldData.type).trigger("change");
        $("#field-label-de").val(fieldData.label_de || "");
        $("#field-label-en").val(fieldData.label_en || "");
        $("#field-index").val(index);
        $("#field-action").val("edit");
        
        // Optionen befüllen, falls vorhanden
        $(".field-options").html("");
        if (fieldData.options) {
            $.each(fieldData.options, function(key, value) {
                var optionRow = $(".option-row").first().clone();
                optionRow.find("input[name=\'option_keys[]\']").val(key);
                optionRow.find("input[name=\'option_values[]\']").val(value);
                $(".field-options").append(optionRow);
            });
        } else {
            // Leere Option einfügen
            var optionRow = $(".option-row").first().clone();
            optionRow.find("input").val("");
            $(".field-options").html(optionRow);
        }
        
        $("#forcal-field-modal").modal("show");
    });
    
    // Neues Feld hinzufügen
    $("#add-field").on("click", function() {
        // Formular zurücksetzen
        $("#field-name").val("");
        $("#field-type").val("text").trigger("change");
        $("#field-label-de").val("");
        $("#field-label-en").val("");
        $("#field-index").val("-1");
        $("#field-action").val("add");
        
        // Optionen zurücksetzen
        var optionRow = $(".option-row").first().clone();
        optionRow.find("input").val("");
        $(".field-options").html(optionRow);
        
        $("#forcal-field-modal").modal("show");
    });
    
    // Feld speichern
    $("#save-field-form").on("submit", function(e) {
        e.preventDefault();
        var fieldName = $("#field-name").val();
        var fieldType = $("#field-type").val();
        var fieldLabelDe = $("#field-label-de").val();
        var fieldLabelEn = $("#field-label-en").val();
        var fieldIndex = $("#field-index").val();
        var fieldAction = $("#field-action").val();
        
        if (!fieldName) {
            alert("' . rex_i18n::msg('forcal_field_name_required') . '");
            return;
        }
        
        // Optionen sammeln, falls vorhanden
        var options = {};
        if (fieldType === "select" || fieldType === "radio" || fieldType === "checkbox") {
            $(".option-row").each(function() {
                var key = $(this).find("input[name=\'option_keys[]\']").val();
                var value = $(this).find("input[name=\'option_values[]\']").val();
                if (key && value) {
                    options[key] = value;
                }
            });
        }
        
        // Feld-Objekt erstellen
        var fieldData = {
            name: fieldName,
            type: fieldType,
            label_de: fieldLabelDe,
            label_en: fieldLabelEn
        };
        
        if (Object.keys(options).length > 0) {
            fieldData.options = options;
        }
        
        $("#field-data").val(JSON.stringify(fieldData));
        
        // Form direkt absenden statt PJAX
        this.submit();
    });
    
    // Feld löschen
    $(".delete-field").on("click", function() {
        if (confirm("' . rex_i18n::msg('forcal_field_delete_confirm') . '")) {
            var index = $(this).data("index");
            var url = "' . rex_url::currentBackendPage(['func' => 'delete_field']) . '&type=' . $type . '&table=' . $table . '&index=" + index;
            
            // Direkte Weiterleitung statt PJAX
            window.location.href = url;
        }
    });
    
    function updateFieldOrder() {
        var newOrder = [];
        $(".field-item").each(function() {
            newOrder.push($(this).data("index"));
        });
        
        var url = "' . rex_url::currentBackendPage(['func' => 'reorder_fields']) . '&type=' . $type . '&table=' . $table . '&order=" + JSON.stringify(newOrder);
        
        // Direkte Weiterleitung statt PJAX
        window.location.href = url;
    }
    
    // Feldliste für JS
    var fields = ' . json_encode($fields) . ';
});
</script>
';

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?= rex_i18n::msg('forcal_custom_fields_editor') ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <button id="add-field" class="btn btn-success"><i class="fa fa-plus"></i> <?= rex_i18n::msg('forcal_add_field') ?></button>
                <hr>
                
                <div class="forcal-fields-container">
                    <?php if (empty($fields)): ?>
                        <div class="alert alert-info"><?= rex_i18n::msg('forcal_no_fields') ?></div>
                    <?php else: ?>
                        <?php foreach ($fields as $index => $field): ?>
                            <div class="panel panel-default field-item" data-index="<?= $index ?>">
                                <div class="panel-heading">
                                    <div class="row">
                                        <div class="col-md-9">
                                            <h3 class="panel-title">
                                                <i class="fa fa-arrows-alt field-move" style="cursor: move; margin-right: 10px;"></i>
                                                <?= rex_escape($field['name']) ?> 
                                                <small>(<?= rex_escape($fieldTypes[$field['type']] ?? $field['type']) ?>)</small>
                                            </h3>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            <button class="btn btn-xs btn-primary edit-field" data-index="<?= $index ?>"><i class="fa fa-pencil"></i> <?= rex_i18n::msg('forcal_edit') ?></button>
                                            <button class="btn btn-xs btn-danger delete-field" data-index="<?= $index ?>"><i class="fa fa-trash"></i> <?= rex_i18n::msg('forcal_delete') ?></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong><?= rex_i18n::msg('forcal_field_label_de') ?>:</strong> <?= rex_escape($field['label_de'] ?? '') ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong><?= rex_i18n::msg('forcal_field_label_en') ?>:</strong> <?= rex_escape($field['label_en'] ?? '') ?>
                                        </div>
                                    </div>
                                    <?php if (isset($field['options']) && !empty($field['options'])): ?>
                                        <div class="row" style="margin-top: 10px;">
                                            <div class="col-md-12">
                                                <strong><?= rex_i18n::msg('forcal_field_options') ?>:</strong>
                                                <table class="table table-striped table-bordered table-condensed">
                                                    <thead>
                                                        <tr>
                                                            <th><?= rex_i18n::msg('forcal_field_option_key') ?></th>
                                                            <th><?= rex_i18n::msg('forcal_field_option_value') ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($field['options'] as $key => $value): ?>
                                                            <tr>
                                                                <td><?= rex_escape($key) ?></td>
                                                                <td><?= rex_escape($value) ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal für Feldbearbeitung -->
<div class="modal fade" id="forcal-field-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?= rex_i18n::msg('forcal_custom_field_edit') ?></h4>
      </div>
      <form id="save-field-form" action="<?= rex_url::currentBackendPage(['func' => 'save_field', 'type' => $type, 'table' => $table]) ?>" method="post">
        <div class="modal-body">
          <div class="form-group">
              <label for="field-name"><?= rex_i18n::msg('forcal_field_name') ?></label>
              <input type="text" class="form-control" id="field-name" name="field_name" required>
          </div>
          <div class="form-group">
              <label for="field-type"><?= rex_i18n::msg('forcal_field_type') ?></label>
              <select class="form-control" id="field-type" name="field_type">
                  <?php foreach ($fieldTypes as $key => $label): ?>
                      <option value="<?= $key ?>"><?= $label ?></option>
                  <?php endforeach; ?>
              </select>
          </div>
          <div class="form-group">
              <label for="field-label-de"><?= rex_i18n::msg('forcal_field_label_de') ?></label>
              <input type="text" class="form-control" id="field-label-de" name="field_label_de">
          </div>
          <div class="form-group">
              <label for="field-label-en"><?= rex_i18n::msg('forcal_field_label_en') ?></label>
              <input type="text" class="form-control" id="field-label-en" name="field_label_en">
          </div>
          <div id="field-options-container" style="display:none;">
              <div class="form-group">
                  <label><?= rex_i18n::msg('forcal_field_options') ?></label>
                  <div class="field-options">
                      <div class="option-row">
                          <div class="input-group">
                              <span class="input-group-addon"><?= rex_i18n::msg('forcal_field_option_key') ?></span>
                              <input type="text" class="form-control" name="option_keys[]">
                              <span class="input-group-addon"><?= rex_i18n::msg('forcal_field_option_value') ?></span>
                              <input type="text" class="form-control" name="option_values[]">
                              <span class="input-group-btn">
                                  <button class="btn btn-default remove-option" type="button"><i class="fa fa-minus"></i></button>
                              </span>
                          </div>
                      </div>
                  </div>
                  <button type="button" class="btn btn-default add-option"><i class="fa fa-plus"></i> <?= rex_i18n::msg('forcal_field_add_option') ?></button>
              </div>
          </div>
          <input type="hidden" id="field-index" name="index" value="-1">
          <input type="hidden" id="field-action" name="action" value="add">
          <input type="hidden" id="field-data" name="field_data" value="">
          <?= $csrf_token ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?= rex_i18n::msg('forcal_cancel') ?></button>
          <button type="submit" class="btn btn-primary"><?= rex_i18n::msg('forcal_save') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $js ?>
