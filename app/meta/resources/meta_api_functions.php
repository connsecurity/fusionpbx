<?php

if (!function_exists('get_waba_templates')) {
    function get_waba_templates($waba_id) {
        return meta_request($waba_id."/message_templates");
    }
}

if (!function_exists('attach_credit_line')) {
    function attach_credit_line($credit_line_id, $waba_id, $waba_currency = "USD") {
        return meta_request($credit_line_id."/whatsapp_credit_sharing_and_attach?waba_id=".$waba_id."&waba_currency=".$waba_currency);
    }
}

if (!function_exists('get_credit_line')) {
    function get_credit_line($business_id) {
        return meta_request($business_id."/extendedcredits");
    }
}

if (!function_exists('get_assigned_users')) {
    function get_assigned_users($waba_id, $business_id) {
        return meta_request($waba_id."/assigned_users?business=".$business_id);
    }
}

if (!function_exists('add_system_user_to_waba')) {
    function add_system_user_to_waba($waba_id, $user_id, $tasks = "MANAGE") {
        return meta_request($waba_id."/assigned_users?user=".$user_id."&tasks=['".$tasks."']", "POST");
    }
}

if (!function_exists('get_system_users')) {
    function get_system_users($business_id) {
        return meta_request($business_id."/system_users");
    }
}

if (!function_exists('get_waba_phone_numbers')) {
    function get_waba_phone_numbers($waba_id) {
        return meta_request($waba_id."/phone_numbers");
    }
}

if (!function_exists('get_waba')) {
    function get_waba($waba_id) {
        return meta_request($waba_id);
    }
}

if (!function_exists('register_phone_number')) {
    function register_phone_number($phone_number_id, $pin) {
        $body = array(
            "messaging_product" => "whatsapp",
            "pin" => $pin
        );
        return meta_request($phone_number_id."/register", "POST", json_encode($body));
    }
}

if (!function_exists('get_owned_wabas')) {
    function get_owned_wabas($business_id) {
        return meta_request($business_id."/owned_whatsapp_business_accounts");
    }
}

if (!function_exists('get_shared_wabas')) {
    function get_shared_wabas($business_id) {
        return meta_request($business_id."/client_whatsapp_business_accounts");
    }
}

if (!function_exists('debug_token')) {
    function debug_token($token) {
        return meta_request("debug_token?input_token=".$token);
    }
}

if (!function_exists('meta_request')) {
    function meta_request($path, $method = "GET", $content = NULL)
    {
    
        $api_access_token = $_SESSION['meta']['system_access_token']['text'];
        $api_version = $_SESSION['meta']['api_version']['text'];
    
        $url = $_SESSION['meta']['graph_url']['text'] . "/" . $api_version . "/" . $path;
    
        $headers = [
            "Content-type: application/json; charset=utf-8",
            "Authorization: Bearer " . $api_access_token
        ];
    
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
