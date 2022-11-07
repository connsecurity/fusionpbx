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
	// else {
	// 	echo "access denied";
	// 	exit;
	// }

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//include the header
$document['title'] = $text['title-agent_panel'];
require_once "resources/header.php";

//show content
echo "<div class='agent-panel'>";
//received
echo "	<div id='received'>";
echo "      <table class='list'>";
echo "          <tbody>";
echo "              <tr class='list-header'>";
echo "                  <th class='shrink'>".$text['label-direction']."</th>";
echo "                  <th class='shrink'>".$text['label-caller_id_number']."</th>";
echo "                  <th class='shrink'>".$text['label-destination_number']."</th>";
echo "                  <th class='shrink'>".$text['label-start_stamp']."</th>";
echo "                  <th class='shrink'>".$text['label-answer_epoch']."</th>";
echo "              </tr></tbody>";
echo "          <tbody id='received_table'>";
echo "          </tdbody></table>";
echo "  </div>";
//contacts
echo "	<div id='contacts'>";
echo "      <table id='contacts_table'>";
echo "      </table>";
echo "  </div>";

//answered
echo "	<div id='answered'>";
echo "      <table class='list'>";
echo "          <tbody>";
echo "              <tr class='list-header'>";
echo "                  <th class='shrink'>".$text['label-direction']."</th>";
echo "                  <th class='shrink'>".$text['label-caller_id_number']."</th>";
echo "                  <th class='shrink'>".$text['label-destination_number']."</th>";
echo "                  <th class='shrink'>".$text['label-start_stamp']."</th>";
echo "                  <th class='shrink'>".$text['label-answer_epoch']."</th>";
echo "              </tr></tbody>";
echo "          <tbody id='answered_table'>";
echo "          </tdbody></table>";
echo "  </div>";
//phone
echo "	<div id='phone'></div>";

echo "</div>";




//include the footer
require_once "resources/footer.php";
?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

$(document).ajaxComplete(function(event, xhr, settings) {
    console.log('ajaxComplete');  
    //console.log(settings);
});

$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.log("global error");
    //window.location.replace("/login.php?path=%2Fapp%2Fagent_panel%2F");
    console.log(event);
    console.log(jqxhr);
    console.log(settings);
    console.log(thrownError);
});

$(document).ajaxSend(function(event, request, settings) {
    console.log('ajaxSend');
});

$(document).ajaxStart(function() {
    console.log('ajaxStart'); 
    //cleanRecords();   
});

$(document).ajaxStop(function() {
    console.log('ajaxStop');
});

$(document).ajaxSuccess(function(event, request, settings) {
    console.log('ajaxSuccess');
});

function showReceived() {
	$.get({
            url: "received.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            //dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data);
                $('#received_table').html(data);
            }});
}

function showAnswered() {
	$.get({
            url: "answered.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            //dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data);
                $('#answered_table').html(data);
            }});
}

function showContacts() {
    $.get({
            url: "contacts.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            //dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data);
                $('#contacts_table').html(data);
            }});
}

function showPhone() {
    $.get({
            url: "phone.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            //dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data);
                $('#phone').html(data);
            }});

}

function call(extension, destination, operation) {
    console.log(extension);
    console.log(destination);
    console.log(operation);
    $.post({
            url: "phone.php", 
            data: {
                extension: extension,
                destination: destination,
                operation: operation,
            },
            //dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data);
                //$('#phone').html(data);
            }});
}

showReceived();
showAnswered();
showContacts();
showPhone();

</script>