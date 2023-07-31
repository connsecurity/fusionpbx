<?php

//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "app/chatwoot_api/resources/chatwoot_api.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    //check if inbox_id is set and is a number
    if (isset($_GET['inbox_id']) && is_numeric($_GET['inbox_id'])) {
        $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/inboxes/".$_GET['inbox_id'];
        $response = chatwoot_agent_request($path);
        $templates = json_decode($response)->message_templates;
        
        echo json_encode($templates);
        exit;
    }    
}