## 6.5.1 - 2026-03-19

### Fixed

- **Update-Tabelle `rex_forcal_user_venues`**: Fix für #83 (Update auf 6.4.1 oder 6.5.0 nicht möglich). Spalten-Umbenennung prüft nun die Existenz der Tabelle und Spalten mit `rex_sql_table::hasColumn()` und `renameColumn()`.

## 6.5.0 - 2026-03-16

### Added

- **Inline Venue-Erstellung**: Neuer „+ Neuen Ort anlegen"-Button direkt im Termin-Formular neben dem Venue-Select. Öffnet ein Bootstrap-Modal mit mehrsprachigen Namensfeldern und Adressfeldern (Straße, Hausnummer, PLZ, Stadt, Land). Der neue Ort wird per AJAX erstellt und sofort im Select übernommen.
- **API-Endpoint `rex_api_forcal_venue_create`**: Neuer Backend-API-Endpoint für die AJAX-basierte Venue-Erstellung mit CSRF-Schutz, Berechtigungsprüfung und JSON-Response.
- **Asset `forcal-venue-inline.js`**: Eigenständiges JavaScript für das Venue-Modal mit Validierung, Fehlerbehandlung und Bootstrap-Select-Aktualisierung.
- Neue Sprachschlüssel für Inline-Venue-Erstellung (de/en).

### Fixed

- **CSRF-Token-Fehler bei Venue-Erstellung**: `rex_url::backendController()` wurde ohne `false`-Parameter aufgerufen, wodurch die URL HTML-escaped wurde (`&amp;` statt `&`). Das CSRF-Token kam dadurch als `amp;_csrf_token` beim Server an und wurde nie validiert.
- **SelectPicker-Refresh nach Venue-Erstellung**: Die Prüfung `select.hasClass('selectpicker')` schlug fehl, da REDAXO Bootstrap-Select automatisch über `data-live-search` initialisiert, ohne die Klasse `selectpicker` zwingend auf dem Element zu setzen. Geändert zu `select.data('selectpicker')`, um die tatsächliche Plugin-Instanz zu prüfen.

## 6.4.0 - 2026-03-13

### Added

- **Orts-Berechtigungssystem (Venue Permissions)**: Neues Rechtemodell für Orte auf Basis von Eigentümerschaft (`createuser`):
  - **Bearbeitungs-Scope** pro Benutzer einstellbar: `own` (nur eigene Orte), `all` (alle Orte bearbeiten), `by_owner` (Orte bestimmter Benutzer bearbeiten).
  - **Löschen** ist nur dem Ersteller (Owner) oder Administratoren erlaubt.
  - **Venue-Dropdown-Einschränkung** (`restrict_venue_selection`): Optionales Flag schränkt das Orte-Dropdown in der Terminmaske auf eigene Orte ein.
  - Orte-Liste zeigt Ersteller und letzten Bearbeiter als Zusatzinfo.
  - Neue Methoden in `forCalUserPermission`: `getVenueEditScope`, `getAllowedOwnerUserIds`, `getAllowedOwnerLogins`, `getOwnVenueIds`, `hasVenueEditPermission`, `canDeleteVenue`, `getVenueListWhere`, `isVenueSelectionRestricted`, `saveVenueEditPermission`, `saveVenueSelectionRestriction`.
  - Benutzeroberfläche in den Benutzerberechtigungen mit Radio-Buttons (eigene/alle/nach Ersteller) und Checkbox für Dropdown-Einschränkung.
  - Neue Datenbankspalte `owner_user_id` in `rex_forcal_user_venues` (ersetzt `venue_id`).
  - Neue Datenbankspalte `restrict_venue_selection` in `rex_forcal_user_media_permissions`.

### Fixed

- **Bug**: SQL-Fehler durch doppeltes Quoting bei `rex_sql::escape()` in `getVenueListWhere` und Venue-Dropdown-Filter in `entries.php` behoben (erzeugte `''value''` statt `'value'` in IN-Clauses).

- **Tagging-Widget für Custom Fields**: Neuer Feldtyp `tagging` in YAML-Definitionen ermöglicht farbige Schlagwörter direkt im Termin-/Kategorie-Formular.
- **`forCalTaggingHelper`** (`lib/forcal/Utils/forCalTaggingHelper.php`): Eigenständige PHP-Klasse (`forCal\Utils` Namespace) mit folgenden Methoden:
  - `decode(string $raw): array` – JSON → `list<array{text, color}>`
  - `encode(array $tags): string` – Tags → JSON-String
  - `getTexts(array $tags): array` – Nur Texte
  - `toHtml(array $tags, string $emptyText = ''): string` – Farbige Chip-Spans
  - `chipHtml(string $text, string $color): string` – Einzelner Chip
  - `fromRaw(string $raw, string $emptyText = ''): string` – Kurzform decode + render
  - `collectFromTable(string $table, string $field): array` – Alle eindeutigen Tags aus DB (alphabetisch)
  - `collectTextsFromTable(string $table, string $field): array` – Nur Texte aus DB
  - `sqlHasTag(string $field, string $tagText): string` – MySQL `JSON_SEARCH`-WHERE-Fragment
  - `filterByTag(array $rows, string $field, string $tagText): array` – PHP-seitiger Filter
  - `renderWidget(string $fieldId, string $fieldName, string $value, array $options): string` – Vollständiges Widget-HTML für `rex_form`
- **Eigener Suggest-API-Endpunkt** (`rex_api_forcal_tagging_suggest`): Tabellen-Whitelist (`forcal_entries`, `forcal_categories`, `forcal_venues`), erweiterbar via Extension Point `FORCAL_TAGGING_ALLOWED_TABLES`.
- **Eigenständige Assets** (`forcal-tagging.js`, `forcal-tagging.css`): Werden automatisch geladen, ohne Konflikte mit anderen Addons.
- **Custom Color Picker mit WCAG-Kontrastprüfung** (Ratio ≥ 3,0:1 für weiße Schrift).

## 6.3.0 - 2026-02-18

### Added
- **Features**: Added support for `callback` parameter in custom field definitions (`select`, `radio`, `checkbox`) to populate options dynamically via static PHP methods.
- **Feature**: Custom Fields editor.


### Changed
- **Permissions**: "Custom Fields" configuration page is now restricted to admins (`perm: admin[]`) and aligned to the right.
- **Layout**: Standardized entitiy form layout to use `div` structure instead of definition lists (`dl`, `dt`, `dd`).
- **Translations**: Updated save button label to correct `form_save` key.

### Fixed
- **Bug**: Fixed `Class "forcalCustomFieldService" not found` error when saving custom fields.
- **Bug**: Fixed `Undefined variable $sidebarContent` warning in custom fields page.
- **Docs**: Corrected misleading documentation regarding PHP execution in YAML files; removed unsafe examples.
