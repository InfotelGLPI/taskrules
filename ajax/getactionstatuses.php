<?php

include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

// Toolbox::logInFile("EdmondDebug","je passe dans ajax/getactionstatuses.php et _GET vaut ".print_r($_GET,true)."\n");

/* Retrieve item_types from ajax call */
$item_types = $_GET['item_types']; // Example : [item_types] => Printer,NetworkEquipment,Computer

$arrValues = PluginTaskrulesCondition::getStatuses($item_types);

// Toolbox::logInFile("EdmondDebug","je passe dans ajax/getactionstatuses.php et arrValues vaut ".print_r($arrValues,true)."\n");

/* Build the dropdown content */
foreach ( $arrValues as $id => $name) {
    echo "<option value='".$id."'>".$name."</option>";
}
