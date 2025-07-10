<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 taskrules plugin for GLPI
 Copyright (C) 2018-2022 by the taskrules Development Team.

 https://github.com/InfotelGLPI/taskrules
 -------------------------------------------------------------------------

 LICENSE

 This file is part of taskrules.

 taskrules is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 taskrules is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with taskrules. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include("../../../inc/includes.php");

    global $DB;

    $config = new PluginTaskrulesCondition();
    //Session::checkRightsOr(PluginTaskrulesCondition::$rightname, [READ, UPDATE]);

    // Toolbox::logInFile("EdmondDebug","front/condition.form.php > _POST=".print_r($_POST,true)."\n");

    Toolbox::logInFile("EdmondDebug","front/condition.form.php > _POST=\n".formatArray($_POST)."\n");
    

    if (isset($_POST["add"]) || isset($_POST["save"]) ) {
        foreach ( $_POST as $type => $val)
        {
            //echo "<b>type</b>=$type <b>val</b>=$val<br>";


        }
        //$config->updateFields($data);
        //$config->update($_POST);
        Html::back();
    }else {
        Html::header(__('Taskrules', 'taskrules'), $_SERVER['PHP_SELF'], 'config', 'taskrules',);
        /* showForm() affiche seulement le formulaire */
        $config->showForm(1);

        /* display() affiche le formulaire ET les onglets */
        // $config->display($_GET);
    
        Html::footer();
    }

function formatArray($array, $prefix = '') {
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