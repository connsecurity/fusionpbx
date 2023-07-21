<?php

//includes
require_once dirname(__DIR__, 3) . "/resources/require.php";
require_once "resources/check_auth.php";
require "app/chatwoot_api/resources/chatwoot_api.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // check if $_GET['before'] is set and is a number
    if (isset($_GET['before']) && is_numeric($_GET['before'])) {
        $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations/".$_GET['id']."/messages?before=".$_GET['before'];
    }
    else {
        $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations/".$_GET['id']."/messages";
    }
    
    $response = chatwoot_agent_request($path);
    echo $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations/".$_GET['id']."/messages";
    $body = file_get_contents('php://input');
    $response = chatwoot_agent_request($path, 'POST', $body);
    echo $response;
}