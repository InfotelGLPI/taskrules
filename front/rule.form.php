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

$cond = new PluginTaskrulesRule();

if (isset($_POST["add"]) || isset($_POST["save"])) {
    Toolbox::logInfo($_POST);
    Html::back();
} else {
    Html::header(__('Taskrules', 'taskrules'), $_SERVER['PHP_SELF'], 'config', 'taskrules', );

    $cond->showForm();

    Html::footer();
}
