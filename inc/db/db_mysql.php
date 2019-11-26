<?php

if (!defined('DB_CHARSET')) define( 'DB_CHARSET', 'utf8mb4' );

/**
 */
function db_mysql_connect() {
	global $db;

	if (!defined('DB_HOST')) throw new YoureDoingItWrong('DB_HOST not defined');
	if (!defined('DB_NAME')) throw new YoureDoingItWrong('DB_NAME not defined');
	if (!defined('DB_USER')) throw new YoureDoingItWrong('DB_USER not defined');
	if (!defined('DB_PASSWORD')) throw new YoureDoingItWrong('DB_PASSWORD not defined');

	$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;

	if (defined('DB_PORT') && !empty(DB_PORT))
		$dsn .= ';port=' . DB_PORT;

	$pdoOptions = array(
	    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	    PDO::ATTR_EMULATE_PREPARES => false
	);

	try {
		$db = new PDO($dsn, DB_USER, DB_PASSWORD, $pdoOptions );
	} catch(PDOException $e) {
		// to-do
		throw $e;  // re-throw
	}

	return $db;
}

/**
 */
function db_mysql_create_table($table_name, $schema) {
	global $db, $sql;

	$sql = 'CREATE TABLE IF NOT EXISTS ' . _i(_t($table_name)) . ' (' . PHP_EOL;

	$cols_cnt = count($schema['cols']);
	$i = 0;
	foreach ($schema['cols'] as $col => $data) {
		$i++;
		if (!is_array($data)) { throw new YoureDoingItWrong('Col data is wrong'); return false; }

		$sql .= "\t" . _i($col)  . ' ';

		switch (strtolower($data[0])) {
			case 'key':  // (always not-null, auto-inc, primary-key)
				$sql .= 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
				break;
			case 'int':  // 1 = default-int, 2 = not-null-bool
				$sql .= 'INT UNSIGNED';
				if (isset($data[1]) && !is_null($data[1])) $sql .= ' DEFAULT ' . intval($data[1]);
				if (isset($data[2]) && $data[2]) $sql .= ' NOT NULL';
				break;
			case 'num':  // 1 = default-int, 2 = not-null-bool
				$sql .= 'INT';
				if (isset($data[1]) && !is_null($data[1])) $sql .= ' DEFAULT ' . intval($data[1]);
				if (isset($data[2]) && $data[2]) $sql .= ' NOT NULL';
				break;
			case 'bool':  // 1 = default-value
				$sql .= 'BOOLEAN';
				if (isset($data[1])) $sql .= ' DEFAULT ' . ($data[1] ? 1 : 0);
				break;
			case 'txt':  // 1 = length, 2 = default, 3 = not-null-bool, 4 = unique-bool
				$sql .= 'VARCHAR';
				if (isset($data[1])) $sql .= "({$data[1]})";
				if (isset($data[2]) && !is_null($data[2])) $sql .= ' DEFAULT \'' . esc_sql($data[2]) . '\'';
				if (isset($data[4]) && $data[4]) $sql .= ' UNIQUE';
				if (isset($data[3]) && $data[3]) $sql .= ' NOT NULL';
				break;
			case 'time':  // 1 = current_timestamp_as_default_bool
				$sql .= 'DATETIME';
				if (isset($data[1]) && $data[1]) $sql .= ' DEFAULT CURRENT_TIMESTAMP';
				break;
		}

		if ($i < $cols_cnt) $sql .= ',' . PHP_EOL;
	}

	if (array_key_exists('key', $schema) && count($schema['key']) > 0) {
		$sql .= ',' . PHP_EOL . "\tPRIMARY KEY (";
		$sql .= _ii(', ', $schema['key']);
		$sql .= ')';
	}

	if (array_key_exists('index', $schema) && count($schema['index']) > 0)
		foreach ($schema['index'] as $idx => $data) {
			$sql .= ',' . PHP_EOL . "\t";
			$sql .= 'INDEX (' . _ii(', ', $data) . ')';
		}

	if (array_key_exists('foreign', $schema) && count($schema['foreign']) > 0)
		foreach ($schema['foreign'] as $con => $data) {
			$sql .= ',' . PHP_EOL . "\t";
			$sql .= 'CONSTRAINT ' . _i(_t($con));
			$sql .= ' FOREIGN KEY (' . _i($data[0]) . ')';
			$sql .= ' REFERENCES ' . _i(_t($data[1])) . ' (' . _i($data[2]) . ')';
			if (isset($data[3]))
				$sql .= __db_get_reference_option('delete', $data[3]);
			if (isset($data[4]))
				$sql .= __db_get_reference_option('update', $data[4]);
		}

	$sql .= PHP_EOL . "\t)";
	$sql .= ' CHARSET=' . DB_CHARSET;

	$sth = $db->prepare($sql);

	return $sth->execute();
}
