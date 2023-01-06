<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "get_agent.php";


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

$contact_type = $_GET['type'];
$associated_contacts = $_GET['associated'];


$sql = "SELECT ";
$sql .= 	"co.contact_name_given, co.contact_nickname, cp.phone_number, co.contact_uuid, ";
$sql .=     "CASE WHEN cr.contact_uuid = :agent_uuid THEN 1 ELSE 0 END as is_associated ";
$sql .= "FROM ";
$sql .= 	"v_contacts as co ";
$sql .= "LEFT JOIN ";
$sql .=     "v_contact_relations as cr ";
$sql .= "ON ";
$sql .=     "co.contact_uuid = cr.relation_contact_uuid ";
$sql .= "AND ";
$sql .=     "cr.contact_uuid = :agent_uuid ";
$sql .= "LEFT JOIN ";
$sql .= 	"v_contact_phones as cp ";
$sql .= "ON ";
$sql .= 	"co.contact_uuid = cp.contact_uuid ";
$sql .= "WHERE ";
$sql .= 	"co.domain_uuid = :domain_uuid ";
if ($associated_contacts == 'true') {
    $sql .= "AND ";
    $sql .=     "cr.contact_uuid = :agent_uuid ";
}
if ($contact_type != "") {
    $sql .= "AND ";
    $sql .=     "co.contact_type = :contact_type ";
    $parameters['contact_type'] = $contact_type;
}
$sql .= "ORDER BY ";
$sql .=     "co.contact_nickname ";

$parameters['agent_uuid'] = $_SESSION['user']['contact_uuid'];
$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$contacts = $database->select($sql, $parameters, 'all');

//build contact list table
// $table = "<tbody>";
// foreach ($contacts as $contact) {
//     $table .= "<tr><td><label>";
//     $table .= "<input type='radio' class='agent_panel_contact' name='contact' value='".$contact['phone_number']."'>";
//     $table .= "<div id='".$contact['contact_nickname']."'>";
//     $table .= "Apelido: ".$contact['contact_nickname'];
//     $table .= "<br>Telefone: ".$contact['phone_number'];
//     $table .= "</div></label></tr>";
// }
// $table .= "</tbody>";

//create token
$object = new token;
$token = $object->create($_SERVER['PHP_SELF']);

$table = "<tbody>";
$x = 0;
foreach ($contacts as $contact) {
    $table .= "<tr id='contact_".$x."'>";
    $table .= "<td><div class='is_".($contact['is_associated'] == 1 ? '' : 'not_')."associated' data-uuid='".$contact['contact_uuid']."' onClick=\"toggleAssociate(this);\"></div></td>";
    $table .= "<td><label>";
    $table .= "<input type='checkbox' class='agent_panel_contact' name='#contact_".$x."' value='".$contact['phone_number']."'>";
    $table .= "<div>";
    $table .= "Apelido: ".$contact['contact_nickname'];
    $table .= "<br>Telefone: ".$contact['phone_number'];
    $table .= "</div></label></td>";
    $table .= "<td><div class='contact_call' onclick=\"call('".$_SESSION['agent']['extension'][0]['extension']."', '".$contact['phone_number']."', 'call');\"></div></td>";
    $table .= "</tr>";
    $x++;
}
$table .= "</tbody>";
$table .= "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

$json = [];
$json['table'] = $table;

$json['sql'] = $sql;
$json['parameters'] = $parameters;
$json['result'] = $contacts;
echo json_encode($json, JSON_UNESCAPED_UNICODE);

?>