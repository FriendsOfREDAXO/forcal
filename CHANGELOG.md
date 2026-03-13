## 6.4.0 - 2026-03-13

### Added

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
