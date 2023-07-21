<?php 

require "chatwoot_api_functions.php";

if (!isset($_SESSION['chatwoot']['account']['domain_uuid'])
    || $_SESSION['chatwoot']['account']['domain_uuid'] !==  $_SESSION['domain_uuid']) {

        $user = chatwoot_user::get_user_by_uuid($_SESSION['user_uuid']);

        $_SESSION['chatwoot']['account']['id'] = chatwoot_account::get_domain_account_id();
        $_SESSION['chatwoot']['account']['domain_uuid'] =  $_SESSION['domain_uuid'];
        $_SESSION['chatwoot']['user']['id'] = $user['user_id'];
        $_SESSION['chatwoot']['user']['access_token'] = $user['access_token'];
        $_SESSION['chatwoot']['user']['pubsub_token'] = $user['pubsub_token'];
}