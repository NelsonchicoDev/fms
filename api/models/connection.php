<?php
require_once __DIR__ . "/../config/env.php";
Env::load(__DIR__ . "/../../.env");

require_once __DIR__ . "/get.model.php";

// Si existe autoload de composer para Firebase JWT
if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Connection{

	private static $link = null;

	/*=============================================
	Información de la base de datos
	=============================================*/

	static public function infoDatabase(){
		$infoDB = array(
			"database" => getenv('DB_NAME') ?: "fms",
			"user" => getenv('DB_USER') ?: "root",
			"pass" => getenv('DB_PASS') ?: "",
            "host" => getenv('DB_HOST') ?: "localhost"
		);
		return $infoDB;
	}

	/*=============================================
	APIKEY
	=============================================*/

	static public function apikey(){
		return getenv('API_KEY') ?: "hdfhsdf3463463457dsfhjdfsgj45745fcgjfdgjr67";
	}

	/*=============================================
	Acceso público
	=============================================*/
	
	static public function publicAccess(){
		$tables = [""];
		return $tables;
	}

	/*=============================================
	Conexión a la base de datos (Singleton)
	=============================================*/

	static public function connect(){
		if (self::$link == null) {
			try{
				$info = self::infoDatabase();
				self::$link = new PDO(
					"mysql:host=".$info["host"].";dbname=".$info["database"],
					$info["user"], 
					$info["pass"]
				);
				self::$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$link->exec("set names utf8mb4");

			}catch(PDOException $e){
				// No mostrar el mensaje de error real en producción
				error_log("Database connection error: " . $e->getMessage());
				die("Error: No se pudo conectar a la base de datos.");
			}
		}
		return self::$link;
	}

	/*=============================================
	Validar existencia de una tabla en la bd
	=============================================*/

	static public function getColumnsData($table, $columns){
		$database = self::infoDatabase()["database"];
		$link = self::connect();

		// USO DE SENTENCIAS PREPARADAS PARA EVITAR SQL INJECTION
		$stmt = $link->prepare("SELECT COLUMN_NAME AS item FROM information_schema.columns WHERE table_schema = :db AND table_name = :table");
		$stmt->execute([':db' => $database, ':table' => $table]);
		$validate = $stmt->fetchAll(PDO::FETCH_OBJ);

		if(empty($validate)){
			return null;
		}else{
			if($columns[0] == "*"){
				array_shift($columns);
			}

			$sum = 0;
			foreach ($validate as $key => $value) {
				$sum += in_array($value->item, $columns);			
			}

			return $sum == count($columns) ? $validate : null;
		}
	}

	/*=============================================
	Generar Token de Autenticación
	=============================================*/

	static public function jwt($id, $email){
		$time = time();
		$token = array(
			"iat" =>  $time,
			"exp" => $time + (60*60*24), // 1 día
			"data" => [
				"id" => $id,
				"email" => $email
			]
		);
		return $token;
	}

	/*=============================================
	Validar el token de seguridad
	=============================================*/

	static public function tokenValidate($token, $table, $suffix){
		try {
			// Decodificación y validación criptográfica del JWT
			$secret = getenv('JWT_SECRET') ?: "dfhsdfg34dfchs4xgsrsdry46";
			$decoded = JWT::decode($token, new Key($secret, 'HS256'));
		} catch (\Exception $e) {
			return "no-auth"; // Token inválido o manipulado
		}

		/*=============================================
		Traemos el usuario de acuerdo al token
		=============================================*/
		$user = GetModel::getDataFilter($table, "token_exp_".$suffix, "token_".$suffix, $token, null, null, null, null);
		
		if(!empty($user)){
			$time = time();
			if($time < $user[0]->{"token_exp_".$suffix}){
				return "ok";
			}else{
				return "expired";
			}
		}else{
			return "no-auth";
		}
	}
}