<?php

//includes
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('customer_chat_view')) {
		//access granted
	}
	else {
		//echo "access denied";
		//exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

	readfile("body.html");

?>

<script src="./test_variables.js?v=<?=time();?>"></script>
<script src="./app.js?v=<?=time();?>"></script>

<?php
//include the footer
	require_once "resources/footer.php";
?>