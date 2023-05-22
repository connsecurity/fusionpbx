<?php 

require "chatwoot_api_functions.php";

if (isset($_SESSION['chatwoot']['account']['id'])) {
    if ($_SESSION['chatwoot']['account']['domain_uuid'] !==  $_SESSION['domain_uuid']) {

        $_SESSION['chatwoot']['account']['id'] = chatwoot_account::get_domain_account_id();
        $_SESSION['chatwoot']['account']['domain_uuid'] =  $_SESSION['domain_uuid'];
    }
} else {
    
    $_SESSION['chatwoot']['account']['id'] = chatwoot_account::get_domain_account_id();
    $_SESSION['chatwoot']['account']['domain_uuid'] =  $_SESSION['domain_uuid'];
}