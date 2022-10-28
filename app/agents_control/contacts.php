<?php

//includes
include_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('contact_view')) {
    //access granted
}
else {
    echo "access denied";
    exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//get data
if (is_array($_GET['filters'])) {
    $filters = $_GET['filters'];
}

// foreach ($_GET['filters'] as $key => $value) {
//     echo $key."[".gettype($value)."] = " . $value . "<br>";
// }

$sql = "SELECT ";
$sql .=	    "ex.extension_setting_value, ";
$sql .=		"co.contact_nickname as nickname, ";
$sql .=		"co.phone_number as number ";
$sql .=	"FROM ";
$sql .=	    "(v_contacts inner join v_contact_phones using (contact_uuid)) as co ";
$sql .=	"INNER JOIN ";
$sql .=		"v_extension_settings as ex ";
$sql .=	"ON	";
$sql .=	    "ex.domain_uuid = :domain_uuid ";
$sql .=	"AND ";
$sql .=		"ex.extension_uuid = :extension_uuid ";
$sql .=	"AND ";
$sql .=		"ex.extension_setting_name = 'contact' ";
$sql .=	"AND ";
$sql .=		"ex.extension_setting_enabled = true ";
$sql .=	"AND ";
$sql .=		"ex.extension_setting_value::uuid = co.contact_uuid ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$parameters['extension_uuid'] = $filters['extension_uuid'];
$database = new database;
$contacts = $database->select($sql, $parameters, 'all');
unset($sql, $parameters, $filters);

$table = "<table width='100%'>";
$table .= "<tr>";

foreach ($contacts as $contact) {
    $table .= "<td><label>";
    $table .= "<input type='radio' class='agent_control' name='contact' value='".$contact['number']."' onclick='showCallRecords();'>";
    $table .= "<div id='".$contact['nickname']."' class='ac_ext'>";
    $table .= "Apelido: ".$contact['nickname'];
    $table .= "<br>Telefone: ".$contact['number'];
}

$table .= "</div></td></tr></table>";
echo json_encode($table);

?>