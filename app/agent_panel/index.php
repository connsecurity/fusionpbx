<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
    require_once "get_agent.php";

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
echo "	<div class='cdr'>";
echo "      <div class='ap_action_bar'>";
echo "          <div class='heading'><b>".$text['title-received']."</b></div>";
echo "      </div>";
echo "      <table class='list'>";
echo "          <tbody>";
echo "              <tr class='list-header'>";
echo "                  <th></th>";
echo "                  <th>".$text['label-caller_id_number']."</th>";
echo "                  <th>".$text['label-destination_number']."</th>";
echo "                  <th class='center'>".$text['label-date']."</th>";
echo "                  <th class='center hide-md-dn'>".$text['label-time']."</th>";
echo "              </tr></tbody>";
echo "          <tbody id='cdr_one_table'>";
echo "          </tdbody></table>";
echo "  </div>";
//contacts
echo "	<div id='contacts'>";
echo "      <div class='ap_action_bar'>";
echo "      	<div class='heading'><b>".$text['title-contacts']."</b></div>";
echo "	        <div class='actions'>";
echo "              <label>";
echo "                  <input class='formfld' type='checkbox' id='associated_contacts' name='associated_contacts'>";
echo "                  <div></div>";
echo "              </label>";
echo "              <select class='formfld' id='contact_type' name='contact_type'>";
if (is_array($_SESSION["contact"]["type"])) {
	sort($_SESSION["contact"]["type"]);
	echo "		<option value=''>All</option>\n";
	foreach($_SESSION["contact"]["type"] as $type) {
		echo "		<option value='".escape($type)."'>".escape($type)."</option>\n";
	}
}
else {
	echo "		<option value='' selected='selected'>".$text['option-contact_type_all']."</option>\n";
	echo "		<option value='customer'>".$text['option-contact_type_customer']."</option>\n";
	echo "		<option value='contractor'>".$text['option-contact_type_contractor']."</option>\n";
	echo "		<option value='friend'>".$text['option-contact_type_friend']."</option>\n";
	echo "		<option value='lead'>".$text['option-contact_type_lead']."</option>\n";
	echo "		<option value='member'>".$text['option-contact_type_member']."</option>\n";
	echo "		<option value='family'>".$text['option-contact_type_family']."</option>\n";
	echo "		<option value='subscriber'>".$text['option-contact_type_subscriber']."</option>\n";
	echo "		<option value='supplier'>".$text['option-contact_type_supplier']."</option>\n";
	echo "		<option value='provider'>".$text['option-contact_type_provider']."</option>\n";
	echo "		<option value='user'>".$text['option-contact_type_user']."</option>\n";
	echo "		<option value='volunteer'>".$text['option-contact_type_volunteer']."</option>\n";
}
echo "              </select>";
if (permission_exists('contact_add')) {
    //echo button::create(['type'=>'button','icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_contact_add','style'=>null,'onclick'=>"loadModal('/app/contacts/contact_edit_modal.php'); modal_open('modal-contact_add','btn_contact_add');"]);
    echo button::create(['type'=>'button','icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_contact_add','style'=>null,'onclick'=>"loadModalContent('contact_add'); modal_open('modal','btn_contact_add');"]);
}
echo "          </div>";
echo "      </div>";
echo "      <table id='contacts_table'>";
echo "      </table>";
echo "  </div>";

//missed
echo "	<div class='cdr'>";
echo "      <div class='ap_action_bar'>";
echo "          <div class='heading'><b>".$text['title-missed']."</b></div>";
echo "      </div>";
echo "      <table class='list'>";
echo "          <tbody>";
echo "              <tr class='list-header'>";
echo "                  <th></th>";
echo "                  <th>".$text['label-caller_id_number']."</th>";
echo "                  <th>".$text['label-destination_number']."</th>";
echo "                  <th class='center'>".$text['label-date']."</th>";
echo "                  <th class='center hide-md-dn'>".$text['label-time']."</th>";
echo "              </tr></tbody>";
echo "          <tbody id='cdr_two_table'>";
echo "          </tdbody></table>";
echo "  </div>";

//phone
echo "	<div id='phone'>";
echo "      <div class='ap_action_bar'>";
echo "          <div><b>".$text['title-phone']." - ".$_SESSION['agent']['extension'][0]['extension']."</b></div>";
echo "          <div id='register_status'></div>";
echo "      </div>";
echo "      <div id='phone_status'></div>";
echo "      <div id='phone_cmd'>";
echo            button::create(['type'=>'button','label'=>$text['label-phone_hangup'],'icon'=>'fas fa-phone-slash','id'=>'btn_phone_hangup','style'=>null,'onclick'=>"call('".$_SESSION['agent']['extension'][0]['extension']."', '', 'hangup');", 'disabled'=>true]);
echo "          <form id='frm_destination_call' onsubmit=\"call('".$_SESSION['agent']['extension'][0]['extension']."', document.getElementById('destination_call').value, 'auto'); return false;\">";
echo "              <input type='text' class='formfld' id='destination_call' style='width: 100px; min-width: 100px; max-width: 100px; text-align: center;'>";
echo "          </form>";
echo            button::create(['type'=>'button','label'=>$text['label-phone_call'],'icon'=>'fas fa-phone','id'=>'btn_phone_call','style'=>null,'onclick'=>"call('".$_SESSION['agent']['extension'][0]['extension']."', document.getElementById('destination_call').value, 'call');"]);
echo            button::create(['type'=>'button','label'=>$text['label-phone_transfer'],'icon'=>'fas fa-angle-double-right','id'=>'btn_phone_transfer','style'=>null,'onclick'=>"call('".$_SESSION['agent']['extension'][0]['extension']."', document.getElementById('destination_call').value, 'transfer');", 'disabled'=>true]);
echo "      </div>";
echo "  </div>";

echo "</div>";

//contact add modal
echo "  <div id='modal' class='modal-window'>";
echo "      <div id='modal-content'>";
echo "      </div>";
echo "  </div>";




//include the footer
require_once "resources/footer.php";
?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

$(document).ajaxComplete(function(event, xhr, settings) {
    //console.log('ajaxComplete');  
    //console.log(settings);
});

$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.log("global error");
    //window.location.replace("/app/agent_panel/");
    console.log(event);
    console.log(jqxhr);
    console.log(settings);
    console.log(thrownError);
});

$(document).ajaxSend(function(event, request, settings) {
    //console.log('ajaxSend');
});

$(document).ajaxStart(function() {
    //console.log('ajaxStart'); 
    //cleanRecords();   
});

$(document).ajaxStop(function() {
    //console.log('ajaxStop');
});

$(document).ajaxSuccess(function(event, request, settings) {
    //console.log('ajaxSuccess');
});

function showCdr(status, dom) {
	$.get({
            url: "cdr.php", 
            data: {
                    call_status: status,
                    extensions: selected,
                },
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log(data['status']);
                $(dom).html(data['table']);
            }});
}

function showContacts() {
    $.get({
            url: "contacts.php", 
            data: {
                associated : document.getElementById('associated_contacts').checked,
                type: document.getElementById('contact_type').value
            },
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log(data['return']);
                //$('#contact_type').html(data['options']);
                $('#contacts_table').html(data['table']);
            }});
}

var transferButton = document.getElementById('btn_phone_transfer');
var hangupButton = document.getElementById('btn_phone_hangup');
function showPhone() {
    $.get({
            url: "phone.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                //console.log(data['html']);                
                $('#register_status').html(data['registered']);
                var html = data['html'] + updateClock();
                $('#phone_status').html(html);
                //console.log(data['channel']);
                //console.log(data['channel_dump']);

                if (data['in_call'] == true) {
                    if (transferButton.disabled == true) {
                        button_enable('btn_phone_transfer');
                    }
                    if (hangupButton.disabled == true) {
                        button_enable('btn_phone_hangup');
                    }
                } else {
                    if (transferButton.disabled == false) {
                        button_disable('btn_phone_transfer');
                    }
                    if (hangupButton.disabled == false) {
                        button_disable('btn_phone_hangup');
                    }
                }
            }});
}

function call(extension, destination, operation) {
    // console.log(extension);
    // console.log(destination);
    // console.log(operation);
    $.post({
            url: "phone.php", 
            data: {
                extension: extension,
                destination: destination,
                operation: operation,
            },
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                console.log(data['switch_result']);
                console.log(data['api_cmd']);
                //$('#phone_status').html(data['html']);                
            }});
}

function loadModalContent(source) {
    $.get({
            url: source + ".php", 
            data: {},
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                //console.log(data);
                $('#modal-content').html(data);
            }});
}

function postData(form, destination) {
    $.post({
            url: destination + ".php", 
            data: $("#" + form).serialize(),
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                console.log(data);    
            }});
}

function toggleAssociate(element) {
    $.post({
            url: "toggle_associate.php", 
            data: {
                contact_uuid: element.getAttribute('data-uuid')
            },
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                if (data['message'] == 'deleted') {
                    element.className = "is_not_associated";
                } else if (data['message'] == 'added') {
                    element.className = "is_associated";
                }
            }});
}

function updateClock() {
    var now = new Date(),
        months = <?php echo $text['months']; ?>,
        days = <?php echo $text['days']; ?>,
        time = "<div style='font-size: 18px; font-weight: bold;'>" 
                + now.getHours().toLocaleString(undefined, {minimumIntegerDigits: 2}) 
                + ':' 
                + now.getMinutes().toLocaleString(undefined, {minimumIntegerDigits: 2}) 
                + '</div>';

        date = [days[now.getDay()] + ',',
                now.getDate(), 
                months[now.getMonth()]].join(' ');

        date = '<div>' + date + '</div>';

    // set the content of the element with the ID time to the formatted string
    return time + date;
}

var selected = [];
//var contactTypeSelect = document.getElementById('contact_type');
//contactTypeSelect.onchange = showContacts;
$('#contact_type').on('change', showContacts);
$('#associated_contacts').on('change', showContacts);
$('#contacts_table').on('change', function() {
    selected = [];
    $('.agent_panel_contact:checked').each(function() {
        selected.push($(this).val());
        $($(this).attr('name')).addClass('selectedContact');
    });
    $('.agent_panel_contact:not(:checked)').each(function() {
        $($(this).attr('name')).removeClass('selectedContact');
    });
    console.log(selected);
    showCdr('all', '#cdr_one_table');
    showCdr('missed', '#cdr_two_table');
});
//console.log(contactTypeSelect);
var phoneRefresher = window.setInterval(showPhone, 1000);

showCdr('all', '#cdr_one_table');
showCdr('missed', '#cdr_two_table');
showContacts();
showPhone();

</script>