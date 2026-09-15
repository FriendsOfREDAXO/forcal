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
     * Zeitzone für den Kalender
     * @var string
     */
    private $timezone = 'Europe/Berlin';

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
            // Kategorien können entweder als Array oder kommaseparierte Liste übergeben werden
            $categoryIds = [];
            
            // Erste Option: Als Array (über POST oder GET)
            $categoriesArray = rex_request('categories', 'array', []);
            if (!empty($categoriesArray)) {
                $categoryIds = $categoriesArray;
            }
            
            // Zweite Option: Als kommaseparierte Liste (category_list=1,2,3)
            $categoryList = rex_request('category_list', 'string', '');
            if (!empty($categoryList)) {
                $categoryIds = array_map('intval', explode(',', $categoryList));
            }
            
            // Dritte Option: Als einzelne Kategorie (category=1)
            $singleCategory = rex_request('category', 'int', 0);
            if ($singleCategory > 0) {
                $categoryIds[] = $singleCategory;
            }
            
            // Restliche Parameter
            $entryId = rex_request('entry', 'int', 0);
            $filename = rex_request('filename', 'string', 'calendar');
            
            // Zeitraum: Standardwerte können über Parameter überschrieben werden
            $startOffset = rex_request('start_offset', 'string', '-1 years');
            $endOffset = rex_request('end_offset', 'string', '+2 years');
            
            // Benutzerdefinierte Zeitzone erlauben
            try {
                $requestedTz = rex_request('timezone', 'string', 'Europe/Berlin');
                // Validate timezone by attempting to create a DateTimeZone object
                new DateTimeZone($requestedTz);
                $this->timezone = $requestedTz;
            } catch (\Exception $e) {
                $this->timezone = 'Europe/Berlin';
            }
            // Start- und Enddatum festlegen
            $startDate = new DateTime($startOffset, new DateTimeZone($this->timezone));
            $endDate = new DateTime($endOffset, new DateTimeZone($this->timezone));

            // Header für Download setzen
            rex_response::sendContentType('text/calendar; charset=utf-8');
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
            'X-WR-TIMEZONE:' . $this->timezone,
        ];
        
        // Zeitzone definieren
        $ical = array_merge($ical, $this->getTimezoneComponent());

        // Termine laden
        $events = [];
        if ($entryId > 0) {
            // Einzelnen Termin laden; alle Vorkommen liegen unter "dates"
            if (count(\forCal\Handler\forCalHandler::getEntry($entryId)) > 0) {
                $events[] = \forCal\Handler\forCalHandler::exchangeEntry($entryId, false);
            }
        } else {
            // Termine nach Kategorien filtern; wiederkehrende Termine kommen bereits
            // als ein Element pro Vorkommen zurueck
            $events = \forCal\Handler\forCalHandler::exchangeEntries(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                false,
                false,
                'SORT_ASC',
                !empty($categoryIds) ? $categoryIds : null,
                null, // venueId
                1, // dateFormat
                1, // timeFormat
                [], // customFilters
                10000, // pageSize - sehr großer Wert, um alle Termine zu bekommen
                1 // pageNumber
            );
        }

        // Termine in iCal-Format konvertieren: ein VEVENT pro Vorkommen
        foreach ($events as $event) {
            if (isset($event['dates']) && is_array($event['dates']) && count($event['dates']) > 0) {
                foreach ($event['dates'] as $occurrence) {
                    $eventCopy = $event;
                    $eventCopy['start_date'] = $occurrence['entry_start_date'];
                    $eventCopy['end_date'] = $occurrence['entry_end_date'];
                    $ical = array_merge($ical, $this->convertEventToVEvent($eventCopy));
                }
            } else {
                $ical = array_merge($ical, $this->convertEventToVEvent($event));
            }
        }

        // iCal abschließen
        $ical[] = 'END:VCALENDAR';

        // Als String zurückgeben
        return implode("\r\n", $ical);
    }
    
    /**
     * Erstellt die VTIMEZONE-Komponente für den Kalender
     * 
     * @return array VTIMEZONE-Komponente als Array von Zeilen
     */
    private function getTimezoneComponent(): array
    {
        $timezone = new DateTimeZone($this->timezone);
        $transitions = $timezone->getTransitions(time(), time() + 31536000); // Ein Jahr vorausschauen
        
        if (count($transitions) < 2) {
            // Wenn keine Übergänge gefunden wurden, vereinfachte Version zurückgeben
            return [
                'BEGIN:VTIMEZONE',
                'TZID:' . $this->timezone,
                'X-LIC-LOCATION:' . $this->timezone,
                'END:VTIMEZONE'
            ];
        }
        
        // Standard- und Sommerzeit-Übergänge finden
        $standardTransition = null;
        $daylightTransition = null;
        
        foreach ($transitions as $i => $transition) {
            if ($i === 0) continue; // Ersten Eintrag überspringen
            
            if ($transition['isdst']) {
                $daylightTransition = $transition;
            } else {
                $standardTransition = $transition;
            }
        }
        
        $vtimezone = [
            'BEGIN:VTIMEZONE',
            'TZID:' . $this->timezone,
            'X-LIC-LOCATION:' . $this->timezone,
        ];
        
        // Sommerzeitinformationen hinzufügen
        if ($daylightTransition) {
            $dtStart = new DateTime('@' . $daylightTransition['ts']);
            $dtStart->setTimezone($timezone);
            
            $vtimezone[] = 'BEGIN:DAYLIGHT';
            $vtimezone[] = 'TZOFFSETFROM:' . $this->formatOffset($standardTransition['offset']);
            $vtimezone[] = 'TZOFFSETTO:' . $this->formatOffset($daylightTransition['offset']);
            $vtimezone[] = 'TZNAME:' . $daylightTransition['abbr'];
            $vtimezone[] = 'DTSTART:' . $dtStart->format('Ymd\THis');
            $vtimezone[] = 'END:DAYLIGHT';
        }
        
        // Standardzeitinformationen hinzufügen
        if ($standardTransition) {
            $dtStart = new DateTime('@' . $standardTransition['ts']);
            $dtStart->setTimezone($timezone);
            
            $vtimezone[] = 'BEGIN:STANDARD';
            $vtimezone[] = 'TZOFFSETFROM:' . $this->formatOffset($daylightTransition ? $daylightTransition['offset'] : 0);
            $vtimezone[] = 'TZOFFSETTO:' . $this->formatOffset($standardTransition['offset']);
            $vtimezone[] = 'TZNAME:' . $standardTransition['abbr'];
            $vtimezone[] = 'DTSTART:' . $dtStart->format('Ymd\THis');
            $vtimezone[] = 'END:STANDARD';
        }
        
        $vtimezone[] = 'END:VTIMEZONE';
        
        return $vtimezone;
    }
    
    /**
     * Formatiert einen Zeitzonenoffset in das iCal-Format
     * 
     * @param int $offset Offset in Sekunden
     * @return string Formatierter Offset (z.B. +0100 oder -0500)
     */
    private function formatOffset(int $offset): string
    {
        $hours = abs((int)($offset / 3600));
        $minutes = abs((int)(($offset % 3600) / 60));
        $sign = $offset >= 0 ? '+' : '-';
        
        return sprintf('%s%02d%02d', $sign, $hours, $minutes);
    }

    /**
     * Konvertiert ein Vorkommen eines Termins in das VEVENT-Format
     *
     * Erwartet die Felder aus forCalHandler::decorateEntry(): start_date/end_date
     * (jeweils einschliesslich, als Datum des Vorkommens), start_time/end_time und
     * full_time. Das Feld "end" aus decorateEntry() ist fuer FullCalendar bereits
     * exklusiv (+1 Tag) und wird hier bewusst nicht verwendet.
     *
     * @param array<string, mixed> $event Das zu konvertierende Vorkommen
     * @return list<string> Die VEVENT-Zeilen
     */
    private function convertEventToVEvent(array $event): array
    {
        $startDay = $this->toDay($event['start_date'] ?? null);
        $endDay = $this->toDay($event['end_date'] ?? null) ?? $startDay;

        if (null === $startDay) {
            return []; // Keine gültigen Datumsangaben
        }

        if ($endDay < $startDay) {
            $endDay = clone $startDay;
        }

        $uid = isset($event['id']) ? (string) $event['id'] : uniqid('forcal-');
        if (isset($event['type']) && 'repeat' === $event['type']) {
            // Jedes Vorkommen einer Wiederholung braucht eine eigene UID
            $uid .= '-' . $startDay->format('Ymd');
        }

        $title = isset($event['title']) ? $event['title'] : 'Unbenannter Termin';
        $description = '';

        if (isset($event['teaser']) && !empty($event['teaser'])) {
            $description = $event['teaser'];
        } elseif (isset($event['text']) && !empty($event['text'])) {
            $description = $event['text'];
        }

        $location = isset($event['venue_name']) ? $event['venue_name'] : '';
        $isFullDay = (bool) ($event['date_time']['full_time'] ?? $event['full_time'] ?? false);

        $lines = [];
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:' . $uid . '@' . rex::getServer();
        $lines[] = 'SUMMARY:' . $this->escapeString($title);

        if (!empty($description)) {
            $lines[] = 'DESCRIPTION:' . $this->escapeString($description);
        }

        if (!empty($location)) {
            $lines[] = 'LOCATION:' . $this->escapeString($location);
        }

        if (isset($event['category_name']) && !empty($event['category_name'])) {
            $lines[] = 'CATEGORIES:' . $this->escapeString($event['category_name']);
        }

        // DTSTAMP/CREATED als UTC ausgeben (RFC 5545-konform)
        $now = new DateTime('now', new DateTimeZone($this->timezone));
        $lines[] = 'DTSTAMP:' . $this->formatDateTime($now, true);
        $lines[] = 'CREATED:' . $this->formatDateTime($now, true);

        if ($isFullDay) {
            // Ganztägig: DTEND ist laut RFC 5545 exklusiv, also der Tag nach dem letzten Termintag
            $dtEnd = clone $endDay;
            $dtEnd->modify('+1 day');
            $lines[] = 'DTSTART;VALUE=DATE:' . $startDay->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $dtEnd->format('Ymd');
        } else {
            $timezone = new DateTimeZone($this->timezone);
            $startDateTime = new DateTime($startDay->format('Y-m-d') . ' ' . ($event['start_time'] ?? '00:00:00'), $timezone);
            $endDateTime = new DateTime($endDay->format('Y-m-d') . ' ' . ($event['end_time'] ?? '00:00:00'), $timezone);

            if ($endDateTime <= $startDateTime) {
                // Wie in der Kalenderansicht: Ende vor/gleich Start bedeutet Ende am Folgetag
                $endDateTime->modify('+1 day');
            }

            $lines[] = 'DTSTART;TZID=' . $this->timezone . ':' . $startDateTime->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=' . $this->timezone . ':' . $endDateTime->format('Ymd\THis');
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * Liest den Kalendertag aus einem Datumswert (DateTime oder String wie
     * "2026-10-05" bzw. ISO 8601 "2026-10-05T00:00:00+0200")
     *
     * Es wird nur der Datumsteil verwendet, damit ein Zeitzonen-Offset im String
     * den Tag nicht verschiebt.
     */
    private function toDay(mixed $value): ?DateTime
    {
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if (!is_string($value) || 1 !== preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return null;
        }

        return new DateTime(substr($value, 0, 10), new DateTimeZone($this->timezone));
    }

    /**
     * Formatiert ein DateTime-Objekt ins iCal-Format
     * 
     * @param DateTime $dateTime Das zu formatierende Datum
     * @param bool $isUTC Ob das Datum als UTC formatiert werden soll
     * @return string Formatiertes Datum
     */
    private function formatDateTime(DateTime $dateTime, bool $isUTC = false): string
    {
        if ($isUTC) {
            $dateTimeUTC = clone $dateTime;
            $dateTimeUTC->setTimezone(new DateTimeZone('UTC'));
            return $dateTimeUTC->format('Ymd\THis\Z');
        }
        
        return $dateTime->format('Ymd\THis');
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

        // Reihenfolge beachten: zuerst Backslashes escapen, dann Newlines/Sonderzeichen.
        // Dadurch bleibt Newline-Escaping als \n erhalten und wird nicht zu \\n.
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\r\n", "\r", "\n"], "\\n", $text);
        $text = str_replace([';', ','], ['\\;', '\\,'], $text);

        // Lange Zeilen aufteilen (RFC 5545)
        $text = wordwrap($text, 75, "\r\n ", true);

        return $text;
    }
}
