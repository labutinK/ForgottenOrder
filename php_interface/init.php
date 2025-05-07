<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
use Bitrix\Main;

Bitrix\Main\Loader::registerNamespace('Local', $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib');


if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/handlers.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/handlers.php');
}

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/agents.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/agents.php');
}






