<?php

define('DIR', __DIR__);

/*=============================================
Depurar errores
=============================================*/

ini_set("display_errors",1);
ini_set("log_errors",1);
ini_set("error_log", DIR."/php_error_log");

require_once "controllers/template.controller.php";
require_once "controllers/curl.controller.php";

$index = new TemplateController();
$index -> index();


