<?php
include 'db.php';
require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->get('/feedbacks','getFeedbacks');
$app->get('/feedbacks/:feedback_id','getFeedback');
$app->post('/feedbacks', 'insertFeedback');
$app->delete('/feedbacks/:feedback_id','deleteFeedbacks');

$app->run();


function getFeedbacks() {
	$sql = "	SELECT		*
				FROM		feedback
				ORDER BY	id DESC";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$updates = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"feedbacks": ' . json_encode($updates) . '}';
		
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function getFeedback($feedbackId) {
	$sql = "	SELECT	*
				FROM	feedback
				WHERE	id = :feedbackId";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);
        $stmt->bindParam("feedbackId", $feedbackId);
		$stmt->execute();
		$updates = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"feedbacks": ' . json_encode($updates) . '}';
		
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}

function insertFeedback() {
	$request = \Slim\Slim::getInstance()->request();
	$feedback = json_decode($request->getBody());
	$sql = "INSERT INTO feedback (url, data, created, ip) VALUES (:url, :data, :created, :ip)";
	try {
		var_dump(json_encode($feedback->data));
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("url", $feedback->url);
		$stmt->bindParam("data", json_encode($feedback->data));
		$time=time();
		$stmt->bindParam("created", $time);
		$ip=$_SERVER['REMOTE_ADDR'];
		$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$feedback->id = $db->lastInsertId();
		$db = null;
		$feedbackId= $feedback->id;
		getFeedback($feedbackId);
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}
function deleteFeedbacks($feedbackId) {
	$sql = "	DELETE FROM	feedback
				WHERE		id=:feedbackId";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("feedbackId", $feedbackId);
		$stmt->execute();
		$db = null;
		echo true;
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}'; 
	}
}
