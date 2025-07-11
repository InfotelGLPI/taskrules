<?php
$AJAX_INCLUDE = 1;
include('../../../inc/includes.php');
header('Content-Type: text/html; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

Toolbox::logInFile("EdmondDebug","ajax/addgroupcriteria.php > _POST=\n".PluginTaskrulesCondition::formatArray($_POST)."\n");

/* Retrieve id value from ajax call */
$id = $_POST['id'] + 1;

PluginTaskrulesCondition::ShowGroupRow($id);
