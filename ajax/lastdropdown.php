<?php

include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

// Toolbox::logInFile("EdmondDebug","je passe dans ajax/lastdropdown.php et _GET vaut ".print_r($_GET,true)."\n");

/* Retrieve itemtype from ajax call */
$criteria_Type = $_GET['criteria_Type'];
$itemtype = $_GET['item_type'];

/* Fill the dropdown according to the chosen criteria */
if ( $criteria_Type == 'Type' )
    $arrValues = PluginTaskrulesCondition::getObjectTypes($itemtype);
if ( $criteria_Type == 'Status' )
    $arrValues = PluginTaskrulesCondition::getStatuses($itemtype);

/* Build the dropdown content */
foreach ( $arrValues as $id => $name) {
    echo "<option value='".$id."'>".$name."</option>";
}
