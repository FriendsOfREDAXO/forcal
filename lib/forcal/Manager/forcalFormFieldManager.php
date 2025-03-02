<?php
/**
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;

use rex;
use rex_form;
use rex_form_element;
use rex_form_container;

class forCalFormFieldManager
{
    /**
     * Überprüft und verarbeitet Formularfelder basierend auf Benutzerberechtigungen
     * 
     * @param rex_form $form Das zu überprüfende Formular
     * @return rex_form Das angepasste Formular
     */
    public static function processFormFields(rex_form $form)
    {
        // Prüfen, ob ein Benutzer eingeloggt ist
        if (!rex::getUser()) {
            return $form;
        }
        
        // Admin und Benutzer mit Media-Rechten überspringen
        if (rex::getUser()->isAdmin() || forCalUserPermission::canUploadMedia()) {
            return $form;
        }
        
        // Bei Benutzern ohne Media-Rechte Media-Felder ausblenden
        self::processFormContainer($form);
        
        return $form;
    }
    
    /**
     * Verarbeitet einen Container und seine untergeordneten Elemente
     * 
     * @param rex_form_container $container Der zu verarbeitende Container
     */
    private static function processFormContainer(rex_form_container $container)
    {
        $fieldsToRemove = [];
        
        // Alle Formularelemente durchgehen
        foreach ($container->getElements() as $key => $element) {
            // Bei Containern rekursiv verarbeiten
            if ($element instanceof rex_form_container) {
                self::processFormContainer($element);
            } 
            // Bei Media-Feldern markieren zum Entfernen
            else if ($element instanceof rex_form_element) {
                if (self::isMediaField($element)) {
                    $fieldsToRemove[] = $key;
                }
            }
        }
        
        // Markierte Felder entfernen
        foreach ($fieldsToRemove as $key) {
            $container->removeElement($key);
        }
    }
    
    /**
     * Prüft, ob es sich um ein Media-Feld handelt
     * 
     * @param rex_form_element $element Das zu prüfende Element
     * @return bool true, wenn es sich um ein Media-Feld handelt
     */
    private static function isMediaField(rex_form_element $element)
    {
        $className = get_class($element);
        return strpos($className, 'MediaElement') !== false || 
               strpos($className, 'MedialistElement') !== false;
    }
}
