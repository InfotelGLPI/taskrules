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


include("../../../inc/includes.php");

global $DB;

$rule = new PluginTaskrulesRule();
$cond = new PluginTaskrulesCondition();

if (isset($_POST["add"])) {

    $input['filter'] = $_POST["filter"];
    unset($_POST['filter']);
    $id = $rule->add($_POST);
    $input['taskrules_id'] = $id;

    if (is_array($input['filter']) && count($input['filter']) > 0) {
        foreach ($input['filter'] as $id => $name) {
            $input['operator'] = $input['filter'][$id]['operator'];
            $input['itemtype'] = $input['filter'][$id]['itemtype'];
            if ($input['filter'][$id]['criteria'] == "Status") {
                if ($input['filter'][$id]['last'] > 0) {
                    $input['states_id'] = $input['filter'][$id]['last'];
                }
            }
            if ($input['filter'][$id]['criteria'] == "Type") {
                if ($input['filter'][$id]['last'] > 0) {
                    $input['objecttypes_id'] = $input['filter'][$id]['last'];
                }
            }
            $cond->add($input);
        }
    }


    Html::back();
} elseif (isset($_POST["update"])) {

    $input['filter'] = $_POST["filter"];
    if (is_array($input['filter']) && count($input['filter']) > 0) {
        foreach ($input['filter'] as $id => $name) {
            $input['operator'] = $input['filter'][$id]['operator'];
            $input['itemtype'] = $input['filter'][$id]['itemtype'];
            if ($input['filter'][$id]['criteria'] == "Status") {
                if ($input['filter'][$id]['last'] > 0) {
                    $input['states_id'] = $input['filter'][$id]['last'];
                }
            }
            if ($input['filter'][$id]['criteria'] == "Type") {
                if ($input['filter'][$id]['last'] > 0) {
                    $input['objecttypes_id'] = $input['filter'][$id]['last'];
                }
            }
            $input['taskrules_id'] = $_POST['id'];

            if (!$cond->getFromDBByCrit(['taskrules_id' => $input['taskrules_id'],
                'itemtype' => $input['itemtype'],
                'objecttypes_id' => $input['objecttypes_id'] ?? 0,
                'states_id' => $input['states_id'] ?? 0,])) {
                $cond->add($input);
            } else {
                $input['id'] = $cond->fields['id'];
                $cond->update($input);
            }
        }
    }

    unset($_POST['filter']);
    $rule->update($_POST);
    Html::back();
} else {
    Html::header(__('Taskrules', 'taskrules'), $_SERVER['PHP_SELF'], 'config', 'taskrules', );

    $rule->showForm();

    Html::footer();
}
