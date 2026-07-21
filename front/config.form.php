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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Taskrules\Rule;

Session::checkRight("plugin_taskrules", READ);

if (Plugin::isPluginActive("taskrules")) {
    Html::header(_n('Task rule', 'Task rules', 2, 'taskrules'), $_SERVER['PHP_SELF'], 'config', Rule::class);

    TemplateRenderer::getInstance()->display('@taskrules/config.html.twig', [
        'log_url'      => PLUGIN_TASKRULES_WEBDIR . '/front/log.php',
        'purge_target' => PLUGIN_TASKRULES_WEBDIR . '/front/log.form.php',
        'can_purge'    => Session::haveRight('plugin_taskrules', PURGE),
    ]);

    Html::footer();
}
