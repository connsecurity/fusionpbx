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

//var_dump($_SESSION['agent']);

$sql = "SELECT ";
$sql .=     "ex.extension,";
$sql .=     "es.extension_uuid, ";
$sql .=     "es.extension_setting_value::json->> 'start' as start_epoch, ";
$sql .=     "es.extension_setting_value::json->> 'end' as end_epoch ";
$sql .= "FROM ";
$sql .=     "v_extension_settings as es ";
$sql .= "INNER JOIN ";
$sql .=     "v_extensions as ex ";
$sql .= "ON ";
$sql .=     "es.domain_uuid = :domain_uuid ";
$sql .= "AND ";
$sql .=     "es.extension_uuid = ex.extension_uuid ";
$sql .= "AND ";
$sql .=     "es.extension_setting_type = 'param' ";
$sql .= "AND ";
$sql .=     "es.extension_setting_name ~ '^agent_\d+$' ";
$sql .= "AND ";
$sql .=     "es.extension_setting_value::json->> 'uuid' = :agent_uuid ";
$sql .= "ORDER BY ";
$sql .=     "start_epoch ";
$sql .= "DESC ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$parameters['agent_uuid'] = $_SESSION['user']['user_uuid'];

$database = new database;
$result = $database->select($sql, $parameters, 'all');
echo "<br><br>";

$x = 0;
foreach ($result as $row) {
    $_SESSION['agent']['extension'][$x]['extension'] = $row['extension'];
    $_SESSION['agent']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
    $_SESSION['agent']['extension'][$x]['start_epoch'] = $row['start_epoch'];        
    $_SESSION['agent']['extension'][$x]['end_epoch'] = $row['end_epoch'];
    $x++;
}
echo"<br><br>_SESSION['agent']['extension']<br>";
var_dump($_SESSION['agent']['extension']);

if (!isset($_SESSION['agent']['extension']) || $_SESSION['agent']['extension'][0]['extension_uuid'] == '') {
    $x = 0;
    foreach ($_SESSION['user']['extension'] as $extension) {
        $_SESSION['agent']['extension'][$x]['extension'] = $extension['user'];
        $_SESSION['agent']['extension'][$x]['extension_uuid'] = $extension['extension_uuid'];
        $x++;
    }
}
echo"<br><br>sql result<br>";
var_dump($result); echo"<br><br>_SESSION['agent']['extension']<br>";
var_dump($_SESSION['agent']['extension']);
unset($sql, $parameters, $result);

echo "<br><br>_SESSION['user']['extension']<br>";
var_dump($_SESSION['user']['extension']);

echo "<br><br>";
var_dump($_SESSION);



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