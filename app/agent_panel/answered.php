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

//get cdr
$sql = "SELECT ";
//$sql .=     "caller_id_name, caller_id_number, caller_destination, source_number, destination_number, start_epoch, start_stamp ";
$sql .=     "direction, caller_id_number, destination_number, start_stamp, answer_epoch ";
$sql .= "FROM ";
$sql .=     "v_xml_cdr ";
$sql .= "WHERE ";
$sql .= 	"domain_uuid = :domain_uuid ";
$sql .= "AND ";
$sql .=     "answer_epoch > 0 ";
$sql .= "AND ";
$x = 0;
foreach ($_SESSION['agent']['extension'] as $extension) {
    $sql_where_array[$x] = "(";
    $sql_where_array[$x] .=     "extension_uuid = :extension_uuid_".$x;
    $parameters['extension_uuid_'.$x] = $extension['extension_uuid'];
    if($extension['start_epoch'] != '') {
        $sql_where_array[$x] .= " AND ";
        $sql_where_array[$x] .=     "start_epoch >= :start_epoch_".$x;
        $parameters['start_epoch_'.$x] = $extension['start_epoch'];
    }
    if ($extension['end_epoch'] != '') {
        $sql_where_array[$x] .= " AND ";
        $sql_where_array[$x] .=     "end_epoch < :end_epoch_".$x;
        $parameters['end_epoch_'.$x] = $extension['end_epoch'];
    }
    $sql_where_array[$x] .= ") ";
    $x++;
}
$sql .= implode(' OR ', $sql_where_array);	
$sql .= "ORDER BY ";
$sql .= 	"start_epoch ";
$sql .= "ASC ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$call_records = $database->select($sql, $parameters, 'all');

foreach ($call_records as $call_record) {
    $table .= "<tr class='list-row'>";
    foreach ($call_record as $column) {
        $table .= "<td class='middle'>";
        $table .= $column;
        //$table .= "</td>";
    }
    //$table .= "</tr>";
}

echo json_encode($table);


?>