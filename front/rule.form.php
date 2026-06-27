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

use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Log;
use GlpiPlugin\Taskrules\Rule;

global $DB;

Session::checkRight("plugin_taskrules", READ);

$rule = new Rule();
$cond = new Condition();

if (isset($_POST["add"])) {
    Session::checkRight("plugin_taskrules", CREATE);

    $types = Condition::$types;
    foreach ($types as $itemtype) {
        $fieldname = Condition::getFieldNameForItemtype($itemtype);
        $criterianame = Condition::getCriteriaNameForItemtype($itemtype);
        if (isset($_POST[$criterianame])) {
            $input[$fieldname] = json_encode($_POST[$criterianame]);
        } else {
            $input[$fieldname] = json_encode([]);
        }

        unset($_POST[$criterianame]);
    }

    $id = $rule->add($_POST);
    $input['taskrules_id'] = $id;

    $cond->add($input);

    Html::back();
} elseif (isset($_POST["update"])) {
    $rule->check((int) ($_POST['id'] ?? 0), UPDATE);

    $types = Condition::$types;
    foreach ($types as $itemtype) {
        $fieldname = Condition::getFieldNameForItemtype($itemtype);
        $criterianame = Condition::getCriteriaNameForItemtype($itemtype);
        if (isset($_POST[$criterianame])) {
            $input[$fieldname] = json_encode($_POST[$criterianame]);
        } else {
            $input[$fieldname] = json_encode([]);
        }
        unset($_POST[$criterianame]);
    }

    $input['taskrules_id'] = $_POST['id'];

    if (!$cond->getFromDBByCrit(['taskrules_id' => $input['taskrules_id']])) {
        $cond->add($input);
    } else {
        $input['id'] = $cond->fields['id'];
        $cond->update($input);
    }

    $rule->update($_POST);
    Html::back();
} else {
    Html::header(_n('Task rule', 'Task rules',2, 'taskrules'), $_SERVER['PHP_SELF'], 'config', Rule::class );

    $rule->showForm();

    Html::footer();
}
