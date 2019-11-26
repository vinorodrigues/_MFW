<?php

if (empty($_SERVER['HTTPS']) || !filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN)) {
	$location = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header('HTTP/1.1 307');
	header('Location: ' . $location);
	exit();
}
