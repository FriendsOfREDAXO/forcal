<?php

/**
 * Daten für den forCal-Picker und die Schnellanlage im Kalenderblatt.
 *
 * GET  type=entry|category|venue, q, category, past, public, offset   Suche
 * GET  type=…, keys=1,2,3                                             Einträge zu gespeicherten IDs
 * POST action=create, name, date, all_day, start, end, category        legt einen Termin an
 *
 * Nur für angemeldete Backend-Benutzer. Wer nur das Recht forcal[pick] hat, darf wählen, aber nicht anlegen.
 *
 * @package forcal
 * @license MIT
 */

use forCal\Utils\forCalPicker;
use forCal\Utils\forCalUserPermission;

class rex_api_forcal_picker extends rex_api_function
{
    public const CSRF = 'forcal_picker';
    private const PAGE = 40;

    /** @var bool Nur im Backend aufrufbar */
    protected $published = false;

    public function execute(): rex_api_result
    {
        rex_response::cleanOutputBuffers();
        $user = rex::getUser();
        if (!rex::isBackend() || null === $user || !self::canPick($user)) {
            self::fail(rex_response::HTTP_FORBIDDEN, rex_i18n::msg('forcal_picker_denied'));
        }

        $type = in_array(rex_request('type', 'string'), forCalPicker::TYPES, true) ? rex_request('type', 'string') : 'entry';
        if ('create' === rex_request('action', 'string')) {
            $this->create($user);
        }

        $ids = forCalPicker::ids(rex_request('keys', 'string'));
        if ([] !== $ids) {
            rex_response::sendJson(['items' => $this->resolve($type, $ids, $user)]);
            exit;
        }

        $result = match ($type) {
            'category' => $this->categories($user),
            'venue' => $this->venues(),
            default => $this->entries($user),
        };
        $result['categories'] = array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => forCalPicker::name($row)], self::allowedCategories($user, false));
        $result['create'] = 'entry' === $type && $user->hasPerm('forcal[]')
            ? array_map(static fn (array $row): array => ['id' => (int) $row['id'], 'name' => forCalPicker::name($row)], self::allowedCategories($user, true))
            : [];
        rex_response::sendJson($result);
        exit;
    }

    public static function canPick(rex_user $user): bool
    {
        return $user->isAdmin() || $user->hasPerm('forcal[]') || $user->hasPerm('forcal[pick]');
    }

    /**
     * Kategorien, aus denen der Benutzer wählen ($forEditing = false) oder in denen er anlegen darf.
     *
     * @return list<array<string, mixed>>
     */
    public static function allowedCategories(rex_user $user, bool $forEditing): array
    {
        $rows = rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('forcal_categories') . ' WHERE status = 1 ORDER BY name_' . (int) rex_clang::getStartId());
        if ($user->isAdmin() || $user->hasPerm('forcal[all]') || (!$forEditing && $user->hasPerm('forcal[pick]'))) {
            return $rows;
        }
        $allowed = array_map('intval', forCalUserPermission::getUserCategories($user->getId()));

        return array_values(array_filter($rows, static fn (array $row): bool => in_array((int) $row['id'], $allowed, true)));
    }

    /**
     * @return array<string, mixed>
     */
    private function entries(rex_user $user): array
    {
        $allowed = array_map(static fn (array $row): int => (int) $row['id'], self::allowedCategories($user, false));
        if ([] === $allowed) {
            return ['items' => [], 'total' => 0, 'offset' => 0];
        }
        $category = rex_request('category', 'int');
        $where = ['en.category IN (' . implode(',', in_array($category, $allowed, true) ? [$category] : $allowed) . ')'];
        $params = [];
        if (rex_request('public', 'bool')) {
            $where[] = 'en.status = 1';
        }
        $past = rex_request('past', 'bool');
        if (!$past) {
            // Kommend: endet heute oder später, oder eine Serie, die noch läuft.
            $where[] = "(en.end_date >= CURDATE() OR (en.type = 'repeat' AND (en.end_repeat_date IS NULL OR en.end_repeat_date = '0000-00-00' OR en.end_repeat_date >= CURDATE())))";
        }
        foreach (preg_split('/\s+/', trim(rex_request('q', 'string'))) ?: [] as $word) {
            if ('' === $word) {
                continue;
            }
            $like = [];
            foreach (rex_clang::getAllIds() as $clangId) {
                $like[] = 'en.name_' . (int) $clangId . ' LIKE ?';
                $params[] = '%' . addcslashes($word, '%_\\') . '%';
            }
            $where[] = '(' . implode(' OR ', $like) . ')';
        }

        $offset = max(0, rex_request('offset', 'int'));
        $from = ' FROM ' . rex::getTable('forcal_entries') . ' en WHERE ' . implode(' AND ', $where);
        $total = (int) rex_sql::factory()->getArray('SELECT COUNT(*) AS c' . $from, $params)[0]['c'];
        $rows = rex_sql::factory()->getArray('SELECT en.*' . $from . ' ORDER BY en.start_date ' . ($past ? 'DESC' : 'ASC') . ', en.start_time ASC LIMIT ' . $offset . ', ' . self::PAGE, $params);

        return ['items' => array_map(fn (array $row): array => $this->describeEntry($row), $rows), 'total' => $total, 'offset' => $offset];
    }

    /**
     * @return array<string, mixed>
     */
    private function categories(rex_user $user): array
    {
        $search = mb_strtolower(trim(rex_request('q', 'string')));
        $items = [];
        foreach (self::allowedCategories($user, false) as $row) {
            if ('' === $search || str_contains(mb_strtolower(forCalPicker::name($row)), $search)) {
                $items[] = $this->describeCategory($row);
            }
        }

        return ['items' => $items, 'total' => count($items), 'offset' => 0];
    }

    /**
     * @return array<string, mixed>
     */
    private function venues(): array
    {
        $search = mb_strtolower(trim(rex_request('q', 'string')));
        $items = [];
        foreach (rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('forcal_venues') . ' WHERE status = 1 ORDER BY name_' . (int) rex_clang::getStartId()) as $row) {
            $item = $this->describeVenue($row);
            if ('' === $search || str_contains(mb_strtolower($item['title'] . ' ' . $item['subtitle']), $search)) {
                $items[] = $item;
            }
        }
        $offset = max(0, rex_request('offset', 'int'));

        return ['items' => array_slice($items, $offset, self::PAGE), 'total' => count($items), 'offset' => $offset];
    }

    /**
     * @param list<int> $ids
     *
     * @return list<array<string, mixed>>
     */
    private function resolve(string $type, array $ids, rex_user $user): array
    {
        $value = implode(',', $ids);
        if ('category' === $type) {
            return array_map(fn (array $row): array => $this->describeCategory($row), forCalPicker::categories($value, false));
        }
        if ('venue' === $type) {
            return array_map(fn (array $row): array => $this->describeVenue($row), forCalPicker::venues($value, false));
        }
        $allowed = array_map(static fn (array $row): int => (int) $row['id'], self::allowedCategories($user, false));

        return array_map(
            fn (array $row): array => $this->describeEntry($row),
            array_values(array_filter(forCalPicker::entries($value, false), static fn (array $row): bool => in_array((int) $row['category'], $allowed, true))),
        );
    }

    /**
     * Legt einen einmaligen Termin an: aus dem Picker-Dialog oder aus der Schnellanlage im Kalenderblatt.
     */
    private function create(rex_user $user): never
    {
        if ('post' !== rex_request_method() || !rex_csrf_token::factory(self::CSRF)->isValid()) {
            self::fail(rex_response::HTTP_FORBIDDEN, rex_i18n::msg('csrf_token_invalid'));
        }
        $category = rex_post('category', 'int');
        $allowed = array_map(static fn (array $row): int => (int) $row['id'], self::allowedCategories($user, true));
        if (!$user->hasPerm('forcal[]') || !in_array($category, $allowed, true)) {
            self::fail(rex_response::HTTP_FORBIDDEN, rex_i18n::msg('forcal_picker_category_denied'));
        }
        $name = trim(rex_post('name', 'string'));
        if ('' === $name) {
            self::fail(rex_response::HTTP_BAD_REQUEST, rex_i18n::msg('forcal_picker_name_required'));
        }
        $date = rex_post('date', 'string');
        $endDate = rex_post('end_date', 'string') ?: $date;
        $allDay = rex_post('all_day', 'bool');
        $start = rex_post('start', 'string');
        $end = rex_post('end', 'string');
        $validDate = static fn (string $value): bool => 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && false !== strtotime($value);
        $validTime = static fn (string $value): bool => 1 === preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value);
        if (!$validDate($date) || !$validDate($endDate) || $endDate < $date || (!$allDay && (!$validTime($start) || !$validTime($end)))) {
            self::fail(rex_response::HTTP_BAD_REQUEST, rex_i18n::msg('forcal_picker_period_invalid'));
        }
        if (!$allDay && $date === $endDate && $end <= $start) {
            $end = date('H:i', strtotime($start) + 3600);
        }

        $sql = rex_sql::factory();
        $sql->setTable(rex::getTable('forcal_entries'));
        $sql->setValue('uid', uniqid((string) mt_rand(), true));
        $sql->setValue('name_' . rex_clang::getStartId(), mb_substr($name, 0, 255));
        $sql->setValue('category', $category);
        $sql->setValue('start_date', $date);
        $sql->setValue('end_date', $endDate);
        $sql->setValue('start_time', $allDay ? '00:00:00' : $start . ':00');
        $sql->setValue('end_time', $allDay ? '00:00:00' : $end . ':00');
        // rex_form speichert das Häkchen "ganztägig" als |1|.
        $sql->setValue('full_time', $allDay ? '|1|' : '');
        $sql->setValue('type', 'one_time');
        $sql->setValue('status', 1);
        $sql->addGlobalCreateFields();
        $sql->addGlobalUpdateFields();
        try {
            $sql->insert();
        } catch (rex_sql_exception $e) {
            self::fail(rex_response::HTTP_INTERNAL_ERROR, rex_i18n::msg('forcal_picker_failed'));
        }

        $row = forCalPicker::entries((string) $sql->getLastId(), false)[0] ?? null;
        rex_response::sendJson(['item' => null === $row ? null : $this->describeEntry($row)]);
        exit;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function describeEntry(array $row): array
    {
        static $categories = null;
        if (null === $categories) {
            $categories = [];
            foreach (rex_sql::factory()->getArray('SELECT * FROM ' . rex::getTable('forcal_categories')) as $category) {
                $categories[(int) $category['id']] = $category;
            }
        }
        $category = $categories[(int) $row['category']] ?? null;
        $repeat = 'repeat' === $row['type'];
        $allDay = '' !== trim((string) $row['full_time'], '| ') && '0' !== trim((string) $row['full_time'], '| ');
        $start = strtotime((string) $row['start_date']) ?: 0;
        $end = strtotime((string) $row['end_date']) ?: $start;
        $when = (string) rex_formatter::intlDate($start, 'EEE, d. MMM y');
        if ($end > $start) {
            $when .= ' – ' . rex_formatter::intlDate($end, 'd. MMM y');
        }
        if (!$allDay) {
            $when .= ', ' . substr((string) $row['start_time'], 0, 5) . '–' . substr((string) $row['end_time'], 0, 5);
        }

        return [
            'key' => (string) $row['id'],
            'title' => forCalPicker::name($row) ?: '#' . $row['id'],
            'subtitle' => $repeat ? rex_i18n::msg('forcal_picker_series_from', $when) : $when,
            'meta' => null === $category ? null : forCalPicker::name($category),
            'color' => null === $category ? null : (string) $category['color'],
            'icon' => $repeat ? 'fa-repeat' : 'fa-calendar-day',
            'offline' => 1 !== (int) $row['status'],
            'url' => rex_url::backendPage('forcal/entries', ['func' => 'edit', 'id' => (int) $row['id']], false),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function describeCategory(array $row): array
    {
        return ['key' => (string) $row['id'], 'title' => forCalPicker::name($row), 'subtitle' => '', 'meta' => 1 === (int) $row['status'] ? null : rex_i18n::msg('forcal_picker_offline'),
            'color' => (string) $row['color'], 'icon' => 'fa-th-list', 'url' => rex_url::backendPage('forcal/categories', ['func' => 'edit', 'id' => (int) $row['id']], false)];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function describeVenue(array $row): array
    {
        $address = trim(implode(', ', array_filter([trim(($row['street'] ?? '') . ' ' . ($row['housenumber'] ?? '')), trim(($row['zip'] ?? '') . ' ' . ($row['city'] ?? ''))])));

        return ['key' => (string) $row['id'], 'title' => forCalPicker::name($row), 'subtitle' => $address, 'meta' => (string) ($row['country'] ?? '') ?: null, 'color' => null,
            'icon' => 'fa-map-marker', 'url' => rex_url::backendPage('forcal/venues', ['func' => 'edit', 'id' => (int) $row['id']], false)];
    }

    private static function fail(int|string $status, string $message): never
    {
        rex_response::setStatus((string) $status);
        rex_response::sendJson(['error' => $message]);
        exit;
    }
}
