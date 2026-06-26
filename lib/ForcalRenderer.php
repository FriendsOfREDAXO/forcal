<?php

namespace FriendsOfREDAXO\Forcal;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use forCal\Handler\forCalHandler;
use rex;
use rex_addon;
use rex_clang;
use rex_logger;
use rex_media_manager;
use rex_sql;
use Throwable;

/**
 * ForcalRenderer
 *
 * Liefert kommende Termine aus dem forcal-Addon fuer das forcal_list Element.
 *
 * Zwei Modi:
 *  - "categories": Naechste N Termine aus einer (oder allen) Kategorien.
 *  - "repeat":     Naechste N Wiederholungen eines bestimmten Termins (Picker).
 *
 * URL-Pattern: optional, mit Platzhalter {id} (z.B. "/termine/?id={id}").
 *
 * @author  Friends Of REDAXO
 */
final class ForcalRenderer
{
    /** @var list<string> */
    public const ALLOWED_LAYOUTS = ['cards', 'list', 'compact'];

    public const MAX_LIMIT = 50;

    /**
     * @param array<string,mixed> $elementData
     * @return array{layout:string, items:list<array<string,mixed>>, error:?string, limit:int}
     */
    public static function fetch(array $elementData): array
    {
        $layout = (string) ($elementData['layout'] ?? 'cards');
        if (!in_array($layout, self::ALLOWED_LAYOUTS, true)) {
            $layout = 'cards';
        }

        $limit = (int) ($elementData['limit'] ?? 6);
        if ($limit < 1) {
            $limit = 6;
        }
        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }

        $teaserLength = (int) ($elementData['teaser_length'] ?? 160);
        if ($teaserLength < 30) {
            $teaserLength = 30;
        }
        if ($teaserLength > 800) {
            $teaserLength = 800;
        }

        $mode = (string) ($elementData['mode'] ?? 'categories');
        if (!in_array($mode, ['categories', 'repeat'], true)) {
            $mode = 'categories';
        }

        $urlPattern = (string) ($elementData['url_pattern'] ?? '');
        $imageField = trim((string) ($elementData['image_field'] ?? ''));
        $showImage = !empty($elementData['show_image']);

        if (!self::isAvailable()) {
            return self::err($layout, 'Das Forcal-Addon ist nicht verfuegbar.');
        }

        try {
            if ('repeat' === $mode) {
                $items = self::fetchRepeating($elementData, $limit, $teaserLength, $urlPattern, $imageField, $showImage);
            } else {
                $items = self::fetchByCategories($elementData, $limit, $teaserLength, $urlPattern, $imageField, $showImage);
            }
        } catch (Throwable $e) {
            rex_logger::logException($e);
            return self::err($layout, 'Fehler beim Laden der Termine.');
        }

        return [
            'layout' => $layout,
            'items' => $items,
            'error' => null,
            'limit' => $limit,
        ];
    }

    /**
     * @param array<string,mixed> $elementData
     * @return list<array<string,mixed>>
     */
    private static function fetchByCategories(array $elementData, int $limit, int $teaserLength, string $urlPattern, string $imageField = '', bool $showImage = false): array
    {
        $cats = self::parseCategoryIds($elementData['categories'] ?? '');
        [$start, $end] = self::resolveDateRange($elementData);
        $venueId = self::resolveVenueId($elementData);

        $entries = forCalHandler::getEntries(
            $start,
            $end,
            false,
            SORT_ASC,
            [] !== $cats ? $cats : null,
            $venueId,
            null,
            null,
            false,
        );

        $items = [];

        foreach ($entries as $event) {
            if (!is_array($event) || !isset($event['entry']) || !$event['entry'] instanceof \stdClass) {
                continue;
            }

            $item = self::makeItemFromLegacyEntry($event['entry'], $teaserLength, $urlPattern, $imageField, $showImage);
            if (null === $item) {
                continue;
            }
            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        usort($items, static fn (array $a, array $b): int => $a['sort_key'] <=> $b['sort_key']);

        return array_slice($items, 0, $limit);
    }

    /**
     * @param array<string,mixed> $elementData
     * @return list<array<string,mixed>>
     */
    private static function fetchRepeating(array $elementData, int $limit, int $teaserLength, string $urlPattern, string $imageField = '', bool $showImage = false): array
    {
        $entryId = (int) ($elementData['repeat_entry'] ?? 0);
        if ($entryId <= 0) {
            return [];
        }

        [$start, $end] = self::resolveDateRange($elementData);

        $entries = forCalHandler::getEntries(
            $start,
            $end,
            false,
            SORT_ASC,
            null,
            null,
            null,
            null,
            false,
        );

        $items = [];

        foreach ($entries as $event) {
            if (!is_array($event) || !isset($event['entry']) || !$event['entry'] instanceof \stdClass) {
                continue;
            }

            $eid = (int) ($event['entry']->entry_id ?? 0);
            if ($eid !== $entryId) {
                continue;
            }

            $item = self::makeItemFromLegacyEntry($event['entry'], $teaserLength, $urlPattern, $imageField, $showImage);
            if (null === $item) {
                continue;
            }
            $items[] = $item;
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $elementData
     * @return array{0:string,1:string}
     */
    private static function resolveDateRange(array $elementData): array
    {
        $startChoice = (string) ($elementData['start_date_choice'] ?? 'today');
        $period = (string) ($elementData['period'] ?? 'quarter');

        $start = new DateTimeImmutable('today');
        if ($startChoice === 'yesterday') {
            $start = $start->modify('-1 day');
        }

        $end = match ($period) {
            'all' => '2100-01-01',
            'halfayear' => $start->modify('+6 months')->format('Y-m-d H:i:s'),
            'year' => $start->modify('+1 year')->format('Y-m-d H:i:s'),
            'twoyears' => $start->modify('+2 years')->format('Y-m-d H:i:s'),
            'threeyears' => $start->modify('+3 years')->format('Y-m-d H:i:s'),
            default => $start->modify('+3 months')->format('Y-m-d H:i:s'),
        };

        if ($period === 'all') {
            return ['1900-01-01 00:00:00', $end];
        }

        return [$start->format('Y-m-d H:i:s'), $end];
    }

    /**
     * @param array<string,mixed> $elementData
     */
    private static function resolveVenueId(array $elementData): ?int
    {
        if (empty($elementData['filter_by_venue'])) {
            return null;
        }

        $venueId = (int) ($elementData['venue_id'] ?? 0);
        return $venueId > 0 ? $venueId : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function makeItemFromLegacyEntry(\stdClass $entry, int $teaserLength, string $urlPattern, string $imageField = '', bool $showImage = false): ?array
    {
        $startDate = $entry->entry_start_date ?? null;
        $endDate = $entry->entry_end_date ?? null;

        if (!$startDate instanceof DateTimeInterface || !$endDate instanceof DateTimeInterface) {
            return null;
        }

        $id = (int) ($entry->entry_id ?? 0);
        $title = (string) ($entry->entry_name ?? '');
        $teaserRaw = (string) ($entry->entry_teaser ?? '');
        $textRaw = (string) ($entry->entry_text ?? '');
        $teaserSource = $teaserRaw !== '' ? $teaserRaw : $textRaw;
        $teaser = self::truncate(strip_tags($teaserSource), $teaserLength);
        $color = (string) ($entry->category_color ?? '');
        $venue = (string) ($entry->venue_name ?? '');
        $startTime = (string) ($entry->entry_start_time ?? '');
        $endTime = (string) ($entry->entry_end_time ?? '');
        $fullTime = !empty($entry->full_time)
            || $startTime === ''
            || $startTime === '00:00:00';

        $href = '';
        if ($urlPattern !== '' && $id > 0) {
            $href = str_replace('{id}', (string) $id, $urlPattern);
        }

        $image = '';
        $imageUrl = '';
        if ($showImage) {
            $clang = rex_clang::getCurrentId();
            $defaultCandidates = [
                'image',
                'lang_image_' . $clang,
                'bild',
                'header_image',
                'teaser_image',
                'media',
                'preview',
            ];
            $candidates = $imageField !== '' ? [$imageField] : $defaultCandidates;
            $expanded = [];
            foreach ($candidates as $candidate) {
                $expanded[] = $candidate;
                if (!str_starts_with($candidate, 'entries_')) {
                    $expanded[] = 'entries_' . $candidate;
                }
            }

            foreach ($expanded as $key) {
                $val = $entry->{$key} ?? null;
                if (is_array($val)) {
                    $val = reset($val);
                }
                if (is_string($val) && trim($val) !== '') {
                    $image = trim($val);
                    if (str_contains($image, ',')) {
                        $image = trim(explode(',', $image)[0]);
                    }
                    break;
                }
            }

            if ($image !== '') {
                $imageUrl = rex_media_manager::getUrl('card', $image);
            }
        }

        return [
            'id' => $id,
            'title' => $title,
            'teaser' => $teaser,
            'category_name' => (string) ($entry->category_name ?? ''),
            'start' => DateTimeImmutable::createFromInterface($startDate),
            'end' => DateTimeImmutable::createFromInterface($endDate),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'full_time' => $fullTime,
            'venue' => $venue,
            'category_color' => $color,
            'href' => $href,
            'image' => $image,
            'image_url' => $imageUrl,
            'sort_key' => $startDate->format('YmdHis'),
        ];
    }

    /**
     * @param array<string,mixed> $event
     * @return array<string,mixed>|null
     */
    private static function makeItem(array $event, int $teaserLength, string $urlPattern, string $imageField = '', bool $showImage = false): ?array
    {
        // forCalHandler::decorateEntry() liefert 'start' und 'end' bereits als
        // ISO-Strings (Y-m-d\TH:i:s bzw. Y-m-d bei Ganztag). Diese Werte parsen.
        $startStr = (string) ($event['start'] ?? '');
        $endStr = (string) ($event['end'] ?? $startStr);

        if ('' === $startStr) {
            // Fallback: aus start_date (ISO) + start_time (HH:MM:SS) zusammenbauen
            $rawDate = (string) ($event['start_date'] ?? '');
            $rawTime = (string) ($event['start_time'] ?? '');
            if ('' === $rawDate) {
                return null;
            }
            $startStr = '' !== $rawTime ? substr($rawDate, 0, 10) . ' ' . $rawTime : $rawDate;
            $endRawDate = (string) ($event['end_date'] ?? $rawDate);
            $endRawTime = (string) ($event['end_time'] ?? $rawTime);
            $endStr = '' !== $endRawTime ? substr($endRawDate, 0, 10) . ' ' . $endRawTime : $endRawDate;
        }

        try {
            $start = new DateTimeImmutable($startStr);
            $end = new DateTimeImmutable($endStr);
        } catch (Exception $e) {
            return null;
        }

        $id = (int) ($event['id'] ?? 0);
        $title = (string) ($event['title'] ?? '');
        $teaserRaw = (string) ($event['teaser'] ?? '');
        $teaser = self::truncate(strip_tags($teaserRaw), $teaserLength);
        $color = (string) ($event['color'] ?? ($event['category_color'] ?? ''));
        $venue = (string) ($event['venue_name'] ?? '');
        $startTime = (string) ($event['start_time'] ?? '');
        $endTime = (string) ($event['end_time'] ?? '');
        $fullTime = !empty($event['full_time'])
            || ('' === $startTime || '00:00:00' === $startTime);

        $href = '';
        if ('' !== $urlPattern && $id > 0) {
            $href = str_replace('{id}', (string) $id, $urlPattern);
        }

        // Bild aus konfiguriertem Feldnamen oder gaengigen Feldern ermitteln.
        // forCal praefixt eigene Felder bei der Ausgabe mit "entries_" – daher
        // pruefen wir sowohl <name> als auch entries_<name>.
        $image = '';
        $imageUrl = '';
        if ($showImage) {
            $clang = rex_clang::getCurrentId();
            $defaultCandidates = [
                'image',
                'lang_image_' . $clang,
                'bild',
                'header_image',
                'teaser_image',
                'media',
                'preview',
            ];
            $candidates = '' !== $imageField ? [$imageField] : $defaultCandidates;
            // Fuer jedes Candidate auch die "entries_"-Variante pruefen.
            $expanded = [];
            foreach ($candidates as $c) {
                $expanded[] = $c;
                if (!str_starts_with($c, 'entries_')) {
                    $expanded[] = 'entries_' . $c;
                }
            }
            foreach ($expanded as $key) {
                $val = $event[$key] ?? null;
                if (is_array($val)) {
                    $val = reset($val);
                }
                if (is_string($val) && '' !== trim($val)) {
                    $image = trim($val);
                    if (str_contains($image, ',')) {
                        $image = trim(explode(',', $image)[0]);
                    }
                    break;
                }
            }
            if ('' !== $image) {
                $imageUrl = rex_media_manager::getUrl('card', $image);
            }
        }

        return [
            'id' => $id,
            'title' => $title,
            'teaser' => $teaser,
            'category_name' => (string) ($event['category_name'] ?? ''),
            'start' => $start,
            'end' => $end,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'full_time' => $fullTime,
            'venue' => $venue,
            'category_color' => $color,
            'href' => $href,
            'image' => $image,
            'image_url' => $imageUrl,
            'sort_key' => $start->format('YmdHis'),
        ];
    }

    /**
     * Liefert eine kompakte Datums-Anzeige fuer Templates.
     *
     * @param array<string,mixed> $item
     */
    public static function formatDate(array $item): string
    {
        $start = $item['start'] ?? null;
        if (!$start instanceof DateTimeInterface) {
            return '';
        }
        $datePart = $start->format('d.m.Y');
        if (!empty($item['full_time'])) {
            return $datePart;
        }
        $startTime = (string) ($item['start_time'] ?? '');
        if ('' === $startTime) {
            return $datePart;
        }
        $startTime = substr($startTime, 0, 5);
        return $datePart . ' &middot; ' . $startTime . ' Uhr';
    }

    /**
     * Baut eine lineare Liste aus Separatoren und Items auf.
     *
     * @param list<array<string,mixed>> $items
     * @return list<array<string,mixed>>
     */
    public static function buildSeparatedRows(array $items, string $groupBy): array
    {
        if (!in_array($groupBy, ['month', 'year', 'year_month'], true)) {
            return array_map(
                static fn (array $item): array => ['type' => 'item', 'item' => $item],
                $items,
            );
        }

        $rows = [];
        $lastYear = '';
        $lastMonth = '';

        foreach ($items as $item) {
            $start = $item['start'] ?? null;
            if (!$start instanceof DateTimeInterface) {
                $rows[] = ['type' => 'item', 'item' => $item];
                continue;
            }

            $year = $start->format('Y');
            $yearMonth = $start->format('Y-m');

            if ('year' === $groupBy && $year !== $lastYear) {
                $rows[] = [
                    'type' => 'separator',
                    'level' => 1,
                    'label' => $year,
                    'key' => $year,
                ];
                $lastYear = $year;
            }

            if ('month' === $groupBy && $yearMonth !== $lastMonth) {
                $rows[] = [
                    'type' => 'separator',
                    'level' => 1,
                    'label' => self::formatMonthYearLabel($start),
                    'key' => $yearMonth,
                ];
                $lastMonth = $yearMonth;
            }

            if ('year_month' === $groupBy) {
                if ($year !== $lastYear) {
                    $rows[] = [
                        'type' => 'separator',
                        'level' => 1,
                        'label' => $year,
                        'key' => $year,
                    ];
                    $lastYear = $year;
                    $lastMonth = '';
                }
                if ($yearMonth !== $lastMonth) {
                    $rows[] = [
                        'type' => 'separator',
                        'level' => 2,
                        'label' => self::formatMonthYearLabel($start),
                        'key' => $yearMonth,
                    ];
                    $lastMonth = $yearMonth;
                }
            }

            $rows[] = ['type' => 'item', 'item' => $item];
        }

        return $rows;
    }

    /**
     * @return array<int,string> id => Name
     */
    public static function getCategoryChoices(): array
    {
        if (!self::isAvailable()) {
            return [];
        }
        $clang = rex_clang::getCurrentId();
        $sql = rex_sql::factory();
        try {
            $rows = $sql->getArray(
                'SELECT id, name_' . $clang . ' AS name FROM ' . rex::getTable('forcal_categories')
                . ' WHERE status = 1 ORDER BY name_' . $clang . ' ASC',
            );
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || '' === $name) {
                continue;
            }
            $out[$id] = $name;
        }
        return $out;
    }

    /**
     * @return array<int,string> id => Name (nur wiederkehrende, aktive Eintraege)
     */
    public static function getRepeatingEntryChoices(): array
    {
        if (!self::isAvailable()) {
            return [];
        }
        $clang = rex_clang::getCurrentId();
        $sql = rex_sql::factory();
        try {
            $rows = $sql->getArray(
                'SELECT id, name_' . $clang . ' AS name, start_date'
                . ' FROM ' . rex::getTable('forcal_entries')
                . ' WHERE type = :t AND status = 1'
                . ' ORDER BY name_' . $clang . ' ASC',
                [':t' => 'repeat'],
            );
        } catch (Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || '' === $name) {
                continue;
            }
            $out[$id] = $name;
        }
        return $out;
    }

    /**
     * @return array<int,string>
     */
    public static function getVenueChoices(): array
    {
        if (!self::isAvailable() || !rex_addon::get('forcal')->getConfig('forcal_venues_enabled', true)) {
            return [];
        }

        $clang = rex_clang::getCurrentId();
        $sql = rex_sql::factory();

        try {
            $rows = $sql->getArray(
                'SELECT id, name_' . $clang . ' AS name FROM ' . rex::getTable('forcal_venues')
                . ' WHERE status = 1 ORDER BY name_' . $clang . ' ASC',
            );
        } catch (Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || $name === '') {
                continue;
            }
            $out[$id] = $name;
        }

        return $out;
    }

    public static function isAvailable(): bool
    {
        return rex_addon::get('forcal')->isAvailable()
            && class_exists(\forCal\Factory\forCalEventsFactory::class);
    }

    /**
     * @return list<int>
     */
    private static function parseCategoryIds(mixed $value): array
    {
        if (is_array($value)) {
            $raw = $value;
        } else {
            $raw = preg_split('/[\s,]+/', (string) $value) ?: [];
        }
        $out = [];
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0 && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }
        return $out;
    }

    private static function truncate(string $text, int $length): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', $text));
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $length - 1)) . '…';
    }

    /**
     * @return array{layout:string, items:list<array<string,mixed>>, error:string, limit:int}
     */
    private static function err(string $layout, string $msg): array
    {
        return [
            'layout' => $layout,
            'items' => [],
            'error' => $msg,
            'limit' => 0,
        ];
    }

    private static function formatMonthYearLabel(DateTimeInterface $date): string
    {
        $locale = str_replace('_', '-', (string) \rex_i18n::getLocale());
        if (class_exists(\IntlDateFormatter::class)) {
            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                date_default_timezone_get(),
                \IntlDateFormatter::GREGORIAN,
                'LLLL yyyy',
            );
            $formatted = $formatter->format($date->getTimestamp());
            if (is_string($formatted) && '' !== trim($formatted)) {
                return $formatted;
            }
        }

        return $date->format('m.Y');
    }
}
