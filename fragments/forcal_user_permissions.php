<?php
/**
 * Fragment zur Verwaltung der Benutzerberechtigungen für forCal
 */

use forCal\Utils\forCalUserPermission;

$users = $this->getVar('users');
$categories = $this->getVar('categories');
$current_user_id = $this->getVar('current_user_id', 0);
$assigned_categories = $this->getVar('assigned_categories', []);
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
                </div>
                <div class="panel-footer">
                    <button type="submit" class="btn btn-primary" name="btn_save" value="1"><?= rex_i18n::msg('forcal_save_permissions') ?></button>
                </div>
            </div>
        </form>
        
        <script>
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
            });
        </script>
    </div>
    <?php endif; ?>
</div>
