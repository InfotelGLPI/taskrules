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

namespace GlpiPlugin\Taskrules;

use Ajax;
use AllAssets;
use CommonDBTM;
use DBConnection;
use Dropdown;
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
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
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

        echo "<div>";
        echo "<table style='width:100%'>";

        $p['showbookmark'] = false;
        $p['mainform'] = true;

        $cond = new self();
        if (!$cond->getFromDBByCrit(["taskrules_id" => $id])) {
            $cond->getEmpty();
        }
        $save_fold_status = $_SESSION['glpifold_search'];
        $_SESSION['glpifold_search'] = 0;

        $types = self::$types;
        $rule = new Rule();
        if ($rule->getFromDB($id)) {
            $used_itemtypes = json_decode($rule->fields['used_itemtypes']);
            if (is_array($used_itemtypes) && count($used_itemtypes) > 0) {
                $types = $used_itemtypes;
            }
        }

        foreach ($types as $itemtype) {
            $item = new $itemtype();
            echo "<tr class='tab_bg_2'>";
            echo "<th>";
            echo $item->getTypeName();
            echo "</th>";
            echo "</tr>";
            echo "<tr class='tab_bg_2'>";
            echo "<td>";
            $criterianame = self::getCriteriaNameForItemtype($itemtype);
            $fieldname = self::getFieldNameForItemtype($itemtype);
            $p[$criterianame] = self::decodeField($cond->fields[$fieldname]);
            $_SESSION['glpisearch'][$itemtype][$criterianame] = self::decodeField($cond->fields[$fieldname]);
            self::showGenericSearch($itemtype, $p);
            echo "</td>";
            echo "</tr>";
        }

        $_SESSION['glpifold_search'] = $save_fold_status;


        echo "</table>";
        echo "</div>";
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

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
        //        $p['criteria']     = [];
        $types = self::$types;
        foreach ($types as $type) {
            $criterianame = self::getCriteriaNameForItemtype($type);
            $p[$criterianame]     = [];
        }

        foreach ($params as $key => $val) {
            $p[$key] = $val;
        }

        $p['target'] = URL::sanitizeURL($p['target']);

        // Itemtype name used in JS function names, etc
        $normalized_itemtype = strtolower(str_replace('\\', '', $itemtype));
        $rand_criteria = mt_rand();
        $main_block_class = '';
        $card_class = 'search-form card card-sm mb-4';
        //        if ($p['mainform'] && $p['showaction']) {
        //            echo "<form name='searchform$normalized_itemtype' class='search-form-container' method='get' action='" . $p['target'] . "'>";
        //        } else {
        $main_block_class = "search-form-container";
        //            $card_class = 'border d-inline-block ms-1';
        //        }
        $display = $_SESSION['glpifold_search'] ? 'style="display: none;"' : '';
        echo "<div class='$card_class' $display>";

        echo "<div id='searchcriteria$rand_criteria' class='$main_block_class' >";
        $nbsearchcountvar      = 'nbcriteria' . $normalized_itemtype . mt_rand();
        $searchcriteriatableid = 'criteriatable' . $normalized_itemtype . mt_rand();
        // init criteria count

        $criterianame = self::getCriteriaNameForItemtype($itemtype);
        $cnt = 0;
        if (is_array($p[$criterianame])) {
            $cnt = count($p[$criterianame]);
        }
        echo Html::scriptBlock("
         var $nbsearchcountvar = " . $cnt . ";
      ");

        echo "<div class='list-group list-group-flush list-group-hoverable criteria-list pt-2' id='$searchcriteriatableid'>";


        // Display normal search parameters
        $i = 0;
        if (is_array($p[$criterianame])
            && count($p[$criterianame]) > 0) {
            foreach (array_keys($p[$criterianame]) as $i) {
                self::displayCriteria([
                    'itemtype' => $itemtype,
                    'num' => $i,
                    'p' => $p,
                ]);
            }
        }


        echo "<a id='more-criteria$rand_criteria' role='button'
            class='normalcriteria fold-search list-group-item p-2 border-0'
            style='display: none;'></a>";

        echo "</div>"; // .list

        // Keep track of the current savedsearches on reload
        //        if (isset($_GET['savedsearches_id'])) {
        //            echo Html::input("savedsearches_id", [
        //                'type' => "hidden",
        //                'value' => $_GET['savedsearches_id'],
        //            ]);
        //        }

        echo "<div class='card-footer d-flex search_actions'>";
        //        $linked = self::getMetaItemtypeAvailable($itemtype);
        echo "<button id='addsearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
               <i class='ti ti-square-plus'></i>
               <span class='d-none d-sm-block'>" . __s('rule') . "</span>
            </button>";
        //        if (count($linked)) {
        //            echo "<button id='addmetasearchcriteria$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
        //                  <i class='ti ti-circle-plus'></i>
        //                  <span class='d-none d-sm-block'>" . __s('global rule') . "</span>
        //               </button>";
        //        }
        echo "<button id='addcriteriagroup$rand_criteria' class='btn btn-sm btn-outline-secondary me-1' type='button'>
               <i class='ti ti-code-plus'></i>
               <span class='d-none d-sm-block'>" . __s('group') . "</span>
            </button>";
        $json_p = json_encode($p);

        //        if ($p['mainform']) {
        //            if ($p['showaction']) {
        //                // Display submit button
        //                echo '<button class="btn btn-sm btn-primary me-1" type="submit" name="' . htmlspecialchars($p['actionname']) . '">
        //                <i class="ti ti-list-search"></i>
        //                <span class="d-none d-sm-block">' . $p['actionvalue'] . '</span>
        //                </button>';
        //            }
        //            if ($p['showbookmark'] || $p['showreset']) {
        //                if ($p['showbookmark']) {
        //                    SavedSearch::showSaveButton(
        //                        SavedSearch::SEARCH,
        //                        $itemtype,
        //                        isset($_GET['savedsearches_id'])
        //                    );
        //                }
        //
        //                if ($p['showreset']) {
        //                    echo "<a class='btn btn-ghost-secondary btn-icon btn-sm me-1 search-reset'
        //                        data-bs-toggle='tooltip' data-bs-placement='bottom'
        //                        href='"
        //                        . $p['target']
        //                        . (strpos($p['target'], '?') ? '&amp;' : '?')
        //                        . "reset=reset' title=\"" . __s('Blank') . "\"
        //                  ><i class='ti ti-circle-x'></i></a>";
        //                }
        //            }
        //        }
        echo "</div>"; //.search_actions

        // idor checks
        $idor_display_criteria       = Session::getNewIDORToken($itemtype);
        $idor_display_meta_criteria  = Session::getNewIDORToken($itemtype);
        $idor_display_criteria_group = Session::getNewIDORToken($itemtype);

        $itemtype_escaped = addslashes($itemtype);
        $root_doc = PLUGIN_TASKRULES_WEBDIR;
        $JS = <<<JAVASCRIPT
         $('#addsearchcriteria$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$root_doc}/ajax/search.php', {
               'action': 'display_criteria',
               'itemtype': '$itemtype_escaped',
               'num': $nbsearchcountvar,
               'p': $json_p,
               '_idor_token': '$idor_display_criteria'
            })
            .done(function(data) {
               $(data).insertBefore('#more-criteria$rand_criteria');
               $nbsearchcountvar++;
            });
         });

         $('#addcriteriagroup$rand_criteria').on('click', function(event) {
            event.preventDefault();
            $.post('{$root_doc}/ajax/search.php', {
               'action': 'display_criteria_group',
               'itemtype': '$itemtype_escaped',
               'meta': true,
               'num': $nbsearchcountvar,
               'p': $json_p,
               '_idor_token': '$idor_display_criteria_group'
            })
            .done(function(data) {
               $(data).insertBefore('#more-criteria$rand_criteria');
               $nbsearchcountvar++;
            });
         });
JAVASCRIPT;

        if ($p['mainform']) {
            $JS .= <<<JAVASCRIPT
         var toggle_fold_search = function(show_search) {
            $('#searchcriteria{$rand_criteria}').closest('.search-form').toggle(show_search);
         };

         // Init search_criteria state
         var search_criteria_visibility = window.localStorage.getItem('show_full_searchcriteria');
         if (search_criteria_visibility !== undefined && search_criteria_visibility == 'false') {
            $('.fold-search').click();
         }

         $(document).on("click", ".remove-search-criteria", function() {
            // force removal of tooltip
            var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
            if (tooltip !== null) {
               tooltip.dispose();
            }

            var rowID = $(this).data('rowid');
            $('#' + rowID).remove();
            $('#searchcriteria{$rand_criteria} .criteria-list .list-group-item:first-child').addClass('headerRow').show();
         });
JAVASCRIPT;
        }
        echo Html::scriptBlock($JS);

        if (count($p['addhidden'])) {
            foreach ($p['addhidden'] as $key => $val) {
                echo Html::hidden($key, ['value' => $val]);
            }
        }

        if ($p['mainform']) {
            // For dropdown
            echo Html::hidden('itemtype', ['value' => $itemtype]);
            // Reset to start when submit new search
            echo Html::hidden('start', ['value'    => 0]);
        }

        echo "</div>"; // #searchcriteria
        echo "</div>"; // .card
        //        if ($p['mainform'] && $p['showaction']) {
        //            Html::closeForm();
        //        }
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;


        if (
            !isset($request["itemtype"])
            || !isset($request["num"])
        ) {
            return;
        }

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $options     = self::getCleanedOptions($request["itemtype"]);
        $randrow     = mt_rand();
        $normalized_itemtype = strtolower(str_replace('\\', '', $request["itemtype"]));
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

            self::displayCriteriaGroup($request);
            return;
        }

        $add_padding = "p-2";
        if (isset($request["from_meta"])) {
            $add_padding = "p-0";
        }

        echo "<div class='list-group-item $add_padding border-0 normalcriteria$addclass' id='$rowid'>";
        echo "<div class='row g-1'>";

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
                    echo Html::hidden('is_deleted', [
                        'value' => $p['is_deleted'],
                        'id'    => 'is_deleted',
                    ]);
                }
                echo Html::hidden('as_map', [
                    'value' => $p['as_map'],
                    'id'    => 'as_map',
                ]);
                echo Html::hidden('browse', [
                    'value' => $p['browse'],
                    'id'    => 'browse',
                ]);
            }
            echo "<div class='col-auto'>";
            echo "<button class='btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria' type='button' data-rowid='$rowid'
                       data-bs-toggle='tooltip' data-bs-placement='left'
                       title=\"" . __s('Delete a rule') . "\">
            <i class='ti ti-square-minus'></i>
         </button>";
            echo "</div>";
        }
        // Display link item
        $value = '';
        if (!$from_meta) {
            echo "<div class='col-auto'>";
            if (isset($criteria["link"])) {
                $value = $criteria["link"];
            }

            $operators = SearchEngine::getLogicalOperators(($num == 0));
            Dropdown::showFromArray("criteria{$prefix}[$num][link]", $operators, [
                'value' => $value,
            ]);
            echo "</div>";
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
        //        if ($CFG_GLPI['allow_search_all'] && !isset($request['from_meta'])) {
        //            $values['all'] = __('All');
        //        }
        $value = '';

        if (isset($criteria['field'])) {
            $value = $criteria['field'];
        }

        echo "<div class='col-auto'>";
        $rand = Dropdown::showFromArray("criteria{$prefix}[$num][field]", $values, [
            'value' => $value,
        ]);
        echo "</div>";
        $field_id = Html::cleanId("dropdown_criteria{$prefix}[$num][field]$rand");
        $spanid   = Html::cleanId('SearchSpan' . $normalized_itemtype . $prefix . $num);

        echo "<div class='col-auto'>";
        echo "<div class='row g-1' id='$spanid'>";

        $used_itemtype = $request["itemtype"];
        // Force Computer itemtype for AllAssets to permit to show specific items
        if ($request["itemtype"] == AllAssets::getType()) {
            $used_itemtype = 'Computer';
        }

        $searchtype = $criteria['searchtype']
            ?? "";
        $p_value    = $criteria['value']
            ?? "";

        $p['prefix_crit'] = $prefix;
        $params = [
            'itemtype'    => $used_itemtype,
            '_idor_token' => Session::getNewIDORToken($used_itemtype),
            'field'       => $value,
            'searchtype'  => $searchtype,
            'value'       => $p_value,
            'num'         => $num,
            'p'           => $p,
        ];
        Search::displaySearchoption($params);
        echo "</div>";

        Ajax::updateItemOnSelectEvent(
            $field_id,
            $spanid,
            $CFG_GLPI["root_doc"] . "/ajax/search.php",
            [
                'action'     => 'display_searchoption',
                'field'      => '__VALUE__',
            ] + $params
        );
        echo "</div>"; //.row
        echo "</div>"; //#$spanid
        echo "</div>";
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
                            implode(', ', $duplicate_keys)
                        ),
                        E_USER_WARNING
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

        $num         = (int) $request['num'];
        $p           = $request['p'];
        $randrow     = mt_rand();
        $rowid       = 'searchrow' . $request['itemtype'] . $randrow;
        $addclass    = $num == 0 ? ' headerRow' : '';
        $prefix      = isset($p['prefix_crit']) ? htmlspecialchars($p['prefix_crit'], ENT_QUOTES) : '';
        $parents_num = $p['parents_num'] ?? [];

        $criterianame = self::getCriteriaNameForItemtype($request['itemtype']);
        if (!$criteria = self::findCriteriaInSession($request['itemtype'], $num, $parents_num)) {
            $criteria = [
                $criterianame => QueryBuilder::getDefaultCriteria($request['itemtype']),
            ];
        }

        echo "<div class='list-group-item p-2 border-0 normalcriteria$addclass' id='$rowid'>";
        echo "<div class='row g-1'>";
        echo "<div class='col-auto'>";
        echo "<button class='btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria' type='button' data-rowid='$rowid'
                    data-bs-toggle='tooltip' data-bs-placement='left'
                    title=\"" . __s('Delete a rule') . "\"
      >
         <i class='ti ti-square-minus'></i>
      </button>";
        echo "</div>";
        echo "<div class='col-auto'>";
        Dropdown::showFromArray("criteria{$prefix}[$num][link]", SearchEngine::getLogicalOperators(), [
            'value' => $criteria["link"] ?? '',
        ]);
        echo "</div>";

        $parents_num = $p['parents_num'] ?? [];
        array_push($parents_num, $num);
        $params = [
            'mainform' => false,
            'parents_num' => $parents_num,
        ];
        $params['prefix_crit'] = "{$prefix}[$num][$criterianame]";
        $params[$criterianame] = $criteria[$criterianame] ?? [];

        echo "<div class='col-auto'>";
        self::showGenericSearch($request['itemtype'], $params);
        echo "</div>";

        echo "</div>";//.row
        echo "</div>";//.list-group-item
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

                            $results = Search::getDatas($item_linked['itemtype'], $searchCriteria);
                            //                            Toolbox::logInfo($results['sql']['search']);
                            //                            Toolbox::logInfo($results['data']['items']);
                            $_SESSION['glpilist_limit'] = $glpilist_limit;
                            foreach ($results['data']['items'] as $k => $val) {
                                $found_items[$k] = $k;
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
            foreach ($itemtypes as $k => $itemtype) {
                $itemtype_l = strtolower($itemtype);
                $itemtypecriteria["is_visible"] = 1;
                $itemtypecriteria["visible_itemtype"] = $itemtype;
            }

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
