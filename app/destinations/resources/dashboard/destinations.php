<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('destination_view') || permission_exists('destination_edit')) {
    //access granted
}
else {
    echo "access denied";
    exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get($_SESSION['domain']['language']['code'], 'app/destinations');

//update destination
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $json = [];

    $destination_uuid = $_POST['destination_uuid'];
    $action_number = $_POST['action_number'];
    $action_value = $_POST['action_value'];
    
    $token = new token;
    if (!$token->validate('/core/dashboard/index.php')) {
        $json['response']['code'] = '400';
        $json['response']['message'] = 'Bad Token';        
        echo json_encode($json);
        exit;
    }

    //get destination actions
    $sql = "SELECT ";
    $sql .=     "* ";
    $sql .= "FROM ";
    $sql .=     "v_destinations ";
    $sql .= "WHERE ";
    $sql .=     "destination_uuid = :destination_uuid ";

    $parameters['destination_uuid'] = $destination_uuid;
    $database = new database;
    $row = $database->select($sql, $parameters, 'row');
    unset($parameters);

    //assign destination variables
    $domain_uuid = $row["domain_uuid"];
    $dialplan_uuid = $row["dialplan_uuid"];
    $destination_type = $row["destination_type"];
    $destination_number = $row["destination_number"];
    $destination_condition_field = $row["destination_condition_field"];
    $destination_prefix = $row["destination_prefix"];
    $destination_trunk_prefix = $row["destination_trunk_prefix"];
    $destination_area_code = $row["destination_area_code"];
    $destination_caller_id_name = $row["destination_caller_id_name"];
    $destination_caller_id_number = $row["destination_caller_id_number"];
    $destination_cid_name_prefix = $row["destination_cid_name_prefix"];
    $destination_hold_music = $row["destination_hold_music"];
    $destination_distinctive_ring = $row["destination_distinctive_ring"];
    $destination_record = $row["destination_record"];
    $destination_accountcode = $row["destination_accountcode"];
    $destination_type_voice = $row["destination_type_voice"];
    $destination_type_fax = $row["destination_type_fax"];
    $destination_type_text = $row["destination_type_text"];
    $destination_type_emergency = $row["destination_type_emergency"];
    $destination_context = $row["destination_context"];
    $destination_conditions = $row["destination_conditions"];
    $destination_actions = $row["destination_actions"];
    $fax_uuid = $row["fax_uuid"];
    $provider_uuid = $row["provider_uuid"];
    $user_uuid = $row["user_uuid"];
    $group_uuid = $row["group_uuid"];
    $currency = $row["currency"];
    $destination_sell = $row["destination_sell"];
    $destination_buy = $row["destination_buy"];
    $currency_buy = $row["currency_buy"];
    $destination_carrier = $row["destination_carrier"];
    $destination_order = $row["destination_order"];
    $destination_enabled = $row["destination_enabled"];
    $destination_description = $row["destination_description"];

    //decode the json to an array
	$destination_conditions = json_decode($destination_conditions, true);
	$destination_actions = json_decode($destination_actions, true);
    
    //Update new action
    $action_array = explode(":", $action_value, 2);
    $destination_actions[$action_number]['destination_app'] = $action_array[0];
    $destination_actions[$action_number]['destination_data'] = $action_array[1];

    //sanitize the destination conditions
    if (is_array($destination_conditions)) {
        $i=0;
        foreach($destination_conditions as $row) {
            if (isset($row['condition_expression']) && strlen($row['condition_expression']) > 0) {
                if ($row['condition_field'] == 'caller_id_number') {
                    $row['condition_expression'] = preg_replace('#[^\+0-9\*]#', '', $row['condition_expression']);
                    $conditions[$i]['condition_field'] = $row['condition_field'];
                    $conditions[$i]['condition_expression'] = $row['condition_expression'];
                    $i++;
                }
            }
        }
    }

    //get the fax information
    if (is_uuid($fax_uuid)) {
        $sql = "select * from v_fax ";
        $sql .= "where fax_uuid = :fax_uuid ";
        //if (!permission_exists('destination_domain')) {
        //	$sql .= "and domain_uuid = :domain_uuid ";
        //}
        $parameters['fax_uuid'] = $fax_uuid;
        //$parameters['domain_uuid'] = $domain_uuid;
        $database = new database;
        $row = $database->select($sql, $parameters, 'row');
        if (is_array($row) && @sizeof($row) != 0) {
            $fax_extension = $row["fax_extension"];
            $fax_destination_number = $row["fax_destination_number"];
            $fax_name = $row["fax_name"];
            $fax_email = $row["fax_email"];
            $fax_pin_number = $row["fax_pin_number"];
            $fax_caller_id_name = $row["fax_caller_id_name"];
            $fax_caller_id_number = $row["fax_caller_id_number"];
            $fax_forward_number = $row["fax_forward_number"];
            $fax_description = $row["fax_description"];
        }
        unset($sql, $parameters, $row);
    }

    //convert the number to a regular expression
    if (isset($destination_prefix) && strlen($destination_prefix) > 0) {
        $destination_numbers['destination_prefix'] = $destination_prefix;
    }
    if (isset($destination_trunk_prefix) && strlen($destination_trunk_prefix) > 0) {
        $destination_numbers['destination_trunk_prefix'] = $destination_trunk_prefix;
    }
    if (isset($destination_area_code) && strlen($destination_area_code) > 0) {
        $destination_numbers['destination_area_code'] = $destination_area_code;
    }
    if (isset($destination_number) && strlen($destination_number) > 0) {
        $destination_numbers['destination_number'] = $destination_number;
    }
    $destination = new destinations;
    $destination_number_regex = $destination->to_regex($destination_numbers);
    unset($destination_numbers);

    //set the dialplan_uuid
    $array['destinations'][$x]["dialplan_uuid"] = $dialplan_uuid;

    //build the dialplan array
    if ($destination_type == "inbound") {
        $dialplan["app_uuid"] = "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4";
    }
    if ($destination_type == "local") {
        $dialplan["app_uuid"] = "b5242951-686f-448f-8b4e-5031ba0601a4";
    }
    $dialplan["dialplan_uuid"] = $dialplan_uuid;
    $dialplan["domain_uuid"] = $domain_uuid;
    $dialplan["dialplan_name"] = ($dialplan_name != '') ? $dialplan_name : format_phone($destination_area_code.$destination_number);
    $dialplan["dialplan_number"] = $destination_area_code.$destination_number;
    $dialplan["dialplan_context"] = $destination_context;
    $dialplan["dialplan_continue"] = "false";
    $dialplan["dialplan_order"] = $destination_order;
    $dialplan["dialplan_enabled"] = $destination_enabled;
    $dialplan["dialplan_description"] = ($dialplan_description != '') ? $dialplan_description : $destination_description;
    $dialplan_detail_order = 10;

    //set the dialplan detail type
    if (strlen($destination_condition_field) > 0) {
        $dialplan_detail_type = $destination_condition_field;
    }
    elseif (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
        $dialplan_detail_type = $_SESSION['dialplan']['destination']['text'];
    }
    else {
        $dialplan_detail_type = "destination_number";
    }

    //set the last destination_app and destination_data variables
    foreach($destination_actions as $destination_action) {
        if (isset($destination_action['destination_app']) && $destination_action['destination_app'] != '') {
            $destination_app = $destination_action['destination_app'];
            $destination_data = $destination_action['destination_data'];
        }
    }    

    //build the xml dialplan
    $dialplan["dialplan_xml"] = "<extension name=\"".$dialplan["dialplan_name"]."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";

    //add the dialplan xml destination conditions
    if (is_array($conditions)) {
        foreach($conditions as $row) {
            if (is_numeric($row['condition_expression']) && strlen($destination_number) == strlen($row['condition_expression']) && strlen($destination_prefix) > 0) {
                $condition_expression = '\+?'.$destination_prefix.'?'.$row['condition_expression'];
            }
            else {
                $condition_expression = str_replace("+", "\+", $row['condition_expression']);
            }
            $dialplan["dialplan_xml"] .= "	<condition field=\"".$row['condition_field']."\" expression=\"^".$condition_expression."$\"/>\n";
        }
    }

    $dialplan["dialplan_xml"] .= "	<condition field=\"".$dialplan_detail_type."\" expression=\"".$destination_number_regex."\">\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_uuid=".$_SESSION['domain_uuid']."\" inline=\"true\"/>\n";
    $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"domain_name=".$_SESSION['domain_name']."\" inline=\"true\"/>\n";

    //add this only if using application bridge
    if ($destination_app == 'bridge') {
            $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"hangup_after_bridge=true\" inline=\"true\"/>\n";
            $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"continue_on_fail=true\" inline=\"true\"/>\n";
    }

    if (strlen($destination_cid_name_prefix) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"effective_caller_id_name=".$destination_cid_name_prefix."#\${caller_id_name}\" inline=\"false\"/>\n";
    }
    if (strlen($destination_record) > 0 && $destination_record == 'true') {
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_name=\${uuid}.\${record_ext}\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_append=true\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"record_in_progress=true\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"recording_follow_transfer=true\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"record_session\" data=\"\${record_path}/\${record_name}\" inline=\"false\"/>\n";
    }
    if (strlen($destination_hold_music) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"hold_music=".$destination_hold_music."\" inline=\"true\"/>\n";
    }
    if (strlen($destination_distinctive_ring) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"sip_h_Alert-Info=".$destination_distinctive_ring."\" inline=\"true\"/>\n";
    }
    if (strlen($destination_accountcode) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"export\" data=\"accountcode=".$destination_accountcode."\" inline=\"true\"/>\n";
    }
    if (strlen($destination_carrier) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"carrier=".$destination_carrier."\" inline=\"true\"/>\n";
    }
    if (strlen($fax_uuid) > 0) {
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"tone_detect_hits=1\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"set\" data=\"execute_on_tone_detect=transfer ".$fax_extension." XML \${domain_name}\" inline=\"true\"/>\n";
        $dialplan["dialplan_xml"] .= "		<action application=\"tone_detect\" data=\"fax 1100 r +3000\"/>\n";
    }

    //add the actions to the dialplan_xml
    foreach($destination_actions as $destination_action) {
        if (isset($destination_action['destination_app']) && $destination_action['destination_app'] != '') {
            if ($destination->valid($destination_action['destination_app'].':'.$destination_action['destination_data'])) {
                $dialplan["dialplan_xml"] .= "		<action application=\"".$destination_action['destination_app']."\" data=\"".$destination_action['destination_data']."\"/>\n";
            }
        }
    }

    $dialplan["dialplan_xml"] .= "	</condition>\n";
    $dialplan["dialplan_xml"] .= "</extension>\n";

    //dialplan details
    if ($_SESSION['destinations']['dialplan_details']['boolean'] == "true") {

        //set initial value of the row id
        $y = 0;

        //increment the dialplan detail order
        $dialplan_detail_order = $dialplan_detail_order + 10;

        //add the dialplan detail destination conditions
        if (is_array($conditions)) {
            foreach ($conditions as $row) {
                //prepare the expression
                if (is_numeric($row['condition_expression']) && strlen($destination_number) == strlen($row['condition_expression']) && strlen($destination_prefix) > 0) {
                    $condition_expression = '\+?' . $destination_prefix . '?' . $row['condition_expression'];
                } else {
                    $condition_expression = str_replace("+", "\+", $row['condition_expression']);
                }

                //add to the dialplan_details array
                $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
                $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
                $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $row['condition_field'];
                $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = '^' . $condition_expression . '$';
                $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
                $y++;

                //increment the dialplan detail order
                $dialplan_detail_order = $dialplan_detail_order + 10;
            }
        }

        //check the destination number
        $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
        $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "condition";
        if (strlen($destination_condition_field) > 0) {
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $destination_condition_field;
        } elseif (strlen($_SESSION['dialplan']['destination']['text']) > 0) {
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $_SESSION['dialplan']['destination']['text'];
        } else {
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "destination_number";
        }
        $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $destination_number_regex;
        $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
        $y++;

        //increment the dialplan detail order
        $dialplan_detail_order = $dialplan_detail_order + 10;

        //add this only if using application bridge
        if ($destination_app == 'bridge') {
            //add hangup_after_bridge
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hangup_after_bridge=true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add continue_on_fail
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "continue_on_fail=true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;
        }

        //increment the dialplan detail order
        $dialplan_detail_order = $dialplan_detail_order + 10;

        //set the caller id name prefix
        if (strlen($destination_cid_name_prefix) > 0) {
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "effective_caller_id_name=" . $destination_cid_name_prefix . "#\${caller_id_name}";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "false";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;
        }

        //set the call accountcode
        if (strlen($destination_accountcode) > 0) {
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "export";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "accountcode=" . $destination_accountcode;
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;
        }

        //set the call carrier
        if (strlen($destination_carrier) > 0) {
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "carrier=$destination_carrier";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;
        }

        //set the hold music
        if (strlen($destination_hold_music) > 0) {
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "export";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "hold_music=" . $destination_hold_music;
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;
        }

        //set the distinctive ring
        if (strlen($destination_distinctive_ring) > 0) {
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "export";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "sip_h_Alert-Info=" . $destination_distinctive_ring;
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;
        }

        //add fax detection
        if (is_uuid($fax_uuid)) {

            //add set tone detect_hits=1
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "tone_detect_hits=1";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //execute on tone detect
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "execute_on_tone_detect=transfer " . $fax_extension . " XML \${domain_name}";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add tone_detect fax 1100 r +5000
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "tone_detect";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "fax 1100 r +5000";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;
        }

        //add option record to the dialplan
        if ($destination_record == "true") {

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_name=\${uuid}.\${record_ext}";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_append=true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "record_in_progress=true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "set";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "recording_follow_transfer=true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "true";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;

            //add a variable
            $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
            $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
            $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = "record_session";
            $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = "\${record_path}/\${record_name}";
            $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = "false";
            $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;
            $y++;

            //increment the dialplan detail order
            $dialplan_detail_order = $dialplan_detail_order + 10;
        }

        //add the actions
        foreach ($destination_actions as $field) {
            $action_app = $field['destination_app'];
            $action_data = $field['destination_data'];
            if (isset($action_array[0]) && $action_array[0] != '') {
                if ($destination->valid($action_app . ':' . $action_data)) {
                    //add to the dialplan_details array
                    $dialplan["dialplan_details"][$y]["domain_uuid"] = $domain_uuid;
                    $dialplan["dialplan_details"][$y]["dialplan_uuid"] = $dialplan_uuid;
                    $dialplan["dialplan_details"][$y]["dialplan_detail_tag"] = "action";
                    $dialplan["dialplan_details"][$y]["dialplan_detail_type"] = $action_app;
                    $dialplan["dialplan_details"][$y]["dialplan_detail_data"] = $action_data;
                    $dialplan["dialplan_details"][$y]["dialplan_detail_order"] = $dialplan_detail_order;

                    //set inline to true
                    if ($action_app == 'set' || $action_app == 'export') {
                        $dialplan["dialplan_details"][$y]["dialplan_detail_inline"] = 'true';
                    }
                    $y++;

                    //increment the dialplan detail order
                    $dialplan_detail_order = $dialplan_detail_order + 10;
                }
            }
        }

        //delete the previous details
        $sql = "delete from v_dialplan_details ";
        $sql .= "where dialplan_uuid = :dialplan_uuid ";
        if (!permission_exists('destination_domain')) {
            $sql .= "and (domain_uuid = :domain_uuid or domain_uuid is null) ";
            $parameters['domain_uuid'] = $domain_uuid;
        }
        $parameters['dialplan_uuid'] = $dialplan_uuid;
        $database = new database;
        $database->execute($sql, $parameters);
        $json['delete'] = $database->message;
        unset($sql, $parameters);
    }

    //build the destination array
    $array['destinations'][$x]["domain_uuid"] = $domain_uuid;
    $array['destinations'][$x]["destination_uuid"] = $destination_uuid;
    $array['destinations'][$x]["dialplan_uuid"] = $dialplan_uuid;
    $array['destinations'][$x]["fax_uuid"] = $fax_uuid;
    if (permission_exists('provider_edit')) {
        $array['destinations'][$x]["provider_uuid"] = $provider_uuid;
    }
    if (permission_exists('user_edit')) {
        $array['destinations'][$x]["user_uuid"] = $user_uuid;
    }
    if (permission_exists('group_edit')) {
        $array['destinations'][$x]["group_uuid"] = $group_uuid;
    }
    $array['destinations'][$x]["destination_type"] = $destination_type;
    if (permission_exists('destination_condition_field')) {
        $array['destinations'][$x]["destination_condition_field"] = $destination_condition_field;
    }
    if (permission_exists('destination_number')) {
        $array['destinations'][$x]["destination_number"] = $destination_number;
        $array['destinations'][$x]["destination_number_regex"] = $destination_number_regex;
        $array['destinations'][$x]["destination_prefix"] = $destination_prefix;
    }
    if (permission_exists('destination_trunk_prefix')) {
        $array['destinations'][$x]["destination_trunk_prefix"] = $destination_trunk_prefix;
    }
    if (permission_exists('destination_area_code')) {
        $array['destinations'][$x]["destination_area_code"] = $destination_area_code;
    }
    $array['destinations'][$x]["destination_caller_id_name"] = $destination_caller_id_name;
    $array['destinations'][$x]["destination_caller_id_number"] = $destination_caller_id_number;
    $array['destinations'][$x]["destination_cid_name_prefix"] = $destination_cid_name_prefix;
    $array['destinations'][$x]["destination_context"] = $destination_context;
    if (permission_exists("destination_hold_music")) {
        $array['destinations'][$x]["destination_hold_music"] = $destination_hold_music;
    }
    if (permission_exists("destination_distinctive_ring")) {
        $array['destinations'][$x]["destination_distinctive_ring"] = $destination_distinctive_ring;
    }
    $array['destinations'][$x]["destination_record"] = $destination_record;
    $array['destinations'][$x]["destination_accountcode"] = $destination_accountcode;
    $array['destinations'][$x]["destination_type_voice"] = $destination_type_voice ? 1 : null;
    $array['destinations'][$x]["destination_type_fax"] = $destination_type_fax ? 1 : null;
    $array['destinations'][$x]["destination_type_text"] = $destination_type_text ? 1 : null;
    if (permission_exists('destination_emergency')) {
        $array['destinations'][$x]["destination_type_emergency"] = $destination_type_emergency ? 1 : null;
    }

    //prepare the destination_conditions json
    if (is_array($conditions)) {
        $array['destinations'][$x]["destination_conditions"] = json_encode($conditions);
        unset($conditions);
    } else {
        $array['destinations'][$x]["destination_conditions"] = '';
    }

    //prepare the $actions array
    $y = 0;
    foreach ($destination_actions as $destination_action) {
        $action_app = $destination_action['destination_app'];
        $action_data = $destination_action['destination_data'];
        if (isset($destination_action['destination_app']) && $destination_action['destination_app'] != '') {
            if ($destination->valid($action_app . ':' . $action_data)) {
                $actions[$y]['destination_app'] = $action_app;
                $actions[$y]['destination_data'] = $action_data;
                $y++;
            }
        }
    }
    $array['destinations'][$x]["destination_actions"] = json_encode($actions);
    $array['destinations'][$x]["destination_order"] = $destination_order;
    $array['destinations'][$x]["destination_enabled"] = $destination_enabled;
    $array['destinations'][$x]["destination_description"] = $destination_description;
    $x++;

    //prepare the array
    $array['dialplans'][] = $dialplan;
    unset($dialplan);

    //add the dialplan permission
    $p = new permissions;
    $p->add("dialplan_add", 'temp');
    $p->add("dialplan_detail_add", 'temp');
    $p->add("dialplan_edit", 'temp');
    $p->add("dialplan_detail_edit", 'temp');

    //save the dialplan
    $database = new database;
    $database->app_name = 'destinations';
    $database->app_uuid = '5ec89622-b19c-3559-64f0-afde802ab139';
    $database->save($array);
    $response = $database->message;

    //remove the temporary permission
    $p->delete("dialplan_add", 'temp');
    $p->delete("dialplan_detail_add", 'temp');
    $p->delete("dialplan_edit", 'temp');
    $p->delete("dialplan_detail_edit", 'temp');

    //clear the cache
    $cache = new cache;
    if ($_SESSION['destinations']['dialplan_mode']['text'] == 'multiple') {
        $cache->delete("dialplan:" . $destination_context);
    }
    if ($_SESSION['destinations']['dialplan_mode']['text'] == 'single') {
        if (isset($destination_prefix) && is_numeric($destination_prefix) && isset($destination_number) && is_numeric($destination_number)) {
            $cache->delete("dialplan:" . $destination_context . ":" . $destination_prefix . $destination_number);
            $cache->delete("dialplan:" . $destination_context . ":+" . $destination_prefix . $destination_number);
        }
        if (isset($destination_number) && substr($destination_number, 0, 1) == '+' && is_numeric(str_replace('+', '', $destination_number))) {
            $cache->delete("dialplan:" . $destination_context . ":" . $destination_number);
        }
        if (isset($destination_number) && is_numeric($destination_number)) {
            $cache->delete("dialplan:" . $destination_context . ":" . $destination_number);
        }
    }

    //return new token
    $token = $token->create('/core/dashboard/index.php');
    $json['token']['name'] = $token['name'];
    $json['token']['value'] = $token['hash'];

    $json['test'] = $destination_actions;
    $json['response'] = $response;

    
    
    
    echo json_encode($json, JSON_UNESCAPED_UNICODE);
    return;
}

//get destinations
$sql = "SELECT ";
$sql .=     "destination_uuid, ";
$sql .=     "destination_number, ";
$sql .=     "destination_actions ";
$sql .= "FROM ";
$sql .=     "v_destinations ";
$sql .= "WHERE ";
$sql .=     "destination_type = :destination_type ";
$sql .= "AND ";
$sql .=     "domain_uuid = :domain_uuid ";
$sql .= "ORDER BY ";
$sql .=     "destination_number ";
$sql .= "ASC ";

$parameters['destination_type'] = 'inbound';
$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
$database = new database;
$destinations = $database->select($sql, $parameters, 'all');
$destination = new destinations;

//create token
$object = new token;
$token = $object->create($_SERVER['PHP_SELF']);

//show content
echo "<div class='action_bar sub'>\n";
echo "	<div class='heading'><b>".$text['title-destinations']." (".count($destinations).")</b></div>\n";
echo "	<div class='actions'>\n";
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";

echo "<form method='post' id='destinations_edit_form'>";

echo "<table class='list'>";
echo "  <tr class='list-header'>";
echo "      <th nowrap='nowrap'>".$text['label-destination_number']."</th>";
echo "      <th nowrap='nowrap'>".$text['label-destination_actions']."</th>";
echo "  </tr>";

$x = 0;
foreach ($destinations as $row) {
    echo "<tr class='list-row'>";
    echo "  <td class='no-link'>";
    echo "      <div>".$row['destination_number']."</div>";
    echo "  </td>";
    echo "  <td class='no-link'>";
    //prepare the destination actions
    $destination_actions = json_decode($row['destination_actions'], true);
    
    if (is_array($destination_actions)) {
        $y = 0;
        echo "<div id='".$row['destination_uuid']."'>";
        foreach($destination_actions as $action) {            
            echo $destination->select('dialplan', 'destination_actions_'.$x.'_'.$y, $action['destination_app'].':'.$action['destination_data']);            
            $y++;
        }        
        echo "</div>";
    } 
    $x++;   
    echo "  </td>";
    echo "</tr>";
}
echo "</table>";
echo "<input type='hidden' id='token' name='".$token['name']."' value='".$token['hash']."'>\n";
echo "</form>\n";

unset($sql, $parameters, $destinations);

?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

$("select[name^='destination_actions']").on('change', function() {

    var token = document.getElementById('token');
    
    var data = [{name: 'destination_uuid', value: this.parentElement.id},
                {name: 'action_number', value: this.name.match(/\d+$/)[0]},
                {name: 'action_value', value: this.value},
                {name: token.getAttribute("name"), value: token.value}];

    $.post({
            url: "/app/destinations/resources/dashboard/destinations.php", 
            data: data,
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                if (data['response']['code'] == '200') {
                    display_message('SUCESSO', 'positive');
                    token.setAttribute("name", data['token']['name']);
                    token.value = data['token']['value'];
                }
                else {
                    display_message('ERROR', 'negative');
                }
                console.log(data['test']);
                console.log(data['response']);
                console.log(data['token']);
            }});
});

</script>