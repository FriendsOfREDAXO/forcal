<?php

/**
 * forCal-Picker: ein gewöhnliches Eingabefeld mit der Klasse "forcal-picker". Das Widget ersetzt es im Backend
 * durch eine Auswahl. Was gewählt wird, bestimmt data-fp-type:
 *
 *   entry     Termine (Standard)   Wert "12,7"
 *   category  Kategorien           Wert "1,3"
 *   venue     Orte                 Wert "4"
 *
 *     <input class="forcal-picker" name="REX_INPUT_VALUE[1]" value="REX_VALUE[1]" data-fp-multiple="true">
 *
 * Dieser Helfer schreibt dasselbe Markup und löst gespeicherte Werte für die Ausgabe auf.
 *
 * @package forcal
 * @license MIT
 */

namespace forCal\Utils;

use rex;
use rex_clang;
use rex_sql;

class forCalPicker
{
    public const CSS_CLASS = 'forcal-picker';
    public const TYPES = ['entry', 'category', 'venue'];

    /**
     * @param array{type?: string, multiple?: bool, max?: int, category?: int|null, public?: bool, past?: bool, create?: bool, id?: string, class?: string} $options
     */
    public static function render(string $name, string|int|null $value = '', array $options = []): string
    {
        $type = in_array($options['type'] ?? 'entry', self::TYPES, true) ? ($options['type'] ?? 'entry') : 'entry';
        $attributes = [
            'type' => 'text',
            'class' => trim(self::CSS_CLASS . ' form-control ' . ($options['class'] ?? '')),
            'name' => $name,
            'value' => (string) $value,
            'id' => $options['id'] ?? null,
            'data-fp-type' => $type,
            'data-fp-multiple' => !empty($options['multiple']) ? 'true' : null,
            'data-fp-max' => isset($options['max']) && $options['max'] > 0 ? (string) $options['max'] : null,
            'data-fp-category' => !empty($options['category']) ? (string) (int) $options['category'] : null,
            'data-fp-public' => !empty($options['public']) ? 'true' : null,
            'data-fp-past' => !empty($options['past']) ? 'true' : null,
            'data-fp-create' => isset($options['create']) && false === $options['create'] ? 'false' : null,
        ];
        $html = '<input';
        foreach ($attributes as $key => $attribute) {
            if ((null !== $attribute && '' !== $attribute) || 'value' === $key) {
                $html .= ' ' . $key . '="' . htmlspecialchars((string) $attribute, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }

        return $html . '>';
    }

    /**
     * IDs aus einem gespeicherten Wert, ohne Doppelte, in der gewählten Reihenfolge.
     *
     * @return list<int>
     */
    public static function ids(?string $value): array
    {
        $ids = [];
        foreach (explode(',', (string) $value) as $id) {
            $id = trim($id);
            if (1 === preg_match('/^[1-9]\d*$/', $id)) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Gewählte Termine in der gewählten Reihenfolge, als Datensätze der Tabelle forcal_entries.
     * Für die Website ($onlineOnly = true) nur Termine mit Status online.
     *
     * @return list<array<string, mixed>>
     */
    public static function entries(?string $value, bool $onlineOnly = true): array
    {
        return self::rows('forcal_entries', self::ids($value), $onlineOnly);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function categories(?string $value, bool $onlineOnly = true): array
    {
        return self::rows('forcal_categories', self::ids($value), $onlineOnly);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function venues(?string $value, bool $onlineOnly = true): array
    {
        return self::rows('forcal_venues', self::ids($value), $onlineOnly);
    }

    /**
     * Name eines Datensatzes in der aktuellen Sprache, sonst in der Startsprache.
     *
     * @param array<string, mixed> $row
     */
    public static function name(array $row, ?int $clangId = null): string
    {
        $clangId ??= rex_clang::getCurrentId();
        $name = trim((string) ($row['name_' . $clangId] ?? ''));

        return '' !== $name ? $name : trim((string) ($row['name_' . rex_clang::getStartId()] ?? ''));
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>>
     */
    private static function rows(string $table, array $ids, bool $onlineOnly): array
    {
        if ([] === $ids) {
            return [];
        }
        $sql = rex_sql::factory();
        $rows = $sql->getArray('SELECT * FROM ' . $sql->escapeIdentifier(rex::getTable($table)) . ' WHERE id IN (' . implode(',', $ids) . ')' . ($onlineOnly ? ' AND status = 1' : ''));
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $result = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $byId[$id];
            }
        }

        return $result;
    }
}
