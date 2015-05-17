<?php
// CORS
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS");
if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
	header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
}

include 'config/config.php';
require 'Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

$app->map('/:x+', function($x) {
    http_response_code(200);
})->via('OPTIONS');

$app->get('/feedbacks','setResponseHeader','getFeedbacks');
$app->get('/feedbacks/:feedback_id','setResponseHeader','getFeedback');
$app->post('/feedbacks','setResponseHeader','insertFeedback');
$app->put('/feedbacks/:feedback_id','setResponseHeader','updateFeedback');
$app->delete('/feedbacks/:feedback_id','setResponseHeader','deleteFeedback');

$app->get('/forms','setResponseHeader','getForms');
$app->get('/forms/:form_id','setResponseHeader','getForm');
$app->post('/forms','setResponseHeader','insertForm');
$app->delete('/forms/:form_id','setResponseHeader','deleteForm');

/*$app->get('/users','setResponseHeader','getUsers');
$app->get('/users/:user_id','setResponseHeader','getUser');
$app->post('/users','setResponseHeader','insertUser');
$app->delete('/users/:user_id','setResponseHeader','deleteUser');*/


$app->run();

// FEEDBACK
// GET http://APISERVER/api/feedbacks
function getFeedbacks() {
	$app = \Slim\Slim::getInstance();
	$feedbacks = array('feedbacks' => array());
	$pageResults = 50;
	$page = 1;
	$request = $app->request();
	$params = $request->get();
	if(isset($params['page'])) {
		$page = $params['page'];
		unset($params['page']);
	}
	if(isset($params['pageResults'])) {
		$pageResults = $params['pageResults'];
	}
	$sql = "	SELECT		*
				FROM		feedback
				ORDER BY	id DESC
				LIMIT " . (($page - 1) * $pageResults) . ", " . $pageResults;
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();
		$feedbacks['feedbacks'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		foreach ($feedbacks['feedbacks'] as $key => $feedback) {
			$feedbacks['feedbacks'][$key]->data = json_decode($feedback->data);
		}
		// pagination
		$sql = "SELECT COUNT(id) FROM feedback";
		$stmt = $db->prepare($sql); 
		$stmt->execute();
		$totalCount = $stmt->fetchColumn();
		$pages = ceil($totalCount/$pageResults);
		$queryString = '';
		if(!empty($params)) {
			$queryString = '?' . http_build_query($params);
		}
		$baseUrl = $request->getUrl() . $request->getPath() . $queryString;
		$feedbacks['_links'] = array(
			'self' => array(
				'href' => $baseUrl ,
			),
		);
		if($page > 1) {
			$feedbacks['_links']['self']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $page;
			if($page > 2) {
				if($page > $pages) {
					$feedbacks['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
				}
				else {
					$feedbacks['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page - 1);
				}
				$feedbacks['_links']['first']['href'] = $baseUrl;
			}
			else {
				$feedbacks['_links']['prev']['href'] = $baseUrl;
			}
		}
		if($page < $pages) {
			$feedbacks['_links']['next']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page + 1);
			if($page <= ($pages - 2 )) {
				$feedbacks['_links']['last']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
			}
		}
		$feedbacks['totalPages'] = $pages;
		$feedbacks['currentPage'] = $page;
		$db = null;
		$app->response->setBody(json_encode($feedbacks));
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		$app->response->setBody(json_encode(array('error' => array('text' => $e->getMessage()))));
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
// PUT http://APISERVER/api/feedbacks/:feedback_id
function updateFeedback($feedbackId) {
	$request = \Slim\Slim::getInstance()->request();
	$feedback = json_decode($request->getBody());
	$sql = "UPDATE feedback SET data = :data WHERE id = :id";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("id", $feedbackId);
		$stmt->bindParam("data", json_encode($feedback->data));
		$stmt->execute();
		$db = null;
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
		echo json_encode(array('message' => 'Feddback deleted'));;
	} catch(PDOException $e) {
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}

// FORM
// GET http://APISERVER/api/forms
function getForms() {
	$items = array('forms' => array());
	$sql = "SELECT * FROM form ORDER BY id DESC";
	addPagination($sql, $items, 'forms');
	echo json_encode($items);
/*	$app = \Slim\Slim::getInstance();
	$forms = array('forms' => array());
	$pageResults = 50;
	$page = 1;
	$request = $app->request();
	$params = $request->get();
	if(isset($params['page'])) {
		$page = $params['page'];
		unset($params['page']);
	}
	if(isset($params['pageResults'])) {
		$pageResults = $params['pageResults'];
	}
	$sql = "	SELECT		*
				FROM		form
				ORDER BY	id DESC
				LIMIT " . (($page - 1) * $pageResults) . ", " . $pageResults;
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$forms['forms'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		foreach ($forms['forms'] as $key => $form) {
			$forms['forms'][$key]->fields = json_decode($form->fields);
		}
		// pagination
		$sql = "SELECT COUNT(id) FROM form";
		$stmt = $db->prepare($sql); 
		$stmt->execute();
		$totalCount = $stmt->fetchColumn();
		$pages = ceil($totalCount/$pageResults);
		$queryString = '';
		if(!empty($params)) {
			$queryString = '?' . http_build_query($params);
		}
		$baseUrl = $request->getUrl() . $request->getPath() . $queryString;
		$forms['_links'] = array(
			'self' => array(
				'href' => $baseUrl ,
			),
		);
		if($page > 1) {
			$forms['_links']['self']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $page;
			if($page > 2) {
				if($page > $pages) {
					$forms['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
				}
				else {
					$forms['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page - 1);
				}
				$forms['_links']['first']['href'] = $baseUrl;
			}
			else {
				$forms['_links']['prev']['href'] = $baseUrl;
			}
		}
		if($page < $pages) {
			$forms['_links']['next']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page + 1);
			if($page <= ($pages - 2 )) {
				$forms['_links']['last']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
			}
		}
		echo json_encode($forms);
		$db = null;
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}*/
}
// GET http://APISERVER/api/forms/:form_id
function getForm($formId) {
	$form['form'] = array();
	$header = getallheaders();
	try {
	    if ($formId == 'this') {
			if(isset($header['Referer'])) {
				if(strpos($header['Referer'], 'http') === FALSE) {
					$urlComponent = parse_url('http://' . $header['Referer']);
				}
				else {
					$urlComponent = parse_url($header['Referer']);
				}
			}
			else {
				throw new Exception('Missing "Referer" header');
			}
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
		    			array(
			    			'type' => 'text',
				    		'label' => 'Mail',
				    		'id' => 'mail',
				    		'required' => TRUE
				    	),
		    			array(
			    			'type' => 'textarea',
				    		'label' => 'Feedback',
				    		'id' => 'message'
				    	),
			    	),
		    	);
    		}
    		else {
    			$form['form'] = json_decode($form['form'][0]->forms);
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
			$resultForm = $stmt->fetchAll(PDO::FETCH_OBJ);
			if(isset($resultForm[0])) {
				$resultForm[0]->fields = json_decode($resultForm[0]->fields);
				$form['form'] = $resultForm[0];
			}
			echo json_encode($form);
			$db = null;
	    }

	} catch(Exception $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
	    echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// POST http://APISERVER/api/forms
function insertForm() {
	$request = \Slim\Slim::getInstance()->request();
	$form = json_decode($request->getBody());
	$sql = "INSERT INTO form (url, fields, created, ip) VALUES (:url, :fields, :created, :ip)";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("url", $form->url);
		$stmt->bindParam("fields", json_encode($form->fields));
		$time=time();
		$stmt->bindParam("created", $time);
		$ip=$_SERVER['REMOTE_ADDR'];
		$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$form->id = $db->lastInsertId();
		$db = null;
		$formId= $form->id;
		getForm($formId);
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
		echo json_encode(array('message' => 'Form deleted'));;
	} catch(PDOException $e) {
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}

/*// USER
// GET http://APISERVER/api/users
function getUsers() {
	$users = array('users' => array());
	$sql = "	SELECT		*
				FROM		user
				ORDER BY	id DESC";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$users['users'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		foreach ($users['users'] as $key => $user) {
			$users['users'][$key]->data = json_decode($user->data);
		}

		echo json_encode($users);
	} catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// GET http://APISERVER/api/users/:user_id
function getUser($userId) {
	$user['user'] = array();
	try {
		$sql = "	SELECT	*
					FROM	user
					WHERE	id = :userId";
		$db = getDB();
		$stmt = $db->prepare($sql);
        $stmt->bindParam("userId", $userId);
		$stmt->execute();
		$user['user'] = $stmt->fetchAll(PDO::FETCH_OBJ);
		echo json_encode($user);
		$db = null;
	} catch(Exception $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
	    echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// POST http://APISERVER/api/users
function insertForm() {
	$request = \Slim\Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$sql = "INSERT INTO user (username, password, created, ip) VALUES (:username, MD5(:password), :created, :ip)";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("url", $user->url);
		$stmt->bindParam("password", $form->password);
		$time=time();
		$stmt->bindParam("created", $time);
		$ip=$_SERVER['REMOTE_ADDR'];
		$stmt->bindParam("ip", $ip);
		$stmt->execute();
		$user->id = $db->lastInsertId();
		$db = null;
		$userId = $user->id;
		getUser($userId);
	} catch(PDOException $e) {
		//error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
// DELETE http://APISERVER/api/users/:user_id
function deleteUser($userId) {
	$sql = "	DELETE FROM	user
				WHERE		id=:userId";
	try {
		$db = getDB();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("userId", $userId);
		$stmt->execute();
		$db = null;
		echo true;
	} catch(PDOException $e) {
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
*/

// UTILITY
function setResponseHeader() {
	$app = \Slim\Slim::getInstance();
	$app->response->headers->set('Content-Type', 'application/json');
}
function addPagination($query, &$items, $itemsName) {
	$app = \Slim\Slim::getInstance();
	$pageResults = 50;
	$page = 1;
	$request = $app->request();
	$params = $request->get();
	if(isset($params['page'])) {
		$page = $params['page'];
		unset($params['page']);
	}
	if(isset($params['pageResults'])) {
		$pageResults = $params['pageResults'];
	}
	$sql = $query . " LIMIT " . (($page - 1) * $pageResults) . ", " . $pageResults;
	try {
		$db = getDB();
		$stmt = $db->prepare($sql); 
		$stmt->execute();		
		$items[$itemsName] = $stmt->fetchAll(PDO::FETCH_OBJ);
		foreach ($items[$itemsName] as $key => $object) {
			foreach ($object as $property => $value) {
				$tmpValue = json_decode($value);
				if(json_last_error() == JSON_ERROR_NONE) {
					$items[$itemsName][$key]->{$property} = $tmpValue;
				}
			}
		}
		// pagination
		$sql = preg_replace('#(SELECT)(.*)(FROM)#si', '$1 COUNT(*) $3', $query);
		// $sql = str_replace('*', 'COUNT(id)', $query);
		$stmt = $db->prepare($sql); 
		$stmt->execute();
		$totalCount = $stmt->fetchColumn();
		$pages = ceil($totalCount/$pageResults);
		$queryString = '';
		if(!empty($params)) {
			$queryString = '?' . http_build_query($params);
		}
		$baseUrl = $request->getUrl() . $request->getPath() . $queryString;
		$items['_links'] = array(
			'self' => array(
				'href' => $baseUrl ,
			),
		);
		if($page > 1) {
			$items['_links']['self']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $page;
			if($page > 2) {
				if($page > $pages) {
					$items['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
				}
				else {
					$items['_links']['prev']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page - 1);
				}
				$items['_links']['first']['href'] = $baseUrl;
			}
			else {
				$items['_links']['prev']['href'] = $baseUrl;
			}
		}
		if($page < $pages) {
			$items['_links']['next']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . ($page + 1);
			if($page <= ($pages - 2 )) {
				$items['_links']['last']['href'] = $baseUrl . (empty($queryString)? '?':'&') .'page=' . $pages;
			}
		}
		//echo json_encode($items);
		$db = null;
 	}
 	catch(PDOException $e) {
	    //error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo json_encode(array('error' => array('text' => $e->getMessage())));
	}
}
