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

// Rule form of a task template (templates/rule_form.html.twig): on/off switches, condition
// searches (add a criterion or a group, remove a row) and the accordion. The form is loaded in
// a tab, over AJAX, after this script: listeners are delegated to the document and the
// accordion is started on each AJAX completion, once per form.
(function () {
    // On/off switch (templates/rule/switch.html.twig)
    $(document).on('click', '[data-taskrules-switch]', function (event) {
        event.preventDefault();
        const input = document.getElementById(this.dataset.taskrulesSwitch);
        const icon = this.querySelector('i');
        if (input === null || icon === null) {
            return;
        }
        const on = input.value !== '1';
        input.value = on ? '1' : '0';
        icon.classList.toggle('fa-toggle-on', on);
        icon.classList.toggle('enabled', on);
        icon.classList.toggle('fa-toggle-off', !on);
        icon.classList.toggle('disabled', !on);
    });

    // Add a criterion or a group of criteria (templates/generic_search.html.twig). Each search
    // block counts its own rows, so the index of a new one never collides with the existing ones.
    $(document).on('click', '[data-taskrules-search] [data-taskrules-add]', function (event) {
        event.preventDefault();
        const block = this.closest('[data-taskrules-search]');
        const config = JSON.parse(block.dataset.taskrulesSearch);
        const action = this.dataset.taskrulesAdd;
        if (block.dataset.taskrulesCount === undefined) {
            block.dataset.taskrulesCount = String(config.count);
        }
        const data = {
            action: action,
            itemtype: config.itemtype,
            num: parseInt(block.dataset.taskrulesCount, 10),
            p: config.p,
            _idor_token: action === 'display_criteria_group' ? config.idor_group : config.idor_criteria,
        };
        if (action === 'display_criteria_group') {
            data.meta = true;
        }
        $.post(config.url, data).done(function (html) {
            // Only the direct "more" anchor of this block: a nested group has its own
            const more = block.querySelector(':scope > .criteria-list > [data-taskrules-more]');
            $(html).insertBefore(more);
            block.dataset.taskrulesCount = String(parseInt(block.dataset.taskrulesCount, 10) + 1);
        });
    });

    // Remove a criterion or a group
    $(document).on('click', '[data-taskrules-search] .remove-search-criteria', function () {
        const tooltip = bootstrap.Tooltip.getInstance(this);
        if (tooltip !== null) {
            tooltip.dispose();
        }
        const block = this.closest('[data-taskrules-search]');
        $('#' + this.dataset.rowid).remove();
        $(block).find('.criteria-list .list-group-item:first-child').addClass('headerRow').show();
    });

    function startAccordions() {
        document.querySelectorAll('#accordion_rules:not([data-taskrules-started])').forEach(function (accordion) {
            accordion.dataset.taskrulesStarted = '1';
            if (typeof accordion_taskrules === 'function') {
                accordion_taskrules(accordion.id);
            }
        });
    }

    $(startAccordions);
    $(document).on('ajaxComplete', startAccordions);
})();
