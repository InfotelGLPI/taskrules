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
 * Class PluginTaskrulesLog
 */
class PluginTaskrulesLog extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';



    public static function showResults($rule, $results, $tickets_id, $tickettasks_id)
    {

        $errors = [];
        $not_found = [];
        if (isset($_SESSION['plugin_taskrules']['results'])) {
            $results = $_SESSION['plugin_taskrules']['results'];
        }

        unset($_SESSION['plugin_taskrules']['results']);
        if (isset($results['errors']['not_found'])
            && count($results['errors']['not_found']) > 0) {
            $not_found = $results['errors']['not_found'];

            if (count($not_found) > 0) {
                echo "<table class='tab_cadre_fixe'>";
                echo "<tr class='tab_bg_2'>";
                echo "<div class='alert alert-danger'>";
                echo __('Items not updated (not match with rule)', 'taskrules');
                echo "</div>";
                //
                //                echo "<td colspan='3' style='background: antiquewhite;'>";
                //                echo __('Items not updated (not match with rule)', 'taskrules');
                //                echo "</td>";
                echo "</tr>";
                foreach ($not_found as $n_found) {
                    echo "<tr class='tab_bg_2'>";
                    echo "<td width='20%'>";
                    echo "<i class='ti ti-circle-x' style='color: red;'></i>";
                    echo "</td>";
                    echo "<td>";
                    $obj = getItemForItemtype($n_found['itemtype']);
                    echo $obj->getTypeName();
                    echo "</td>";
                    echo "<td>";
                    $obj->getFromDB($n_found['items_id']);
                    echo $obj->getLink();
                    echo "</td>";
                    echo "<td>";
                    echo $obj->fields['serial'];
                    echo "</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
            }
        }
        unset($results['errors']);

        if (count($results) > 0) {

            echo "<table class='tab_cadre_fixe'>";
            //            echo "<tr class='tab_bg_2'>";
            echo "<div class='alert alert-success'>";
            echo __('Items updated', 'taskrules');
            echo "</div>";
            //            echo "</tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td>";

            foreach ($results as $k => $itemtype) {
                if (isset($itemtype['errors'])) {
                    if (count($itemtype['errors']) > 0
                        && ($rule->fields['user_action'] == 2
                            || $rule->fields['group_action'] == 2)) {
                        $errors = $itemtype['errors'];
                    }
                }
                $errors = array_unique($errors);
            }

            $log_errors = [];
            $i = 0;
            $j = 0;
            foreach ($results as $k => $itemtype) {
                unset($itemtype['users_id']);
                unset($itemtype['groups_id']);
                unset($itemtype['errors']);
                if (is_array($itemtype)) {
                    if (count($itemtype) > 0) {
                        unset($itemtype['not_found']);
                        echo "<table class='tab_cadre_fixe'>";
                        foreach ($itemtype as $type => $type_results) {
                            $i++;
                            if (is_array($type_results)) {
                                foreach ($type_results as $items_id => $items_id_results) {

                                    echo "<tr class='tab_bg_2'>";
                                    echo "<th width='20%'>";
                                    $obj = getItemForItemtype($type);
                                    echo $obj->getTypeName();
                                    echo "</th>";
                                    echo "<th>";
                                    $obj->getFromDB($items_id);
                                    echo $obj->getLink();
                                    echo "</th>";
                                    echo "<th>";
                                    echo $obj->fields['serial'];
                                    echo "</th>";
                                    echo "</tr>";

                                    foreach ($items_id_results as $action => $res) {
                                        $j++;
                                        echo "<tr class='tab_bg_2'>";
                                        echo "<td>";
                                        echo PluginTaskrulesAction::getActionName($action);
                                        echo "</td>";
                                        echo "<td>";
                                        if ($action == 'state_action') {
                                            echo PluginTaskrulesAction::getStatusActionName($res['action']);
                                        } elseif ($action == 'location_action') {
                                            echo PluginTaskrulesAction::getLocationActionName($res['action']);
                                        } elseif ($action == 'contact_action') {
                                            echo PluginTaskrulesAction::getContactActionName($res['action']);
                                        } elseif ($action == 'user_action') {
                                            echo PluginTaskrulesAction::getUserActionName($res['action']);
                                        } elseif ($action == 'group_action') {
                                            echo PluginTaskrulesAction::getGroupActionName($res['action']);
                                        }
                                        echo "</td>";
                                        echo "<td>";
                                        if (($action == 'location_action'
                                                || $action == 'status_action'
                                                || $action == 'user_action'
                                                || $action == 'group_action')
                                            && $res['action'] == 1 && $res['value'] == 0) {
                                            echo "<i class='ti ti-check' style='color: forestgreen;'></i>";
                                        } elseif ($action == 'contact_action'
                                            && $res['value'] == 'null') {
                                            echo "<i class='ti ti-check' style='color: forestgreen;'></i>";
                                        } elseif ($action == 'state_action'
                                            && $res['action'] == 2) {
                                            echo Dropdown::getDropdownName('glpi_states', $res['value']);
                                        } elseif ($action == 'location_action'
                                            && ($res['action'] == 2 || $res['action'] == 3)) {
                                            echo Dropdown::getDropdownName('glpi_locations', $res['value']);
                                        } elseif ($action == 'user_action'
                                            && $res['action'] == 2) {
                                            $ko = 0;
                                            if ($errors) {
                                                foreach ($errors as $name => $error) {
                                                    if ($name == 'user_action') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>" . $error . "</span><br>";
                                                        $ko = 1;
                                                        $log_errors[$i][$j] = ['itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error];
                                                    }
                                                }
                                            }
                                            if ($ko == 0) {
                                                echo getUserName($res['value']);
                                            }
                                        } elseif ($action == 'group_action'
                                            && $res['action'] == 2) {
                                            $ko = 0;
                                            $kp = 0;
                                            if ($errors) {
                                                foreach ($errors as $name => $error) {
                                                    if ($name == 'group_action') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>" . $error . "</span><br>";
                                                        $kp = 1;
                                                        $log_errors[$i][$j] = ['itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error];
                                                    }
                                                    if ($name == 'user_action') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>" . $error . "</span><br>";
                                                        $ko = 1;
                                                        $log_errors[$i][$j] = ['itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error];
                                                    }
                                                }
                                            }
                                            if ($ko == 0 && $kp == 0) {
                                                echo Dropdown::getDropdownName('glpi_groups', $res['value']);
                                            }

                                        } else {
                                            echo $res['value'];
                                        }

                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                }
                            }
                        }
                        echo "</table>";
                    }
                }
            }
            echo "</td>";
            echo "</tr>";
            echo "</table>";
        }
        unset($_SESSION['plugin_taskrules']['show_updateresults']['results']);

        $log = new PluginTaskrulesLog();

        //        $errors = [];
        //        if (count($results) > 0) {
        //            foreach ($results as $k => $itemtype) {
        //                if (isset($itemtype['errors'])) {
        //                    Toolbox::logInfo($itemtype['errors']);
        //                    if (isset($itemtype['errors']['user'])) {
        //                        $errors['user_action'] = $itemtype['errors']['user'];
        //                        $errors['group_action'] = $itemtype['errors']['user'];
        //                    }
        //                    if (isset($itemtype['errors']['group'])) {
        //                        $errors['group_action'] = $itemtype['errors']['group'];
        //                    }
        //                }
        //            }
        //        }
        //        foreach ($not_found as $action => $res) {
        //            $inputr['action_field'] = $action;
        //            $inputr['tickets_id'] = $tickets_id;
        //            $inputr['tickettasks_id'] = $tickettasks_id;
        //            $inputr['itemtype'] = $res['itemtype'];
        //            $inputr['items_id'] = $res['items_id'];
        //            $inputr['action'] = '0';
        //            $inputr['value'] = 'not found';
        //            $inputr['users_id'] = $_SESSION['glpiID'];
        //            $inputr['date'] = $_SESSION['glpi_currenttime'];
        //            $log->add($inputr);
        //        }

        //        //report Results or errors

        foreach ($log_errors as $k => $logs) {
            foreach ($logs as $k => $res) {
                $inputr['action_field'] = $res['action_field'];
                $inputr['tickets_id'] = $tickets_id;
                $inputr['tickettasks_id'] = $tickettasks_id;
                $inputr['itemtype'] = $res['itemtype'];
                $inputr['items_id'] = $res['items_id'];
                $inputr['action'] = $res['action'];
                $inputr['value'] = $res['value'];
                $inputr['users_id'] = $_SESSION['glpiID'];
                $inputr['date'] = $_SESSION['glpi_currenttime'];
                $log->add($inputr);
            }
        }
        //
        //        $log = new PluginTaskrulesLog();
        //        $logs_found = $log->find([
        //            'tickets_id' => $tickets_id,
        //            'tickettasks_id' => $tickettasks_id,
        //        ]);
        //

        //        $logs_found = $_SESSION['plugin_taskrules']['show_updateresults']['results'];
        //        $log->deleteByCriteria([
        //            'tickets_id' => $tickets_id,
        //            'tickettasks_id' => $tickettasks_id,
        //        ]);
    }

    /**
     * @return array
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id' => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id' => '1',
            'table' => $this->getTable(),
            'field' => 'action_field',
            'name' => __('action_field'),
            //            'datatype' => 'itemlink',
            //            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '2',
            'table' => $this->getTable(),
            'field' => 'tickets_id',
            'name' => __('Ticket'),
            'datatype' => 'itemlink',
            'itemlink_type' => 'Ticket',
        ];

        $tab[] = [
            'id' => '3',
            'table' => $this->getTable(),
            'field' => 'tickettasks_id',
            'name' => __('Task'),
            'datatype' => 'itemlink',
            'itemlink_type' => 'TicketTask',
        ];

        $tab[] = [
            'id' => '4',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id',
            'name' => __('User'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field'              => 'itemtype',
            'name'               => _n('Type', 'Types', 1),
            'massiveaction'      => false,
            'datatype'           => 'itemtypename',
        ];

        $tab[] = [
            'id' => '6',
            'table' => $this->getTable(),
            'field' => 'items_id',
            'name' => __('Item'),
//                        'datatype' => 'specific',
            //            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '7',
            'table' => $this->getTable(),
            'field' => 'action',
            'name' => __('Action'),
            'datatype' => 'specific',
            //            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '8',
            'table' => $this->getTable(),
            'field' => 'value',
            'name' => __('Value'),
            //            'datatype' => 'itemlink',
            //            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        $tab[] = [
            'id' => '121',
            'table' => $this->getTable(),
            'field' => 'date',
            'name' => __('Date'),
            'datatype' => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * @param string $field
     * @param array|string $values
     * @param array $options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        global $PLUGIN_HOOKS;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {

            case 'action_field':
                return PluginTaskrulesAction::getActionName($values[$field]);

            case 'action':
                $log = new self();
                if (isset($options["raw_data"]["id"])) {
                    $log->getFromDB($options["raw_data"]["id"]);
                }
                if (isset($log->fields['action_field'])) {
                    if ($log->fields['action_field'] == 'state_action') {
                        return PluginTaskrulesAction::getStatusActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'location_action') {
                        return PluginTaskrulesAction::getLocationActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'contact_action') {
                        return PluginTaskrulesAction::getContactActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'user_action') {
                        return PluginTaskrulesAction::getUserActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'group_action') {
                        return PluginTaskrulesAction::getGroupActionName($values[$field]);
                    }
                }

                return ;

                //            case 'value':
                //                $log = new self();
                //                if (isset($options["raw_data"]["id"])) {
                //                    $log->getFromDB($options["raw_data"]["id"]);
                //                }
                //                if (isset($log->fields['action_field'])) {
                //                    if ($log->fields['action_field'] == 'state_action') {
                //                        return Dropdown::getDropdownName('glpi_states', $values[$field]);
                //                    } elseif ($log->fields['action_field'] == 'location_action') {
                //                        return Dropdown::getDropdownName('glpi_locations', $values[$field]);
                //                    } else {
                //                        return $values[$field];
                //                    }
                //                }
                //
                //                return ;

        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            //            case 'itilcategories_id':
            //                $opt = ['name' => $name,
            //                    'value' => $values[$field],
            //                    'display' => false];
            //                return ITILCategory::dropdown($opt);
            //            case 'type':
            //                $options['value'] = $values[$field];
            //                return Ticket::dropdownType($name, $options);
            //            case 'object_to_create':
            //                return Dropdown::showFromArray($name, self::getObjectTypes(), $options);
            //                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }
}
