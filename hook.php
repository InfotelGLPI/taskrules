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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QuerySubQuery;
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

    // Display preferences, saved searches... of the plugin itemtypes (Log is listed through
    // Search::show()): left behind, they would point to classes that no longer exist
    foreach ([
        DisplayPreference::class,
        SavedSearch::class,
        SavedSearch_User::class,
        Notepad::class,
        \Log::class,
    ] as $itemtype) {
        $item = new $itemtype();
        if ($item instanceof SavedSearch_User) {
            $item->deleteByCriteria(['savedsearches_id' => new QuerySubQuery([
                'SELECT' => 'id',
                'FROM'   => SavedSearch::getTable(),
                'WHERE'  => ['itemtype' => [Rule::class, Condition::class, Log::class]],
            ])]);
            continue;
        }
        $item->deleteByCriteria(['itemtype' => [Rule::class, Condition::class, Log::class]]);
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

/**
 * Task whose rule results wait to be shown (set by Rule::afterAddTask() / afterUpdateTask()).
 *
 * @return int 0 when no result is pending
 */
function plugin_taskrules_get_pending_task(): int
{
    $tickettasks_id = $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] ?? 0;
    return is_int($tickettasks_id) && $tickettasks_id > 0 ? $tickettasks_id : 0;
}

/**
 * Results modal of the rules (one per page, whatever the number of tasks displayed), with the
 * results of the pending task when given: they are rendered with the page, so the modal opens
 * at once, without an extra AJAX round trip.
 *
 * @param array<int, bool> $task_states states of the tasks a rule may change
 */
function plugin_taskrules_display_results_modal(int $pending_task, array $task_states = []): void
{
    static $rendered = false;

    $results_html = '';
    if ($pending_task > 0) {
        unset($_SESSION['plugin_taskrules']['show_updateresults']);
        $results_html = Log::renderPendingResults($pending_task);
    }

    TemplateRenderer::getInstance()->display('@taskrules/results_modal.html.twig', [
        'modal_rendered' => $rendered,
        'results_html'   => $results_html,
        'task_states'    => $task_states,
    ]);
    $rendered = true;
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

    // The timeline shows the task just saved: its results are shown now, not when a task
    // form is opened later (plugin_taskrules_post_item_form() only runs for the forms)
    if (Session::haveRight("plugin_taskrules", UPDATE)
        && plugin_taskrules_get_pending_task() === (int) $item->fields['id']) {
        plugin_taskrules_display_results_modal((int) $item->fields['id']);
    }
    if (!isset($item->fields['tasktemplates_id']) || $item->fields['tasktemplates_id'] <= 0) {
        return;
    }

    $rule = new Rule();
    if (!$rule->getFromDBByCrit(['tasktemplates_id' => $item->fields['tasktemplates_id'], 'trigger_update' => 1, 'active' => 1])) {
        return;
    }

    // The "done" checkbox of the task is locked by public/scripts/task_results.js
    TemplateRenderer::getInstance()->display('@taskrules/task_locked.html.twig', [
        'task_id' => (int) $item->fields['id'],
    ]);
}

function plugin_taskrules_post_item_form($params)
{
    // Results of the rules are only shown to the profiles that can update them
    if (!Session::haveRight("plugin_taskrules", UPDATE)
        || !($params['item'] instanceof TicketTask)) {
        return;
    }

    // Results still pending (the task just saved was not shown in a timeline)
    $pending_task = plugin_taskrules_get_pending_task();

    // States of the tasks of the ticket a rule may change, synchronised in the timeline
    $task_states = [];
    $tickets_id  = (int) ($params['item']->fields['tickets_id'] ?? 0);
    if ($pending_task === 0 && $tickets_id > 0) {
        $item_ticket = new Item_Ticket();
        if (count($item_ticket->find(['tickets_id' => $tickets_id])) > 0) {
            $tickettask = new TicketTask();
            foreach ($tickettask->find(['tickets_id' => $tickets_id]) as $task) {
                $rule = new Rule();
                if ($rule->getFromDBByCrit(['tasktemplates_id' => $task['tasktemplates_id'], 'trigger_update' => 1, 'active' => 1])) {
                    $task_states[(int) $task['id']] = (int) $task['state'] === Planning::DONE;
                }
            }
        }
    }

    if ($pending_task === 0 && $task_states === []) {
        return;
    }

    plugin_taskrules_display_results_modal($pending_task, $task_states);
}
