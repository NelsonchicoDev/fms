<?php

require_once "connection.php";
require_once "get.model.php";

class DeleteModel
{

	static public function deleteData($table, $id, $nameId)
	{
		// CORRECCIÓN: Sanear el nombre de la tabla y columna ID antes de inyectarlo
		$safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
		$safeNameId = preg_replace('/[^a-zA-Z0-9_]/', '', $nameId);

		$response = GetModel::getDataFilter($safeTable, $safeNameId, $safeNameId, $id, null, null, null, null);

		if (empty($response)) {
			return null;
		}

		$sql = "DELETE FROM $safeTable WHERE $safeNameId = :id";

		$link = Connection::connect();
		$stmt = $link->prepare($sql);

		$stmt->bindParam(":id", $id, PDO::PARAM_STR);

		if ($stmt->execute()) {
			return array("comment" => "The process was successful");
		} else {
			// CORRECCIÓN: No retornar errorInfo en producción para evitar fuga de información
			error_log("DB Error on delete: " . json_encode($link->errorInfo()));
			return array("error" => "Error interno al eliminar");
		}
	}
}
