<?php
/**
 * Erweitertes Filter-Fragment für ForCal Einträge
 * Mit gespeicherten Filtern, Suche und erweiterten Filteroptionen
 */

use forCal\Service\forcalFilterService;
use forCal\Utils\forCalUserPermission;

$addon = rex_addon::get('forcal');
$user = rex::getUser();
$userId = $user->getId();

// Aktuelle Filter-Parameter aus der URL
$currentCategory = rex_request('category_filter', 'int', null);
$currentVenue = rex_request('venue_filter', 'int', null);
$currentStatus = rex_request('status_filter', 'string', ''); // String, um zwischen leer, '0' und '1' zu unterscheiden
$currentSearch = rex_request('search', 'string', '');
$currentCreator = rex_request('creator_filter', 'int', null);
$currentDateFrom = rex_request('date_from', 'string', '');
$currentDateTo = rex_request('date_to', 'string', '');

// Sortierung aus Session holen
$tableEvent = rex::getTablePrefix() . "forcal_entries";
$currentSort = rex_session('rex_list_' . $tableEvent . '_sort', 'string', '');
$currentSortDirection = rex_session('rex_list_' . $tableEvent . '_direction', 'string', '');

// Gespeicherte Filter laden
$savedFilters = forcalFilterService::getUserFilters($userId);

// Benutzer-Kategorien für nicht-Admins vorbereiten
$user_categories = [];
if (!$user->isAdmin()) {
    $user_categories = forCalUserPermission::getUserCategories($userId);
}

// Standard-Filter laden, falls kein Filter aktiv ist
$defaultFilter = null;
if (empty($currentCategory) && empty($currentVenue) && empty($currentStatus) && empty($currentSearch) && empty($currentCreator) && empty($currentDateFrom)) {
    $defaultFilter = forcalFilterService::getDefaultFilter($userId);
    if ($defaultFilter) {
        $filterData = $defaultFilter['filter_data'];
        
        // Sicherheitsprüfung: Kategorie-Zugriff für nicht-Admins
        $currentCategory = $filterData['category'] ?? null;
        if ($currentCategory && !$user->isAdmin()) {
            if (!in_array($currentCategory, $user_categories)) {
                $currentCategory = null; // Kategorie nicht mehr erlaubt
            }
        }
        
        $currentVenue = $filterData['venue'] ?? null;
        $currentStatus = $filterData['status'] ?? null;
        $currentSearch = $filterData['search'] ?? '';
        $currentCreator = $filterData['creator'] ?? null;
        $currentDateFrom = $filterData['date_from'] ?? '';
        $currentDateTo = $filterData['date_to'] ?? '';
        
        // Sortierung aus Filter wiederherstellen
        if (isset($filterData['sort']) && !empty($filterData['sort'])) {
            rex_set_session('rex_list_' . $tableEvent . '_sort', $filterData['sort']);
            $currentSort = $filterData['sort'];
        }
        if (isset($filterData['sort_direction']) && !empty($filterData['sort_direction'])) {
            rex_set_session('rex_list_' . $tableEvent . '_direction', $filterData['sort_direction']);
            $currentSortDirection = $filterData['sort_direction'];
        }
    }
}

// Filter laden (aus gespeichertem Filter)
if (rex_request('load_filter', 'int', 0) > 0) {
    $loadedFilter = forcalFilterService::getFilter(rex_request('load_filter', 'int'), $userId);
    if ($loadedFilter) {
        $filterData = $loadedFilter['filter_data'];
        
        // Sicherheitsprüfung: Kategorie-Zugriff für nicht-Admins
        if (isset($filterData['category']) && $filterData['category'] && !$user->isAdmin()) {
            if (!in_array($filterData['category'], $user_categories)) {
                unset($filterData['category']); // Kategorie nicht mehr erlaubt
            }
        }
        
        // Sortierung wiederherstellen
        if (isset($filterData['sort']) && !empty($filterData['sort'])) {
            rex_set_session('rex_list_' . $tableEvent . '_sort', $filterData['sort']);
        }
        if (isset($filterData['sort_direction']) && !empty($filterData['sort_direction'])) {
            rex_set_session('rex_list_' . $tableEvent . '_direction', $filterData['sort_direction']);
        }
    }
}

// Filter speichern
if (rex_post('save_filter', 'string') === '1') {
    $filterName = rex_post('filter_name', 'string', '');
    $setAsDefault = rex_post('set_as_default', 'int', 0) === 1;
    
    if (!empty($filterName)) {
        $filterData = [
            'category' => $currentCategory,
            'venue' => $currentVenue,
            'status' => $currentStatus,
            'search' => $currentSearch,
            'creator' => $currentCreator,
            'date_from' => $currentDateFrom,
            'date_to' => $currentDateTo,
            'sort' => $currentSort,
            'sort_direction' => $currentSortDirection,
        ];
        
        forcalFilterService::saveFilter($userId, $filterName, $filterData, $setAsDefault);
        
        // Seite neu laden, um gespeicherte Filter anzuzeigen
        header('Location: ' . rex_url::currentBackendPage());
        exit;
    }
}

// Filter löschen
if (rex_request('delete_filter', 'int', 0) > 0) {
    forcalFilterService::deleteFilter(rex_request('delete_filter', 'int'), $userId);
    header('Location: ' . rex_url::currentBackendPage());
    exit;
}

// Als Standard setzen
if (rex_request('set_default_filter', 'int', 0) > 0) {
    forcalFilterService::setDefaultFilter(rex_request('set_default_filter', 'int'), $userId);
    header('Location: ' . rex_url::currentBackendPage());
    exit;
}

// Kategorien laden
$all_categories = rex_sql::factory()->getArray(
    'SELECT id, name_' . rex_clang::getCurrentId() . ' as name, color 
     FROM ' . rex::getTable('forcal_categories') . ' 
     WHERE status = 1 
     ORDER BY name_' . rex_clang::getCurrentId()
);

// Venues laden (nur wenn aktiviert)
$all_venues = [];
$venuesEnabled = $addon->getConfig('forcal_venues_enabled', true);
if ($venuesEnabled) {
    $all_venues = rex_sql::factory()->getArray(
        'SELECT id, name_' . rex_clang::getCurrentId() . ' as name 
         FROM ' . rex::getTable('forcal_venues') . ' 
         WHERE status = 1 
         ORDER BY name_' . rex_clang::getCurrentId()
    );
}

// Benutzer laden (für Ersteller-Filter)
$creators = rex_sql::factory()->getArray(
    'SELECT DISTINCT u.id, u.name 
     FROM ' . rex::getTable('user') . ' u
     INNER JOIN ' . rex::getTable('forcal_entries') . ' e ON u.login = e.createuser
     ORDER BY u.name'
);

// Aktuelle URL ohne Filter-Parameter
$baseUrl = rex_url::currentBackendPage();
$currentParams = [];
foreach ($_GET as $param => $value) {
    if (!in_array($param, ['category_filter', 'venue_filter', 'status_filter', 'search', 'creator_filter', 'date_from', 'date_to', 'save_filter', 'filter_name', 'set_as_default', 'delete_filter', 'set_default_filter', 'load_filter'])) {
        $currentParams[$param] = $value;
    }
}

?>

<?php if (!empty($savedFilters)): ?>
    <div class="panel panel-default" style="margin-bottom: 10px;">
        <div class="panel-body" style="padding: 10px;">
            <label style="font-size: 12px; margin-bottom: 5px; display: inline-block; margin-right: 10px;"><?= $addon->i18n('forcal_saved_filters') ?>:</label>
            <div class="btn-group btn-group-xs" role="group" style="display: inline-block;">
                <?php foreach ($savedFilters as $filter): ?>
                    <a href="<?= rex_url::currentBackendPage(array_merge($currentParams, $filter['filter_data'], ['load_filter' => $filter['id']])) ?>" 
                       class="btn btn-default <?= $filter['is_default'] ? 'btn-info' : '' ?>" 
                       title="<?= $filter['is_default'] ? $addon->i18n('forcal_default_filter') : '' ?>">
                        <?= rex_escape($filter['name']) ?>
                        <?= $filter['is_default'] ? ' <i class="rex-icon fa-star"></i>' : '' ?>
                    </a>
                    <div class="btn-group btn-group-xs">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <?php if (!$filter['is_default']): ?>
                            <li>
                                <a href="<?= rex_url::currentBackendPage(array_merge($currentParams, ['set_default_filter' => $filter['id']])) ?>">
                                    <i class="rex-icon fa-star"></i> <?= $addon->i18n('forcal_set_as_default') ?>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a href="<?= rex_url::currentBackendPage(array_merge($currentParams, ['delete_filter' => $filter['id']])) ?>" 
                                   onclick="return confirm('<?= $addon->i18n('forcal_delete_filter_confirm') ?>')">
                                    <i class="rex-icon fa-trash"></i> <?= $addon->i18n('forcal_delete') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="panel panel-default">
    <div class="panel-heading" style="cursor: pointer;" data-toggle="collapse" data-target="#filter-collapse">
        <div class="panel-title">
            <i class="rex-icon fa-filter"></i> <?= $addon->i18n('forcal_filter_search') ?>
            <span class="pull-right"><i class="rex-icon fa-angle-down"></i></span>
        </div>
    </div>
    <div id="filter-collapse" class="panel-collapse collapse <?= (!empty($currentCategory) || !empty($currentVenue) || !empty($currentStatus) || !empty($currentSearch) || !empty($currentCreator) || !empty($currentDateFrom)) ? 'in' : '' ?>">
        <div class="panel-body">
            
            <form action="<?= $baseUrl ?>" method="get" id="filter-form">
                <?php foreach ($currentParams as $param => $value): ?>
                    <input type="hidden" name="<?= $param ?>" value="<?= $value ?>">
                <?php endforeach; ?>
                
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <input type="text" name="search" class="form-control input-sm" value="<?= rex_escape($currentSearch) ?>" 
                                   placeholder="<?= $addon->i18n('forcal_search_placeholder') ?>">
                        </div>
                    </div>
                    
                    <div class="col-sm-2">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <select name="category_filter" class="form-control input-sm selectpicker" data-live-search="true" data-size="8">
                                <option value=""><?= $addon->i18n('forcal_all_categories') ?></option>
                                <?php 
                                $filter_categories = $all_categories;
                                if (!$user->isAdmin()) {
                                    $filter_categories = array_filter($all_categories, function($category) use ($user_categories) {
                                        return in_array($category['id'], $user_categories);
                                    });
                                }
                                
                                foreach ($filter_categories as $category): 
                                    $colorCircle = '<span class="category-color-circle" style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:' . rex_escape($category['color']) . ';margin-right:6px;"></span>';
                                ?>
                                    <option value="<?= $category['id'] ?>" 
                                            data-content="<?= rex_escape($colorCircle . rex_escape($category['name'])) ?>"
                                            <?= $currentCategory == $category['id'] ? 'selected' : '' ?>>
                                        <?= rex_escape($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($venuesEnabled): ?>
                    <div class="col-sm-2">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <select name="venue_filter" class="form-control input-sm selectpicker" data-live-search="true" data-size="8">
                                <option value=""><?= $addon->i18n('forcal_all_venues') ?></option>
                                <?php foreach ($all_venues as $venue): ?>
                                    <option value="<?= $venue['id'] ?>" <?= $currentVenue == $venue['id'] ? 'selected' : '' ?>>
                                        <?= rex_escape($venue['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-sm-2">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <select name="status_filter" class="form-control input-sm selectpicker" data-size="8">
                                <option value=""><?= $addon->i18n('forcal_all_statuses') ?></option>
                                <option value="1" <?= $currentStatus === '1' ? 'selected' : '' ?>><?= $addon->i18n('forcal_active') ?></option>
                                <option value="0" <?= $currentStatus === '0' ? 'selected' : '' ?>><?= $addon->i18n('forcal_inactive') ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-sm-3">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <div class="input-group input-group-sm">
                                <input type="date" name="date_from" class="form-control input-sm" value="<?= rex_escape($currentDateFrom) ?>" 
                                       placeholder="<?= $addon->i18n('forcal_from') ?>" style="font-size: 12px;">
                                <span class="input-group-addon" style="padding: 4px 8px;">-</span>
                                <input type="date" name="date_to" class="form-control input-sm" value="<?= rex_escape($currentDateTo) ?>" 
                                       placeholder="<?= $addon->i18n('forcal_to') ?>" style="font-size: 12px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-sm-2">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <select name="creator_filter" class="form-control input-sm selectpicker" data-live-search="true" data-size="8">
                                <option value=""><?= $addon->i18n('forcal_all_creators') ?></option>
                                <?php foreach ($creators as $creator): ?>
                                    <option value="<?= $creator['id'] ?>" <?= $currentCreator == $creator['id'] ? 'selected' : '' ?>>
                                        <?= rex_escape($creator['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-sm-10">
                        <div class="btn-group btn-group-sm" style="margin-bottom: 10px;">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="rex-icon fa-search"></i> <?= $addon->i18n('forcal_apply_filter') ?>
                            </button>
                            <a href="<?= rex_url::currentBackendPage($currentParams) ?>" class="btn btn-default btn-sm">
                                <i class="rex-icon fa-times"></i> <?= $addon->i18n('forcal_reset_filter') ?>
                            </a>
                            <?php 
                            $hasActiveFilters = !empty($currentSearch) || !empty($currentCategory) || !empty($currentVenue) || 
                                               ($currentStatus !== null && $currentStatus !== '') || !empty($currentCreator) || 
                                               !empty($currentDateFrom) || !empty($currentDateTo);
                            ?>
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#save-filter-modal" 
                                    <?= !$hasActiveFilters ? 'disabled title="' . $addon->i18n('forcal_no_filters_to_save') . '"' : '' ?>>
                                <i class="rex-icon fa-save"></i> <?= $addon->i18n('forcal_save_filter') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal zum Speichern von Filtern -->
<div class="modal fade" id="save-filter-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= $baseUrl ?>" method="post">
                <?php foreach (array_merge($currentParams, [
                    'category_filter' => $currentCategory,
                    'venue_filter' => $currentVenue,
                    'status_filter' => $currentStatus,
                    'search' => $currentSearch,
                    'creator_filter' => $currentCreator,
                    'date_from' => $currentDateFrom,
                    'date_to' => $currentDateTo,
                ]) as $param => $value): ?>
                    <?php if ($value !== null && $value !== ''): ?>
                        <input type="hidden" name="<?= $param ?>" value="<?= $value ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="hidden" name="save_filter" value="1">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><?= $addon->i18n('forcal_save_filter') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="filter_name"><?= $addon->i18n('forcal_filter_name') ?> *</label>
                        <input type="text" class="form-control" id="filter_name" name="filter_name" required maxlength="100">
                    </div>
                    
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong><?= $addon->i18n('forcal_filter_settings') ?>:</strong>
                        </div>
                        <div class="panel-body" style="padding: 10px;">
                            <?php 
                            $hasFilters = !empty($currentSearch) || !empty($currentCategory) || !empty($currentVenue) || 
                                         ($currentStatus !== null && $currentStatus !== '') || !empty($currentCreator) || 
                                         !empty($currentDateFrom) || !empty($currentDateTo) || !empty($currentSort);
                            ?>
                            
                            <?php if (!$hasFilters): ?>
                                <p class="text-muted"><?= $addon->i18n('forcal_no_filters_active') ?></p>
                            <?php else: ?>
                            <table class="table table-condensed" style="margin-bottom: 0;">
                                <?php if (!empty($currentSearch)): ?>
                                <tr>
                                    <td style="width: 30%;"><strong><?= $addon->i18n('forcal_search') ?>:</strong></td>
                                    <td><?= rex_escape($currentSearch) ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($currentCategory)): ?>
                                <?php 
                                $selectedCategory = array_filter($all_categories, function($cat) use ($currentCategory) {
                                    return $cat['id'] == $currentCategory;
                                });
                                $selectedCategory = reset($selectedCategory);
                                if ($selectedCategory):
                                ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_category') ?>:</strong></td>
                                    <td>
                                        <span class="category-color-circle" style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:<?= rex_escape($selectedCategory['color']) ?>;margin-right:6px;border:1px solid rgba(0,0,0,0.1);"></span>
                                        <?= rex_escape($selectedCategory['name']) ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($currentVenue)): ?>
                                <?php 
                                $selectedVenue = array_filter($all_venues, function($venue) use ($currentVenue) {
                                    return $venue['id'] == $currentVenue;
                                });
                                $selectedVenue = reset($selectedVenue);
                                if ($selectedVenue):
                                ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_venue') ?>:</strong></td>
                                    <td><?= rex_escape($selectedVenue['name']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($currentStatus !== null && $currentStatus !== ''): ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_status') ?>:</strong></td>
                                    <td><?= $currentStatus === '1' ? $addon->i18n('forcal_active') : $addon->i18n('forcal_inactive') ?></td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($currentCreator)): ?>
                                <?php 
                                $selectedCreator = array_filter($creators, function($creator) use ($currentCreator) {
                                    return $creator['id'] == $currentCreator;
                                });
                                $selectedCreator = reset($selectedCreator);
                                if ($selectedCreator):
                                ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_creator') ?>:</strong></td>
                                    <td><?= rex_escape($selectedCreator['name']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (!empty($currentDateFrom) || !empty($currentDateTo)): ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_date_range') ?>:</strong></td>
                                    <td>
                                        <?php if (!empty($currentDateFrom)): ?>
                                            <?= $addon->i18n('forcal_from') ?> <?= rex_escape($currentDateFrom) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($currentDateTo)): ?>
                                            <?= $addon->i18n('forcal_to') ?> <?= rex_escape($currentDateTo) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php if (!empty($currentSort)): ?>
                                <tr>
                                    <td><strong><?= $addon->i18n('forcal_sorting') ?>:</strong></td>
                                    <td><?= rex_escape($currentSort) ?> (<?= $currentSortDirection === 'desc' ? $addon->i18n('forcal_descending') : $addon->i18n('forcal_ascending') ?>)</td>
                                </tr>
                                <?php endif; ?>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="set_as_default" value="1">
                            <?= $addon->i18n('forcal_set_as_default_filter') ?>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?= $addon->i18n('forcal_cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="rex-icon fa-save"></i> <?= $addon->i18n('forcal_save') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script nonce="<?= rex_response::getNonce() ?>">
$(document).ready(function() {
    $('.selectpicker').selectpicker({
        noneSelectedText: '<?= $addon->i18n('forcal_please_select') ?>',
        size: 8,
        style: 'btn-default btn-sm'
    });
    
    // Filter-Panel-Status speichern
    $('#filter-collapse').on('shown.bs.collapse', function () {
        localStorage.setItem('forcal_filter_collapsed', 'false');
    });
    $('#filter-collapse').on('hidden.bs.collapse', function () {
        localStorage.setItem('forcal_filter_collapsed', 'true');
    });
});
</script>

<style nonce="<?= rex_response::getNonce() ?>">
.panel-heading[data-toggle="collapse"]:hover {
    background-color: #f5f5f5;
}
.input-group-sm .form-control {
    height: 30px;
    font-size: 12px;
}
.selectpicker.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}
.category-color-circle {
    vertical-align: middle;
    border: 1px solid rgba(0,0,0,0.1);
}
</style>
