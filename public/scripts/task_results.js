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

// Ticket timeline (templates/results_modal.html.twig, templates/task_locked.html.twig):
// - the "done" checkbox of a task whose state is managed by a rule is locked;
// - the state of the tasks a rule may change is synchronised (checkbox, label, progress);
// - the results of the rule of a task just saved, rendered with the page, are shown in the
//   single results modal (no request).
// The timeline is loaded over AJAX: everything runs again on each AJAX completion, once per
// marker.
(function () {
    function lockTasks() {
        document.querySelectorAll('[data-taskrules-locked-task]:not([data-taskrules-done])').forEach((marker) => {
            marker.dataset.taskrulesDone = '1';
            const task = document.getElementById('TicketTask_' + marker.dataset.taskrulesLockedTask);
            const checkbox = task !== null ? task.querySelector('.todo-list-state .task-state-checkbox') : null;
            if (checkbox !== null) {
                checkbox.disabled = true;
                checkbox.removeAttribute('onclick');
                checkbox.title = marker.dataset.title;
            }
        });
    }

    function syncStates(marker) {
        let states = {};
        try {
            states = JSON.parse(marker.dataset.taskStates || '{}');
        } catch (e) {
            return;
        }
        Object.keys(states).forEach((task_id) => {
            const task = document.getElementById('TicketTask_' + task_id);
            if (task === null) {
                return;
            }
            const is_done = states[task_id] === true;
            const label = is_done ? marker.dataset.doneLabel : marker.dataset.todoLabel;
            const checkbox = task.querySelector('.todo-list-state .task-state-checkbox');
            if (checkbox !== null) {
                checkbox.checked = is_done;
                checkbox.title = label;
                checkbox.setAttribute('aria-label', label);
            }
            const read_only = task.querySelector('.read-only-content');
            if (read_only !== null) {
                read_only.classList.toggle('done', is_done);
            }
        });

        const total = document.querySelectorAll('.todo-list-state .task-state-checkbox').length;
        const done = document.querySelectorAll('.todo-list-state .task-state-checkbox:checked').length;
        const pct = total > 0 ? Math.floor(100 * done / total) : 0;
        const bar = document.querySelector('.timeline-progress');
        if (bar !== null) {
            bar.style.width = pct + '%';
            bar.setAttribute('aria-valuenow', pct);
        }
        const progress = document.querySelector('.task-progress-label');
        if (progress !== null) {
            progress.textContent = pct + '%';
        }
    }

    // The results are rendered with the page (templates/results_modal.html.twig): no request
    function showResults(marker) {
        const results = marker.querySelector(':scope > template');
        const modal = document.getElementById('taskrules_results');
        if (results === null || modal === null) {
            return;
        }
        const body = modal.querySelector('.modal-body');
        body.replaceChildren(results.content.cloneNode(true));
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    function init() {
        lockTasks();
        document.querySelectorAll('[data-taskrules-results]:not([data-taskrules-done])').forEach((marker) => {
            marker.dataset.taskrulesDone = '1';
            syncStates(marker);
            showResults(marker);
        });
    }

    $(init);
    $(document).on('ajaxComplete', init);
})();
