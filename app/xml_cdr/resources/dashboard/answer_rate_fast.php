<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('xml_cdr_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//set the time zone
	if (isset($_SESSION['domain']['time_zone']['name'])) {
		$time_zone = $_SESSION['domain']['time_zone']['name'];
	}
	else {
		$time_zone = date_default_timezone_get();
	}

//load extensions from the user
    // $sql = "select 
	// 			z.extension_uuid
	// 		from
	// 			v_contact_settings as x
	// 		inner join
	// 			v_users as y
	// 		on
	// 			x.contact_uuid = y.contact_uuid
	// 		inner join
	// 			v_extensions as z
	// 		on
	// 			x.contact_setting_value = z.call_group			
	// 		where
	// 			y.user_uuid = :user_uuid
	// 			and
	// 			x.contact_setting_subcategory = 'inbound_config'
	// 			and
	// 			x.contact_setting_enabled = 'true'";

	// $parameters['user_uuid'] = $_SESSION['user_uuid'];
	// $database = new database;
	// $call_groups_extensions = $database->select($sql, $parameters, 'all');

	// if (is_array($call_groups_extensions) && sizeof($call_groups_extensions) != 0) {
	// 	foreach ($call_groups_extensions as $extension) {
	// 		$extensions_list[] = $extension['extension_uuid'];
	// 	}
	// }
    
    // unset($sql, $parameters, $call_groups_extensions);

    if ($_GET['document'] == 'ready') {

        //set delimiters    
        $today = date_create("today", timezone_open($time_zone));
        $today = date_timestamp_get($today);  
        $tomorrow = strtotime("+1 day", $today);
        $parameters['start_last_month'] = strtotime("first day of last month 00:00:00", $today); // first day of previous month
        $parameters['end_last_month'] = strtotime("last day of last month 23:59:59", $today); //last day of previous month 
        $parameters['start_this_month'] = strtotime("first day of this month 00:00:00", $today); //first day of current month    
        $parameters['start_last_week'] = strtotime("-2 week sunday 00:00:00", $tomorrow); //start of previous week
        $parameters['end_last_week'] = strtotime("-1 week saturday 23:59:59", $tomorrow); //end of previous week
        $parameters['start_this_week'] = strtotime("last sunday 00:00:00", $tomorrow); //start of this week
        $parameters['start_yesterday'] = strtotime("yesterday", $today);
        $parameters['end_yesterday'] = strtotime("yesterday 23:59:59", $today);
        $parameters['start_today'] = strtotime("00:00:00", $today);
        $parameters['start_select'] = $parameters['start_last_month'];//strtotime("first day of last month 00:00:00", $today);        

    //select the averages
        $sql = "
                select 
                    (count(case when missed_call = false and start_epoch >= :start_today then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_today then 1 end), 0)) as today,

                    (count(case when missed_call = false and start_epoch >= :start_yesterday and start_epoch <= :end_yesterday then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_yesterday and start_epoch <= :end_yesterday then 1 end), 0)) as yesterday,

                    (count(case when missed_call = false and start_epoch >= :start_this_week then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_this_week then 1 end), 0)) as this_week,

                    (count(case when missed_call = false and start_epoch >= :start_last_week and start_epoch <= :end_last_week then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_last_week and start_epoch <= :end_last_week then 1 end), 0)) as last_week,

                    (count(case when missed_call = false and start_epoch >= :start_this_month then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_this_month then 1 end), 0)) as this_month,

                    (count(case when missed_call = false and start_epoch >= :start_last_month and start_epoch <= :end_last_month then 1 end) * 100.0
                    /nullif(count(case when start_epoch >= :start_last_month and start_epoch <= :end_last_month then 1 end), 0)) as last_month
                from
                    v_xml_cdr
                where
                    domain_uuid = :domain_uuid ";
                if (is_array($extensions_list) && sizeof($extensions_list) != 0)
                {
                    $x = 0;
                    foreach ($extensions_list as $extension_uuid) {
                        $sql_where_array[] = "extension_uuid = :extension_uuid_".$x;
                        $parameters['extension_uuid_'.$x] = $extension_uuid;
                        $x++;
                    }
                    $sql .= "and (".implode(' or ', $sql_where_array).") ";
                    unset($sql_where_array);				
                }
                $sql .= "
                and
                    progress_epoch > 0
                and
                    direction = 'inbound'
                and
                    caller_id_number ~ '.{6}'
                and
                    start_epoch >= :start_select";            

        $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
        $database = new database;
        $result = $database->select($sql, $parameters, 'row');        

    //define row styles
        $c = 0;
        $row_style["0"] = "row_style0";
        $row_style["1"] = "row_style1";

    //time to answer
        $html = "<div class='hud_box'>\n";

    //draw doughnut chart        
        $html .= "<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;'>";
        $html .= "    <div style='width: 175px; height: 175px;'><canvas id='answer_rate_chart'></canvas></div>";
        $html .= "</div>";

        $html .= "<script>";
        $html .= "    var answer_rate_chart_context = document.getElementById('answer_rate_chart').getContext('2d');";
        $html .= "    const answer_rate_chart_data = {";
        $html .= "        datasets: [{";
        $html .= "            data: ['".$result["today"]."', 0.00001],";
        $html .= "            backgroundColor: ['".$_SESSION['dashboard']['answer_rate_chart_main_background_color']['text']."',";
        $html .= "            '".$_SESSION['dashboard']['missed_calls_chart_sub_background_color']['text']."'],";
        $html .= "            borderColor: '".$_SESSION['dashboard']['answer_rate_chart_border_color']['text']."',";
        $html .= "            borderWidth: '".$_SESSION['dashboard']['answer_rate_chart_border_width']['text']."',";
        $html .= "            cutout: chart_cutout";
        $html .= "        }]";
        $html .= "    };";

        $html .= "    const answer_rate_chart_config = {";
        $html .= "        type: 'doughnut',";
        $html .= "        data: answer_rate_chart_data,";
        $html .= "        options: {";
        $html .= "            responsive: true,";
        $html .= "            maintainAspectRatio: false,";
        $html .= "            plugins: {";
        $html .= "                chart_counter: {";
        $html .= "                    chart_text: '".number_format($result["today"], 1)."%'";
        $html .= "                },";
        $html .= "                legend: {";
        $html .= "                    display: false";
        $html .= "                },";
        $html .= "                title: {";
        $html .= "                    display: true,";
        $html .= "                    text: '".$text['label-answer_rate']."'";
        $html .= "                }";
        $html .= "            }";
        $html .= "        },";
        $html .= "        plugins: [chart_counter],";
        $html .= "    };";

        $html .= "    const answer_rate_chart = new Chart(";
        $html .= "        answer_rate_chart_context,";
        $html .= "        answer_rate_chart_config";
        $html .= "    );";
        $html .= "</script>";

        $html .= "<div class='hud_details hud_box' id='hud_answer_rate_details'>";
        $html .= "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
        $html .= "<tr>\n";
        $html .= "<th class='hud_heading'>&nbsp;</th>\n";
        $html .= "<th class='hud_heading' width='100%'>".$text['label-period']."</th>\n";
        $html .= "<th class='hud_heading'>".$text['label-percent']."</th>\n";
        $html .= "</tr>\n";

        foreach ($result as $period => $percent) {
            $html .= "<tr>\n";
            $html .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'></td>";
            $html .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$text['label-tta_'.$period]."</td>\n";
            $html .= "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".(($percent != 0) ? number_format($percent, 1) : "-------")."%</td>\n";
            $html .= "</tr>\n";
        }    

        unset($sql, $parameters, $result, $database);
        $html .= "</table>\n";
        $html .= "</div>";
        $html .= "<span class='hud_expander' onclick=\"$('#hud_answer_rate_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
        $html .= "</div>\n";

        $json['html'] = $html;
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
        return;
    }

//show loading animation
    echo "<div class='hud_box'>";
    echo "  <div class='lds-default'><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>";
    echo "</div>";    
?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

// make query when DOMs are loaded
$(document).ready(function() {
    $.get({
            url: "/app/xml_cdr/resources/dashboard/answer_rate_fast.php", 
            data: { document: 'ready' },
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                $('#answer_rate').html(data['html']); 
            }});
});

</script>