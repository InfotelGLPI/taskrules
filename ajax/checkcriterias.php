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

use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Rule;

Session::checkRight("plugin_taskrules", READ);

$ok = 0;

$tickettasks_id = (int) ($_POST['tickettasks_id'] ?? 0);
if ($tickettasks_id > 0) {
    $tickettask = new TicketTask();
    if ($tickettask->getFromDB($tickettasks_id)) {
        unset($_SESSION['plugin_taskrules']['show_updateresults']);
        $rule = new Rule();
        if ($rule->getFromDBByCrit(["tasktemplates_id" => $tickettask->fields['tasktemplates_id'], "preventchange" => 1]
        ) && $rule->fields['active'] == 1) {
            $rule = new Rule();
            if ($rule->getFromDBByCrit(
                ["tasktemplates_id" => $tickettask->fields['tasktemplates_id'], "trigger_update" => 1]
            )) {
                $item_ticket = new Item_Ticket();
                $items_linked = $item_ticket->find([
                    'tickets_id' => $tickettask->fields['tickets_id'],
                ]);
                $found_items = [];
                $results = [];
                if (count($items_linked) > 0) {
                    $found_items = Condition::checkCriteras(
                        "updatestate",
                        $rule,
                        $tickettask->fields['tickets_id'],
                        $tickettask->getID(),
                        $items_linked
                    );
                    if (count($found_items) > 0) {
                        foreach ($items_linked as $item_linked) {
                            if (getItemForItemtype($item_linked['itemtype'])) {
                                $item = new $item_linked['itemtype']();
                                if ($item->getfromDB($item_linked['items_id'])) {
                                    if (in_array($item_linked['items_id'], $found_items)) {
                                        $ok = 1;
                                    } else {
                                        $ok = 0;
                                    }
                                }
                            }
                        }
                    } else {
                        $ok = 0;
                    }
                }
            }
        } else {
            $ok = 1;
        }
    }
}

echo $ok;
