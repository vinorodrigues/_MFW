<?php

// include 'inc/ssl.php';
include_once 'config.php';
include_once 'inc/core.php';


add_style('body { background-color: #FEFEFE }');

add_script('$( document ).ready(function() {
    console.log( "Ready!" );
});', 99, true);

include 'themes/' . APP_THEME . '/top.php';
?>

<div class="container">
	<h1>Content goes here.</h1>
</div>


<?php
include 'themes/' . APP_THEME . '/bottom.php';
