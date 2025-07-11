<?php
$AJAX_INCLUDE = 1;
include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

Toolbox::logInFile("EdmondDebug","je passe dans ajax/addcriteria.php > _POST=\n".PluginTaskrulesCondition::formatArray($_POST)."\n");

/* Retrieve id value from ajax call */
$id = $_POST['id'] + 1;
$groupIndex = $_POST['groupIndex'];

Toolbox::logInFile("EdmondDebug","Nouveau groupIndex=\n".$groupIndex."\n");

PluginTaskrulesCondition::ShowCriteriaRow($id,$groupIndex);