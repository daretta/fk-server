<?php
function getDB() {
	$dbhost="todo";
	$dbuser="todo";
	$dbpass="todo";
	$dbname="todo";
	$dbConnection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}
?>
