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


use Glpi\Plugin\Hooks;

define('PLUGIN_TASKRULES_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_TASKRULES_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_TASKRULES_MAX_GLPI_VERSION", "10.0.99");

define("PLUGIN_TASKRULES_DIR", Plugin::getPhpDir("taskrules"));
define("PLUGIN_TASKRULES_NOTFULL_DIR", Plugin::getPhpDir("taskrules", false));
define("PLUGIN_TASKRULES_WEBDIR", Plugin::getWebDir("taskrules"));
/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_taskrules()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['taskrules'] = true;
    $PLUGIN_HOOKS['change_profile']['taskrules']   = ['PluginTaskrulesProfile', 'initProfile'];

    $plugin = new Plugin();
    if (
        $plugin->isInstalled('taskrules')
        && $plugin->isActivated('taskrules')
    ) {

        Plugin::registerClass("PluginTaskrulesProfile", ['addtabon' => "Profile"]);

        if (Session::haveRight("plugin_taskrules", READ)
            || Session::haveRight("config", READ)) {

            if (isset($_SERVER['REQUEST_URI'])
                && strpos($_SERVER['REQUEST_URI'], 'tasktemplate.form.php') !== false) {
                $PLUGIN_HOOKS['add_javascript']['taskrules'][] = 'js/accordion.js';
                $PLUGIN_HOOKS['add_css']['taskrules'][] = "css/accordion.css";
            }
            $PLUGIN_HOOKS['add_css']['taskrules'][] = "css/taskrules.css";
            /* Prepare hooks to intercept tasks events (add task and task update) */
//            $PLUGIN_HOOKS['pre_item_add']['taskrules'] = ['TicketTask' => 'plugin_taskrules_pre_additem'];
            $PLUGIN_HOOKS['item_add']['taskrules'] = ['TicketTask' => 'plugin_taskrules_additem'];
            $PLUGIN_HOOKS['item_update']['taskrules'] = ['TicketTask' => 'plugin_taskrules_updateitem'];

            $PLUGIN_HOOKS['post_item_form']['taskrules'] = 'plugin_taskrules_post_item_form';
            /* Add a new tab on the Task template page */
            Plugin::registerClass('PluginTaskrulesRule', ['addtabon' => ['TaskTemplate']]);
        }
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
