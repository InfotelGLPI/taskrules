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

use GlpiPlugin\Taskrules\Log;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_taskrules", READ);

$tickets_id = (int)($_POST['tickets_id'] ?? 0);
$tickettasks_id = (int)($_POST['tickettasks_id'] ?? 0);

if ($tickets_id > 0 && $tickettasks_id > 0) {
    $log = new Log();
    $logs_found = $log->find([
        'tickets_id' => $tickets_id,
        'tickettasks_id' => $tickettasks_id,
    ]);

    if (count($logs_found) > 0) {
        foreach ($logs_found as $log_found) {
            Toolbox::logDebug($log_found);
        }
    }

    if (Session::haveRight("plugin_taskrules", PURGE)) {
        $log->deleteByCriteria([
            'tickets_id' => $tickets_id,
            'tickettasks_id' => $tickettasks_id,
        ]);
    }
}
