<?php
/**
 * Dashboard für forCal
 * 
 * @author YourName
 * @package redaxo5
 * @license MIT
 */

// Diese Datei würde im Hauptverzeichnis des AddOns liegen, z.B. als dashboard.php
// Sie kann dann über eine Subpage im package.yml eingebunden werden

use forCal\Factory\forCalEventsFactory;

// Sicherheitscheck
if (!rex::isBackend() || !rex::getUser()) {
    return;
}

// Aktuelle Benutzer-ID
$user_id = rex::getUser()->getId();

// Heutige Termine abrufen
$today_events = forCalEventsFactory::create()
    ->from('today')
    ->to('today')
    ->get();

// Kommende Termine (nächste 7 Tage)
$upcoming_events = forCalEventsFactory::create()
    ->from('tomorrow')
    ->to('+7 days')
    ->get();

// Zuletzt hinzugefügte Termine
// Statt 'all' zu verwenden, nehmen wir einen deutlich zurückliegenden Zeitpunkt
$latest_events = forCalEventsFactory::create()
    ->from('-10 years')  // Weit in der Vergangenheit starten
    ->sortBy('id', 'desc')  // Nach ID absteigend sortieren (neueste zuerst)
    ->get();
$latest_events = array_slice($latest_events, 0, 10);  // Nur die ersten 10 nehmen

// Gesamtzahl aller Termine ermitteln
// Statt 'all' zu verwenden, Zeitraum sehr groß wählen
$total_events_query = rex_sql::factory();
$total_events_query->setQuery('SELECT COUNT(*) as total FROM ' . rex::getTablePrefix() . 'forcal_entries');
$total_events = $total_events_query->getValue('total');

// Zukünftige Termine zählen
$total_future_events = count(forCalEventsFactory::create()
    ->from('today')
    ->get());

// Kategorien abrufen
$sql = rex_sql::factory();
$categories = $sql->getArray('SELECT id, name_' . rex_clang::getCurrentId() . ' as name, color FROM ' . rex::getTable('forcal_categories') . ' WHERE status = 1');

// Das Dashboard-Layout ausgeben
echo rex_view::title(rex_i18n::msg('forcal_title') . ' - Dashboard');
?>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-12 mb-3">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-bolt"></i> Schnellzugriff</div>
            <div class="panel-body">
                <a href="<?= rex_url::backendPage('forcal/entries', ['func' => 'add']) ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> <?= rex_i18n::msg('forcal_add_entry') ?>
                </a>
                
                <a href="<?= rex_url::backendPage('forcal/calendar') ?>" class="btn btn-default">
                    <i class="fa fa-calendar"></i> <?= rex_i18n::msg('forcal_calendar') ?>
                </a>
                
                <a href="<?= rex_url::backendPage('forcal/entries') ?>" class="btn btn-default">
                    <i class="fa fa-list"></i> <?= rex_i18n::msg('forcal_entries') ?>
                </a>
                
                <?php if (rex::getUser()->isAdmin() || rex::getUser()->hasPerm('forcal[catspage]')): ?>
                <a href="<?= rex_url::backendPage('forcal/categories') ?>" class="btn btn-default">
                    <i class="fa fa-tags"></i> <?= rex_i18n::msg('forcal_categories') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-chart-bar"></i> Statistik</div>
            <div class="panel-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <span class="badge"><?= $total_events ?></span>
                        Termine gesamt
                    </div>
                    <div class="list-group-item">
                        <span class="badge"><?= $total_future_events ?></span>
                        Zukünftige Termine
                    </div>
                    <div class="list-group-item">
                        <span class="badge"><?= count($today_events) ?></span>
                        Termine heute
                    </div>
                    <div class="list-group-item">
                        <span class="badge"><?= count($categories) ?></span>
                        Kategorien
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Events -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-calendar-day"></i> Heutige Termine</div>
            <div class="panel-body">
                <?php if (count($today_events) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?= rex_i18n::msg('forcal_entry_name') ?></th>
                                    <th>Uhrzeit</th>
                                    <th><?= rex_i18n::msg('forcal_category') ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_events as $event): ?>
                                <tr>
                                    <td><?= $event['title'] ?></td>
                                    <td>
                                        <?php if (isset($event['date_time']['full_time']) && $event['date_time']['full_time']): ?>
                                            <span class="label label-default">Ganztägig</span>
                                        <?php elseif (isset($event['date_time']['time'])): ?>
                                            <?= $event['date_time']['time'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($event['color']) && isset($event['category_name'])): ?>
                                        <span class="label" style="background-color: <?= $event['color'] ?>">
                                            <?= $event['category_name'] ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= rex_url::backendPage('forcal/entries', ['func' => 'edit', 'id' => $event['id']]) ?>" 
                                           class="btn btn-xs btn-default">
                                            <i class="fa fa-edit"></i> <?= rex_i18n::msg('edit') ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Keine Termine für heute
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upcoming Events -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-calendar-week"></i> Kommende Termine</div>
            <div class="panel-body">
                <?php if (count($upcoming_events) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($upcoming_events as $event): ?>
                            <a href="<?= rex_url::backendPage('forcal/entries', ['func' => 'edit', 'id' => $event['id']]) ?>" 
                               class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?= $event['title'] ?>
                                    <?php if (isset($event['color']) && isset($event['category_name'])): ?>
                                    <span class="label" style="background-color: <?= $event['color'] ?>">
                                        <?= $event['category_name'] ?>
                                    </span>
                                    <?php endif; ?>
                                </h4>
                                <p class="list-group-item-text">
                                    <?php if (isset($event['date_time']['date'])): ?>
                                    <i class="fa fa-calendar-alt"></i> <?= $event['date_time']['date'] ?>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($event['date_time']) && 
                                              isset($event['date_time']['full_time']) && 
                                              !$event['date_time']['full_time'] && 
                                              isset($event['date_time']['time'])): ?>
                                        <i class="fa fa-clock ml-2"></i> <?= $event['date_time']['time'] ?>
                                    <?php endif; ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Keine kommenden Termine
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Latest Events -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-history"></i> Neueste Termine</div>
            <div class="panel-body">
                <?php if (count($latest_events) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($latest_events as $event): ?>
                            <a href="<?= rex_url::backendPage('forcal/entries', ['func' => 'edit', 'id' => $event['id']]) ?>" 
                               class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?= $event['title'] ?>
                                    <?php if (isset($event['color']) && isset($event['category_name'])): ?>
                                    <span class="label" style="background-color: <?= $event['color'] ?>">
                                        <?= $event['category_name'] ?>
                                    </span>
                                    <?php endif; ?>
                                </h4>
                                <p class="list-group-item-text">
                                    <?php if (isset($event['date_time']['date'])): ?>
                                    <i class="fa fa-calendar-alt"></i> <?= $event['date_time']['date'] ?>
                                    <?php endif; ?>
                                </p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Keine Termine vorhanden
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Overview -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading"><i class="fa fa-tags"></i> <?= rex_i18n::msg('forcal_categories') ?></div>
            <div class="panel-body">
                <div class="row">
                    <?php foreach ($categories as $category): ?>
                        <div class="col-md-3 col-sm-4 mb-3">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="background-color: <?= $category['color'] ?>; color: white;">
                                    <?= $category['name'] ?>
                                </div>
                                <div class="panel-body">
                                    <?php
                                        // Zähle Termine in dieser Kategorie ab heute
                                        $category_events = forCalEventsFactory::create()
                                            ->from('today')
                                            ->inCategories($category['id'])
                                            ->get();
                                    ?>
                                    <p>
                                        <strong><?= count($category_events) ?></strong> 
                                        kommende Termine
                                    </p>
                                    <a href="<?= rex_url::backendPage('forcal/entries', ['category_filter' => $category['id']]) ?>" 
                                       class="btn btn-default btn-sm">
                                        <i class="fa fa-list"></i> Termine anzeigen
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
