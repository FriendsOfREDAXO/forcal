<?php
/**
 * Forcal-Termine-Element.
 */

use FriendsOfREDAXO\Forcal\ForcalRenderer;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

$forcalAvailable = rex_addon::exists('forcal')
    && rex_addon::get('forcal')->isAvailable()
    && class_exists(ForcalRenderer::class)
    && ForcalRenderer::isAvailable();

if (!$forcalAvailable) {
    return null;
}

$catChoices = ['' => '— Alle Kategorien —'];
$repeatChoices = ['' => '— Bitte Termin waehlen —'];

foreach (ForcalRenderer::getCategoryChoices() as $id => $name) {
    $catChoices[(string) $id] = $name . ' (#' . $id . ')';
}
foreach (ForcalRenderer::getRepeatingEntryChoices() as $id => $name) {
    $repeatChoices[(string) $id] = $name . ' (#' . $id . ')';
}

$categoryChoices = [];
foreach ($catChoices as $key => $label) {
    if ($key !== '') {
        $categoryChoices[(string) $key] = $label;
    }
}

$venueChoices = ['' => '— Alle Orte —'];
foreach (ForcalRenderer::getVenueChoices() as $id => $name) {
    $venueChoices[(string) $id] = $name . ' (#' . $id . ')';
}

return [
    'label' => 'Forcal-Termine',
    'description' => 'Kommende Termine aus dem forcal-Kalender (nach Kategorie oder Serientermin)',
    'version' => '1.15.0',
    'icon' => 'fa-calendar',
    'category' => 'data',

    'settings_modal' => [
        'label' => 'Layout & Sektion',
        'icon' => 'fa-cog',
        'fields' => array_merge(
            StarterConfig::getGridFieldNames(),
            [
                'teaser_length',
                'headline_tag',
                'headline_style',
                'group_by',
                'group_heading_tag',
                'group_heading_style',
                'show_links',
                'url_pattern',
                'image_field',
            ],
            StarterConfig::getOptionalSectionFieldNames()
        ),
    ],

    'fields' => array_merge(
        StarterConfig::getGridFields(),
        [
            'mode' => [
                'type' => 'choice',
                'label' => 'Modus',
                'choices' => [
                    'categories' => 'Nach Kategorie(n) - kommende Termine',
                    'repeat' => 'Wiederkehrender Termin - naechste X Wiederholungen',
                ],
                'default' => 'categories',
                'notice' => 'Zuerst die Art der Terminliste waehlen. Danach werden nur die passenden Optionen eingeblendet.',
            ],
            'categories' => [
                'type' => 'choice',
                'label' => 'Kategorien',
                'choices' => [] !== $categoryChoices
                    ? $categoryChoices
                    : ['' => '— keine Kategorien gefunden —'],
                'multiple' => true,
                'notice' => 'Modus "Nach Kategorie": Mehrfachauswahl moeglich. Keine Auswahl = alle Kategorien.',
                'default' => [],
                'visible_if' => [
                    'mode' => 'categories',
                ],
            ],
            'repeat_entry' => [
                'type' => 'choice',
                'label' => 'Serientermin',
                'choices' => $repeatChoices,
                'notice' => 'Modus "Wiederkehrender Termin": waehlt einen Eintrag aus, dessen naechste Wiederholungen aufgelistet werden.',
                'default' => '',
                'visible_if' => [
                    'mode' => 'repeat',
                ],
            ],
            'period' => [
                'type' => 'choice',
                'label' => 'Zeitraum',
                'choices' => [
                    'quarter' => 'Vierteljahr',
                    'halfayear' => 'Halbes Jahr',
                    'year' => '1 Jahr (ab jetzt)',
                    'twoyears' => '2 Jahre (ab jetzt)',
                    'threeyears' => '3 Jahre (ab jetzt)',
                    'all' => 'Gesamter Zeitraum',
                ],
                'default' => 'quarter',
                'notice' => 'Begrenzt das Suchfenster fuer kommende Termine.',
            ],
            'start_date_choice' => [
                'type' => 'choice',
                'label' => 'Termine anzeigen',
                'choices' => [
                    'today' => 'Ab heute',
                    'yesterday' => 'Ab gestern',
                ],
                'default' => 'today',
            ],
            'filter_by_venue' => [
                'type' => 'checkbox',
                'label' => 'Nach Ort filtern',
                'default' => false,
                'visible_if' => [
                    'mode' => 'categories',
                ],
            ],
            'venue_id' => [
                'type' => 'choice',
                'label' => 'Ort',
                'choices' => $venueChoices,
                'default' => '',
                'visible_if' => [
                    'mode' => 'categories',
                    'filter_by_venue' => '1',
                ],
            ],
            'headline' => [
                'type' => 'text',
                'label' => 'Ueberschrift',
                'notice' => 'Optional ueber der Liste angezeigt',
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Beschreibung',
                'notice' => 'Optional als Einleitungstext',
            ],
            'headline_tag' => [
                'type' => 'choice',
                'label' => 'Hauptueberschrift: HTML-Tag',
                'selectpicker' => false,
                'choices' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV (kein Heading)',
                ],
                'default' => 'h2',
                'notice' => 'Tag der Hauptueberschrift.',
            ],
            'headline_style' => [
                'type' => 'choice',
                'label' => 'Hauptueberschrift: Stil',
                'choices' => [
                    'plain' => 'Schlicht',
                    'uk-heading-line' => 'Mit Linie (uk-heading-line)',
                    'uk-heading-bullet' => 'Bullet (uk-heading-bullet)',
                    'uk-heading-divider' => 'Divider (uk-heading-divider)',
                    'uk-heading-small' => 'Klein (uk-heading-small)',
                    'uk-heading-medium' => 'Medium (uk-heading-medium)',
                    'uk-heading-large' => 'Gross (uk-heading-large)',
                    'uk-text-uppercase uk-text-meta' => 'Meta / Uppercase',
                ],
                'default' => 'uk-heading-line',
                'notice' => 'Visueller Stil - wirkt im UIkit-Template.',
            ],
            'layout' => [
                'type' => 'choice',
                'label' => 'Layout',
                'choices' => [
                    'cards' => 'Kacheln (Cards)',
                    'list' => 'Liste mit Datum + Anriss',
                    'compact' => 'Kompakt (nur Titel + Datum)',
                ],
                'default' => 'cards',
            ],
            'show_category_colors' => [
                'type' => 'checkbox',
                'label' => 'Kategoriefarben anzeigen',
                'notice' => 'Zeigt Kategorie-Hinweise farblich passend zum gewaehlten Layout an.',
                'default' => false,
            ],
            'limit' => [
                'type' => 'text',
                'label' => 'Anzahl Termine',
                'notice' => '1-' . ForcalRenderer::MAX_LIMIT . '. Default: 6.',
                'default' => '6',
            ],
            'show_image' => [
                'type' => 'checkbox',
                'label' => 'Bild anzeigen',
                'notice' => 'Wenn aktiv, wird zu jedem Termin ein Bild ausgegeben (im Cards-Layout oben, in der Liste als Thumbnail).',
                'default' => false,
            ],
            'teaser_length' => [
                'type' => 'text',
                'label' => 'Teaser-Laenge in Zeichen',
                'notice' => '30-800. Default: 160',
                'default' => '160',
            ],
            'url_pattern' => [
                'type' => 'text',
                'label' => 'URL-Pattern fuer Termin-Detailseite',
                'notice' => 'Optional. Platzhalter <code>{id}</code>, z.B. <code>/termine/?id={id}</code>. Leer = keine Verlinkung.',
                'default' => '',
            ],
            'image_field' => [
                'type' => 'text',
                'label' => 'Forcal-Feldname fuer Bild',
                'notice' => 'Name des Media-Felds aus dem forcal-Fieldset (z.B. <code>image</code>, <code>bild</code>, <code>header_image</code>). Leer = kein Bild.',
                'default' => '',
            ],
            'show_links' => [
                'type' => 'checkbox',
                'label' => 'Termine verlinken (URL-Pattern muss gesetzt sein)',
                'default' => true,
            ],
            'group_by' => [
                'type' => 'choice',
                'label' => 'Trenner / Gruppierung',
                'choices' => [
                    '' => '— keine Trenner —',
                    'month' => 'Monatstrenner (z.B. "Mai 2026")',
                    'year' => 'Jahrestrenner (z.B. "2026")',
                    'year_month' => 'Jahres- + Monatstrenner (verschachtelt)',
                ],
                'default' => '',
                'notice' => 'Optional: Termine nach Monat oder Jahr gruppieren mit Zwischenueberschrift.',
            ],
            'group_heading_tag' => [
                'type' => 'choice',
                'label' => 'Trenner: HTML-Tag',
                'selectpicker' => false,
                'choices' => [
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'DIV (kein Heading)',
                ],
                'default' => 'h3',
                'notice' => 'Tag der Trenner-Ueberschrift.',
            ],
            'group_heading_style' => [
                'type' => 'choice',
                'label' => 'Trenner: Stil',
                'choices' => [
                    'plain' => 'Schlicht',
                    'uk-heading-line' => 'Mit Linie (uk-heading-line)',
                    'uk-heading-bullet' => 'Bullet (uk-heading-bullet)',
                    'uk-heading-divider' => 'Divider (uk-heading-divider)',
                    'uk-heading-small' => 'Klein (uk-heading-small)',
                    'uk-heading-medium' => 'Medium (uk-heading-medium)',
                    'uk-heading-large' => 'Gross (uk-heading-large)',
                    'uk-text-uppercase uk-text-meta' => 'Meta / Uppercase',
                ],
                'default' => 'uk-heading-line',
                'notice' => 'Visueller Stil - wirkt im UIkit-Template.',
            ],
        ],
        StarterConfig::getOptionalSectionFields()
    ),
];
