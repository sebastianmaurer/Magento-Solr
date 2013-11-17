<?php

define('DEBUG', true);
define('SECRET', '7f5edbe43c029bfaf9904684ea3bb519');
// snycconfig

// check secret
if(!isset($_GET['secret']) || $_GET['secret'] != SECRET) {
	http_response_code(403);
	exit;
}

if(isset($_POST['method'])) {

	if(DEBUG) {
		$log_handler = fopen('debug.log', 'a+');
		fwrite($log_handler, date('Y-m-d H:i:s').':'.$_POST['method']."\n");
	}

	switch ($_POST['method']) {
		case 'synonyms/update':
			// $_POST['data'] contains synonyms stream
			$handler = fopen('synonyms.txt', 'w+');
			fwrite($handler, $_POST['data']);
			# code...
			break;
		
		default:
			# code...
			break;
	}
}