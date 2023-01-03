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

//set the time zone
if (isset($_SESSION['domain']['time_zone']['name'])) {
    $time_zone = $_SESSION['domain']['time_zone']['name'];
}
else {
    $time_zone = date_default_timezone_get();
}
$parameters['time_zone'] = $time_zone;

//set GET variables
$call_status = $_GET['call_status'];
$unformatted_extensions = $_GET['extensions'];
foreach ($unformatted_extensions as $unformatted_extension) {
    $tmp = preg_replace('/\D/', '', $unformatted_extension);
    preg_match('/(?:0?[1-9]{2})?(9?[1-9][\d]{7})$/', $tmp, $matches);
    if($matches[1] != '') {
        $formatted_extensions[] = '%'.$matches[1].'%';
    }
}


//get cdr
$sql = "SELECT ";
//$sql .=     "caller_id_name, caller_id_number, caller_destination, source_number, destination_number, start_epoch, start_stamp ";
$sql .= "   direction, ";
$sql .= "   caller_id_number, ";
$sql .= "   destination_number, ";
$sql .= "   to_char(timezone(:time_zone, start_stamp), 'DD Mon YYYY') as start_date_formatted, ";
$sql .= "   to_char(timezone(:time_zone, start_stamp), 'HH24:MI:SS') as start_time_formatted, ";
$sql .= "   answer_epoch, ";
$sql .= "   bridge_uuid, ";
$sql .= "   sip_hangup_disposition ";
$sql .= "FROM ";
$sql .=     "v_xml_cdr ";
$sql .= "WHERE ";
$sql .= 	"domain_uuid = :domain_uuid ";
//if call status
if ($call_status == 'answered') {
    $sql .= "AND ";
    $sql .=     "answer_epoch > 0 ";
} else if ($call_status == 'missed') {
    $sql .= "AND ";
    $sql .=     "missed_call = 'true' ";
}
//if extensions
if (!empty($formatted_extensions)) {
    $x = 0;
    $sql .= "AND ";
    $sql .=     "(";
    foreach ($formatted_extensions as $formatted_extension) {
        $sql_where_array[$x] =     "caller_id_number LIKE :caller_id_number_".$x;
        $parameters['caller_id_number_'.$x] = $formatted_extension;
    }
    $sql .= implode(' OR ', $sql_where_array);	
    $sql .=     ")";
    unset($sql_where_array);
}
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
$sql .= "DESC ";
$sql .= "LIMIT 50 ";

$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$call_records = $database->select($sql, $parameters, 'all');

foreach ($call_records as $row) {
    $table .= "<tr class='list-row'>\n";

    //direction
    $table .= "<td class='middle'>\n";
    if ($row['direction'] == 'inbound' || $row['direction'] == 'local') {
        if ($row['answer_epoch'] != 0 && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
        else if ($row['answer_epoch'] != 0 && $row['bridge_uuid'] == '') { $call_result = 'voicemail'; }
        else if ($row['answer_epoch'] == 0 && $row['bridge_uuid'] == '' && $row['sip_hangup_disposition'] != 'send_refuse') { $call_result = 'cancelled'; }
        else { $call_result = 'failed'; }
    }
    else if ($row['direction'] == 'outbound') {
        if ($row['answer_epoch'] != 0 && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
        else if ($row['answer_epoch'] == 0 && $row['bridge_uuid'] != '') { $call_result = 'cancelled'; }
        else { $call_result = 'failed'; }
    }
    if (strlen($row['direction']) > 0) {
        $image_name = "icon_cdr_" . $row['direction'] . "_" . $call_result;
        if ($row['leg'] == 'b') {
            $image_name .= '_b';
        }
        $image_name .= ".png";
        $table .= "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/".escape($image_name)."' width='16' style='border: none; cursor: help;' title='".$text['label-'.$row['direction']].": ".$text['label-'.$call_result]. ($row['leg']=='b'?'(b)':'') . "'>\n";
    }
    $table .= "</td>\n";

    //caller_id_number
    $table .= "	<td class='middle no-link no-wrap'>";
    $table .= " <a href=\"javascript:void(0)\" onclick=\"call('".$_SESSION['agent']['extension'][0]['extension']."', '".$row['caller_id_number']."', 'call');\">";
    if (is_numeric($row['caller_id_number'])) {
        $table .= "		".escape(format_phone(substr($row['caller_id_number'], 0, 20))).' ';
    }
    else {
        $table .= "		".escape(substr($row['caller_id_number'], 0, 20)).' ';
    }
    $table .= " </a>";
    $table .= "	</td>\n";

    //destination_number
    $table .= "	<td class='middle no-link no-wrap'>";
    if (is_numeric($row['destination_number'])) {
        $table .= format_phone(escape(substr($row['destination_number'], 0, 20)))."\n";
    }
    else {
        $table .= escape(substr($row['destination_number'], 0, 20))."\n";
    }
    $table .= "	</td>\n";

    //start_stamp
    $table .= "	<td class='middle right no-wrap'>".$row['start_date_formatted']."</td>\n";
	$table .= "	<td class='middle right no-wrap hide-md-dn'>".$row['start_time_formatted']."</td>\n";


    // foreach ($call_record as $column) {
    //     $table .= "<td class='middle'>\n";
    //     $table .= $column;
    //     $table .= "</td>\n";
    // }
    $table .= "</tr>\n";
}
$json = [];
$json['table'] = $table;
//$json['status'] = $sql;
echo json_encode($json, JSON_UNESCAPED_UNICODE);


?>