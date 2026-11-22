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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Taskrules\AssetRestriction;
use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Rule;

Session::checkRight("config", UPDATE);

if (Plugin::isPluginActive("taskrules")) {
    if (isset($_POST['update_restrictions'])) {
        // The multiple select is posted with an empty companion value, so that clearing it still
        // sends something: only existing profiles are kept.
        $posted   = is_array($_POST['restricted_profiles'] ?? null) ? $_POST['restricted_profiles'] : [];
        $existing = array_keys((new \Profile())->find(['id' => array_map('intval', $posted) ?: [0]]));
        AssetRestriction::setRestrictedProfiles($existing);
        Html::back();
    }

    Html::header(_n('Task rule', 'Task rules', 2, 'taskrules'), $_SERVER['PHP_SELF'], 'config', Rule::class);

    $profiles = [];
    foreach ((new \Profile())->find([], 'name') as $profile) {
        $profiles[$profile['id']] = $profile['name'];
    }
    $itemtypes = [];
    foreach (Condition::$types as $itemtype) {
        $itemtypes[] = $itemtype::getTypeName(Session::getPluralNumber());
    }

    TemplateRenderer::getInstance()->display('@taskrules/config.html.twig', [
        'log_url'      => PLUGIN_TASKRULES_WEBDIR . '/front/log.php',
        'purge_target' => PLUGIN_TASKRULES_WEBDIR . '/front/log.form.php',
        'can_purge'    => Session::haveRight('plugin_taskrules', PURGE),
        'config_target'       => PLUGIN_TASKRULES_WEBDIR . '/front/config.form.php',
        'profiles'            => $profiles,
        'restricted_profiles' => AssetRestriction::getRestrictedProfiles(),
        'restricted_types'    => $itemtypes,
    ]);

    Html::footer();
}
