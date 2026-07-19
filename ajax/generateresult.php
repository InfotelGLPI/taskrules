<?php

/*
 -------------------------------------------------------------------------
 Taskrules plugin for GLPI
 Copyright (C) 2025-2026 by the Taskrules Development Team.

 https://github.com/InfotelGLPI/Taskrules
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Taskrules.

 Taskrules is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 Taskrules is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Taskrules. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Taskrules\Action;
use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Log;
use GlpiPlugin\Taskrules\Rule;

// This endpoint mutates the assets linked to the ticket, so it requires the
// plugin UPDATE right (not merely READ).
Session::checkRight("plugin_taskrules", UPDATE);

$tickettasks_id = (int) ($_POST['tickettasks_id'] ?? 0);
if ($tickettasks_id > 0) {
    $tickettask = new TicketTask();
    if ($tickettask->getFromDB($tickettasks_id)) {
        // Enforce access to the parent ticket before acting on its linked assets
        // (defends against IDOR: a user must not drive writes on an arbitrary
        // tickettasks_id belonging to a ticket they cannot access).
        $ticket = new Ticket();
        if (!$ticket->can((int) $tickettask->fields['tickets_id'], READ)) {
            throw new AccessDeniedHttpException();
        }
        unset($_SESSION['plugin_taskrules']['show_updateresults']);
        $rule = new Rule();
        if ($rule->getFromDBByCrit(["tasktemplates_id" => $tickettask->fields['tasktemplates_id']]) && $rule->fields['active'] == 1) {
            $item_ticket = new Item_Ticket();
            $items_linked = $item_ticket->find([
                'tickets_id' => $tickettask->fields['tickets_id'],
            ]);
            $found_items = [];
            $results = [];
            if (count($items_linked) > 0) {
                $found_items = Condition::checkCriteras("launchresults", $rule, $tickettask->fields['tickets_id'], $tickettask->getID(), $items_linked);
                if (count($found_items) > 0) {
                    foreach ($items_linked as $item_linked) {
                        if (getItemForItemtype($item_linked['itemtype'])) {
                            $item = new $item_linked['itemtype']();
                            if ($item->getfromDB($item_linked['items_id'])) {
                                if (in_array($item_linked['items_id'], $found_items)) {
                                    $results[] = Action::launchActions(
                                        $rule,
                                        $tickettask->fields['tickets_id'],
                                        $tickettasks_id,
                                        $item_linked
                                    );
                                } else {
                                    $results['errors']['not_found'][] = ['itemtype' => $item_linked['itemtype'], 'items_id' => $item_linked['items_id']];
                                }
                            }
                        }
                    }
                } else {
                    foreach ($items_linked as $item_linked) {
                        $results['errors']['not_found'][] = [
                            'itemtype' => $item_linked['itemtype'],
                            'items_id' => $item_linked['items_id']
                        ];
                    }
                }
            }
            if (count($results) > 0) {
                Log::showResults($rule, $results, $tickettask->fields['tickets_id'], $tickettasks_id);
            }
//            Html::redirect(Ticket::getFormURLWithID($tickettask->fields['tickets_id']));
        }
    }
} else {
    exit;
}
