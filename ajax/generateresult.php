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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Taskrules\Log;
use GlpiPlugin\Taskrules\Rule;

// Results of the rule of a task, shown in the modal opened after the task was saved.
//
// The actions themselves are run once, by the TicketTask hooks (Rule::afterAddTask() /
// afterUpdateTask()), which keep their results in $_SESSION['plugin_taskrules']['results']
// and set the one-shot marker consumed below. This endpoint used to replay
// Condition::checkCriteras() + Action::launchActions() on the linked items, so every action
// ran twice, while Log::showResults() rendered the session results anyway. It now only
// renders those results: the log is written by the hooks themselves (Log::writeLog()).
//
// Same right as the hook that opens the modal (plugin_taskrules_post_item_form()).
Session::checkRight("plugin_taskrules", UPDATE);

$tickettasks_id = (int) ($_POST['tickettasks_id'] ?? 0);
if ($tickettasks_id <= 0) {
    return;
}

$tickettask = new TicketTask();
if (!$tickettask->getFromDB($tickettasks_id)) {
    return;
}

// Access to the parent ticket (IDOR: the task id comes from the client)
$ticket = new Ticket();
if (!$ticket->can((int) $tickettask->fields['tickets_id'], READ)) {
    throw new AccessDeniedHttpException();
}

// Only for a task whose rule was really triggered by the TicketTask hooks
if (empty($_SESSION['plugin_taskrules']['pending_results'][$tickettasks_id])) {
    throw new AccessDeniedHttpException();
}
unset($_SESSION['plugin_taskrules']['pending_results'][$tickettasks_id]);
unset($_SESSION['plugin_taskrules']['show_updateresults']);

$rule = new Rule();
if (!$rule->getFromDBByCrit(['tasktemplates_id' => $tickettask->fields['tasktemplates_id']])) {
    return;
}

$results = $_SESSION['plugin_taskrules']['results'] ?? [];
if (is_array($results) && count($results) > 0) {
    Log::showResults($rule, $results, $tickettask->fields['tickets_id'], $tickettasks_id);
}
