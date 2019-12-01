<?php

function fix_darkmode_stylesheet($lightsheet, $darksheet) {
	global $app_styles;

	$idx = -1;
	foreach ($app_styles as $key => $value) {
		if (isset($value['i']) && 'bootstrap' == $value['i']) {
			$idx = $key;
			break;
		}
	}
	if ($idx < 0) return false;

	if (!isset($app_styles[$idx]['d']) || !is_array($app_styles[$idx]['d']))
		$app_styles[$idx]['d'] = array();

	$app_styles[$idx]['d']['light'] = $lightsheet;
	$app_styles[$idx]['d']['dark'] = $darksheet;

	$app_dark = false;
	if (isset($_COOKIE['dark'])) $app_dark = filter_var($_COOKIE['dark'], FILTER_VALIDATE_BOOLEAN);

	$app_styles[$idx]['f'] = $app_dark ? $darksheet : $lightsheet;

	return $idx;
}

add_script_file('cookie', 'vendor/jquery/js/jquery.cookie.js');

add_script('darkmode', "
$( document ).ready(function() {
	function getDarkMode() {
		if (window.matchMedia) {
			if (window.matchMedia('(prefers-color-scheme: dark)').matches) return true;
			if (window.matchMedia('(prefers-color-scheme: light)').matches) return false;
		}
		return undefined;
	};
	function fixDarkMode({ matches }) {
		if (matches != undefined) {
			darkMode = matches ? 1 : 0;
			if (darkMode != 0) {
				$('body').removeClass('light');
				$('body').addClass('dark');
			} else {
				$('body').removeClass('dark');
				$('body').addClass('light');
			}
			darkCookie = $.cookie('dark');
			if (darkMode != darkCookie) {
				$.cookie('dark', darkMode);
				$('#test').html(darkCookie);
				if (darkMode != 0) {
					$('link#bootstrap').attr('href', $('link#bootstrap').attr('data-dark'));
				} else {
					$('link#bootstrap').attr('href', $('link#bootstrap').attr('data-light'));
				}
			}
		}
	}
	fixDarkMode( getDarkMode() );
	if (window.matchMedia) window.matchMedia('(prefers-color-scheme: dark)').addListener( fixDarkMode );
});
");

fix_darkmode_stylesheet(
	'vendor/bootswatch/flatly/bootstrap.min.css',
	'vendor/bootswatch/darkly/bootstrap.min.css');
