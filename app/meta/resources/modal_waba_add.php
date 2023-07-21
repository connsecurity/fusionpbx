<?php
//includes
require_once dirname(__DIR__, 3) . "/resources/require.php";
require_once "resources/check_auth.php";
require "meta_api.php";

//check permissions
if (permission_exists('meta_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get(null, 'app/meta');

//create token
$object = new token;
$token = $object->create("/app/meta/waba_add.php");

//get wabas
$shared_wabas = json_decode(get_shared_wabas($_SESSION['meta']['business_id']['text']))->data;
$owned_wabas = json_decode(get_owned_wabas($_SESSION['meta']['business_id']['text']))->data;
?>
<div>
	<span title="<?= $text['button-close'] ?>" class='modal-close' onclick="modal_close();">&times</span>
	<span class='modal-title'><?= $text['label-manually_add'] ?></span>
	<form id="waba_form" method="post" action="waba_add.php">		
		<select name="id">
			<option value=""></option>
			<optgroup label="<?= $text['label-shared_wabas'] ?>">
				<?php foreach ($shared_wabas as $shared_waba) : ?>
					<option value="<?= $shared_waba->id ?>"><?= $shared_waba->name ?></option>
				<?php endforeach; ?>
			</optgroup>
			<optgroup label="<?= $text['label-owned_wabas'] ?>">
				<?php foreach ($owned_wabas as $owned_waba) : ?>
					<option value="<?= $owned_waba->id ?>"><?= $owned_waba->name ?></option>
				<?php endforeach; ?>
			</optgroup>
		</select>
		<input type="hidden" name="<?= $token['name'] ?>" value="<?= $token['hash'] ?>"/>
	</form>
	<br/>
	<span class='modal-actions'>
		<?= button::create(['type'=>'button','label'=>$text['button-cancel'],'icon'=>$_SESSION['theme']['button_icon_cancel'],'collapse'=>'never','onclick'=>'modal_close();']) ?>
		<?= button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'collapse'=>'never','form'=>'waba_form']) ?>
	</span>
</div>