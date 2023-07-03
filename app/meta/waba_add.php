<?php
//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "resources/meta_api.php";

//check permissions
if (permission_exists('meta_edit')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//if post, save the waba
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //validate the token
    $token = new token;
    if (!$token->validate($_SERVER['PHP_SELF'])) {
        message::add($text['message-invalid_token'],'negative');
        header('Location: ./');
        exit;
    }

    $waba_id = $_POST['id'];

    $waba = new waba($waba_id);
    $success = $waba->save();

    if ($success) {
        message::add($text['message-add']);        
    } else {
        message::add($text['message-failed'],'negative');
    }
    header('Location: ./');
    exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//create token
$object = new token;
$token = $object->create($_SERVER['PHP_SELF']);

//show content
$document['title'] = $text['title-waba_add'];
require_once "resources/header.php";
?>

<div class="action_bar">
    <div class="heading">
            <b><?= $text['title-waba_add'] ?></b>
    </div>
    <div class="actions">
        <?= button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','link'=>'./']) ?>
        <?= button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;','form'=>'waba_form']) ?>
    </div>
</div>
<form id="waba_form" method="post"  action="waba_add.php">
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%" class="vncellreq" valign="top" align="left" nowrap="nowrap">
                <?= $text['label-id'] ?>
            </td>
            <td width="70%" class="vtable" align="left">
                <input class="formfld" type="text" name="id" maxlength="16" required="required"/>
                <br/>
                <?= $text['description-id'] ?>
            </td>
        </tr>
    </table>
    <input type="hidden" name="<?= $token['name'] ?>" value="<?= $token['hash'] ?>"/>
</form>

<?php
require_once "resources/footer.php";