<?php

include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

Toolbox::logInFile("EdmondDebug","je passe dans ajax/typesdropdown.php et _GET vaut ".print_r($_GET,true)."\n");

/* Retrieve itemtype from ajax call */
$item_Type = $_GET['item_Type'];

/* Get all the tables for that itemtype */
$arrTypes = PluginTaskrulesCondition::getObjectTypes($item_Type);

/* Build the dropdown content */
foreach ( $arrTypes as $id => $name) {
    echo "<option value='".$id."'>".$name."</option>";
}
