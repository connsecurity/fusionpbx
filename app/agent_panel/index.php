<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (permission_exists('operator_panel_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
$document['title'] = $text['title-agent_panel'];
require_once "resources/header.php";

//show content
echo "<div class='agent-panel'>";
echo "	<div='received'></div>";
echo "	<div='contacts'></div>";
echo "	<div='answered'></div>";
echo "	<div='phone'></div>";
echo "</div>";




//include the footer
require_once "resources/footer.php";
?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

</script>