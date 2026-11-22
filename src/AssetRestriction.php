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

namespace GlpiPlugin\Taskrules;

use CommonDBTM;
use Config;
use Session;

/**
 * Assets the plugin is the only way to update, for some profiles.
 *
 * The profiles running the task rules need the UPDATE right on the assets the rules modify
 * (Action::launchActions() checks it). For the profiles listed in the configuration, that right
 * is withdrawn everywhere else through the ITEM_CAN hook (CommonDBTM::can()): the asset form
 * becomes read only and the massive actions, the API and the sub items of the asset are refused.
 * The updates the plugin makes itself run inside runAsTaskrules(), which lifts the restriction.
 */
class AssetRestriction
{
    public const CONFIG_CONTEXT = 'plugin:taskrules';

    public const CONFIG_KEY = 'restricted_profiles';

    /** Set while the plugin updates an asset itself (runAsTaskrules()) */
    private static bool $taskrules_update = false;

    /** Restricted profiles, read once per request: can() runs many times per page */
    private static ?array $restricted_profiles = null;

    /**
     * Profiles the restriction applies to
     *
     * @return int[]
     */
    public static function getRestrictedProfiles(): array
    {
        if (self::$restricted_profiles === null) {
            $values  = Config::getConfigurationValues(self::CONFIG_CONTEXT, [self::CONFIG_KEY]);
            $decoded = json_decode($values[self::CONFIG_KEY] ?? '[]', true);

            self::$restricted_profiles = is_array($decoded) ? array_values(array_map('intval', $decoded)) : [];
        }

        return self::$restricted_profiles;
    }

    /**
     * @param array $profiles_id profiles the restriction applies to
     */
    public static function setRestrictedProfiles(array $profiles_id): void
    {
        $profiles_id = array_values(array_unique(array_filter(array_map('intval', $profiles_id))));

        Config::setConfigurationValues(self::CONFIG_CONTEXT, [
            self::CONFIG_KEY => json_encode($profiles_id),
        ]);
        self::$restricted_profiles = $profiles_id;
    }

    public static function uninstall(): void
    {
        Config::deleteConfigurationValues(self::CONFIG_CONTEXT, [self::CONFIG_KEY]);
    }

    /**
     * Run updates on behalf of the plugin: the restriction does not apply to them
     *
     * @template T
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function runAsTaskrules(callable $callback): mixed
    {
        $previous               = self::$taskrules_update;
        self::$taskrules_update = true;
        try {
            return $callback();
        } finally {
            self::$taskrules_update = $previous;
        }
    }

    /**
     * ITEM_CAN hook of the assets the task rules modify (Condition::$types): withdraw the UPDATE
     * right from the restricted profiles, outside of the plugin's own updates.
     *
     * CommonDBTM::can() stores the requested right in $item->right before the hook and refuses
     * the access when the hook changed it.
     */
    public static function itemCan(CommonDBTM $item): void
    {
        if (($item->right ?? null) !== UPDATE
            || self::$taskrules_update
            || !in_array($item::class, Condition::$types, true)
            || !self::isCurrentProfileRestricted()) {
            return;
        }

        $item->right = 0;
    }

    /**
     * Whether the user session runs under a restricted profile. Automatic actions and
     * inventory have no profile of their own and are never restricted.
     */
    public static function isCurrentProfileRestricted(): bool
    {
        if (Session::isCron() || Session::isInventory()) {
            return false;
        }

        $profiles_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);

        return $profiles_id > 0 && in_array($profiles_id, self::getRestrictedProfiles(), true);
    }
}
