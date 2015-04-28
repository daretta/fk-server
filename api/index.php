<?php
include 'db.php';
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();$app->contentType("application/json");

$app->get('/feedbacks','getFeedbacks');
$app->get('/feedbacks/:feedback_id','getFeedback');
$app->post('/feedbacks', 'insertFeedback');
$app->delete('/feedbacks/:feedback_id','deleteFeedback');

$app->get('/forms','getForms');
$app->get('/forms/:form_id','getForm');
$app->post('/forms', 'insertForm');
$app->delete('/forms/:form_id','deleteForm');

$app->run();

// FEEDBACK
// GET http://APISERVER/api/feedbacks
function getFeedbacks() {
	$feedbacks = array('feedbacks' => array());
	$sql = "	SELECT		*
				FROM		feedback
				ORDER BY	id DESC";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$feedbacks['feedbacks'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		foreach ($feedbacks['feedbacks'] as $key => $feedback) {
			$feedbacks['feedbacks'][$key]->data = json_decode($feedback->data);
		}
		echo json_encode($feedbacks);
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// GET http://APISERVER/api/feedbacks/:feedback_id
function getFeedback($feedbackId) {
	$feedback = array('feedback' => array());
	$sql = "	SELECT	*
				FROM	feedback
				WHERE	id = :feedbackId";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);
        $stmt->bindParam("feedbackId", $feedbackId);
		$stmt->execute();
		$resultFeedback = $stmt->fetchAll(PDO::FETCH_OBJ);
		if(isset($resultFeedback[0])) {
			$resultFeedback[0]->data = json_decode($resultFeedback[0]->data);
			$feedback['feedback'] = $resultFeedback[0];
		}
		$db = null;
		echo json_encode($feedback);
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// POST http://APISERVER/api/feedbacks
function insertFeedback() {
	$request = \Slim\Slim::getInstance()->request();
	$feedback = json_decode($request->getBody());
	$sql = "INSERT INTO feedback (url, data, created, ip) VALUES (:url, :data, :created, :ip)";
	try {
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
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// DELETE http://APISERVER/api/feedbacks/:feedback_id
function deleteFeedback($feedbackId) {
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
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}

// FORM
// GET http://APISERVER/api/forms
function getForms() {
	$forms = array('forms' => array());
	$sql = "	SELECT		*
				FROM		form
				ORDER BY	id DESC";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$forms['forms'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		foreach ($forms['forms'] as $key => $form) {
			$forms['forms'][$key]->data = json_decode($form->data);
		}
/*$response = $app->response();
$response['Content-Type'] = 'application/json';
$response['X-Powered-By'] = 'Potato Energy';
$response->status(200);
$response->body(json_encode($forms));*/
		echo json_encode($forms);
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// GET http://APISERVER/api/feedbacks/:feedback_id
function getForm($formId) {
	$form['form'] = array();
	$header = getallheaders();
	try {
		if(isset($header['Request-Origin'])) {
			if(strpos($header['Request-Origin'], 'http') === FALSE) {
				$urlComponent = parse_url('http://' . $header['Request-Origin']);
			}
			else {
				$urlComponent = parse_url($header['Request-Origin']);
			}
		    if ($formId == 'this') {
		    	$paths = array($urlComponent['host']);
		    	$pathComponent = array();
		    	if(isset($urlComponent['path'])) {
		    		$pathComponent = explode('/', $urlComponent['path']);
		    		$pathComponent = array_filter($pathComponent);
		    	}
		    	$lastValue = array();
		    	foreach ($pathComponent as $key => $value) {
		    		$lastValue[] = $value;
		    		array_unshift($paths, $urlComponent['host'] . '/' . implode('/', $lastValue));
		    	}
	    		foreach ($paths as $path) {
					$sql = "	SELECT	*
								FROM	form
								WHERE	url = :path";
					$db = getDB();
					$stmt = $db->prepare($sql);
			        $stmt->bindParam("path", $path);
					$stmt->execute();
					$form['form'] = $stmt->fetchAll(PDO::FETCH_OBJ);
					$db = null;
					if(!empty($form['form'])) {
						break;
					}
	    		}
	    		if(empty($form['form'])) {
			    	$form['form'] = array(
			    		'fields' => array(
				    		'type' => 'textarea',
				    		'label' => 'Feedback',
				    		'id' => 'message'
				    	),
			    	);
	    		}
	    		else {
	    			$form['form'] = json_decode($form['form'][0]->data);
	    		}
				echo json_encode($form);
		    }
		    else {
				$sql = "	SELECT	*
							FROM	form
							WHERE	id = :formId";
				$db = getDB();
				$stmt = $db->prepare($sql);
		        $stmt->bindParam("formId", $formId);
				$stmt->execute();
				$form['form'] = $stmt->fetchAll(PDO::FETCH_OBJ);
				echo json_encode($form);
				$db = null;
		    }
		}
		else {
    		throw new Exception('Missing "Request-Origin" header');
		}
	} catch(Exception $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
	    echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// POST http://APISERVER/api/feedbacks
function insertForm() {
	$request = \Slim\Slim::getInstance()->request();
	$form = json_decode($request->getBody());
	$sql = "INSERT INTO form (url, data, created, ip) VALUES (:url, :data, :created, :ip)";
	try {
		var_dump(json_encode($form->data));
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("url", $form->url);
		$stmt->bindParam("data", json_encode($form->data));
		$time=time();
		$stmt->bindParam("created", $time);
		$ip=$_SERVER['REMOTE_ADDR'];
		$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$form->id = $db->lastInsertId();
		$db = null;
		$formId= $form->id;
		getFeedback($formId);
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// DELETE http://APISERVER/api/feedbacks/:feedback_id
function deleteForm($formId) {
	$sql = "	DELETE FROM	form
				WHERE		id=:formId";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("formId", $formId);
		$stmt->execute();
		$db = null;
		echo true;
	} catch(PDOException $e) {
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
