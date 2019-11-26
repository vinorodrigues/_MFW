<?php

function _i($i) { return $i; }
function _ii($g, $p) { return implode($g, $p); }

function db_sqlite_connect() {
	global $db;

	if (!defined('DB_HOST')) throw new YoureDoingItWrong('DB_HOST not defined');
	if (!defined('DB_NAME')) throw new YoureDoingItWrong('DB_NAME not defined');

	$dsn = "sqlite:" . trailingslashit(DB_HOST) . DB_NAME . ".sq3";

	$pdoOptions = array(
	    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	);

	try {
		$db = new PDO($dsn, '', '', $pdoOptions );
	} catch(PDOException $e) {
		// to-do
		throw $e;  // re-throw
	}

	return $db;
}

function db_sqlite_create_table($table_name, $schema) {
	global $db, $sql;

	$sql = 'CREATE TABLE IF NOT EXISTS ' . _i(_t($table_name)) . ' (' . PHP_EOL;

	$cols_cnt = count($schema['cols']);
	$i = 0;
	foreach ($schema['cols'] as $col => $data) {
		$i++;
		if (!is_array($data)) { throw new YoureDoingItWrong('Col data is wrong'); return false; }

		$sql .= "\t" . _i($col)  . ' ';

		$data[0] = strtolower($data[0]);
		switch ($data[0]) {
			case 'key':  // (always not-null, auto-inc, primary-key)
				$sql .= 'INTEGER PRIMARY KEY AUTOINCREMENT';  // sets up ROWID, $see https://www.sqlite.org/autoinc.html
				break;
			case 'int':  // 1 = default-int, 2 = not-null-bool
			case 'num':  // 1 = default-int, 2 = not-null-bool
			case 'bool':  // 1 = default-value
				$sql .= 'num' == $data[0] ? 'NUMERIC' : 'INTEGER';
				if (isset($data[1]) && !is_null($data[1])) $sql .= ' DEFAULT ' . intval($data[1]);
				if (isset($data[2]) && $data[2]) $sql .= ' NOT NULL';
				break;
			case 'txt':  // 1 = length, 2 = default, 3 = not-null-bool, 4 = unique-bool
				$sql .= 'TEXT';
				// $data[1], size, ignored
				if (isset($data[2]) && !is_null($data[2])) $sql .= ' DEFAULT \'' . esc_sql($data[2]) . '\'';
				if (isset($data[4]) && $data[4]) $sql .= ' UNIQUE';
				if (isset($data[3]) && $data[3]) $sql .= ' NOT NULL';
				break;
			case 'time':  // 1 = current_timestamp_as_default_bool
				$sql .= 'TIMESTAMP';  // as ISO8601 strings ("YYYY-MM-DD HH:MM:SS.SSS").
				if (isset($data[1]) && $data[1]) $sql .= ' DEFAULT CURRENT_TIMESTAMP';
				break;
		}

		if ($i < $cols_cnt) $sql .= ',' . PHP_EOL;
	}

	if (array_key_exists('key', $schema) && count($schema['key']) > 0) {
		$sql .= ',' . PHP_EOL . "\tPRIMARY KEY (";
		$sql .= _ii(', ', $schema['key']);
		$sql .= ')';
		$has_pk = true;
	} else {
		$has_pk = false;
	}

	// TO-DO : Index

	if (array_key_exists('foreign', $schema) && count($schema['foreign']) > 0)
		foreach ($schema['foreign'] as $con => $data) {
			$sql .= ',' . PHP_EOL . "\t";
			$sql .= 'FOREIGN KEY (' . _i($data[0]) . ')';
			$sql .= PHP_EOL . "\t\t" . 'REFERENCES ' . _i(_t($data[1])) . ' (' . _i($data[2]) . ')';
			if (isset($data[3]))
				$sql .= PHP_EOL . "\t\t\t" . __db_get_reference_option('delete', $data[3]);
			if (isset($data[4]))
				$sql .= PHP_EOL . "\t\t\t" . __db_get_reference_option('update', $data[4]);
		}

	$sql .= PHP_EOL . "\t)";
	if ($has_pk) $sql .= ' WITHOUT ROWID';

	$sth = $db->prepare($sql);

	return $sth->execute();
}
