<?php
/**
 * @author Thomas Skerbis
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;

use rex_url;
use rex;
use rex_path;
/**
 * Hilfsklasse zum Einbinden der FullCalendar-Assets im Frontend
 */
class forcalAssetHelper
{
    /**
     * Gibt die notwendigen Script- und CSS-Tags für FullCalendar zurück
     * 
     * @param bool $includeJQuery Wenn true, wird auch jQuery eingebunden
     * @param array $plugins Array mit den gewünschten FullCalendar-Plugins (core wird immer geladen)
     * @return string HTML mit den notwendigen Script- und CSS-Tags
     */
    public static function getFullCalendarAssets($includeJQuery = false, $plugins = ['daygrid', 'timegrid', 'list', 'interaction'])
    {
        $assetPath = rex_url::addonAssets('forcal');
        $output = '';
        
        // jQuery einbinden, falls gewünscht
        if ($includeJQuery) {
            $output .= '<script src="' . rex_url::base('assets/jquery/jquery.min.js') . '"></script>' . PHP_EOL;
        }
        
        // Core CSS und JS immer einbinden
        $output .= '<link rel="stylesheet" href="' . $assetPath . '/vendor/fullcalendar-6.x/core/index.global.min.css">' . PHP_EOL;
        $output .= '<script src="' . $assetPath . '/vendor/fullcalendar-6.x/core/index.global.min.js"></script>' . PHP_EOL;
        
        // Zusätzliche Plugins einbinden
        foreach ($plugins as $plugin) {
            if (file_exists(rex_path::addon('forcal', 'assets/vendor/fullcalendar-6.x/' . $plugin . '/index.global.min.js'))) {
                $output .= '<script src="' . $assetPath . '/vendor/fullcalendar-6.x/' . $plugin . '/index.global.min.js"></script>' . PHP_EOL;
            }
        }
        
        // ForCal CSS einbinden
        $output .= '<link rel="stylesheet" href="' . $assetPath . '/forcal.css">' . PHP_EOL;
        
        return $output;
    }
    
    /**
     * Gibt ein JavaScript-Template zur Initialisierung des FullCalendar zurück
     * 
     * @param string $calendarId Die ID des HTML-Elements, in dem der Kalender dargestellt werden soll
     * @param string $locale Die gewünschte Sprache (z.B. 'de', 'en')
     * @param string $apiEndpoint Der Endpunkt für die Kalender-Daten (optional, Standard ist die ForCal-API)
     * @param array $options Zusätzliche Optionen für den FullCalendar
     * @return string JavaScript-Code zur Initialisierung des Kalenders
     */
    public static function getFullCalendarInitScript($calendarId = 'calendar', $locale = 'de', $apiEndpoint = null, $options = [])
    {
        // Standard-API-Endpunkt verwenden, falls keiner angegeben wurde
        if ($apiEndpoint === null) {
            $apiEndpoint = rex_url::frontendController(['rex-api-call' => 'forcal_exchange']);
        }
        
        // Standard-Optionen für den FullCalendar
        $defaultOptions = [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek'
            ],
            'navLinks' => true,
            'editable' => false,
            'dayMaxEvents' => true,
        ];
        
        // Optionen zusammenführen
        $calendarOptions = array_merge($defaultOptions, $options);
        
        // JSON für die JavaScript-Optionen erzeugen
        $optionsJson = json_encode($calendarOptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $optionsJs = self::convertOptionsToJS($optionsJson); 
  
        // JavaScript-Code für die Initialisierung
        $script = <<<EOD
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('$calendarId');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
      locale: '$locale',
      events: '$apiEndpoint',
      $optionsJs  
    });
    
    calendar.render();
  });
</script>
EOD;
        
        return $script;
    }
    
    /**
     * Hilfsmethode zum Konvertieren der JSON-Optionen in ein gültiges JavaScript-Objekt
     */
    private static function convertOptionsToJS($optionsJson)
    {
        // Die ersten und letzten Klammern entfernen
        $optionsStr = substr($optionsJson, 1, -1);
        
        // Rückgabe, wenn Optionen leer sind
        if (empty(trim($optionsStr))) {
            return '';
        }
        
        return $optionsStr;
    }
}
