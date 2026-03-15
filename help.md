# FORCal – Benutzerhandbuch

FORCal ist ein Kalender-AddOn für REDAXO zur Verwaltung von Terminen, Veranstaltungen und wiederkehrenden Ereignissen. Diese Anleitung beschreibt alle Funktionen aus Sicht der Redaktion.

---

## Übersichtsseiten

FORCal bietet mehrere Bereiche, die über die Navigation erreichbar sind:

| Bereich | Beschreibung |
|---|---|
| **Kalender** | Visuelle Monats-, Wochen- und Tagesansicht aller Termine |
| **Termine** | Listenansicht aller Termine mit Filter- und Suchfunktion |
| **Kategorien** | Verwaltung der Termin-Kategorien mit Farbzuordnung |
| **Orte** | Verwaltung von Veranstaltungsorten (optional) |

---

## Kalender

Die Kalenderansicht zeigt alle Termine in einer interaktiven Übersicht. Über die Schaltflächen oben rechts lässt sich zwischen **Monat**, **Woche**, **Tag** und **Liste** wechseln.

- **Termin anlegen**: Auf einen Tag oder Zeitraum im Kalender klicken – das Termin-Formular öffnet sich mit vorausgefülltem Datum.
- **Termin öffnen**: Auf einen bestehenden Termin klicken, um ihn zu bearbeiten.
- **Navigation**: Mit den Pfeiltasten vor- und zurückblättern oder über „Heute" zum aktuellen Datum springen.

Die Termine werden farblich nach ihrer Kategorie dargestellt.

---

## Termine verwalten

### Termin-Liste

Die Terminliste zeigt alle Einträge tabellarisch an. Über der Liste stehen folgende Funktionen zur Verfügung:

- **Neuer Termin**: Über die Schaltfläche „Termin anlegen" einen neuen Eintrag erstellen.
- **Kategoriefilter**: Termine nach Kategorie filtern – die Auswahl wird bis zum Zurücksetzen gespeichert.
- **Sortierung**: Auf die Spaltenköpfe klicken, um die Sortierung zu ändern. Über „Standardsortierung" wird die Sortierung zurückgesetzt.
- **Duplizieren**: Über das Duplikatsymbol in der Liste einen bestehenden Termin kopieren.
- **Status ändern**: Über das Statussymbol lässt sich ein Termin zwischen „online" und „offline" umschalten.

### Termin anlegen / bearbeiten

Das Terminformular enthält folgende Felder:

#### Grunddaten

| Feld | Beschreibung |
|---|---|
| **Titel** | Name des Termins (Pflichtfeld). Bei mehrsprachigen Installationen gibt es je Sprache ein eigenes Feld. |
| **Kategorie** | Zuordnung zu einer Farb-Kategorie (Pflichtfeld) |
| **Ort** | Zuordnung zu einem Veranstaltungsort (optional, siehe Abschnitt „Orte") |
| **Status** | Online oder Offline – nur Online-Termine werden im Frontend angezeigt |

#### Datum & Uhrzeit

| Feld | Beschreibung |
|---|---|
| **Datum von / bis** | Start- und Enddatum des Termins |
| **Uhrzeit von / bis** | Start- und Endzeit (entfällt bei ganztägigen Terminen) |
| **Ganztägiges Ereignis** | Ist diese Option aktiviert, werden keine Uhrzeiten angezeigt |

#### Wiederholende Termine

Termine können als **wiederkehrende Ereignisse** angelegt werden. Dazu den Typ auf „wiederkehrendes Ereignis" umstellen. Folgende Wiederholungsarten stehen zur Verfügung:

| Art | Beschreibung |
|---|---|
| **Wöchentlich** | Wiederholung jede(n) X. Woche(n) |
| **Monatlich** | Wiederholung jeden X. Monat |
| **Monatlich (Wochentag)** | Z.B. „jeden ersten Montag im Monat" oder „jeden letzten Freitag" |
| **Jährlich** | Wiederholung jedes X. Jahr |

Für jede Wiederholung muss ein **Enddatum** festgelegt werden, bis zu dem die Wiederholung gilt.

#### Teaser & Beschreibung

Für jede Sprache kann ein **Teaser** (Kurzbeschreibung) und eine ausführliche **Beschreibung** eingegeben werden. Je nach Konfiguration steht ein Text-Editor zur Verfügung.

#### Eigene Felder

Falls vom Administrator eigene Felder definiert wurden (z.B. Bilder, Dateien, Zusatzinformationen), erscheinen diese im Formular unterhalb der Standardfelder. Mögliche Feldtypen sind unter anderem:

- **Bild / Bilderliste** – Medien aus dem Medienpool auswählen
- **Datei** – Dateianhänge aus dem Medienpool
- **Textfelder** – Zusätzliche Texteingaben
- **Auswahlfelder** – Dropdowns, Checkboxen oder Radiobuttons
- **Link / Linkliste** – Interne REDAXO-Seiten verknüpfen
- **Schlagwörter (Tags)** – Farbige Tags vergeben (siehe Abschnitt „Schlagwörter")

#### Tastenkürzel

Falls in den Einstellungen aktiviert, kann ein Termin mit **Strg+S** (Windows) bzw. **Cmd+S** (Mac) gespeichert werden.

---

## Kategorien

Kategorien dienen zur farblichen und inhaltlichen Gruppierung von Terminen. Jedem Termin muss genau eine Kategorie zugeordnet werden.

### Kategorie anlegen / bearbeiten

| Feld | Beschreibung |
|---|---|
| **Name** | Bezeichnung der Kategorie (je Sprache) |
| **Farbe** | Farbwert für die Kalenderdarstellung (Pflichtfeld) |
| **Status** | Online / Offline |

Die gewählte Farbe wird im Kalender und in der Terminliste als farbliche Kennzeichnung verwendet.

### iCal-Export

Zu jeder Kategorie wird eine **iCal-URL** angezeigt, über die alle Termine dieser Kategorie als Kalender-Abo abonniert werden können (z.B. in Google Calendar, Apple Kalender oder Outlook).

---

## Orte

Die Orte-Verwaltung ist optional und kann in den Einstellungen aktiviert oder deaktiviert werden. Ist sie aktiv, lassen sich Veranstaltungsorte zentral pflegen und Terminen zuordnen.

### Ort anlegen / bearbeiten

| Feld | Beschreibung |
|---|---|
| **Name** | Bezeichnung des Ortes (je Sprache) |
| **Straße / Hausnummer** | Adresse |
| **PLZ / Stadt** | Postleitzahl und Ort |
| **Land** | Land |
| **Status** | Online / Offline |

### Schnellerstellung im Termin-Formular

Direkt im Termin-Formular kann über den Button **„+ Neuen Ort anlegen"** neben dem Orte-Dropdown ein neuer Ort erstellt werden, ohne die Seite zu verlassen. Es öffnet sich ein Dialog mit Namens- und Adressfeldern. Nach dem Speichern wird der neue Ort automatisch im Dropdown ausgewählt.

---

## Schlagwörter (Tags)

Falls Schlagwort-Felder konfiguriert sind, können Termine mit farbigen Tags versehen werden.

### Tags vergeben

1. Im entsprechenden Tag-Feld den gewünschten Text eingeben.
2. Während der Eingabe erscheinen **Vorschläge** aus bereits verwendeten Tags.
3. Einen Vorschlag auswählen oder mit **Enter** einen neuen Tag anlegen.
4. Über den **Farbkreis** neben dem Eingabefeld eine Farbe für den Tag wählen.
5. Tags können über das **×** am jeweiligen Chip wieder entfernt werden.

Die Farben werden automatisch auf ausreichenden Kontrast geprüft.

---

## Filter & Suche

In der Terminliste stehen umfangreiche Filter- und Suchfunktionen zur Verfügung:

### Filtern

- **Kategorie** – Nur Termine einer bestimmten Kategorie anzeigen
- **Ort** – Nach Veranstaltungsort filtern
- **Status** – Aktive oder inaktive Termine anzeigen
- **Ersteller** – Termine eines bestimmten Benutzers anzeigen
- **Zeitraum** – Start- und Enddatum eingrenzen

### Textsuche

Über das Suchfeld lassen sich Termine nach **Titel** oder **Beschreibung** durchsuchen.

### Filter speichern

Häufig verwendete Filterkombinationen können unter einem Namen gespeichert und jederzeit wieder aufgerufen werden:

1. Gewünschte Filter einstellen.
2. Auf **„Filter speichern"** klicken und einen Namen vergeben.
3. Optional als **Standard-Filter** markieren – dieser wird dann bei jedem Seitenaufruf automatisch angewendet.

Gespeicherte Filter sind benutzerspezifisch und stehen nur dem jeweiligen Benutzer zur Verfügung.

---

## Benutzerberechtigungen

Abhängig von der Rolle und den Einstellungen des Administrators stehen folgende Berechtigungsebenen zur Verfügung:

### Kategorien

- Standardmäßig werden nur die zugewiesenen Kategorien angezeigt und bearbeitbar.
- Bei aktiviertem Recht „Zugriff auf alle Kategorien" sind sämtliche Kategorien sichtbar.

### Orte

- **Eigene Orte**: Nur selbst erstellte Orte können bearbeitet werden.
- **Alle Orte**: Alle Orte sind bearbeitbar (Löschen bleibt dem Ersteller vorbehalten).
- **Nach Ersteller**: Orte bestimmter anderer Benutzer dürfen bearbeitet werden.
- **Dropdown-Einschränkung**: Falls aktiviert, werden im Termin-Formular nur die eigenen Orte im Dropdown angezeigt.

### Medien

Die Berechtigung zum Hochladen von Medien (Bilder, Dateien) kann pro Benutzer gesteuert werden. Ohne diese Berechtigung werden Medien-Felder im Formular ausgeblendet.

---

## iCal-Export

Termine können im **iCal-Format** exportiert und in externen Kalenderprogrammen abonniert werden.

### Export-Möglichkeiten

| Variante | Beschreibung |
|---|---|
| **Alle Termine** | Kompletter Kalender-Export |
| **Nach Kategorie** | Nur Termine bestimmter Kategorien |
| **Einzelner Termin** | Ein einzelner Termin als ICS-Datei |

Die iCal-URLs finden sich in der Kategorieverwaltung. Dort kann die URL direkt kopiert und als Kalender-Abo eingerichtet werden.

### Termin-Links

Für einzelne Termine können im Frontend Links generiert werden, um den Termin in verschiedene Kalender zu übernehmen:

- **Google Calendar**
- **Outlook (Web)**
- **Yahoo Calendar**
- **ICS-Download**

---

## Tipps für den Alltag

- **Duplizieren nutzen**: Bei ähnlichen Terminen ist das Duplizieren in der Terminliste oft schneller als ein komplett neuer Eintrag.
- **Ganztägig als Standard**: In den Einstellungen kann festgelegt werden, ob „Ganztägiges Ereignis" standardmäßig vorausgewählt ist.
- **Startseite wählen**: In den Einstellungen lässt sich festlegen, ob beim Öffnen von FORCal die Kalenderansicht oder die Terminliste erscheint.
- **Standard-Filter**: Einen häufig genutzten Filter als Standard setzen – so startet die Terminliste immer mit der bevorzugten Ansicht.
- **Orte schnell anlegen**: Den „+ Neuen Ort anlegen"-Button im Terminformular nutzen, statt in die Orte-Verwaltung zu wechseln.
