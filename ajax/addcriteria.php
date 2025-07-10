<?php
$AJAX_INCLUDE = 1;
include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

// Toolbox::logInFile("EdmondDebug","je passe dans ajax/addcriteria.php et _GET vaut ".print_r($_POST,true)."\n");

/* Retrieve id value from ajax call */
$id = $_POST['id'] + 1;

PluginTaskrulesCondition::ShowCriteriaRow($id);