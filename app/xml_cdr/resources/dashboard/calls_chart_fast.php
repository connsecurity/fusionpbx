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

//create assigned extensions array
	if (is_array($_SESSION['user']['extension'])) {
		foreach ($_SESSION['user']['extension'] as $assigned_extension) {
			$assigned_extensions[$assigned_extension['extension_uuid']] = $assigned_extension['user'];
		}
	}
//get contact call group
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

	// variables for the chart
	$today = strtotime("-3 hour"); 
	$parameters['start_hour'] = strtotime("00:00:00-0300", $today);
	$parameters['start_hour_2'] = $parameters['start_hour'];
	$parameters['number_of_bars'] = 24;
	$parameters['interval'] = 3600;	

	$sql = "WITH recursive 
			Hours_CTE
				AS (SELECT CAST(:start_hour as integer) AS hours
					UNION ALL
					SELECT hours + 3600
					FROM   Hours_CTE
					WHERE  hours < :start_hour_2 + :interval * (:number_of_bars - 1)	)
			SELECT
				to_timestamp(h.hours) as time_interval,
				count(v.*) as calls_received,
				count(case when v.bridge_epoch = 0 then 1 end) as calls_missed,
				avg(case when v.bridge_epoch > 0 then (v.bridge_epoch - v.progress_epoch) end) as time_to_answer
			FROM   
				Hours_CTE as h
			left join
				v_xml_cdr as v
			on
				v.start_epoch between h.hours and h.hours + 3599
			and
				v.domain_uuid = :domain_uuid
			and
				v.caller_id_number ~ '.{1}' 
			and
				v.last_app <> 'ivr' ";
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
			group by 
				h.hours
			order by 
				h.hours asc";

	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    $result = $database->select($sql, $parameters);
	//var_dump($sql);
	//echo "<br><br>";
	//var_dump($parameters);
	//echo "<br><br>";
	//var_dump($result);

	foreach ($result as $row) {
		$chart_data['labels'][] = date('H:i', strtotime($row['time_interval']));
		$chart_data['calls_received'][] = $row['calls_received'];
		$chart_data['calls_missed'][] = $row['calls_missed'];
		$chart_data['time_to_answer'][] = $row['time_to_answer'] != 0 ? $row['time_to_answer'] : 'NULL';
	}

	//echo "<br><br>";
	//echo "['".implode("', '", $chart_data['labels'])."']";
	//var_dump($chart_data);

	//echo "['".implode("', '", $chart_data['time_to_answer'])."']"; 
	
    echo "<div class='hud_box' style='min-height: 220px'>\n";
?>

	<div id='bar_chart_div' style='display: justify; flex-wrap: wrap;  justify-content: center;'>
		<div><canvas id='calls_bar_chart' style='max-height: 568px'></canvas></div>
	</div>
	<script>		

		var calls_bar_chart_context = document.getElementById('calls_bar_chart').getContext('2d');

		const onResize = function (chart, size) {

			if (size.width > 800) {
				calls_bar_chart_options.plugins.legend.display = true;
				calls_bar_chart_options.maintainAspectRatio = true;
				calls_bar_chart_options.scales.yAxisLine.display = true;
			} else {
				calls_bar_chart_options.plugins.legend.display = false;
				calls_bar_chart_options.maintainAspectRatio = false;
				calls_bar_chart_options.scales.yAxisLine.display = false;
			}

		}
		
		const calls_bar_chart_data = {
			labels: <?php echo "['".implode("', '", $chart_data['labels'])."']"; ?>,
  			datasets: [{
				label: 'Tempo de Espera',
				type: 'line',
				data: <?php echo "['".implode("', '", $chart_data['time_to_answer'])."']"; ?>,				
				backgroundColor: 'rgb(250, 65, 0, 0.5)',
				borderColor: 'rgb(250, 65, 0, 0.75)',
				yAxisID: 'yAxisLine',
				borderWidth: 3
			},{
				label: 'Chamadas Perdidas',
				type: 'bar',
				data: <?php echo "['".implode("', '", $chart_data['calls_missed'])."']"; ?>,
				backgroundColor: 'rgba(255, 99, 132, 0.5)',
				borderColor: 'rgba(255, 99, 132)',
				yAxisID: 'yAxisBar',
				borderWidth: 1
			},{
				label: 'Chamadas Recebidas',
				type: 'bar',
				data: <?php echo "['".implode("', '", $chart_data['calls_received'])."']"; ?>,
				backgroundColor: 'rgba(54, 162, 235, 0.5)',
				borderColor: 'rgba(54, 162, 235)',
				yAxisID: 'yAxisBar',
				borderWidth: 1
			}]
		};

		const calls_bar_chart_options = {
			onResize: onResize,
			maintainAspectRatio: true,
			scales: {
				xAxes: {
					stacked: true,
				},
				yAxisBar: {	
				},
				yAxisLine: {
					display: false,
					ticks: {
						// Include a dollar sign in the ticks
						callback: function(value, index, ticks) {
							return Chart.Ticks.formatters.numeric.apply(this, [value, index, ticks]) + 's';
						},
					},	
					grid: {
						display: false
					},
					position: 'right',
				},				
			},
			plugins: {
				title: {
					display: true,
					text: '<?php echo (strlen($text['label-calls_chart_title']) > 0 ? $text['label-calls_chart_title'] : "Chart"); ?>',
				},
				legend: {
					display: true,
				}
        	},
			
		};

		const calls_bar_chart_config = {
			type: 'bar',
			data: calls_bar_chart_data,
			options: calls_bar_chart_options

		};


		const calls_bar_chart = new Chart(calls_bar_chart_context, calls_bar_chart_config);
	</script>
	<?php

unset($sql, $parameters, $result, $chart_data);
echo "</div>\n";

?>

