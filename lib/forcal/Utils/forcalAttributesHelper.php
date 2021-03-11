<?php
/**
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;

use rex_addon;
use rex_config;

class forCalAttributesHelper
{
    /**
     * @param rex_form_element $field
     * @return rex_form_element
     * @author Thorben Jaworr
     */    
    public static function setAdditionalAttributes($field)
    {
        $additionalAttributes = null;

        if (strpos($field->getFieldName(), 'text') !== false)
        {
            $additionalAttributes = rex_config::get('forcal', 'forcal_additional_attributes_text');
        }
        elseif (strpos($field->getFieldName(), 'teaser') !== false)
        {
            $additionalAttributes = rex_config::get('forcal', 'forcal_additional_attributes_teaser');
        }

        if(!$additionalAttributes)
        {
            return $field;
        }
        
        $attributesArray = json_decode(htmlspecialchars_decode($additionalAttributes), true);

        foreach ($attributesArray as $key => $value)
        {
            if($key == "class")
            {
                $additionalClasses = $value;
                
                if ($field->getAttribute("class"))
                {
                    $additionalClasses .= ' ' . $field->getAttribute("class") . ' ';
                }

                $field->setAttribute($key, $additionalClasses);
            }
            else
            {
                $field->setAttribute($key, $value);
            }
        }
        
        return $field;
    }
}