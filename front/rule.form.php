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
use GlpiPlugin\Taskrules\Rule;

global $DB;

Session::checkRight("plugin_taskrules", READ);

$rule = new Rule();
$cond = new Condition();

if (isset($_POST["add"])) {
    // A rule has no entity scope: once created it is evaluated against the
    // tickets and assets of every entity. Authoring one is therefore a global,
    // cross-entity administrative operation, so require the global `config`
    // UPDATE right in addition to the entity-agnostic plugin CREATE right. This
    // prevents a profile scoped to a child entity from defining rules that reach
    // beyond its perimeter.
    Session::checkRight("plugin_taskrules", CREATE);
    Session::checkRight("config", UPDATE);

    $input = [];
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

    // The conditions belong to the rule: none is stored when the rule was refused
    $id = $rule->add($_POST);
    if ($id) {
        $input['taskrules_id'] = $id;
        $cond->add($input);
    }

    Html::back();
} elseif (isset($_POST["update"])) {
    // Same rationale as the add branch: altering a global-scope rule is a
    // cross-entity administrative operation, so require the global `config`
    // UPDATE right alongside the per-object plugin UPDATE check.
    Session::checkRight("config", UPDATE);
    $rule->check((int) ($_POST['id'] ?? 0), UPDATE);

    $input = [];
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

    $input['taskrules_id'] = (int) $_POST['id'];

    if (!$cond->getFromDBByCrit(['taskrules_id' => $input['taskrules_id']])) {
        $cond->add($input);
    } else {
        $input['id'] = $cond->fields['id'];
        $cond->update($input);
    }

    $rule->update($_POST);
    Html::back();
} else {
    // A rule is edited in the tab of its task template (Rule::showForm($tasktemplates_id)):
    // there is nothing to show on its own, a direct GET used to end on a fatal error
    Html::redirect(TaskTemplate::getSearchURL());
}
