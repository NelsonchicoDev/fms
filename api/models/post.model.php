<?php 

require_once "connection.php";

class PostModel{

	/*=============================================
	Peticion POST para crear datos de forma dinámica
	=============================================*/

	static public function postData($table, $data){

		/*=============================================
		Validar existencia de la tabla y de las columnas
		=============================================*/
		$columnsArray = array_keys($data);
		if(empty(Connection::getColumnsData($table, $columnsArray))){
			return null;
		}

		$columns = "";
		$params = "";

		foreach ($data as $key => $value) {
			$columns .= $key.",";
			$params .= ":".$key.",";
		}

		$columns = substr($columns, 0, -1);
		$params = substr($params, 0, -1);

		// Sanitizar nombre de tabla (ya validada por getColumnsData, pero por defensa en profundidad)
		$safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

		$sql = "INSERT INTO $safeTable ($columns) VALUES ($params)";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		foreach ($data as $key => $value) {
			$stmt->bindParam(":".$key, $data[$key], PDO::PARAM_STR);
		}

		if($stmt -> execute()){
			$response = array(
				"lastId" => $link->lastInsertId(),
				"comment" => "The process was successful"
			);
			return $response;
		}else {
			// No exponer información interna de errores de BD en producción
			error_log("PostModel Error: " . json_encode($link->errorInfo()));
			return null;
		}

	}

}