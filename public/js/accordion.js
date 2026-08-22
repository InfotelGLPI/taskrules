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

function accordion_taskrules(id = 'accordion_rules', openall = true) {
    jQuery(function () {
        const $accordion = $("#" + id); // $accordion est une variable JS, pas PHP

        $accordion.accordion({
            collapsible: true,
            active: false, // tout fermé à l'init
            heightStyle: "content"
        });

        if (openall) {
            // Ouvre tous les panneaux manuellement
            $accordion.find('.ui-accordion-content').each(function () {
                $(this).show().addClass('ui-accordion-content-active');
                $(this).prev('.ui-accordion-header')
                    .addClass('ui-accordion-header-active ui-state-active ui-corner-top')
                    .removeClass('ui-corner-all');
            });
        }
    });
}
