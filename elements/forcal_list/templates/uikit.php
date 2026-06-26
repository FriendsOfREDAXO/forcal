<?php
/**
 * Forcal-Termine - UIkit Template
 *
 * @var array<string,mixed> $elementData
 */

use FriendsOfREDAXO\Forcal\ForcalRenderer;

if (!class_exists(ForcalRenderer::class)) {
    return;
}

$result = ForcalRenderer::fetch($elementData);

$headline = (string) ($elementData['headline'] ?? '');
$description = (string) ($elementData['description'] ?? '');
$showLinks = !isset($elementData['show_links']) || !empty($elementData['show_links']);
$showCategoryColors = !empty($elementData['show_category_colors']);
$layout = $result['layout'];
$items = $result['items'];
$error = $result['error'];
$groupBy = (string) ($elementData['group_by'] ?? '');
$groupHeadingTag = (string) ($elementData['group_heading_tag'] ?? 'h3');
$groupHeadingStyle = (string) ($elementData['group_heading_style'] ?? 'plain');

if (!in_array($groupHeadingTag, ['h2', 'h3', 'h4', 'h5', 'h6', 'div'], true)) {
    $groupHeadingTag = 'h3';
}

$groupHeadingClass = '';
if ($groupHeadingStyle !== '' && $groupHeadingStyle !== 'plain') {
    $groupHeadingClass = $groupHeadingStyle;
}

$rows = ForcalRenderer::buildSeparatedRows((array) $items, $groupBy);

$sectionBg = $elementData['section_bg'] ?? '';
$sectionBgImage = (string) ($elementData['section_bg_image'] ?? '');
$sectionPadding = $elementData['section_padding'] ?? '';
$containerWidth = $elementData['container_width'] ?? 'uk-container';
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$wrapper = new rex_fragment();
$wrapper->setVar('enable_section', $enableSection, false);
$wrapper->setVar('enable_container', $enableContainer, false);
$wrapper->setVar('section_bg', $sectionBg, false);
$wrapper->setVar('section_bg_image', $sectionBgImage, false);
$wrapper->setVar('section_padding', $sectionPadding, false);
$wrapper->setVar('container_width', $containerWidth, false);
$wrapper->setVar('section_light', $sectionLight, false);

$wrapperClose = new rex_fragment();
$wrapperClose->setVar('mode', 'close', false);
$wrapperClose->setVar('enable_section', $enableSection, false);
$wrapperClose->setVar('enable_container', $enableContainer, false);
$wrapperClose->setVar('section_bg_image', $sectionBgImage, false);
$wrapperClose->setVar('container_width', $containerWidth, false);

$columns = (string) ($elementData['columns'] ?? '3');
$columnsTablet = (string) ($elementData['columns_tablet'] ?? '2');
$columnsMobile = (string) ($elementData['columns_mobile'] ?? '1');

echo $wrapper->parse('ycb_elements/wrapper.php');

if ($headline !== '') {
    echo '<h2 class="uk-heading-line uk-margin-medium-bottom"><span>' . rex_escape($headline) . '</span></h2>';
}
if ($description !== '') {
    echo '<div class="uk-margin-medium-bottom uk-text-lead">' . nl2br(rex_escape($description)) . '</div>';
}

if ($error !== null) {
    echo '<div class="uk-alert uk-alert-warning" uk-alert><p>' . rex_escape($error) . '</p></div>';
} elseif ($items === []) {
    echo '<div class="uk-alert uk-alert-default" uk-alert><p>Keine kommenden Termine.</p></div>';
} elseif ($layout === 'cards') {
    $colClass = 'uk-child-width-1-' . rex_escape($columnsMobile)
        . ' uk-child-width-1-' . rex_escape($columnsTablet) . '@s'
        . ' uk-child-width-1-' . rex_escape($columns) . '@m';

    echo '<div class="' . $colClass . '" uk-grid uk-height-match="target: > div > .uk-card">';
    foreach ($rows as $row) {
        if (($row['type'] ?? '') === 'separator') {
            $sepLabel = rex_escape((string) ($row['label'] ?? ''));
            $sepLevel = (int) ($row['level'] ?? 1);
            $sepTag = $sepLevel === 2 && $groupHeadingTag !== 'div' ? 'h4' : $groupHeadingTag;
            $sepClass = trim(($sepLevel === 2 ? 'uk-text-meta ' : '') . $groupHeadingClass . ' uk-margin-small-top uk-margin-small-bottom');
            echo '<div class="uk-width-1-1"><' . $sepTag . ($sepClass !== '' ? ' class="' . rex_escape($sepClass) . '"' : '') . '>' . $sepLabel . '</' . $sepTag . '></div>';
            continue;
        }
        $it = (array) ($row['item'] ?? []);
        $title = rex_escape((string) $it['title']);
        $teaser = rex_escape((string) $it['teaser']);
        $href = $showLinks ? (string) $it['href'] : '';
        $dateStr = ForcalRenderer::formatDate($it);
        $imageUrl = (string) ($it['image_url'] ?? '');
        $categoryName = rex_escape((string) ($it['category_name'] ?? ''));
        $categoryColor = (string) ($it['category_color'] ?? '');
        $showCategoryBadge = $showCategoryColors && ($categoryName !== '' || $categoryColor !== '');

        $titleHtml = $href !== '' ? '<a href="' . rex_escape($href) . '" class="uk-link-reset">' . $title . '</a>' : $title;
        $imgHtml = $imageUrl !== '' ? '<div class="uk-card-media-top"><img src="' . rex_escape($imageUrl) . '" alt="" loading="lazy"></div>' : '';
        $categoryHtml = '';
        if ($showCategoryBadge) {
            $categoryStyle = ' style="border-radius:0 0 0 .4rem;padding:.3rem .62rem;font-size:.72rem;font-weight:600;letter-spacing:.02em;box-shadow:0 1px 3px rgba(0,0,0,.18);';
            $categoryStyle .= $categoryColor !== '' ? 'background:' . rex_escape($categoryColor) . ';' : '';
            $categoryStyle .= '"';
            $categoryHtml = '<div style="position:absolute;top:0;right:0;z-index:2;"><span class="uk-label"' . $categoryStyle . '>' . ($categoryName !== '' ? $categoryName : 'Kategorie') . '</span></div>';
        }

        $cardStyle = 'position:relative;';
        if ($showCategoryColors && $categoryColor !== '') {
            $cardStyle .= 'border-top:4px solid ' . rex_escape($categoryColor) . ';';
        }

        echo '<div><div class="uk-card uk-card-default" style="' . $cardStyle . '">' . $categoryHtml . $imgHtml . '<div class="uk-card-body">'
            . '<div class="uk-text-meta uk-text-uppercase">' . $dateStr . '</div>'
            . '<h3 class="uk-card-title uk-margin-small-top uk-margin-remove-bottom">' . $titleHtml . '</h3>'
            . ($teaser !== '' ? '<p class="uk-margin-small-top">' . $teaser . '</p>' : '')
            . '</div></div></div>';
    }
    echo '</div>';
} elseif ($layout === 'list') {
    echo '<ul class="uk-list uk-list-divider">';
    foreach ($rows as $row) {
        if (($row['type'] ?? '') === 'separator') {
            $sepLabel = rex_escape((string) ($row['label'] ?? ''));
            $sepLevel = (int) ($row['level'] ?? 1);
            $sepTag = $sepLevel === 2 && $groupHeadingTag !== 'div' ? 'h5' : $groupHeadingTag;
            $sepClass = trim(($sepLevel === 2 ? 'uk-text-meta ' : '') . $groupHeadingClass . ' uk-margin-small-top uk-margin-small-bottom');
            echo '<li><' . $sepTag . ($sepClass !== '' ? ' class="' . rex_escape($sepClass) . '"' : '') . '>' . $sepLabel . '</' . $sepTag . '></li>';
            continue;
        }
        $it = (array) ($row['item'] ?? []);
        $title = rex_escape((string) $it['title']);
        $teaser = rex_escape((string) $it['teaser']);
        $href = $showLinks ? (string) $it['href'] : '';
        $dateStr = ForcalRenderer::formatDate($it);
        $categoryName = rex_escape((string) ($it['category_name'] ?? ''));
        $categoryColor = (string) ($it['category_color'] ?? '');
        $titleHtml = $href !== '' ? '<a href="' . rex_escape($href) . '">' . $title . '</a>' : $title;
        $categoryHtml = '';
        if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')) {
            $categoryStyle = $categoryColor !== '' ? ' style="background:' . rex_escape($categoryColor) . ';"' : '';
            $categoryHtml = '<div class="uk-margin-small-top"><span class="uk-label"' . $categoryStyle . '>' . ($categoryName !== '' ? $categoryName : 'Kategorie') . '</span></div>';
        }
        echo '<li' . ($showCategoryColors && $categoryColor !== '' ? ' style="border-left:4px solid ' . rex_escape($categoryColor) . ';padding-left:1rem;"' : '') . '><div class="uk-text-meta">' . $dateStr . '</div>' . $categoryHtml . '<h4 class="uk-margin-remove">' . $titleHtml . '</h4>'
            . ($teaser !== '' ? '<p class="uk-margin-remove-top">' . $teaser . '</p>' : '')
            . '</li>';
    }
    echo '</ul>';
} else {
    echo '<ul class="uk-list">';
    foreach ($rows as $row) {
        if (($row['type'] ?? '') === 'separator') {
            $sepLabel = rex_escape((string) ($row['label'] ?? ''));
            $sepLevel = (int) ($row['level'] ?? 1);
            $sepTag = $sepLevel === 2 && $groupHeadingTag !== 'div' ? 'h6' : $groupHeadingTag;
            $sepClass = trim(($sepLevel === 2 ? 'uk-text-meta ' : '') . $groupHeadingClass . ' uk-margin-small-top uk-margin-small-bottom');
            echo '<li><' . $sepTag . ($sepClass !== '' ? ' class="' . rex_escape($sepClass) . '"' : '') . '>' . $sepLabel . '</' . $sepTag . '></li>';
            continue;
        }
        $it = (array) ($row['item'] ?? []);
        $title = rex_escape((string) $it['title']);
        $href = $showLinks ? (string) $it['href'] : '';
        $dateStr = ForcalRenderer::formatDate($it);
        $categoryName = rex_escape((string) ($it['category_name'] ?? ''));
        $categoryColor = (string) ($it['category_color'] ?? '');
        $titleHtml = $href !== '' ? '<a href="' . rex_escape($href) . '">' . $title . '</a>' : $title;
        $categoryHtml = '';
        if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')) {
            $dotColor = $categoryColor !== '' ? $categoryColor : '#6c757d';
            $categoryHtml = '<span class="uk-margin-small-right" style="display:inline-block;width:.7rem;height:.7rem;border-radius:50%;background:' . rex_escape($dotColor) . ';vertical-align:middle;"></span>';
            if ($categoryName !== '') {
                $categoryHtml .= '<span class="uk-text-meta uk-margin-small-left">' . $categoryName . '</span>';
            }
        }
        $compactMeta = '<span class="uk-text-meta" style="min-width:8.25rem;display:inline-block;">' . $dateStr . '</span>';
        if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')) {
            $dotColor = $categoryColor !== '' ? $categoryColor : '#6c757d';
            $compactMeta .= '<span class="uk-margin-small-left uk-margin-small-right" style="display:inline-block;width:.48rem;height:.48rem;border-radius:50%;background:' . rex_escape($dotColor) . ';vertical-align:middle;"></span>';
            if ($categoryName !== '') {
                $compactMeta .= '<span class="uk-text-meta uk-margin-small-right">' . $categoryName . '</span>';
            }
        }

        echo '<li style="padding:.45rem 0;border-bottom:1px solid rgba(0,0,0,.08);"><div class="uk-flex uk-flex-wrap uk-flex-middle">' . $compactMeta . '<span style="font-weight:600;">' . $titleHtml . '</span></div></li>';
    }
    echo '</ul>';
}

echo $wrapperClose->parse('ycb_elements/wrapper.php');
