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
 * Class PluginTaskrulesAction
 */
class PluginTaskrulesAction extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';

    public static function getActionName($action)
    {
        switch ($action) {
            case 'state_action':
                return __('Status');
            case 'location_action':
                return __('Location');
            case 'contact_action':
                return __('Contact');
            case 'user_action':
                return __('User');
            case 'group_action':
                return __('Group');
        }
    }

    public static function getStatusActionName($action)
    {
        $states[0] = __('Do not change', 'taskrules');
        $states[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $states[2] = __('Affect a specific state on the linked item of ticket', 'taskrules');

        return $states[$action];
    }

    public static function getLocationActionName($action)
    {
        $locations[0] = __('Do not change', 'taskrules');
        $locations[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $locations[2] = __('Affect location of ticket requester on the linked item of ticket', 'taskrules');
        $locations[3] = __('Affect a specific location on the linked item of ticket', 'taskrules');

        return $locations[$action];
    }

    public static function getContactActionName($action)
    {
        $contacts[0] = __('Do not change', 'taskrules');
        $contacts[1] = __('Empty the field on the linked item of ticket', 'taskrules');

        return $contacts[$action];
    }

    public static function getUserActionName($action)
    {
        $users[0] = __('Do not change', 'taskrules');
        $users[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $users[2] = __('Affect ticket requester on the linked item of ticket', 'taskrules');

        return $users[$action];
    }

    public static function getGroupActionName($action)
    {
        $groups[0] = __('Do not change', 'taskrules');
        $groups[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        $groups[2] = __('Affect group of the ticket requester on the linked item of ticket', 'taskrules');

        return $groups[$action];
    }

    public static function launchActions($rule, $tickets_id, $tickettasks_id, $item_linked)
    {
        $results = [];
        $tu = new Ticket_User();
        $users_id = 0;
        $groups_id = 0;
        $existing_users = $tu->find(['tickets_id' => $tickets_id, 'type' => CommonITILActor::REQUESTER]);
        if (count($existing_users) > 0 && count($existing_users) > 1) {
            $results['errors']['user_action'] = __('Multiple requesters', 'taskrules');
        }
        if (count($existing_users) == 1) {
            foreach ($existing_users as $existing_user) {
                $users_id = $existing_user['users_id'];
                $results['users_id'] = $users_id;
            }
        }

        $groups_of_user = Group_User::getUserGroups($users_id);
        $groups = [];
        foreach ($groups_of_user as $group_of_user) {

            $group = new Group();
            if ($group->getFromDB($group_of_user['id'])) {
                if (!$group->fields['is_itemgroup'] == 1) {
                    continue;
                }
            }

            $groups[$group_of_user['id']] = $group_of_user['id'];
        }

        if (count($groups) == 1) {
            foreach ($groups as $group) {
                $groups_id = $group;
                $results['groups_id'] = $groups_id;
            }
        } else {
            $results['errors']['group_action'] = __('Requester have multiple groups', 'taskrules');
        }
        //Launch Actions
        $item = new $item_linked['itemtype']();
        // $states[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        // $states[2] = __('Affect a specific state on the linked item of ticket', 'taskrules');
        if ($rule->fields['state_action'] == 1) {
            $input['states_id'] = 0;
            $input['id'] = $item_linked['items_id'];
            $item->update($input);

            $results[$item_linked['itemtype']][$item_linked['items_id']]['state_action'] = [
                'tickets_id' => $tickets_id,
                'tickettasks_id' => $tickettasks_id,
                'action' => 1,
                'value' => 0,
            ];
        } elseif ($rule->fields['state_action'] == 2) {
            if ($rule->fields['states_id'] > 0) {
                $input['states_id'] = $rule->fields['states_id'];
                $input['id'] = $item_linked['items_id'];
                $item->update($input);
                $results[$item_linked['itemtype']][$item_linked['items_id']]['state_action'] = [
                    'tickets_id' => $tickets_id,
                    'tickettasks_id' => $tickettasks_id,
                    'action' => 2,
                    'value' => $rule->fields['states_id'],
                ];
            }
        }

        // $locations[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        // $locations[2] = __('Affect location of ticket requester on the linked item of ticket', 'taskrules');
        // $locations[3] = __('Affect a specific location on the linked item of ticket', 'taskrules');
        if ($rule->fields['location_action'] == 1) {
            $input['locations_id'] = 0;
            $input['id'] = $item_linked['items_id'];
            $item->update($input);
            $results[$item_linked['itemtype']][$item_linked['items_id']]['location_action'] = [
                'tickets_id' => $tickets_id,
                'tickettasks_id' => $tickettasks_id,
                'action' => 1,
                'value' => 0,
            ];
        } elseif ($rule->fields['location_action'] == 2) {
            if ($users_id > 0) {
                $user = new User();
                if ($user->getFromDB($users_id)) {
                    if ($user->fields['locations_id'] > 0) {
                        $input['locations_id'] = $user->fields['locations_id'];
                        $input['id'] = $item_linked['items_id'];
                        $item->update($input);
                        $results[$item_linked['itemtype']][$item_linked['items_id']]['location_action'] = [
                            'tickets_id' => $tickets_id,
                            'tickettasks_id' => $tickettasks_id,
                            'action' => 2,
                            'value' => $user->fields['locations_id'],
                        ];
                    }
                }
            }
        } elseif ($rule->fields['location_action'] == 3) {
            if ($rule->fields['locations_id'] > 0) {
                $input['locations_id'] = $rule->fields['locations_id'];
                $input['id'] = $item_linked['items_id'];
                $item->update($input);
                $results[$item_linked['itemtype']][$item_linked['items_id']]['location_action'] = [
                    'tickets_id' => $tickets_id,
                    'tickettasks_id' => $tickettasks_id,
                    'action' => 3,
                    'value' => $rule->fields['locations_id'],
                ];
            }
        }

        // $contacts[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        if ($rule->fields['contact_action'] == 1) {
            $input['contact'] = '';
            $input['id'] = $item_linked['items_id'];
            $item->update($input);
            $results[$item_linked['itemtype']][$item_linked['items_id']]['contact_action'] = [
                'tickets_id' => $tickets_id,
                'tickettasks_id' => $tickettasks_id,
                'action' => 1,
                'value' => 'null',
            ];
        }

        // $users[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        // $users[2] = __('Affect ticket requester on the linked item of ticket', 'taskrules');
        if ($rule->fields['user_action'] == 1) {
            $input['users_id'] = 0;
            $input['id'] = $item_linked['items_id'];
            $item->update($input);
            $results[$item_linked['itemtype']][$item_linked['items_id']]['user_action'] = [
                'tickets_id' => $tickets_id,
                'tickettasks_id' => $tickettasks_id,
                'action' => 1,
                'value' => 0,
            ];
        } elseif ($rule->fields['user_action'] == 2) {
            if ($users_id > 0) {
                $user = new User();
                if ($user->getFromDB($users_id)) {
                    $input['users_id'] = $users_id;
                    $input['id'] = $item_linked['items_id'];
                    $item->update($input);
                    $results[$item_linked['itemtype']][$item_linked['items_id']]['user_action'] = [
                        'tickets_id' => $tickets_id,
                        'tickettasks_id' => $tickettasks_id,
                        'action' => 2,
                        'value' => $users_id,
                    ];
                }
            } else {
                $results[$item_linked['itemtype']][$item_linked['items_id']]['user_action'] = [
                    'tickets_id' => $tickets_id,
                    'tickettasks_id' => $tickettasks_id,
                    'action' => 2,
                    'value' => -1,
                ];
            }
        }

        // $groups[1] = __('Empty the field on the linked item of ticket', 'taskrules');
        // $groups[2] = __('Affect group of the requester on the linked item of ticket', 'taskrules');
        if ($rule->fields['group_action'] == 1) {
            $input['groups_id'] = 0;
            $input['id'] = $item_linked['items_id'];
            $item->update($input);
            $results[$item_linked['itemtype']][$item_linked['items_id']]['group_action'] = [
                'tickets_id' => $tickets_id,
                'tickettasks_id' => $tickettasks_id,
                'action' => 1,
                'value' => 0,
            ];
        } elseif ($rule->fields['group_action'] == 2) {
            if ($groups_id > 0) {
                $group = new Group();
                if ($group->getFromDB($groups_id)) {
                    $input['groups_id'] = $groups_id;
                    $input['id'] = $item_linked['items_id'];
                    $item->update($input);
                    $results[$item_linked['itemtype']][$item_linked['items_id']]['group_action'] = [
                        'tickets_id' => $tickets_id,
                        'tickettasks_id' => $tickettasks_id,
                        'action' => 2,
                        'value' => $groups_id,
                    ];
                }
            } else {
                $results[$item_linked['itemtype']][$item_linked['items_id']]['group_action'] = [
                    'tickets_id' => $tickets_id,
                    'tickettasks_id' => $tickettasks_id,
                    'action' => 2,
                    'value' => -1,
                ];
            }
        }

        return $results;
    }
}
