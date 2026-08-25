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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Taskrules\Log;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_taskrules", READ);

$tickets_id = (int) ($_POST['tickets_id'] ?? 0);
$tickettasks_id = (int) ($_POST['tickettasks_id'] ?? 0);

if ($tickets_id > 0 && $tickettasks_id > 0) {
    // Enforce access to the parent ticket before touching its audit logs.
    // PURGE is a global right (not entity-scoped), so without this check any
    // holder could delete another entity's log entries via a forged POST (IDOR).
    $tickettask = new TicketTask();
    if (
        !$tickettask->getFromDB($tickettasks_id)
        || (int) $tickettask->fields['tickets_id'] !== $tickets_id
    ) {
        throw new AccessDeniedHttpException();
    }
    $ticket = new Ticket();
    if (!$ticket->can($tickets_id, READ)) {
        throw new AccessDeniedHttpException();
    }

    $log = new Log();
    $logs_found = $log->find([
        'tickets_id' => $tickets_id,
        'tickettasks_id' => $tickettasks_id,
    ]);

    if (Session::haveRight("plugin_taskrules", PURGE)) {
        $log->deleteByCriteria([
            'tickets_id' => $tickets_id,
            'tickettasks_id' => $tickettasks_id,
        ]);
    }
}
