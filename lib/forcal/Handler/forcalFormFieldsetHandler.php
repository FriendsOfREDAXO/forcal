<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Handler;


use rex;
use rex_clang;
use rex_form;
use rex_i18n;
use forCal\Utils\forCalFormHelper;
use forCal\Utils\forCalUserPermission;

class forCalFormFieldsetHandler
{
    /**
     * @param rex_form $form
     * @param $fieldset
     * @param rex_clang $clang
     * @author Joachim Doerr
     */
    public static function handleLangFormFieldset(rex_form $form, $fieldset, rex_clang $clang)
    {
        self::handleFormFieldset($form, $fieldset, $clang, true);
    }

    /**
     * @param rex_form $form
     * @param $fieldset
     * @param rex_clang $clang
     * @param bool $langField
     * @author Joachim Doerr
     */
    public static function handleFormFieldset(rex_form $form, $fieldset, rex_clang $clang, $langField = false)
    {
        foreach ($fieldset as $key => $field) {

            if (array_key_exists('panel', $field) && array_key_exists('fields', $field)) {
                // open panel
                self::openPanel($form, $field, $clang, $langField);
                // work with elements
                if ($langField) {
                    foreach ($field['fields'] as $langItem) {
                        $item = $langItem;
                        if (array_key_exists('name', $langItem)) {
                            $item['name'] = $langItem['name'] . '_' . $clang->getId();
                        }
                        self::addFormElementByField($form, $item);
                    }
                } else {
                    foreach ($field->fields as $item) {
                        self::addFormElementByField($form, $item);
                    }
                }

                // close panel
                self::closePanel($form);

            } else {
                // work with elements
                if (rex_clang::count() > 1 && $langField) {
                    $field['name'] = $field['name'] . '_' . $clang->getId();
                    self::addFormElementByField($form, $field);
                } else {
                    self::addFormElementByField($form, $field);
                }
            }
        }
    }

    /**
     * @param rex_form $form
     * @param $field
     * @author Joachim Doerr
     */
    private static function openPanel(rex_form $form, $field, rex_clang $clang, $langField = false)
    {
        forCalFormHelper::addCollapsePanel($form, 'wrapper', '', $clang, $langField);
        forCalFormHelper::addCollapsePanel($form, 'inner_wrapper', self::getLabel($field), $clang, $langField);
    }

    /**
     * @param rex_form $form
     * @author Joachim Doerr
     */
    private static function closePanel(rex_form $form)
    {
        forCalFormHelper::addCollapsePanel($form, 'close_inner_wrapper');
        forCalFormHelper::addCollapsePanel($form, 'close_wrapper');
    }

    /**
     * @param $field
     * @return string
     * @author Joachim Doerr
     */
    private static function getLabel($field)
    {
        if (array_key_exists('label_all', $field)) {
            return $field['label_all'];
        }

        $lang = explode('_', rex_i18n::getLocale());

        foreach ($lang as $value) {
            $property = 'label_' . $value;
            if (array_key_exists($property, $field)) {
                return $field[$property];
            }
        }

        return '';
    }

    /**
     * @param $field
     * @return string
     * @author Joachim Doerr
     */
    private static function getPrefix($field)
    {
        if (array_key_exists('prefix_all', $field)) {
            return $field['prefix_all'];
        }

        $lang = explode('_', rex_i18n::getLocale());

        foreach ($lang as $value) {
            $property = 'prefix_' . $value;
            if (array_key_exists($property, $field)) {
                return $field[$property];
            }
        }

        return '';
    }

    /**
     * @param $field
     * @return string
     * @author Joachim Doerr
     */
    private static function getSuffix($field)
    {
        if (array_key_exists('suffix_all', $field)) {
            return $field['suffix_all'];
        }

        $lang = explode('_', rex_i18n::getLocale());

        foreach ($lang as $value) {
            $property = 'suffix_' . $value;
            if (array_key_exists($property, $field)) {
                return $field[$property];
            }
        }

        return '';
    }

    /**
     * @param rex_form $form
     * @param $field
     * @author Joachim Doerr
     */
    private static function addFormElementByField(rex_form $form, $field)
    {
        // Prüfen, ob der aktuelle Benutzer Media-Felder sehen darf
        $canUploadMedia = rex::getUser() && (rex::getUser()->isAdmin() || forCalUserPermission::canUploadMedia());
        
        if (array_key_exists('type', $field)) {
            // Bei Media-Feldern prüfen, ob der Benutzer die Berechtigung hat
            if (in_array($field['type'], ['media', 'medialist']) && !$canUploadMedia) {
                return; // Media-Feld überspringen
            }
            
            switch ($field['type']) {
                case 'media':
                    $formField = $form->addMediaField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'medialist':
                    $formField = $form->addMedialistField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'text':
                    $formField = $form->addTextField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'linklist':
                    $formField = $form->addLinklistField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'link':
                    $formField = $form->addLinkmapField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'textarea':
                    $formField = $form->addTextAreaField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    break;
                case 'checkbox':
                    $formField = $form->addCheckboxField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    if (array_key_exists('options', $field)) {
                        foreach ($field['options'] as $k => $v) {
                            $formField->addOption($v, $k);
                        }
                    }
                    break;
                case 'checkboxsql':
                    $formField = $form->addCheckboxField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    $options = \rex_sql::factory()->getArray($field['qry']);
                    foreach ($options as $v) {
                        $formField->addOption($v['name'], $v['id']);
                    }
                    break;
                case 'radio':
                case 'radiosql':
                    $formField = $form->addRadioField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    if (array_key_exists('options', $field)) {
                        foreach ($field['options'] as $k => $v) {
                            $formField->addOption($v, $k);
                        }
                    }
                    if (array_key_exists('qry', $field)) {
                        $options = \rex_sql::factory()->getArray($field['qry']);
                        foreach ($options as $v) {
                            $formField->addOption($v['name'], $v['id']);
                        }
                    }
                    break;
                case 'select':
                case 'selectsql':
                    $formField = $form->addSelectField($field['name']);
                    $formField->setLabel(self::getLabel($field));
                    $formField->setPrefix(self::getPrefix($field));
                    $formField->setSuffix(self::getSuffix($field));
                    $formField->setAttribute('class', 'selectpicker form-control');
                    if (array_key_exists('options', $field)) {
                        $select = $formField->getSelect();
                        foreach ($field['options'] as $key => $val) {
                            $select->addOption($val, $key);
                        }
                    }
                    if (array_key_exists('qry', $field)) {
                        $options = \rex_sql::factory()->getArray($field['qry']);
                        $select = $formField->getSelect();
                        foreach ($options as $v) {
                            $select->addOption($v['name'], $v['id']);
                        }
                    }
                    break;
            }

            if (is_object($formField)) {
                if (array_key_exists('attribute', $field)) {
                    foreach ($field['attribute'] as $at_key => $at_value) {
                        $formField->setAttribute($at_key, $at_value);
                    }
                }
            }

        }
    }
    
    /**
     * Prüft, ob ein Feld vom Typ 'media' oder 'medialist' ist
     * 
     * @param array $field Das zu prüfende Feld
     * @return bool true wenn es ein Media-Feld ist
     */
    private static function isMediaField($field)
    {
        return isset($field['type']) && in_array($field['type'], ['media', 'medialist']);
    }
}
