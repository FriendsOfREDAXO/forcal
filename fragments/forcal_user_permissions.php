<?php
/**
 * Fragment zur Verwaltung der Benutzerberechtigungen für forCal
 */

use forCal\Utils\forCalUserPermission;

$users = $this->getVar('users');
$categories = $this->getVar('categories');
$venues = $this->getVar('venues', []);
$current_user_id = $this->getVar('current_user_id', 0);
$assigned_categories = $this->getVar('assigned_categories', []);
$assigned_venues = $this->getVar('assigned_venues', []);
$own_venue_ids = $this->getVar('own_venue_ids', []);
$can_upload_media = $this->getVar('can_upload_media', false);

// Filtere Benutzer, um nur diejenigen mit forcal-Rechten anzuzeigen
$users = forCalUserPermission::filterUsersWithForcalPermission($users);

?>

<div class="row">
    <div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-heading"><?= rex_i18n::msg('forcal_users') ?></div>
            <div class="list-group">
                <?php foreach ($users as $user): ?>
                <a href="<?= rex_url::currentBackendPage(['user_id' => $user->getValue('id')]) ?>" class="list-group-item<?= $current_user_id == $user->getValue('id') ? ' active' : '' ?>">
                    <?= $user->getValue('name') ?> (<?= $user->getValue('login') ?>)
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php if ($current_user_id > 0): ?>
    <div class="col-md-9">
        <form action="<?= rex_url::currentBackendPage() ?>" method="post">
            <input type="hidden" name="user_id" value="<?= $current_user_id ?>">
            
            <div class="panel panel-default">
                <div class="panel-heading"><?= rex_i18n::msg('forcal_assign_categories') ?></div>
                <div class="panel-body">
                    <p><?= rex_i18n::msg('forcal_assign_categories_info') ?></p>
                    
                    <?php
                    // Prüfen, ob der Benutzer das globale forcal[all]-Recht hat
                    $user = rex_user::get($current_user_id);
                    $has_all_perm = $user->hasPerm('forcal[all]');
                    ?>
                    
                    <!-- Medienberechtigungen -->
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="can_upload_media" value="1" <?= $can_upload_media ? 'checked' : '' ?>>
                            <strong><?= rex_i18n::msg('forcal_media_upload_permission') ?></strong>
                        </label>
                    </div>
                    
                    <hr>
                    
                    <!-- Kategorien-Berechtigungen -->
                    <div class="checkbox-group" id="category-list" <?= $has_all_perm ? 'style="opacity: 0.5;"' : '' ?>>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="select-all"> 
                                <strong><?= rex_i18n::msg('forcal_select_all_categories') ?></strong>
                            </label>
                        </div>
                        
                        <?php foreach ($categories as $category): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="categories[]" value="<?= $category->id ?>" <?= in_array($category->id, $assigned_categories) ? 'checked' : '' ?>>
                                <span style="display: inline-block; width: 20px; height: 20px; background-color: <?= $category->color ?>; margin-right: 5px; vertical-align: middle;"></span>
                                <?= $category->name ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($venues)): ?>
                    <hr>

                    <!-- Venue-Berechtigungen -->
                    <p><strong><?= rex_i18n::msg('forcal_assign_venues') ?></strong></p>
                    <p class="text-muted"><?= rex_i18n::msg('forcal_assign_venues_info') ?></p>

                    <?php
                    // Eigene Orte (createuser) – immer automatisch erlaubt, nicht änderbar
                    $ownVenues = array_filter($venues, fn($v) => in_array($v->id, $own_venue_ids));
                    // Fremde Orte – können vom Admin geteilt werden
                    $otherVenues = array_filter($venues, fn($v) => !in_array($v->id, $own_venue_ids));
                    ?>

                    <?php if (!empty($ownVenues)): ?>
                    <p class="text-muted small"><?= rex_i18n::msg('forcal_own_venues_info') ?></p>
                    <ul class="list-unstyled" style="margin-bottom: 10px;">
                        <?php foreach ($ownVenues as $venue): ?>
                        <li>
                            <span class="label label-default" style="margin-right:4px;"><i class="rex-icon fa-map-marker"></i></span>
                            <?= rex_escape($venue->name) ?>
                            <span class="text-muted small">&nbsp;(<?= rex_i18n::msg('forcal_own_venue') ?>)</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (!empty($otherVenues)): ?>
                    <div class="checkbox-group" id="venue-list" <?= $has_all_perm ? 'style="opacity: 0.5;"' : '' ?>>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="select-all-venues">
                                <strong><?= rex_i18n::msg('forcal_select_all_venues') ?></strong>
                            </label>
                        </div>

                        <?php foreach ($otherVenues as $venue): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="venues[]" value="<?= $venue->id ?>" <?= in_array($venue->id, $assigned_venues) ? 'checked' : '' ?>>
                                <?= rex_escape($venue->name) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted small"><?= rex_i18n::msg('forcal_no_other_venues') ?></p>
                    <?php endif; ?>

                    <?php endif; ?>
                </div>
                <div class="panel-footer">
                    <button type="submit" class="btn btn-primary" name="btn_save" value="1"><?= rex_i18n::msg('forcal_save_permissions') ?></button>
                </div>
            </div>
        </form>
        
        <script nonce="<?=rex_response::getNonce()?>">
            $(document).ready(function() {
                // Alle Kategorien auswählen/abwählen
                $('#select-all').change(function() {
                    $('input[name="categories[]"]').prop('checked', $(this).prop('checked'));
                });
                
                // Status "Alle auswählen" aktualisieren
                function updateSelectAllState() {
                    var allChecked = $('input[name="categories[]"]:checked').length === $('input[name="categories[]"]').length;
                    $('#select-all').prop('checked', allChecked);
                }
                
                $('input[name="categories[]"]').change(updateSelectAllState);
                updateSelectAllState();

                // Alle Venues auswählen/abwählen
                $('#select-all-venues').change(function() {
                    $('input[name="venues[]"]').prop('checked', $(this).prop('checked'));
                });

                function updateSelectAllVenuesState() {
                    var total = $('input[name="venues[]"]').length;
                    if (total === 0) return;
                    var allChecked = $('input[name="venues[]"]:checked').length === total;
                    $('#select-all-venues').prop('checked', allChecked);
                }

                $('input[name="venues[]"]').change(updateSelectAllVenuesState);
                updateSelectAllVenuesState();
            });
        </script>
    </div>
    <?php endif; ?>
</div>
