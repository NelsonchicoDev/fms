<?php
require_once __DIR__ . "/../config/env.php";
Env::load(__DIR__ . "/../../.env");

require_once __DIR__ . "/get.model.php";

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
	require_once __DIR__ . "/../vendor/autoload.php";
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Connection
{

	private static $link = null;

	static public function infoDatabase()
	{
		return array(
			"database" => getenv('DB_NAME') ?: "fms",
			"user"     => getenv('DB_USER') ?: "root",
			"pass"     => getenv('DB_PASS') ?: "",
			"host"     => getenv('DB_HOST') ?: "localhost"
		);
	}

	static public function apikey()
	{
		$key = getenv('API_KEY');
		if (!$key) {
			error_log("CRITICAL: API_KEY no está definida en el entorno.");
			// En vez de exponer una clave por defecto, cerramos con error de servidor
			throw new Exception("Configuración de servidor incompleta.");
		}
		return $key;
	}

	static public function publicAccess()
	{
		return [""];
	}

	static public function connect()
	{
		if (self::$link == null) {
			try {
				$info = self::infoDatabase();
				self::$link = new PDO(
					"mysql:host=" . $info["host"] . ";dbname=" . $info["database"],
					$info["user"],
					$info["pass"],
					[
						// Optimizaciones modernas de PDO
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
						PDO::ATTR_EMULATE_PREPARES => false // Seguridad extra contra SQLi
					]
				);
				self::$link->exec("set names utf8mb4");
			} catch (PDOException $e) {
				error_log("Database connection error: " . $e->getMessage());
				// Devolver siempre formato JSON válido en APIs
				header('Content-Type: application/json');
				http_response_code(500);
				echo json_encode(["status" => 500, "results" => "Error interno de base de datos."]);
				exit();
			}
		}
		return self::$link;
	}

	static public function getColumnsData($table, $columns)
	{
		$database = self::infoDatabase()["database"];
		$link = self::connect();

		$stmt = $link->prepare("SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = :db AND table_name = :table");
		$stmt->bindValue(':db', $database, PDO::PARAM_STR);
		$stmt->bindValue(':table', $table, PDO::PARAM_STR);
		$stmt->execute();
		$validate = $stmt->fetchAll(PDO::FETCH_OBJ);

		if (empty($validate)) {
			return null;
		} else {
			if ($columns[0] == "*") {
				array_shift($columns);
			}

			$sum = 0;
			foreach ($validate as $key => $value) {
				$sum += in_array($value->item, $columns, true);	// true para validación estricta		
			}

			return $sum == count($columns) ? $validate : null;
		}
	}

	static public function jwt($id, $email)
	{
		$time = time();
		$token = array(
			"iat" =>  $time,
			"exp" => $time + (60 * 60 * 24), // 1 día
			"data" => [
				"id" => $id,
				"email" => $email
			]
		);
		return $token;
	}

	static public function tokenValidate($token, $table, $suffix)
	{
		$secret = getenv('JWT_SECRET');
		if (!$secret) {
			error_log("CRITICAL: JWT_SECRET no configurado.");
			return "no-auth";
		}

		try {
			$decoded = JWT::decode($token, new Key($secret, 'HS256'));
		} catch (\Exception $e) {
			return "no-auth";
		}

		$user = GetModel::getDataFilter($table, "token_exp_" . $suffix, "token_" . $suffix, $token, null, null, null, null);

		if (!empty($user)) {
			$time = time();
			if ($time < $user[0]->{"token_exp_" . $suffix}) {
				return "ok";
			} else {
				return "expired";
			}
		} else {
			return "no-auth";
		}
	}
}
