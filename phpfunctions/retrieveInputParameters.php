<?php
    include("config.php");
	include("jwt_helper.php");
    session_start(); 
    
    $auth = false;

    if (!isset($_POST['token'])) {
        http_response_code(400);
        $JsonReq = array('http_response_code' => 400, 'title' => 'Error', 'message' => 'You are not signed in!!! Please sign in/up');
        print json_encode($JsonReq);
        exit();
    }

    try {
        // Get email
        $key=SERVERKEY.date("m.d.y");
        $email = JWT::decode($_POST['token'], $key);
        $auth = true;
    }
    catch (Exception $e) {  //hide $key on error
        http_response_code(400);
        $JsonReq = array('http_response_code' => 400, 'title' => 'Error', 'message' => 'Authentication error!!!');
        print json_encode($JsonReq);
        exit();
    }			

    if (!$auth) {
        http_response_code(400);
        $JsonReq = array('http_response_code' => 400, 'title' => 'Error', 'message' => 'Authentication error!!!');
        print json_encode($JsonReq);
        exit();
    }

    $identity=md5($email);

    //Connect to mysql
    $con1 = new mysqli(HOST, USERNAME, PWD, DB);
    if ($con1->connect_error) {
        http_response_code(201);
        $JsonReq = array('http_response_code' => 201, 'title' => 'Error', 'message' => "Connection Error" . $con1->connect_error);
        print json_encode($JsonReq);
        exit();
    }

    if (!isset($_POST['dataset'])) {
        http_response_code(201);
        $JsonReq = array('http_response_code' => 201, 'title' => 'Error', 'message' => 'No dataset name has been declared!');
        print json_encode($JsonReq);
        exit();
    }

    //get dataset type is the fisrt character of $_POST['dataset']
    $datasetType=substr($_POST['dataset'],0,1);
    //get the filename
    $filename=substr($_POST['dataset'],1);
    //Create dataset path
    $fpatho="../Python/output/".$identity."/".$datasetType."/".$filename;
    $fpatho_parts = pathinfo($fpatho);
    $fpatho=$fpatho_parts['dirname']."/".$fpatho_parts['filename'].".json";
    $message = '';
    if (is_file($fpatho)) {
        $json = file_get_contents($fpatho);
        $jsonReq = json_decode($json, true);
    } else {
        $message = "No input parameters are saved!!!\n";
    }

    if ($message) {
        http_response_code(201);
        $jsonReq = array('http_response_code' => 201, 'title' => "exclamation" , 'message' => $message);
        print json_encode($jsonReq);
        exit();        
    }

    http_response_code(200);
    print json_encode($jsonReq);
    exit();  
?>