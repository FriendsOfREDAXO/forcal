<?php

/**
 * API-Endpoint zum schnellen Anlegen eines Venue aus dem Termin-Formular.
 *
 * Erstellt einen neuen Venue via AJAX und gibt die neue ID + den Namen zurück,
 * damit das Venue-Select direkt aktualisiert werden kann.
 *
 * @package forcal
 * @license MIT
 */

class rex_api_forcal_venue_create extends rex_api_function
{
    /** @var bool Nur im Backend aufrufbar */
    protected $published = false;

    protected function requiresCsrfProtection(): bool
    {
        return true;
    }

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();

        // Nur eingeloggte Backend-User
        if (!rex::isBackend() || !rex::getUser()) {
            rex_response::setStatus(rex_response::HTTP_UNAUTHORIZED);
            rex_response::sendJson(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $user = rex::getUser();

        // Mindestrecht: forcal[] muss vorhanden sein
        if (!$user->hasPerm('forcal[]')) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['success' => false, 'error' => rex_i18n::msg('permission_denied')]);
            exit;
        }

        // Venues müssen aktiviert sein
        if (!rex_addon::get('forcal')->getConfig('forcal_venues_enabled', true)) {
            rex_response::setStatus(rex_response::HTTP_FORBIDDEN);
            rex_response::sendJson(['success' => false, 'error' => 'Venues disabled']);
            exit;
        }

        // Name ist Pflichtfeld (für jede aktive Sprache)
        $names = [];
        $firstNameEmpty = true;
        foreach (rex_clang::getAll() as $clang) {
            $name = trim(rex_post('name_' . $clang->getId(), 'string', ''));
            $names[$clang->getId()] = $name;
            if ($clang->getId() === rex_clang::getStartId() && $name !== '') {
                $firstNameEmpty = false;
            }
        }

        if ($firstNameEmpty) {
            rex_response::setStatus(rex_response::HTTP_BAD_REQUEST);
            rex_response::sendJson([
                'success' => false,
                'error' => rex_i18n::msg('forcal_venue_name_validation'),
            ]);
            exit;
        }

        // Optionale Adressfelder
        $city = trim(rex_post('city', 'string', ''));
        $zip = trim(rex_post('zip', 'string', ''));
        $street = trim(rex_post('street', 'string', ''));
        $housenumber = trim(rex_post('housenumber', 'string', ''));
        $country = trim(rex_post('country', 'string', ''));

        $table = rex::getTable('forcal_venues');

        $sql = rex_sql::factory();
        $sql->setTable($table);

        foreach ($names as $clangId => $name) {
            $sql->setValue('name_' . $clangId, $name);
        }

        $sql->setValue('city', $city);
        $sql->setValue('zip', $zip);
        $sql->setValue('street', $street);
        $sql->setValue('housenumber', $housenumber);
        $sql->setValue('country', $country);
        $sql->setValue('status', 1);
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();

        try {
            $sql->insert();
            $newId = (int) $sql->getLastId();

            // Name für aktuelle Sprache zurückgeben
            $displayName = $names[rex_clang::getCurrentId()] !== ''
                ? $names[rex_clang::getCurrentId()]
                : $names[rex_clang::getStartId()];

            rex_response::sendJson([
                'success' => true,
                'id' => $newId,
                'name' => $displayName,
            ]);
        } catch (rex_sql_exception $e) {
            rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
            rex_response::sendJson([
                'success' => false,
                'error' => 'Database error',
            ]);
        }

        exit;
    }
}
