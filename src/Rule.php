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

use Ajax;
use Glpi\Application\View\TemplateRenderer;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Html;
use Item_Ticket;
use Location;
use Migration;
use Planning;
use Session;
use Ticket;
use TaskTemplate;
use TicketTask;

/**
 * Class Rule
 */
class Rule extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';

    public static function getTypeName($nb = 0)
    {
        return _n('Task rule', 'Task rules', $nb, 'taskrules');
    }

    public static function getIcon()
    {
        return "ti ti-settings";
    }

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
                        `tasktemplates_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `used_itemtypes` VARCHAR(255) NOT NULL,
                        `active` tinyint NOT NULL DEFAULT '0',
                        `preventcreation` tinyint NOT NULL DEFAULT '0',
                        `preventchange` tinyint NOT NULL DEFAULT '0',
                        `trigger_creation` tinyint NOT NULL DEFAULT '0',
                        `trigger_update` tinyint NOT NULL DEFAULT '0',
                        `state_action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `location_action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `contact_action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `user_action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `group_action` int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`),
                        KEY `tasktemplates_id` (`tasktemplates_id`),
                        KEY `states_id` (`states_id`),
                        KEY `locations_id` (`locations_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $table  = self::getTable();
        $DB->dropTable($table, true);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!self::canView()) {
            return '';
        }
        switch ($item->getType()) {
            case TaskTemplate::class:
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $dbu = new DbUtils();
                    return self::createTabEntry(
                        self::getTypeName(),
                        $dbu->countElementsInTable(
                            $this->getTable(),
                            ["tasktemplates_id" => $item->getID()],
                        ),
                    );
                }
                return self::createTabEntry(self::getTypeName());
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // The tab is reachable through common.tabs.php even when it is not registered: gate the rendering
        if ($item->getType() != TaskTemplate::class || !self::canView()) {
            return false;
        }

        $rule = new self();
        $rule->showForm($item->getID());

        return true;
    }

    public function showForm($tasktemplates_id, array $options = [])
    {
        $ID = 0;
        if ($this->getFromDBByCrit(['tasktemplates_id' => $tasktemplates_id])) {
            $ID = $this->fields['id'];
            $cond = new Condition();
            if ($conditions = $cond->find(['taskrules_id' => $ID])) {
                if (count($conditions) == 0) {
                    $cond->getEmpty();
                }
            }
        } else {
            $this->getEmpty();
            $cond = new Condition();
            $cond->getEmpty();
        }

        TemplateRenderer::getInstance()->display('@taskrules/rule_form.html.twig', [
            'target'           => $this->getFormURL(),
            'tasktemplates_id' => $tasktemplates_id,
            'id'               => $ID,
            'webdir'           => PLUGIN_TASKRULES_WEBDIR,
            'parameters_rows'  => $this->getParametersRows(),
            // Condition searches: rendered by Condition (search templates of the plugin)
            'criterias_form'   => $cond->renderCriteriasForm($ID),
            'triggers_rows'    => $this->getTriggersRows(),
            'actions_rows'     => $this->getActionsRows($ID),
        ]);

        return true;
    }

    public function showParametersForm()
    {
        TemplateRenderer::getInstance()->display('@taskrules/rule/settings_table.html.twig', [
            'rows' => $this->getParametersRows(),
        ]);
    }

    /**
     * Rows of the parameters part of the rule form
     *
     * @return list<array<string, mixed>> rows of rule/settings_table.html.twig
     */
    private function getParametersRows(): array
    {
        $possible_values = [];
        foreach (Condition::$types as $itemtype) {
            $item = new $itemtype();
            $possible_values[$itemtype] = $item->getTypeName();
        }

        $values = $this->fields['used_itemtypes'] == null ? [] : json_decode($this->fields['used_itemtypes']);

        return [
            [
                'label' => __("Active", "taskrules"),
                'type'  => 'switch',
                'switch' => self::getSwitchData('active', $this->fields['active']),
            ],
            [
                'label' => __("Prevent task creation on error", "taskrules"),
                'type'  => 'switch',
                'switch' => self::getSwitchData('preventcreation', $this->fields['preventcreation']),
            ],
            [
                'label' => __(
                    "Prevent the task from changing its status (from \"to do\" to \"done\") in case of an error",
                    "taskrules",
                ),
                'type'  => 'switch',
                'switch' => self::getSwitchData('preventchange', $this->fields['preventchange']),
            ],
            [
                'label' => __('Used itemtypes', 'taskrules'),
                'type'  => 'widget',
                'widget' => Dropdown::showFromArray('used_itemtypes', $possible_values, [
                    'values'   => $values,
                    'multiple' => 'multiples',
                    'display'  => false,
                ]),
            ],
        ];
    }

    public function showTriggersForm()
    {
        TemplateRenderer::getInstance()->display('@taskrules/rule/settings_table.html.twig', [
            'rows' => $this->getTriggersRows(),
        ]);
    }

    /**
     * Rows of the triggers part of the rule form
     *
     * @return list<array<string, mixed>> rows of rule/settings_table.html.twig
     */
    private function getTriggersRows(): array
    {
        return [
            [
                'label' => __("Activate when a task is added to the ticket", "taskrules"),
                'type'  => 'switch',
                'switch' => self::getSwitchData('trigger_creation', $this->fields['trigger_creation']),
            ],
            [
                'label' => __('Trigger upon updating the task status from "to do" to "done"', "taskrules"),
                'type'  => 'switch',
                'switch' => self::getSwitchData('trigger_update', $this->fields['trigger_update']),
            ],
        ];
    }

    public function showActionsForm($id)
    {
        TemplateRenderer::getInstance()->display('@taskrules/rule/settings_table.html.twig', [
            'rows' => $this->getActionsRows($id),
        ]);
    }

    /**
     * Rows of the actions part of the rule form: one selector per field of the linked item,
     * the status and location ones reloading their specific value through
     * ajax/updateactionsdropdowns.php
     *
     * @return list<array<string, mixed>> rows of rule/settings_table.html.twig
     */
    private function getActionsRows($id): array
    {
        $types = Condition::$types;
        $rule = new Rule();
        if ($rule->getFromDB($id)) {
            $used_itemtypes = json_decode($rule->fields['used_itemtypes']);
            if (is_array($used_itemtypes) && count($used_itemtypes) > 0) {
                $types = $used_itemtypes;
            }
        }
        // IDOR token bound to this rule id: the endpoint may only read the rule the form
        // actually exposed, not an arbitrary id forged by the client.
        $idor_token = Session::getNewIDORToken('', ['id' => (int) $id]);
        $ajax_url   = PLUGIN_TASKRULES_WEBDIR . "/ajax/updateactionsdropdowns.php";

        // Status
        $states = [
            0 => __('Do not change', 'taskrules'),
            1 => __('Empty the field on the linked item of ticket', 'taskrules'),
            2 => __('Affect a specific state on the linked item of ticket', 'taskrules'),
        ];
        $randstate = mt_rand();
        $state_field = Dropdown::showFromArray('state_action', $states, [
            'value'   => $this->fields['state_action'] ?? 0,
            'rand'    => $randstate,
            'display' => false,
        ]);
        // Reload script of the core, kept with the selector it observes
        $state_field .= Ajax::updateItemOnSelectEvent(
            "dropdown_state_action$randstate",
            "specific_states_id",
            $ajax_url,
            [
                'state_action' => '__VALUE__',
                'states_id'    => $this->fields['states_id'],
                'id'           => $id,
                '_idor_token'  => $idor_token,
            ],
            false,
        );
        $specific_state = '';
        if ($this->fields['states_id'] > 0) {
            $condition = new Condition();
            $specific_state = Dropdown::showFromArray('states_id', $condition->getStatuses($types), [
                'value'   => $this->fields['states_id'] ?? 0,
                'display' => false,
            ]);
        }

        // Location
        $locations = [
            0 => __('Do not change', 'taskrules'),
            1 => __('Empty the field on the linked item of ticket', 'taskrules'),
            2 => __('Affect location of ticket requester on the linked item of ticket', 'taskrules'),
            3 => __('Affect a specific location on the linked item of ticket', 'taskrules'),
        ];
        $randlocation = mt_rand();
        $location_field = Dropdown::showFromArray('location_action', $locations, [
            'value'   => $this->fields['location_action'],
            'rand'    => $randlocation,
            'display' => false,
        ]);
        // Reload script of the core, kept with the selector it observes
        $location_field .= Ajax::updateItemOnSelectEvent(
            "dropdown_location_action$randlocation",
            "specific_locations_id",
            $ajax_url,
            [
                'location_action' => '__VALUE__',
                'locations_id'    => $this->fields['locations_id'],
                'id'              => $id,
                '_idor_token'     => $idor_token,
            ],
            false,
        );
        $specific_location = '';
        if ($this->fields['locations_id'] > 0
            && $this->fields['location_action'] == 3) {
            $specific_location = Location::dropdown([
                'name'    => 'locations_id',
                'value'   => $this->fields['locations_id'],
                'entity'  => $_SESSION["glpiactive_entity"],
                'display' => false,
            ]);
        }

        $contacts = [
            0 => __('Do not change', 'taskrules'),
            1 => __('Empty the field on the linked item of ticket', 'taskrules'),
        ];
        $users = [
            0 => __('Do not change', 'taskrules'),
            1 => __('Empty the field on the linked item of ticket', 'taskrules'),
            2 => __('Affect ticket requester on the linked item of ticket', 'taskrules'),
        ];
        $groups = [
            0 => __('Do not change', 'taskrules'),
            1 => __('Empty the field on the linked item of ticket', 'taskrules'),
            2 => __('Affect group of the requester on the linked item of ticket', 'taskrules'),
        ];

        return [
            [
                'label'    => __('Status'),
                'type'     => 'widget',
                'widget'   => $state_field,
                'specific' => ['id' => 'specific_states_id', 'widget' => $specific_state],
                'note'  => __(
                    'Please note that only statuses common to all objects appear if multiple itemtypes are used.',
                    "taskrules",
                ),
            ],
            [
                'label'    => __('Location'),
                'type'     => 'widget',
                'widget'   => $location_field,
                'specific' => ['id' => 'specific_locations_id', 'widget' => $specific_location],
            ],
            [
                'label' => __('Alternate username'),
                'type'  => 'widget',
                'widget' => Dropdown::showFromArray('contact_action', $contacts, [
                    'value'   => $this->fields['contact_action'],
                    'display' => false,
                ]),
            ],
            [
                'label' => __('User'),
                'type'  => 'widget',
                'widget' => Dropdown::showFromArray('user_action', $users, [
                    'value'   => $this->fields['user_action'],
                    'display' => false,
                ]),
            ],
            [
                'label' => __('Group'),
                'type'  => 'widget',
                'widget' => Dropdown::showFromArray('group_action', $groups, [
                    'value'   => $this->fields['group_action'],
                    'display' => false,
                ]),
            ],
        ];
    }

    public static function showSwitchField($name, $value)
    {
        TemplateRenderer::getInstance()->display('@taskrules/rule/switch.html.twig', self::getSwitchData($name, $value));
    }

    /**
     * Data of an on/off switch of a rule setting (rule/switch.html.twig, toggled by
     * public/scripts/rule_form.js)
     *
     * @return array{name: string, id: string, value: bool}
     */
    public static function getSwitchData($name, $value): array
    {
        return [
            'name'  => (string) $name,
            'id'    => $name . mt_rand(),
            'value' => $value == 1,
        ];
    }

    public static function preAddTask($item)
    {
        $ko = 0;
        $koitem = 0;
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->input['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->input['tasktemplates_id']]) && $rule->fields['active'] == 1) {
                    if ($rule->fields['preventcreation'] == 1) {
                        $item_ticket = new Item_Ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->input['tickets_id'],
                        ]);
                        $found_items = [];
                        if (count($items_linked) > 0) {
                            $found_items = Condition::checkCriteras("pre add task", $rule, $item->input['tickets_id'], $item->getID(), $items_linked);
                        }
                        if (count($found_items) > 0) {
                            // Keep $item (the TicketTask) intact: a single non-matching asset blocks
                            foreach ($items_linked as $item_linked) {
                                $asset = getItemForItemtype($item_linked['itemtype']);
                                if ($asset && $asset->getFromDB($item_linked['items_id'])) {
                                    if (!in_array($item_linked['items_id'], $found_items)) {
                                        $ko = 1;
                                        break;
                                    }
                                }
                            }
                        } else {
                            $ko = 1;
                        }
                    } else {
                        $item_ticket = new Item_Ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->input['tickets_id'],
                        ]);
                        if ($items_linked === false) {
                            $koitem = 1;
                        }
                    }
                }
            }
            if ($koitem == 1) {
                $errormsg = __('Warning : No items linked with this ticket', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
            }
            if ($ko == 1) {
                $errormsg = __('Items not match with rule, task not created', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
                // Cancel the TicketTask creation (core aborts add() when input is not an array)
                $item->input = false;
                return false;
            }
        }
    }

    public static function afterAddTask($item)
    {
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'])) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']])
                && $rule->fields['active'] == 1) {
                    $koitem = 0;
                    if ($rule->fields['trigger_creation'] == 1) {
                        $item_ticket = new Item_Ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->fields['tickets_id'],
                        ]);
                        $found_items = [];
                        $results = [];
                        if (count($items_linked) > 0) {
                            $found_items = Condition::checkCriteras("add task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);

                            if (count($found_items) > 0) {
                                foreach ($items_linked as $item_linked) {
                                    if (getItemForItemtype($item_linked['itemtype'])) {
                                        $itemTicket = new $item_linked['itemtype']();
                                        if ($itemTicket->getfromDB($item_linked['items_id'])) {
                                            if (in_array($item_linked['items_id'], $found_items)) {
                                                $results[] = Action::launchActions(
                                                    $rule,
                                                    $item->fields['tickets_id'],
                                                    $item->fields['id'],
                                                    $item_linked,
                                                );
                                            } else {
                                                $results['errors']['not_found'][] = ['itemtype' => $item_linked['itemtype'], 'items_id' => $item_linked['items_id']];
                                            }
                                        }
                                    }
                                }
                            } else {
                                $item_ticket = new Item_Ticket();
                                $items_linked = $item_ticket->find([
                                    'tickets_id' => $item->input['tickets_id'],
                                ]);
                                if ($items_linked === false) {
                                    $koitem = 1;
                                } else {
                                    foreach ($items_linked as $item_linked) {
                                        $results['errors']['not_found'][] = [
                                            'itemtype' => $item_linked['itemtype'],
                                            'items_id' => $item_linked['items_id'],
                                        ];
                                    }
                                }
                            }
                        }
                        if ($koitem == 1) {
                            $errormsg = __('Warning : No items linked with this ticket', 'taskrules');
                            Session::addMessageAfterRedirect($errormsg, false, ERROR);
                        }
                        if (count($results) > 0) {
                            $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] = $item->fields['id'];
                            $_SESSION['plugin_taskrules']['results'] = $results;
                            //                            Log::showResults($rule, $results, $item->fields['tickets_id'], $item->fields['id']);
                        }
                    }
                }
            }
        }
    }

    public static function preUpdateTask($item)
    {
        $ko = 0;
        $koitem = 0;
        if ($item::getType() === TicketTask::getType()) {
            if (isset($item->fields['tasktemplates_id'], $item->input['state']) && $item->input['state'] == Planning::DONE) {
                $rule = new self();
                if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']]) && $rule->fields['active'] == 1) {
                    if ($rule->fields['preventchange'] == 1) {
                        $item_ticket = new Item_Ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->fields['tickets_id'],
                        ]);
                        $found_items = [];
                        if (count($items_linked) > 0) {
                            $found_items = Condition::checkCriteras("pre update task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);
                        }

                        if (count($found_items) > 0) {
                            // Keep $item (the TicketTask) intact: a single non-matching asset blocks
                            foreach ($items_linked as $item_linked) {
                                $asset = getItemForItemtype($item_linked['itemtype']);
                                if ($asset && $asset->getFromDB($item_linked['items_id'])) {
                                    if (!in_array($item_linked['items_id'], $found_items)) {
                                        $ko = 1;
                                        break;
                                    }
                                }
                            }
                        } else {
                            $ko = 1;
                        }
                    } else {
                        $item_ticket = new Item_Ticket();
                        $items_linked = $item_ticket->find([
                            'tickets_id' => $item->fields['tickets_id'],
                        ]);
                        if (!$items_linked) {
                            $koitem = 1;
                        }
                    }
                }
            }
            if ($koitem == 1) {
                $errormsg = __('Warning : No items linked with this ticket', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
            }
            if ($ko == 1) {
                $errormsg = __('Items not match with rule, task not updated', 'taskrules');
                Session::addMessageAfterRedirect($errormsg, false, ERROR);
                // Block the state change on the TicketTask itself
                unset($item->input['state']);
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
                    if ($rule->getFromDBByCrit(["tasktemplates_id" => $item->fields['tasktemplates_id']]) && $rule->fields['active'] == 1) {
                        $koitem = 0;
                        if ($rule->fields['trigger_update'] == 1) {
                            $item_ticket = new Item_Ticket();
                            $items_linked = $item_ticket->find([
                                'tickets_id' => $item->fields['tickets_id'],
                            ]);
                            $found_items = [];
                            $results = [];
                            if (count($items_linked) > 0) {
                                $found_items = Condition::checkCriteras("update task", $rule, $item->fields['tickets_id'], $item->getID(), $items_linked);
                            }
                            if (count($found_items) > 0) {
                                foreach ($items_linked as $item_linked) {
                                    if (getItemForItemtype($item_linked['itemtype'])) {
                                        $itemTicket = new $item_linked['itemtype']();
                                        if ($itemTicket->getfromDB($item_linked['items_id'])) {
                                            if (in_array($item_linked['items_id'], $found_items)) {
                                                $results[] = Action::launchActions(
                                                    $rule,
                                                    $item->fields['tickets_id'],
                                                    $item->fields['id'],
                                                    $item_linked,
                                                );
                                            } else {
                                                $results['errors']['not_found'][] = ['itemtype' => $item_linked['itemtype'], 'items_id' => $item_linked['items_id']];
                                            }
                                        }
                                    }
                                }
                            } else {
                                $item_ticket = new Item_Ticket();
                                $items_linked = $item_ticket->find([
                                    'tickets_id' => $item->input['tickets_id'],
                                ]);
                                if ($items_linked === false) {
                                    $koitem = 1;
                                } else {
                                    foreach ($items_linked as $item_linked) {
                                        $results['errors']['not_found'][] = [
                                            'itemtype' => $item_linked['itemtype'],
                                            'items_id' => $item_linked['items_id'],
                                        ];
                                    }
                                }
                            }
                            if ($koitem == 1) {
                                $errormsg = __('Warning : No items linked with this ticket', 'taskrules');
                                Session::addMessageAfterRedirect($errormsg, false, ERROR);
                            }
                            if (count($results) > 0) {
                                $_SESSION['plugin_taskrules']['show_updateresults']['tickettasks_id'] = $item->fields['id'];
                                $_SESSION['plugin_taskrules']['results'] = $results;
                                //                                Log::showResults($rule, $results, $item->fields['tickets_id'], $item->fields['id']);
                            }
                            //                            Html::redirect(Ticket::getFormURLWithID($item->fields['tickets_id']));
                        }
                    }
                }
            }
        }
    }

    public function prepareInputForAdd($input)
    {
        $allowed = ['tasktemplates_id', 'used_itemtypes', 'active', 'preventcreation',
            'preventchange', 'trigger_creation', 'trigger_update', 'state_action',
            'states_id', 'location_action', 'locations_id', 'contact_action',
            'user_action', 'group_action'];
        $input = array_intersect_key($input, array_flip($allowed));

        if (!empty($input['used_itemtypes'])) {
            // Keep only known itemtypes: prevents persisting an arbitrary class name
            // later instantiated via `new $itemtype()` in Condition::showCriteriasForm.
            $itemtypes = array_values(array_intersect((array) $input['used_itemtypes'], Condition::$types));
            $input['used_itemtypes'] = json_encode($itemtypes);
        } else {
            $input['used_itemtypes'] = json_encode([]);
        }

        return parent::prepareInputForAdd($input);
    }

    /**
     * @param $input
     *
     * @return mixed[]
     */
    public function prepareInputForUpdate($input)
    {
        $allowed = ['id', 'tasktemplates_id', 'used_itemtypes', 'active', 'preventcreation',
            'preventchange', 'trigger_creation', 'trigger_update', 'state_action',
            'states_id', 'location_action', 'locations_id', 'contact_action',
            'user_action', 'group_action'];
        $input = array_intersect_key($input, array_flip($allowed));

        if (!empty($input['used_itemtypes'])) {
            // Keep only known itemtypes: prevents persisting an arbitrary class name
            // later instantiated via `new $itemtype()` in Condition::showCriteriasForm.
            $itemtypes = array_values(array_intersect((array) $input['used_itemtypes'], Condition::$types));
            $input['used_itemtypes'] = json_encode($itemtypes);
        } else {
            $input['used_itemtypes'] = json_encode([]);
        }

        return parent::prepareInputForUpdate($input);
    }
}
