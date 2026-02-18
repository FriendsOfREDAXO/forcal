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
