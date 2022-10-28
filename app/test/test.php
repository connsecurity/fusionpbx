<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//add multi-lingual support
$language = new text;
$text = $language->get();

//get the header
$document['title'] = 'Teste';
require_once "resources/header.php";

$uuid = '51a270b4-d8b8-4219-a139-f206a8baa501';
$switch_command = "uuid_transfer ".$uuid." -bleg ".$_SESSION['user']['extension'][0]['user']." XML ".$_SESSION['domain_name'];

// if (isset($switch_command)) {
//     $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
//     $response = event_socket_request($fp, 'api '.$switch_command);
// }
// echo $switch_command;
// echo "<br>";
// echo $fp;
// echo "<br>";
// echo $response;

//include the footer
require_once "resources/footer.php";

?>