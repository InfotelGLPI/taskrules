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
 * Class PluginTaskrulesCondition
 */
class PluginTaskrulesCondition extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules_taskrules';

    public static $types     = ['Computer', 'Peripheral', 'Phone', 'Printer', 'Monitor', 'NetworkEquipment'];

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return _n('Task condition', 'Task conditions', 2, 'taskrules');
    }

    public function showCriteriasForm()
    {

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";
        // echo '<div class="panel" style="display: none;">';
        echo '<div class="search-form card card-sm mb-4">
        <div id="searchcriteria">
        <div class="list-group list-group-flush list-group-hoverable criteria-list pt-2" id="criteriatablecomputer">';

        /* Draw a row with all the dropdowns */
        $this->ShowCriteriaRow(1);

        echo "<a id='more-criteria' class='hespace' role='button' style='display: block;'></a>";

        echo '<button id="addsearchcriteria" class="btn btn-sm btn-outline-secondary me-1" type="button">
        <i class="ti ti-square-plus"></i>
        <span class="d-none d-sm-block">règle</span>
        </button>';

        echo "<script>
        var id = '1';
        $('#addsearchcriteria').on('click', function(event) {
            event.preventDefault();
            // Make the statuses dropdown updatable in the Action(s) section
            var bStatus = document.getElementsByName('boolUpdStatuses')[0];
            bStatus.value = 0;
            $.post('" . plugin::getWebDir('taskrules') . "/ajax/addcriteria.php', {
                id: id,
                groupIndex: -1
            })
            .done(function(data) {
                $(data).insertBefore('#more-criteria');
                id++;
            });
        });
        </script>";

        echo '<button id="addcriteriagroup" class="btn btn-sm btn-outline-secondary me-1" type="button">
        <i class="ti ti-code-plus"></i>
        <span class="d-none d-sm-block">groupe</span>
        </button>';

        echo "<script>
        var id = '1';
        $('#addcriteriagroup').on('click', function(event) {
            event.preventDefault();
            // var input = document.getElementById('groupIndex');
            var input = document.getElementsByName('groupIndex')[0];
            input.value = (parseInt(input.value, 10) || 0) + 1;
            $.post('" . plugin::getWebDir('taskrules') . "/ajax/addgroupcriteria.php', {
                id: id
            })
            .done(function(data) {
                $(data).insertBefore('#more-criteria');
                id++;
            });
        });
        </script>";

        echo "</table>";
        echo "</div>";
    }



    public function ShowCriteriaRow($cnt, $groupIndex = 0)
    {
        $bkcolor = $groupIndex > 0 ? '#ced4db' : '#e4e8ed';

        echo '<script>
    var input = document.getElementsByName("groupIndex")[0];
    input.value = (parseInt(input.value, 10) || 0) + 1;
    </script>';

        echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row' . $cnt . '" style="background-color: ' . $bkcolor . ';">';
        echo '<div class="row g-1">
                <div class="col-auto">
                <!--<h5>' . $cnt . '</h5>-->
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row' . $cnt . '"
                   data-bs-toggle="tooltip" data-bs-placement="left"
                   title=' . __("Delete a rule", "taskrules") . '">
                      <i class="ti ti-square-minus" alt="-"></i>
                   </button>
                </div>';

        echo Html::scriptBlock('$(document).on("click", ".remove-search-criteria", function() {
        // Make the statuses dropdown updatable in the Action(s) section
        var bStatus = document.getElementsByName("boolUpdStatuses")[0];
        bStatus.value = 0;
        // force removal of tooltip
        var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
        if (tooltip !== null) {
            tooltip.dispose();
        }
        var rowID = $(this).data("rowid");
        $("#" + rowID).remove();
        });
    ');

        $operator = "";
        $controlId = 'filter[' . $cnt . '][operator]';
        if ($groupIndex > 0) {
            $controlId = 'filter[' . $cnt . '][group][' . $groupIndex . '][operator]';
        }
        $this->showOperatorsDropdown($cnt, $operator, $controlId);
        echo '<div class="col-auto d-flex align-items-center">';
        echo '  <span class="espace">' . __("Object", "taskrules") . '</span>';
        echo '</div>';
        $itemtype = 0;
        $controlId = 'filter[' . $cnt . '][itemtype]';
        if ($groupIndex > 0) {
            $controlId = 'filter[' . $cnt . '][group][' . $groupIndex . '][itemtype]';
        }
        $this->showItemtypesDropdown($cnt, $itemtype, $controlId);
        $controlId = 'filter[' . $cnt . '][criteria]';
        if ($groupIndex > 0) {
            $controlId = 'filter[' . $cnt . '][group][' . $groupIndex . '][criteria]';
        }

        $this->showCriteriaDropdown($cnt, $itemtype, $controlId);
        $controlId = 'filter[' . $cnt . '][last]';
        if ($groupIndex > 0) {
            $controlId = 'filter[' . $cnt . '][group][' . $groupIndex . '][last]';
        }
        $this->showLastDropdown($cnt, $itemtype, $controlId);
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function ShowGroupRow($cnt)
    {
        $grpcnt = 0;
        $grpcnt++;

        echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row' . $cnt . '" style="background-color: #e4e8ed;">
             <div class="row g-1">
                <div class="col-auto">
                <!--<h5>' . $cnt . '</h5>-->
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row' . $cnt . '"
                   data-bs-toggle="tooltip" data-bs-placement="left"
                   title=' . __("Delete a rule", "taskrules") . '">
                      <i class="ti ti-square-minus" alt="-"></i>
                   </button>
                </div>';
        echo Html::scriptBlock('$(document).on("click", ".remove-search-criteria", function() {
        // force removal of tooltip
        var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
        if (tooltip !== null) {
            tooltip.dispose();
        }
        var rowID = $(this).data("rowid");
        $("#" + rowID).remove();
        });
        ');

        $operator = "";
        $this->showOperatorsDropdown($cnt, $operator, 'filter[' . $cnt . '][operator]');
        echo '<div class="col-auto d-flex align-items-center">';

        /** Group begins */
        echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row' . $cnt . '" style="background-color: #ced4db;">
             <div class="row g-1" style="margin: 0;">
                <div class="col-auto">
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row' . $cnt . '"
                   data-bs-toggle="tooltip" data-bs-placement="left"
                   title=' . __("Delete a rule", "taskrules") . '">
                      <i class="ti ti-square-minus" alt="-"></i>
                   </button>
                </div>';
        echo Html::scriptBlock('$(document).on("click", ".remove-search-criteria", function() {
        // force removal of tooltip
        var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
        if (tooltip !== null) {
            tooltip.dispose();
        }
        var rowID = $(this).data("rowid");
        $("#" + rowID).remove();
        });
        ');
        $operator = "";
        $this->showOperatorsDropdown($cnt, $operator, 'filter[' . $cnt . '][group][' . $grpcnt . '][operator]');
        echo '<div class="col-auto d-flex align-items-center">';
        echo '  <span class="espace">' . __("Object", "taskrules") . '</span>';
        echo '</div>';
        $itemtype = "";
        $this->showItemtypesDropdown($cnt, $itemtype, 'filter[' . $cnt . '][group][' . $grpcnt . '][itemtype]');
        $this->showCriteriaDropdown($cnt, $itemtype, 'filter[' . $cnt . '][group][' . $grpcnt . '][criteria]');
        $this->showLastDropdown($cnt, $itemtype, 'filter[' . $cnt . '][group][' . $grpcnt . '][last]');
        echo '</div>';

        echo "<a id='more-grpcriteria' class='hespace' role='button' style='display: block;'></a>";

        echo '<div style="background-color: white; padding:2px 0 10px 0;">';

        // echo "<a id='more-grpcriteria' class='hespace' role='button' style='display: block;'></a>";

        echo '<button id="addsearchcriteria" class="btn btn-sm btn-outline-secondary me-1" type="button">
    <i class="ti ti-square-plus"></i>
    <span class="d-none d-sm-block">règle de groupe</span>
    </button>';

        echo "<script>
    var id = '1';
    // var input = document.getElementById('groupIndex');
    var input = document.getElementsByName('groupIndex')[0];
    $('#addsearchcriteria').on('click', function(event) {
        event.preventDefault();
        $.post('" . plugin::getWebDir('taskrules') . "/ajax/addcriteria.php', {
            id: id,
            groupIndex: input.value
        })
        .done(function(data) {
            $(data).insertBefore('#more-grpcriteria');
            id++;
        });
    });
    </script>";

        // echo '<button id="addcriteriagroup" class="btn btn-sm btn-outline-secondary me-1" type="button">
        // <i class="ti ti-code-plus"></i>
        // <span class="d-none d-sm-block">groupe</span>
        // </button>';

        // echo "<script>
        // var id = '1';
        // $('#addcriteriagroup').on('click', function(event) {
        //     event.preventDefault();
        //     $.post('".plugin::getWebDir('taskrules')."/ajax/addgroupcriteria.php', {
        //         id: id
        //     })
        //     .done(function(data) {
        //         $(data).insertBefore('#more-grpcriteria');
        //         id++;
        //     });
        // });
        // </script>";

        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public static function showSwitchField($name, $value)
    {
        echo Html::hidden($name, ['id' => $name,
            'value' => $value]);
        echo Html::scriptBlock("(function(){
                            var toggleButton = $('.$name');
                            toggleButton.click(function() {
                            if ($(this).hasClass('fa-toggle-on')) {
                                toggleButton.removeClass('fa-toggle-on');
                                toggleButton.addClass('fa-toggle-off');
                                toggleButton.removeClass('enabled');
                                toggleButton.addClass('disabled');
                                document.getElementById('$name').value = '0';
                                } else {
                                toggleButton.removeClass('fa-toggle-off');
                                toggleButton.addClass('fa-toggle-on');
                                toggleButton.removeClass('disabled');
                                toggleButton.addClass('enabled');
                                document.getElementById('$name').value = '1';
                                }
                            });
                        })();");
        if ($value == 1) {
            echo "<a class=\"button\"><i class=\"$name fa-fw fas fa-2x fa-toggle-on enabled\"></i></a>";
        } else {
            echo "<a class=\"button\"><i class=\"$name fa-fw fas fa-2x fa-toggle-off disabled\"></i></a>";
        }
    }

    /* Return an array of operators */
    public static function getOperators()
    {
        $OPERATORS = [
            Dropdown::EMPTY_VALUE => Dropdown::EMPTY_VALUE,
            'AND' => __("AND", "taskrules"),
            'OR' => __("OR", "taskrules"),
            'AND NOT' => __("AND NOT", "taskrules"),
            'OR NOT' => __("OR NOT", "taskrules"),
        ];

        return $OPERATORS;
    }

    public static function getStatuses($itemtypes)
    {
        global $DB;

        $values[0] = Dropdown::EMPTY_VALUE;

        $itemtypecriteria = [];
        if (is_array($itemtypes) && count($itemtypes) > 0) {
            foreach ($itemtypes as $k => $itemtype) {
                $itemtypecriteria["is_visible_" . $itemtype] = 1;
            }

            /** Retrieve object types data from the database */
            $iterateur = $DB->request(['FIELDS' => ['glpi_states' => ['id', 'name']],
                'FROM' => 'glpi_states',
                'WHERE' => ['OR' => $itemtypecriteria]]);

            foreach ($iterateur as $data) {
                $values[$data['id']] = $data['name'];
            }

            asort($values);
        }
        return $values;
    }

    /* Return an array of types from the Itemtype passed as parameter */
    public static function getObjectTypes($selectedItemtype)
    {
        global $DB;

        $values[0] = Dropdown::EMPTY_VALUE;

        $OBJECTTYPES = [
            'Computer' => 'glpi_computertypes',
            'Printer' => 'glpi_printertypes',
            'Phone' => 'glpi_phonetypes',
            'Monitor' => 'glpi_monitortypes',
            'Peripheral' => 'glpi_peripheraltypes',
            'NetworkEquipment' => 'glpi_networkequipmenttypes',
        ];

        if ($selectedItemtype != "") {
            /** Retrieve object types data from the database */
            $iterateur = $DB->request(['FIELDS' => [$OBJECTTYPES[$selectedItemtype] => ['id', 'name']],
                'FROM' => $OBJECTTYPES[$selectedItemtype]]);

            foreach ($iterateur as $data) {
                $values[$data['id']] = $data['name'];
            }
        }

        asort($values);
        return $values;
    }

    /**
     * Type than could be linked to a Rack
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


    public function showOperatorsDropdown($id, $operator, $controlId)
    {
        echo '<div class="col-auto">';
        /* First dropdown : list of operators */
        Dropdown::showFromArray($controlId, self::getOperators(), [
            'value' => $operator,
        ]);
        echo '</div>';
    }

    public function showItemtypesDropdown($id, $itemtype, $controlId)
    {
        echo '<div class="col-auto">';
        /* Second dropdown : list of itemtypes */
        $possible_values[Dropdown::EMPTY_VALUE] = Dropdown::EMPTY_VALUE;
        $itemtypes = self::getTypes();
        if (!empty($itemtypes)) {
            foreach ($itemtypes as $k => $itemtype) {
                if ($item = getItemForItemtype($itemtype)) {
                    $possible_values[$itemtype] = $item->getTypeName();
                }
            }
        }

        Dropdown::showFromArray($controlId, $possible_values, [
            'value' => $itemtype,
            'on_change' => "updateHidden$id(this.value)",
        ]);
        echo '<input type="hidden" id="itemtype' . $id . '" value="">';
        echo '</div>';
    }

    public function showCriteriaDropdown($id, $itemtype, $controlId)
    {
        $criteria = [
            Dropdown::EMPTY_VALUE => Dropdown::EMPTY_VALUE,
            'Status' => __("Status", "taskrules"),
            'Type' => __("Type", "taskrules"),
        ];

        echo '<div class="col-auto">';
        /* Third dropdown : status or type */
        //        $controlId = is_int($controlId) ?? 0;
        $cr = Dropdown::showFromArray($controlId, $criteria, [
            'value' => $itemtype,
            'on_change' => "updateLastDropdown$id(this.value)",
        ]);
        /* Ajax code to reset the criteria dropdown */
        $selectId = str_replace("[", "_", $controlId);
        $selectId = "dropdown_" . str_replace("]", "_", $selectId) . $cr;
        echo Html::scriptBlock("
            function updateHidden$id(value) {
                var input = document.getElementById('itemtype" . $id . "');
                input.value = value;            
                $.ajax({
                    url: '" . plugin::getWebDir('taskrules') . "/ajax/resetcriteria.php',
                    type: 'GET',
                    data: { item_type: value },
                    success: function(response) {
                        $('#" . $selectId . "').html(response);
                    },
                    error: function (xhr, status, error) {
                    console.log(xhr);
                    console.log(status);
                    console.log(error);
                    }
                });
            }");
        echo '</div>';
    }

    public function showLastDropdown($id, $itemtype, $controlId)
    {

        echo '<div class="col-auto">';
        /* Fourth dropdown : list of statuses for the selected object type */
        //        $controlId = is_int($controlId) ?? 0;
        $cr = Dropdown::showFromArray($controlId, $this->getStatuses(self::getTypes()), [
            'value' => $itemtype,
        ]);

        /* Ajax code to update the last dropdown from the third dropdown selection */
        $selectId = str_replace("[", "_", $controlId);
        $selectId = "dropdown_" . str_replace("]", "_", $selectId) . $cr;
        echo Html::scriptBlock("
    function updateLastDropdown$id(criteria) {
        // Retrieve itemtype from stored value in hidden field
        var itemtype = document.getElementById('itemtype" . $id . "');
        $.ajax({
            url: '" . plugin::getWebDir('taskrules') . "/ajax/lastdropdown.php',
            type: 'POST',
            data: { criteria_Type: criteria, item_type: itemtype.value },
            success: function(response) {
                $('#" . $selectId . "').html(response);
            },
            error: function (xhr, status, error) {
            console.log(xhr);
            console.log(status);
            console.log(error);
            }
        });
    }");

        echo '</div>';
    }
    public static function formatArray($array, $prefix = '')
    {
        $result = '';
        foreach ($array as $key => $value) {
            $newKey = $prefix . '[' . $key . ']';
            if (is_array($value)) {
                $result .= formatArray($value, $newKey);
            } else {
                $result .= $newKey . '=' . $value . PHP_EOL;
            }
        }
        return $result;
    }
}
