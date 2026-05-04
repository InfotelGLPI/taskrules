<?php

/*
 -------------------------------------------------------------------------
 Taskrules plugin for GLPI
 Copyright (C) 2025-2025 by the Taskrules Development Team.

 https://github.com/InfotelGLPI/taskrules
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Taskrules.

 Taskrules is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Taskrules is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Taskrules. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */


global $CFG_GLPI;

use Glpi\Plugin\Hooks;
use GlpiPlugin\Taskrules\Profile;
use GlpiPlugin\Taskrules\Rule;

define('PLUGIN_TASKRULES_VERSION', '2.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_TASKRULES_MIN_GLPI_VERSION", "11.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_TASKRULES_MAX_GLPI_VERSION", "11.0.99");

define("PLUGIN_TASKRULES_DIR", Plugin::getPhpDir("taskrules"));
$root = $CFG_GLPI['root_doc'] . '/plugins/taskrules';
define("PLUGIN_TASKRULES_WEBDIR", $root);
/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_taskrules()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['taskrules'] = [Profile::class, 'initProfile'];

    if (Plugin::isPluginActive('taskrules')) {

        Plugin::registerClass(Profile::class, ['addtabon' => "Profile"]);

        if (Session::haveRight("plugin_taskrules", READ)
            || Session::haveRight("config", READ)) {

            if (isset($_SERVER['REQUEST_URI'])
                && strpos($_SERVER['REQUEST_URI'], 'tasktemplate.form.php') !== false) {
                $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['taskrules'][] = 'js/accordion.js';
                $PLUGIN_HOOKS[Hooks::ADD_CSS]['taskrules'][] = "css/accordion.css";
            }
            $PLUGIN_HOOKS[Hooks::ADD_CSS]['taskrules'][] = "css/taskrules.css";
            /* Prepare hooks to intercept tasks events (add task and task update) */
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['taskrules']    = ['TicketTask' => 'plugin_taskrules_pre_additem'];
            $PLUGIN_HOOKS[Hooks::POST_PREPAREADD]['taskrules'] = ['TicketTask' => 'plugin_taskrules_post_prepareadditem'];
            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['taskrules']        = ['TicketTask' => 'plugin_taskrules_additem'];
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['taskrules'] = ['TicketTask' => 'plugin_taskrules_pre_updateitem'];
            $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['taskrules']     = ['TicketTask' => 'plugin_taskrules_updateitem'];

            $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['taskrules'] = 'plugin_taskrules_post_item_form';
            $PLUGIN_HOOKS[Hooks::POST_SHOW_ITEM]['taskrules'] = 'plugin_taskrules_post_show_item';
            /* Add a new tab on the Task template page */
            Plugin::registerClass(Rule::class, ['addtabon' => ['TaskTemplate']]);
        }
    }
    if (Session::haveRight("config", UPDATE)) {
        $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['taskrules'] = 'front/config.form.php';
    }
}

/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_taskrules()
{
    return [
        'name'           => _n('Task rule', 'Task rules', 2, 'taskrules'),
        'version'        => PLUGIN_TASKRULES_VERSION,
        'author'         => 'Doudard Edmond / Infotel',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi'       => [
                'min' => PLUGIN_TASKRULES_MIN_GLPI_VERSION,
                'max' => PLUGIN_TASKRULES_MAX_GLPI_VERSION,
            ],
        ],
    ];
}
