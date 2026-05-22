/**
 * forCal Definition Editor
 * Moderner Editor für YAML-Definitionen
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 */

(function($) {
    'use strict';

    const ForCalDefinitionEditor = {
        // Aktueller Zustand
        currentDefinition: {
            fields: [],
            langfields: []
        },
        definitionType: '',
        fieldTypes: {},
        isDirty: false,

        /**
         * Initialisierung
         */
        init: function() {
            const $editor = $('.forcal-definition-editor');
            if ($editor.length === 0) return;

            this.definitionType = $editor.data('definition-type');
            this.loadFieldTypes();
            this.loadDefinition();
            this.bindEvents();
        },

        /**
         * Feldtypen laden
         */
        loadFieldTypes: function() {
            this.apiCall('field_types', {}, (response) => {
                if (response.success && response.data.fieldTypes) {
                    this.fieldTypes = response.data.fieldTypes;
                    this.typesNeedingOptions = response.data.typesNeedingOptions || [];
                    this.typesSupportingSql = response.data.typesSupportingSql || [];
                }
            });
        },

        /**
         * Definition laden
         */
        loadDefinition: function() {
            const data = $('#forcal-definition-data').val();
            if (data) {
                try {
                    this.currentDefinition = JSON.parse(data);
                    this.renderEditor();
                } catch (e) {
                    console.error('Fehler beim Parsen der Definition:', e);
                }
            }
        },

        /**
         * Events binden
         */
        bindEvents: function() {
            // Add Field Button
            $('#forcal-def-add-field').on('click', () => this.addField('fields'));
            
            // Add Panel Button
            $('#forcal-def-add-panel').on('click', () => this.addPanel());
            
            // Save Button
            $('#forcal-def-save').on('click', () => this.saveDefinition());
            
            // Import Button
            $('#forcal-def-import').on('click', () => this.showImportModal());
            
            // Export Button
            $('#forcal-def-export').on('click', () => this.exportDefinition());
            
            // View YAML Button
            $('#forcal-def-view-yaml').on('click', () => this.showYamlModal());
            
            // Import Confirm
            $('#forcal-import-confirm').on('click', () => this.doImport());
            
            // Import File
            $('#forcal-import-file').on('change', (e) => this.handleFileImport(e));
            
            // Copy YAML
            $('#forcal-yaml-copy').on('click', () => this.copyYaml());
            
            // Warn bei ungespeicherten Änderungen
            $(window).on('beforeunload', (e) => {
                if (this.isDirty) {
                    e.preventDefault();
                    return 'Sie haben ungespeicherte Änderungen. Möchten Sie die Seite wirklich verlassen?';
                }
            });
        },

        /**
         * Editor rendern
         */
        renderEditor: function() {
            const $area = $('#forcal-definition-editor-area');
            $area.empty();

            // Fields Section
            if (this.currentDefinition.fields && this.currentDefinition.fields.length > 0) {
                $area.append(this.renderFieldsSection('fields', 'Felder', this.currentDefinition.fields));
            }

            // Langfields Section
            if (this.currentDefinition.langfields && this.currentDefinition.langfields.length > 0) {
                $area.append(this.renderFieldsSection('langfields', 'Sprachabhängige Felder', this.currentDefinition.langfields));
            }

            // Sortable machen
            this.makeSortable();
        },

        /**
         * Fields Section rendern
         */
        renderFieldsSection: function(sectionKey, title, items) {
            const $section = $('<div class="forcal-def-section" data-section="' + sectionKey + '">');
            $section.append('<h3>' + title + '</h3>');
            
            const $list = $('<div class="forcal-def-list sortable-list">');
            
            items.forEach((item, index) => {
                if (item.panel) {
                    $list.append(this.renderPanel(sectionKey, index, item));
                } else {
                    $list.append(this.renderField(sectionKey, index, item));
                }
            });
            
            $section.append($list);
            
            // Add Button für diese Section
            const $addBtn = $('<button type="button" class="btn btn-sm btn-default forcal-add-field-btn">');
            $addBtn.html('<i class="rex-icon rex-icon-add"></i> Feld hinzufügen');
            $addBtn.on('click', () => this.addField(sectionKey));
            $section.append($addBtn);
            
            return $section;
        },

        /**
         * Einzelnes Feld rendern
         */
        renderField: function(section, index, field) {
            const $item = $('<div class="forcal-def-item" data-section="' + section + '" data-index="' + index + '">');
            
            // Header mit Drag Handle
            const $header = $('<div class="forcal-def-item-header">');
            $header.append('<span class="forcal-def-drag-handle"><i class="rex-icon rex-icon-menu"></i></span>');
            $header.append('<span class="forcal-def-item-title">' + (field.name || 'Neues Feld') + '</span>');
            $header.append('<span class="forcal-def-item-type">' + (field.type || '') + '</span>');
            
            const $actions = $('<span class="forcal-def-item-actions">');
            $actions.append('<button type="button" class="btn btn-xs btn-default forcal-edit-btn" title="Bearbeiten"><i class="rex-icon rex-icon-edit"></i></button>');
            $actions.append('<button type="button" class="btn btn-xs btn-danger forcal-delete-btn" title="Löschen"><i class="rex-icon rex-icon-delete"></i></button>');
            $header.append($actions);
            
            $item.append($header);
            
            // Body (Form für Bearbeitung)
            const $body = $('<div class="forcal-def-item-body" style="display:none;">');
            $body.append(this.renderFieldForm(section, index, field));
            $item.append($body);
            
            // Event Handlers
            $item.find('.forcal-edit-btn').on('click', () => {
                $item.find('.forcal-def-item-body').slideToggle();
            });
            
            $item.find('.forcal-delete-btn').on('click', () => {
                if (confirm('Feld wirklich löschen?')) {
                    this.deleteItem(section, index);
                }
            });
            
            return $item;
        },

        /**
         * Panel rendern
         */
        renderPanel: function(section, index, panel) {
            const $item = $('<div class="forcal-def-item forcal-def-panel" data-section="' + section + '" data-index="' + index + '">');
            
            const $header = $('<div class="forcal-def-item-header">');
            $header.append('<span class="forcal-def-drag-handle"><i class="rex-icon rex-icon-menu"></i></span>');
            $header.append('<span class="forcal-def-item-title">Panel: ' + (panel.panel || 'Neues Panel') + '</span>');
            $header.append('<span class="forcal-def-item-type">Panel (' + (panel.fields ? panel.fields.length : 0) + ' Felder)</span>');
            
            const $actions = $('<span class="forcal-def-item-actions">');
            $actions.append('<button type="button" class="btn btn-xs btn-default forcal-edit-btn" title="Bearbeiten"><i class="rex-icon rex-icon-edit"></i></button>');
            $actions.append('<button type="button" class="btn btn-xs btn-danger forcal-delete-btn" title="Löschen"><i class="rex-icon rex-icon-delete"></i></button>');
            $header.append($actions);
            
            $item.append($header);
            
            const $body = $('<div class="forcal-def-item-body" style="display:none;">');
            $body.append(this.renderPanelForm(section, index, panel));
            $item.append($body);
            
            // Event Handlers
            $item.find('.forcal-edit-btn').on('click', () => {
                $item.find('.forcal-def-item-body').slideToggle();
            });
            
            $item.find('.forcal-delete-btn').on('click', () => {
                if (confirm('Panel wirklich löschen?')) {
                    this.deleteItem(section, index);
                }
            });
            
            return $item;
        },

        /**
         * Feld-Formular rendern
         */
        renderFieldForm: function(section, index, field) {
            const self = this;
            const $form = $('<div class="forcal-def-form">');
            
            // Name
            $form.append(this.renderFormGroup('Name', 
                '<input type="text" class="form-control forcal-field-input" data-field="name" value="' + (field.name || '') + '">'));
            
            // Type
            let typeSelect = '<select class="form-control forcal-field-input forcal-type-select" data-field="type">';
            typeSelect += '<option value="">-- Typ wählen --</option>';
            for (let key in this.fieldTypes) {
                const selected = field.type === key ? ' selected' : '';
                typeSelect += '<option value="' + key + '"' + selected + '>' + this.fieldTypes[key] + '</option>';
            }
            typeSelect += '</select>';
            $form.append(this.renderFormGroup('Typ', typeSelect));
            
            // Labels
            $form.append(this.renderFormGroup('Label (DE)', 
                '<input type="text" class="form-control forcal-field-input" data-field="label_de" value="' + (field.label_de || '') + '">'));
            $form.append(this.renderFormGroup('Label (EN)', 
                '<input type="text" class="form-control forcal-field-input" data-field="label_en" value="' + (field.label_en || '') + '">'));
            
            // Placeholder
            $form.append(this.renderFormGroup('Placeholder', 
                '<input type="text" class="form-control forcal-field-input" data-field="placeholder" value="' + (field.placeholder || '') + '">'));
            
            // Notice
            $form.append(this.renderFormGroup('Hinweistext', 
                '<input type="text" class="form-control forcal-field-input" data-field="notice" value="' + (field.notice || '') + '">'));
            
            // Default Value
            $form.append(this.renderFormGroup('Standardwert', 
                '<input type="text" class="form-control forcal-field-input" data-field="default" value="' + (field.default || '') + '">'));
            
            // Required
            const requiredChecked = field.required ? ' checked' : '';
            $form.append(this.renderFormGroup('Pflichtfeld', 
                '<label class="checkbox"><input type="checkbox" class="forcal-field-input" data-field="required" value="1"' + requiredChecked + '> Ja, Pflichtfeld</label>'));
            
            // Attributes (für alle Felder verfügbar)
            const attributesValue = field.attributes ? JSON.stringify(field.attributes, null, 2) : '';
            const attributesGroup = this.renderFormGroup('Zusätzliche Attribute (JSON)', 
                '<textarea class="form-control forcal-field-input" data-field="attributes" rows="3" placeholder=\'{"class": "my-class", "data-foo": "bar"}\'>' + 
                attributesValue + '</textarea>' +
                '<small class="help-block">Zusätzliche HTML-Attribute als JSON-Objekt (z.B. class, style, data-*)</small>');
            $form.append(attributesGroup);
            
            // Container für typ-spezifische Felder
            const $typeSpecific = $('<div class="forcal-type-specific-fields">');
            $form.append($typeSpecific);
            
            // Typ-spezifische Felder initial rendern
            this.renderTypeSpecificFields($typeSpecific, field);
            
            // Change Events
            $form.find('.forcal-field-input').on('change input', (e) => {
                const fieldName = $(e.target).data('field');
                let value = $(e.target).val();
                
                // Bei Checkbox den checked-Status nehmen
                if ($(e.target).is(':checkbox')) {
                    value = $(e.target).is(':checked');
                }
                
                this.updateField(section, index, fieldName, value);
            });
            
            // Type Select - bei Änderung typ-spezifische Felder neu rendern
            $form.find('.forcal-type-select').on('change', function() {
                const currentField = self.currentDefinition[section][index];
                self.renderTypeSpecificFields($typeSpecific, currentField);
            });
            
            return $form;
        },
        
        /**
         * Typ-spezifische Felder rendern
         */
        renderTypeSpecificFields: function($container, field) {
            const self = this;
            $container.empty();
            
            const fieldType = field.type;
            
            if (!fieldType) {
                return; // Kein Typ gewählt, keine typ-spezifischen Felder
            }
            
            $container.append('<h5 style="margin-top: 10px; margin-bottom: 15px; color: #2e7bcf; border-top: 2px solid #e0e0e0; padding-top: 15px;">Typ-spezifische Optionen</h5>');
            
            // SQL Query - separates Feld für direkte SQL-Queries (select, radio, checkbox, sql)
            if (this.typesSupportingSql && (this.typesSupportingSql.indexOf(fieldType) !== -1 || fieldType === 'sql')) {
                const sqlGroup = this.renderFormGroup('SQL Query', 
                    '<textarea class="form-control forcal-field-input" data-field="sql" rows="4" placeholder="SELECT id, name FROM rex_table WHERE status = 1 ORDER BY name">' + 
                    (field.sql || '') + '</textarea>' +
                    '<small class="help-block">SQL-Abfrage für dynamische Optionen. Erste Spalte = Wert, zweite Spalte = Anzeigename</small>');
                $container.append(sqlGroup);
            }
            
            // Options - nur für select, radio, checkbox, datalist (wenn keine SQL-Query verwendet wird)
            if (this.typesNeedingOptions && this.typesNeedingOptions.indexOf(fieldType) !== -1) {
                const optionsValue = field.options ? (typeof field.options === 'object' ? JSON.stringify(field.options, null, 2) : field.options) : '';
                const optionsGroup = this.renderFormGroup('Optionen (manuell)', 
                    '<textarea class="form-control forcal-field-input" data-field="options" rows="6" placeholder=\'Als Objekt:\n{"key1": "Label 1", "key2": "Label 2"}\n\nAls Array:\n["Wert 1", "Wert 2", "Wert 3"]\'>' + 
                    optionsValue + '</textarea>' +
                    '<small class="help-block">Manuelle Werte als JSON. Wird ignoriert, wenn SQL Query gesetzt ist.</small>');
                $container.append(optionsGroup);
            }
            
            // Multiple (für select)
            if (fieldType === 'select') {
                const multipleChecked = field.multiple ? ' checked' : '';
                $container.append(this.renderFormGroup('Mehrfachauswahl', 
                    '<label class="checkbox"><input type="checkbox" class="forcal-field-input" data-field="multiple" value="1"' + multipleChecked + '> Mehrfachauswahl erlauben</label>'));
            }
            
            // Size (für select, textarea)
            if (fieldType === 'select' || fieldType === 'textarea') {
                const sizeValue = field.size || '';
                let sizeLabel = fieldType === 'select' ? 'Größe (Anzahl sichtbare Zeilen)' : 'Zeilen (rows)';
                $container.append(this.renderFormGroup(sizeLabel, 
                    '<input type="number" class="form-control forcal-field-input" data-field="size" value="' + sizeValue + '" min="1" max="50">'));
            }
            
            // Rows/Cols für textarea
            if (fieldType === 'textarea') {
                const colsValue = field.cols || '';
                $container.append(this.renderFormGroup('Spalten (cols)', 
                    '<input type="number" class="form-control forcal-field-input" data-field="cols" value="' + colsValue + '" min="1" max="200">'));
            }
            
            // Max/Min für number
            if (fieldType === 'number') {
                const minValue = field.min !== undefined ? field.min : '';
                const maxValue = field.max !== undefined ? field.max : '';
                const stepValue = field.step || '';
                $container.append(this.renderFormGroup('Minimum', 
                    '<input type="number" class="form-control forcal-field-input" data-field="min" value="' + minValue + '">'));
                $container.append(this.renderFormGroup('Maximum', 
                    '<input type="number" class="form-control forcal-field-input" data-field="max" value="' + maxValue + '">'));
                $container.append(this.renderFormGroup('Schrittweite (step)', 
                    '<input type="number" class="form-control forcal-field-input" data-field="step" value="' + stepValue + '" step="0.01">'));
            }
            
            // Maxlength für text, textarea, etc.
            if (['text', 'textarea', 'email', 'url'].indexOf(fieldType) !== -1) {
                const maxlengthValue = field.maxlength || '';
                $container.append(this.renderFormGroup('Maximale Länge (maxlength)', 
                    '<input type="number" class="form-control forcal-field-input" data-field="maxlength" value="' + maxlengthValue + '" min="1">'));
            }
            
            // Pattern für text, email, url
            if (['text', 'email', 'url'].indexOf(fieldType) !== -1) {
                const patternValue = field.pattern || '';
                $container.append(this.renderFormGroup('Regex Pattern (pattern)', 
                    '<input type="text" class="form-control forcal-field-input" data-field="pattern" value="' + patternValue + '" placeholder="[A-Za-z0-9]+">' +
                    '<small class="help-block">Regulärer Ausdruck für die Validierung</small>'));
            }
            
            // Event-Handler für die neuen Felder
            $container.find('.forcal-field-input').on('change input', function(e) {
                const fieldName = $(e.target).data('field');
                let value = $(e.target).val();
                
                // Bei Checkbox den checked-Status nehmen
                if ($(e.target).is(':checkbox')) {
                    value = $(e.target).is(':checked');
                }
                
                // Bei JSON-Feldern versuchen zu parsen
                if (fieldName === 'options' || fieldName === 'attributes') {
                    if (value) {
                        try {
                            value = JSON.parse(value);
                        } catch (e) {
                            // Wenn kein gültiges JSON, als String lassen
                            console.warn('Ungültiges JSON für ' + fieldName + ', wird als String gespeichert');
                        }
                    } else {
                        value = null;
                    }
                }
                
                const section = $(e.target).closest('.forcal-def-item').data('section');
                const index = $(e.target).closest('.forcal-def-item').data('index');
                self.updateField(section, index, fieldName, value);
            });
        },

        /**
         * Panel-Formular rendern
         */
        renderPanelForm: function(section, index, panel) {
            const $form = $('<div class="forcal-def-form">');
            
            // Panel Name
            $form.append(this.renderFormGroup('Panel Name', 
                '<input type="text" class="form-control forcal-field-input" data-field="panel" value="' + (panel.panel || '') + '">'));
            
            // Labels
            $form.append(this.renderFormGroup('Label (DE)', 
                '<input type="text" class="form-control forcal-field-input" data-field="label_de" value="' + (panel.label_de || '') + '">'));
            $form.append(this.renderFormGroup('Label (EN)', 
                '<input type="text" class="form-control forcal-field-input" data-field="label_en" value="' + (panel.label_en || '') + '">'));
            
            // Fields im Panel
            $form.append('<h4>Felder im Panel</h4>');
            const $panelFields = $('<div class="forcal-panel-fields">');
            
            if (panel.fields && panel.fields.length > 0) {
                panel.fields.forEach((pField, pIndex) => {
                    $panelFields.append(this.renderPanelField(section, index, pIndex, pField));
                });
            }
            
            $form.append($panelFields);
            
            // Add Field Button
            const $addBtn = $('<button type="button" class="btn btn-sm btn-default">');
            $addBtn.html('<i class="rex-icon rex-icon-add"></i> Feld zu Panel hinzufügen');
            $addBtn.on('click', () => this.addFieldToPanel(section, index));
            $form.append($addBtn);
            
            // Change Events
            $form.find('.forcal-field-input').on('change input', (e) => {
                this.updateField(section, index, $(e.target).data('field'), $(e.target).val());
            });
            
            return $form;
        },

        /**
         * Feld innerhalb eines Panels rendern
         */
        renderPanelField: function(section, panelIndex, fieldIndex, field) {
            const $field = $('<div class="forcal-panel-field">');
            $field.append('<strong>' + (field.name || 'Feld ' + (fieldIndex + 1)) + '</strong> (' + (field.type || 'kein Typ') + ')');
            
            const $editBtn = $('<button type="button" class="btn btn-xs btn-default">Bearbeiten</button>');
            const $delBtn = $('<button type="button" class="btn btn-xs btn-danger">Löschen</button>');
            
            $delBtn.on('click', () => this.deleteFieldFromPanel(section, panelIndex, fieldIndex));
            
            $field.append($editBtn).append($delBtn);
            return $field;
        },

        /**
         * Form Group Helper
         */
        renderFormGroup: function(label, input) {
            return '<div class="form-group"><label>' + label + '</label>' + input + '</div>';
        },

        /**
         * Sortable aktivieren
         */
        makeSortable: function() {
            const self = this;
            $('.sortable-list').sortable({
                handle: '.forcal-def-drag-handle',
                placeholder: 'forcal-def-placeholder',
                update: function(event, ui) {
                    self.reorderItems($(this).closest('.forcal-def-section').data('section'));
                }
            });
        },

        /**
         * Feld hinzufügen
         */
        addField: function(section) {
            if (!this.currentDefinition[section]) {
                this.currentDefinition[section] = [];
            }
            
            const newField = {
                name: 'new_field_' + Date.now(),
                type: 'text',
                label_de: 'Neues Feld',
                label_en: 'New Field'
            };
            
            this.currentDefinition[section].push(newField);
            this.isDirty = true;
            this.renderEditor();
        },

        /**
         * Panel hinzufügen
         */
        addPanel: function() {
            if (!this.currentDefinition.langfields) {
                this.currentDefinition.langfields = [];
            }
            
            const newPanel = {
                panel: 'new_panel_' + Date.now(),
                label_de: 'Neues Panel',
                label_en: 'New Panel',
                fields: []
            };
            
            this.currentDefinition.langfields.push(newPanel);
            this.isDirty = true;
            this.renderEditor();
        },

        /**
         * Feld zu Panel hinzufügen
         */
        addFieldToPanel: function(section, panelIndex) {
            const newField = {
                name: 'new_field_' + Date.now(),
                type: 'text',
                label_de: 'Neues Feld',
                label_en: 'New Field'
            };
            
            if (!this.currentDefinition[section][panelIndex].fields) {
                this.currentDefinition[section][panelIndex].fields = [];
            }
            
            this.currentDefinition[section][panelIndex].fields.push(newField);
            this.isDirty = true;
            this.renderEditor();
        },

        /**
         * Feld aktualisieren
         */
        updateField: function(section, index, fieldName, value) {
            if (this.currentDefinition[section] && this.currentDefinition[section][index]) {
                this.currentDefinition[section][index][fieldName] = value;
                this.isDirty = true;
                
                // Titel aktualisieren
                const $item = $('.forcal-def-item[data-section="' + section + '"][data-index="' + index + '"]');
                if (fieldName === 'name') {
                    $item.find('.forcal-def-item-title').text(value || 'Neues Feld');
                } else if (fieldName === 'type') {
                    $item.find('.forcal-def-item-type').text(value);
                } else if (fieldName === 'panel') {
                    $item.find('.forcal-def-item-title').text('Panel: ' + (value || 'Neues Panel'));
                }
            }
        },

        /**
         * Item löschen
         */
        deleteItem: function(section, index) {
            if (this.currentDefinition[section]) {
                this.currentDefinition[section].splice(index, 1);
                this.isDirty = true;
                this.renderEditor();
            }
        },

        /**
         * Feld aus Panel löschen
         */
        deleteFieldFromPanel: function(section, panelIndex, fieldIndex) {
            if (this.currentDefinition[section][panelIndex].fields) {
                this.currentDefinition[section][panelIndex].fields.splice(fieldIndex, 1);
                this.isDirty = true;
                this.renderEditor();
            }
        },

        /**
         * Items neu sortieren
         */
        reorderItems: function(section) {
            const newOrder = [];
            $('.forcal-def-item[data-section="' + section + '"]').each((i, el) => {
                const oldIndex = $(el).data('index');
                newOrder.push(this.currentDefinition[section][oldIndex]);
                $(el).attr('data-index', i);
            });
            
            this.currentDefinition[section] = newOrder;
            this.isDirty = true;
        },

        /**
         * Definition speichern
         */
        saveDefinition: function() {
            const data = {
                action: 'save',
                type: this.definitionType,
                definition: JSON.stringify(this.currentDefinition)
            };
            
            this.apiCall('save', data, (response) => {
                if (response.success) {
                    this.isDirty = false;
                    this.showMessage('success', 'Definition erfolgreich gespeichert');
                } else {
                    this.showMessage('error', 'Fehler beim Speichern: ' + response.message);
                }
            });
        },

        /**
         * Import Modal anzeigen
         */
        showImportModal: function() {
            $('#forcal-import-text').val('');
            $('#forcal-import-file').val('');
            $('#forcal-import-modal').modal('show');
        },

        /**
         * Datei-Import behandeln
         */
        handleFileImport: function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    $('#forcal-import-text').val(e.target.result);
                };
                reader.readAsText(file);
            }
        },

        /**
         * Import durchführen
         */
        doImport: function() {
            const yaml = $('#forcal-import-text').val();
            
            if (!yaml) {
                alert('Bitte YAML-Daten eingeben oder Datei auswählen');
                return;
            }
            
            const data = {
                action: 'import',
                type: this.definitionType,
                yaml: yaml
            };
            
            this.apiCall('import', data, (response) => {
                if (response.success) {
                    $('#forcal-import-modal').modal('hide');
                    this.showMessage('success', 'Definition erfolgreich importiert');
                    location.reload();
                } else {
                    this.showMessage('error', 'Fehler beim Importieren: ' + response.message);
                }
            });
        },

        /**
         * Export durchführen
         */
        exportDefinition: function() {
            const data = {
                action: 'export',
                type: this.definitionType
            };
            
            this.apiCall('export', data, (response) => {
                if (response.success && response.data.yaml) {
                    // Download als Datei
                    const blob = new Blob([response.data.yaml], { type: 'text/yaml' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename || 'definition.yml';
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    this.showMessage('success', 'Definition exportiert');
                } else {
                    this.showMessage('error', 'Fehler beim Exportieren');
                }
            });
        },

        /**
         * YAML Modal anzeigen
         */
        showYamlModal: function() {
            const data = {
                action: 'export',
                type: this.definitionType
            };
            
            this.apiCall('export', data, (response) => {
                if (response.success && response.data.yaml) {
                    $('#forcal-yaml-content').text(response.data.yaml);
                    $('#forcal-yaml-modal').modal('show');
                }
            });
        },

        /**
         * YAML kopieren
         */
        copyYaml: function() {
            const text = $('#forcal-yaml-content').text();
            navigator.clipboard.writeText(text).then(() => {
                this.showMessage('success', 'YAML in Zwischenablage kopiert');
            });
        },

        /**
         * API Call
         */
        apiCall: function(action, data, callback) {
            const url = rex.backend + '?rex-api-call=forcal_definition&action=' + action;
            
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (callback) callback(response);
                },
                error: function(xhr, status, error) {
                    console.error('API Error:', error);
                    alert('Fehler bei der API-Anfrage');
                }
            });
        },

        /**
         * Nachricht anzeigen
         */
        showMessage: function(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const $alert = $('<div class="alert ' + alertClass + ' alert-dismissible">');
            $alert.html('<button type="button" class="close" data-dismiss="alert">&times;</button>' + message);
            
            $('.forcal-definition-toolbar').after($alert);
            
            setTimeout(() => {
                $alert.fadeOut(() => $alert.remove());
            }, 5000);
        }
    };

    // Init beim Document Ready
    $(document).ready(function() {
        ForCalDefinitionEditor.init();
    });

})(jQuery);
