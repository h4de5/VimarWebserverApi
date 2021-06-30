<?php

require rtrim(realpath(__DIR__ . '/../lib'), '\\/').'/ClassAutoloader.php';

use Pnet\Bus\Communicate;
use Pnet\Bus\Render;
use Pnet\Bus\Config;

$autoloader = new ClassAutoloader();

//header('Content-Type: text/html; charset=utf-8');
setlocale(LC_ALL, "de_DE");
ini_set('assert.exception', 1);

$config = (new Config('../config.ini'))->load()->validate()->get();


// Communicate
//	set configuration
$com = new Communicate(
	$config['auth']['host'],
	$config['auth']['user'],
	$config['auth']['password']);

// if session in cookie
//	set session id
if(!empty($_COOKIE['session'])) {
	$com->setSessionId($_COOKIE['session']);
}

$com->getUser();

// TODO: check if session is valid
// 	login user

if(empty($com->getUserId())) {
	$com->login();
	$session = $com->getSessionId();
	if(!empty($session)) {
		setcookie ( 'session', $session,  time()+60*60*24*30, null, null, true, true);
	}
	// $com->getUser();
}

// get main groups
$com->getMainGroupIds();

// load all elements
// $com->loadElements();

// load all rooms
// $com->getRooms();

$com->getStatus();







/*
if(!empty($_GET['session'])) {
	$com->setSessionId($_GET['session']);
} else {
	$com->login();
	echo '<a href="?session='. $com->getSessionId() . '" title="use this link for further requests">next request</a>';
}
*/

if(isset($_GET['ajax'])) {

	$data = $_POST;

	if(!empty($data['eid']) && is_numeric($data['eid']) &&
		!empty($data['sid']) && is_numeric($data['sid']) &&
			isset($data['value']) && is_numeric($data['value'])) {
		$com->setValue($data['sid'], $data['value']);

		$result = $com->getObject($data['eid']);
		$com->buildBusProject($result);

		$elements = $com->getBusElements();
		$result = $com->getValue(array_keys($elements));
		$com->updateBusProject($result);


		echo Render::generateElement($com->getElement($data['eid']), false);
	} else {
		header("HTTP/1.0 400 Bad Request");
		echo 'invalid parameters';
	}


	exit(0);
}

if(isset($_GET['dump'])) {
	$com->queryGetTableInfo();
}


// $com->getObjectList();
//
// Ziel:
// einloggen: user ID bekommen
// Liste mit allen Geräten > array mit IDs
// Liste mit allen Stati > geräte, typen + stati
// array darstellen
// regelmäßig auf updates prüfen
// auf knopfdruck reagieren und ein und ausschalten
// gruppen definieren: pro gerät, pro stockwerk,
//


$result = $com->getObjectList();
//echo Render::generateResultTable($result);
$com->buildBusProject($result);
$elements = $com->getBusElements();

/*
echo '<pre>';
echo '$elements: ';
var_dump($com->getElements());
echo '$buselements: ';
var_dump($elements);
echo '</pre>';
*/


// $result = $com->getValue(array_keys($elements));
// //echo Render::generateResultTable($result);
// $com->updateBusProject($result);



//var_dump($com->getElements());

/*
$com->setValue(710, 1);
$com->setValue(712, 60);
$com->setValue(710, 0);
*/
//echo "</pre>";

?><!doctype html>
<html class="no-js" lang="">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>BUS Webserver</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="css/main.css">
		<style>
			.btn-multiline {
				margin-bottom:4px;
				white-space: normal;
			}
		</style>
		<!--[if lt IE 9]>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
			<script>window.html5 || document.write('<script src="js/vendor/html5shiv.js"><\/script>')</script>
		<![endif]-->
	</head>
	<body>
		<div class="container">
		<?php


echo Render::generateElementList($com->getElements());


		?>
		</div>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>

		<script src="js/vendor/bootstrap.min.js"></script>

		<script src="js/main.js"></script>
	</body>
</html>
