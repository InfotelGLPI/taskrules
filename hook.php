<?php

/**
 * -------------------------------------------------------------------------
 * Taskrules plugin for GLPI
 * Copyright (C) 2025-2026 by the Taskrules Development Team.
 *
 * https://github.com/InfotelGLPI/Taskrules
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Taskrules.
 *
 * Taskrules is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Taskrules is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Taskrules. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Log;
use GlpiPlugin\Taskrules\Profile;
use GlpiPlugin\Taskrules\Rule;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_taskrules_install()
{
    $migration = new Migration(PLUGIN_TASKRULES_VERSION);

    Condition::install($migration);

    Log::install($migration);

    Rule::install($migration);

    $migration->executeMigration();

    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    Profile::initProfile();

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

    // Purge the plugin's profile rights BEFORE dropping the tables: getAllRights()
    // resolves its right set through \Profile::getRightsFor(Rule::class), which
    // instantiates Rule and introspects its table (SHOW COLUMNS). Running this after
    // Rule::uninstall() would query glpi_plugin_taskrules_rules once it no longer
    // exists and abort the whole uninstall. $all=true so any secondary rights are
    // removed too (no residual rows left in glpi_profilerights).
    $profileRight = new ProfileRight();
    foreach (Profile::getAllRights(true) as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    Condition::uninstall();

    Log::uninstall();

    Rule::uninstall();

    return true;
}


function plugin_taskrules_pre_additem(CommonDBTM $item)
{
    Rule::preAddTask($item);
}

function plugin_taskrules_pre_updateitem(CommonDBTM $item)
{
    Rule::preUpdateTask($item);
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
    Rule::afterAddTask($item);
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

    Rule::afterUpdateTask($item);
}

function plugin_taskrules_post_show_item($params)
{
    if (!Session::haveRight("plugin_taskrules", READ)) {
        return;
    }
    $item = $params['item'];
    if (!($item instanceof TicketTask) || $item->isNewItem()) {
        return;
    }
    if (!isset($item->fields['tasktemplates_id']) || $item->fields['tasktemplates_id'] <= 0) {
        return;
    }

    $rule = new Rule();
    if (!$rule->getFromDBByCrit(['tasktemplates_id' => $item->fields['tasktemplates_id'], 'trigger_update' => 1, 'active' => 1])) {
        return;
    }

    $id = (int) $item->fields['id'];

    echo Html::scriptBlock("
        (function () {
            var taskItem = document.querySelector('#TicketTask_{$id}');
            if (!taskItem) return;
            var checkbox = taskItem.querySelector('.todo-list-state .task-state-checkbox');
            if (checkbox) {
                checkbox.disabled = true;
                checkbox.removeAttribute('onclick');
                checkbox.title = " . json_encode(__('State managed by a rule', 'taskrules'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ";
            }
        })();
    ");
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
            // One-shot marker consumed by ajax/generateresult.php: the endpoint
            // only runs for a task whose rule was actually triggered by a hook.
            $_SESSION['plugin_taskrules']['pending_results'][(int) $tickettasks_id] = true;
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
                ],
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
                    }",
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
                            $rule = new Rule();
                            if ($rule->getFromDBByCrit(
                                ['tasktemplates_id' => $task->fields['tasktemplates_id'], 'trigger_update' => 1],
                            ) && $rule->fields['active'] == 1) {

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
