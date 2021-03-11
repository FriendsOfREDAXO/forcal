<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

// thanks @thorben for the cool calendar view idea !
// and for the nice colors for the calender headline and weekend

// Get Backend-Lang
$userLang = rex::getUser()->getLanguage();
        if ('' === trim($userLang)) {
            $userLang = rex::getProperty('lang');
}
$userLang = substr($userLang , 0,-3); 
// show
$fragment = new rex_fragment();
$fragment->setVar('class', 'default calendarview', false);
$fragment->setVar('title', rex_i18n::msg('forcal_calendar_view'));
$fragment->setVar('body', '<div id="forcal" data-date="' . date("Y-m-d") . '" data-csrf="' . \forCal\Handler\forCalApi::getToken() . '" data-locale="'. $userLang.'"></div>
<div id="calmodal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog modal-full" role="document">
    <div class="modal-content">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <div class="calmodal-body"></div>
    </div>
  </div>
</div>', false);
echo $fragment->parse('core/page/section.php');

