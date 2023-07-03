<?php

require "meta_api_functions.php";

function embedded_signup($access_token)
{

    //get id of the shared waba
    $waba_id = get_waba_id($access_token);
    if (empty($waba_id)) {
        return "No waba_id found";
    }

    //get waba phone numbers
    $waba_phone_numbers_id = get_waba_phone_numbers_id($waba_id);

    //how do i know which phone to register?

    //register phone with any code method
    foreach ($waba_phone_numbers_id as $phone_number_id) {
        register_phone_number($phone_number_id, '000000');
    }        

    //add system user to waba
    add_system_user_to_waba($waba_id, $_SESSION['meta']['user_id']['text']);
    //check if user was added successfully
    get_assigned_users($waba_id, $_SESSION['meta']['business_id']['text']);

    //attach credit line to client waba

    //subscribe waba

    return $waba_id;
}

function get_waba_id($access_token)
{
    $token = json_decode(debug_token($access_token));
    // loop through the granular_scopes to find whatsapp_business_management scope and get the first target_ids
    foreach ($token->data->granular_scopes as $scope) {
        if ($scope->scope === "whatsapp_business_management") {
            return $scope->target_ids[0];
        }
    }
}

function get_waba_phone_numbers_id($waba_id)
{
    $phone_numbers_id = [];
    $waba_phone_numbers = json_decode(get_waba_phone_numbers($waba_id));
    foreach ($waba_phone_numbers->data as $phone_number) {
        $phone_numbers_id[] = $phone_number->id;
    }
    return $phone_numbers_id;
}

function load_domain_wabas() {
    $wabas = waba::get_domain_wabas();
    foreach ($wabas as $key => $waba) {        
        $wabas[$key] = $wabas[$key] + get_waba_details($waba['waba_id']);
    }
    return $wabas;
}

function get_waba_details($id) {
    $waba_info = json_decode(get_waba($id));
    if ($waba_info->error) {
        $waba['error'] = true;
        return $waba;
    }    
    $waba['name'] = $waba_info->name;
    $waba_phone_numbers = json_decode(get_waba_phone_numbers($id), true);
    $waba['phone_numbers'] = $waba_phone_numbers['data'];
    return $waba;
}