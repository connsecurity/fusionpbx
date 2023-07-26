<?php

//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "app/chatwoot_api/resources/chatwoot_api.php";

//check permissions
if (permission_exists('customer_chat_view')) {
	//access granted
} else {
	//echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//show content
require_once "resources/header.php";

?>

<div id="customer_chat">
	<div id="conversation_list">
		<div id="username"><?= $_SESSION['username'] ?></div>
	</div>
	<div id="chat">
		<div id="chat_header">
			<span id="contact_name"><?= $text['label-contact_name'] ?></span>
			<div id="actions">
				<button id="action_button"><?= $text['label-resolve'] ?></button>
				<button id="options_button">?</button>
			</div>
		</div>
		<div id="chat_messages"></div>
		<form id="message_form">
			<textarea id="message_input" placeholder="Type your message..."> </textarea>
			<button type="submit" id="send_button" class="fas fa-paper-plane"></button>
		</form>
	</div>
</div>

<script>
window.chatwoot = {};
chatwoot.chatwoot_api_url = "<?= $_SESSION['chat']['chatwoot_url']['text'] . "/api/v1" ?>";
chatwoot.websocket_url = "<?= str_replace("https", "wss", $_SESSION['chat']['chatwoot_url']['text']) . "/cable" ?>";
chatwoot.account_id = "<?= $_SESSION['chatwoot']['account']['id'] ?>";
chatwoot.user_id = "<?= $_SESSION['chatwoot']['user']['id']  ?>";
chatwoot.user_api_access_token = "<?= $_SESSION['chatwoot']['user']['access_token'] ?>";
chatwoot.contact_pubsub_token = "<?= $_SESSION['chatwoot']['user']['pubsub_token'] ?>";

//labels for buttons
chatwoot.label_resolve = "<?= $text['label-resolve'] ?>";
chatwoot.label_open = "<?= $text['label-open'] ?>";
</script>
<script src="./app.js?v=<?= time(); ?>"></script>

<?php
//include the footer
require_once "resources/footer.php";
?>