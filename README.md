# REDAXO AddOn: FORCal

Das AddOn ist ein variabel einsetzbarer Kalender(-Generator), Skedule, Newssystem, Event- und Terminplaner für REDAXO 5.x. Das AddOn kann nach Belieben angepasst werden. Es können jderzeit zusätzliche Eingabe-Felder hinzugefügt werden. Darüber hinaus unterstützt das AddOn mehrsprachige REDAXO-Installationen.

## Hauptmerkmale

*   **Flexibler Kalender**: Erstellung von Terminkalendern, Veranstaltungskalendern und Nachrichten.
*   **Erweiterbarkeit**: Anpassbare Eingabeformulare durch benutzerdefinierte `.yml`-Dateien.
*   **Mehrsprachigkeit**: Unterstützung mehrsprachiger REDAXO-Installationen.
*   **Einfache Datenabfrage**: Abruf der Termine über PHP-Class-Methoden, Rückgabe als Objekte.
*   **API für JSON-Ausgabe**: Ermöglicht die Ausgabe und Filterung von Events als JSON.
*   **forCalLink-Klasse**: Generierung von Links für `.ics`-Dateien, Terminübergabe zu Google Calendar und Microsoft Outlook (Web).
*   **Backend- und Frontend-Integration**: Vollständig kompatibel mit der bestehenden `forCalHandler`-Implementierung.
*   **Benutzerberechtigungen**: Automatische Berücksichtigung von Benutzerrechten im Backend und Frontend.

## FORCalEventsFactory

Die `forCalEventsFactory`-Klasse bietet eine elegante und flexible Methode, um Termine in forCal abzufragen. Mit einer intuitiven Fluent API ermöglicht sie komplexe Abfragen ohne umständliche Parameter und unterstützt automatisch die korrekte Behandlung von Benutzerberechtigungen im Backend und Frontend.

### Grundlegende Verwendung

Die Factory-Klasse bietet eine fluide API für das Abrufen von Terminen:

```php
// Alle Termine ab heute für die nächsten 6 Monate
$termine = forCalEventsFactory::create()
    ->from('today')
    ->to('+6 months')
    ->get();
```

### Methoden im Überblick

#### Initialisierung

*   **create()**: Statische Methode zum Erstellen einer neuen Factory-Instanz

#### Zeitraum

*   **from($startTime)**: Setzt den Startzeitpunkt (z.B. 'now', 'today', 'all', DateTime-Objekt)
*   **to($endTime)**: Setzt den Endzeitpunkt (z.B. '+6 months', '+1 year', DateTime-Objekt)

#### Filterung

*   **inCategories($categories)**: Filtert nach Kategorien (ID oder Array von IDs)
*   **atVenue($venueId)**: Filtert nach Veranstaltungsort
*   **withFilter($field, $value)**: Fügt einen benutzerdefinierten Filter hinzu
*   **withUserPermissions($use)**: Aktiviert/Deaktiviert die Benutzerberechtigungen explizit

#### Sortierung

*   **sortBy($field, $direction)**: Fügt ein Sortierkriterium hinzu ('asc' oder 'desc')

#### Ausführung

*   **get()**: Führt die Abfrage aus und gibt die Termine zurück
*   **getEntryById($id)**: Gibt einen einzelnen Termin nach ID zurück

### Beispiele

#### Zeiträume mit Strings

```php
// Termine ab jetzt
$termine = forCalEventsFactory::create()
    ->from('now')
    ->get();

// Termine ab heute bis Ende des Jahres
$termine = forCalEventsFactory::create()
    ->from('today')
    ->to('last day of december')
    ->get();

// Alle vergangenen und zukünftigen Termine
$termine = forCalEventsFactory::create()
    ->from('all')
    ->get();

// Termine der letzten Woche
$termine = forCalEventsFactory::create()
    ->from('-1 week')
    ->to('today')
    ->get();

// Termine für die nächsten 4 Wochen
$termine = forCalEventsFactory::create()
    ->from('now')
    ->to('+4 weeks')
    ->get();
```

#### Zeiträume mit DateTime-Objekten

```php
// Mit DateTime-Objekten für den aktuellen Monat
$startDate = new \DateTime('first day of this month');
$endDate = new \DateTime('last day of this month');

$termine = forCalEventsFactory::create()
    ->from($startDate)
    ->to($endDate)
    ->get();

// Einen spezifischen Zeitraum definieren
$startDate = new \DateTime('2024-05-01');
$endDate = new \DateTime('2024-08-31');

$termine = forCalEventsFactory::create()
    ->from($startDate)
    ->to($endDate)
    ->get();

// DateTime mit spezifischen Uhrzeiten
$start = new \DateTime();
$start->setTime(8, 0, 0); // Heute um 8:00 Uhr

$end = new \DateTime();
$end->setTime(18, 0, 0); // Heute um 18:00 Uhr
$end->modify('+7 days'); // Eine Woche später

$termine = forCalEventsFactory::create()
    ->from($start)
    ->to($end)
    ->get();
```

#### Filtern nach Kategorien

```php
// Termine der Kategorie 3
$termine = forCalEventsFactory::create()
    ->from('today')
    ->inCategories(3)
    ->get();

// Termine der Kategorien 1, 3 und 5
$termine = forCalEventsFactory::create()
    ->from('today')
    ->inCategories([1, 3, 5])
    ->get();
```

#### Filtern nach Orten

```php
// Termine am Ort mit ID 2
$termine = forCalEventsFactory::create()
    ->from('today')
    ->atVenue(2)
    ->get();

// Termine der Kategorie 3 am Ort 2
$termine = forCalEventsFactory::create()
    ->from('today')
    ->inCategories(3)
    ->atVenue(2)
    ->get();
```

#### Benutzerdefinierte Filter

```php
// Nur Termine mit Bildern
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('image', true)
    ->get();

// Termine in Berlin
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('city', 'Berlin')
    ->get();

// Termine mit Bildern, aber ohne Datei-Anhänge
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('image', true)
    ->withFilter('file', false)
    ->get();
```

#### Sortierung

```php
// Nach Titel alphabetisch sortieren
$termine = forCalEventsFactory::create()
    ->from('today')
    ->sortBy('title', 'asc')
    ->get();

// Nach Datum absteigend (neueste zuerst)
$termine = forCalEventsFactory::create()
    ->from('today')
    ->sortBy('date_time.date', 'desc')
    ->get();

// Mehrfache Sortierung: Erst nach Kategorie, dann nach Datum
$termine = forCalEventsFactory::create()
    ->from('today')
    ->sortBy('category_name', 'asc')  // Primäres Sortierkriterium
    ->sortBy('date_time.date', 'asc') // Sekundäres Sortierkriterium
    ->get();
```

#### Frontend/Backend-Erkennung

Die Factory erkennt automatisch, ob sie im Frontend oder Backend verwendet wird:

```php
// Im Frontend: Ignoriert Benutzerberechtigungen
$termine = forCalEventsFactory::create()
    ->from('today')
    ->get();

// Im Backend: Berücksichtigt Benutzerberechtigungen automatisch
$termine = forCalEventsFactory::create()
    ->from('today')
    ->get();

// Explizit Benutzerberechtigungen ignorieren (z.B. für Admin-Übersicht)
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withUserPermissions(false)
    ->get();
```

#### Einzelnen Termin abrufen

```php
// Termin mit ID 123 abrufen
$termin = forCalEventsFactory::create()
    ->getEntryById(123);

// Termin mit ID 123 abrufen, aber nur wenn er ein Bild hat
$termin = forCalEventsFactory::create()
    ->withFilter('image', true)
    ->getEntryById(123);
```

#### Komplexere Beispiele

```php
// Workshop-Termine aus Berlin mit Bildern, nach Datum sortiert
$termine = forCalEventsFactory::create()
    ->from('today')
    ->to('+1 year')
    ->inCategories(3)  // Angenommen, Kategorie 3 ist "Workshops"
    ->withFilter('city', 'Berlin')
    ->withFilter('image', true)
    ->sortBy('date_time.date', 'asc')
    ->get();

// Alle vergangenen Konzerte, gruppiert nach Veranstaltungsort
$termine = forCalEventsFactory::create()
    ->from('all')
    ->to('today')
    ->inCategories(2)  // Angenommen, Kategorie 2 ist "Konzerte"
    ->sortBy('venue_name', 'asc')
    ->sortBy('date_time.date', 'desc')
    ->get();
```

## Integration in Module

### Einfaches Modul zur Anzeige kommender Termine

```php
// Termine abrufen
$termine = forCalEventsFactory::create()
    ->from('today')
    ->to('+3 months')
    ->sortBy('date_time.date', 'asc')
    ->get();

// Termine ausgeben
echo '<div class="termine-liste">';
foreach ($termine as $termin) {
    echo '<div class="termin">';
    echo '<h3>' . $termin['title'] . '</h3>';
    echo '<p>' . $termin['date_time']['date'] . '</p>';

    if (!$termin['date_time']['full_time'] && !empty($termin['date_time']['time'])) {
        echo '<p>' . $termin['date_time']['time'] . '</p>';
    }

    if (isset($termin['teaser'])) {
        echo '<div class="teaser">' . $termin['teaser'] . '</div>';
    }

    echo '</div>';
}
echo '</div>';
```

### Filtern nach benutzerdefinierten Feldern

```php
// Termine in einer bestimmten Stadt und mit einem bestimmten Attribut
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('city', 'München')
    ->withFilter('besondere_eigenschaft', true)
    ->get();
```

### Verwendung von Callback-Funktionen für komplexe Filter

```php
// Nur Termine, deren Name das Wort "Workshop" enthält
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('custom_filter', function($entry) {
        return stripos($entry->entry_name, 'Workshop') !== false;
    })
    ->get();

// Nur Termine, die vormittags stattfinden
$termine = forCalEventsFactory::create()
    ->from('today')
    ->withFilter('time_filter', function($entry) {
        $hour = (int)substr($entry->entry_start_time, 0, 2);
        return $hour >= 8 && $hour < 12;
    })
    ->get();
```

## Eigene Felder definieren

Eigene Felder können im Ordner `/redaxo/data/addons/forcal/definitions/` angelegt werden. Die nach Installation dort befindlichen .yml Dateien erzeugen die Standardfelder. Möchte man eigene Definitionen erstellen, erstellt man entsprechende yml-files mit dem Prefix `custom_`. Möchte man die Standardfelder behalten und weiternutzen, sollten diese auch in die custom Definitionen kopiert werden.
Beispiele für mögliche Felder findet man auch in den mitgelieferten yml.

### Feldtypen

*   **media**: Definiert ein Medienauswahfeld für Medien aus dem Medienpool
*   **medialist**: Definiert ein Mehrfach-Medienauswahfeld für Medien aus dem Medienpool
*   **text**: Stellt eine Texteingabe zur Verfügung
*   **textarea**: Stellt eine mehrzeilige Texteingabe zur Verfügung
*   **Link**: Stellt einen Auswahldialog zur Auswahl eines internen Links zur Verfügung
*   **Linklist**: Stellt einen Auswahldialog zur Mehrfach-Auswahl eines internen Links zur Verfügung
*   **select**: Fügt dem Formular eine Selectbox mit definierbaren Werten und ggf. Werte aus einer Tabelle hinzu
*   **radio**: Fügt dem Formular eine Radiobutton Group mit definierbaren Werten und ggf. Werte aus einer Tabelle hinzu
*   **checkbox**: Fügt dem Formular eine Checkbox mit definierbaren Werten hinzu
*   **checkboxsql**: Fügt dem Formular eine Checkbox mit Werten aus einer Tabelle hinzu

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
    attribute:
      multiple: multiple
      size: 5
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

forCal erlaubt es, einen beliebigen Editor für die Eingabe in den Textfeldern zu wählen. Die Standard Textfelder können über JSON-Definitionen eingestellt werden. Das Verfahren entspricht der Lösung in yForm.

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

Es können beliebige weitere Attribute hinzugefügt werden wie `required`, `data-attribute`, Zeichenlänge etc..

### Eigene Textfelder

In den Eigenen Feldern können für jedes Feld Attribute angegeben werden, die die Textfelder beeinflussen und so auch Editoren einbinden.

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

*   http://www.redaxo.org
*   https://github.com/FriendsOfREDAXO

**Development-Team**

*   [Joachim Dörr](https://github.com/joachimdoerr)
*   [Wolfgang Bund](https://github.com/dtpop)
*   [Thomas Skerbis](https://github.com/skerbis)
*   [Christian Gehrke](https://github.com/chrison94)

**Chief Developer**

[Joachim Dörr](https://github.com/joachimdoerr)

Mit freundlicher Unterstützung durch:

[Deutsche Fußball-Route NRW e.V.](https://dfr-nrw.de)
```
