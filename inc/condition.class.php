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
$OPERATORS = [];

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
    echo "ID=$ID";

    $prof = new self();
    $prof->showForm($ID);

    return true;
}

public function showForm($ID, array $options = [])
    {
        Global $DB;

        $this->getFromDB($ID);

        //The configuration is not deletable
        $options['colspan'] = 1;
        
        echo "<div class='center'>";
        //echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";
        //echo "<form name='form' method='post' action='" . self::getFormURL() . "'>";
        echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL($this->getType()) . "'>";

        echo Html::hidden('id', ['value' => $ID]);

        /* ---------------------- */
        /* "Condition(s)" section */
        /* ---------------------- */
        echo '<button class="accordiontskrul">' . __("Condition(s)", "taskrules") . '</button>';

        /* Active (Yes/No) on top of the section */
        echo "<p>" . __("Active : ", "taskrules");
        $this::showSwitchField("actif", false);

        echo '<div class="search-form card card-sm mb-4">
        <div id="searchcriteria">
        <div class="list-group list-group-flush list-group-hoverable criteria-list pt-2" id="criteriatablecomputer">';

        /* Draw a row with all the dropdowns */
        $this::ShowCriteriaRow(1);

        echo "<a id='more-criteria' role='button' style='display: none;'>not shown</a>";

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

        echo "<br><br>";

        /* ----------------- */
        /* "Trigger" section */
        /* ----------------- */
        echo '<button class="accordiontskrul">' . __("Trigger", "taskrules") . '</button>';
        echo '<div class="panel" style="display: block;"><p>';
        echo '<input type="radio" class="-check-input" id="trigger" name="trigger"/>';
        echo '&nbsp;' . __("Activate when a task is added to the ticket", "taskrules") . '<br><br>';
        echo '<input type="radio" class="-check-input" id="trigger" name="trigger"/>';
        echo '&nbsp;' . __('Trigger upon updating the task status from "to do" to "done"', "taskrules") . '';
        echo '</p></div>';

        /* ------------------- */
        /* "Action(s)" section */
        /* ------------------- */
        echo '<button class="accordiontskrul">' . __("Action(s)", "taskrules") . '</button>';
        echo '<div class="panel" style="display: block;">';
        echo '<p>
            <label for="statut">Statut :</label>
            <select id="statut" name="statut">
                <option value="preparation">En préparation</option>
                <option value="autre">Autre</option>
            </select>

    <div class="form-row">
        <div class="form-group">
            <label for="lieu">Lieu :</label>
            <select id="lieu" name="lieu">
                <option value="petit_stock">Petit stock</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="form-group">
            <input type="checkbox" id="vider_lieu" name="vider_lieu">
            <label for="vider_lieu">Vider le champ Lieu</label>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="usager">Usager :</label>
            <select id="usager" name="usager">
                <option value="usager_du_materiel">Usager du matériel</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="form-group">
            <input type="checkbox" id="vider_usager" name="vider_usager">
            <label for="vider_usager">Vider le champ Usager</label>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="utilisateur">Utilisateur :</label>
            <select id="utilisateur" name="utilisateur">
                <option value="demandeur">Demandeur</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="form-group">
            <input type="checkbox" id="vider_utilisateur" name="vider_utilisateur">
            <label for="vider_utilisateur">Vider le champ Utilisateur</label>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="groupe">Groupe :</label>
            <select id="groupe" name="groupe">
                <option value="groupe_du_demandeur">Groupe du demandeur</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div class="form-group">
            <input type="checkbox" id="vider_groupe" name="vider_groupe">
            <label for="vider_groupe">Vider le champ Groupe</label>
        </div>
    </div>        
        
        </p>';
        echo '</div>';
    
        echo "</div>";

       $options['candel'] = false;
        $this->showFormButtons($options);

        echo "<input type='submit' name='submit' value='Sauvegarder'>";

        Html::closeForm();

        return true;
    }  

public static function ShowCriteriaRow($id)
{
    static $isInitialized = false;

    if (!$isInitialized) {
        global $OPERATORS;
        $OPERATORS = [
            'None' => '-------',
            'AND' => __("AND", "taskrules"),
            'OR' => __("OR", "taskrules"),
            'NOT' => __("NOT", "taskrules"),
        ];
        $isInitialized = true;
    }

    echo '
          <div class="list-group-item p-2 border-0 normalcriteria" id="searchrowcomputer">
             <div class="row g-1">
                <div class="col-auto">
                   <button class="btn btn-sm btn-icon btn-ghost-secondary remove-search-criteria" type="button" data-rowid="'.$id.'"
                   data-bs-toggle="tooltip" data-bs-placement="left"
                   title=' . __s('Delete a rule') . '">
                      <i class="ti ti-square-minus" alt="-"></i>
                   </button>
                </div>
                <div class="col-auto">';
            /* First dropdown : list of operators */
            $value = "None";
            $operator = "";
            $cr = Dropdown::showFromArray('operator_'.$id, $OPERATORS, [
                'value' => $operator
            ]);
    echo '</div>
    <div class="col-auto d-flex align-items-center">';

    echo Html::scriptBlock('$(document).on("click", ".remove-search-criteria", function() {
        // force removal of tooltip
        var tooltip = bootstrap.Tooltip.getInstance($(this)[0]);
        if (tooltip !== null) {
           tooltip.dispose();
        }
        var rowID = $(this).data("rowid");
        alert("rowID : " + rowID);
        $("#" + rowID).remove();
     });
                ');    

    echo "&nbsp;&nbsp;" . __("Object", "taskrules") . "&nbsp;&nbsp;";
    echo '</div>
    <div class="col-auto">';
            /* Second dropdown : list of itemtypes */
            $value = "None";
            $itemtype = "";
            $cr = Dropdown::showFromArray('operator_'.$id, self::getItemtypes(true), [
                'value' => $itemtype,
                'on_change' => "updateTypesDropdown$id(this.value)"
            ]);
    echo '</div>
    <div class="col-auto d-flex align-items-center">';
    echo "&nbsp;&nbsp;" . __("of the type", "taskrules") . "&nbsp;&nbsp;";
    echo '</div>
    <div class="col-auto">';
            /* Third dropdown : list of types for the selected itemtype */
            $value = "None";
            $selectedItemtype = "";
            $cr = Dropdown::showFromArray('type_'.$id, self::getObjectTypes($selectedItemtype), [
                'value' => $itemtype
            ]);
                    /* Ajax code to update the second dropdown from the first dropdown selection */
                    $selectId = "dropdown_type_$id$cr";
                    echo Html::scriptBlock("
                    function updateTypesDropdown$id(itemType) {
                        //alert('Appel ajax pour '+itemType);
                        $.ajax({
                            url: '".plugin::getWebDir('taskrules')."/ajax/typesdropdown.php',
                            type: 'GET',
                            data: { item_Type: itemType },
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
    echo '</div>
    <div class="col-auto d-flex align-items-center">';
    echo "&nbsp;&nbsp;" . __("of status", "taskrules") . "&nbsp;&nbsp;";
    echo '</div>
    <div class="col-auto">';
            /* Fourth dropdown : list of statuses for the selected object type */
            $value = "None";
            $cr = Dropdown::showFromArray('type_'.$id, self::getStatuses(), [
                'value' => $itemtype
            ]);

    echo '</div>
    </div>
 </div>
 <a class="normalcriteria fold-search list-group-item p-2 border-0 headerRow"></a>
</div>';

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

public static function getStatuses() 
{
    global $DB;

    $values[self::NO_VALUE] = __('----------', 'taskrules');

    /** Retrieve object types data from the database */
    $iterateur = $DB->request(['FIELDS' => ['glpi_states' => ['id', 'name']], 'FROM' => 'glpi_states']);
    
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
 * Return all types that can be injected using datainjection
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


}