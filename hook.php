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

const STATUS_INFORMATION   = 0;
const STATUS_TODO          = 1;
const STATUS_DONE          = 2;

/**
 * Handle update item hook
 *
 * @param CommonDBTM $item Item instance
 *
 * @return void
 */
function taskrules_updateitem_called(CommonDBTM $item)
{
    //do everything you want!
    //remember that $item is passed by reference (it is an object)
    //so changes you will do here will be used by the core.
    if ($item::getType() === TicketTask::getType()) {
        Toolbox::logInFile("EdmondDebug", "Plugin TaskRules : a task was updated, I'm in taskrules_updateitem_called()\n");
        //Toolbox::logInFile("EdmondDebug",print_r($item,true));

        switch ($item->fields['state']) {
            case STATUS_INFORMATION:
                Toolbox::logInFile("EdmondDebug", "Etat de la tâche : Information\n");
                break;
            case STATUS_TODO:
                Toolbox::logInFile("EdmondDebug", "Etat de la tâche : To do\n");
                break;
            case STATUS_DONE:
                Toolbox::logInFile("EdmondDebug", "Etat de la tâche : Done\n");
                break;
            default:
                echo "The value is not 1, 2, or 3.";
                break;
        }
    } else {
        Toolbox::logInFile("EdmondDebug", "Plugin TaskRules : something was updated, I'm in taskrules_updateitem_called()\n");
        Toolbox::logInFile("EdmondDebug", "Plugin TaskRules : something was updated, it's a " . $item::getType() . "\n");
    }
}

function taskrules_pre_additem_called(CommonDBTM $item)
{

    $condition = true;
    if ($condition) {
        $item->input = []; /* This action triggers cancellation of the task creation */
        Session::addMessageAfterRedirect('Condition non remplie, tâche non créée.', false, ERROR);
        return;
    }
}

/**
 * Handle add item hook
 *
 * @param CommonDBTM $item Item instance
 *
 * @return void
 */
function taskrules_additem_called(CommonDBTM $item)
{
    //do everything you want!
    //remember that $item is passed by reference (it is an object)
    //so changes you will do here will be used by the core.
    if ($item::getType() === TicketTask::getType()) {
        Toolbox::logInFile("EdmondDebug", "Plugin TaskRules : a task was added, I'm in taskrules_additem_called()\n");
        //Toolbox::logInFile("EdmondDebug",print_r($item,true));
    } else {
        Toolbox::logInFile("EdmondDebug", "Plugin TaskRules : something was added, I'm in taskrules_additem_called()\n");
    }

    // if ($item::getType() === Computer::getType()) {
    //    //we're working with a computer
    // } elseif ($item::getType() === Phone::getType()) {
    //    //we're working with a phone
    // }
}

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

    PluginTaskrulesProfile::removeRightsFromSession();
    PluginTaskrulesProfile::removeRightsFromDB();

    return true;
}
