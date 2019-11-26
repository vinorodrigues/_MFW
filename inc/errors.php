<?php

class YoureDoingItWrong extends Exception { }

function log_error($e) {
	global $err;

	if (!isset($err) || !is_array($err)) {
		$err = array();
	}

	if (is_object($e) && is_a($e, 'Exception')) {
		return app_exception_handler($e);
	} elseif (is_string($e)) {
		$err[] = $e;
		return false;
	}
	return true;
}

function errors() {
	global $err;
	return isset($err) ? $err : false;
}

function app_error_handler($errno, $errstr, $errfile, $errline, $errcontext = null) {
	if (!(error_reporting() & $errno)) {
		// This error code is not included in error_reporting, so let it fall
		// through to the standard PHP error handler
		return false;
	}

    if ($errno & (E_ERROR | E_USER_ERROR)) echo 'ERROR ';
	if ($errno & (E_WARNING | E_USER_WARNING)) echo 'WARNING ';
	if ($errno & (E_NOTICE | E_USER_NOTICE)) echo 'NOTICE ';

    echo "{$errno}: {$errstr}";
	if (defined("APP_DEBUG") && true == APP_DEBUG)
		echo " in line {$errline} of file {$errfile}";

	if ($errno & (E_ERROR | E_USER_ERROR)) {
		echo ', PHP ' . PHP_VERSION . ' (' . PHP_OS . ').';
		echo ' Aborting...';
		exit(1);
	}

    /* Don't execute PHP internal error handler */
    return true;
}

function app_exception_handler($e) {
	echo 'EXCEPTION ' . get_class($e) . ' [' . $e->getCode() . ']: ' . $e->getMessage();
	if (defined("APP_DEBUG") && true == APP_DEBUG)
		echo ' in line ' . $e->getLine() . ' of file ' . $e->getFile();
	echo ', PHP ' . PHP_VERSION . ' (' . PHP_OS . ').';
	echo ' Aborting...';
	exit(1);
}

function dump($var, $name = '') {
	echo '<pre>';
	if (!empty($name)) echo "<b>{$name}</b> = ";
	var_dump($var);
	echo '</pre>';
}

function todo($function, $line, $file) {
	echo '<pre><b>To do:</b> ';
	$line--;
	echo "{$function} on line {$line} in file {$file}";
	echo '</pre>';

}

function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}

function trailingslashit( $string ) {
	return untrailingslashit( $string ) . '/';
}

set_error_handler('app_error_handler');
set_exception_handler('app_exception_handler');
