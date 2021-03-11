<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Manager;


use rex_clang;
use rex_form;
use forCal\Handler\forCalFormFieldsetHandler;
use forCal\Utils\forCalDefinitions;

class forCalFormManager
{
    /**
     * @param rex_form $form
     * @param rex_clang $clang
     * @return rex_form
     * @author Joachim Doerr
     */
    public static function addCustomLangFormField(rex_form $form, rex_clang $clang)
    {
        $definition = forCalDefinitions::getDefinition($form->getTableName());
        if (!is_null($definition)) {
            foreach ($definition['data'] as $fieldsetKey => $fieldset) {
                if ($fieldsetKey == 'langfields') {
                    forCalFormFieldsetHandler::handleLangFormFieldset($form, $fieldset, $clang);
                    break;
                }
            }
        }

        return $form;
    }

    /**
     * @param rex_form $form
     * @param rex_clang $clang
     * @return rex_form
     * @author Joachim Doerr
     */
    public static function addCustomFormField(rex_form $form, rex_clang $clang)
    {
        $definition = forCalDefinitions::getDefinition($form->getTableName());
        if (!is_null($definition)) {
            foreach ($definition['data'] as $fieldsetKey => $fieldset) {
                if ($fieldsetKey == 'fields') {
                    forCalFormFieldsetHandler::handleFormFieldset($form, $fieldset, $clang);
                    break;
                }
            }
        }
        return $form;

    }
}