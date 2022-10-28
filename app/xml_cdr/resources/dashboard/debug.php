<?php

//includes
	require_once "root.php";
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

//recent calls
	echo "<div class='hud_box'>\n";

    /* $i = 1;

    foreach ($_SESSION as $key => $array) {
        echo "array ". $i ."-------------------------------------------------------------<br>";
        echo $key ."   "; var_dump($array);
        echo "---------------------------------------------------------------------------<br>";
    }//4e88dd8b-8be7-4c76-a1eb-5dec8441f57d */

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
			echo $extension['extension_uuid'] . "<br>";
		}
	}

	foreach ($extensions_list as $extension_uuid) {
		$sql_where_array[] = "extension_uuid = " . $extension_uuid;
	}
	
	//var_dump($call_groups_extensions);

	echo "<br>";
    echo "</div>\n";
?>