<?php

include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

// Toolbox::logInFile("EdmondDebug","je passe dans ajax/resetcriteria.php et _GET vaut ".print_r($_GET,true)."\n");

$criteria = [
    'None' => '-------',
    'Status' => __("Status", "taskrules"),
    'Type' => __("Type", "taskrules"),
];

/* Build the dropdown content */
foreach ( $criteria as $id => $name) {
    echo "<option value='".$id."'>".$name."</option>";
}
