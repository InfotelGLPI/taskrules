<?php

include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

/* Retrieve itemtype from ajax call */
$item_Type = $_GET['item_Type'];

/* Get all the tables for that itemtype */
$arrTypes = PluginTaskrulesCondition::getObjectTypes($item_Type);

/* Build the dropdown content */
foreach ( $arrTypes as $id => $name) {
    echo "<option value='".$id."'>".$name."</option>";
}

