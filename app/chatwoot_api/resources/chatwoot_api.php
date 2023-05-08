<?php 

require "functions.php";

if (!isset($_SESSION['chatwoot']['account']['id'])) {
    $_SESSION['chatwoot']['account']['id'] = chatwoot_account::get_domain_account_id();
}    