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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        //get current active calls
        $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
        if ($fp) {
            $switch_result = event_socket_request($fp, 'api show channels as json');
            $json_array = json_decode($switch_result, true);
        }

        //check if there is call active with the current agent
        $found = false;
        if (isset($json_array['rows'])) {
            foreach($json_array['rows'] as &$field) {                 
                $presence = explode("@", $field['presence_id']);
                $presence_id = $presence[0];
                $presence_domain = $presence[1];
                if ($_SESSION['agent']['extension'][0]['extension'] == $presence_id) {
                    if ($presence_domain == $_SESSION['domain_name']) {
                        $found = true;
                        $call_uuid = $field['call_uuid'];
                        break;
                    }
                }
            }
        }

        echo "<form id='frm_destination_call' onsubmit=\"call('".$_SESSION['agent']['extension'][0]['extension']."', document.getElementById('destination_call').value, 'call'); return false;\">\n";
        echo    "<input type='text' class='formfld' id='destination_call' style='width: 100px; min-width: 100px; max-width: 100px; margin-top: 10px; text-align: center;'>\n";
        echo "</form>\n";
        break;
    
    case 'POST':
        $api_cmd = '';
        $uuid_pattern = '/[^-A-Fa-f0-9]/';
        $num_pattern = '/[^-A-Za-z0-9()*#]/';

        //get current active calls
        $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
        if ($fp) {
            $switch_result = event_socket_request($fp, 'api show channels as json');
            $json_array = json_decode($switch_result, true);
        }

        //check if there is call active with the current agent
        $found = false;
        $call_uuid = '';
        if (isset($json_array['rows'])) {
            foreach($json_array['rows'] as &$field) {                 
                $presence = explode("@", $field['presence_id']);
                $presence_id = $presence[0];
                $presence_domain = $presence[1];
                if ($_SESSION['agent']['extension'][0]['extension'] == $presence_id) {
                    if ($presence_domain == $_SESSION['domain_name']) {
                        $found = true;
                        $call_uuid = $field['call_uuid'];
                        break;
                    }
                }
            }
        }

        $extension = preg_replace($num_pattern,'',$_POST['extension']);
		$destination = preg_replace($num_pattern,'',$_POST['destination']);

        if ($found) {
            $uuid = preg_replace($uuid_pattern,'',$call_uuid);
            $api_cmd = 'uuid_transfer ' . $uuid . ' ' . $destination . ' XML ' . trim($_SESSION['user_context']);
        } else {
            $api_cmd = 'bgapi originate {origination_caller_id_number=' . $extension . ',sip_h_Call-Info=_undef_}user/' . $extension . '@' . $_SESSION['domain_name'] . ' ' . $destination . ' XML ' . trim($_SESSION['user_context']);
        }
        $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
        $switch_result = event_socket_request($fp, 'api '.$api_cmd);
        echo json_encode($switch_result);
        echo json_encode($api_cmd);
        break;
}

?>