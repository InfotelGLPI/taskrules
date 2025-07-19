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
        echo "<h3><a href='#'>" . _n("Trigger", "Triggers", 1, "taskrules") . "</a></h3>";
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
        echo __("Prevent the task from changing its status (from \"to do\" to \"done\") in case of an error", "taskrules");
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

        echo '<div class="center">';
        echo "<div>" . __('Please note, only the statuses common to the objects selected in the conditions appear', "taskrules") . "</div>";

        $actionsStatuses = [
            Dropdown::EMPTY_VALUE => Dropdown::EMPTY_VALUE,
            'DoNotChange' => __("Keep current status", "taskrules"),
            'Empty' => __("Empty current status", "taskrules"),
        ];

        echo '<div class="col-auto">';
        // /* Action statuses dropdown */
        $cr = Dropdown::showFromArray("actionStatus", $actionsStatuses, [
            'on_change' => "handleClick()",
        ]);
        $selectId = "dropdown_actionStatus$cr";
        echo Html::scriptBlock("
        var actionStatus = $selectId;
        function handleClick() {
            var bStatus = document.getElementsByName('boolUpdStatuses')[0];
//            console.log('bStatus.value=' + bStatus.value);
            if ( bStatus.value == 1 )
                return;
            const selectElement = document.getElementById('$selectId');
            const selectedValue = selectElement.value;
//            console.log('selectedValue=' + selectedValue);
            /* Get the comma separated list of itemtypes in the Condition(s) section */
            let values = '';
            document.querySelectorAll('select[name*=\"itemtype\"]').forEach(function(select) {
                values += select.value + ',';
            });
            values = values.slice(0, -1);
            /* Ajax call to populate statuses belonging to itemtypes in the Condition(s) section */
            $.ajax({
                url: '" . plugin::getWebDir('taskrules') . "/ajax/getactionstatuses.php',
                type: 'POST',
                data: { item_types: values },
                success: function(response) {
                    $('#" . $selectId . "').html(response);
                },
                error: function (xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
                }
            });
            bStatus.value = 1;
        }
        ");

        echo "</div>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Status');
        echo "</td>";
        echo "<td>";
        $states[0] = __('Do not change', 'tasrules');
        $states[1] = __('Empty the field', 'taskrules');
        $states[2] = __('A specific state', 'taskrules');
        $randstate = Dropdown::showFromArray('state_action', $states, [
            'value' => $this->fields['state_action'] ?? 0,
        ]);

        $params = ['state_action' => '__VALUE__',
            'states_id' => $this->fields['states_id'] ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_state_action$randstate",
            "specific_states_id",
            PLUGIN_TASKRULES_WEBDIR . "/ajax/updateactionsdropdowns.php",
            $params
        );


        echo "&nbsp;<span id='specific_states_id'>";
        //        if ($this->fields['states_id'] > 0) {
        //            $condition = new PluginTaskrulesCondition();
        //            $states_id = $condition->getStatuses(PluginTaskrulesCondition::getTypes());
        //            Dropdown::showFromArray('states_id', $states_id, [
        //                'value' => $this->fields['states_id'] ?? 0,
        //            ]);
        //        }
        echo "</span>";
        echo "<td>";

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Location');
        echo "</td>";
        echo "<td>";
        $locations[0] = __('Do not change', 'tasrules');
        $locations[1] = __('Location of ticket requester', 'taskrules');
        $locations[2] = __('Empty the field', 'taskrules');
        $locations[3] = __('A specific location', 'taskrules');
        $randlocation = Dropdown::showFromArray('location_action', $locations, [
            'value' => $this->fields['location_action'],
        ]);

        $params = ['location_action' => '__VALUE__',
            'locations_id' => $this->fields['locations_id'] ];
        Ajax::updateItemOnSelectEvent(
            "dropdown_location_action$randlocation",
            "specific_locations_id",
            PLUGIN_TASKRULES_WEBDIR . "/ajax/updateactionsdropdowns.php",
            $params
        );


        echo "&nbsp;<span id='specific_locations_id'>";
        if ($this->fields['locations_id'] > 0) {
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
        $contacts[0] = __('Do not change', 'tasrules');
        $contacts[1] = __('Empty the field', 'taskrules');
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
        $users[0] = __('Do not change', 'tasrules');
        $users[1] = __('Ticket requester', 'taskrules');
        $users[2] = __('Empty the field', 'taskrules');
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
        $groups[0] = __('Do not change', 'tasrules');
        $groups[1] = __('Group of the ticket requester', 'taskrules');
        $groups[2] = __('Empty the field', 'taskrules');
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
        $js = <<<JS
                (function(){
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
                })();
                JS;

        echo Html::scriptBlock($js);

        echo Html::hidden($name, ['id' => $id,
            'value' => ($value == 1 ? '1' : '0')]);
        // Génère l’icône dans un bouton
        $classes = $value == 1
            ? "$id fa-fw fas fa-2x fa-toggle-on enabled"
            : "$id fa-fw fas fa-2x fa-toggle-off disabled";

        echo "<a class=\"button\"><i class=\"$classes\"></i></a>";
    }

    public static function preAddTask($item) {
        if ($item::getType() === TicketTask::getType()) {

            if (isset($item->input['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->input['tasktemplates_id']])) {
                    $item_ticket = new Item_ticket();
                    $items_linked = $item_ticket->find([
                        'tickets_id' => $item->input['tickets_id'],
                    ]);
                    if (count($items_linked) > 0) {
                        foreach ($items_linked as $item_linked) {
                            Toolbox::logInfo($item_linked['itemtype']);
                            Toolbox::logInfo($item_linked['items_id']);
                        }
                    }
                }
            }
//        Session::addMessageAfterRedirect('Condition non remplie, tâche non créée.', false, ERROR);
            return;
        }
    }

    public static function afterAddTask($item) {
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])) {
                    $item_ticket = new Item_ticket();
                    $items_linked = $item_ticket->find([
                        'tickets_id' => $item->fields['tickets_id'],
                    ]);
                    if (count($items_linked) > 0) {
                        foreach ($items_linked as $item_linked) {
                            Toolbox::logInfo($item_linked['itemtype']);
                            Toolbox::logInfo($item_linked['items_id']);
                        }
                    }
                }
            }
        }
    }

    public static function afterUpdateTask($item) {

        if ($item::getType() === TicketTask::getType()) {

            if (isset($item->fields['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])) {
                    $item_ticket = new Item_ticket();
                    $items_linked = $item_ticket->find([
                        'tickets_id' => $item->fields['tickets_id'],
                    ]);
                    if (count($items_linked) > 0) {
                        foreach ($items_linked as $item_linked) {
                            Toolbox::logInfo($item_linked['itemtype']);
                            Toolbox::logInfo($item_linked['items_id']);
                        }
                    }

                    switch ($item->fields['state']) {
                        case Planning::INFO:
                            Toolbox::logInfo("Etat de la tâche : Information\n");
                            break;
                        case Planning::TODO:
                            Toolbox::logInfo("Etat de la tâche : To do\n");
                            break;
                        case Planning::DONE:
                            Toolbox::logInfo("Etat de la tâche : Done\n");
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }
}
