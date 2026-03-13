<?php

/**
 * API-Endpoint für das Forcal Tagging-Widget.
 * Liefert vorhandene Tags aus einer Tabellenspalte als JSON-Suggestionsliste.
 *
 * Eigenständig – keine Abhängigkeit zum fields-Addon.
 *
 * @package forcal
 * @license MIT
 */

use forCal\Utils\forCalTaggingHelper;

class rex_api_forcal_tagging_suggest extends rex_api_function
{
    /** @var bool Nur im Backend aufrufbar */
    protected $published = false;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        if (!rex::isBackend() || !rex::getUser()) {
            rex_response::sendJson(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $table = rex_request('table', 'string', '');
        $field = rex_request('field', 'string', '');

        if ($table === '' || $field === '') {
            rex_response::sendJson(['success' => false, 'error' => 'Missing parameters']);
            exit;
        }

        // Tabelle auf bekannte Forcal-Tabellen beschränken (Security)
        $allowedTables = [
            'forcal_entries',
            'forcal_categories',
            'forcal_venues',
        ];

        // Eigene Tabellen können via Extension Point ergänzt werden
        $allowedTables = rex_extension::registerPoint(new rex_extension_point('FORCAL_TAGGING_ALLOWED_TABLES', $allowedTables));

        if (!in_array($table, (array) $allowedTables, true)) {
            // Auch prefixierte Varianten erlauben
            $unprefixed = str_replace(rex::getTablePrefix(), '', $table);
            if (!in_array($unprefixed, (array) $allowedTables, true)) {
                rex_response::sendJson(['success' => false, 'error' => 'Table not allowed']);
                exit;
            }
        }

        $tags = forCalTaggingHelper::collectFromTable($table, $field);

        rex_response::sendJson([
            'success' => true,
            'tags'    => $tags,
        ]);
        exit;
    }
}
