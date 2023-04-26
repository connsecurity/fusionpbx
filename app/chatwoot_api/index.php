<?php

//includes
	include "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('chatwoot_api_view')) {
		//access granted
	}
	else {
		//echo "access denied";
		//exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();


//include the footer
	require_once "resources/footer.php";
?>