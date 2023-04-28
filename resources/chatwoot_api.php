<?php

/*
    Chatwoot API functions
*/

if (!function_exists('get_account_users')) {
    function get_account_users($account_id) {
        
        $path = "/platform/api/v1/accounts/".$account_id."/account_users";

        $response = http_request($path, "GET");
        return $response;
    }
}

if (!function_exists('create_account')) {
    function create_account($name) {
        $path = "/platform/api/v1/accounts/";
        
        $body = array(
            'name' => $name,
        );
        
        $response = http_request($path, "POST", json_encode($body));
        $json_response = json_decode($response, true);
        
        $id = $json_response["id"];
        if ($id > 1) {
            return $id;
        } else {
            return false;
        }        
    }
}

if (!function_exists('delete_account')) {
    function delete_account($account_id) {
        $path = "/platform/api/v1/accounts/".$account_id;
        
        $response = http_request($path, "DELETE");
        $json_response = json_decode($response, true);

        if ($response === "") {
            return true;
        } else {
            return false;
        }     
    }
}

if (!function_exists('create_user')) {
    function create_user($name, $email, $password, $custom_attributes = NULL) {
        $path = "/platform/api/v1/users";

        $body = array(
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'custom_attributes' => $custom_attributes
        );

        $response = http_request($path, "POST", json_encode($body));        
        $json_response = json_decode($response, true);
        
        $id = $json_response["id"];
        if ($id > 1) {
            return $id;
        } else {
            return false;
        }    
    }
}

if (!function_exists('delete_user')) {
    function delete_user($id) {
        $path = "/platform/api/v1/users/".$id;

        $response = http_request($path, "DELETE");

        if ($response === "") {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('create_inbox')) {
    function create_inbox($id, $name, $channel) {
        $path = "/api/v1/accounts/".$id."/inboxes";

        $body = array(
            'name' => $name,
            'enable_auto_assignment' => false,
            'channel' => $channel
        );

        $response = http_request($path, "POST", json_encode($body));
        return $response;
    }
}

if (!function_exists('get_inbox')) {
    function get_inbox($account_id, $inbox_id) {
        $path = "/api/v1/accounts/".$account_id."/inboxes/".$inbox_id;

        $response = http_request($path, "GET");
        return $response;
    }
}

if (!function_exists('delete_inbox')) {
    function delete_inbox($account_id, $inbox_id) {
        $path = "/api/v1/accounts/".$account_id."/inboxes/".$inbox_id;
        $response = http_request($path, "DELETE");
        if ($response === "") {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('get_inbox_agents')) {
    function get_inbox_agents($account_id, $inbox_id) {
        $path = "/api/v1/accounts/".$account_id."/inbox_members/".$inbox_id;

        $response = http_request($path, "GET");
        return $response;
    }
}

if (!function_exists('http_request')) {
    function http_request($path, $method = "GET", $content = NULL) {

        $token_type = explode("/", $path)[1];

        if ($token_type === "api") {
            $api_access_token = $_SESSION['chat']['user_access_token']['text'];
        } elseif ($token_type === "platform") {
            $api_access_token = $_SESSION['chat']['platform_access_token']['text'];
        }

        $url = $_SESSION['chat']['chatwoot_url']['text'].$path;

        $headers = ["Content-type: application/json; charset=utf-8",
                    "api_access_token: ".$api_access_token];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($content) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

?>