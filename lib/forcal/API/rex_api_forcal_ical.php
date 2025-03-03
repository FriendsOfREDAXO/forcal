<?php
/**
 * API-Controller für iCal-Export von forCal
 * Generiert eine iCal-Datei für Termine ab heute bis max. 5 Jahre
 * 
 * @package redaxo5
 * @license MIT
 */

class rex_api_forcal_ical extends rex_api_function
{
    /**
     * @var bool
     */
    protected $published = true;
    
    /**
     * Führt den API-Call aus
     *
     * @return rex_api_result Das Ergebnis des API-Calls
     */
    public function execute()
    {
        // Laufende Ausgabepuffer leeren
        rex_response::cleanOutputBuffers();
        
        try {
            // Parameter abrufen
            $categoryIds = rex_request('categories', 'array', []);
            $entryId = rex_request('entry', 'int', 0);
            $filename = rex_request('filename', 'string', 'calendar');
            
            // Start- und Enddatum festlegen (heute bis 5 Jahre in der Zukunft)
            $startDate = new DateTime('-10 years');
            $endDate = clone $startDate;
            $endDate->modify('+10 years');
            
            // Header für Download setzen
            rex_response::sendContentType('text/calendar');
            rex_response::setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.ics"');
            
            // Generiere iCal Inhalt
            $content = $this->generateIcal($categoryIds, $entryId, $startDate, $endDate);
            
            // Ausgabe
            rex_response::sendContent($content);
            exit;
            
        } catch (Exception $e) {
            // Bei Fehlern eine Fehlermeldung zurückgeben
            rex_response::setStatus(rex_response::HTTP_INTERNAL_ERROR);
            rex_response::sendContentType('text/plain');
            rex_response::sendContent('Fehler: ' . $e->getMessage());
            exit;
        }
    }

    /**
     * Generiert den iCal-Inhalt
     *
     * @param array $categoryIds Array mit Kategorie-IDs
     * @param int $entryId ID eines einzelnen Termins (optional)
     * @param DateTime $startDate Startdatum
     * @param DateTime $endDate Enddatum
     * @return string Der generierte iCal-Inhalt
     */
    private function generateIcal(array $categoryIds, int $entryId, DateTime $startDate, DateTime $endDate): string
    {
        // Basis-iCal-Header erstellen
        $ical = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//REDAXO CMS//forCal Calendar//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:forCal Termine',
            'X-WR-TIMEZONE:Europe/Berlin',
        ];

        // Termine laden
        if ($entryId > 0) {
            // Einzelnen Termin laden
            $events = \forCal\Handler\forCalHandler::getEntry($entryId);
        } else {
            // Termine nach Kategorien filtern
            $events = \forCal\Handler\forCalHandler::exchangeEntries(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                false,
                false,
                'SORT_ASC',
                !empty($categoryIds) ? $categoryIds : null
            );
        }

        // Termine in iCal-Format konvertieren
        foreach ($events as $event) {
            // Bei wiederkehrenden Terminen müssen wir jedes Vorkommen berücksichtigen
            if (isset($event['type']) && $event['type'] == 'repeat' && isset($event['dates'])) {
                // Für jeden wiederholten Termin ein VEVENT erstellen
                foreach ($event['dates'] as $occurrence) {
                    $eventCopy = $event;
                    // Überschreibe Start- und Enddatum mit dem jeweiligen Vorkommen
                    $eventCopy['start'] = $occurrence['entry_start_date'];
                    $eventCopy['end'] = $occurrence['entry_end_date'];
                    $vevent = $this->convertEventToVEvent($eventCopy);
                    $ical = array_merge($ical, $vevent);
                }
            } else {
                // Einzelne Termine normal konvertieren
                $vevent = $this->convertEventToVEvent($event);
                $ical = array_merge($ical, $vevent);
            }
        }
        
        // iCal abschließen
        $ical[] = 'END:VCALENDAR';
        
        // Als String zurückgeben
        return implode("\r\n", $ical);
    }

    /**
     * Konvertiert einen Termin in das VEVENT-Format
     *
     * @param array $event Der zu konvertierende Termin
     * @return array Die VEVENT-Zeilen
     */
    private function convertEventToVEvent(array $event): array
    {
        $lines = [];
        
        // Basisdaten extrahieren
        $uid = isset($event['id']) ? $event['id'] : uniqid('forcal-');
        $title = isset($event['title']) ? $event['title'] : 'Unbenannter Termin';
        $description = '';
        
        if (isset($event['teaser']) && !empty($event['teaser'])) {
            $description = $event['teaser'];
        } elseif (isset($event['text']) && !empty($event['text'])) {
            $description = $event['text'];
        }
        
        $location = isset($event['venue_name']) ? $event['venue_name'] : '';
        $isFullDay = isset($event['date_time']['full_time']) ? (bool)$event['date_time']['full_time'] : false;
        
        // Datum und Zeit verarbeiten
        $startDate = null;
        $endDate = null;
        
        if (isset($event['start'])) {
            if (is_string($event['start'])) {
                $startDate = new DateTime($event['start']);
            } elseif ($event['start'] instanceof DateTime) {
                $startDate = $event['start'];
            }
        }
        
        if (isset($event['end'])) {
            if (is_string($event['end'])) {
                $endDate = new DateTime($event['end']);
            } elseif ($event['end'] instanceof DateTime) {
                $endDate = $event['end'];
            }
        }
        
        if (!$startDate || !$endDate) {
            return $lines; // Keine gültigen Datumsangaben
        }
        
        // Eindeutige UID für jedes Vorkommen
        $eventUID = $uid;
        if (isset($event['occurrence_id'])) {
            $eventUID .= '-' . $event['occurrence_id'];
        }
        
        // VEVENT erstellen
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $eventUID . '@' . rex::getServer();
        $lines[] = 'SUMMARY:' . $this->escapeString($title);
        
        if (!empty($description)) {
            $lines[] = 'DESCRIPTION:' . $this->escapeString($description);
        }
        
        if (!empty($location)) {
            $lines[] = 'LOCATION:' . $this->escapeString($location);
        }
        
        // Kategorie hinzufügen, falls vorhanden
        if (isset($event['category_name']) && !empty($event['category_name'])) {
            $lines[] = 'CATEGORIES:' . $this->escapeString($event['category_name']);
        }
        
        // DTSTAMP (aktueller Zeitstempel)
        $lines[] = 'DTSTAMP:' . $this->formatDateTime(new DateTime());
        $lines[] = 'CREATED:' . $this->formatDateTime(new DateTime());
        
        // Wiederholungsregel (RRULE) für wiederkehrende Termine
        // Wir fügen die RRULE nur beim ersten Vorkommen hinzu
        if (!isset($event['occurrence_id']) && isset($event['type']) && $event['type'] == 'repeat') {
            $rrule = $this->generateRRule($event);
            if (!empty($rrule)) {
                $lines[] = $rrule;
            }
        }
        
        // Start- und Endzeit
        if ($isFullDay) {
            // Ganztägiges Event
            $lines[] = 'DTSTART;VALUE=DATE:' . $startDate->format('Ymd');
            
            // Bei ganztägigen Events muss das Enddatum um einen Tag erhöht werden
            $endDateAdjusted = clone $endDate;
            $endDateAdjusted->modify('+1 day');
            $lines[] = 'DTEND;VALUE=DATE:' . $endDateAdjusted->format('Ymd');
        } else {
            // Event mit Uhrzeit
            $lines[] = 'DTSTART:' . $this->formatDateTime($startDate);
            $lines[] = 'DTEND:' . $this->formatDateTime($endDate);
        }
        
        $lines[] = 'END:VEVENT';
        
        return $lines;
    }

    /**
     * Generiert eine RRULE für wiederkehrende Termine
     * 
     * @param array $event Der Termin mit den Wiederholungsregeln
     * @return string Die RRULE oder leeren String
     */
    private function generateRRule(array $event): string
    {
        // Prüfen, ob es sich um einen Termin mit Wiederholungen handelt
        $repeatType = '';
        if (isset($event['repeat'])) {
            $repeatType = $event['repeat'];
        } elseif (isset($event['repeats'])) {
            $repeatType = $event['repeats'];
        }
        
        if (empty($repeatType)) {
            return '';
        }
        
        $rrule = 'RRULE:';
        $parts = [];
        
        switch ($repeatType) {
            case 'weekly':
                $parts[] = 'FREQ=WEEKLY';
                
                // Intervall hinzufügen (in wiederholten Wochen)
                $interval = null;
                if (isset($event['repeat_interval'])) {
                    $interval = (int)$event['repeat_interval'];
                } elseif (isset($event['repeat_weeks'])) {
                    $interval = (int)$event['repeat_weeks'];
                }
                
                if ($interval && $interval > 1) {
                    $parts[] = 'INTERVAL=' . $interval;
                }
                break;
                
            case 'monthly':
                $parts[] = 'FREQ=MONTHLY';
                
                // Intervall hinzufügen (in wiederholten Monaten)
                $interval = null;
                if (isset($event['repeat_interval'])) {
                    $interval = (int)$event['repeat_interval'];
                } elseif (isset($event['repeat_months'])) {
                    $interval = (int)$event['repeat_months'];
                }
                
                if ($interval && $interval > 1) {
                    $parts[] = 'INTERVAL=' . $interval;
                }
                break;
                
            case 'monthly-week':
                $parts[] = 'FREQ=MONTHLY';
                
                // Für monatliche Wiederholungen an bestimmten Wochentagen
                // (z.B. "erster Montag im Monat")
                $day = '';
                $week = '';
                
                if (isset($event['repeat_day'])) {
                    $day = $this->getDayAbbreviation($event['repeat_day']);
                }
                
                if (isset($event['repeat_month_week'])) {
                    $week = $this->getWeekNumber($event['repeat_month_week']);
                }
                
                if ($day && $week) {
                    $parts[] = 'BYDAY=' . $week . $day;
                }
                
                // Intervall hinzufügen (in wiederholten Monaten)
                $interval = isset($event['repeat_months']) ? (int)$event['repeat_months'] : 1;
                if ($interval > 1) {
                    $parts[] = 'INTERVAL=' . $interval;
                }
                break;
                
            case 'yearly':
                $parts[] = 'FREQ=YEARLY';
                
                // Intervall hinzufügen (in wiederholten Jahren)
                $interval = null;
                if (isset($event['repeat_interval'])) {
                    $interval = (int)$event['repeat_interval'];
                } elseif (isset($event['repeat_years'])) {
                    $interval = (int)$event['repeat_years'];
                }
                
                if ($interval && $interval > 1) {
                    $parts[] = 'INTERVAL=' . $interval;
                }
                break;
                
            default:
                return ''; // Unbekannter Wiederholungstyp
        }
        
        // Enddatum der Wiederholung
        if (isset($event['end_repeat_date'])) {
            $endRepeatDate = $event['end_repeat_date'];
            
            if (is_string($endRepeatDate)) {
                $endDate = new DateTime($endRepeatDate);
            } elseif ($endRepeatDate instanceof DateTime) {
                $endDate = clone $endRepeatDate;
            } else {
                $endDate = null;
            }
            
            if ($endDate) {
                // Bei UNTIL muss die Zeit auf 23:59:59 gesetzt werden
                $endDate->setTime(23, 59, 59);
                $parts[] = 'UNTIL=' . $this->formatDateTime($endDate, true);
            }
        }
        
        if (empty($parts)) {
            return '';
        }
        
        return $rrule . implode(';', $parts);
    }

    /**
     * Konvertiert den forCal-Wochentag in iCal-Abkürzung
     */
    private function getDayAbbreviation(string $day): string
    {
        $days = [
            'mon' => 'MO', 'tue' => 'TU', 'wed' => 'WE', 
            'thu' => 'TH', 'fri' => 'FR', 'sat' => 'SA', 'sun' => 'SU'
        ];
        
        return isset($days[$day]) ? $days[$day] : '';
    }

    /**
     * Konvertiert die forCal-Wochennummer in iCal-Format
     */
    private function getWeekNumber(string $week): string
    {
        $weeks = [
            'first_week' => '1', 'second_week' => '2', 'third_week' => '3',
            'fourth_week' => '4', 'last_week' => '-1'
        ];
        
        return isset($weeks[$week]) ? $weeks[$week] : '';
    }

    /**
     * Formatiert ein DateTime-Objekt ins iCal-Format
     */
    private function formatDateTime(DateTime $dateTime, bool $withTime = true): string
    {
        if ($withTime) {
            return $dateTime->format('Ymd\THis\Z');
        }
        
        return $dateTime->format('Ymd\THis\Z');
    }

    /**
     * Escaped einen String für die Verwendung in iCal und entfernt HTML-Tags
     */
    private function escapeString(string $text): string
    {
        // HTML-Tags entfernen
        $text = strip_tags($text);
        
        // HTML-Entities dekodieren (z.B. &amp; zu &)
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Zeilenumbrüche für iCal formatieren
        $text = str_replace(["\r\n", "\n"], "\\n", $text);
        
        // Spezielle Zeichen escapen
        $text = str_replace(["\\", ";", ","], ["\\\\", "\\;", "\\,"], $text);
        
        // Lange Zeilen aufteilen (RFC 5545)
        $text = wordwrap($text, 75, "\r\n ", true);
        
        return $text;
    }
}
