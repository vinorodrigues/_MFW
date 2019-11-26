<?php

require_once 'core.php';
require_once 'db/database.php';

function passwd_hash(string $password) {
	global $password_hash_cost;

	if (!function_exists('password_hash')) {
		return md5($password);
	}

	if (!isset($password_hash_cost)) {
		$tt = 0.05;  // 50 milliseconds
		$password_hash_cost = 5;
		do {
			$password_hash_cost++;
			$st = microtime(true);
			$ret = password_hash($password, PASSWORD_BCRYPT, ["cost" => $password_hash_cost]);
			$et = microtime(true);
		} while (($et - $st) < $tt);
	} else {
		$ret = password_hash($password, PASSWORD_BCRYPT, ["cost" => $password_hash_cost]);
	}
	return $ret;
}

function passwd_verify(string $password, string $hash) {
	if (!function_exists('password_verify')) {
		return $hash === md5($password);
	}

	return password_verify($password, $hash);
}

function find_user($f_user) {
	global $db, $sql, $user;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (is_numeric($f_user)) {
		$stmt = db_select(
			array(
				'select' => '*',
				'from' => 'users',
				'where' => array('user_id' => $f_user),
				)
			);
	} elseif (is_string($f_user)) {
		$stmt = db_select(
			array(
				'select' => '*',
				'from' => 'users',
				'where' => array('username' => $f_user),
				)
			);
	} else {
		throw new YoureDoingItWrong('Can\'t find that kind of user');
		return -1;
	}

	if (false === $stmt) return false;

	$user = $stmt->fetch(PDO::FETCH_ASSOC);
	if (false === $user) return false;

	$id = isset($user['rowid']) ? $user['rowid'] : $user['user_id'];
	if (!isset($user['id'])) $user = array_merge(['id' => $id], $user);

	$stmt = db_select(
		array(
			'select' => ['group_id'],
			'from' => 'user_groups',
			'where' => array('user_id' => $id),
			'order' => 'group_id',
			)
		);
	if (false === $stmt) return $user;
	$user['groups'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
	return $user;
}

function find_group($f_group) {
	global $db, $sql;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (is_numeric($f_group)) {
		$stmt = db_select(
			array(
				'select' => '*',
				'from' => 'groups',
				'where' => array('group_id' => $f_group),
				)
			);
	} elseif (is_string($f_group)) {
		$stmt = db_select(
			array(
				'select' => '*',
				'from' => 'groups',
				'where' => array('groupname' => $f_group),
				)
			);
	} else {
		throw new YoureDoingItWrong('Can\'t find that kind of group');
		return -1;
	}

	if (false === $stmt) return false;

	$group = $stmt->fetch(PDO::FETCH_ASSOC);
	if (false === $group) return false;

	$id = isset($group['rowid']) ? $group['rowid'] : $group['group_id'];
	if (!isset($group['id'])) $group = array_merge(['id' => $id], $group);

	return $group;
}

function _clean_username($name) {
	return preg_replace("/[^a-z0-9]/", '', strtolower($name));
}

function add_group($a_group) {
	global $db, $sql;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (is_string($a_group)) $a_group = _clean_username($a_group);
	elseif (is_array($a_group) && isset($a_group['groupname']))
		$a_group['groupname'] = _clean_username($a_group['groupname']);

	if (is_string($a_group) || is_numeric($a_group)) {
		$group = find_group($a_group);
	} elseif (is_array($a_group)) {
		if (isset($a_group['groupname'])) {
			$group = find_group($a_group['groupname']);
		} elseif (isset($a_group['group_id'])) {
			$group = find_group($a_group['group_id']);
		} else {
			$group = false;
		}
	} else {
		throw new YoureDoingItWrong();
		return false;
	}

	if (false !== $group) {
		log_error('Group \'' . $group['groupname'] . '\' already exists as group_id ' . $group['group_id']);
		$gid = $group['group_id'];
	} else {
		$group = array();

		if (is_string($a_group)) {
			$group['groupname'] = $a_group;
		} else {
			$group['groupname'] = isset($a_group['groupname']) ? $a_group['groupname'] : '';
		}

		if (empty($group['groupname'])) throw new Exception('Groupname cannot be empty');
		if (is_numeric($group['groupname'])) throw new Exception('Groupname cannot be numeric');

		$group['realname'] = isset($a_group['realname']) ? $a_group['realname'] : '';
		$group['createdon'] = sql_time();  // now
		$group['enabled'] = isset($a_group['enabled']) ? sql_bool($a_group['enabled']) : true;

		$gid = db_insert('groups', $group);
		if (false !== $gid) $group['id'] = $gid;
	}

	return $gid;
}

function add_user($a_user, $a_group = null) {
	global $db, $sql, $user;

	if (!isset($db) || $db == null) { throw new YoureDoingItWrong('DB not connected'); return false; }

	if (is_string($a_user)) $a_user = _clean_username($a_user);
	elseif (is_array($a_user) && isset($a_user['username']))
		$a_user['username'] = _clean_username($a_user['username']);

	if (is_string($a_user)) {
		$user = find_user($a_user);
	} elseif (is_array($a_user)) {
		if (isset($a_user['username'])) {
			$user = find_user($a_user['username']);
		} elseif (isset($a_user['user_id'])) {
			$user = find_user($a_user['user_id']);
		} else {
			$user = false;
		}
	} else {
		throw new YoureDoingItWrong();
		return false;
	}

	if (false !== $user) {
		log_error('User \'' . $user['username'] . '\' already exists as user_id ' . $user['user_id']);
		$uid = $user['user_id'];
	} else {
		$user = array();

		if (is_string($a_user)) {
			$user['username'] = $a_user;
		} else {
			$user['username'] = isset($a_user['username']) ? $a_user['username'] : '';
		}

		if (empty($user['username'])) throw new Exception('Username cannot be empty');
		if (is_numeric($user['username'])) throw new Exception('Username cannot be numeric');

		$passwd = isset($a_user['passwd']) ? $a_user['passwd'] : $user['username'];
		if ('$' != substr($passwd, 0, 1)) $passwd = passwd_hash($passwd);
		$user['passwd'] = $passwd;
		$user['realname'] = isset($a_user['realname']) ? $a_user['realname'] : '';
		$user['email'] = isset($a_user['email']) ? $a_user['email'] : '';
		$user['createdon'] = sql_time();  // now
		$user['enabled'] = isset($a_user['enabled']) ? sql_bool($a_user['enabled']) : true;

		$uid = db_insert('users', $user);
		if (false !== $uid) $user['id'] = $uid;
	}

	if (is_null($a_group) || empty($a_group)) $a_group = $user['username'];

	$gid = add_group($a_group);

	$link_array = array(
		'user_id' => $uid,
		'group_id' => $gid,
		);
	$stmt = db_select(
		array(
			'select' => 'COUNT(1)',
			'from' => 'user_groups',
			'where' => $link_array,
			)
		);
	if (false !== $stmt) {
		$cnt = $stmt->fetchColumn();
		if (0 == $cnt) db_insert('user_groups', $link_array);
	}

	return $uid;
}
