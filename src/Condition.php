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
use AllAssets;
use CommonDBTM;
use DBConnection;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Search\SearchEngine;
use Glpi\Toolbox\URL;
use Html;
use Migration;
use Search;
use Session;
use Toolbox;

/**
 * Class Condition
 */
class Condition extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules';

    public static $types     = ['Computer', 'Monitor', 'Peripheral', 'Printer', 'Phone'];

    public static $search = [];

    private static function decodeField($value): array
    {
        if (empty($value)) {
            return [];
        }
        // Try JSON first (new format)
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        // Fall back to PHP unserialize (legacy format stored before JSON migration)
        $unserialized = @unserialize($value, ['allowed_classes' => false]);
        if (is_array($unserialized)) {
            return $unserialized;
        }
        return [];
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
                        `taskrules_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `fields_computer` text DEFAULT NULL,
                        `fields_monitor` text DEFAULT NULL,
                        `fields_peripheral` text DEFAULT NULL,
                        `fields_printer` text DEFAULT NULL,
                        `fields_phone` text DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `taskrules_id` (`taskrules_id`)
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

    public function showCriteriasForm($id)
    {
        echo $this->renderCriteriasForm($id);
    }

    /**
     * Conditions part of the rule form: one search of criteria per used itemtype
     */
    public function renderCriteriasForm($id): string
    {
        $p = [];
        $p['showbookmark'] = false;
        $p['mainform'] = true;

        $cond = new self();
        if (!$cond->getFromDBByCrit(["taskrules_id" => $id])) {
            $cond->getEmpty();
        }
        $save_fold_status = $_SESSION['glpifold_search'] ?? 0;
        $_SESSION['glpifold_search'] = 0;

        $types = self::$types;
        $rule = new Rule();
        if ($rule->getFromDB($id)) {
            $used_itemtypes = json_decode($rule->fields['used_itemtypes']);
            if (is_array($used_itemtypes) && count($used_itemtypes) > 0) {
                $types = $used_itemtypes;
            }
        }

        $criterias = [];
        foreach ($types as $itemtype) {
            // Guard the dynamic instantiation against a stale/forged itemtype,
            // mirroring the getItemForItemtype() guard used in checkCriteras().
            $item = getItemForItemtype($itemtype);
            if (!$item) {
                continue;
            }
            $criterianame = self::getCriteriaNameForItemtype($itemtype);
            $fieldname    = self::getFieldNameForItemtype($itemtype);
            $decoded      = self::decodeField($cond->fields[$fieldname]);
            $p[$criterianame]                                = $decoded;
            $_SESSION['glpisearch'][$itemtype][$criterianame] = $decoded;

            $criterias[] = [
                'type_name'   => $item->getTypeName(),
                'search_html' => self::renderGenericSearch($itemtype, $p),
            ];
        }

        $_SESSION['glpifold_search'] = $save_fold_status;

        return TemplateRenderer::getInstance()->render('@taskrules/criterias_form.html.twig', [
            'criterias' => $criterias,
        ]);
    }

    /**
     * Print generic search form
     *
     * Params need to parsed before using Search::manageParams function
     *
     * @param class-string<CommonDBTM> $itemtype  Type to display the form
     * @param array  $params    Array of parameters may include sort, is_deleted, criteria, metacriteria
     *
     * @return void
     **/
    public static function showGenericSearch($itemtype, array $params)
    {
        echo self::renderGenericSearch($itemtype, $params);
    }

    /**
     * Search form of the criteria of an itemtype, as a string (see showGenericSearch())
     *
     * @param class-string<CommonDBTM> $itemtype
     * @param array<string, mixed>     $params
     */
    public static function renderGenericSearch($itemtype, array $params): string
    {

        // Default values of parameters
        $p['sort']         = '';
        $p['is_deleted']   = 0;
        $p['as_map']       = 0;
        $p['browse']       = 0;

        $p['metacriteria'] = [];
        if (class_exists($itemtype)) {
            $p['target']       = $itemtype::getSearchURL();
        } else {
            $p['target']       = Toolbox::getItemTypeSearchURL($itemtype);
        }
        $p['showreset']    = true;
        $p['showbookmark'] = true;
        $p['showfolding']  = true;
        $p['mainform']     = true;
        $itemtype_l = strtolower($itemtype);
        $p['prefix_crit'] = "_{$itemtype_l}";
        $p['addhidden']    = [];
        $p['showaction']   = true;
        $p['actionname']   = 'search';
        $p['actionvalue']  = _sx('button', 'Search');
        $types = self::$types;
        foreach ($types as $type) {
            $criterianame     = self::getCriteriaNameForItemtype($type);
            $p[$criterianame] = [];
        }

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $p['target'] = URL::sanitizeURL($p['target']);

        // Itemtype name used in element ids
        $normalized_itemtype = strtolower(str_replace('\\', '', $itemtype));
        $rand_criteria = mt_rand();
        $searchcriteriatableid = 'criteriatable' . $normalized_itemtype . mt_rand();

        // Criteria rows already set
        $criterianame = self::getCriteriaNameForItemtype($itemtype);
        // Each row is a criterion (or a group) rendered by its own template
        $criteria_rows = [];
        $cnt = 0;
        if (is_array($p[$criterianame])) {
            $cnt = count($p[$criterianame]);
            foreach (array_keys($p[$criterianame]) as $i) {
                $criteria_rows[] = self::renderCriteria([
                    'itemtype' => $itemtype,
                    'num'      => $i,
                    'p'        => $p,
                ]);
            }
        }

        // name => value, output as hidden inputs by generic_search.html.twig
        $hidden_fields = [];
        foreach ($p['addhidden'] as $key => $val) {
            $hidden_fields[(string) $key] = $val;
        }
        if ($p['mainform']) {
            // For dropdown
            $hidden_fields['itemtype'] = $itemtype;
            // Reset to start when submit new search
            $hidden_fields['start'] = 0;
        }

        return TemplateRenderer::getInstance()->render('@taskrules/generic_search.html.twig', [
            'card_class'        => 'search-form card card-sm mb-4',
            'main_block_class'  => 'search-form-container',
            'folded'            => (bool) ($_SESSION['glpifold_search'] ?? false),
            'rand_criteria'     => $rand_criteria,
            'criteria_table_id' => $searchcriteriatableid,
            'criteria_rows'     => $criteria_rows,
            'hidden_fields'     => $hidden_fields,
            // Read by public/scripts/rule_form.js to add a criterion or a group
            'search_config'     => [
                'url'           => PLUGIN_TASKRULES_WEBDIR . '/ajax/search.php',
                'itemtype'      => $itemtype,
                'count'         => $cnt,
                'p'             => $p,
                'idor_criteria' => Session::getNewIDORToken($itemtype),
                'idor_group'    => Session::getNewIDORToken($itemtype),
            ],
        ]);
    }

    /* Display a criteria field set, this function should be called by ajax/search.php
    *
    * @since 9.4
    *
    * @param  array  $request we should have these keys of parameters:
    *                            - itemtype: main itemtype for criteria, sub one for metacriteria
    *                            - num: index of the criteria
    *                            - p: params of showGenericSearch method
    *
    * @return void
    */
    public static function displayCriteria($request = [])
    {
        echo self::renderCriteria($request);
    }

    /**
     * One criterion of a search, as a string (see displayCriteria())
     *
     * @param array<string, mixed> $request
     */
    public static function renderCriteria($request = []): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return '';
        }

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $options     = self::getCleanedOptions($request["itemtype"]);
        $randrow     = mt_rand();
        // Keep only alphanumerics: this value is echoed into HTML id/data-rowid
        // attributes, so strip any quote/angle-bracket payload (defence in depth
        // on top of the validateIDOR guard in ajax/search.php).
        $normalized_itemtype = preg_replace('/[^a-z0-9]/', '', strtolower($request["itemtype"]));
        $rowid       = 'searchrow' . $normalized_itemtype . $randrow;
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $parents_num = $p['parents_num'] ?? [];
        $criteria    = [];
        $from_meta   = isset($request['from_meta']) && $request['from_meta'];

        $sess_itemtype = $request["itemtype"];
        if ($from_meta) {
            $sess_itemtype = $request["parent_itemtype"];
        }

        if (!$criteria = self::findCriteriaInSession($sess_itemtype, $num, $parents_num)) {
            $criteria =  QueryBuilder::getDefaultCriteria($request["itemtype"]);
        }

        $criterianame = self::getCriteriaNameForItemtype($request["itemtype"]);
        if (
            (isset($criteria[$criterianame])
            && is_array($criteria[$criterianame]))) {

            return self::renderCriteriaGroup($request);
        }

        $add_padding = "p-2";
        if (isset($request["from_meta"])) {
            $add_padding = "p-0";
        }

        // name => value, output as hidden inputs by search/criteria.html.twig
        $hidden_fields = [];
        $link_dropdown = '';
        if (!$from_meta) {
            // First line display add / delete images for normal and meta search items
            if (
                $num == 0
                && isset($p['mainform'])
                && $p['mainform']
            ) {
                // Instanciate an object to access method
                $item = null;
                if ($request["itemtype"] != AllAssets::getType()) {
                    $item = getItemForItemtype($request["itemtype"]);
                }
                if ($item && $item->maybeDeleted()) {
                    $hidden_fields['is_deleted'] = $p['is_deleted'];
                }
                $hidden_fields['as_map'] = $p['as_map'];
                $hidden_fields['browse'] = $p['browse'];
            }

            // Link item
            $operators = SearchEngine::getLogicalOperators(($num == 0));
            $link_dropdown = Dropdown::showFromArray("criteria{$prefix}[$num][link]", $operators, [
                'value'   => $criteria["link"] ?? '',
                'display' => false,
            ]);
        }

        $values   = [];
        // display select box to define search item
        if ($CFG_GLPI['allow_search_view'] == 2 && !isset($request['from_meta'])) {
            $values['view'] = Dropdown::EMPTY_VALUE;
        }

        reset($options);
        $group = '';

        foreach ($options as $key => $val) {
            // print groups
            if (!is_array($val)) {
                $group = $val;
            } elseif (count($val) == 1) {
                $group = $val['name'];
            } else {
                if (
                    (!isset($val['nosearch']) || ($val['nosearch'] == false))
                    && (!$from_meta || !array_key_exists('nometa', $val) || $val['nometa'] !== true)
                ) {
                    $values[$group][$key] = $val["name"];
                }
            }
        }
        if ($CFG_GLPI['allow_search_view'] == 1 && !isset($request['from_meta'])) {
            $values['view'] = Dropdown::EMPTY_VALUE;
        }
        $value = $criteria['field'] ?? '';

        $rand = mt_rand();
        $field_dropdown = Dropdown::showFromArray("criteria{$prefix}[$num][field]", $values, [
            'value'   => $value,
            'rand'    => $rand,
            'display' => false,
        ]);
        $field_id = Html::cleanId("dropdown_criteria{$prefix}[$num][field]$rand");
        $spanid   = Html::cleanId('SearchSpan' . $normalized_itemtype . $prefix . $num);

        $used_itemtype = $request["itemtype"];
        // Force Computer itemtype for AllAssets to permit to show specific items
        if ($request["itemtype"] == AllAssets::getType()) {
            $used_itemtype = 'Computer';
        }

        $p['prefix_crit'] = $prefix;
        $params = [
            'itemtype'    => $used_itemtype,
            '_idor_token' => Session::getNewIDORToken($used_itemtype),
            'field'       => $value,
            'searchtype'  => $criteria['searchtype'] ?? "",
            'value'       => $criteria['value'] ?? "",
            'num'         => $num,
            'p'           => $p,
        ];

        return TemplateRenderer::getInstance()->render('@taskrules/search/criteria.html.twig', [
            'padding'        => isset($request["from_meta"]) ? 'p-0' : 'p-2',
            'header_class'   => $addclass,
            'rowid'          => $rowid,
            'from_meta'      => $from_meta,
            'hidden_fields'  => $hidden_fields,
            'link_dropdown'  => $link_dropdown,
            'field_dropdown' => $field_dropdown,
            'spanid'         => $spanid,
            // Search option part of the core (QueryBuilder::displaySearchoption()), which only
            // displays: captured as a raw slot, the core renders and escapes it
            'search_option'  => self::captureCoreSearchOption($params),
            'update_script'  => Ajax::updateItemOnSelectEvent(
                $field_id,
                $spanid,
                $CFG_GLPI["root_doc"] . "/ajax/search.php",
                [
                    'action'     => 'display_searchoption',
                    'field'      => '__VALUE__',
                ] + $params,
                false,
            ),
        ]);
    }

    /**
     * Search option part of a criterion, rendered by the core: Search::displaySearchoption()
     * has no return mode, its output is the only one captured here
     *
     * @param array<string, mixed> $params
     */
    private static function captureCoreSearchOption(array $params): string
    {
        ob_start();
        Search::displaySearchoption($params);

        return (string) ob_get_clean();
    }

    /**
     * Clean search options depending of user active profile
     *
     * @param string  $itemtype     Item type to manage
     * @param int $action       Action which is used to manupulate searchoption
     *                               (default READ)
     * @param bool $withplugins  Get plugins options (true by default)
     *
     * @return array Clean $SEARCH_OPTION array
     **/
    public static function getCleanedOptions($itemtype, $action = READ, $withplugins = true)
    {

        $options = self::getSearchOptions($itemtype, $withplugins);

        $opts['common'] = ['name' => __('Characteristics')];
        $opts[4]      = $options[4];
        $opts[31]      = $options[31];

        return $opts;
    }

    /**
     * Get the SEARCH_OPTION array
     *
     * @param string  $itemtype     Item type
     * @param bool $withplugins  Get search options from plugins (true by default)
     *
     * @return array The reference to the array of search options for the given item type
     **/
    public static function &getSearchOptions($itemtype, $withplugins = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $item = null;

        if (!isset(self::$search[$itemtype])) {
            // standard type first
            switch ($itemtype) {

                default:
                    if ($item = getItemForItemtype($itemtype)) {
                        self::$search[$itemtype] = $item->searchOptions();
                    }
                    break;
            }

            $fn_append_options = static function ($new_options) use ($itemtype) {
                // Check duplicate keys between new options and existing options
                $duplicate_keys = array_intersect(array_keys(self::$search[$itemtype]), array_keys($new_options));
                if (count($duplicate_keys) > 0) {
                    trigger_error(
                        sprintf(
                            'Duplicate keys found in search options for item type %s: %s',
                            $itemtype,
                            implode(', ', $duplicate_keys),
                        ),
                        E_USER_WARNING,
                    );
                }
                self::$search[$itemtype] += $new_options;
            };

            // Complete linkfield if not define
            if (is_null($item)) { // Special union type
                $itemtable = $CFG_GLPI['union_search_type'][$itemtype];
            } else {
                $itemtable = $item->getTable();
            }

            foreach (self::$search[$itemtype] as $key => $val) {
                if (!is_array($val) || count($val) == 1) {
                    // skip sub-menu
                    continue;
                }
                // Compatibility before 0.80 : Force massive action to false if linkfield is empty :
                if (isset($val['linkfield']) && empty($val['linkfield'])) {
                    self::$search[$itemtype][$key]['massiveaction'] = false;
                }

                // Set default linkfield
                if (!isset($val['linkfield']) || empty($val['linkfield'])) {
                    if (
                        (strcmp($itemtable, $val['table']) == 0)
                        && (!isset($val['joinparams']) || (count($val['joinparams']) == 0))
                    ) {
                        self::$search[$itemtype][$key]['linkfield'] = $val['field'];
                    } else {
                        self::$search[$itemtype][$key]['linkfield'] = getForeignKeyFieldForTable($val['table']);
                    }
                }
                // Add default joinparams
                if (!isset($val['joinparams'])) {
                    self::$search[$itemtype][$key]['joinparams'] = [];
                }
            }
        }

        return self::$search[$itemtype];
    }

    public static function getCriteriaNameForItemtype($itemtype)
    {

        $itemtype_l = strtolower($itemtype);
        return "criteria_{$itemtype_l}";
    }

    public static function getFieldNameForItemtype($itemtype)
    {

        $itemtype_l = strtolower($itemtype);
        return "fields_{$itemtype_l}";
    }


    public static function displayCriteriaGroup($request = [])
    {
        echo self::renderCriteriaGroup($request);
    }

    /**
     * A group of criteria, as a string (see displayCriteriaGroup())
     *
     * @param array<string, mixed> $request
     */
    public static function renderCriteriaGroup($request = []): string
    {

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $randrow     = mt_rand();
        // Sanitize the request itemtype before it lands in HTML id/data-rowid
        // attributes (defence in depth on top of validateIDOR).
        $rowid       = 'searchrow' . preg_replace('/[^a-z0-9]/i', '', (string) $request['itemtype']) . $randrow;
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $parents_num = $p['parents_num'] ?? [];

        $criterianame = self::getCriteriaNameForItemtype($request['itemtype']);
        if (!$criteria = self::findCriteriaInSession($request['itemtype'], $num, $parents_num)) {
            $criteria = [
                $criterianame => QueryBuilder::getDefaultCriteria($request['itemtype']),
            ];
        }

        $parents_num = $p['parents_num'] ?? [];
        array_push($parents_num, $num);
        $params = [
            'mainform'    => false,
            'parents_num' => $parents_num,
        ];
        $params['prefix_crit'] = "{$prefix}[$num][$criterianame]";
        $params[$criterianame] = $criteria[$criterianame] ?? [];

        return TemplateRenderer::getInstance()->render('@taskrules/search/criteria_group.html.twig', [
            'header_class'  => $addclass,
            'rowid'         => $rowid,
            'link_dropdown' => Dropdown::showFromArray("criteria{$prefix}[$num][link]", SearchEngine::getLogicalOperators(), [
                'value'   => $criteria["link"] ?? '',
                'display' => false,
            ]),
            'search'        => self::renderGenericSearch($request['itemtype'], $params),
        ]);
    }

    /**
     * Retrieve a single criteria in Session by its index
     *
     * @since 9.4
     *
     * @param  string  $itemtype    which glpi type we must search in session
     * @param  int $num         index of the criteria
     * @param  array   $parents_num node indexes of the parents (@see displayCriteriaGroup)
     *
     * @return array|false   the found criteria array, or false if nothing found
     */
    public static function findCriteriaInSession($itemtype = '', $num = 0, $parents_num = [])
    {

        $criterianame = self::getCriteriaNameForItemtype($itemtype);
        if (!isset($_SESSION['glpisearch'][$itemtype][$criterianame])) {
            return false;
        }
        $criteria = &$_SESSION['glpisearch'][$itemtype][$criterianame];

        if (count($parents_num)) {
            foreach ($parents_num as $parent) {
                if (!isset($criteria[$parent][$criterianame])) {
                    return false;
                }
                $criteria = &$criteria[$parent][$criterianame];
            }
        }
        if (
            isset($criteria[$num])
            && is_array($criteria[$num])
        ) {

            return $criteria[$num];
        }

        return false;
    }

    public static function checkCriteras($from, $rule, $tickets_id, $tickettasks_id, $items_linked)
    {

        //        Toolbox::logInfo($from);
        $found_items = [];
        foreach ($items_linked as $item_linked) {
            if (getItemForItemtype($item_linked['itemtype'])) {
                $item = new $item_linked['itemtype']();
                if ($item->getfromDB($item_linked['items_id'])) {
                    $types = self::$types;
                    $used_itemtypes = json_decode($rule->fields['used_itemtypes']);
                    if (is_array($used_itemtypes) && count($used_itemtypes) > 0) {
                        $types = $used_itemtypes;
                    }

                    $cond = new Condition();
                    if ($cond->getFromDBByCrit(["taskrules_id" => $rule->getID()])
                        && in_array($item_linked['itemtype'], $types)) {
                        $fieldname = Condition::getFieldNameForItemtype($item_linked['itemtype']);
                        if (!empty($cond->fields[$fieldname])) {
                            $criterias = self::decodeField($cond->fields[$fieldname]);

                            $criterias[999] = ['link' => 'AND', 'field' => 2, 'searchtype' => 'equals', 'value' => $item_linked['items_id']];


                            $criterianame = self::getCriteriaNameForItemtype($item_linked['itemtype']);

                            foreach ($criterias as &$subArray) {
                                if (isset($subArray[$criterianame])) {
                                    $subArray['criteria'] = $subArray[$criterianame];
                                    unset($subArray[$criterianame]);
                                }
                            }
                            unset($subArray);

                            //Add interpretation of the criteria
                            if (isset($_SESSION["glpisearchcount"][$item_linked['itemtype']])) {
                                unset($_SESSION["glpisearchcount"][$item_linked['itemtype']]);
                            }
                            if (isset($_SESSION["glpisearchcount2"][$item_linked['itemtype']])) {
                                unset($_SESSION["glpisearchcount2"][$item_linked['itemtype']]);
                            }
                            $glpilist_limit = $_SESSION['glpilist_limit'];
                            $_SESSION['glpilist_limit'] = 999999999;
                            $searchCriteria = [
                                'criteria' => $criterias,
                                'reset' => 'reset',
                            ];

                            // Guarantee the unbounded pagination override is reverted even if
                            // Search::getDatas() throws (malformed criteria, invalid search
                            // option, SQL error). Without the finally, an exception would leave
                            // glpilist_limit = 999999999 in the user's session, making every
                            // later search load all rows unpaginated for the rest of the session.
                            try {
                                $results = Search::getDatas($item_linked['itemtype'], $searchCriteria);
                                foreach ($results['data']['items'] as $k => $val) {
                                    $found_items[$k] = $k;
                                }
                            } finally {
                                $_SESSION['glpilist_limit'] = $glpilist_limit;
                            }
                        }
                    }
                }
            }
        }
        return $found_items;
    }

    public static function getStatuses($itemtypes)
    {
        global $DB;

        $values[0] = Dropdown::EMPTY_VALUE;

        $itemtypecriteria = [];
        if (is_array($itemtypes) && count($itemtypes) > 0) {
            // States visible for any of the chosen itemtypes: the loop used to overwrite the
            // criterion, so only the last itemtype filtered the list
            $itemtypecriteria = [
                'glpi_dropdownvisibilities.is_visible'       => 1,
                'glpi_dropdownvisibilities.visible_itemtype' => array_values(array_map('strval', $itemtypes)),
            ];

            /** Retrieve object types data from the database */
            $iterateur = $DB->request([
                'FIELDS' => ['glpi_states' => ['id', 'completename']],
                'FROM' => 'glpi_states',
                'LEFT JOIN'       => [
                    'glpi_dropdownvisibilities' => [
                        'ON' => [
                            'glpi_dropdownvisibilities' => 'items_id',
                            'glpi_states'          => 'id', [
                                'AND' => [
                                    'glpi_dropdownvisibilities.itemtype' => 'State',
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => ['AND' => $itemtypecriteria]]);

            foreach ($iterateur as $data) {
                $values[$data['id']] = $data['completename'];
            }

            asort($values);
        }

        return $values;
    }

    /**
     * Type than could be linked to a task
     *
     * @param $all boolean, all type, or only allowed ones
     *
     * @return array of types
     **/
    public static function getTypes()
    {

        // Only allowed types
        $types = self::$types;

        foreach ($types as $key => $itemtype) {
            if (!class_exists($itemtype)) {
                continue;
            }

            if (!$itemtype::canView()) {
                unset($types[$key]);
            }
        }

        return $types;
    }
}
