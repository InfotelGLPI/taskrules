<?php

/**
 * -------------------------------------------------------------------------
 * TaskRules plugin for GLPI
 * Copyright (C) 2025 by the TaskRules Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */

define('PLUGIN_TASKRULES_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_TASKRULES_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_TASKRULES_MAX_GLPI_VERSION", "10.0.99");

define("PLUGIN_TASKRULES_DIR", Plugin::getPhpDir("taskrules"));

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

    $PLUGIN_HOOKS['add_css']['taskrules'][] = "css/accordion.css";

    if (array_key_exists('REQUEST_URI', $_SERVER) && strpos($_SERVER['REQUEST_URI'], "tasktemplate.form.php") !== false && isset($_GET['id'])) {
        $PLUGIN_HOOKS['add_javascript']['taskrules'][] = 'js/accordion.js';
    }

    /* Prepare hooks to intercept tasks events (add task and task update) */
    $PLUGIN_HOOKS['pre_item_add']['taskrules'] = ['TicketTask' => 'taskrules_pre_additem_called'];
    $PLUGIN_HOOKS['item_add']['taskrules'] = ['TicketTask' => 'taskrules_additem_called'];
    $PLUGIN_HOOKS['item_update']['taskrules'] = ['TicketTask' => 'taskrules_updateitem_called'];

    /* Add a new tab on the Task template page */
    Plugin::registerClass('PluginTaskrulesCondition', ['addtabon' => ['TaskTemplate']]);
      

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
        'name'           => 'TaskRules',
        'version'        => PLUGIN_TASKRULES_VERSION,
        'author'         => 'Doudard Edmond / Infotel',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi'       => [
                'min' => PLUGIN_TASKRULES_MIN_GLPI_VERSION,
                'max' => PLUGIN_TASKRULES_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_taskrules_check_prerequisites()
{
    return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_taskrules_check_config($verbose = false)
{
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo __('Installed / not configured', 'taskrules');
    }
    return false;
}
