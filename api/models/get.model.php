<?php
require_once "connection.php";

class GetModel{

	/*=============================================
	Helper para sanitizar ORDER BY y LIMIT
	=============================================*/
	private static function getSuffix($orderBy, $orderMode, $startAt, $endAt) {
		$sql = "";
		if($orderBy != null && $orderMode != null){
			// Sanitizar orderMode (solo ASC o DESC)
			$orderMode = strtoupper($orderMode) === 'DESC' ? 'DESC' : 'ASC';
			// Sanitizar orderBy (solo alfanumérico y guion bajo)
			$orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
			$sql .= " ORDER BY $orderBy $orderMode";
		}
		if($startAt != null && $endAt != null){
			// Forzar a enteros
			$startAt = (int)$startAt;
			$endAt = (int)$endAt;
			$sql .= " LIMIT $startAt, $endAt";
		}
		return $sql;
	}

	/*=============================================
	Helper para construir INNER JOIN seguros
	=============================================*/
	private static function getInnerJoins($rel, $type) {
		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		if(count($relArray) > 1){
			foreach ($relArray as $key => $value) {
				if(empty(Connection::getColumnsData($value, ["*"]))) return null;
				if($key > 0){
					// Sanitizar nombres de tablas y columnas de cruce
					$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);
					$t1 = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
					$colKey = preg_replace('/[^a-zA-Z0-9_]/', '', $typeArray[$key]);
					$col0 = preg_replace('/[^a-zA-Z0-9_]/', '', $typeArray[0]);
					
					$innerJoinText .= " INNER JOIN $t1 ON $t0.id_{$colKey}_{$col0} = $t1.id_{$colKey}";
				}
			}
		}
		return $innerJoinText;
	}

	/*=============================================
	Peticiones GET sin filtro
	=============================================*/
	static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt){
		if(empty(Connection::getColumnsData($table, explode(",",$select)))) return null;
		
		$sql = "SELECT $select FROM $table" . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET con filtro
	=============================================*/
	static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt){
		$linkToArray = explode(",", $linkTo);
		$equalToArray = explode(",", $equalTo);
		$selectArray = array_unique(array_merge(explode(",",$select), $linkToArray));

		if(empty(Connection::getColumnsData($table, $selectArray))) return null;
		
		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			$whereClauses[] = "$col = :val_$key";
		}
		
		$sql = "SELECT $select FROM $table WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		foreach ($linkToArray as $key => $value) {
			$stmt->bindParam(":val_$key", $equalToArray[$key], PDO::PARAM_STR);
		}
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET sin filtro entre tablas relacionadas
	=============================================*/
	static public function getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt){
		$innerJoinText = self::getInnerJoins($rel, $type);
		if($innerJoinText === null) return null;
		
		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$sql = "SELECT $select FROM $t0 $innerJoinText" . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET con filtro entre tablas relacionadas
	=============================================*/
	static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt){
		$innerJoinText = self::getInnerJoins($rel, $type);
		if($innerJoinText === null) return null;
		
		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$linkToArray = explode(",", $linkTo);
		$equalToArray = explode(",", $equalTo);
		
		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			$whereClauses[] = "$col = :val_$key";
		}

		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		foreach ($linkToArray as $key => $value) {
			$stmt->bindParam(":val_$key", $equalToArray[$key], PDO::PARAM_STR);
		}
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET para el buscador sin relaciones
	=============================================*/
	static public function getDataSearch($table, $select, $linkTo, $search, $orderBy, $orderMode, $startAt, $endAt){
		$linkToArray = explode(",", $linkTo);
		$searchArray = explode(",", $search);
		$selectArray = array_unique(array_merge(explode(",",$select), $linkToArray));

		if(empty(Connection::getColumnsData($table, $selectArray))) return null;
		
		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			if($key == 0) {
				$whereClauses[] = "$col LIKE :search_0";
			} else {
				$whereClauses[] = "$col = :val_$key";
			}
		}

		$sql = "SELECT $select FROM $table WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		$searchTerm = '%' . $searchArray[0] . '%';
		$stmt->bindParam(":search_0", $searchTerm, PDO::PARAM_STR);
		
		foreach ($linkToArray as $key => $value) {
			if($key > 0){
				$stmt->bindParam(":val_$key", $searchArray[$key], PDO::PARAM_STR);
			}
		}
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET para buscador con relaciones
	=============================================*/
	static public function getRelDataSearch($rel, $type, $select, $linkTo, $search, $orderBy, $orderMode, $startAt, $endAt){
		$innerJoinText = self::getInnerJoins($rel, $type);
		if($innerJoinText === null) return null;
		
		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$linkToArray = explode(",", $linkTo);
		$searchArray = explode(",", $search);
		
		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			if($key == 0) {
				$whereClauses[] = "$col LIKE :search_0";
			} else {
				$whereClauses[] = "$col = :val_$key";
			}
		}

		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		$searchTerm = '%' . $searchArray[0] . '%';
		$stmt->bindParam(":search_0", $searchTerm, PDO::PARAM_STR);
		
		foreach ($linkToArray as $key => $value) {
			if($key > 0){
				$stmt->bindParam(":val_$key", $searchArray[$key], PDO::PARAM_STR);
			}
		}
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET para selección de rangos
	=============================================*/
	static public function getDataRange($table, $select, $linkTo, $between1, $between2, $orderBy, $orderMode, $startAt, $endAt, $filterTo, $inTo){
		$linkToArray = explode(",", $linkTo);
		$filterToArray = $filterTo != null ? explode(",", $filterTo) : [];
		$selectArray = array_unique(array_merge(explode(",",$select), $linkToArray, $filterToArray));

		if(empty(Connection::getColumnsData($table, $selectArray))) return null;
		
		$colLink = preg_replace('/[^a-zA-Z0-9_]/', '', $linkTo);
		$sql = "SELECT $select FROM $table WHERE $colLink BETWEEN :b1 AND :b2";
		
		if($filterTo != null && $inTo != null){
			$colFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $filterTo);
			// Validate IN values are alphanumeric or safe to avoid injection
			$inArray = explode(",", $inTo);
			$safeIn = [];
			foreach($inArray as $val) {
				$safeIn[] = preg_replace('/[^a-zA-Z0-9_]/', '', $val);
			}
			$sql .= " AND $colFilter IN ('" . implode("','", $safeIn) . "')";
		}
		
		$sql .= self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		$stmt->bindParam(":b1", $between1, PDO::PARAM_STR);
		$stmt->bindParam(":b2", $between2, PDO::PARAM_STR);
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}

	/*=============================================
	Peticiones GET para selección de rangos con relaciones
	=============================================*/
	static public function getRelDataRange($rel, $type, $select, $linkTo, $between1, $between2, $orderBy, $orderMode, $startAt, $endAt, $filterTo, $inTo){
		$innerJoinText = self::getInnerJoins($rel, $type);
		if($innerJoinText === null) return null;
		
		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$colLink = preg_replace('/[^a-zA-Z0-9_]/', '', $linkTo);
		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE $colLink BETWEEN :b1 AND :b2";
		
		if($filterTo != null && $inTo != null){
			$colFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $filterTo);
			$inArray = explode(",", $inTo);
			$safeIn = [];
			foreach($inArray as $val) {
				$safeIn[] = preg_replace('/[^a-zA-Z0-9_]/', '', $val);
			}
			$sql .= " AND $colFilter IN ('" . implode("','", $safeIn) . "')";
		}
		
		$sql .= self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);
		
		$stmt->bindParam(":b1", $between1, PDO::PARAM_STR);
		$stmt->bindParam(":b2", $between2, PDO::PARAM_STR);
		
		try{ $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_CLASS); }
		catch(PDOException $e){ return null; }
	}
}
