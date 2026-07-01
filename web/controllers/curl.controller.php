<?php

class CurlController
{

	// NOTA: Es ideal que esta base y el token vengan de un archivo de configuración o variables de entorno (.env)
	const API_BASE_URL = 'http://localhost/tu_api_ruta'; // CAMBIAR POR TU URL REAL
	const API_TOKEN = 'hdfhsdf3463463457dsfhjdfsgj45745fcgjfdgjr67'; // MOVER A CONFIGURACIÓN

	/*=============================================
	Peticiones a la API propia
	=============================================*/
	static public function request($url, $method, $fields = array())
	{

		$curl = curl_init();

		// Construir URL completa
		$fullUrl = self::API_BASE_URL . '/' . ltrim($url, '/');

		curl_setopt_array($curl, array(
			CURLOPT_URL => $fullUrl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30, // Añadido timeout real
			CURLOPT_SSL_VERIFYHOST => 2, // SEGURIDAD: Habilitar validación SSL (usar 2)
			CURLOPT_SSL_VERIFYPEER => true, // SEGURIDAD: Habilitar validación SSL
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_HTTPHEADER => array(
				'Authorization: ' . self::API_TOKEN
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		// Manejo de errores de conexión
		if ($err) {
			// Registrar el error en logs y devolver un objeto de error estructurado
			error_log("cURL Error en request(): " . $err);
			return (object) [
				"status" => 500,
				"results" => [],
				"error" => "Error de conexión con la API interna."
			];
		}

		$decodedResponse = json_decode($response);

		// Validar que la respuesta sea un JSON válido
		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log("cURL Error JSON: La API no devolvió un JSON válido. Respuesta: " . $response);
			return (object) [
				"status" => 500,
				"results" => [],
				"error" => "Respuesta malformada de la API."
			];
		}

		return $decodedResponse;
	}

	/*=============================================
	Peticiones a la API DE VIMEO
	=============================================*/
	static public function getThumbnailVimeo($idVimeo)
	{

		// Sanitizar el ID para evitar inyección en la URL
		$idVimeo = urlencode(strip_tags(trim($idVimeo)));

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://vimeo.com/api/v2/video/' . $idVimeo . '.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 5,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			// Se eliminaron las cookies hardcodeadas. La API v2 pública de Vimeo no las requiere.
			CURLOPT_HTTPHEADER => array(
				'Accept: application/json'
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			error_log("cURL Error en getThumbnailVimeo(): " . $err);
			return null; // o una imagen de placeholder por defecto
		}

		$decodedResponse = json_decode($response);

		// Validar existencia de los datos antes de acceder al índice
		if (is_array($decodedResponse) && isset($decodedResponse[0]->thumbnail_medium)) {
			return $decodedResponse[0]->thumbnail_medium;
		}

		// Si falla o no encuentra el video, devolver null
		error_log("cURL Error Vimeo: No se encontró el thumbnail para el ID: " . $idVimeo);
		return null;
	}
}
