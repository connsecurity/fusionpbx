<?php

//includes
	include "root.php";
	require_once "resources/require.php";
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

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script src="./test_variables.js?v=<?=time();?>"></script>
<script src="./app.js?v=<?=time();?>"></script>

<?php
//include the footer
	require_once "resources/footer.php";
?>