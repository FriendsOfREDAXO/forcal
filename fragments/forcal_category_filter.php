<?php
/**
 * Fragment zur Filterung der Kategorien im Kalender
 * - Vereinfachte Version mit direkter Filterung
 */

$all_categories = rex_sql::factory()->getArray(
    'SELECT id, name_' . rex_clang::getCurrentId() . ' as name, color 
     FROM ' . rex::getTablePrefix() . 'forcal_categories 
     WHERE status = 1 
     ORDER BY name_' . rex_clang::getCurrentId()
);

$user_categories = [];
$user = rex::getUser();

if (!$user->isAdmin()) {
    // Benutzerrechte abrufen
    $user_categories = \forCal\Utils\forCalUserPermission::getUserCategories($user->getId());
}

$userFilter = rex_request('user_filter', 'array', []);

// Aktuelle URL mit Parametern abrufen
$currentUrl = rex_url::currentBackendPage();
$currentParams = [];
foreach ($_GET as $param => $value) {
    if ($param !== 'user_filter') {
        $currentParams[$param] = $value;
    }
}

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?= rex_i18n::msg('forcal_category_filter') ?></h3>
    </div>
    <div class="panel-body">
        <form action="<?= $currentUrl ?>" method="get" class="form-inline">
            <?php foreach ($currentParams as $param => $value): ?>
                <input type="hidden" name="<?= $param ?>" value="<?= $value ?>">
            <?php endforeach; ?>
            
            <!-- Kategorie-Auswahl -->
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="user_filter"><?= rex_i18n::msg('forcal_category_select') ?>:</label>
                <select name="user_filter[]" id="user_filter" class="form-control selectpicker" data-width="300px" multiple data-selected-text-format="count" data-actions-box="true" onchange="this.form.submit()">
                    <?php 
                    // Bestimmen, welche Kategorien im Dropdown angezeigt werden sollen
                    $filter_categories = $all_categories;
                    
                    // Für nicht-Admins nur die zugewiesenen Kategorien anzeigen
                    if (!$user->isAdmin()) {
                        $filter_categories = array_filter($all_categories, function($category) use ($user_categories) {
                            return in_array($category['id'], $user_categories);
                        });
                    }
                    
                    foreach ($filter_categories as $category): 
                    ?>
                        <option value="<?= $category['id'] ?>" <?= in_array($category['id'], $userFilter) ? 'selected' : '' ?>>
                            <?= $category['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        
        <?php if (!empty($all_categories)): ?>
            <div class="category-legend">
                <h4><?= rex_i18n::msg('forcal_categories') ?></h4>
                <div class="row">
                    <?php 
                    $filtered_categories = $all_categories;
                    
                    // Wenn User kein Admin ist, nur seine zugewiesenen Kategorien anzeigen
                    if (!$user->isAdmin()) {
                        $filtered_categories = array_filter($all_categories, function($category) use ($user_categories) {
                            return in_array($category['id'], $user_categories);
                        });
                    }
                    
                    // Wenn Filter gesetzt ist
                    if (!empty($userFilter)) {
                        $filtered_categories = array_filter($all_categories, function($category) use ($userFilter) {
                            return in_array($category['id'], $userFilter);
                        });
                    }
                    
                    foreach ($filtered_categories as $category): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="category-item" style="margin-bottom: 10px;">
                                <span class="category-color" style="display: inline-block; width: 20px; height: 20px; background-color: <?= $category['color'] ?>; margin-right: 5px; vertical-align: middle;"></span>
                                <span class="category-name"><?= $category['name'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Bootstrap-Select initialisieren
    $('.selectpicker').selectpicker({
        selectAllText: '<?= rex_i18n::msg('forcal_all_categories') ?>',
        deselectAllText: '<?= rex_i18n::msg('forcal_no_categories') ?>',
        noneSelectedText: '<?= rex_i18n::msg('forcal_select_categories') ?>'
    });
});
</script>
