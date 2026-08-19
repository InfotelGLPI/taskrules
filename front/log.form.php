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

use GlpiPlugin\Taskrules\Log;

// Global, irreversible purge of the whole audit table: require PURGE (not DELETE).
Session::checkRight("plugin_taskrules", PURGE);

if (isset($_POST["purge"])) {
    $log = new Log();
    // The log table is entity-assigned (entities_id/is_recursive), and Search::show()
    // restricts reads to the reader's visible entities. Mirror that isolation on the
    // destructive path: a PURGE holder limited to a child entity must not wipe the whole
    // audit trail across every entity. Super-admins (glpishowallentities) still get a
    // WHERE-true criteria and purge everything they can already see.
    $log->deleteByCriteria(getEntitiesRestrictCriteria(Log::getTable(), '', '', true));
    Html::back();
}
