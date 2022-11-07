<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('xml_cdr_view')) {
    //access granted
}
// else {
//     echo "access denied";
//     exit;
// }

//add multi-lingual support
$language = new text;
$text = $language->get();

$sql = "SELECT ";
$sql .= 	"co.contact_name_given, co.contact_nickname, cp.phone_number ";
$sql .= "FROM ";
$sql .= 	"v_contacts as co ";
$sql .= "INNER JOIN ";
$sql .= 	"v_contact_phones as cp ";
$sql .= "ON ";
$sql .= 	"co.contact_uuid = cp.contact_uuid ";
$sql .= "AND ";
$sql .= 	"co.domain_uuid = :domain_uuid ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$contacts = $database->select($sql, $parameters, 'all');

$table = "<tbody>";

foreach ($contacts as $contact) {
    $table .= "<tr><td><label>";
    $table .= "<input type='radio' class='agent_panel_contact' name='contact' value='".$contact['phone_number']."'>";
    $table .= "<div id='".$contact['contact_nickname']."'>";
    $table .= "Apelido: ".$contact['contact_nickname'];
    $table .= "<br>Telefone: ".$contact['phone_number'];
    //$table .= "</div></label></tr>";
}

//$table .= "</tbody>";

echo json_encode($table, JSON_UNESCAPED_UNICODE);

?>