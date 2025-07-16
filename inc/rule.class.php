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
 * Class PluginTaskrulesRule
 */
class PluginTaskrulesRule extends CommonDBTM
{
    public static $rightname = 'plugin_taskrules_taskrules';

    public static $types     = ['Computer', 'Peripheral', 'Phone', 'Printer', 'Monitor', 'NetworkEquipment'];

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return _n('Task rule', 'Task rules', 2, 'taskrules');
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->getType() != 'TaskTemplate') {
            return;
        }

        $rule = new self();
        $rule->showForm($item->getID());

        return true;
    }

    public function showForm($ID, array $options = [])
    {
        echo Html::css(PLUGIN_TASKRULES_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.css");
        echo Html::script(PLUGIN_TASKRULES_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.js");


//        if (!$this->getFromDB($ID)) {
            $this->getEmpty();
        $cond = new PluginTaskrulesCondition();
        $cond->getEmpty();
//        }
//        Toolbox::logInfo($this);
        echo "<form name='form' method='post' action='" . $this->getFormURL() . "'>";
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr class='tab_bg_2'>\n";
        echo "<td class='top'>\n";
        echo Html::hidden('tasktemplates_id', ['value' => $ID]);
        echo Html::hidden('groupIndex', ['value' => 0]);
        echo Html::hidden('boolUpdStatuses', ['value' => 0]);

//        echo $JS = <<<JAVASCRIPT
//
//JAVASCRIPT;

        echo "<div id='accordion_rules'>";

        /* ---------------------- */
        /* "Parameters" section */
        /* ---------------------- */
        echo "<h3><a href='#'>" . _n("Parameter", "Parameters", 2, "taskrules") . "</a></h3>";
        $this->showParametersForm();

        /* ---------------------- */
        /* "Condition(s)" section */
        /* ---------------------- */
        echo "<h3><a href='#'>" . _n("Condition", "Conditions", 2, "taskrules") . "</a></h3>";
        $cond->showCriteriasForm();

        /* ----------------- */
        /* "Trigger" section */
        /* ----------------- */
        echo "<h3><a href='#'>" . _n("Trigger", "Triggers", 1, "taskrules") . "</a></h3>";
        $this->showTriggersForm();

        /* ------------------- */
        /* "Action(s)" section */
        /* ------------------- */
        echo "<h3><a href='#'>" . _n("Action", "Actions", 2, "taskrules") . "</a></h3>";
        $this->showActionsForm();

        echo "</div>";
        echo "<script>accordion_taskrules();</script>";

        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
        echo Html::submit(_sx('button', 'Save'), ['name' => 'save', 'class' => 'btn btn-primary']);
        echo "</td></tr>";

        echo "</table>\n";
        Html::closeForm();
        echo "</div>\n";

        Html::closeForm();

        return true;
    }

    public function showParametersForm()
    {

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Active", "taskrules");
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('active', $this->fields['active']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Prevent task creation on error", "taskrules");
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('preventcreation', $this->fields['preventcreation']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Prevent the task from changing its status (from \"to do\" to \"done\") in case of an error", "taskrules");
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('preventchange', $this->fields['preventchange']);
        echo "</td>";
        echo "</tr>";

        echo "</table>";
        echo "</div>";
    }


    public function showTriggersForm()
    {

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __("Activate when a task is added to the ticket", "taskrules");
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('trigger_creation', $this->fields['trigger_creation']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>";
        echo __('Trigger upon updating the task status from "to do" to "done"', "taskrules");
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo('trigger_update', $this->fields['trigger_update']);
        echo "</td>";
        echo "</tr>";


        echo "</table>";
        echo "</div>";
    }

    public function showActionsForm()
    {

        echo "<div>";
        echo "<table class='tab_cadre' width='100%'>";
        // echo '<div class="panel" style="display: flex;">';
        /** ATTENTION : NE PAS OUBLIER */
        /** POUR LES AFFECTATIONS AUX GROUPES, NE PAS INCLURE DANS LA LISTE DES GROUPES SELECTIONNABLES
         * CEUX DONT LA CONFIGURATION "PEUT CONTENIR DES ELEMENTS" EST A "NON"
         */
        echo '<div class="center">';
        echo "<div>" . __("(i) attention, n'apparaissent que les statuts communs aux objets sélectionnés dans les conditions", "taskrules") . "</div>";

        /** Créer une liste déroulante des statuts avec les valeurs suivantes :
         * Ne pas changer le statut (en dur)
         * Vider le statut (en dur)
         * Statut 1 (commun aux matériels listés dans les conditions)
         * Statut 2 (commun aux matériels listés dans les conditions)
         */
        $actionsStatuses = [
            Dropdown::EMPTY_VALUE => Dropdown::EMPTY_VALUE,
            'DoNotChange' => __("Keep current status", "taskrules"),
            'Empty' => __("Empty current status", "taskrules"),
        ];

        echo '<div class="col-auto">';
        // /* Action statuses dropdown */
        Dropdown::showFromArray("actionStatus", $actionsStatuses, [
            'id' => "actionStatus",
            'on_change' => "handleClick()",
        ]);


        $selectId = 'actionStatus';
        echo Html::scriptBlock('
        function handleClick() {
            var bStatus = document.getElementsByName("boolUpdStatuses")[0];
            console.log("bStatus.value=" + bStatus.value);
            if ( bStatus.value == 1 )
                return;
            const selectElement = document.getElementById("actionStatus");
            const selectedValue = selectElement.value;
            console.log("selectedValue=" + selectedValue);
            /* Get the comma separated list of itemtypes in the Condition(s) section */
            let values = "";
            document.querySelectorAll("select[name*=\'itemtype\']").forEach(function(select) {
                values += select.value + ",";
            });
            values = values.slice(0, -1);
            /* Ajax call to populate statuses belonging to itemtypes in the Condition(s) section */
            $.ajax({
                url: "' . plugin::getWebDir('taskrules') . '/ajax/getactionstatuses.php",
                type: "POST",
                data: { item_types: values },
                success: function(response) {
                    $("#' . $selectId . '").html(response);
                },
                error: function (xhr, status, error) {
                console.log(xhr);
                console.log(status);
                console.log(error);
                }
            });
            bStatus.value = 1;
        }
        ');

        echo '</div>';

        echo '<div class="list-group list-group-flush list-group-hoverable criteria-list pt-2" style="background-color: white; id="actiontable">
              <div class="list-group-item p-2 border-0 normalcriteria" id="row1">
                <div class="row g-1">
                  <div class="col-1"></div>
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
            </div>';

        echo "</table>";
        echo "</div>";
    }
}
