<?php

if (defined('APP_DEBUG') && true === APP_DEBUG) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

if (!defined('APP_THEME')) define('APP_THEME', 'default');

include_once 'errors.php';
include_once 'functions.php';
if (file_exists('themes/'.APP_THEME.'/functions.php'))
	include_once 'themes/'.APP_THEME.'/functions.php';

function _find_next_in_array(array $a, int $i = 10) {
	if (!is_array($a)) return false;
	while (isset($a[$i])) { $i++; }
	return $i;
}

function add_style(string $src = '', int $priority = 10, string $media = 'all', bool $is_file = false ) {
	global $app_styles;

	if (!isset($app_styles)) $app_styles = array();

	$priority = _find_next_in_array($app_styles, $priority);
	$app_styles[$priority] = [($is_file ? 'f' : 's') => $src, 'm' => $media];
	return $priority;
}

function add_style_sheet(string $src = '', int $priority = 10, string $media = 'all' ) {
	return add_style($src, $priority, $media, true );
}


function add_script(string $src = '', int $priority = 10, bool $in_footer = true, bool $is_file = false) {
	global $app_scripts;

	if (!isset($app_scripts)) $app_scripts = array();

	$priority = _find_next_in_array($app_scripts, $priority);
	$app_scripts[$priority] = [($is_file ? 'f' : 's') => $src, 'b' => filter_var($in_footer, FILTER_VALIDATE_BOOLEAN)];
	return $priority;
}

function add_script_file(string $src = '', int $priority = 10, bool $in_footer = true) {
	return add_script($src, $priority, $in_footer, true);
}

function do_action(string $tag) {
	global $app_actions;

	if (!isset($app_actions)) return false;

	$tag = strtolower($tag);
	if (!isset($app_actions[$tag]) || !is_array($app_actions[$tag])) return false;

	$args = func_get_args();
	if (count($args) <= 1) $args = null;
	elseif (count($args) == 2) $args = $args[1];
	else $args = array_shift($args);

	$i = 0;
	foreach ($app_actions[$tag] as $value) {
		if (is_array($args)) call_user_func_array($value['f'], $args);
		else call_user_func($value['f'], $args);
	}
}

function add_action(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 0 ) {
	global $app_actions;

	if (!isset($app_actions)) $app_actions = array();

	$tag = strtolower($tag);
	if (!isset($app_actions[$tag])) $app_actions[$tag] = array();

	$priority = _find_next_in_array($app_actions[$tag], $priority);
	$app_actions[$tag][$priority] = ['f' => $function_to_add, 'a' => $accepted_args];
}

function remove_action(string $tag, callable $function_to_remove, int $priority = 10 ) {
	todo(__FUNCTION__, __LINE__, __FILE__);
}

function apply_filters(string $tag, $value) {
	$args = func_get_args();

	var_dump(args);
}

function add_filter(string $tag, callable $function_to_add, int $priority = 10, int $accepted_args = 1 ) {
	todo(__FUNCTION__, __LINE__, __FILE__);
}

function remove_filter(string $tag, callable $function_to_remove, int $priority = 10 ) {
	todo(__FUNCTION__, __LINE__, __FILE__);
}

function app_default_header() {
	global $app_styles, $app_scripts;

	if (isset($app_styles) && is_array($app_styles)) {
		foreach ($app_styles as $value) {
			if (isset($value['s'])) {
				echo "<style type=\"text/css\"";
				if (isset($value['m'])) { echo " media=\"" . $value['m'] . "\""; }
				echo ">\n";
				echo $value['s'];
				echo "\n</style>\n";
			} elseif (isset($value['f'])) {
				echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $value['f'] . "\"";
				if (isset($value['m'])) { echo " media=\"" . $value['m'] . "\""; }
				echo ">\n";
			}
		}
	}
}
add_action('header', 'app_default_header');

function app_default_footer() {
	global $app_scripts;

	if (isset($app_scripts) && is_array($app_scripts)) {
		foreach ($app_scripts as $value) {
			if (!isset($value['b']) || !$value['b']) continue;

			echo "<script type=\"text/javascript\"";

			if (isset($value['s'])) {
				echo ">\n";
				echo $value['s'];
				echo "\n";
			} elseif (isset($value['f'])) {
				echo " src=\"" . $value['f'] . "\">";
			}
			echo "</script>\n";
		}
	}
}
add_action('footer', 'app_default_footer');

// eof
