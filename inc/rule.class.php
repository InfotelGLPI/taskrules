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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginTaskrulesRule
 */
class PluginTaskrulesRule extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';

    public static function getTypeName($nb = 0)
    {
        return _n('Task rule', 'Task rules', $nb, 'taskrules');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case 'TaskTemplate':
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        self::getTypeName(),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["tasktemplates_id" => $item->getID()]
                        )
                    );
                }
                return self::getTypeName();
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() != 'TaskTemplate') {
            return;
        }

        $rule = new self();
        $rule->showForm($item->getID());

        return true;
    }

    public function showForm($tasktemplates_id, array $options = [])
    {
        echo Html::css(PLUGIN_TASKRULES_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.css");
        echo Html::script(PLUGIN_TASKRULES_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.js");

        $ID = 0;
        if ($this->getFromDBByCrit(['tasktemplates_id' => $tasktemplates_id])) {
            $ID = $this->fields['id'];
            $cond = new PluginTaskrulesCondition();
            if ($conditions = $cond->find(['taskrules_id' => $ID])) {
                if (count($conditions) == 0) {
                    $cond->getEmpty();
                }
            }
        } else {
            $this->getEmpty();
            $cond = new PluginTaskrulesCondition();
            $cond->getEmpty();
        }

        echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr class='tab_bg_2'>\n";
        echo "<td class='top'>\n";
        echo Html::hidden('tasktemplates_id', ['value' => $tasktemplates_id]);
        echo Html::hidden('id', ['value' => $ID]);
        echo Html::hidden('groupIndex', ['value' => 0]);
        echo Html::hidden('boolUpdStatuses', ['value' => 0]);


        echo "<div id='accordion_rules'>";

        /* ---------------------- */
        /* "Parameters" section */
        /* ---------------------- */
        echo "<h3><a href='#'>" . _n("Parameter", "Parameters", 2, "taskrules") . "</a></h3>";
        $this->showParametersForm();

        /* ---------------------- */
        /* "Condition(s)" section */
        /* ---------------------- */
        echo "<h3><a href='#'>" . _n("Condition", "Conditions", 2, "taskrules") . "</a></h3>";
        $cond->showCriteriasForm($ID);

        /* ----------------- */
        /* "Trigger" section */
        /* ----------------- */
        echo "<h3><a href='#'>" . _n("Trigger", "Triggers", 2, "taskrules") . "</a></h3>";
        $this->showTriggersForm();

        /* ------------------- */
        /* "Action(s)" section */
        /* ------------------- */
        echo "<h3><a href='#'>" . _n("Action", "Actions", 2, "taskrules") . "</a></h3>";
        $this->showActionsForm();

        echo "</div>";
        echo "<script>accordion_taskrules();</script>";

        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
        if ($ID > 0) {
            echo Html::submit(_sx('button', 'Update'), ['name' => 'update', 'class' => 'btn btn-primary']);
        } else {
            echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
        }

        echo "</td></tr>";

        echo "</table>\n";
        Html::closeForm();
        echo "</div>\n";

        Html::closeForm();

        return true;
    }

    public function showParametersForm()
    {
        echo "<div>";
        echo "<table class='' width='100%'>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Active", "taskrules");
        echo "</td>";
        echo "<td>";
        self::showSwitchField('active', $this->fields['active']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Prevent task creation on error", "taskrules");
        echo "</td>";
        echo "<td>";
        self::showSwitchField('preventcreation', $this->fields['preventcreation']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __(
            "Prevent the task from changing its status (from \"to do\" to \"done\") in case of an error",
            "taskrules"
        );
        echo "</td>";
        echo "<td>";
        self::showSwitchField('preventchange', $this->fields['preventchange']);
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
    }


    public function showTriggersForm()
    {
        echo "<div>";
        echo "<table class='' width='100%'>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Activate when a task is added to the ticket", "taskrules");
        echo "</td>";
        echo "<td>";
        self::showSwitchField('trigger_creation', $this->fields['trigger_creation']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Trigger upon updating the task status from "to do" to "done"', "taskrules");
        echo "</td>";
        echo "<td>";
        self::showSwitchField('trigger_update', $this->fields['trigger_update']);
        echo "</td>";
        echo "</tr>";


        echo "</table>";
        echo "</div>";
    }

    public function showActionsForm()
    {
        echo "<div>";
        echo "<table class='' width='100%'>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Status');
        echo "</td>";
        echo "<td>";
        $states[0] = __('Do not change', 'taskrules');
        $states[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $states[2] = __('Affect a specific state on the linked item of ticket', 'taskrules');
        $randstate = Dropdown::showFromArray('state_action', $states, [
            'value' => $this->fields['state_action'] ?? 0,
        ]);

        $params = [
            'state_action' => '__VALUE__',
            'states_id' => $this->fields['states_id'],
        ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_state_action$randstate",
            "specific_states_id",
            PLUGIN_TASKRULES_WEBDIR . "/ajax/updateactionsdropdowns.php",
            $params
        );

        echo "&nbsp;<span id='specific_states_id'>";
        if ($this->fields['states_id'] > 0) {
            $condition = new PluginTaskrulesCondition();
            $states_id = $condition->getStatuses(PluginTaskrulesCondition::getTypes());
            Dropdown::showFromArray('states_id', $states_id, [
                'value' => $this->fields['states_id'] ?? 0,
            ]);
        }
        echo "</span>";
        echo "<td>";
        echo "<div class='left'>" . __(
            'Please note that only statuses common to all objects appear.',
            "taskrules"
        ) . "</div>";

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Location');
        echo "</td>";
        echo "<td>";
        $locations[0] = __('Do not change', 'taskrules');
        $locations[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $locations[2] = __('Affect location of ticket requester on the linked item of ticket', 'taskrules');
        $locations[3] = __('Affect a specific location on the linked item of ticket', 'taskrules');
        $randlocation = Dropdown::showFromArray('location_action', $locations, [
            'value' => $this->fields['location_action'],
        ]);

        $params = [
            'location_action' => '__VALUE__',
            'locations_id' => $this->fields['locations_id'],
        ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_location_action$randlocation",
            "specific_locations_id",
            PLUGIN_TASKRULES_WEBDIR . "/ajax/updateactionsdropdowns.php",
            $params
        );

        echo "&nbsp;<span id='specific_locations_id'>";
        if ($this->fields['locations_id'] > 0
            && $this->fields['location_action'] == 3) {
            Location::Dropdown([
                'name' => 'locations_id',
                'value' => $this->fields['locations_id'],
                'entity' => $_SESSION["glpiactive_entity"],
            ]);
        }

        echo "</span>";
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Alternate username');
        echo "</td>";
        echo "<td>";
        $contacts[0] = __('Do not change', 'taskrules');
        $contacts[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        Dropdown::showFromArray('contact_action', $contacts, [
            'value' => $this->fields['contact_action'],
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('User');
        echo "</td>";
        echo "<td>";
        $users[0] = __('Do not change', 'taskrules');
        $users[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $users[2] = __('Affect ticket requester on the linked item of ticket', 'taskrules');
        Dropdown::showFromArray('user_action', $users, [
            'value' => $this->fields['user_action'],
        ]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Group');
        echo "</td>";
        echo "<td>";
        $groups[0] = __('Do not change', 'taskrules');
        $groups[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $groups[2] = __('Affect group of the ticket requester on the linked item of ticket', 'taskrules');
        Dropdown::showFromArray('group_action', $groups, [
            'value' => $this->fields['group_action'],
        ]);
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
    }

    public static function showSwitchField($name, $value)
    {
        $rand = mt_rand();
        $id = $name . $rand;
        $js = "(function(){
                    var toggleButton = $('.$id');
                    toggleButton.on('click', function() {
                        var el = $(this);
                        var isOn = el.hasClass('fa-toggle-on');
                        var input = document.getElementById('$id');
                
                        if (isOn) {
                            el.removeClass('fa-toggle-on enabled')
                              .addClass('fa-toggle-off disabled');
                            input.value = '0';
                        } else {
                            el.removeClass('fa-toggle-off disabled')
                              .addClass('fa-toggle-on enabled');
                            input.value = '1';
                        }
                    });
                })();";

        echo Html::scriptBlock($js);

        echo Html::hidden($name, [
            'id' => $id,
            'value' => ($value == 1 ? '1' : '0'),
        ]);
        // Génère l’icône dans un bouton
        $classes = $value == 1
            ? "$id fa-fw fas fa-2x fa-toggle-on enabled"
            : "$id fa-fw fas fa-2x fa-toggle-off disabled";

        echo "<a class=\"button\"><i class=\"$classes\"></i></a>";
    }

    public static function preAddTask($item)
    {
        $ko = 0;
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->input['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->input['tasktemplates_id']])) {
                    if ($rule->fields['preventcreation'] == 1) {
                        $item_ticket = new Item_ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->input['tickets_id'],
                        ]);
                        if (count($items_linked) > 0) {
                            $found_items = PluginTaskrulesCondition::checkCriteras("pre add task", $rule, $item->input['tickets_id'], $item->getID(), $items_linked);
                        }
                        if (count($found_items) > 0) {
                            foreach ($items_linked as $item_linked) {
                                if (getItemForItemtype($item_linked['itemtype'])) {
                                    $item = new $item_linked['itemtype']();
                                    if ($item->getfromDB($item_linked['items_id'])) {
                                        if (in_array($item_linked['items_id'], $found_items)) {
                                            $ko = 0;
                                        } else {
                                            $ko = 1;
                                        }
                                    }
                                }
                            }
                        } else {
                            $ko = 1;
                        }
                    }
                }
            }
            if ($ko == 1) {
                $errormsg = __('Items not match with rule, task not created', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
                $item->input = [];
                return false;
            }
        }
    }

    public static function afterAddTask($item)
    {
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])) {
                    if ($rule->fields['trigger_creation'] == 1) {
                        $item_ticket = new Item_ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->fields['tickets_id'],
                        ]);
                        $found_items = [];
                        $results = [];
                        if (count($items_linked) > 0) {
                            $found_items = PluginTaskrulesCondition::checkCriteras("add task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);

                            if (count($found_items) > 0) {
                                foreach ($items_linked as $item_linked) {
                                    if (getItemForItemtype($item_linked['itemtype'])) {
                                        $itemTicket = new $item_linked['itemtype']();
                                        if ($itemTicket->getfromDB($item_linked['items_id'])) {
                                            if (in_array($item_linked['items_id'], $found_items)) {
                                                $results[] = PluginTaskrulesAction::launchActions(
                                                    $rule,
                                                    $item->fields['tickets_id'],
                                                    $item->fields['id'],
                                                    $item_linked
                                                );
                                            } else {
                                                $results['errors']['not_found'][] = ['itemtype' => $item_linked['itemtype'], 'items_id' => $item_linked['items_id']];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (count($results) > 0) {
                            $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] = $item->fields['id'];
                            //                            PluginTaskrulesLog::showResults($rule, $results, $item->fields['tickets_id'], $item->fields['id']);
                        }
                    }
                }
            }
        }
    }

    public static function preUpdateTask($item)
    {
        $ko = 0;
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])) {
                    if ($rule->fields['preventchange'] == 1) {
                        $item_ticket = new Item_ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->input['tickets_id'],
                        ]);
                        if (count($items_linked) > 0) {
                            $found_items = PluginTaskrulesCondition::checkCriteras("pre update task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);
                        }
                        if (count($found_items) > 0) {
                            foreach ($items_linked as $item_linked) {
                                if (getItemForItemtype($item_linked['itemtype'])) {
                                    $item = new $item_linked['itemtype']();
                                    if ($item->getfromDB($item_linked['items_id'])) {
                                        if (in_array($item_linked['items_id'], $found_items)) {
                                            $ko = 0;
                                        } else {
                                            $ko = 1;
                                        }
                                    }
                                }
                            }
                        } else {
                            $ko = 1;
                        }
                    }
                }
            }
            if ($ko == 1) {
                $errormsg = __('Items not match with rule, task not updated', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
                $item->fields = [];
                return false;
            }
        }
    }

    public static function afterUpdateTask($item)
    {
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'])) {
                if ($item->fields['state'] == Planning::DONE) {
                    $rule = new self();
                    if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])) {
                        if ($rule->fields['trigger_update'] == 1) {
                            $item_ticket = new Item_ticket();
                            $items_linked = $item_ticket->find([
                                'tickets_id' => $item->fields['tickets_id'],
                            ]);
                            $found_items = [];
                            $results = [];
                            if (count($items_linked) > 0) {
                                $found_items = PluginTaskrulesCondition::checkCriteras("update task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);
                            }
                            if (count($found_items) > 0) {
                                foreach ($items_linked as $item_linked) {
                                    if (getItemForItemtype($item_linked['itemtype'])) {
                                        $itemTicket = new $item_linked['itemtype']();
                                        if ($itemTicket->getfromDB($item_linked['items_id'])) {
                                            if (in_array($item_linked['items_id'], $found_items)) {
                                                $results[] = PluginTaskrulesAction::launchActions(
                                                    $rule,
                                                    $item->fields['tickets_id'],
                                                    $item->fields['id'],
                                                    $item_linked
                                                );
                                            } else {
                                                $results['errors']['not_found'][] = ['itemtype' => $item_linked['itemtype'], 'items_id' => $item_linked['items_id']];
                                            }
                                        }
                                    }
                                }
                            }
                            if (count($results) > 0) {
                                $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] = $item->fields['id'];
                                //                                PluginTaskrulesLog::showResults($rule, $results, $item->fields['tickets_id'], $item->fields['id']);
                            }
                        }
                    }
                }
            }
        }
    }

    public function launchResultsOnTicketTaskState($tickettasks_id)
    {
        $task = new TicketTask();

        if ($task->getFromDB($tickettasks_id)) {
            echo Ajax::createIframeModalWindow(
                "results$tickettasks_id",
                PLUGIN_TASKRULES_WEBDIR . "/front/modalresults.php",
                [
                    'title' => __('Results of changes of linked items', 'taskrules'),
                    'reloadonclose' => false,
                    'display' => false,
                    'width' => 1140,
                    'height' => 500,
                    'dialog_class'  => 'modal-xl',
                ]
            );

            $id = (int) $tickettasks_id;
            echo Html::scriptBlock(
                "let taskDiv$id = document.querySelector('#TicketTask_$id .todo-list-state');

                    if (taskDiv$id) {
                        let span = taskDiv$id.querySelector('.state');
                    
                        if (span) {
                            let button = document.createElement('button');
                            button.className = span.className;
                            button.title = span.title;
                            button.style.border = 'none';
                            button.innerHTML = '';
                    
                            let onclickAttr = span.getAttribute('onclick');

                            if (onclickAttr && onclickAttr.match) {
                                let match = onclickAttr.match(/change_task_state\((\d+),\s*this\)/);
                                if (match) {
                                    let taskId = match[1];
                        
                                    $.ajax({
                                        type: 'POST',
                                        url: '" . PLUGIN_TASKRULES_WEBDIR . "/ajax/checkcriterias.php',
                                        dataType: 'html',
                                        data: {
                                            'tickettasks_id': taskId
                                        },
                                        success: function(response){
                                            if (response == 1) {
                                               button.addEventListener('click', function () {
                                                    change_task_state(taskId, button);
                                                    setTimeout(() => {
                                                        if (button.classList.contains('state_1')) {
                                                            plugin_taskrules_showresults(taskId);
                                                        }
                                                    }, 50);
                                                });
                                            } else {
                                               button.addEventListener('click', function () {
                                                  plugin_taskrules_showresults(taskId);
                                               });
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                          console.log(xhr);
                                          console.log(status);
                                          console.log(error);
                                        }
                                    });
                                    span.replaceWith(button);
                                }
                            }
                        }
                    }
                    
                    function plugin_taskrules_showresults(taskId) {

                        $.ajax({
                            type: 'POST',
                            url: '" . PLUGIN_TASKRULES_WEBDIR . "/ajax/generateresult.php',
                            data: {
                                'tickettasks_id': taskId
                            },
                            success: function (html) {
                                let modalBody = document.querySelector('#results' + taskId + ' div.modal-body');
                                if (modalBody) {
                                    modalBody.innerHTML = html;

                                    $('#results' + taskId).modal('show');
                                    $('#ajax_loader').hide();
                                }
                            }
                        });
                    }"
            );
        }
    }
}
