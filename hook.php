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


/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_taskrules_install()
{
    global $DB;

    if (!$DB->tableExists("glpi_plugin_taskrules_rules")) {
        $DB->runFile(PLUGIN_TASKRULES_DIR . "/install/sql/empty-1.0.0.sql");
    }

    PluginTaskrulesProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    PluginTaskrulesProfile::initProfile();

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_taskrules_uninstall()
{
    global $DB;

    $tables = ["glpi_plugin_taskrules_rules", "glpi_plugin_taskrules_conditions"];

    foreach ($tables as $table) {
        $DB->dropTable($table);
    }

    $profileRight = new ProfileRight();
    foreach (PluginTaskrulesProfile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    return true;
}


function plugin_taskrules_pre_additem(CommonDBTM $item)
{
    PluginTaskrulesRule::preAddTask($item);
}

/**
 * Handle add item hook
 *
 * @param CommonDBTM $item Item instance
 *
 * @return void
 */
function plugin_taskrules_additem(CommonDBTM $item)
{
    PluginTaskrulesRule::afterAddTask($item);
}

/**
 * Handle update item hook
 *
 * @param CommonDBTM $item Item instance
 *
 * @return void
 */
function plugin_taskrules_updateitem(CommonDBTM $item)
{
    PluginTaskrulesRule::afterUpdateTask($item);

}
