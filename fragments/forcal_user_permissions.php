<?php
/**
 * Fragment zur Verwaltung der Benutzerberechtigungen für forCal
 */

use forCal\Utils\forCalUserPermission;

$users                    = $this->getVar('users');
$categories               = $this->getVar('categories');
$current_user_id          = $this->getVar('current_user_id', 0);
$assigned_categories      = $this->getVar('assigned_categories', []);
$venue_edit_scope         = $this->getVar('venue_edit_scope', 'own');
$allowed_owner_ids        = $this->getVar('allowed_owner_ids', []);
$other_forcal_users       = $this->getVar('other_forcal_users', []);
$restrict_venue_selection = $this->getVar('restrict_venue_selection', false);
$can_upload_media         = $this->getVar('can_upload_media', false);

$users = forCalUserPermission::filterUsersWithForcalPermission($users);
?>

<div class="row">
    <div class="col-md-3">
        <div class="panel panel-default">
            <div class="panel-heading"><?= rex_i18n::msg('forcal_users') ?></div>
            <div class="list-group">
                <?php foreach ($users as $u): ?>
                <a href="<?= rex_url::currentBackendPage(['user_id' => $u->getValue('id')]) ?>"
                   class="list-group-item<?= $current_user_id == $u->getValue('id') ? ' active' : '' ?>">
                    <?= rex_escape($u->getValue('name')) ?> (<?= rex_escape($u->getValue('login')) ?>)
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
                    $currentUser  = rex_user::get($current_user_id);
                    $has_all_perm = $currentUser instanceof rex_user && $currentUser->hasPerm('forcal[all]');
                    ?>

                    <!-- Medienberechtigungen -->
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="can_upload_media" value="1" <?= $can_upload_media ? 'checked' : '' ?>>
                            <strong><?= rex_i18n::msg('forcal_media_upload_permission') ?></strong>
                        </label>
                    </div>

                    <hr>

                    <!-- Kategorien -->
                    <div class="checkbox-group" id="category-list" <?= $has_all_perm ? 'style="opacity:0.5;"' : '' ?>>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="select-all">
                                <strong><?= rex_i18n::msg('forcal_select_all_categories') ?></strong>
                            </label>
                        </div>
                        <?php foreach ($categories as $category): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="categories[]" value="<?= (int) $category->id ?>"
                                    <?= in_array($category->id, $assigned_categories, true) ? 'checked' : '' ?>>
                                <span style="display:inline-block;width:20px;height:20px;background-color:<?= rex_escape($category->color) ?>;margin-right:5px;vertical-align:middle;"></span>
                                <?= rex_escape($category->name) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <!-- Ort-Verwaltungsrechte -->
                    <p><strong><?= rex_i18n::msg('forcal_venue_edit_scope_label') ?></strong></p>
                    <p class="text-muted"><?= rex_i18n::msg('forcal_venue_edit_scope_info') ?></p>

                    <div class="radio">
                        <label>
                            <input type="radio" name="venue_edit_scope" value="own" <?= $venue_edit_scope === 'own' ? 'checked' : '' ?>>
                            <?= rex_i18n::msg('forcal_venue_scope_own') ?>
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="venue_edit_scope" value="all" <?= $venue_edit_scope === 'all' ? 'checked' : '' ?>>
                            <?= rex_i18n::msg('forcal_venue_scope_all') ?>
                        </label>
                    </div>
                    <?php if (!empty($other_forcal_users)): ?>
                    <div class="radio">
                        <label>
                            <input type="radio" name="venue_edit_scope" value="by_owner" <?= $venue_edit_scope === 'by_owner' ? 'checked' : '' ?>>
                            <?= rex_i18n::msg('forcal_venue_scope_by_owner') ?>
                        </label>
                    </div>
                    <div id="by-owner-list" style="margin-left:20px;margin-top:8px;<?= $venue_edit_scope !== 'by_owner' ? 'display:none;' : '' ?>">
                        <p class="text-muted small"><?= rex_i18n::msg('forcal_venue_scope_by_owner_info') ?></p>
                        <?php foreach ($other_forcal_users as $ou): ?>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="allowed_owner_ids[]" value="<?= (int) $ou->getId() ?>"
                                    <?= in_array((int) $ou->getId(), $allowed_owner_ids, true) ? 'checked' : '' ?>>
                                <?= rex_escape($ou->getName()) ?>
                                <span class="text-muted">(<?= rex_escape($ou->getLogin()) ?>)</span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <hr>

                    <!-- Termin-Formular: Orte-Auswahl einschränken -->
                    <p><strong><?= rex_i18n::msg('forcal_venue_selection_label') ?></strong></p>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="restrict_venue_selection" value="1" <?= $restrict_venue_selection ? 'checked' : '' ?>>
                            <?= rex_i18n::msg('forcal_venue_selection_restrict') ?>
                        </label>
                        <p class="text-muted small" style="margin-top:4px;"><?= rex_i18n::msg('forcal_venue_selection_restrict_info') ?></p>
                    </div>

                </div>
                <div class="panel-footer">
                    <button type="submit" class="btn btn-primary" name="btn_save" value="1"><?= rex_i18n::msg('forcal_save_permissions') ?></button>
                </div>
            </div>
        </form>

        <script nonce="<?= rex_response::getNonce() ?>">
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var selAll = document.getElementById('select-all');
                function updateSelAll() {
                    var total = document.querySelectorAll('input[name="categories[]"]').length;
                    var chk   = document.querySelectorAll('input[name="categories[]"]:checked').length;
                    if (selAll) { selAll.checked = total > 0 && chk === total; }
                }
                if (selAll) {
                    selAll.addEventListener('change', function () {
                        document.querySelectorAll('input[name="categories[]"]').forEach(function (cb) { cb.checked = selAll.checked; });
                    });
                    document.querySelectorAll('input[name="categories[]"]').forEach(function (cb) { cb.addEventListener('change', updateSelAll); });
                    updateSelAll();
                }
                var byOwnerList = document.getElementById('by-owner-list');
                document.querySelectorAll('input[name="venue_edit_scope"]').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (byOwnerList) {
                            byOwnerList.style.display = (radio.value === 'by_owner' && radio.checked) ? '' : 'none';
                        }
                    });
                });
            });
        }());
        </script>
    </div>
    <?php endif; ?>
</div>
