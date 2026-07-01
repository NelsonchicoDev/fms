<?php

/*=============================================
1. Configuración Segura de Variables de Sesión
=============================================*/
// Mitigación de XSS (Impide acceso a la cookie desde JS)
ini_set('session.cookie_httponly', 1);
// Evita ID de sesión en la URL
ini_set('session.use_only_cookies', 1);
// Mitigación CSRF
ini_set('session.cookie_samesite', 'Strict');

ob_start();
session_start();

/*=============================================
2. Zona Horaria
=============================================*/
date_default_timezone_set("America/Santiago");

/*=============================================
3. Capturar y procesar las rutas de la URL (Optimizado)
=============================================*/
// Extraemos la ruta ignorando los parámetros GET de forma nativa
$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$routesArray = explode("/", trim($requestUri, "/"));

// Definimos la página actual o 'home' por defecto
$currentPage = !empty($routesArray[0]) ? $routesArray[0] : 'home';

// Lista blanca de páginas que NO usan el layout principal (Top, Content, Up, Modal)
$validIndependentPages = ['logout'];

?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FMS | File Manager System</title>
	<link rel="icon" href="https://cdn.filestackcontent.com/1LIBQrPFRuGylthi5tUp">

	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,600,500,700">
	<link rel="stylesheet" href="/views/assets/plugins/bootstrap5/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" href="/views/assets/plugins/fontawesome-free/css/all.min.css">
	<link rel="stylesheet" href="/views/assets/plugins/jquery-ui/jquery-ui.css">
	<link rel="stylesheet" href="/views/assets/plugins/material-preloader/material-preloader.css">
	<link rel="stylesheet" href="/views/assets/plugins/toastr/toastr.min.css">
	<link rel="stylesheet" href="/views/assets/css/fms/fms.css">

	<script src="/views/assets/plugins/jquery/jquery.min.js"></script>
	<script src="/views/assets/plugins/jquery-ui/jquery-ui.js"></script>
	<script src="/views/assets/plugins/bootstrap5/bootstrap.bundle.min.js"></script>
	<script src="/views/assets/js/alerts/alerts.js"></script>
	<script src="/views/assets/plugins/sweetalert/sweetalert.min.js"></script>
	<script src="/views/assets/plugins/material-preloader/material-preloader.js"></script>
	<script src="/views/assets/plugins/toastr/toastr.min.js"></script>
</head>

<body>

	<?php
	/*=============================================
	4. Controlador Lógico del Layout (Enrutamiento Seguro y DRY)
	=============================================*/
	if (in_array($currentPage, $validIndependentPages)) {

		// SEGURIDAD: basename() previene ataques de Directory Traversal (LFI)
		$safePage = basename($currentPage);
		include "pages/{$safePage}/{$safePage}.php";
	} else {

		// Carga del Layout Principal (Evitamos repetir este bloque de código)
		include "modules/top/top.php";
		include "modules/content/content.php";
		include "modules/up/up.php";
		include "modules/modal/modal.php";
	}
	?>

	<?php if (!in_array($currentPage, $validIndependentPages)): ?>
		<script src="/views/assets/js/fms/fms.js"></script>
	<?php endif; ?>

</body>

</html>