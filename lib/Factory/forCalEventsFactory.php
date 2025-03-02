<?php

namespace forCal\Factory;

use rex;

class forCalEventsFactory
{
    protected $startTime = 'now';
    protected $endTime = '+6 months';
    protected $categories = null;
    protected $venueId = null;
    protected $customFilters = [];
    protected $sortCriteria = [];
    protected $useUserPermissions = null; // Automatisch bestimmen lassen

    /**
     * Setzt den Startzeitpunkt
     */
    public function from($startTime)
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * Setzt den Endzeitpunkt
     */
    public function to($endTime)
    {
        $this->endTime = $endTime;
        return $this;
    }

    /**
     * Filtert nach Kategorien
     */
    public function inCategories($categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * Filtert nach Veranstaltungsort
     */
    public function atVenue($venueId)
    {
        $this->venueId = $venueId;
        return $this;
    }

    /**
     * Fügt einen benutzerdefinierten Filter hinzu
     */
    public function withFilter($field, $value)
    {
        $this->customFilters[$field] = $value;
        return $this;
    }

    /**
     * Fügt ein Sortierungskriterium hinzu
     * Mehrere sortBy-Aufrufe erzeugen eine mehrstufige Sortierung
     */
    public function sortBy($field, $direction = 'asc')
    {
        $this->sortCriteria[] = [
            'field' => $field,
            'direction' => $direction
        ];
        return $this;
    }

    /**
     * Explizit Benutzerberechtigungen aktivieren/deaktivieren
     * In der Regel nicht notwendig, da automatisch erkannt wird
     */
    public function withUserPermissions($usePermissions = true)
    {
        $this->useUserPermissions = $usePermissions;
        return $this;
    }

    /**
     * Gibt alle Termine zurück
     */
    public function get()
    {
        // Bestimme, ob Benutzerberechtigungen verwendet werden sollen
        // Falls nicht explizit gesetzt, automatisch erkennen
        $useUserPermissions = $this->useUserPermissions;
        if ($useUserPermissions === null) {
            // Im Frontend keine Berechtigungen verwenden, im Backend nur wenn angemeldet
            $useUserPermissions = rex::isBackend() && rex::getUser();
        }

        // Erst die Standardsortierung nach Datum verwenden
        $results = \forCal\Handler\forCalHandler::exchangeEntries(
            $this->startTime,
            $this->endTime,
            false,
            false,
            'SORT_ASC', // Immer aufsteigend, da wir später selbst sortieren
            $this->categories,
            $this->venueId,
            1,
            1,
            $this->customFilters,
            null, // pageSize
            null, // pageNumber
            $useUserPermissions // Benutzerberechtigungen
        );

        // Wenn benutzerdefinierte Sortierung angegeben wurde
        if (!empty($this->sortCriteria)) {
            // Mehrfache Sortierung anwenden
            usort($results, function ($a, $b) {
                // Durch alle Sortierkriterien durchgehen
                foreach ($this->sortCriteria as $criteria) {
                    $field = $criteria['field'];
                    $direction = $criteria['direction'];

                    // Werte extrahieren (auch aus verschachtelten Arrays)
                    $a_value = $this->extractValue($a, $field);
                    $b_value = $this->extractValue($b, $field);

                    // Wenn die Werte unterschiedlich sind, sortieren
                    if ($a_value != $b_value) {
                        return ($direction === 'asc')
                            ? $this->compare($a_value, $b_value)
                            : $this->compare($b_value, $a_value);
                    }
                    // Sonst mit dem nächsten Kriterium fortfahren
                }

                // Wenn alle Kriterien gleich sind, keine Änderung
                return 0;
            });
        }

        return $results;
    }
    /**
     * Gibt einen einzelnen Termin nach ID zurück
     * 
     * @param int $id Die ID des Termins
     * @return array|null Der dekorierte Termin oder null, wenn nicht gefunden
     */
    public function getEntryById($id)
    {
        // Bestimme, ob Benutzerberechtigungen verwendet werden sollen
        $useUserPermissions = $this->useUserPermissions;
        if ($useUserPermissions === null) {
            $useUserPermissions = rex::isBackend() && rex::getUser();
        }

        // Termin abrufen
        $entry = \forCal\Handler\forCalHandler::exchangeEntry(
            $id,
            false,  // Vollständige Informationen
            1,      // Datumsformat
            1       // Zeitformat
        );

        // Wenn kein Termin gefunden wurde
        if (empty($entry)) {
            return null;
        }

        // Prüfen, ob der Termin den angegebenen Filtern entspricht
        if (!empty($this->customFilters)) {
            $include = true;

            foreach ($this->customFilters as $field => $value) {
                // Callback-Funktion als Filter
                if (is_callable($value)) {
                    if (!$value($entry)) {
                        $include = false;
                        break;
                    }
                    continue;
                }

                // Wert aus dem Eintrag extrahieren
                $entryValue = $this->extractValue($entry, $field);

                // Filtern nach dem Wert
                if ($value === true && empty($entryValue)) {
                    $include = false;
                    break;
                }

                if ($value === false && !empty($entryValue)) {
                    $include = false;
                    break;
                }

                if (is_string($value) || is_numeric($value)) {
                    if ($entryValue != $value) {
                        $include = false;
                        break;
                    }
                }
            }

            if (!$include) {
                return null;
            }
        }

        return $entry;
    }

    /**
     * Hilfsmethode zum Extrahieren von Werten aus verschachtelten Arrays
     */
    protected function extractValue($array, $field)
    {
        // Unterstützung für Punkte-Notation, z.B. "date_time.date"
        if (strpos($field, '.') !== false) {
            $parts = explode('.', $field);
            $value = $array;

            foreach ($parts as $part) {
                if (isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    return null;
                }
            }

            return $value;
        }

        return isset($array[$field]) ? $array[$field] : null;
    }

    /**
     * Hilfsmethode zum Vergleichen verschiedener Datentypen
     */
    protected function compare($a, $b)
    {
        // Für NULL-Werte
        if ($a === null && $b === null) return 0;
        if ($a === null) return -1;
        if ($b === null) return 1;

        // Für Datumsstrings
        if (
            preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $a) &&
            preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $b)
        ) {
            return strtotime($a) - strtotime($b);
        }

        // Standard-Vergleich
        return $a <=> $b;
    }

    /**
     * Statische Fabrikmethode für flüssigere API
     */
    public static function create()
    {
        return new self();
    }
}
