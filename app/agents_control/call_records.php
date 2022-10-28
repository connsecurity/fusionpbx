<?php

//includes
include_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('xml_cdr_view')) {
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

//build query
$sql = "SELECT ";
$sql .=     "direction, ";
$sql .=     "caller_id_number, ";
$sql .=     "destination_number, ";
$sql .=     "start_stamp, ";
$sql .=     "answer_epoch ";
$sql .= "FROM ";
$sql .=     "v_xml_cdr ";
$sql .= "WHERE ";
$sql .=     "domain_uuid = :domain_uuid ";
$sql .= "AND ";
$sql .=     "extension_uuid = :extension_uuid ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$parameters['extension_uuid'] = $filters['extension_uuid'];

$database = new database;
$call_records = $database->select($sql, $parameters, 'all');
unset($sql, $parameters, $filters);

//build table
$table = "<table class='list'>";
$table .= "<tbody>";
$table .= "<tr class='list-header'>";
//columns
foreach ($call_records[0] as $key => $value) {
    $table .= "<th class='shrink'>";
    $table .= $text['label-'.$key];    
    $table .= "</th>";
}
$table .= "</tr>";
//rows
foreach ($call_records as $call_record) {
    $table .= "<tr class='list-row'>";
    foreach ($call_record as $value) {
        $table .= "<td class='middle'>";
        $table .= $value;
        $table .= "</td>";
    }
    $table .= "</tr>";
}

$table .= "</tbody></table>";
echo json_encode($table);


?>