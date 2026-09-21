<?php

/**
 * @var rex_yform_value_forcal_picker $this
 * @psalm-scope-this rex_yform_value_forcal_picker
 */

use forCal\Utils\forCalPicker;

$notice = [];
if ('' !== (string) $this->getElement('notice')) {
    $notice[] = rex_i18n::translate((string) $this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>';
}
$notice = [] === $notice ? '' : '<p class="help-block small">' . implode('<br>', $notice) . '</p>';

$class = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());
?>
<div class="<?= $class ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?= $this->getFieldId() ?>"><?= $this->getLabel() ?></label>
    <?= forCalPicker::render($this->getFieldName(), (string) $this->getValue(), [
        'id' => $this->getFieldId(),
        'type' => (string) $this->getElement('type') ?: 'entry',
        'multiple' => 1 === (int) $this->getElement('multiple'),
        'max' => (int) $this->getElement('max'),
        'category' => (int) $this->getElement('category') ?: null,
        'public' => 1 === (int) $this->getElement('public'),
        'past' => 1 === (int) $this->getElement('past'),
    ]) ?>
    <?= $notice ?>
</div>
