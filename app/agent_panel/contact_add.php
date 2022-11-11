<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('contact_add') || permission_exists('contact_edit')) {
    //access granted
}
else {
    echo "access denied";
    exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //validate the token
    $token = new token;
    if (!$token->validate($_SERVER['PHP_SELF'])) {
        message::add($text['message-invalid_token'],'negative');
        //header('Location: index.php');        
        exit;
    }

    //add the contact_uuid
    $contact_uuid = uuid();

    //prepare the array
    $array['contacts'][0]['contact_uuid'] = $contact_uuid;
    $array['contacts'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['contacts'][0]['contact_type'] = $_POST['contact_type'];
    $array['contacts'][0]['contact_nickname'] = $_POST['contact_nickname'];
    $array['contacts'][0]['contact_name_given'] = $_POST['contact_name_given'];
    $array['contacts'][0]['contact_name_family'] = $_POST['contact_name_family'];
    $array['contacts'][0]['contact_note'] = $_POST['contact_note'];
    $array['contacts'][0]['last_mod_date'] = "now()";
    $array['contacts'][0]['last_mod_user'] = $_SESSION['user_uuid'];

    $array['contacts'][0]['contact_phones'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['contacts'][0]['contact_phones'][0]['contact_uuid'] = $contact_uuid;
    $array['contacts'][0]['contact_phones'][0]['phone_label'] = $_POST['contact_phones'][0]['phone_label'];
    $array['contacts'][0]['contact_phones'][0]['phone_number'] = $_POST['contact_phones'][0]['phone_number'];

    $array['contacts'][0]['contact_addresses'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['contacts'][0]['contact_addresses'][0]['contact_uuid'] = $contact_uuid;
    $array['contacts'][0]['contact_addresses'][0]['address_type'] = $_POST['contact_addresses'][0]['address_type'];
    $array['contacts'][0]['contact_addresses'][0]['address_street'] = $_POST['contact_addresses'][0]['address_street'];
    $array['contacts'][0]['contact_addresses'][0]['address_extended'] = $_POST['contact_addresses'][0]['address_extended'];
    $array['contacts'][0]['contact_addresses'][0]['address_region'] = $_POST['contact_addresses'][0]['address_region'];
    $array['contacts'][0]['contact_addresses'][0]['address_locality'] = $_POST['contact_addresses'][0]['address_locality'];
    $array['contacts'][0]['contact_addresses'][0]['address_postal_code'] = $_POST['contact_addresses'][0]['address_postal_code'];
    $array['contacts'][0]['contact_addresses'][0]['address_country'] = $_POST['contact_addresses'][0]['address_country'];

    $array['contacts'][0]['contact_emails'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
    $array['contacts'][0]['contact_emails'][0]['contact_uuid'] = $contact_uuid;
    $array['contacts'][0]['contact_emails'][0]['email_address'] = $_POST['contact_emails'][0]['email_address'];    

    //save the data
    if (is_array($array) && @sizeof($array) != 0) {
        //add the permission object
        $p = new permissions;
        $p->add('contact_add', 'temp');
        $p->add('contact_phone_add', 'temp');
        $p->add('contact_address_add', 'temp');
        $p->add('contact_user_add', 'temp');
        $p->add('contact_group_add', 'temp');

        $database = new database;
        $database->app_name = 'contacts';
        $database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
        $database->save($array);
        $message = $database->message;
        unset($array);

        $p->delete('contact_add', 'temp');
        $p->delete('contact_phone_add', 'temp');
        $p->delete('contact_address_add', 'temp');
        $p->delete('contact_user_add', 'temp');
        $p->delete('contact_group_add', 'temp');
    }

    echo json_encode($message);
    exit;
}

//create token
$object = new token;
$token = $object->create($_SERVER['PHP_SELF']);

$form = "<form method='post' id='contact_add_form'>";
$form .= "<span title='' class='modal-close' onclick='modal_close();'>×</span>";
$form .= button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'onclick' => "postData('contact_add_form', 'contact_add'); modal_close(); showContacts();",'style'=>'margin-left: 15px;','id'=>'btn_save','collapse'=>'hide-xs']);

$form .= "	<div class='form_set'>\n";

$form .= "		<div class='heading'>\n";
$form .= "			<b>".$text['label-name']."</b>\n";
$form .= "		</div>\n";
$form .= "		<div style='clear: both;'></div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_type']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
if (is_array($_SESSION["contact"]["type"])) {
	sort($_SESSION["contact"]["type"]);
	$form .= "	<select class='formfld' name='contact_type'>\n";
	$form .= "		<option value=''></option>\n";
	foreach($_SESSION["contact"]["type"] as $type) {
		$form .= "		<option value='".escape($type)."' ".(($type == $contact_type) ? "selected='selected'" : null).">".escape($type)."</option>\n";
	}
	$form .= "	</select>\n";
}
else {
	$form .= "	<select class='formfld' name='contact_type'>\n";
	$form .= "		<option value=''></option>\n";
	$form .= "		<option value='customer' ".(($contact_type == "customer") ? "selected='selected'" : null).">".$text['option-contact_type_customer']."</option>\n";
	$form .= "		<option value='contractor' ".(($contact_type == "contractor") ? "selected='selected'" : null).">".$text['option-contact_type_contractor']."</option>\n";
	$form .= "		<option value='friend' ".(($contact_type == "friend") ? "selected='selected'" : null).">".$text['option-contact_type_friend']."</option>\n";
	$form .= "		<option value='lead' ".(($contact_type == "lead") ? "selected='selected'" : null).">".$text['option-contact_type_lead']."</option>\n";
	$form .= "		<option value='member' ".(($contact_type == "member") ? "selected='selected'" : null).">".$text['option-contact_type_member']."</option>\n";
	$form .= "		<option value='family' ".(($contact_type == "family") ? "selected='selected'" : null).">".$text['option-contact_type_family']."</option>\n";
	$form .= "		<option value='subscriber' ".(($contact_type == "subscriber") ? "selected='selected'" : null).">".$text['option-contact_type_subscriber']."</option>\n";
	$form .= "		<option value='supplier' ".(($contact_type == "supplier") ? "selected='selected'" : null).">".$text['option-contact_type_supplier']."</option>\n";
	$form .= "		<option value='provider' ".(($contact_type == "provider") ? "selected='selected'" : null).">".$text['option-contact_type_provider']."</option>\n";
	$form .= "		<option value='user' ".(($contact_type == "user") ? "selected='selected'" : null).">".$text['option-contact_type_user']."</option>\n";
	$form .= "		<option value='volunteer' ".(($contact_type == "volunteer") ? "selected='selected'" : null).">".$text['option-contact_type_volunteer']."</option>\n";
	$form .= "	</select>\n";
}
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_nickname']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_nickname' placeholder='' maxlength='255' value='".escape($contact_nickname)."'>\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_organization']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_organization' placeholder='' maxlength='255' value='".escape($contact_organization)."'>\n";
$form .= "		</div>\n";

// $form .= "		<div class='label'>\n";
// $form .= "			".$text['label-contact_name_prefix']."\n";
// $form .= "		</div>\n";
// $form .= "		<div class='field no-wrap'>\n";
// $form .= "				<input class='formfld' type='text' name='contact_name_prefix' placeholder='' maxlength='255' value='".escape($contact_name_prefix)."'>\n";
// $form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_name_given']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_name_given' placeholder='' maxlength='255' value='".escape($contact_name_given)."'>\n";
$form .= "		</div>\n";

// $form .= "		<div class='label'>\n";
// $form .= "			".$text['label-contact_name_middle']."\n";
// $form .= "		</div>\n";
// $form .= "		<div class='field no-wrap'>\n";
// $form .= "				<input class='formfld' type='text' name='contact_name_middle' placeholder='' maxlength='255' value='".escape($contact_name_middle)."'>\n";
// $form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_name_family']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_name_family' placeholder='' maxlength='255' value='".escape($contact_name_family)."'>\n";
$form .= "		</div>\n";

// $form .= "		<div class='label'>\n";
// $form .= "			".$text['label-contact_name_suffix']."\n";
// $form .= "		</div>\n";
// $form .= "		<div class='field no-wrap'>\n";
// $form .= "				<input class='formfld' type='text' name='contact_name_suffix' placeholder='' maxlength='255' value='".escape($contact_name_suffix)."'>\n";
// $form .= "		</div>\n";

// $form .= "		<div class='label empty_row' style='grid-row: 10 / span 99;'>\n";
// $form .= "			&nbsp;\n";
// $form .= "		</div>\n";
// $form .= "		<div class='field empty_row' style='grid-row: 10 / span 99;'>\n";
// $form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-phone_label']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<select class='formfld' name='contact_phones[0][phone_label]' style=''>\n";
$form .= "				<option value=''></option>\n";
if ($row['phone_label'] == "work") {
    $form .= "				<option value='work' selected='selected'>".$text['option-work']."</option>\n";
}
else {
    $form .= "				<option value='work'>".$text['option-work']."</option>\n";
}
if ($row['phone_label'] == "home") {
    $form .= "				<option value='home' selected='selected'>".$text['option-home']."</option>\n";
}
else {
    $form .= "				<option value='home'>".$text['option-home']."</option>\n";
}
if ($row['phone_label'] == "mobile") {
    $form .= "				<option value='mobile' selected='selected'>".$text['option-mobile']."</option>\n";
}
else {
    $form .= "				<option value='mobile'>".$text['option-mobile']."</option>\n";
}
if ($row['phone_label'] == "main") {
    $form .= "				<option value='main' selected='selected'>".$text['option-main']."</option>\n";
}
else {
    $form .= "				<option value='main'>".$text['option-main']."</option>\n";
}
if ($row['phone_label'] == "billing") {
    $form .= "				<option value='billing' selected='selected'>".$text['option-billing']."</option>\n";
}
else {
    $form .= "				<option value='billing'>".$text['option-billing']."</option>\n";
}
if ($row['phone_label'] == "fax") {
    $form .= "				<option value='fax' selected='selected'>".$text['option-fax']."</option>\n";
}
else {
    $form .= "				<option value='fax'>".$text['option-fax']."</option>\n";
}
if ($row['phone_label'] == "voicemail") {
    $form .= "				<option value='voicemail' selected='selected'>".$text['option-voicemail']."</option>\n";
}
else {
    $form .= "				<option value='voicemail'>".$text['option-voicemail']."</option>\n";
}
if ($row['phone_label'] == "text") {
    $form .= "				<option value='text' selected='selected'>".$text['option-text']."</option>\n";
}
else {
    $form .= "				<option value='text'>".$text['option-text']."</option>\n";
}
if ($row['phone_label'] == "other") {
    $form .= "				<option value='other' selected='selected'>".$text['option-other']."</option>\n";
}
else {
    $form .= "				<option value='other'>".$text['option-other']."</option>\n";
}
$form .= "			</select>\n";
//$form .= 				$text['description-phone_label']."\n";
$form .= "		</div>\n";

$form .= "		<div class='label required'>\n";
$form .= "			".$text['label-phone_number']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_phones[0][phone_number]' placeholder='' style=''  maxlength='255' style='max-width:90px;' value=\"".escape($row["phone_number"])."\">\n";
//$form .= 				$text['description-phone_speed_dial']."\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-address_type']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<select class='formfld' name='contact_addresses[0][address_type]'>\n";
$form .= "				<option value=''></option>\n";
if ($row['address_type'] == "work") {
    $form .= "				<option value='work' selected='selected'>".$text['option-work']."</option>\n";
}
else {
    $form .= "				<option value='work'>".$text['option-work']."</option>\n";
}
if ($row['address_type'] == "home") {
    $form .= "				<option value='home' selected='selected'>".$text['option-home']."</option>\n";
}
else {
    $form .= "				<option value='home'>".$text['option-home']."</option>\n";
}
if ($row['address_type'] == "domestic") {
    $form .= "				<option value='domestic' selected='selected'>".$text['option-dom']."</option>\n";
}
else {
    $form .= "				<option value='domestic'>".$text['option-dom']."</option>\n";
}
if ($row['address_type'] == "international") {
    $form .= "				<option value='international' selected='selected'>".$text['option-intl']."</option>\n";
}
else {
    $form .= "				<option value='international'>".$text['option-intl']."</option>\n";
}
if ($row['address_type'] == "postal") {
    $form .= "				<option value='postal' selected='selected'>".$text['option-postal']."</option>\n";
}
else {
    $form .= "				<option value='postal'>".$text['option-postal']."</option>\n";
}
if ($row['address_type'] == "parcel") {
    $form .= "				<option value='parcel' selected='selected'>".$text['option-parcel']."</option>\n";
}
else {
    $form .= "				<option value='parcel'>".$text['option-parcel']."</option>\n";
}
if ($row['address_type'] == "preferred") {
    $form .= "				<option value='preferred' selected='selected'>".$text['option-pref']."</option>\n";
}
else {
    $form .= "				<option value='preferred'>".$text['option-pref']."</option>\n";
}
$form .= "			</select>\n";
$form .= "		</div>\n";

$form .= "		<div class='label required'>\n";
$form .= "			".$text['label-address_address']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_street]' placeholder='".$text['label-address_address']." 1' maxlength='255' value=\"".escape($row["address_street"])."\"><br />\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_extended]' placeholder='".$text['label-address_address']." 2' maxlength='255' value=\"".escape($row["address_extended"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-address_region']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_region]' placeholder='' maxlength='255' value=\"".escape($row["address_region"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-address_locality']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_locality]' placeholder='' maxlength='255' value=\"".escape($row["address_locality"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-address_postal_code']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_postal_code]' placeholder='' maxlength='255' value=\"".escape($row["address_postal_code"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-address_country']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<input class='formfld' type='text' name='contact_addresses[0][address_country]' placeholder='' maxlength='255' value=\"".escape($row["address_country"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label required'>\n";
$form .= "			".$text['label-email_address']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "				<input class='formfld' type='text' name='contact_emails[0][email_address]' placeholder='".escape($text['label-email_address'])."' maxlength='255' value=\"".escape($row["email_address"])."\">\n";
$form .= "		</div>\n";

$form .= "		<div class='label'>\n";
$form .= "			".$text['label-contact_note']."\n";
$form .= "		</div>\n";
$form .= "		<div class='field no-wrap'>\n";
$form .= "			<textarea class='formfld' style='width: 100%; height: 100%;' name='contact_note'>".$contact_note."</textarea>\n";
$form .= "		</div>\n";


$form .= "	</div>\n";
$form .= "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
$form .= "</form>";

echo json_encode($form, JSON_UNESCAPED_UNICODE);
//echo $form;

?>