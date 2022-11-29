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

//load extensions from the user
    $sql = "select 
				z.extension_uuid
			from
				v_contact_settings as x
			inner join
				v_users as y
			on
				x.contact_uuid = y.contact_uuid
			inner join
				v_extensions as z
			on
				x.contact_setting_value = z.call_group			
			where
				y.user_uuid = :user_uuid
				and
				x.contact_setting_subcategory = 'inbound_config'
				and
				x.contact_setting_enabled = 'true'";

	$parameters['user_uuid'] = $_SESSION['user_uuid'];
	$database = new database;
	$call_groups_extensions = $database->select($sql, $parameters, 'all');

	if (is_array($call_groups_extensions) && sizeof($call_groups_extensions) != 0) {
		foreach ($call_groups_extensions as $extension) {
			$extensions_list[] = $extension['extension_uuid'];
		}
	}
    
    unset($sql, $parameters, $call_groups_extensions);

//set delimiters    
    $today = strtotime("-3 hours");    
    $tomorrow = strtotime("+1 day", $today);
    $parameters['start_last_month'] = strtotime("first day of last month 00:00:00", $today); // first day of previous month
    $parameters['end_last_month'] = strtotime("last day of last month 23:59:59", $today); //last day of previous month 
    $parameters['start_last_month_2'] = $parameters['start_last_month'];//strtotime("first day of last month 00:00:00", $today);   
    $parameters['end_last_month_2'] = $parameters['end_last_month'];//strtotime("last day of last month 23:59:59", $today); 
    $parameters['start_this_month'] = strtotime("first day of this month 00:00:00", $today); //first day of current month    
    $parameters['start_this_month_2'] = $parameters['start_this_month'];//strtotime("first day of this month 00:00:00", $today);
    $parameters['start_last_week'] = strtotime("-2 week sunday 00:00:00", $tomorrow); //start of previous week
    $parameters['end_last_week'] = strtotime("-1 week saturday 23:59:59", $tomorrow); //end of previous week
    $parameters['start_last_week_2'] = $parameters['start_last_week'];//strtotime("-2 week sunday 00:00:00", $tomorrow);
    $parameters['end_last_week_2'] = $parameters['end_last_week'];//strtotime("-1 week saturday 23:59:59", $tomorrow);
    $parameters['start_this_week'] = strtotime("last sunday 00:00:00", $tomorrow); //start of this week
    $parameters['start_this_week_2'] = $parameters['start_this_week'];//strtotime("last sunday 00:00:00", $tomorrow);
    $parameters['start_yesterday'] = strtotime("yesterday", $today);
    $parameters['end_yesterday'] = strtotime("yesterday 23:59:59", $today);
    $parameters['start_yesterday_2'] = $parameters['start_yesterday'];//strtotime("yesterday", $today);
    $parameters['end_yesterday_2'] = $parameters['end_yesterday'];//strtotime("yesterday 23:59:59", $today);
    $parameters['start_today'] = strtotime("00:00:00", $today);
    $parameters['start_today_2'] = $parameters['start_today'];//strtotime("00:00:00", $today);
    $parameters['start_select'] = $parameters['start_last_month'];//strtotime("first day of last month 00:00:00", $today);

//select the averages
    $sql = "
            select 
                (count(case when bridge_epoch > 0 and start_epoch >= :start_today then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_today_2 then 1 end), 0)) as today,

                (count(case when bridge_epoch > 0 and start_epoch >= :start_yesterday and start_epoch <= :end_yesterday then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_yesterday_2 and start_epoch <= :end_yesterday_2 then 1 end), 0)) as yesterday,

                (count(case when bridge_epoch > 0 and start_epoch >= :start_this_week then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_this_week_2 then 1 end), 0)) as this_week,

                (count(case when bridge_epoch > 0 and start_epoch >= :start_last_week and start_epoch <= :end_last_week then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_last_week_2 and start_epoch <= :end_last_week_2 then 1 end), 0)) as last_week,

                (count(case when bridge_epoch > 0 and start_epoch >= :start_this_month then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_this_month_2 then 1 end), 0)) as this_month,

                (count(case when bridge_epoch > 0 and start_epoch >= :start_last_month and start_epoch <= :end_last_month then 1 end) * 100.0
                /nullif(count(case when start_epoch >= :start_last_month_2 and start_epoch <= :end_last_month_2 then 1 end), 0)) as last_month
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
                last_app <> 'ivr'
            and
                caller_id_number ~ '.{1}'
            and
                start_epoch >= :start_select";            

    $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    $result = $database->select($sql, $parameters, 'row');  
    //var_dump($result);

    //define row styles
	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";

    //time to answer
    echo "<div class='hud_box'>\n";

    //draw doughnut chart
    ?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;'>
		<div style='width: 175px; height: 175px;'><canvas id='answer_rate_chart'></canvas></div>
	</div>

	<script>
		var answer_rate_chart_context = document.getElementById('answer_rate_chart').getContext('2d');

		const answer_rate_chart_data = {
			datasets: [{
				data: ['<?php echo $result["today"]; ?>', 0.00001],
				backgroundColor: ['<?php echo $_SESSION['dashboard']['answer_rate_chart_main_background_color']['text']; ?>',
				'<?php echo $_SESSION['dashboard']['missed_calls_chart_sub_background_color']['text']; ?>'],
				borderColor: '<?php echo $_SESSION['dashboard']['answer_rate_chart_border_color']['text']; ?>',
				borderWidth: '<?php echo $_SESSION['dashboard']['answer_rate_chart_border_width']['text']; ?>',
				cutout: chart_cutout
			}]
		};

		const answer_rate_chart_config = {
			type: 'doughnut',
			data: answer_rate_chart_data,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					chart_counter: {
						chart_text: '<?php echo number_format($result["today"], 1); ?>%'
					},
					legend: {
						display: false
					},
					title: {
						display: true,
						text: '<?php echo $text['label-answer_rate']; ?>'
					}
				}
			},
			plugins: [chart_counter],
		};

		const answer_rate_chart = new Chart(
			answer_rate_chart_context,
			answer_rate_chart_config
		);
	</script>
	<?php

    echo "<div class='hud_details hud_box' id='hud_answer_rate_details'>";
    echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
    echo "<tr>\n";
    echo "<th class='hud_heading'>&nbsp;</th>\n";
    echo "<th class='hud_heading' width='100%'>".$text['label-period']."</th>\n";
    echo "<th class='hud_heading'>".$text['label-percent']."</th>\n";
    echo "</tr>\n";

    foreach ($result as $period => $percent) {
        echo "<tr>\n";
        echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'></td>";
        echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".$text['label-tta_'.$period]."</td>\n";
        echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap'>".(($percent != 0) ? number_format($percent, 1) : "-------")."%</td>\n";
        echo "</tr>\n";
    }    

    unset($sql, $parameters, $result, $database);
    echo "</table>\n";
	//echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/xml_cdr/xml_cdr.php'>".$text['label-view_all']."</a></span>\n";
	echo "</div>";
    
    //echo "<br>";
    // $sunday = strtotime("2022-10-02");
    // $today = strtotime("-3 hours");
    // $tomorrow = strtotime("+1 day", $today);
    // echo "<br> today start: ".strtotime("yesterday 23:59:59", $today);
    // echo "<br> sunday: ".$sunday;
    // echo "<br> today: ".$today;
    // echo "<br> tomorrow: ".$tomorrow;
    // echo "<br> first previous month: ".strtotime("first day of last month 00:00:00", $today); // first day of previous month
    // echo "<br> last previous month: ".strtotime("last day of last month 23:59:59", $today); //last day of previous month    
    // echo "<br> first current month: ".strtotime("first day of this month 00:00:00", $today); //first day of current month
    // echo "<br> last current month: ".strtotime("last day of this month 23:59:59", $today); //last day of current month
    // echo "<br> start previous week: ".strtotime("-1 week sunday 00:00:00", $tomorrow); //start of previous week
    // echo "<br> end previous week: ".strtotime("-1 week saturday 23:59:59", $tomorrow); //end of previous week
    // echo "<br> start this week: ".strtotime("last sunday 00:00:00", $tomorrow); //start of this week

    // $today = strtotime("-3 hours");    
    // echo "<br>".$today.": today ".date("D d M H:i:s", $today);
    // $tomorrow = strtotime("+1 day", $today);
    // echo "<br>".$tomorrow.": tomorrow ".date("D d M H:i:s", $tomorrow);
    // $parameters['start_last_month'] = strtotime("first day of last month 00:00:00", $today); // first day of previous month
    // echo "<br>".$parameters['start_last_month'].": parameters['start_last_month'] ".date("D d M H:i:s", $parameters['start_last_month']);
    // $parameters['end_last_month'] = strtotime("last day of last month 23:59:59", $today); //last day of previous month    
    // echo "<br>".$parameters['end_last_month'].": parameters['end_last_month'] ".date("D d M H:i:s", $parameters['end_last_month']);
    // $parameters['start_this_month'] = strtotime("first day of this month 00:00:00", $today); //first day of current month
    // echo "<br>".$parameters['start_this_month'].": parameters['start_this_month'] ".date("D d M H:i:s", $parameters['start_this_month']);
    // $parameters['end_this_month'] = strtotime("last day of this month 23:59:59", $today); //last day of current month
    // echo "<br>".$parameters['end_this_month'].": parameters['end_this_month'] ".date("D d M H:i:s", $parameters['end_this_month']);
    // $parameters['start_last_week'] = strtotime("-2 week sunday 00:00:00", $tomorrow); //start of previous week
    // echo "<br>".$parameters['start_last_week'].": parameters['start_last_week'] ".date("D d M H:i:s", $parameters['start_last_week']);
    // $parameters['end_last_week'] = strtotime("-1 week saturday 23:59:59", $tomorrow); //end of previous week
    // echo "<br>".$parameters['end_last_week'].": parameters['end_last_week'] ".date("D d M H:i:s", $parameters['end_last_week']);
    // $parameters['start_this_week'] = strtotime("last sunday 00:00:00", $tomorrow); //start of this week
    // echo "<br>".$parameters['start_this_week'].": parameters['start_this_week'] ".date("D d M H:i:s", $parameters['start_this_week']);
    // $parameters['start_yesterday'] = strtotime("yesterday", $today);
    // echo "<br>".$parameters['start_yesterday'].": parameters['start_yesterday'] ".date("D d M H:i:s", $parameters['start_yesterday']);
    // $parameters['end_yesterday'] = strtotime("yesterday 23:59:59", $today);
    // echo "<br>".$parameters['end_yesterday'].": parameters['end_yesterday'] ".date("D d M H:i:s", $parameters['end_yesterday']);
    // $parameters['start_today'] = strtotime("00:00:00", $today);

    echo "<span class='hud_expander' onclick=\"$('#hud_answer_rate_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
    echo "</div>\n";    
    
  

   

?>