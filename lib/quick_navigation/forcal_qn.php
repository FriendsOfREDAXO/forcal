<?hp class forcalQn
{
    public static function getCalHistory($ep): ?string
    {
        $subject = $ep->getSubject();
        
        $start = date("Y-m-d");
        $halfayear = date("Y-m-d", strtotime('+2 month', strtotime($start)));

        $forcals = \forCal\Handler\forCalHandler::getEntries($start, $halfayear, false, 'SORT_ASC');

        $links = [];

        foreach ($forcals as $forcal) {
            $forcal_entry = $forcal['entry'];

            $forcal_name = rex_escape($forcal_entry->entry_name);
            $forcal_start_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_start_date->format('d.m.Y')));
            $forcal_end_date = rex_formatter::intlDate(strtotime($forcal_entry->entry_end_date->format('d.m.Y')));

            $entry_start_time_date = new DateTime($forcal_entry->entry_start_time);
            $entry_end_time_date = new DateTime($forcal_entry->entry_end_time);

            $forcal_start_time = rex_escape($entry_start_time_date->format('H:i'));
            $forcal_end_time = rex_escape($entry_end_time_date->format('H:i'));
            $forcal_color = rex_escape($forcal_entry->category_color);

            $href = rex_url::backendPage(
                'forcal/entries',
                [
                    'func' => 'edit',
                    'id' => rex_escape($forcal['id'])
                ]
            );

            $links[] = '<li class="quick_navi_forcal_border" style="border-color:' . $forcal_color . '"><a href="' . $href . '" title="' . $forcal_name  . '">' . $forcal_name . '<small>' . $forcal_start_date . ' bis ' . $forcal_end_date . ' - ' . $forcal_start_time . ' bis ' . $forcal_end_time . '</small></a></li>';
        }

        $href = rex_url::backendPage(
            'forcal/entries',
            [
                'func' => 'add'
            ]
        );

        $addLink = '<li class=""><a class="btn btn-default" accesskey="e" href="' . $href . '" title="' . rex_i18n::msg("forcal_add_new_entry") . '"><i class="fa fa-plus" aria-hidden="true"></i>&nbsp' . rex_i18n::msg("forcal_add_new_entry") . '</a></li>';

        $fragment = new rex_fragment();
        $fragment->setVar('link', $addLink, false);

        if (count($links)) {
            $fragment->setVar('items', implode('', $links), false);
        }

        $fragment->setVar('icon', 'fa fa-calendar');
        $subject .= $fragment->parse('quick_button.php');
        
        return $subject;
    }
}
