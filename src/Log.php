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

namespace GlpiPlugin\Taskrules;

use CommonDBTM;
use DBConnection;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Ticket;

/**
 * Class Log
 */
class Log extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';


    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `is_recursive` tinyint NOT NULL DEFAULT '0',
                        `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `tickettasks_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `itemtype` varchar(100) NOT NULL,
                        `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `action_field` text DEFAULT NULL,
                        `action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `value` text DEFAULT NULL,
                        `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `entities_id` (`entities_id`),
                        KEY `tickets_id` (`tickets_id`),
                        KEY `tickettasks_id` (`tickettasks_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        } else {
            // Entity isolation for existing installs: add entities_id/is_recursive so
            // GLPI marks the log as entity-assigned and restricts Search::show() to the
            // reader's accessible entities. Legacy rows default to the root entity (0).
            $migration->addField($table, 'entities_id', 'integer', ['after' => 'id']);
            $migration->addField($table, 'is_recursive', 'bool', ['after' => 'entities_id']);
            $migration->addKey($table, 'entities_id');
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $table  = self::getTable();
        $DB->dropTable($table, true);
    }

    public function prepareInputForAdd($input)
    {
        // Stamp the log with the parent ticket's entity so GLPI can scope
        // Search::show() to entities the reader is allowed to see.
        if (!isset($input['entities_id']) && !empty($input['tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB((int) $input['tickets_id'])) {
                $input['entities_id'] = (int) $ticket->fields['entities_id'];
            }
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * Build the render-model rows for items that did not match the rule.
     *
     * Rows carry raw field values on purpose: HTML escaping is delegated to
     * Twig auto-escaping in results.html.twig ({{ row.serial }} / {{ row.type_name }}).
     * The {{ row.link|raw }} column is a GLPI-built link and is emitted as-is.
     *
     * @param array $not_found list of ['itemtype' => class, 'items_id' => int]
     *
     * @return array<int, array{type_name: string, link: string, serial: string}>
     */
    public static function buildNotFoundRows(array $not_found): array
    {
        $rows = [];
        foreach ($not_found as $n_found) {
            $obj = getItemForItemtype($n_found['itemtype']);
            $type_name = '';
            $link = '';
            $serial = '';
            if ($obj) {
                $type_name = $obj->getTypeName();
                $obj->getFromDB($n_found['items_id']);
                $link = $obj->getLink();
                $serial = $obj->fields['serial'] ?? '';
            }
            $rows[] = [
                'type_name' => $type_name,
                'link'      => $link,
                'serial'    => $serial,
            ];
        }
        return $rows;
    }

    public static function showResults($rule, $results, $tickets_id, $tickettasks_id)
    {
        $errors = [];
        $not_found = [];
        if (isset($_SESSION['plugin_taskrules']['results'])) {
            $results = $_SESSION['plugin_taskrules']['results'];
        }

        unset($_SESSION['plugin_taskrules']['results']);

        // Collect the "not found" rows (items that did not match the rule).
        $not_found_rows = [];
        if (isset($results['errors']['not_found'])
            && count($results['errors']['not_found']) > 0) {
            $not_found_rows = self::buildNotFoundRows($results['errors']['not_found']);
        }
        unset($results['errors']);

        // Collect the "no rights" rows: assets that matched the rule but whose
        // mutation was blocked by the can(UPDATE) guard in Action::launchActions().
        // Each numeric entry is a launchActions() return that may carry
        // ['errors']['no_right'] — surface them instead of silently dropping them.
        $no_right_rows = [];
        foreach ($results as $itemtype) {
            if (is_array($itemtype)
                && isset($itemtype['errors']['no_right'])
                && is_array($itemtype['errors']['no_right'])) {
                foreach ($itemtype['errors']['no_right'] as $nr) {
                    $obj = getItemForItemtype($nr['itemtype']);
                    $type_name = '';
                    $link = '';
                    $serial = '';
                    if ($obj) {
                        $type_name = $obj->getTypeName();
                        $obj->getFromDB($nr['items_id']);
                        $link = $obj->getLink();
                        $serial = $obj->fields['serial'] ?? '';
                    }
                    $no_right_rows[] = [
                        'type_name' => $type_name,
                        'link'      => $link,
                        'serial'    => $serial,
                    ];
                }
            }
        }

        // Build the "updated" render model and, as a side effect, the
        // $log_actions entries persisted at the end of the method.
        $log_actions = [];
        $updated_blocks = [];
        if (count($results) > 0) {
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

            $i = 0;
            $j = 0;
            foreach ($results as $k => $itemtype) {
                unset($itemtype['users_id']);
                unset($itemtype['groups_id']);
                unset($itemtype['errors']);
                if (is_array($itemtype)) {
                    if (count($itemtype) > 0) {
                        unset($itemtype['not_found']);
                        $groups = [];
                        foreach ($itemtype as $type => $type_results) {
                            $i++;
                            if (is_array($type_results)) {
                                foreach ($type_results as $items_id => $items_id_results) {
                                    $obj = getItemForItemtype($type);
                                    $type_name = '';
                                    $link = '';
                                    $serial = '';
                                    if ($obj) {
                                        $type_name = $obj->getTypeName();
                                        $obj->getFromDB($items_id);
                                        $link = $obj->getLink();
                                        $serial = $obj->fields['serial'] ?? '';
                                    }

                                    $item_actions = [];
                                    foreach ($items_id_results as $action => $res) {
                                        $j++;
                                        $action_name = Action::getActionName($action);
                                        $action_type = '';
                                        if ($action == 'state_action') {
                                            $action_type = Action::getStatusActionName($res['action']);
                                        } elseif ($action == 'location_action') {
                                            $action_type = Action::getLocationActionName($res['action']);
                                        } elseif ($action == 'contact_action') {
                                            $action_type = Action::getContactActionName($res['action']);
                                        } elseif ($action == 'user_action') {
                                            $action_type = Action::getUserActionName($res['action']);
                                        } elseif ($action == 'group_action') {
                                            $action_type = Action::getGroupActionName($res['action']);
                                        }

                                        $result_html = '';
                                        if (($action == 'location_action'
                                                || $action == 'status_action'
                                                || $action == 'user_action'
                                                || $action == 'group_action')
                                            && $res['action'] == 1 && $res['value'] == 0) {
                                            $result_html = "<i class='ti ti-check' style='color: forestgreen;'></i>";
                                            $titlelog = "";
                                            if ($action == 'location_action') {
                                                $titlelog = __('Location');
                                            } elseif ($action == 'status_action') {
                                                $titlelog = __('Status');
                                            } elseif ($action == 'user_action') {
                                                $titlelog = __('User');
                                            } elseif ($action == 'group_action') {
                                                $titlelog = __('Group');
                                            }

                                            $log_actions[$i][$j] = [
                                                'itemtype' => $type,
                                                'items_id' => $items_id,
                                                'action_field' => $action,
                                                'action' => 1,
                                                'value' => $titlelog . " - " . __('Empty the field', 'taskrules'),
                                            ];
                                        } elseif ($action == 'contact_action'
                                            && $res['value'] == 'null') {
                                            $result_html = "<i class='ti ti-check' style='color: forestgreen;'></i>";
                                            $log_actions[$i][$j] = [
                                                'itemtype' => $type,
                                                'items_id' => $items_id,
                                                'action_field' => $action,
                                                'action' => 1,
                                                'value' => __('Alternate username') . " - " . __(
                                                    'Empty the field',
                                                    'taskrules'
                                                ),
                                            ];
                                        } elseif ($action == 'state_action'
                                            && $res['action'] == 2) {
                                            // Dropdown names are stored raw (GLPI 11) and result_html is
                                            // rendered with |raw in results.html.twig: escape like the else branch.
                                            $result_html = htmlspecialchars((string) Dropdown::getDropdownName('glpi_states', $res['value']), ENT_QUOTES, 'UTF-8');
                                            $log_actions[$i][$j] = [
                                                'itemtype' => $type,
                                                'items_id' => $items_id,
                                                'action_field' => $action,
                                                'action' => 2,
                                                'value' => Dropdown::getDropdownName('glpi_states', $res['value']),
                                            ];
                                        } elseif ($action == 'location_action'
                                            && ($res['action'] == 2 || $res['action'] == 3)) {
                                            // Dropdown names are stored raw (GLPI 11) and result_html is
                                            // rendered with |raw in results.html.twig: escape like the else branch.
                                            $result_html = htmlspecialchars((string) Dropdown::getDropdownName('glpi_locations', $res['value']), ENT_QUOTES, 'UTF-8');
                                            $log_actions[$i][$j] = [
                                                'itemtype' => $type,
                                                'items_id' => $items_id,
                                                'action_field' => $action,
                                                'action' => 2,
                                                'value' => Dropdown::getDropdownName('glpi_locations', $res['value']),
                                            ];
                                        } elseif ($action == 'user_action'
                                            && $res['action'] == 2) {
                                            $ko = 0;
                                            if ($errors) {
                                                foreach ($errors as $name => $error) {
                                                    if ($name == 'user_action') {
                                                        $result_html .= "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        $result_html .= "<span style='color:red'>" . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . "</span><br>";
                                                        $ko = 1;
                                                        $log_actions[$i][$j] = [
                                                            'itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error,
                                                        ];
                                                    }
                                                }
                                            }
                                            if ($ko == 0) {
                                                // User display name is stored raw (GLPI 11) and result_html is
                                                // rendered with |raw in results.html.twig: escape it.
                                                $result_html .= htmlspecialchars((string) getUserName($res['value']), ENT_QUOTES, 'UTF-8');
                                                $log_actions[$i][$j] = [
                                                    'itemtype' => $type,
                                                    'items_id' => $items_id,
                                                    'action_field' => $action,
                                                    'action' => 2,
                                                    'value' => getUserName($res['value']),
                                                ];
                                            }
                                        } elseif ($action == 'group_action'
                                            && $res['action'] == 2) {
                                            $ko = 0;
                                            $kp = 0;
                                            if ($errors) {
                                                foreach ($errors as $name => $error) {
                                                    if ($name == 'group_action') {
                                                        $result_html .= "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        $result_html .= "<span style='color:red'>" . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . "</span><br>";
                                                        $kp = 1;
                                                        $log_actions[$i][$j] = [
                                                            'itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error,
                                                        ];
                                                    }
                                                    if ($name == 'user_action') {
                                                        $result_html .= "<i class='ti ti-circle-x' style='color: red;'></i>&nbsp;";
                                                        $result_html .= "<span style='color:red'>" . htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') . "</span><br>";
                                                        $ko = 1;
                                                        $log_actions[$i][$j] = [
                                                            'itemtype' => $type,
                                                            'items_id' => $items_id,
                                                            'action_field' => $name,
                                                            'action' => 2,
                                                            'value' => $error,
                                                        ];
                                                    }
                                                }
                                            }
                                            if ($ko == 0 && $kp == 0) {
                                                // Group names are stored raw (GLPI 11) and result_html is
                                                // rendered with |raw in results.html.twig: escape it.
                                                $result_html .= htmlspecialchars((string) Dropdown::getDropdownName('glpi_groups', $res['value']), ENT_QUOTES, 'UTF-8');
                                                $log_actions[$i][$j] = [
                                                    'itemtype' => $type,
                                                    'items_id' => $items_id,
                                                    'action_field' => $action,
                                                    'action' => 2,
                                                    'value' => Dropdown::getDropdownName('glpi_groups', $res['value']),
                                                ];
                                            }
                                        } else {
                                            $result_html = htmlspecialchars((string) ($res['value'] ?? ''), ENT_QUOTES, 'UTF-8');
                                        }

                                        $item_actions[] = [
                                            'action_name' => $action_name,
                                            'action_type' => $action_type,
                                            'result_html' => $result_html,
                                        ];
                                    }

                                    $groups[] = [
                                        'type_name' => $type_name,
                                        'link'      => $link,
                                        'serial'    => $serial,
                                        'actions'   => $item_actions,
                                    ];
                                }
                            }
                        }
                        $updated_blocks[] = ['groups' => $groups];
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('@taskrules/results.html.twig', [
            'not_found_rows' => $not_found_rows,
            'no_right_rows'  => $no_right_rows,
            'updated_blocks' => $updated_blocks,
        ]);
        unset($_SESSION['plugin_taskrules']['show_updateresults']['results']);

        $log = new Log();

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

        foreach ($log_actions as $k => $logs) {
            foreach ($logs as $key => $res) {
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
        //        $log = new Log();
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
            'table' => 'glpi_users',
            'field' => 'name',
            'linkfield' => 'users_id',
            'name' => __('User'),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '5',
            'table' => $this->getTable(),
            'field' => 'itemtype',
            'name' => _n('Type', 'Types', 1),
            'massiveaction' => false,
            'datatype' => 'itemtypename',
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

        $tab[] = [
            'id' => '80',
            'table' => 'glpi_entities',
            'field' => 'completename',
            'linkfield' => 'entities_id',
            'name' => Entity::getTypeName(1),
            'datatype' => 'dropdown',
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
                return Action::getActionName($values[$field]);

            case 'action':
                $log = new self();
                if (isset($options["raw_data"]["id"])) {
                    $log->getFromDB($options["raw_data"]["id"]);
                }
                if (isset($log->fields['action_field'])) {
                    if ($log->fields['action_field'] == 'state_action') {
                        return Action::getStatusActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'location_action') {
                        return Action::getLocationActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'contact_action') {
                        return Action::getContactActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'user_action') {
                        return Action::getUserActionName($values[$field]);
                    } elseif ($log->fields['action_field'] == 'group_action') {
                        return Action::getGroupActionName($values[$field]);
                    }
                }

                return '';

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
