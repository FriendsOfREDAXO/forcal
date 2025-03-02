<?php
namespace forCal\Factory;

class forCalEventsFactory
{
    protected $startTime = 'now';
    protected $endTime = '+6 months';
    protected $categories = null;
    protected $venueId = null;
    protected $customFilters = [];
    protected $sortCriteria = [];

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
     * Gibt alle Termine zurück
     */
    public function get()
    {
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
            $this->customFilters
        );

        // Wenn benutzerdefinierte Sortierung angegeben wurde
        if (!empty($this->sortCriteria)) {
            // Mehrfache Sortierung anwenden
            usort($results, function($a, $b) {
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
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $a) && 
            preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $b)) {
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
