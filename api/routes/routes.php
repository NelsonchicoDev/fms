<?php

require_once "models/connection.php";
require_once "controllers/get.controller.php";

$routesArray = explode("/", parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
array_shift($routesArray);
$routesArray = array_filter($routesArray);

if (count($routesArray) == 0) {
	$json = array('status' => 404, 'results' => 'Not Found');
	echo json_encode($json, http_response_code($json["status"]));
	return;
}

if (count($routesArray) > 0 && isset($_SERVER['REQUEST_METHOD'])) {

	// CORRECCIÓN: Sanear estrictamente el nombre de la tabla para evitar inyecciones
	$table = preg_replace('/[^a-zA-Z0-9_]/', '', explode("?", $routesArray[1])[0]);

	// CORRECCIÓN: Captura segura de headers (compatible con Nginx/Apache)
	$headers = getallheaders();
	$authHeader = $headers["Authorization"] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

	if (!$authHeader || $authHeader != Connection::apikey()) {

		if (in_array($table, Connection::publicAccess()) == 0) {
			$json = array('status' => 400, "results" => "You are not authorized to make this request");
			echo json_encode($json, http_response_code($json["status"]));
			return;
		} else {
			$response = new GetController();
			$response->getData($table, "*", null, null, null, null);
			return;
		}
	}

	// Archivos de rutas específicas (GET, POST, PUT, DELETE)
	$method = $_SERVER['REQUEST_METHOD'];
	$validMethods = ['GET', 'POST', 'PUT', 'DELETE'];

	if (in_array($method, $validMethods)) {
		include "services/" . strtolower($method) . ".php";
	}
}
