<?php
require_once __DIR__ . "/connection.php";

class GetModel
{

	private static function getSuffix($orderBy, $orderMode, $startAt, $endAt)
	{
		$sql = "";
		if ($orderBy != null && $orderMode != null) {
			$orderMode = strtoupper($orderMode) === 'DESC' ? 'DESC' : 'ASC';
			$orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
			$sql .= " ORDER BY $orderBy $orderMode";
		}
		if ($startAt != null && $endAt != null) {
			$startAt = (int)$startAt;
			$endAt = (int)$endAt;
			$sql .= " LIMIT $startAt, $endAt";
		}
		return $sql;
	}

	private static function getInnerJoins($rel, $type)
	{
		$relArray = explode(",", $rel);
		$typeArray = explode(",", $type);
		$innerJoinText = "";

		// Validar que ambos arrays tengan el mismo tamaño
		if (count($relArray) !== count($typeArray)) return null;

		if (count($relArray) > 1) {
			foreach ($relArray as $key => $value) {
				if (empty(Connection::getColumnsData($value, ["*"]))) return null;
				if ($key > 0) {
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

	static public function getData($table, $select, $orderBy, $orderMode, $startAt, $endAt)
	{
		if (empty(Connection::getColumnsData($table, explode(",", $select)))) return null;

		$sql = "SELECT $select FROM $table" . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getDataFilter($table, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt)
	{
		$linkToArray = explode(",", $linkTo);
		$equalToArray = explode(",", $equalTo);

		if (count($linkToArray) !== count($equalToArray)) return null;

		$selectArray = array_unique(array_merge(explode(",", $select), $linkToArray));
		if (empty(Connection::getColumnsData($table, $selectArray))) return null;

		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			$whereClauses[] = "$col = :val_$key";
		}

		$sql = "SELECT $select FROM $table WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		foreach ($linkToArray as $key => $value) {
			// bindValue en lugar de bindParam
			$stmt->bindValue(":val_$key", $equalToArray[$key], PDO::PARAM_STR);
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getRelData($rel, $type, $select, $orderBy, $orderMode, $startAt, $endAt)
	{
		$innerJoinText = self::getInnerJoins($rel, $type);
		if ($innerJoinText === null) return null;

		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$sql = "SELECT $select FROM $t0 $innerJoinText" . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getRelDataFilter($rel, $type, $select, $linkTo, $equalTo, $orderBy, $orderMode, $startAt, $endAt)
	{
		$innerJoinText = self::getInnerJoins($rel, $type);
		if ($innerJoinText === null) return null;

		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$linkToArray = explode(",", $linkTo);
		$equalToArray = explode(",", $equalTo);

		if (count($linkToArray) !== count($equalToArray)) return null;

		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			$whereClauses[] = "$col = :val_$key";
		}

		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		foreach ($linkToArray as $key => $value) {
			$stmt->bindValue(":val_$key", $equalToArray[$key], PDO::PARAM_STR);
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getDataSearch($table, $select, $linkTo, $search, $orderBy, $orderMode, $startAt, $endAt)
	{
		$linkToArray = explode(",", $linkTo);
		$searchArray = explode(",", $search);

		if (count($linkToArray) !== count($searchArray)) return null;

		$selectArray = array_unique(array_merge(explode(",", $select), $linkToArray));
		if (empty(Connection::getColumnsData($table, $selectArray))) return null;

		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			if ($key == 0) {
				$whereClauses[] = "$col LIKE :search_0";
			} else {
				$whereClauses[] = "$col = :val_$key";
			}
		}

		$sql = "SELECT $select FROM $table WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		$searchTerm = '%' . $searchArray[0] . '%';
		$stmt->bindValue(":search_0", $searchTerm, PDO::PARAM_STR);

		foreach ($linkToArray as $key => $value) {
			if ($key > 0) {
				$stmt->bindValue(":val_$key", $searchArray[$key], PDO::PARAM_STR);
			}
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getRelDataSearch($rel, $type, $select, $linkTo, $search, $orderBy, $orderMode, $startAt, $endAt)
	{
		$innerJoinText = self::getInnerJoins($rel, $type);
		if ($innerJoinText === null) return null;

		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$linkToArray = explode(",", $linkTo);
		$searchArray = explode(",", $search);

		if (count($linkToArray) !== count($searchArray)) return null;

		$whereClauses = [];
		foreach ($linkToArray as $key => $value) {
			$col = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
			if ($key == 0) {
				$whereClauses[] = "$col LIKE :search_0";
			} else {
				$whereClauses[] = "$col = :val_$key";
			}
		}

		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE " . implode(" AND ", $whereClauses) . self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		$searchTerm = '%' . $searchArray[0] . '%';
		$stmt->bindValue(":search_0", $searchTerm, PDO::PARAM_STR);

		foreach ($linkToArray as $key => $value) {
			if ($key > 0) {
				$stmt->bindValue(":val_$key", $searchArray[$key], PDO::PARAM_STR);
			}
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getDataRange($table, $select, $linkTo, $between1, $between2, $orderBy, $orderMode, $startAt, $endAt, $filterTo, $inTo)
	{
		$linkToArray = explode(",", $linkTo);
		$filterToArray = $filterTo != null ? explode(",", $filterTo) : [];
		$selectArray = array_unique(array_merge(explode(",", $select), $linkToArray, $filterToArray));

		if (empty(Connection::getColumnsData($table, $selectArray))) return null;

		$colLink = preg_replace('/[^a-zA-Z0-9_]/', '', $linkTo);
		$sql = "SELECT $select FROM $table WHERE $colLink BETWEEN :b1 AND :b2";

		$inArray = [];
		if ($filterTo != null && $inTo != null) {
			$colFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $filterTo);
			$inArray = explode(",", $inTo);

			// CORRECCIÓN CRÍTICA: Uso seguro de IN() con placeholders dinámicos
			$placeholders = [];
			foreach ($inArray as $k => $v) {
				$placeholders[] = ":in_$k";
			}
			$sql .= " AND $colFilter IN (" . implode(",", $placeholders) . ")";
		}

		$sql .= self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		$stmt->bindValue(":b1", $between1, PDO::PARAM_STR);
		$stmt->bindValue(":b2", $between2, PDO::PARAM_STR);

		// Vincular valores de IN()
		if (!empty($inArray)) {
			foreach ($inArray as $k => $v) {
				$stmt->bindValue(":in_$k", $v, PDO::PARAM_STR);
			}
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}

	static public function getRelDataRange($rel, $type, $select, $linkTo, $between1, $between2, $orderBy, $orderMode, $startAt, $endAt, $filterTo, $inTo)
	{
		$innerJoinText = self::getInnerJoins($rel, $type);
		if ($innerJoinText === null) return null;

		$relArray = explode(",", $rel);
		$t0 = preg_replace('/[^a-zA-Z0-9_]/', '', $relArray[0]);

		$colLink = preg_replace('/[^a-zA-Z0-9_]/', '', $linkTo);
		$sql = "SELECT $select FROM $t0 $innerJoinText WHERE $colLink BETWEEN :b1 AND :b2";

		$inArray = [];
		if ($filterTo != null && $inTo != null) {
			$colFilter = preg_replace('/[^a-zA-Z0-9_]/', '', $filterTo);
			$inArray = explode(",", $inTo);

			$placeholders = [];
			foreach ($inArray as $k => $v) {
				$placeholders[] = ":in_$k";
			}
			$sql .= " AND $colFilter IN (" . implode(",", $placeholders) . ")";
		}

		$sql .= self::getSuffix($orderBy, $orderMode, $startAt, $endAt);
		$stmt = Connection::connect()->prepare($sql);

		$stmt->bindValue(":b1", $between1, PDO::PARAM_STR);
		$stmt->bindValue(":b2", $between2, PDO::PARAM_STR);

		if (!empty($inArray)) {
			foreach ($inArray as $k => $v) {
				$stmt->bindValue(":in_$k", $v, PDO::PARAM_STR);
			}
		}

		try {
			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			error_log($e->getMessage());
			return null;
		}
	}
}
