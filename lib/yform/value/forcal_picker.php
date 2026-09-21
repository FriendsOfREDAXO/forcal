<?php

use forCal\Utils\forCalPicker;

/**
 * YForm-Feld "forcal_picker": wählt Termine, Kategorien oder Orte aus forCal.
 * Gespeichert wird eine kommagetrennte Liste von IDs.
 *
 * @package forcal
 * @license MIT
 */
class rex_yform_value_forcal_picker extends rex_yform_value_abstract
{
    public function enterObject(): void
    {
        $ids = forCalPicker::ids(is_array($this->getValue()) ? implode(',', $this->getValue()) : (string) $this->getValue());
        if (1 !== (int) $this->getElement('multiple')) {
            $ids = array_slice($ids, 0, 1);
        }
        $this->setValue(implode(',', $ids));

        if ($this->needsOutput() && $this->isViewable()) {
            $this->params['form_output'][$this->getId()] = $this->parse($this->isEditable() ? 'value.forcal_picker.tpl.php' : ['value.view.tpl.php', 'value.forcal_picker.tpl.php']);
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->saveInDb()) {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }
    }

    public function getDescription(): string
    {
        return 'forcal_picker|name|label|[type entry/category/venue]|[multiple 0/1]|[max]|[category-id]|[public 0/1]|[past 0/1]|[notice]';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefinitions(): array
    {
        $categories = ['' => rex_i18n::msg('forcal_picker_yform_all_categories')];
        foreach (rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('forcal_categories') . ' ORDER BY name_' . (int) rex_clang::getStartId()) as $row) {
            $categories[(string) $row['id']] = forCalPicker::name($row);
        }
        $types = [];
        foreach (forCalPicker::TYPES as $type) {
            $types[$type] = rex_i18n::msg('forcal_picker_type_' . $type);
        }

        return [
            'type' => 'value',
            'name' => 'forcal_picker',
            'values' => [
                'name' => ['type' => 'name', 'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_label')],
                'type' => ['type' => 'choice', 'label' => rex_i18n::msg('forcal_picker_yform_type'), 'choices' => $types, 'default' => 'entry'],
                'multiple' => ['type' => 'checkbox', 'label' => rex_i18n::msg('forcal_picker_yform_multiple')],
                'max' => ['type' => 'text', 'label' => rex_i18n::msg('forcal_picker_yform_max')],
                'category' => ['type' => 'choice', 'label' => rex_i18n::msg('forcal_picker_yform_category'), 'choices' => $categories],
                'public' => ['type' => 'checkbox', 'label' => rex_i18n::msg('forcal_picker_yform_public')],
                'past' => ['type' => 'checkbox', 'label' => rex_i18n::msg('forcal_picker_yform_past')],
                'notice' => ['type' => 'text', 'label' => rex_i18n::msg('yform_values_defaults_notice')],
            ],
            'description' => rex_i18n::msg('forcal_picker_yform_description'),
            'formbuilder' => false,
            'db_type' => ['text', 'varchar(191)'],
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function getListValue($params): string
    {
        $value = (string) ($params['subject'] ?? '');
        $rows = match ((string) ($params['params']['field']['type'] ?? 'entry')) {
            'category' => forCalPicker::categories($value, false),
            'venue' => forCalPicker::venues($value, false),
            default => forCalPicker::entries($value, false),
        };
        $names = array_map(static fn (array $row): string => forCalPicker::name($row), $rows);
        if (count($names) > 4) {
            $names = [...array_slice($names, 0, 3), '+' . (count($names) - 3)];
        }

        return rex_escape(implode(', ', $names));
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function getSearchField($params): void
    {
        rex_yform_value_text::getSearchField($params);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return mixed
     */
    public static function getSearchFilter($params)
    {
        return rex_yform_value_text::getSearchFilter($params);
    }
}
