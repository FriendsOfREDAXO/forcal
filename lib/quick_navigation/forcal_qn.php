<?php
class forcalQn
{
    public static function getCalHistory($ep): ?string
    {
        $subject = $ep->getSubject();
        $forcals = $categoryId = $filter_date = $forcalID = $start = $addLink = $filter_date = $today = $halfayear = '';

        $filter_date = ("Y-m-d");
        $categoryId = null;
        $start = date("Y-m-d");
        $today = strtotime($start);
        $halfayear = strtotime('+ 2 month', $today);
        $filter_date = date("Y-m-d", $halfayear);


        $forcals = \forCal\Handler\forCalHandler::getEntries($start, $filter_date, false, 'SORT_ASC', $categoryId);
        $link = [];
        if (count($forcals)) {

            foreach ($forcals as $forcal) {
                $forcalId = rex_escape($forcal['id']);
                $forcal_entry = rex_escape($forcal['entry']);
                $forcal_name = rex_escape($forcal_entry->entry_name);
                $forcal_start_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_start_date->format('d.m.Y')));
                $forcal_end_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_end_date->format('d.m.Y')));
                $entry_start_time = $forcal_entry->entry_start_time;
                $entry_start_time_date = new DateTime($entry_start_time);
                $forcal_start_time = rex_escape($entry_start_time_date->format('H:i'));

                $entry_end_time = $forcal_entry->entry_end_time;
                $entry_end_time_date = new DateTime($entry_end_time);
                $forcal_end_time = rex_escape($entry_end_time_date->format('H:i'));

                $forcal_color = rex_escape($forcal_entry->category_color);


                $href = rex_url::backendPage(
                    'forcal/entries',
                    [
                        'func' => 'edit',
                        'id' => $forcalId
                    ]
                );
              $link[] = '<li class="forcal_border" style="border-color:' . $forcal_color . '"><a href="' . $href . '" title="' . $forcal_name  . '">' . $forcal_name . '<small>' . $forcal_start_date . ' bis ' . $forcal_end_date . ' - ' . $forcal_start_time . ' bis ' . $forcal_end_time . '</small></a></li>';
            }
        }
        $href = rex_url::backendPage(
            'forcal/entries',
            [
                'func' => 'add'
            ]
        );

        $addLink = '<li class=""><a class="btn btn-default" accesskey="e" href="' . $href . '" title="' . rex_i18n::msg(" forcal_add_new_entry") . '"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp' . rex_i18n::msg("forcal_add_new_entry") . '</a></li>';
        $fragment = new rex_fragment();
        $fragment->setVar('link', $addLink, false);
        if (count($link)) {
            $fragment->setVar('items', $link, false);
        }
        $fragment->setVar('icon', 'fa fa-calendar');
        $subject .= $fragment->parse('quick_button.php');        
        return $subject;
    }
}
