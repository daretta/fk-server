<?php
function getDB() {
	$dbhost="mysql1025.servage.net";
	$dbuser="fkserver";
	$dbpass="aiNg8voh";
	$dbname="fkserver";
	$dbConnection = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}
?>
