# FOR Calendars

## 4.0.x
- Removed old Texteditor profile generation
- Small deprecation fixes
- Removed conversion from sked-calendars

## 3.8

* uses: actions/checkout@v3 by @aeberhard in https://github.com/FriendsOfREDAXO/forcal/pull/37
* Bump spatie/calendar-links from 1.4.3 to 1.8.0 by @dependabot in https://github.com/FriendsOfREDAXO/forcal/pull/38
* 3.8.0 by @skerbis in https://github.com/FriendsOfREDAXO/forcal/pull/40


## 3.7.x
Quick Navigation button now delivered by FORCal

## 3.6.0
- Fix: Die Checkbox "Ganztägiges Ereignis" scheint nicht zu greifen  @chrisrex

## 3.4.0 - 3.5.2 
- Neu: Rechte für Kategorie-Verwaltung
- Neu: Rechte für Orte-Verwaltung
- Kleine optische Anpassungen hier und da

**Hinweis zum Update**
Die Rechte für die Orte- und Kategorieverwaltung müssen nach dem Update festgelegt werden. 

Danke: @eaCe , @isospin

**Hinweis zum Update**
Die Rechte für die Orte- und Kategorieverwaltung müssen nach dem Update festgelegt werden. 

## 3.3.0
- Bei Click auf + in der Terminliste wird das aktuelle Datum übernommen
- Neue Frontend-Anleitung, danke @godsdog
- CSRF aus Frontend entfernt, danke @godsdog
- Kalender wird im Systemblock gelistet

### 3.1.0
- neu:Termin kopieren - "Kopie" anhängen und deaktivieren  @danspringer

### 3.0.0 
- a new name
- Vendoren aktualisiert
- Erweiterung der Funktion `getFromToDate()`, um das Datum mit Jahreszahl auszugeben und ein ganztägiges Ereignis (quick&dirty) in der Ausgabe als einzelnes Datum zu erhalten.

Möglicher Aufruf der Funktion aus Modul:

```php
$date = \forCal\Utils\forCalDateTimeHelper::getFromToDate(new \DateTime($data['start']), 
new \DateTime($data['end']), 
2, 
$data['date_time']['date_interval_days']);
```


### 2.1.0
- Tastenkombinationen zum hinzufügen und speichern von Terminen hinzugefügt @eaCe 
- Auswahl ob ganztägig vorausgewählt @eaCe 
- Einstellungen aktualisiert @eaCe 

### 2.0.1
- Backend Language fix

### 2.0.0
- NEU Wiederkehrende Termine jede/r xte Wochentag im Monat, monatlich(Wochentag)
- NEU Sortierung nach Startzeit innerhalb eines Tages
- NEU Watson-Integration
- NEU Kalender-Link
- NEU Kalenderblatt bietet in jeder Ansicht (+) für Termine
- Uid
- Vendoren aktualisiert
- Pflichtfelder bei der Termineingabe
- diverse Bugfixes
- Wenn doku plugin installiert, wird es gelöscht


### 1.10.0
- Doku-Plugin entfernt und nach README übertragen
- Traducción en castellano @nnandes2062

### 1.9.0
- Diverse Korrekturen @alexplusde
- Docs werden automatisch installiert @alexplusde
- Einstellungen stehen erst nach Rechtevergabe zur Verfügung

### 1.8.0
- Zeitstempel von Haus aus korrekt setzen
- MySQL auf min. 5.6.15 gesetzt

### 1.7.2
API-Aufruf geändert wg. 404 auf index in YRewrite

### 1.7.1
Fixed Warning


### 1.7.0
- Danish translation, tak @PReimers
- optimized images
- Allow setting of form field prefix and suffix thx @DanielWeitenauer
- Add immutable venue and category data thx @DanielWeitenauer
- prevent error message if no fields are set via yaml thx @DanielWeitenauer

### 1.6.2

some bugfixes for attributes and settings

### 1.6.1

changed: index.php now uses: includeCurrentPageSubPath


### Version 1.6.0

* Kategorie-Colors Support für `rgb` und `rgba` hinzugefügt.
* Demo `rgba` Color im default-set hinzugefügt.
* Add PR von Wolfgang Bund "Spalte löschen bei Terminen hinzugefügt"
* Add PR von Wolfgang Bund "Erweiterte Kategorien"
* Add PR von Thomas Skerbis "Attribute fixed" für custom fields
* Add changelog
* Termine können dupliziert werden
* Status/Funktion in Listenansicht um duplizieren, editeren ergänzt
* Status/Funktion optisch und funktional in Termin, Kategorie und Ort angeglichen
* Ganztägige Events können gekenzeichnet werden
* Urhzeit 00:00:00 bei Start und End-Datum führt nach speichern zu Flag ganztägig
* Update sql für Flag ganztätgig sorgt dafür das alle alten Events die ganztägig sind gekennzeichnet werden
* Kategorie status selection bei API output wird nur berücksichtigt sofern eine Relation zur einer Kategorie vorhanden ist
* API Search und Detailansicht berücksichtigt full_time und gibt immer Start und End-Datum zurück
* Farbe in Kategorie ist Pflichtfeld
* Pagination in der API hinzugefügt
