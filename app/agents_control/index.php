<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('extension_view')) {
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
$document['title'] = $text['title-agents_control'];
require_once "resources/header.php";

//show agents
//echo "<div>";

echo "  <div style='border-bottom: solid; border-radius: 5px; border-color: #AFC8FF; border-width: 4px; padding: 4px; margin-bottom: 4px;'>";
echo "      <div class='action_bar'>";
echo "          <div class='heading'> <b>Extension</b> </div>";
echo "          <div class='actions'>";
echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','style'=>null,'link'=>'/app/extensions/extension_edit.php']);
echo "          </div>";
echo "      </div>";
echo "      <div id='agents'>";
echo "      </div>";
echo "  </div>";

echo "  <div style='border-bottom: solid; border-radius: 5px; border-color: #AFC8FF; border-width: 4px; padding: 4px; margin-bottom: 4px;'>";
echo "      <div class='action_bar'>";
echo "          <div class='heading'> <b>Contacts</b> </div>";
echo "          <div class='actions'>";
if (permission_exists('contact_add')) {
    echo button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_contact_add','style'=>null,'onclick'=>"loadModal('/app/contacts/contact_edit_modal.php'); modal_open('modal-contact_add','btn_contact_add');"]);
}
echo "          </div>";
echo "      </div>";
echo "      <div id='contacts'>";
echo "      </div>";
echo "  </div>";

echo "  <div style='border-bottom: solid; border-radius: 5px; border-color: #AFC8FF; border-width: 4px; padding: 4px; margin-bottom: 4px;'>";
echo "      <div class='action_bar'>";
echo "          <div class='heading'> <b>Call Records</b> </div>";
echo "      </div>";
echo "  <div id='call_records'>";
//build table
echo "      <table class='list'>";
echo "          <tbody>";
echo "              <tr class='list-header'>";
echo "                  <th class='shrink'>".$text['label-direction']."</th>";
echo "                  <th class='shrink'>".$text['label-caller_id_number']."</th>";
echo "                  <th class='shrink'>".$text['label-destination_number']."</th>";
echo "                  <th class='shrink'>".$text['label-start_stamp']."</th>";
echo "                  <th class='shrink'>".$text['label-answer_epoch']."</th>";
echo "              </tr></tbody></table>";
echo "  </div>";
echo "  </div>";

echo "  <div id='modal-contact_add' class='modal-ac-window'>";
echo "      <div id='modal-content'>";
echo "          <span title='' class='modal-close' onclick='modal_close();'>×</span>";
require "app/contacts/contact_edit_modal.php";
echo "      </div>";
echo "  </div>";

//echo "</div>";


//show the footer
require_once "resources/footer.php";

?>

<script language="JavaScript" type="text/javascript" src="<?php echo PROJECT_PATH; ?>/resources/jquery/jquery-ui.min.js"></script>
<script type="text/javascript">

//storing empty table for cleaning
var empty_table = document.getElementById('call_records').innerHTML;

$(document).ajaxComplete(function(event, xhr, settings) {
    console.log('ajaxComplete');  
    //console.log(settings);
});

$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.log("global error");
    window.location.replace("/login.php?path=%2Fapp%2Fagents_control%2F");
});

$(document).ajaxSend(function(event, request, settings) {
    console.log('ajaxSend');
});

$(document).ajaxStart(function() {
    console.log('ajaxStart'); 
    cleanRecords();   
});

$(document).ajaxStop(function() {
    console.log('ajaxStop');
});

$(document).ajaxSuccess(function(event, request, settings) {
    console.log('ajaxSuccess');
});

function showAgents() {
    $.get({
            url: "agents.php", 
            data: {                
                filters: {
                    name: '',
                    extension: '',
                    group: '',
            }},
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                // console.log("i was here");
                // console.log(data);
                $('#agents').html(data);
            }})
        .then(
            () => {
                var agents = document.querySelectorAll('.agent_control');             
                if (agents && agents.length) {
                    agents[0].checked = true;
                    showContacts();
                }
            }
    );
}
function showContacts() {
    $.get({
            url: "contacts.php",
            data: {
                filters: {
                    extension_uuid: document.querySelector('input[name="operator"]:checked').value
            }},
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                // console.log("i was here");
                // console.log(data);
                $('#contacts').html(data);
            },
            // error: function (xhr, status, error) {
            //     console.log("error");
            //     console.log(xhr);
            //     console.log(status);
            //     console.log(error);
            // }
        })
        .then(
            () => {
                var contacts = document.getElementsByName('contact');
                if (contacts && contacts.length) {
                    contacts[0].checked = true;
                    showCallRecords();
                }
            }
    );
}

function showCallRecords() {
    //console.log('call records');
    $.get({
            url: "call_records.php",
            data: {
                filters: {
                    extension_uuid: document.querySelector('input[name="operator"]:checked').value,
                    phone_number: document.querySelector('input[name="contact"]:checked').value
            }},
            dataType: "json",
            success: function (data, textStatus, jqXHR) {
                //console.log("i was here");
                //console.log(data);
                $('#call_records').html(data);
            },
            error: function (xhr, status, error) {
                console.log("error");
                console.log(xhr);
                console.log(status);
                console.log(error);
            }
        });
}

function cleanRecords() {
    document.getElementById('call_records').innerHTML = empty_table;
}

function loadModal(url) {
    console.log(url);
    //$('#modal-content').load(url);
}
showAgents();
</script>