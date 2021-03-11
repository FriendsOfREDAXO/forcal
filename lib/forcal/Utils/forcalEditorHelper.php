<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

namespace forCal\Utils;


use redactor2;
use rex_addon;
use rex_config;

class forCalEditorHelper
{
    /**
     * @author Joachim Doerr
     */
    public static function addEditorSets()
    {
        self::redactor2set();
//        self::markitupSet('textile');
//        self::markitupSet('markdown');
    }

    /**
     * @author Joachim Doerr
     */
    public static function redactor2set()
    {
        if (rex_addon::exists('redactor2') && array_key_exists('redactor2',rex_addon::getAvailableAddons())) {
            if (!redactor2::profileExists('forcal_teaser')) {
                redactor2::insertProfile('forcal_teaser', 'forCal teaser config', 150, 250, 'relative', 0, 0, 0, 1, 'blockquote,bold,italic,underline,deleted,cleaner,fontsize[100%|120%|140%],grouplink[email|external|internal|media]');
            }
            if (!redactor2::profileExists('forcal_text')) {
                redactor2::insertProfile('forcal_text', 'forCal text config', 300, 800, 'relative', 0, 0, 0, 1, 'groupheading[2|3|4|5],unorderedlist,alignment,blockquote,bold,italic,underline,deleted,cleaner,fontsize[100%|120%|140%],grouplink[email|external|internal|media],horizontalrule,fullscreen');
            }
        }
    }

//    /**
//     * @author Joachim Doerr
//     * @param string $type
//     */
//    public static function markitupSet($type = 'textile')
//    {
//        if (rex_addon::exists('markitup') && array_key_exists('markitup',rex_addon::getAvailableAddons())) {
//            if (!markitup::profileExists('forcal_teaser_'.$type)) {
//                markitup::insertProfile('forcal_teaser_'.$type, 'forCal teaser config', $type, 150, 250, 'relative', 'bold,italic');
//            }
//            if (!markitup::profileExists('forcal_text_'.$type)) {
//                markitup::insertProfile('forcal_teaser_'.$type, 'forCal text config', $type, 300, 800, 'relative', 'bold,italic');
//            }
//        }
//    }

    /**
     * @param string $profil
     * @return string
     * @author Joachim Doerr
     */
    public static function getEditorClass($profil = 'forcal_teaser')
    {
        switch (rex_config::get('forcal', 'forcal_editor'))
        {
            case 3:
                return 'redactorEditor2-' . $profil;
            case 2:
                return 'markitupEditor-' . $profil . '_textile';
            case 1:
                return 'markitupEditor-' . $profil . '_markdown';
            default:
            case 0:
                return 'none';
        }
    }
}