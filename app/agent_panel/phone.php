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

//get current active calls
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if ($fp) {
    $switch_result = event_socket_request($fp, 'api show channels as json');
    $json_array = json_decode($switch_result, true);
}

//check if there is call active with the current agent
$found = false;
$channel_uuid = '';
$channel = null;
if (isset($json_array['rows'])) {
    foreach($json_array['rows'] as &$field) {                 
        $presence = explode("@", $field['presence_id']);
        $presence_id = $presence[0];
        $presence_domain = $presence[1];
        if ($_SESSION['agent']['extension'][0]['extension'] == $presence_id) {
            if ($presence_domain == $_SESSION['domain_name']) {
                $found = true;
                $channel_uuid = $field['uuid'];
                $channel = $field;

                break;
            }
        }
    }
}
//$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
$channel_dump = json_decode(event_socket_request($fp, 'api uuid_dump ' .$channel_uuid. ' json'), true);

//show extension status
$json = [];
$in_call = false;
$html = "";
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {    
    
    case 'POST':
        $api_cmd = '';
        $uuid_pattern = '/[^-A-Fa-f0-9]/';
        $num_pattern = '/[^-A-Za-z0-9()*#]/';        

        $extension = preg_replace($num_pattern,'',$_POST['extension']);
		$destination = preg_replace($num_pattern,'',$_POST['destination']);
        $direction = (user_exists($destination) ? 'local' : 'outbound');
        $operation = $_POST['operation'];

        if ($found && ($operation == 'transfer' || $operation == 'auto')) {
            $uuid = preg_replace($uuid_pattern,'',$channel_uuid);
            $api_cmd = 'uuid_transfer ' . $uuid . ' -bleg ' . $destination . ' XML ' . trim($_SESSION['user_context']);
        } elseif ($found && $operation == 'hangup'){
            $uuid = preg_replace($uuid_pattern,'',$channel_uuid);
            $api_cmd = 'uuid_kill ' . $uuid;
        } elseif ($operation == 'call' || $operation == 'auto') {
            $api_cmd = 'bgapi originate {origination_caller_id_number='.$extension;
            $api_cmd .= ',hangup_after_bridge=true';
            $api_cmd .= ',call_direction='.$direction;
            $api_cmd .= ',sip_to_user='.$destination;
            $api_cmd .= ',sip_h_Call-Info=_undef_}';
            $api_cmd .= 'user/'.$extension.'@'.$_SESSION['domain_name'].' '.$destination.' XML '.trim($_SESSION['user_context']);
        }
        $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
        $switch_result = event_socket_request($fp, 'api '.$api_cmd);

        $json['switch_result'] = $switch_result;
        $json['api_cmd'] = $api_cmd;

    case 'GET':  
        //check registered status
        //$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
        $registered = event_socket_request($fp, 'api sofia_contact '.$_SESSION['agent']['extension'][0]['extension'].'@'.$_SESSION['domain_name']);
        $json['registered'] = (preg_match("/^error/", $registered) ? $text['status-not_registered'] : $text['status-registered']);       

        if ($found) {
            $callstate = $channel['callstate'];

            if ($callstate == 'RINGING' || $callstate == 'EARLY' || $callstate == 'RING_WAIT') {
                $start_epoch = $channel['created_epoch'];
                $status = $text['status-ringing'];
            } elseif ($callstate == 'ACTIVE') {
                $start_epoch = substr($channel_dump['Caller-Channel-Answered-Time'], 0, -6);
                $status = $text['status-active'];
                $in_call = true;
            } else {
                $start_epoch = $channel['created_epoch'];
                $status = "Other";
            }
            //calculate and set the call length
            $call_length_seconds = time() - $start_epoch;
            $call_length_hour = floor($call_length_seconds/3600);
            $call_length_min = floor($call_length_seconds/60 - ($call_length_hour * 60));
            $call_length_sec = $call_length_seconds - (($call_length_hour * 3600) + ($call_length_min * 60));
            $call_length_hour = sprintf("%02d", $call_length_hour);
            $call_length_min = sprintf("%02d", $call_length_min);
            $call_length_sec = sprintf("%02d", $call_length_sec);
            $call_length = $call_length_hour.':'.$call_length_min.':'.$call_length_sec;

            $html .= "<div>".$status."</div>";
            $html .= "<div>".$call_length."</div>";
        } else {
            $html = "<div>".$text['status-available']."</div>";
            $html .= "<div>--:--:--</div>";
        }
        break;
}
$json['html'] = $html;
$json['in_call'] = $in_call;
$json['channel'] = $channel;
$json['channel_dump'] = $channel_dump;

echo json_encode($json);
?>