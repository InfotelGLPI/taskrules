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
use GlpiPlugin\Taskrules\Rule;

$AJAX_INCLUDE = 1;
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_taskrules", UPDATE);

if (isset($_POST['location_action']) && $_POST['location_action'] == 3) {
    Location::Dropdown([
        'name' => 'locations_id',
        'value' => (int) ($_POST['locations_id'] ?? 0),
        'entity' => $_SESSION["glpiactive_entity"],
    ]);
}

$types = Condition::getTypes();

$rule = new Rule();
$rule_id = (int) ($_POST['id'] ?? 0);
if ($rule_id > 0 && $rule->getFromDB($rule_id)) {
    $used_itemtypes = json_decode($rule->fields['used_itemtypes']);
    if (is_array($used_itemtypes) && count($used_itemtypes) > 0) {
        $types = $used_itemtypes;
    }
}

if (isset($_POST['state_action']) && $_POST['state_action'] == 2) {
    $condition = new Condition();
    $states_id = $condition->getStatuses($types);
    Dropdown::showFromArray('states_id', $states_id, [
        'value' => (int) ($_POST['states_id'] ?? 0),
    ]);
}
