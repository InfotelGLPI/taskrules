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

    $tables = ["glpi_plugin_taskrules_rules",
        "glpi_plugin_taskrules_conditions",
        "glpi_plugin_taskrules_logs"];

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

function plugin_taskrules_pre_updateitem(CommonDBTM $item)
{
    PluginTaskrulesRule::preUpdateTask($item);
}

function plugin_taskrules_post_prepareadditem(CommonDBTM $item)
{
//    $item->input['state'] = Planning::DONE;
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

function plugin_taskrules_post_item_form($params)
{

    if (Session::haveRight("plugin_taskrules", READ)
     && $params['item']::getType() === TicketTask::getType()) {

        if (isset($_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'])
            && is_int($_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'])
        && $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] > 0) {
            $tickettasks_id = $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'];

            unset($_SESSION['plugin_taskrules']['show_updateresults']);
            echo Ajax::createIframeModalWindow(
                "results$tickettasks_id",
                PLUGIN_TASKRULES_WEBDIR . "/front/modalresults.php",
                [
                    'title' => __('Results of changes of linked items', 'taskrules'),
                    'reloadonclose' => false,
                    'display' => false,
                    'width' => 1140,
                    'height' => 500,
                    'dialog_class'  => 'modal-xl',
                ]
            );

            $id = (int) $tickettasks_id;
            echo Html::scriptBlock(
                "
            let taskDiv$id = document.getElementById('TicketTask_$id');

                    if (taskDiv$id) {
                        
                        let button$id = document.createElement('button');
                        button$id.className = ''; // classes Bootstrap ou les tiennes
                        button$id.textContent = '';
                        button$id.style.marginTop = '10px';
                        
                        button$id.addEventListener('click', function () {
                            plugin_taskrules_showresults($id);
                        });
                        
                        button$id.click();
                    }

                    function plugin_taskrules_showresults(taskId) {
                        $.ajax({
                            type: 'POST',
                            url: '" . PLUGIN_TASKRULES_WEBDIR . "/ajax/generateresult.php',
                            data: {
                                'tickettasks_id': taskId
                            },
                            success: function (html) {
                                let modalBody = document.querySelector('#results' + taskId + ' div.modal-body');
                                if (modalBody) {
                                    modalBody.innerHTML = html;

                                    $('#results' + taskId).modal('show');
                                    $('#ajax_loader').hide();
                                }
                            }
                        });
                    }"
            );
        } else {
            $tickettask = $params['item'];
            if (isset($tickettask->fields['tickets_id'])) {
                $tickettasks = new TicketTask();
                $tasks_found = $tickettasks->find([
                    'tickets_id' => $tickettask->fields['tickets_id'],
                ]);
                if (count($tasks_found) > 0) {
                    foreach ($tasks_found as $task_found) {
                        $task = new TicketTask();
                        if ($task->getFromDB($task_found['id'])) {
                            $rule = new PluginTaskrulesRule();
                            if ($rule->getFromDBByCrit(
                                ['tasktemplates_id' => $task->fields['tasktemplates_id'], 'trigger_update' => 1]
                            )) {
                                $item_ticket = new Item_Ticket();
                                $items_linked = $item_ticket->find([
                                    'tickets_id' => $tickettask->fields['tickets_id'],
                                ]);
                                if (count($items_linked) > 0) {
                                    $rule->launchResultsOnTicketTaskState($task_found['id']);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
