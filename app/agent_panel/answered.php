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

//get agent extensions
if(!isset($_SESSION['agent']['extension'])){

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
    
    $x = 0;
    foreach ($result as $row) {
        $_SESSION['agent']['extension'][$x]['extension'] = $row['extension'];
        $_SESSION['agent']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
        $_SESSION['agent']['extension'][$x]['start_epoch'] = $row['start_epoch'];        
        $_SESSION['agent']['extension'][$x]['end_epoch'] = $row['end_epoch'];
        $x++;
    }
    unset($sql, $parameters, $result);

    if (!isset($_SESSION['agent']['extension']) || $_SESSION['agent']['extension'][0]['extension_uuid'] == '') {
        $x = 0;
        foreach ($_SESSION['user']['extension'] as $extension) {
            $_SESSION['agent']['extension'][$x]['extension'] = $extension['user'];
            $_SESSION['agent']['extension'][$x]['extension_uuid'] = $extension['extension_uuid'];
            $x++;
        }
    }
    unset($x);
    
}

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