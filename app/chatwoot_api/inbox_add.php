<?php

//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "resources/chatwoot_api.php";
require "app/meta/resources/meta_api.php";

//check permissions
if (permission_exists('chatwoot_api_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//if post, save the inbox
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //validate the token
    $token = new token;
    if (!$token->validate($_SERVER['PHP_SELF'])) {
        message::add($text['message-invalid_token'],'negative');
        header('Location: ./');
        exit;
    }

	$phone_number = explode(':', $_POST['phone_number']);
	$name = $_POST['name'];
	$waba_id = $phone_number[0];
	$phone_number_display_number = '+'.$phone_number[1];
	$phone_number_id = $phone_number[2];

	$account_id = $_SESSION['chatwoot']['account']['id'];

	$channel = array(
		'type' => 'whatsapp',
		'provider' => 'whatsapp_cloud',
		'phone_number' => $phone_number_display_number,
		'provider_config' => array(
			'api_key' => $_SESSION['meta']['system_access_token']['text'],
			'phone_number_id' => $phone_number_id,
			'business_account_id' => $waba_id
		)
	);

	$inbox = create_inbox($account_id, $name, $channel);

	if ($inbox->id > 0) {
		message::add($text['message-add'], 'posivite');
        header('Location: ./');
        exit;
	} else {
		var_dump($inbox);
		var_dump($channel);
		
		// message::add("Error", 'negative');
        // header('Location: ./');
        exit;
	}
}


//add multi-lingual support
$language = new text;
$text = $language->get();

//create token
$object = new token;
$token = $object->create("/app/chatwoot_api/inbox_add.php");

$wabas = load_domain_wabas();

?>
<div>
	<span title="<?= $text['button-close'] ?>" class='modal-close' onclick="modal_close();">&times</span>
	<span class='modal-title'><?= $text['label-inbox_add'] ?></span>
	<form id="inbox_form" method="post" action="inbox_add.php">
		<label><?= $text['label-name'] ?>:<input type="text" name="name"/></label>
		<label>
			<?= $text['label-phone_number'] ?>:
			<select name="phone_number">
				<?php foreach ($wabas as $waba) : ?>
					<optgroup label="<?= $waba['name'] ?>">
					<?php foreach($waba['phone_numbers'] as $phone_number): ?>
						<option value="<?= $waba['waba_id'].":".$phone_number['display_phone_number'].":".$phone_number['id'] ?>"><?= $phone_number['display_phone_number'] ?></option>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</select>
		</label>
		<input type="hidden" name="<?= $token['name'] ?>" value="<?= $token['hash'] ?>"/>
	</form>
	<br/>
	<span class='modal-actions'>
		<?= button::create(['type'=>'button','label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'collapse'=>'never','onclick'=>'modal_close();']) ?>
		<?= button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>'never','form'=>'inbox_form']) ?>
	</span>
</div>