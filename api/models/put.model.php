<?php

require_once "connection.php";
require_once "get.model.php";

class PutModel
{

	/*=============================================
	Peticion Put para editar datos de forma dinámica
	=============================================*/

	static public function putData($table, $data, $id, $nameId)
	{

		// 1. CORRECCIÓN: Saneamiento estricto de la tabla y la columna ID 
		// Esto evita que un atacante inyecte sentencias SQL a través de la URL
		$safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
		$safeNameId = preg_replace('/[^a-zA-Z0-9_]/', '', $nameId);

		/*=============================================
		Validar el ID
		=============================================*/
		$response = GetModel::getDataFilter($safeTable, $safeNameId, $safeNameId, $id, null, null, null, null);

		if (empty($response)) {
			return null;
		}

		/*=============================================
		Construcción segura del SET para el UPDATE
		=============================================*/
		$set = "";
		$safeData = [];

		foreach ($data as $key => $value) {
			// 2. CORRECCIÓN: Sanear las llaves del array de datos (columnas)
			// Garantiza que si se manda un JSON manipulado, las columnas sean válidas
			$safeKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);

			$set .= $safeKey . " = :" . $safeKey . ",";
			$safeData[$safeKey] = $value;
		}

		$set = substr($set, 0, -1);

		$sql = "UPDATE $safeTable SET $set WHERE $safeNameId = :$safeNameId";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		/*=============================================
		Vinculación de parámetros
		=============================================*/
		foreach ($safeData as $key => $value) {
			// 3. CORRECCIÓN: Usar bindValue en lugar de bindParam dentro de bucles foreach.
			// bindParam asigna por referencia, lo que puede causar bugs lógicos en PHP al iterar.
			$stmt->bindValue(":" . $key, $value, PDO::PARAM_STR);
		}

		$stmt->bindValue(":" . $safeNameId, $id, PDO::PARAM_STR);

		if ($stmt->execute()) {

			return array(
				"comment" => "The process was successful"
			);
		} else {

			// 4. CORRECCIÓN: Ocultar los detalles internos de la base de datos en producción.
			// Reemplazamos el 'return $link->errorInfo();' por un registro en el log interno.
			error_log("PutModel Error: " . json_encode($link->errorInfo()));
			return null;
		}
	}
}
