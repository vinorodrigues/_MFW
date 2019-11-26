<?php

/**
 * @see: https://www.w3schools.com/sql/default.asp
 * @see: https://stackoverflow.com/tags/pdo/info
 */


if (!defined('DB_DATABASE')) define('DB_DATABASE', 'mysql');
if (!defined('DB_HOST')) define('DB_HOST', false);
if (!defined('DB_USER')) define('DB_USER', false);
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', false);
if (!defined('DB_NAME')) define('DB_NAME', false);
if (!defined('DB_PORT')) define('DB_PORT', false);

require_once 'db_' . DB_DATABASE . '.php';

date_default_timezone_set('UTC');

function esc_sql_real($data) {
	static $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
	static $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
	return str_replace($search, $replace, $data);
}

function esc_sql($data) {
	if ( is_array( $data ) ) {
		foreach ( $data as $k => $v ) {
			if ( is_array( $v ) ) {
				$data[ $k ] = esc_sql( $v );
			} else {
				$data[ $k ] = esc_sql_real( $v );
			}
		}
	} else {
		$data = esc_sql_real( $data );
	}

	return $data;
}

function sql_bool($val) {
	return filter_var($val, FILTER_VALIDATE_BOOLEAN);
}

function sql_time($time = null) {
	if (null === $time) {
		return date('Y-m-d G:i:s');
	} else {
		return date('Y-m-d G:i:s', $time);
	}
}

if (!function_exists('_t')) {
	function _t(string $table_name) {
		$tn = defined('DB_PREFIX') ? DB_PREFIX : '';
		$tn .= $table_name;
		if (defined('DB_SUFFIX')) $tn = $tn . DB_SUFFIX;
		return $tn;
	}
}

if (!function_exists('_i')) {
	function _i(string $identifier) {
		return '`' . $identifier . '`';
	}
}

if (!function_exists('_ii')) {
	function _ii(string $glue , array $pieces) {
		return '`' . implode('`'.$glue.'`', $pieces) . '`';
	}
}

function db_connect($dbhost = DB_HOST, $dbuser = DB_USER, $dbpasswd = DB_PASSWORD, $dbname = DB_NAME, $dbport = DB_PORT) {
	global $db;
	$db = call_user_func('db_'.DB_DATABASE.'_connect', $dbhost, $dbuser, $dbpasswd, $dbname, $dbport);
	return $db;
}

function db_close() {
	global $db;
	$db = null;
}

/**
 */
function __db_get_reference_option($action, $reference_option) {
	$reference_option = strtolower(str_replace([' ', '-', '_'], '', $reference_option));

	switch ($reference_option) {
		case 'restrict':   $opt = 'RESTRICT';    break;
		case 'cascade':    $opt = 'CASCADE';     break;
		case 'setnull':    $opt = 'SET NULL';    break;
		case 'noaction':   $opt = 'NO ACTION';   break;
		case 'setdefault': $opt = 'SET DEFAULT'; break;
		default:           $opt = false;         break;
	}
	if (false !== $opt) $opt = ' ON ' . strtoupper($action) . ' ' . $opt;
	return $opt;
}

function db_create_table($table_name, $schema) {
	global $db;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (!is_array($schema)) { throw new YoureDoingItWrong('No schema'); return false; }
	if (!array_key_exists('cols', $schema)) { throw new YoureDoingItWrong('\'cols\' not in schema'); return false; }

	if (0 == count($schema['cols'])) { throw new YoureDoingItWrong('No \'cols\' in schema'); return false; }

	return call_user_func('db_'.DB_DATABASE.'_create_table', $table_name, $schema);
}

function db_insert($table, $data) {
	global $db, $sql;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (function_exists('db_'.DB_DATABASE.'_insert')) {
		return call_user_func('db_'.DB_DATABASE.'_insert', $table, $data);
	}

	$fields = $values = array();
	foreach ($data as $fld => $val) {
		$fields[] = $fld;
		$values[] = $val;
	}

	$sql = 'INSERT INTO ' . _i(_t($table)) . ' (';
	$sql .= _ii(', ', $fields);
	$sql .= ') VALUES (?';
	for ($i = 1; $i < count($values); $i++) $sql .= ', ?';
	$sql .= ')';

	$stmnt = $db->prepare($sql);
	$err = false;
	try {
		$stmnt->execute($values);
	} catch(PDOException $e) {
		log_error($e);
		$err = true;
	}

	return $err ? false : $db->lastInsertId();
}

function db_select($criteria) {
	global $db, $sql;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }
	if (!isset($criteria['select'])) { throw new YoureDoingItWrong(); return false; }
	if (!isset($criteria['from'])) { throw new YoureDoingItWrong(); return false; }

	$sql = 'SELECT ';
	if (isset($criteria['distinct']) && $criteria['distinct']) $sql .= 'DISTINCT';
	if (is_array($criteria['select'])) {
		$sql .= _ii(', ', $criteria['select']);
	} else {
		$sql .= $criteria['select'];
	}

	$sql .= ' FROM ';
	if (is_array($criteria['from'])) {
		dump('TODO: FROM FROM ARRAY');
		exit(-1);
	} else {
		$sql .= _i(_t($criteria['from']));
	}

	if (isset($criteria['join'])) {
		dump('TODO : JOIN table ON join_condition');
		exit(-1);
	}

	if (isset($criteria['where'])) {
		$sql .= ' WHERE ';
		if (is_array($criteria['where'])) {
			$fields = $valuse = array();
			foreach ($criteria['where'] as $fld => $val) {
				$fields[] = $fld;
				$values[] = $val;
			}
			$c = count($fields);
			$i = 0;
			foreach ($fields as $idx => $fld) {
				$sql .= _i($fld) . ' = ?';
				$i++;
				if ($i < $c) $sql .= ' AND ';
			}
		} else {
			$sql .= $criteria['where'];
		}
	}

	if (isset($criteria['order'])) {
		if (is_array($criteria['order'])) {
			dump('TODO : ORDER BY column');
			exit(-1);
		} else {
			$sql .= ' ORDER BY ' . _i($criteria['order']) . ' ASC';
		}
	}

	if (isset($criteria['limit'])) {
		dump('TODO : LIMIT count OFFSET offset');
		exit(-1);
	}

	$stmt = $db->prepare($sql);
	if (isset($values)) {
		$res = $stmt->execute($values);
	} else {
		$res = $stmt->execute();
	}
	if (!$res) return false;

	return $stmt;
}


/**
 * Example @see: https://wiki.phpbb.com/Dbal.sql_build_query
 */
// function sql_query($query, $array) {
// 	global $sql;
// 	// $query = strtolower(str_replace(' ', '', $query));

// 	$sql = '';
// 	switch ($query) {
// 		case 'distinct':
// 			$sql = ' DISTINCT';
// 		case 'select':
// 			$sql = 'SELECT' . $sql . ' ' . $array['select'] . ' FROM ';

// 			// Build table array. We also build an alias array for later checks.
// 			$table_array = $aliases = array();
// 			$used_multi_alias = false;

// 			foreach ($array['FROM'] as $table_name => $alias) {
// 				if (is_array($alias)) {
// 					$used_multi_alias = true;

// 					foreach ($alias as $multi_alias)
// 					{
// 						$table_array[] = $table_name . ' ' . $multi_alias;
// 						$aliases[] = $multi_alias;
// 					}
// 				} else {
// 					$table_array[] = $table_name . ' ' . $alias;
// 					$aliases[] = $alias;
// 				}
// 			}

// 			// We run the following code to determine if we need to re-order the table array. ;)
// 			// The reason for this is that for multi-aliased tables (two equal tables) in the FROM statement the last table need to match the first comparison.
// 			// DBMS who rely on this: Oracle, PostgreSQL and MSSQL. For all other DBMS it makes absolutely no difference in which order the table is.
// 			if (!empty($array['LEFT_JOIN']) && sizeof($array['FROM']) > 1 && $used_multi_alias !== false) {
// 				// Take first LEFT JOIN
// 				$join = current($array['LEFT_JOIN']);

// 				// Determine the table used there (even if there are more than one used, we only want to have one
// 				preg_match('/(' . implode('|', $aliases) . ')\.[^\s]+/U', str_replace(array('(', ')', 'AND', 'OR', ' '), '', $join['ON']), $matches);

// 				// If there is a first join match, we need to make sure the table order is correct
// 				if (!empty($matches[1])) {
// 					$first_join_match = trim($matches[1]);
// 					$table_array = $last = array();

// 					foreach ($array['FROM'] as $table_name => $alias) {
// 						if (is_array($alias)) {
// 							foreach ($alias as $multi_alias) {
// 								($multi_alias === $first_join_match) ? $last[] = $table_name . ' ' . $multi_alias : $table_array[] = $table_name . ' ' . $multi_alias;
// 							}
// 						} else {
// 							($alias === $first_join_match) ? $last[] = $table_name . ' ' . $alias : $table_array[] = $table_name . ' ' . $alias;
// 						}
// 					}

// 					$table_array = array_merge($table_array, $last);
// 				}
// 			}

// 			$sql .= $this->_sql_custom_build('FROM', implode(' CROSS JOIN ', $table_array));

// 			if (!empty($array['LEFT_JOIN'])) {
// 				foreach ($array['LEFT_JOIN'] as $join) {
// 					$sql .= ' LEFT JOIN ' . key($join['FROM']) . ' ' . current($join['FROM']) . ' ON (' . $join['ON'] . ')';
// 				}
// 			}

// 			if (!empty($array['WHERE'])) {
// 				$sql .= ' WHERE ' . $this->_sql_custom_build('WHERE', $array['WHERE']);
// 			}

// 			if (!empty($array['GROUP_BY'])) {
// 				$sql .= ' GROUP BY ' . $array['GROUP_BY'];
// 			}

// 			if (!empty($array['ORDER_BY'])) {
// 				$sql .= ' ORDER BY ' . $array['ORDER_BY'];
// 			}

// 		break;
// 	}

// 	return $sql;
// }

