# REDAXO AddOn: FOR calendar

![Screenshot](https://github.com/FriendsOfREDAXO/forcal/blob/assets/screenshot.png?raw=true)
Screenshot mit Suche

Das AddOn ist ein variabel einsetzbarer Kalender(-Generator), Skedule, Newssystem, Event- und Terminplaner für REDAXO 5.x. Das AddOn kann nach Belieben angepasst werden. Es können jderzeit zusätzliche Eingabe-Felder hinzugefügt werden. Darüber hinaus unterstützt das AddOn mehrsprachige REDAXO-Installationen.


## Features
- Erfassung der Einträge
- Wiederkehrende Ereignisse
- Zyklen für wiederkehrende Ereignisse (z.B. alle x Monate, jeder x Wochentag im Monat, alle x Wochen)
- Mehrfachkategorisierung der Einträge
- Location-Verwaltung
- Mehrsprachigkeit
- Anpassbare Eingabeformulare, definierbar über custom yml-Dateien
- Sprachspezifische Felder
- Einfacher Abruf der Termine per PHP-Class-Methoden, Rückgabe als Objekte
- API zur Ausgabe und Filterung der Events als JSON
- forCalLink class für: data: link für .ics, Terminübergabe zu Google Calendar, Microsoft Outlook (web) möglich

FOR Calendars ist daher vielfältig einsetzbar und erweiterbar. Terminkalender, Veranstaltungskalender, Nachrichten… alles ist denkbar.  

## Eigene Felder definieren
Eigene Felder können im Ordner `/redaxo/data/addons/forcal/definitions/` angelegt werden. Die nach Installation dort befindlichen .yml Dateien erzeugen die Standardfelder. Möchte man eigene Definitionen erstellen, erstellt man entsprechende yml-files mit dem Prefix `custom_`. Möchte man die Standardfelder behalten und weiternutzen, sollten diese auch in die custom Definitionen kopiert werden. 
Beispiele für mögliche Felder findet man auch in den mitgelieferten yml. 

### Feldtypen

- **media** definiert ein Medienauswahfeld für Medien aus dem Medienpool
- **medialist** definiert ein Mehrfach-Medienauswahfeld für Medien aus dem Medienpool
- **text** stellt eine Texteingabe zur Veffügung
- **textarea** stellt eine mehrzeilige Texteingabe zur Veffügung
- **Link** stellt einen Auswahldialog zur Auswahl eines internen Links zur Verfügung
- **Linklist** stellt einen Auswahldialog zur Mehrfach-Auswahl eines internen Links zur Verfügung
- **select** fügt dem Formular eine Selectbox mit definierbaren Werten und ggf. Werte aus einer Tabelle hinzu
- **radio** fügt dem Formular eine Radiobutton Group mit definierbaren Werten und ggf. Werte aus einer Tabelle hinzu
- **checkbox** fügt dem Formular eine Checkbox mit definierbaren Werten hinzu
- **checkboxsql** fügt dem Formular eine Checkbox mit Werten aus einer Tabelle hinzu

### Beispiele

#### Bei Auslieferung vorhanden: 

```yml
fields:
  - name: 'image'
    type: 'media'
    label_de: 'Bildelement'
    label_en: 'Image element'
  - name: 'file'
    type: 'media'
    label_de: 'Datei Anhang'
    label_en: 'File attachment'

langfields:
  - panel: 'images'
    label_de: 'Sprachbezogene Bildelemente'
    label_en: 'Language-related images elements'
    fields:
      - name: 'lang_image'
        type: 'media'
        label_de: 'Bild'
        label_en: 'Image'
      - name: 'lang_images'
        type: 'medialist'
        label_de: 'Bilder'
        label_en: 'Images'
```

#### Select

```yml
  - name: favfood
    type: select
    label_de: 'Lieblingsspeise'
    label_en: 'Favorite Food'
    options:
      lk: 'Leberkäse'
      pz: 'Pizza'
      tf: 'Tofu'
```

#### Mehrfachauswahl aus SQL Select:

```yml
  - name: favfood
    type: select
    label_de: 'Lieblingsspeise'
    label_en: 'Favorite Food'
    qry: 'SELECT id, name FROM rex_yourfoodtable'
```

#### Checkbox

```yml
  - name: haircolor
    type: checkbox
    label_de: 'Haarfarbe'
    label_en: 'Hair Color'
    options:
      bl: 'Blau'
      gr: 'Grün'
      rt: 'Rot'
```

#### Radiobutton

```yml
  - name: haircolor
    type: radio
    label_de: 'Haarfarbe'
    label_en: 'Hair Color'
    options:
      bl: 'Blau'
      gr: 'Grün'
      rt: 'Rot'
```


### Attribute

Zu allen Feldern können Attribute angegeben werden.

Beispiel für ein Textfeld, für das der Tinymce Editor verwendet werden soll. Das AddOn muss natürlich installiert sein damit das funktioniert.

```yml
  - name: extratext
    type: textarea
    label_de: Extratext
    label_en: Extratext
    attribute:
      class: 'tinyMCEEditor'
```

Ein Selectfeld mit mehrfacher Auswahlmöglichkeit und einer Höhe von 5 Elementen

```yml
  - name: blumenstrauss
    type: select
    label_de: 'Zusammenstellung'
    label_en: 'Collection'
    options:
      ro: 'Rosen'
      da: 'Dalien'
      tu: 'Tulpen'
    attribute:
      multiple: multiple
      size: 5
```


## Text-Editor definieren

forCal erlaubt es einen beliebigen Editor für die Eingabe in den Textfeldern zu wählen. Die Standard Textfelder können über JSON-Definitionen eingestellt werden. Das Verfahren entspreicht der Lösung in yForm. 

### Teaser und Beschreibung 
In den Einstellungen findet man zwei Felder zur Definition der *individuellen Attribute* für die Textfelder. 
Hier gibt man die gewünschten Attribute für den gewünschten Editor ein: 

z.B.
```
{"class":"redactorEditor2-forcal_text"}
```
oder
```
{"class":"tinyMCEEditor-lite"}
```
Es können beliebige weitere Attribute hinzugefügt werden wie required , data-attribute, Zeichenlänge etc.. 

### Eigene Textfelder

In den Eigenen Feldern können für jedes Feld Attribute angegeben werden, die die Textfelder beeinflussen und so auch Editoren einbinden. 


## Modulbeispiel
Hier ein Modul, das einige Filtermöglichkeiten zur Verfügung stellt. 

### Modul-Eingabe:

Es werden drei Filter-Dropdowns erstellt
- Zeitraum
- Kategorie (zieht die Werte aus der Datenbank)
- Location (zieht die Werte aus der Datenbank) + 'Alle Locations'

Anhand der gesetzten Filter werden im Output die Kalendereinträge geladen

```php 
<?php
// Perioden zur Auswahl stellen. 
// ------------------------------------
$select_p = new rex_select();
$select_p->setId('period'); 
$select_p->setAttribute('class', 'selectpicker form-control');
$select_p->setName('REX_INPUT_VALUE[2]');
$select_p->addOption('Gesamter Zeitraum','all');
$select_p->addOption('Halbes Jahr','halfayear');
$select_p->addOption('Vierteljahr','quarter');
$select_p->setSelected('REX_VALUE[2]');
$periodselect = $select_p->get(); 

// forCal-Kategorien zur Auswahl stellen. 
// ------------------------------------
$select = new rex_select();
$select->setId('forcal_category');
$select->setAttribute('class', 'selectpicker form-control');
$select->setName('REX_INPUT_VALUE[3]');
$select->addOption('Alle','');
$select->addSqlOptions('SELECT `name_1`, `id` FROM `' . rex::getTablePrefix() . 'forcal_categories` ORDER BY `name_1` ASC');
$select->setSelected('REX_VALUE[3]');
$catselect = $select->get(); 

// Venues zur Auswahl stellen. 
// ------------------------------------
$select_v = new rex_select();
$select_v->setId('forcal_category');
$select_v->setAttribute('class', 'selectpicker form-control');
$select_v->addOption('Alle',null);
$select_v->setName('REX_INPUT_VALUE[4]');
$select_v->addSqlOptions('SELECT `name_1`, `id` FROM `' . rex::getTablePrefix() . 'forcal_venues` ORDER BY `name_1` ASC');
$select_v->setSelected('REX_VALUE[4]');
$venueselect = $select_v->get(); 
?>

<fieldset class="form-horizontal">
  <div class="form-group">
    <label class="col-sm-2 control-label" for="category">Kategorie</label>
    <div class="col-sm-10">
    <?= $catselect ?>
    </div>
  </div>
</fieldset>
<fieldset class="form-horizontal">
  <div class="form-group">
    <label class="col-sm-2 control-label" for="period">Zeitraum</label>
    <div class="col-sm-10">
	    <?= $periodselect ?>
    </div>
  </div>
</fieldset>
<fieldset class="form-horizontal">
  <div class="form-group">
    <label class="col-sm-2 control-label" for="venues">Location</label>
    <div class="col-sm-10">
	    <?= $venueselect ?>
    </div>
  </div>
</fieldset>
```

### Modulausgabe:

```php
<?php
// Sprache festlegen ... ggf. aus Sprachmetas auslesen 
setlocale (LC_ALL, 'de_DE.utf8');

$categoryId ='';
$filter_date = "";
$today       = date("Y-m-d H:i:s");
$today       = strtotime($today);

//init start date and get end date
$start = date("Y-m-d H:i:s");
 $end = REX_VALUE[2];

//react to period filter
if ($end == 'all') {
    $start       = new DateTime("1900-08-09");
    $filter_date = ("2100-01-01");
}
if ($end == 'halfayear') {
    $halfayear = strtotime('+ 6 month', $today);
    $filter_date = date("Y-m-d", $halfayear);
}
if ($end == 'quarter') {
    $quarter     = strtotime('+ 3 month', $today);
    $filter_date = date("Y-m-d", $quarter);
}
//get CategoryID and VenueID
$categoryId = REX_VALUE[3];
if($categoryId==''){
$categoryId = null;
}
else {
    $categoryId = REX_VALUE[3];
}
$venues = REX_VALUE[4];
if ($venues == '') {
    $entries = \forCal\Handler\forCalHandler::getEntries($start, $filter_date, true, 'SORT_ASC', $categoryId);
}
if ($venues != '') {
    $entries = \forCal\Handler\forCalHandler::getEntries($start, $filter_date, true, 'SORT_ASC', $categoryId, $venues);
}

////////////////////////////////////////////////////////////
//////////You get the dates from every forCal entry//////////
//////////////////////////////////////////////////////////

foreach ($entries as $data) {
    // dump($data);   // Array ausgeben  
    $event                 = $data['entry'];
    //Format start and end date
    $end_date = rex_formatter::format(date_timestamp_get($event->entry_end_date),'strftime','%A, %d. %B %Y');   
    $start_date = rex_formatter::format(date_timestamp_get($event->entry_start_date),'strftime','%A, %d. %B %Y');
    //Format start time without seconds
    $entry_start_time      = $event->entry_start_time;
    $entry_start_time_date = new DateTime($entry_start_time);
    $start_time            = $entry_start_time_date->format('H:i');
    //Format end time without seconds
    $entry_end_time        = $event->entry_end_time;
    $entry_end_time_date   = new DateTime($entry_end_time);
    $end_time              = $entry_end_time_date->format('H:i');
    //dump($events);
    if ($event->entry_id != '0') {
        echo '<div id="wrapper_entry">';
        echo '<div class="entry_name"> ' . $event->entry_name . ' </div>';
        echo '<div class="entry_data"> ' . $event->venue_name . ' <i class="fa fa-map-marker" aria-hidden="true"></i></div>';
        echo '<div class="entry_data">', $start_date, ' ', ' bis ', $end_date, '</br>', $start_time, ' ', ' bis ', $end_time, '</div>';
        echo '<p class="entry_data">' . $event->type . '</p>';
        echo '<div class="entry_teaser">' . $event->entry_teaser . '</div>';
        echo '<div class="textbox entry_text">' . $event->entry_text . '</p></div>';
        echo '</div>';
    }
}
```


## FOR calendar als FullCalendar im Frontend

inkl. Anzeige einer Detailseite 

### CSS und Javascript 

Zunächst erstellt man ein Javascript zur Initialisierung des Kalenders. Dieses verwendet die API um sich die Termine des Kalenders zu holen. 
Es sucht auf der Website einen Container mit der ID `#forcal` in dem der Kalender ausgegeben wird. Hier wird ein Kalender inkl. Terminliste ausgegeben. 
Dies lässt sich leicht anpassen und den eigenen Wünschen entsprechend gestalten. Weitere Infos dazu hier: [FullCalendar - JavaScript Event Calendar](https://fullcalendar.io/) 

Das Skript legt man z.B. unter `/assets/js/forcal.js` ab 

```js
$(function () {
    forcal_init();
});

function forcal_init() {
    var forcal = $('#forcal');

    if (forcal.length) {
        forcal_fullcalendar(forcal);
    }

}

function forcal_fullcalendar(forcal) {
    var base_link = forcal.data('link'),
        calendarEl = document.getElementById(forcal.attr('id'));

    let calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: ['interaction', 'dayGrid', 'timeGrid'],
        
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'de',
        weekNumbers: true,
        weekNumbersWithinDays:true,
        dragScroll: true,
        eventLimit: true, // allow "more" link when too many events
        eventDrop: function(event, delta, revertFunc) {
        },
        eventResize: function(event, delta, revertFunc) {
        },
        eventClick: function(info) {
            window.location.replace(base_link + '?event_id=' + info.event.id);
        },
        events: {
            url: '/?rex-api-call=forcal_exchange',
            cache: true,
            error: function(xhr, type, exception) {
                 console.log("Error: " + exception);
            },
            success: function(doc) {
                // add plus circle
            }
        }
    });
    
     calendar.render();
}

```

> wird kein Rewriter verwendet muss `window.location.replace(base_link + '?event_id=' + info.event.id);` in `window.location.replace(base_link + '&event_id=' + info.event.id);`
geändert werden. 

Anschließend bindet man die erforderlichen JS und CSS für die Frontendausgabe im Template ein. 

#### CSS

```html
<link rel="stylesheet" href="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/core/main.min.css') ?>">
<link rel="stylesheet" href="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/daygrid/main.min.css') ?>"> 
<link rel="stylesheet" href="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/list/main.min.css') ?>">
<link rel="stylesheet" href="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/timegrid/main.min.css') ?>">
```


#### Javascript 

***JQuery*** muss vor allen anderen Skripten eingebunden sein. Die Skripte sollten im Header oder vor dem schließenden body Tag eingebunden werden.  

```html
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/core/main.js') ?>"></script>
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/daygrid/main.js') ?>"></script>   
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/interaction/main.js') ?>"></script>   
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/timegrid/main.js') ?>"></script>    
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/list/main.js') ?>"></script> 
<script type="text/javascript" src="<?= rex_url::base('assets/addons/forcal/vendor/fullcalendar/packages/core/locales-all.min.js') ?>"></script>
<script type="text/javascript" src="<?= rex_url::base('assets/js/forcal.js') ?>"></script>
```


### Das Modul für die Ausgabe. 

Es besteht nur aus einem Ausgabecode. (Diesen ggf. den eigenen Stilen entsprechend anpassen)

```php 
<?php
// Ausgabe der Detail-Seite
if(!is_null(rex_request::get('event_id', 'integer', null))) {
    $data = \forCal\Handler\forCalHandler::exchangeEntry(rex_request::get('event_id'), false);
    // dump($data);
    $header = '<div class="forcal-title">';
    $header .= '<h1>'.$data['title'].'</h1>';
    $header .= '<span class="forcal-meta">' . \forCal\Utils\forCalDateTimeHelper::getFromToDate(new \DateTime($data['start']), new \DateTime($data['end'])) . ' ' . \forCal\Utils\forCalDateTimeHelper::getFromToTime(new \DateTime($data['start']), new \DateTime($data['end'])) . '</span> ';
    $header .= '<hr style="border-color:'.$data['color'].'"> ';
    // Backlink
    $header .= '<div class="pull-left">
    <a class="btn btn-primary" href="'.rex_getUrl('REX_ARTICLE_ID', rex_clang::getCurrentId()).'">Kalender</a>
    </div>';
    // Bild
    if (!empty($data['entries_image'])) {
        $media = rex_media::get($data['entries_image']);
        $header .= '<img class="forcal-img" src="'.$media->getUrl().'">';
    }
    $header .= '</div>';
    $teaser = '<div class="forcal-teaser">'.$data['teaser'].'</div>';
    echo $header.$teaser.'<article class="forcal-text">'.$data['text'].'</article>';
} 
// Kalender ausgeben
else {
?>
<div id="forcal" class="forcal" data-link="<?php echo rex_getUrl('REX_ARTICLE_ID', rex_clang::getCurrentId());?>"></div>
<?php } ?>

```

## Terminlink erstellen 


```php
// Datum und Uhrzeit für Termin-Link holen und vorbereiten
$entry_start_time      	= $event->entry_start_time;
$entry_start_time_date	= new DateTime($entry_start_time);
$start_time		= $entry_start_time_date->format('H:i');
$entry_end_time        	= $event->entry_end_time;
$entry_end_time_date   	= new DateTime($entry_end_time);
$end_time             	= $entry_end_time_date->format('H:i');

// Daten und Uhrzeiten formatieren
$from_datetime 		= rex_formatter::format(date_timestamp_get($event->entry_start_date),'strftime','%Y-%m-%d').' '.$start_time;
$to_datetime 		= rex_formatter::format(date_timestamp_get($event->entry_end_date),'strftime','%Y-%m-%d').' '.$end_time;
$from 			= DateTime::createFromFormat('Y-m-d H:i', $from_datetime);
$to 			= DateTime::createFromFormat('Y-m-d H:i', $to_datetime);

$link = forCalLink::create(rex_escape($event->entry_name), $from, $to)
	->description($event->entry_teaser) // Auskommentieren, falls kein Teaser vorhanden
	->address($location); // Auskommentieren, falls keine Location vorhanden

echo '<a href="'.$link->google().'">Google Calendar</a><br>';
echo '<a href="'.$link->yahoo().'">Yahoo</a><br>';
echo '<a href="'.$link->webOutlook().'">Outlook</a><br>';
echo '<a href="'.$link->ics().'">ICS</a>';
```



## Bugtracker

Du hast einen Fehler gefunden oder ein nettes Feature parat? [Lege ein Issue an](https://github.com/FriendsOfREDAXO/forcal/issues). Bevor du ein neues Issue erstellst, suche bitte ob bereits eines mit deinem Anliegen existiert und lese die [Issue Guidelines (englisch)](https://github.com/necolas/issue-guidelines) von [Nicolas Gallagher](https://github.com/necolas/).


## Lizenz

siehe [LICENSE](https://github.com/FriendsOfREDAXO/forcal/blob/master/LICENCE)

## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO


**Development-Team / Autoren**
* [Joachim Dörr](https://github.com/joachimdoerr) 
* [Wolfgang Bund](https://github.com/dtpop) 
* [Thomas Skerbis](https://github.com/skerbis)

**Credits**

Concept,Development & Release bis Version: 1.6.2

[Joachim Dörr](https://github.com/joachimdoerr) 


Mit freundlicher Unterstützung durch: 

[Deutsche Fußball-Route NRW e.V.](https://dfr-nrw.de)
