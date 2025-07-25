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

include('../../../inc/includes.php');

Session::checkRight("plugin_taskrules", READ);

if (Plugin::isPluginActive("taskrules")) {
    Html::header(_n('Task rule', 'Task rules', 2, 'taskrules'), $_SERVER['PHP_SELF'], 'config', 'taskrules');

    echo "<table class='tab_cadre_fixe'>";
    echo "<tr class='tab_bg_2'>";
    echo "<th colspan='2'>";
    echo _n('Task rule', 'Task rules', 2, 'taskrules');
    echo "</th>";
    echo "</tr>";
    echo "<tr class='tab_bg_2'>";
    echo "<td>";
    echo "<a href='" . PLUGIN_TASKRULES_WEBDIR . "/front/log.php'>" . __('See logs', 'taskrules') . "</a>";
    echo "</td>";
    echo "<td>";
    echo __('Drop logs', 'taskrules');
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    Html::footer();
}
