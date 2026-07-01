<?php
/*=============================================
Mostrar y registrar errores de forma segura
=============================================*/
ini_set('display_errors', 0); // Desactivar en producción para no filtrar código
ini_set("log_errors", 1);
// Ruta relativa dinámica e independiente del SO
ini_set("error_log",  __DIR__ . "/php_error.log");

/*=============================================
CORS (Configuración controlada)
=============================================*/
// NOTA: Se recomienda cambiar '*' por los dominios permitidos en producción
$allowed_origin = getenv('CORS_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

// Manejo preflight para peticiones complejas (CORS OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

/*=============================================
Requerimientos y Entrada principal
=============================================*/
require_once "controllers/routes.controller.php";

$index = new RoutesController();
$index->index();
