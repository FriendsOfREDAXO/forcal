<?php

/**
 * Demo und Referenz für den forCal-Picker und die Schnellanlage.
 *
 * @package forcal
 * @license MIT
 */

use forCal\Utils\forCalPicker;

$section = static function (string $title, string $body): string {
    $fragment = new rex_fragment();
    $fragment->setVar('title', $title, false);
    $fragment->setVar('body', $body, false);

    return $fragment->parse('core/page/section.php');
};
$msg = static fn (string $key, string ...$args): string => rex_escape(rex_i18n::rawMsg('forcal_picker_' . $key, ...$args));
$code = static fn (string $source): string => '<pre class="forcal-demo-code"><code>' . rex_escape(trim($source)) . '</code></pre>';
$demo = static fn (string $title, string $text, string $widget, string $source): string => '<div class="forcal-demo"><div class="forcal-demo-live"><h3>' . $title . '</h3><p class="text-muted">' . $text . '</p>'
    . $widget . '<p class="forcal-demo-value">' . $msg('demo_value') . ' <code data-demo-value></code></p></div>' . $code($source) . '</div>';

$sample = array_map(static fn (array $row): int => (int) $row['id'], rex_sql::factory()->getArray('SELECT id FROM ' . rex::getTable('forcal_entries') . ' WHERE status = 1 ORDER BY start_date DESC LIMIT 2'));
$category = rex_sql::factory()->getArray('SELECT id FROM ' . rex::getTable('forcal_categories') . ' WHERE status = 1 ORDER BY id LIMIT 1')[0]['id'] ?? '';

// ------------------------------------------------------------------ Live

$live = '<p>' . $msg('demo_intro') . '</p>';
$live .= $demo($msg('demo_entry'), $msg('demo_entry_text'), forCalPicker::render('demo_entry', ''), '<input class="forcal-picker" name="REX_INPUT_VALUE[1]" value="REX_VALUE[1]">');
$live .= $demo($msg('demo_entries'), $msg('demo_entries_text'), forCalPicker::render('demo_entries', implode(',', $sample), ['multiple' => true, 'max' => 6, 'public' => true]),
    '<input class="forcal-picker" name="REX_INPUT_VALUE[2]" value="REX_VALUE[2]"' . "\n       " . 'data-fp-multiple="true" data-fp-max="6" data-fp-public="true">');
$live .= $demo($msg('demo_category'), $msg('demo_category_text'), forCalPicker::render('demo_category', (string) $category, ['type' => 'category', 'multiple' => true]),
    '<input class="forcal-picker" name="…" value="…" data-fp-type="category" data-fp-multiple="true">');
if (rex_addon::get('forcal')->getConfig('forcal_venues_enabled', true)) {
    $live .= $demo($msg('demo_venue'), $msg('demo_venue_text'), forCalPicker::render('demo_venue', '', ['type' => 'venue']), '<input class="forcal-picker" name="…" value="…" data-fp-type="venue">');
}
$live .= $demo($msg('demo_past'), $msg('demo_past_text'), forCalPicker::render('demo_past', '', ['multiple' => true, 'past' => true, 'create' => false]),
    '<input class="forcal-picker" name="…" value="…" data-fp-multiple="true"' . "\n       " . 'data-fp-past="true" data-fp-create="false">');
echo $section($msg('demo_live'), $live);

// ------------------------------------------------------------------ Repeater-Probe

$row = '<div class="forcal-demo-row" data-demo-row><label>' . $msg('demo_clone_label') . '</label>' . forCalPicker::render('demo_clone[]', (string) ($sample[0] ?? ''), ['multiple' => true]) . '</div>';
echo $section($msg('demo_clone'), '<p>' . $msg('demo_clone_text') . '</p><div data-demo-rows>' . $row . '</div>'
    . '<button type="button" class="btn btn-default" data-demo-clone><i class="rex-icon fa-clone"></i> ' . $msg('demo_clone_button') . '</button>');

// ------------------------------------------------------------------ JS-API

$buttons = '';
foreach (forCalPicker::TYPES as $type) {
    $buttons .= '<button type="button" class="btn btn-default" data-demo-open="' . $type . '">' . $msg('type_' . $type) . '</button> ';
}
$buttons .= '<button type="button" class="btn btn-save" data-demo-create><i class="rex-icon fa-plus"></i> ' . $msg('demo_api_create') . '</button>';
echo $section($msg('demo_api'), '<p>' . $msg('demo_api_text') . '</p><p>' . $buttons . '</p><pre class="forcal-demo-code" data-demo-result hidden></pre>'
    . $code("ForcalPicker.open(function (entry) {\n    console.log(entry.key, entry.title, entry.subtitle, entry.color);\n}, { type: 'entry' });\n\nForcalPicker.open(function (items) { … }, { type: 'category', multiple: true, max: 3, selected: ['1'] });\n\n// Schnellanlage wie im Kalenderblatt; liefert false, wenn der Benutzer nichts anlegen darf\nForcalPicker.create({ date: '2026-10-05', allDay: false, start: '19:00', end: '21:00', detailsUrl: 'index.php?page=forcal/entries&func=add' }, function (entry) { … });\n\n// Felder, die nachträglich ins DOM kommen (rex:ready erledigt das sonst von selbst):\nForcalPicker.init(container);"));

// ------------------------------------------------------------------ Referenz

$attributes = [
    'class="forcal-picker"' => 'ref_class', 'data-fp-type="entry"' => 'ref_type_entry', 'data-fp-type="category"' => 'ref_type_category', 'data-fp-type="venue"' => 'ref_type_venue',
    'data-fp-multiple="true"' => 'ref_multiple', 'data-fp-max="5"' => 'ref_max', 'data-fp-category="3"' => 'ref_category', 'data-fp-public="true"' => 'ref_public', 'data-fp-past="true"' => 'ref_past',
    'data-fp-create="false"' => 'ref_create',
];
$table = '<table class="table"><thead><tr><th>' . $msg('ref_attribute') . '</th><th>' . $msg('ref_effect') . '</th></tr></thead><tbody>';
foreach ($attributes as $attribute => $key) {
    $table .= '<tr><td><code>' . rex_escape($attribute) . '</code></td><td>' . $msg($key) . '</td></tr>';
}
$table .= '</tbody></table><p>' . $msg('ref_value') . '</p>';

$usage = '<h3>' . $msg('use_module') . '</h3>' . $code(<<<'CODE'
<!-- Eingabe -->
<input class="forcal-picker" name="REX_INPUT_VALUE[1]" value="REX_VALUE[1]" data-fp-multiple="true" data-fp-public="true">
<input class="forcal-picker" name="REX_INPUT_VALUE[2]" value="REX_VALUE[2]" data-fp-type="category" data-fp-multiple="true">

<?php // Ausgabe
use forCal\Utils\forCalPicker;

// Handverlesene Termine in der gewählten Reihenfolge (nur Status online)
foreach (forCalPicker::entries('REX_VALUE[1]') as $entry) {
    echo rex_escape(forCalPicker::name($entry)), ' ', rex_formatter::intlDate(strtotime($entry['start_date']));
}

// Kommende Termine aus den gewählten Kategorien
$categoryIds = forCalPicker::ids('REX_VALUE[2]');
$entries = \forCal\Factory\forCalEventsFactory::create()->from('today')->to('+6 months')->inCategories($categoryIds)->get();
CODE)
    . '<h3>MForm</h3>' . $code(<<<'CODE'
$mform = MForm::factory();
$mform->addTextField('1', ['label' => 'Termin', 'class' => 'forcal-picker']);
$mform->addTextField('2', ['label' => 'Kategorien', 'class' => 'forcal-picker', 'data-fp-type' => 'category', 'data-fp-multiple' => 'true']);

// Im Repeater und in MBlock genauso: Das Widget baut sich nach dem Klonen selbst neu auf.
$mform->addRepeaterElement(3, MForm::factory()
    ->addTextField('headline', ['label' => 'Überschrift'])
    ->addTextField('entries', ['label' => 'Termine', 'class' => 'forcal-picker', 'data-fp-multiple' => 'true']));
echo $mform->show();
CODE)
    . '<h3>YForm</h3><p>' . $msg('use_yform') . '</p>' . $code(<<<'CODE'
forcal_picker|termin|Termin
forcal_picker|termine|Termine|entry|1|6||1          // mehrere, max. 6, nur online
forcal_picker|kategorien|Kategorien|category|1
forcal_picker|ort|Veranstaltungsort|venue

// ohne eigenes Feld, in jedem HTML-Feld:
html|termine||<input class="forcal-picker" name="termine" data-fp-multiple="true">
CODE)
    . '<h3>rex_form</h3>' . $code(<<<'CODE'
$field = $form->addTextField('entry_ids');
$field->setLabel('Termine');
$field->setAttribute('class', 'forcal-picker form-control');
$field->setAttribute('data-fp-multiple', 'true');
CODE)
    . '<h3>PHP</h3>' . $code(<<<'CODE'
use forCal\Utils\forCalPicker;

echo forCalPicker::render('termine', $value, ['type' => 'entry', 'multiple' => true, 'max' => 5, 'category' => 3, 'public' => true]);

forCalPicker::ids($value);                // [12, 7]
forCalPicker::entries($value);            // Datensätze aus forcal_entries, nur Status online
forCalPicker::categories($value);         // Datensätze aus forcal_categories
forCalPicker::venues($value);             // Datensätze aus forcal_venues
forCalPicker::entries($value, false);     // auch offline, nur fürs Backend
forCalPicker::name($row);                 // Name in der aktuellen Sprache, sonst Startsprache
CODE);
echo $section($msg('ref_title'), $table . $usage . '<p class="help-block">' . $msg('ref_perm') . '</p>');
?>
<style nonce="<?= rex_response::getNonce() ?>">
.forcal-demo { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 24px; align-items: start; padding: 16px 0; border-top: 1px solid rgb(127 127 127 / .3); }
@media (max-width: 1100px) { .forcal-demo { grid-template-columns: 1fr; } }
.forcal-demo-live h3 { margin: 0 0 4px; font-size: 1.1em; }
.forcal-demo-value { margin: 10px 0 0; font-size: .9em; opacity: .8; }
.forcal-demo-code { margin: 0; padding: 12px; overflow: auto; font-size: .85em; white-space: pre; }
.forcal-demo-row { margin-bottom: 12px; padding: 10px 12px; border: 1px dashed rgb(127 127 127 / .4); border-radius: 6px; }
.forcal-demo-row label { display: block; }
</style>
<script nonce="<?= rex_response::getNonce() ?>">
(function () {
  document.querySelectorAll('.forcal-demo-live').forEach(function (box) {
    var input = box.querySelector('input.forcal-picker'), out = box.querySelector('[data-demo-value]');
    if (!input || !out) return;
    var update = function () { out.textContent = '"' + input.value + '"'; };
    input.addEventListener('change', update); update();
  });
  // Klonen wie MBlock oder der MForm-Repeater: fertiges Markup kopieren, dann rex:ready auf dem neuen Element.
  document.querySelector('[data-demo-clone]').addEventListener('click', function () {
    var rows = document.querySelector('[data-demo-rows]'), copy = rows.lastElementChild.cloneNode(true);
    rows.append(copy);
    if (window.jQuery) window.jQuery(copy).trigger('rex:ready', [window.jQuery(copy)]); else ForcalPicker.init(copy);
  });
  var out = document.querySelector('[data-demo-result]');
  var show = function (result) { out.hidden = false; out.textContent = JSON.stringify(result, null, 2); };
  document.querySelectorAll('[data-demo-open]').forEach(function (button) {
    button.addEventListener('click', function () { ForcalPicker.open(show, { type: button.dataset.demoOpen }); });
  });
  document.querySelector('[data-demo-create]').addEventListener('click', function () {
    ForcalPicker.create({ detailsUrl: 'index.php?page=forcal/entries&func=add' }, show).then(function (opened) { if (!opened) show({ info: 'Keine Berechtigung zum Anlegen.' }); });
  });
})();
</script>
