<?php

require_once "models/get.model.php";
require_once "models/post.model.php";
require_once "models/connection.php";
require_once "models/put.model.php";

require_once "vendor/autoload.php";

use Firebase\JWT\JWT;

class PostController
{

	static public function postData($table, $data)
	{
		$response = PostModel::postData($table, $data);
		$return = new PostController();
		$return->fncResponse($response, null, null);
	}

	static public function postRegister($table, $data, $suffix)
	{
		if (isset($data["password_" . $suffix]) && $data["password_" . $suffix] != null) {

			// CORRECCIÓN: Uso de password_hash moderno en lugar de crypt() obsoleto
			$data["password_" . $suffix] = password_hash($data["password_" . $suffix], PASSWORD_BCRYPT);
			$response = PostModel::postData($table, $data);

			$return = new PostController();
			$return->fncResponse($response, null, $suffix);
		} else {
			$response = PostModel::postData($table, $data);
			if (isset($response["comment"]) && $response["comment"] == "The process was successful") {

				$response = GetModel::getDataFilter($table, "*", "email_" . $suffix, $data["email_" . $suffix], null, null, null, null);

				if (!empty($response)) {
					$token = Connection::jwt($response[0]->{"id_" . $suffix}, $response[0]->{"email_" . $suffix});

					// CORRECCIÓN: Usar variable de entorno para el secreto JWT
					$secret = getenv('JWT_SECRET') ?: throw new Exception("JWT_SECRET no configurado");
					$jwt = JWT::encode($token, $secret, 'HS256');

					$updateData = array(
						"token_" . $suffix => $jwt,
						"token_exp_" . $suffix => $token["exp"]
					);

					$update = PutModel::putData($table, $updateData, $response[0]->{"id_" . $suffix}, "id_" . $suffix);

					if (isset($update["comment"]) && $update["comment"] == "The process was successful") {
						$response[0]->{"token_" . $suffix} = $jwt;
						$response[0]->{"token_exp_" . $suffix} = $token["exp"];
						$return = new PostController();
						$return->fncResponse($response, null, $suffix);
					}
				}
			}
		}
	}

	static public function postLogin($table, $data, $suffix)
	{
		$response = GetModel::getDataFilter($table, "*", "email_" . $suffix, $data["email_" . $suffix], null, null, null, null);

		if (!empty($response)) {
			if ($response[0]->{"password_" . $suffix} != null) {

				// CORRECCIÓN: Uso de password_verify contra el hash almacenado
				if (password_verify($data["password_" . $suffix], $response[0]->{"password_" . $suffix})) {

					$token = Connection::jwt($response[0]->{"id_" . $suffix}, $response[0]->{"email_" . $suffix});

					// CORRECCIÓN: Usar variable de entorno
					$secret = getenv('JWT_SECRET') ?: throw new Exception("JWT_SECRET no configurado");
					$jwt = JWT::encode($token, $secret, 'HS256');

					$updateData = array(
						"token_" . $suffix => $jwt,
						"token_exp_" . $suffix => $token["exp"]
					);

					$update = PutModel::putData($table, $updateData, $response[0]->{"id_" . $suffix}, "id_" . $suffix);

					if (isset($update["comment"]) && $update["comment"] == "The process was successful") {
						$response[0]->{"token_" . $suffix} = $jwt;
						$response[0]->{"token_exp_" . $suffix} = $token["exp"];
						$return = new PostController();
						$return->fncResponse($response, null, $suffix);
					}
				} else {
					$return = new PostController();
					$return->fncResponse(null, "Wrong password", $suffix);
				}
			} else {
				// (Lógica de login con apps externas conservada)
				$token = Connection::jwt($response[0]->{"id_" . $suffix}, $response[0]->{"email_" . $suffix});
				$secret = getenv('JWT_SECRET') ?: throw new Exception("JWT_SECRET no configurado");
				$jwt = JWT::encode($token, $secret, 'HS256');

				$updateData = array(
					"token_" . $suffix => $jwt,
					"token_exp_" . $suffix => $token["exp"]
				);

				$update = PutModel::putData($table, $updateData, $response[0]->{"id_" . $suffix}, "id_" . $suffix);

				if (isset($update["comment"]) && $update["comment"] == "The process was successful") {
					$response[0]->{"token_" . $suffix} = $jwt;
					$response[0]->{"token_exp_" . $suffix} = $token["exp"];
					$return = new PostController();
					$return->fncResponse($response, null, $suffix);
				}
			}
		} else {
			$return = new PostController();
			$return->fncResponse(null, "Wrong email", $suffix);
		}
	}

	public function fncResponse($response, $error, $suffix)
	{
		if (!empty($response)) {
			if (isset($response[0]->{"password_" . $suffix})) {
				unset($response[0]->{"password_" . $suffix});
			}
			$json = array('status' => 200, 'results' => $response);
		} else {
			if ($error != null) {
				$json = array('status' => 400, "results" => $error);
			} else {
				$json = array('status' => 404, 'results' => 'Not Found', 'method' => 'post');
			}
		}
		echo json_encode($json, http_response_code($json["status"]));
	}
}
