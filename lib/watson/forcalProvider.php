<?php

/**
 * This file is part of the forcal package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Watson\Workflows\forCal;

use Watson\Foundation\SupportProvider;
use Watson\Foundation\Workflow;

class forCalProvider extends SupportProvider
{
    /**
     * Register the directory to search a translation file.
     *
     * @return string
     */
    public function i18n()
    {
        return __DIR__;
    }

    /**
     * Register the service provider.
     *
     * @return Workflow|array
     */
    public function register()
    {
        if (\rex_addon::get('forcal')->isAvailable()) {
            return $this->registerforCalSearch();
        }
        return [];
    }

    /**
     * Register yform search.
     *
     * @return Workflow
     */
    public function registerforCalSearch()
    {
        return new forCalSearch();
    }
}

