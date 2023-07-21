<?php

//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "resources/chatwoot_api.php";

//check permissions
if (permission_exists('chatwoot_api_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//if there is no account associated with this domain, create one
if ($_SESSION['chatwoot']['account']['id'] === false) {
	$_SESSION['chatwoot']['account']['id'] = chatwoot_account::create();
}

$account_id = $_SESSION['chatwoot']['account']['id'];

$inbox_list = chatwoot_inbox::get_inbox_list()->payload;
$user_list = chatwoot_user::get_user_list('LEFT');

//show content
$document['title'] = $text['title-chatwoot_api'];
require_once "resources/header.php";

?>

<div class="heading">
		<b><?= $text['title-chatwoot_api'] ?> ID: <?= $account_id ?></b>
</div>

<div class="action_bar">
	<div class="heading">
		<b><?= $text['label-inboxes'] ?> (<?= count($inbox_list) ?>)</b>
	</div>
	<div class="actions">
		<?= button::create(['type'=>'button','label'=>$text['button-add'],'icon'=>$_SESSION['theme']['button_icon_add'], 'onclick'=>'load_modal_inbox_add();']) ?>
	</div>
</div>

<table class="list">
	<tbody>
		<tr class="header">			
			<th><?= $text['label-name'] ?></th>
			<th><?= $text['label-type'] ?></th>
		</tr>
		<?php foreach ($inbox_list as $inbox): ?>
		<tr class="list-row" <?= "href='/app/chatwoot_api/inbox.php?id=".$inbox->id."'" ?>>
			<td><?= $inbox->name ?></td>
			<td><?= $inbox->channel_type ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="action_bar">
	<div class="heading">
		<b><?= $text['label-agents'] ?> (<?= count($user_list) ?>)</b>
	</div>
	<div class="actions">
		<?= button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'id'=>'btn_delete','name'=>'btn_delete','style'=>'display: none;']) ?>
	</div>
</div>

<form action="/app/chatwoot_api/user.php" method="POST">
	<input type="hidden" id="action" name="action" value="POST">
	<table class="list">
		<tbody>
			<tr class="header">
				<th class="checkbox"><input type="checkbox" id="checkbox_all"></th>
				<th><?= $text['label-name'] ?></th>
				<th>ID</th>
			</tr>
			<?php foreach ($user_list as $user): ?>
			<tr class="list-row" <?= ($user['user_id'] > 0 ? 'href=/app/chatwoot_api/user.php?user_uuid='.$user['user_uuid'] : '') ?> >
				<td class="checkbox"><input type="checkbox" name="users[<?=$user['user_id']?>]" value ="<?=$user['user_uuid']?>"></td>
				<td><?= $user['username'] ?></td>
				<td><?= ($user['user_id'] > 0 ? $user['user_id'] : new_user_button($user['user_uuid'])) ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</form>

<div id="modal_inbox_add" class="modal-window"></div>

<script>

async function load_modal_inbox_add() {
    let init = {
            method: 'GET',
            headers: {
                "Content-Type": "text/html; charset=UTF-8"
            }
        };
    
    try {
        const response = await fetch('inbox_add.php', init);
        var modal_html = await response.text();

    } catch (error) {
        console.error(error);
    }

    const modal = document.getElementById('modal_inbox_add');
    modal.innerHTML = modal_html;
    modal_open('modal_inbox_add');
}

const checkbox_all_elem = document.getElementById('checkbox_all');
const checkboxes = document.querySelectorAll('input[type=checkbox]');
const button_delete_elem = document.getElementById('btn_delete');

checkbox_all_elem.addEventListener('click', () => list_all_toggle());
checkboxes.forEach((checkbox) => {
	checkbox.addEventListener('click', checkbox_on_change);
});
button_delete_elem.addEventListener('click', () => submit_delete());

function submit_delete() {
	const form = document.querySelector('form');
	const action = document.getElementById('action');
	action.value = "DELETE";
	form.submit();
}

</script>

<?php
//include the footer
require_once "resources/footer.php";

function new_user_button($user_uuid) {
	return button::create([
		'type' => 'submit',
		'icon' => $_SESSION['theme']['button_icon_add'],
		'value' => $user_uuid,
		'name' => 'user_uuid'
	]);
}