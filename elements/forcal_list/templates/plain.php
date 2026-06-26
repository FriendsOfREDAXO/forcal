<?php

/** @var array<string,mixed> $elementData */

use FriendsOfREDAXO\Forcal\ForcalRenderer;
use FriendsOfREDAXO\Builder\Starter\StarterConfig;

if (!class_exists(ForcalRenderer::class)) {
    return;
}

$result = ForcalRenderer::fetch($elementData);
$headline = (string) ($elementData['headline'] ?? '');
$description = (string) ($elementData['description'] ?? '');
$showLinks = !isset($elementData['show_links']) || !empty($elementData['show_links']);
$showCategoryColors = !empty($elementData['show_category_colors']);
$layout = (string) $result['layout'];
$items = (array) $result['items'];
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

$rows = ForcalRenderer::buildSeparatedRows($items, $groupBy);

$sectionBg = (string) ($elementData['section_bg'] ?? '');
$sectionPadding = (string) ($elementData['section_padding'] ?? '');
$containerWidth = (string) ($elementData['container_width'] ?? 'uk-container');
$sectionLight = !empty($elementData['section_light']);
$enableSection = !isset($elementData['enable_section']) || !empty($elementData['enable_section']);
$enableContainer = !isset($elementData['enable_container']) || !empty($elementData['enable_container']);

$sectionStyle = StarterConfig::mapBg($sectionBg, 'plain') . StarterConfig::mapPadding($sectionPadding, 'plain');
if ($sectionLight) {
    $sectionStyle .= 'color:#fff;';
}
$containerStyle = StarterConfig::mapContainer($containerWidth, 'plain');
?>
<?php if ($enableSection): ?><section<?= $sectionStyle !== '' ? ' style="' . rex_escape($sectionStyle) . '"' : '' ?>><?php endif; ?>
<?php if ($enableContainer): ?><div style="<?= rex_escape($containerStyle) ?>"><?php endif; ?>

<?php if ($headline !== ''): ?><h2><?= rex_escape($headline) ?></h2><?php endif; ?>
<?php if ($description !== ''): ?><p><?= nl2br(rex_escape($description)) ?></p><?php endif; ?>

<?php if ($error !== null): ?>
<p><?= rex_escape((string) $error) ?></p>
<?php elseif ($items === []): ?>
<p>Keine kommenden Termine.</p>
<?php elseif ($layout === 'cards'): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:1rem;">
    <?php foreach ($rows as $row): ?>
    <?php if (($row['type'] ?? '') === 'separator'): ?>
    <?php
    $sepLabel = rex_escape((string) ($row['label'] ?? ''));
    $sepLevel = (int) ($row['level'] ?? 1);
    $sepTag = $sepLevel === 2 && $groupHeadingTag !== 'div' ? 'h4' : $groupHeadingTag;
    $sepClass = trim(($sepLevel === 2 ? 'small ' : '') . $groupHeadingClass);
    ?>
    <div style="grid-column:1 / -1;">
        <<?= $sepTag ?><?= $sepClass !== '' ? ' class="' . rex_escape($sepClass) . '"' : '' ?> style="margin:.2rem 0 .15rem 0;"><?= $sepLabel ?></<?= $sepTag ?>>
    </div>
    <?php continue; ?>
    <?php endif; ?>
    <?php $it = (array) ($row['item'] ?? []); ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $dateStr = ForcalRenderer::formatDate($it);
    $imageUrl = (string) ($it['image_url'] ?? '');
    $categoryName = rex_escape((string) ($it['category_name'] ?? ''));
    $categoryColor = (string) ($it['category_color'] ?? '');
    $categoryBadgeHtml = '';
    if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')) {
        $badgeStyle = 'display:inline-block;color:#fff;border-radius:0 0 0 .4rem;padding:.3rem .62rem;font-size:.72rem;font-weight:600;letter-spacing:.02em;line-height:1;box-shadow:0 1px 3px rgba(0,0,0,.18);';
        $badgeStyle .= $categoryColor !== '' ? 'background:' . rex_escape($categoryColor) . ';' : 'background:#6c757d;';
        $categoryBadgeHtml = '<span style="' . $badgeStyle . '">' . ($categoryName !== '' ? $categoryName : 'Kategorie') . '</span>';
    }
    ?>
    <article style="position:relative;border:1px solid #ddd;border-radius:6px;overflow:hidden;<?= $showCategoryColors && $categoryColor !== '' ? 'border-top:4px solid ' . rex_escape($categoryColor) . ';' : '' ?>">
        <?php if ($categoryBadgeHtml !== ''): ?><div style="position:absolute;top:0;right:0;z-index:2;"><?= $categoryBadgeHtml ?></div><?php endif; ?>
        <?php if ($imageUrl !== ''): ?><img src="<?= rex_escape($imageUrl) ?>" alt="" loading="lazy" style="width:100%;height:auto;display:block;"><?php endif; ?>
        <div style="padding:.9rem;">
            <div style="color:#666;font-size:.85rem;"><?= $dateStr ?></div>
            <h3 style="margin:.35rem 0;"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></h3>
            <?php if ($teaser !== ''): ?><p style="margin:0;"><?= $teaser ?></p><?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php else: ?>
<ul>
    <?php foreach ($rows as $row): ?>
    <?php if (($row['type'] ?? '') === 'separator'): ?>
    <?php
    $sepLabel = rex_escape((string) ($row['label'] ?? ''));
    $sepLevel = (int) ($row['level'] ?? 1);
    $sepTag = $sepLevel === 2 && $groupHeadingTag !== 'div' ? 'h5' : $groupHeadingTag;
    $sepClass = trim(($sepLevel === 2 ? 'small ' : '') . $groupHeadingClass);
    ?>
    <li style="list-style:none;margin:.45rem 0 .25rem 0;">
        <<?= $sepTag ?><?= $sepClass !== '' ? ' class="' . rex_escape($sepClass) . '"' : '' ?> style="margin:0;"><?= $sepLabel ?></<?= $sepTag ?>>
    </li>
    <?php continue; ?>
    <?php endif; ?>
    <?php $it = (array) ($row['item'] ?? []); ?>
    <?php
    $title = rex_escape((string) ($it['title'] ?? ''));
    $teaser = rex_escape((string) ($it['teaser'] ?? ''));
    $href = $showLinks ? (string) ($it['href'] ?? '') : '';
    $dateStr = ForcalRenderer::formatDate($it);
    $categoryName = rex_escape((string) ($it['category_name'] ?? ''));
    $categoryColor = (string) ($it['category_color'] ?? '');
    $categoryHtml = '';
    if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')) {
        $dotColor = $categoryColor !== '' ? $categoryColor : '#6c757d';
        $categoryHtml = '<span style="display:inline-block;width:.7rem;height:.7rem;border-radius:50%;background:' . rex_escape($dotColor) . ';margin-right:.45rem;vertical-align:middle;"></span>';
        if ($categoryName !== '') {
            $categoryHtml .= '<span style="font-size:.85rem;color:#555;">' . $categoryName . '</span>';
        }
    }
    ?>
    <li style="padding:.45rem 0;border-bottom:1px solid rgba(0,0,0,.08);">
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.45rem .55rem;">
            <small style="color:#6b7280;min-width:8.25rem;"><?= $dateStr ?></small>
            <?php if ($showCategoryColors && ($categoryName !== '' || $categoryColor !== '')): ?>
                <?php $dotColor = $categoryColor !== '' ? $categoryColor : '#6c757d'; ?>
                <span style="display:inline-block;width:.48rem;height:.48rem;border-radius:50%;background:<?= rex_escape($dotColor) ?>;"></span>
            <?php endif; ?>
            <?php if ($showCategoryColors && $categoryName !== ''): ?><small style="color:#6b7280;"><?= $categoryName ?></small><?php endif; ?>
            <span style="font-weight:600;"><?php if ($href !== ''): ?><a href="<?= rex_escape($href) ?>" style="text-decoration:none;"><?= $title ?></a><?php else: ?><?= $title ?><?php endif; ?></span>
        </div>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($enableContainer): ?></div><?php endif; ?>
<?php if ($enableSection): ?></section><?php endif; ?>
