<?php

//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "app/chatwoot_api/resources/chatwoot_api.php";

// if ($_SERVER['REQUEST_METHOD'] === 'GET') {

//     $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations";
//     $response = chatwoot_agent_request($path);
//     echo $response;
// }

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations/filter";
    $body = array(
        'payload' => array(array(
            'attribute_key' => 'status',
            'attribute_model' => 'standard',
            'filter_operator' => 'equal_to',
            'values' => array ('all'),
            'custom_attribute_type' => ''
        ))
    );
    $response = chatwoot_agent_request($path, 'POST', json_encode($body));
    echo $response;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents('php://input'));
    if ($data->action == 'toggle_status') {

        $conversation_id = $data->conversation_id;
        $status = $data->status;
        $path = "/accounts/".$_SESSION['chatwoot']['account']['id']."/conversations/".$conversation_id."/toggle_status";
        $body = array (
            'status' => $status
        );

        $response = chatwoot_agent_request($path, 'POST', json_encode($body));
        echo $response;
        exit;
    }
}   