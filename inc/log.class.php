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

use Glpi\Toolbox\Sanitizer;
use Glpi\Toolbox\URL;

/**
 * Class PluginTaskrulesLog
 */
class PluginTaskrulesLog extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';



    public static function showResults($rule, $results, $tickets_id, $tickettasks_id)
    {
        //        //report Results or errors
//        $log = new PluginTaskrulesLog();
//        foreach ($results as $k => $itemtype) {
//            foreach ($itemtype as $action => $res) {
//                if (is_array($res)) {
//                    $inputr['action_field'] = $action;
//                    $inputr['tickets_id'] = $res['tickets_id'];
//                    $inputr['tickettasks_id'] = $res['tickettasks_id'];
//                    $inputr['itemtype'] = $res['itemtype'];
//                    $inputr['items_id'] = $res['items_id'];
//                    $inputr['action'] = $res['action'];
//                    $inputr['value'] = $res['value'];
//                    $inputr['date'] = $_SESSION['glpi_currenttime'];
//                    $log->add($inputr);
//                }
//            }
//        }
//
//        $log = new PluginTaskrulesLog();
//        $logs_found = $log->find([
//            'tickets_id' => $tickets_id,
//            'tickettasks_id' => $tickettasks_id,
//        ]);
//

//        $logs_found = $_SESSION['plugin_taskrules']['show_updateresults']['results'];

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

                    echo "</tr>";
                }
                echo "</table><br>";
            }
        }

        if (count($results) > 0) {
            echo "<table class='tab_cadre_fixe'>";
//            echo "<tr class='tab_bg_2'>";
            echo "<div class='alert alert-success'>";
            echo __('Items updated', 'taskrules');
            echo "</div>";
//            echo "</tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td>";

            $errors = [];
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

            foreach ($results as $k => $itemtype) {
                unset($itemtype['users_id']);
                unset($itemtype['groups_id']);
                unset($itemtype['errors']);
                if (is_array($itemtype)) {
                    if (count($itemtype) > 0) {
                        unset($itemtype['not_found']);
                        echo "<table class='tab_cadre_fixe'>";
                        foreach ($itemtype as $type => $type_results) {
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
                                    echo "</th>";
                                    echo "</tr>";

                                    foreach ($items_id_results as $action => $res) {
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
                                                    if ($name == 'user') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>".$error."</span>";
                                                        $ko = 1;
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
                                                    if ($name == 'group') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>".$error."</span>";
                                                        $kp = 1;
                                                    }
                                                    if ($name == 'user') {
                                                        echo "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        echo "<span style='color:red'>".$error."</span>";
                                                        $ko = 1;
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
//
//        $log->deleteByCriteria([
//            'tickets_id' => $tickets_id,
//            'tickettasks_id' => $tickettasks_id,
//        ]);
    }
}
