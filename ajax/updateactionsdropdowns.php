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

$AJAX_INCLUDE = 1;
include("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

//Session::checkRight("plugin_taskrules_taskrules", READ);

if (isset($_POST['location_action']) && $_POST['location_action'] == 3) {
    Location::Dropdown([
        'name' => 'locations_id',
        'value' => $_POST['locations_id'],
        'entity' => $_SESSION["glpiactive_entity"]
    ]);
}

if (isset($_POST['state_action']) && $_POST['state_action'] == 2) {
    $condition = new PluginTaskrulesCondition();
    $states_id = $condition->getStatuses(PluginTaskrulesCondition::getTypes());
    Dropdown::showFromArray('states_id', $states_id, [
        'value' => $_POST['states_id'] ?? 0,
    ]);
}
