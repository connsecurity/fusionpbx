<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('user_view')) {
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

//build query
$sql = "SELECT ";
$sql .= "   ex.extension, ";
$sql .= "	co.contact_nickname as nickname, ";
$sql .= "	co.username, ";
$sql .= "	ex.extension_uuid ";
$sql .= "FROM ";
$sql .= "	v_extensions as ex ";
$sql .= "INNER JOIN ";
$sql .= "	v_extension_users as eu ";
$sql .= "ON ";
$sql .= "	ex.extension_uuid = eu.extension_uuid ";
if (isset($filters['extension']) && $filters['extension'] != '') {  //if there is an extension filter
    $sql .= "AND ";
    $sql .= "ex.extension = :extension ";
    $parameters['extension'] = $filters['extension'];
}
$sql .= "LEFT JOIN ";
$sql .= "	( ";
$sql .= "		v_users LEFT JOIN v_contacts ON v_users.contact_uuid = v_contacts.contact_uuid ";
$sql .= "	) as co ";
$sql .= "ON ";
$sql .= "	eu.user_uuid = co.user_uuid ";
$sql .= "AND ";
$sql .= "	ex.domain_uuid = :domain_uuid ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$extensions = $database->select($sql, $parameters, 'all');
//var_dump($extensions);

unset($sql, $parameters, $filters);

// foreach ($extensions as $extension) {
//     echo "<br>";
//     echo "Ramal: ".$extension['extension'];
//     echo "<br>";
//     echo "Apelido: ".$extension['nickname'];
//     echo "<br>";
//     echo "Usuario: ".$extension['username'];
//     echo "<br>";
//     echo "UUID: ".$extension['extension_uuid'];
//     echo "<br>------------------------------------------------ <br>";

    
// }

$table = "<table width='100%'>";
$table .= "<tr>";

foreach ($extensions as $extension) {
    $table .= "<td><label>";
    $table .= "<input type='radio' class='agent_control' name='operator' value='".$extension['extension_uuid']."' onclick='showContacts();'>";
    $table .= "<div id='".$extension['extension']."' class='ac_ext'>";
    $table .= "Ramal: ".$extension['extension'];
    $table .= "<br>Apelido: ".$extension['nickname'];
    $table .= "<br>Usuario: ".$extension['username'];
}

$table .= "</div></td></tr></table>";
echo json_encode($table);




// echo "<form id='dashboard' method='POST' onsubmit='setFormSubmitting()'>\n";
// echo "<div class='action_bar' id='action_bar'>\n";
// echo "	<div class='heading'><b>".$text['title-agents']."</b></div>\n";
// echo "	<div class='actions'>\n";
// echo "	</div>\n";
// echo "</div>\n";
// echo "<input type='hidden' id='widget_order' name='widget_order' value='' />\n";
// echo "</form>\n";

// $x = 0;
// $agent_uuid = '1232';

// echo "<div class='hud_box'>\n";
// echo "<input type='radio' id='agent_".$x."' name='agent_uuid' value='".$agent_uuid."'> <label for='html'>oi</label><br>";
// echo "</div>"

?>