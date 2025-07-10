<?php

/*
 -------------------------------------------------------------------------
 Taskrules plugin for GLPI
 Copyright (C) 2018-2022 by the Taskrules Development Team.

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

$GLPI_TYPES = [];

/**
 * Class PluginTaskrulesCondition
 */
class PluginTaskrulesCondition extends CommonDBTM
{
    static $rightname = 'plugin_taskrules_setup';

    const NO_VALUE = 'none';


function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
    return ['Règle(s)'];
}

static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

    if ( $item->getType() != 'TaskTemplate')
        return;

    $ID = $item->getID();
    //echo "ID=$ID";

    $prof = new self();
    $prof->showForm($ID);

    return true;
}

public function showForm($ID, array $options = [])
{
        Global $DB;
        Global $OPERATORS;

        $this->getFromDB($ID);

        //The configuration is not deletable
        $options['colspan'] = 1;
        
        echo "<div class='center'>";
        echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";

        echo Html::hidden('id', ['value' => $ID]);

        /* ---------------------- */
        /* "Parameters" section */
        /* ---------------------- */
        //$this::showSwitchField("actif", false); // show a switch that can be turned off or on
        // echo '<button class="accordiontskrul">' . __("Parameters", "taskrules") . '</button>';

        // echo '<div class="panel" style="display: flex;">';
        // echo '  <div style="flex: 1; padding: 10px;"></div>';
        // echo '  <div style="flex: 4; padding: 10px;">';
        // echo '    <div style="display: flex;">';
        // echo '      <div style="display: flex; flex-direction: column; gap: 18px; min-width: 320px;">';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // echo '          <label for="dropdown_active1919881355" class="form-check-label" style="text-align: left;">'. __("Active : ", "taskrules").'</label>';
        // echo '        </div>';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // echo '          <label for="dropdown_preventcreation1168545998" class="form-check-label" style="text-align: left;">'. __("Prevent task creation on error :", "taskrules").'</label>';
        // echo '        </div>';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // echo '          <label for="dropdown_preventchange1593546928" class="form-check-label" style="text-align: left;">'. __("Prevent the task from changing its status (from \"to do\" to \"done\") in case of an error :", "taskrules").'</label>';
        // echo '        </div>';
        // echo '      </div>';
        // echo '      <div style="display: flex; flex-direction: column; gap: 18px; min-width: 120px;">';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // Dropdown::showYesNo('active', false);
        // echo '        </div>';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // Dropdown::showYesNo('preventcreation', false);
        // echo '        </div>';
        // echo '        <div style="height: 38px; display: flex; align-items: center;">';
        // Dropdown::showYesNo('preventchange', false);
        // echo '        </div>';
        // echo '      </div>';
        // echo '    </div>';
        // echo '  </div>';
        // echo '</div>';

        /* ---------------------- */
        /* "Condition(s)" section */
        /* ---------------------- */
        echo '<button class="accordiontskrul">' . __("Condition(s)", "taskrules") . '</button>';

        echo '<div class="search-form card card-sm mb-4">
        <div id="searchcriteria">
        <div class="list-group list-group-flush list-group-hoverable criteria-list pt-2" id="criteriatablecomputer">';

        /* Draw a row with all the dropdowns */
        $this::ShowCriteriaRow(1);

        echo "<a id='more-criteria' class='hespace' role='button' style='display: block;'></a>";

        echo '<button id="addsearchcriteria" class="btn btn-sm btn-outline-secondary me-1" type="button">
        <i class="ti ti-square-plus"></i>
        <span class="d-none d-sm-block">règle</span>
        </button>';

        echo "<script>
        var id = '1';
        $('#addsearchcriteria').on('click', function(event) {
            event.preventDefault();
            $.post('".plugin::getWebDir('taskrules')."/ajax/addcriteria.php', {
                id: id
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
            $.post('".plugin::getWebDir('taskrules')."/ajax/addgroupcriteria.php', {
                id: id
            })
            .done(function(data) {
                $(data).insertBefore('#more-criteria');
                id++;
            });
        });
        </script>";

        echo "<br><br>";

        /* ----------------- */
        /* "Trigger" section */
        /* ----------------- */
        echo '<button class="accordiontskrul">' . __("Trigger", "taskrules") . '</button>';
        echo '<div class="panel" style="display: flex;">
        <div style="flex: 1; padding: 10px;">
        </div>
        <div style="flex: 4; padding: 10px;">
        <label class="form-check" style="display: block; text-align: left;">
            <input class="form-check-input" type="checkbox" name="trigger_creation" checked="checked" value="1" style="margin-right: 10px;">
            <span class="form-check-label" style="vertical-align: middle;">
            ' . __("Activate when a task is added to the ticket", "taskrules") . '
            </span>
        </label>
        <label class="form-check" style="display: block; text-align: left;">
            <input class="form-check-input" type="checkbox" name="trigger_update" checked="checked" value="1" style="margin-right: 10px;">
            <span class="form-check-label" style="vertical-align: middle;">
            ' . __('Trigger upon updating the task status from "to do" to "done"', "taskrules") . '   
            </span>
        </label>
        </div>
        </div>';

        echo '</div>';

        /* ------------------- */
        /* "Action(s)" section */
        /* ------------------- */
        echo '<button class="accordiontskrul">' . __("Action(s)", "taskrules") . '</button>';

        /** ATTENTION : NE PAS OUBLIER */
        /** POUR LES AFFECTATIONS AUX GROUPES, NE PAS INCLURE DANS LA LISTE DES GROUPES SELECTIONNABLES
         * CEUX DONT LA CONFIGURATION "PEUT CONTENIR DES ELEMENTS" EST A "NON"
         */
?>

<!-- 
<select name="filter[0][operator]"><option value="test">test</option></select>
<select name="filter[0][field]"><option value="test">test</option></select>
<select name="filter[0][type]"><option value="test">test</option></select>
<select name="filter[0][status]"><option value="test">test</option></select>

<select name="filter[1][operator]"><option value="test">test</option></select>
<select name="filter[1][field]"><option value="test">test</option></select>
<select name="filter[1][type]"><option value="test">test</option></select>
<select name="filter[1][status]"><option value="test">test</option></select>

<select name="filter[2][operator]"><option value="test">test</option></select>

<select name="filter[2][group][0][operator]"><option value="test">test</option></select>
<select name="filter[2][group][0][field]"><option value="test">test</option></select>
<select name="filter[2][group][0][type]"><option value="test">test</option></select>
<select name="filter[2][group][0][status]"><option value="test">test</option></select>

<select name="filter[2][group][1][operator]"><option value="test">test</option></select>
<select name="filter[2][group][1][field]"><option value="test">test</option></select>
<select name="filter[2][group][1][type]"><option value="test">test</option></select>
<select name="filter[2][group][1][status]"><option value="test">test</option></select>
-->

<div class='center'>
<div class="list-group list-group-flush list-group-hoverable criteria-list pt-2" style="background-color: white; id="criteriatablecomputer">
  <div class="list-group-item p-2 border-0 normalcriteria" id="row1">
    <div class="row g-1">
      <div class="col-1"></div> <!-- Colonne vide à gauche -->
      <div class="col-2 d-flex align-items-center">Statut :</div>
      <div class="col-2 d-flex align-items-center">
        <select id="statut" name="statut">
          <option value="preparation">En préparation</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="col d-flex align-items-center"></div>
    </div>
    <div class="row g-1">
      <div class="col-1"></div>
      <div class="col-2 d-flex align-items-center">Lieu :</div>
      <div class="col-2 d-flex align-items-center">
        <select id="lieu" name="lieu">
          <option value="petit_stock">Petit stock</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="col d-flex align-items-center">
        <input type="checkbox" id="vider_lieu" name="vider_lieu">
        <label for="vider_lieu">Vider le champ Lieu</label>
      </div>
    </div>
    <div class="row g-1">
      <div class="col-1"></div>
      <div class="col-2 d-flex align-items-center">Usager :</div>
      <div class="col-2 d-flex align-items-center">
        <select id="usager" name="usager">
          <option value="usager_du_materiel">Usager du matériel</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="col d-flex align-items-center">
        <input type="checkbox" id="vider_usager" name="vider_usager">
        <label for="vider_usager">Vider le champ Usager</label>
      </div>
    </div>
    <div class="row g-1">
      <div class="col-1"></div>
      <div class="col-2 d-flex align-items-center">Utilisateur :</div>
      <div class="col-2 d-flex align-items-center">
        <select id="utilisateur" name="utilisateur">
          <option value="demandeur">Demandeur</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="col d-flex align-items-center">
        <input type="checkbox" id="vider_utilisateur" name="vider_utilisateur">
        <label for="vider_utilisateur">Vider le champ Utilisateur</label>
      </div>
    </div>
    <div class="row g-1">
      <div class="col-1"></div>
      <div class="col-2 d-flex align-items-center">Groupe :</div>
      <div class="col-2 d-flex align-items-center">
        <select id="groupe" name="groupe">
          <option value="groupe_du_demandeur">Groupe du demandeur</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="col d-flex align-items-center">
        <input type="checkbox" id="vider_groupe" name="vider_groupe">
        <label for="vider_groupe">Vider le champ Groupe</label>
      </div>
    </div>
  </div>
</div>
</div>

<?php

        echo '<div class="panel" style="display: block;">';
        echo '<p>
        </p>';
        echo '</div>';
    
        echo "</div>";

        $options['candel'] = false;  // Hide [Cancel] button
        $options['no_save'] = false; // Display [Save] button
        // $options['id'] =$ID;

        echo '<div class="card-body mx-n2 mb-4 border-top d-flex flex-row-reverse align-items-start flex-wrap">
        <button class="btn btn-primary me-2" type="submit" name="save" value="1">
                  <i class="far fa-save"></i>
                  <span>Sauvegarder</span>
               </button></div>';

        //$this->showFormButtons($options);

        Html::closeForm();

        return true;
    }  

public static function ShowCriteriaRow($cnt)
{
    echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row'.$cnt.'" style="background-color: #e4e8ed;">
             <div class="row g-1">
                <div class="col-auto">
                <!--<h5>'.$cnt.'</h5>-->
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row'.$cnt.'"
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
    self::showOperatorsDropdown($cnt, $operator, 'filter['.$cnt.'][operator]');
    echo '<div class="col-auto d-flex align-items-center">';
    echo '  <span class="espace">' . __("Object", "taskrules") . '</span>';
    echo '</div>';
    $itemtype = "";
    self::showItemtypesDropdown($cnt, $itemtype, 'filter['.$cnt.'][itemtype]');
    self::showCriteriaDropdown($cnt, $itemtype, 'filter['.$cnt.'][criteria]');
    self::showLastDropdown($cnt, $itemtype, 'filter['.$cnt.'][last]');
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

public static function ShowGroupRow($cnt)
{
    $grpcnt = 0;
    $grpcnt++;

    echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row'.$cnt.'" style="background-color: #e4e8ed;">
             <div class="row g-1">
                <div class="col-auto">
                <!--<h5>'.$cnt.'</h5>-->
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row'.$cnt.'"
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
    self::showOperatorsDropdown($cnt, $operator, 'filter['.$cnt.'][operator]');
    echo '<div class="col-auto d-flex align-items-center">';

    /** Group begins */
    echo '<div class="list-group-item p-2 border-0 normalcriteria" id="row'.$cnt.'" style="background-color: #ced4db;">
             <div class="row g-1" style="margin: 0;">
                <div class="col-auto">
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="row'.$cnt.'"
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
    self::showOperatorsDropdown($cnt, $operator, 'filter['.$cnt.'][group]['.$grpcnt.'][operator]');
    echo '<div class="col-auto d-flex align-items-center">';
    echo '  <span class="espace">' . __("Object", "taskrules") . '</span>';
    echo '</div>';
    $itemtype = "";
    self::showItemtypesDropdown($cnt, $itemtype, 'filter['.$cnt.'][group]['.$grpcnt.'][itemtype]');
    self::showCriteriaDropdown($cnt, $itemtype, 'filter['.$cnt.'][group]['.$grpcnt.'][criteria]');
    self::showLastDropdown($cnt, $itemtype, 'filter['.$cnt.'][group]['.$grpcnt.'][last]');
    echo '</div>';

    echo "<a id='more-grpcriteria' class='hespace' role='button' style='display: block;'></a>";

    echo '<div style="background-color: white; padding:2px 0 10px 0;">';

    // echo "<a id='more-grpcriteria' class='hespace' role='button' style='display: block;'></a>";

    echo '<button id="addsearchcriteria" class="btn btn-sm btn-outline-secondary me-1" type="button">
    <i class="ti ti-square-plus"></i>
    <span class="d-none d-sm-block">règle</span>
    </button>';

    echo "<script>
    var id = '1';
    $('#addsearchcriteria').on('click', function(event) {
        event.preventDefault();
        $.post('".plugin::getWebDir('taskrules')."/ajax/addcriteria.php', {
            id: id
        })
        .done(function(data) {
            $(data).insertBefore('#more-grpcriteria');
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
        $.post('".plugin::getWebDir('taskrules')."/ajax/addgroupcriteria.php', {
            id: id
        })
        .done(function(data) {
            $(data).insertBefore('#more-grpcriteria');
            id++;
        });
    });
    </script>";

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
		'None' => '-------',
		'AND' => __("AND", "taskrules"),
		'OR' => __("OR", "taskrules"),
		'AND NOT' => __("AND NOT", "taskrules"),
		'OR NOT' => __("OR NOT", "taskrules"),
	];

    return $OPERATORS;
}

public static function getStatuses($itemtype) 
{
    global $DB;

    $values[self::NO_VALUE] = __('----------', 'taskrules');

    /** Retrieve object types data from the database */
    $itemtypecriteria = 'is_visible_'.$itemtype;
    $iterateur = $DB->request(['FIELDS' => ['glpi_states' => ['id', 'name']], 'FROM' => 'glpi_states', 'WHERE' => [$itemtypecriteria => 1]]);

    foreach ($iterateur as $data) {
        $values[$data['id']] = $data['name'];
    }

    asort($values);
    return $values;
}

/* Return an array of types from the Itemtype passed as parameter */
public static function getObjectTypes($selectedItemtype)
{
    global $DB;

    $values[self::NO_VALUE] = __('----------', 'taskrules');

    $OBJECTTYPES = [
        'Computer' => 'glpi_computertypes',
        'Printer' => 'glpi_printertypes',
        'Phone' => 'glpi_phonetypes',
        'Monitor' => 'glpi_monitortypes',
        'Cluster' => 'glpi_clustertypes',
        'Peripheral' => 'glpi_peripheraltypes',
        'NetworkEquipment' => 'glpi_networkequipmenttypes',
    ];

    if ( $selectedItemtype!="" ) {
        /** Retrieve object types data from the database */
        $iterateur = $DB->request(['FIELDS' => [$OBJECTTYPES[$selectedItemtype] => ['id', 'name']], 'FROM' => $OBJECTTYPES[$selectedItemtype]]);
        
        foreach ($iterateur as $data) {
            $values[$data['id']] = $data['name'];
        }
    }

    asort($values);
    return $values;
}

/**
    * Return all types
    *
    * @return an array which contains array(itemtype => itemtype name)
   **/
  public static function getItemtypes()
  {
      global $GLPI_TYPES;

      self::getTypesToInject();

      $values[self::NO_VALUE] = __('----------', 'taskrules');

      foreach ($GLPI_TYPES as $type => $connected) {
          if ( class_exists($type) ) {
              $instance = new $type();
              //If user has no right to create an object of this type, do not display type in the list
              if (!$instance->canCreate()) {
                  continue;
              }
              $name = call_user_func([$type, 'getTypeName']);
              $values[$type] = $name;
          }
      }
      asort($values);
      return $values;
  }

/**
 * Return all types that can be treated in the current plugin
 *
 * @return void
 */
public static function getTypesToInject(): void
{
    global $GLPI_TYPES;

    if (is_array($GLPI_TYPES) && count($GLPI_TYPES)) {
        // already populated
        return;
    }

    $GLPI_TYPES = [
        'Computer' => ['Computer'],
        'Peripheral' => ['Peripheral'],
        'Phone' => ['Phone'],
        'Printer' => ['Printer'],
        'Monitor' => ['Monitor'],
        'NetworkEquipment' => ['NetworkEquipment'],
    ];

}

public static function showOperatorsDropdown($id, $operator, $controlId) 
{
    echo '<div class="col-auto">';
    /* First dropdown : list of operators */
    $value = "None";
    //$cr = Dropdown::showFromArray('filter['.$id.']['.$name.']', self::getOperators(), [
    $cr = Dropdown::showFromArray($controlId, self::getOperators(), [
        'value' => $operator
    ]);
    echo '</div>';
}

public static function showItemtypesDropdown($id, $itemtype, $controlId) 
{
    echo '<div class="col-auto">';
        /* Second dropdown : list of itemtypes */
        $value = "None";
        //$cr = Dropdown::showFromArray('filter['.$id.']['.$name.']', self::getItemtypes(true), [
        $cr = Dropdown::showFromArray($controlId, self::getItemtypes(true), [
            'value' => $itemtype,
            'on_change' => "updateHidden$id(this.value)"
        ]);
    echo '<input type="hidden" id="itemtype'.$id.'" value="">';
    echo '</div>';
}

public static function showCriteriaDropdown($id, $itemtype, $controlId) 
{
    echo "controlId=".$controlId; //
    $criteria = [
		'None' => '-------',
        'Status' => __("Status", "taskrules"),
        'Type' => __("Type", "taskrules"),
    ];

    echo '<div class="col-auto">';
            /* Third dropdown : status or type */
            $value = "None";
            //$selectedItemtype = "";
            // $cr = Dropdown::showFromArray('filter['.$id.']['.$name.']', $criteria, [
            $cr = Dropdown::showFromArray($controlId, $criteria, [
                'value' => $itemtype,
                 'on_change' => "updateLastDropdown$id(this.value)"
            ]);
            /* Ajax code to reset the criteria dropdown */
            $selectId = str_replace("[", "_", $controlId);
            $selectId = "dropdown_".str_replace("]", "_", $selectId).$cr;
            echo Html::scriptBlock("
            function updateHidden$id(value) {
                var input = document.getElementById('itemtype".$id."');
                input.value = value;            
                $.ajax({
                    url: '".plugin::getWebDir('taskrules')."/ajax/resetcriteria.php',
                    type: 'GET',
                    data: { item_type: value },
                    success: function(response) {
                        $('#".$selectId."').html(response);
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

public static function showLastDropdown($id, $itemtype, $controlId) 
{
    echo '<div class="col-auto">';
            /* Fourth dropdown : list of statuses for the selected object type */
            $value = "None";
            //$cr = Dropdown::showFromArray('filter['.$id.']['.$name.']', self::getStatuses(''), [
            $cr = Dropdown::showFromArray($controlId, self::getStatuses(''), [
                'value' => $itemtype
            ]);

    /* Ajax code to update the last dropdown from the third dropdown selection */
    $selectId = str_replace("[", "_", $controlId);
    $selectId = "dropdown_".str_replace("]", "_", $selectId).$cr;
    echo Html::scriptBlock("
    function updateLastDropdown$id(criteria) {
        // Retrieve itemtype from stored value in hidden field
        var itemtype = document.getElementById('itemtype".$id."');
        $.ajax({
            url: '".plugin::getWebDir('taskrules')."/ajax/lastdropdown.php',
            type: 'GET',
            data: { criteria_Type: criteria, item_type: itemtype.value },
            success: function(response) {
                $('#".$selectId."').html(response);
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

}