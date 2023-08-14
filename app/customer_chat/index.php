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
				<button id="options_button"><i class="fas fa-chevron-down"></i></button>
				<div id="dropdown_pane" style="display: none;">
					<button id="mark_as_pending">
						<i class="fas fa-pause"></i>
						<?= $text['label-mark_pending'] ?>
					</button>
					<div id="divider"></div>
					<div id="snooze_container">
						<span><?= $text['label-snooze_until'] ?></span>						
						<button id="snooze_next_reply">
							<i class="fas fa-comment-dots"></i>
							<?= $text['label-next_reply'] ?>
						</button>
						<button id="snooze_tomorrow">
							<i class="fas fa-calendar-day"></i>
							<?= $text['label-tomorrow'] ?>
						</button>
						<button id="snooze_next_week">
							<i class="fas fa-calendar-week"></i>
							<?= $text['label-next_week'] ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div id="chat_messages"></div>
		<form id="message_form">
			<button type="button" id="templates_button"><i class="fab fa-whatsapp"></i></button>
			<textarea id="message_input" placeholder="Type your message..."> </textarea>
			<button type="submit" id="send_button" class="fas fa-paper-plane"></button>
		</form>
	</div>
</div>

<div id="modal_templates" class="modal-window">
	<div id="modal_container">
		<span title="<?= $text['button-close'] ?>" class='modal-close' onclick="modal_close();">&times</span>
		<span class='modal-title'><?= $text['label-templates'] ?></span>
		<span id="templates_description"><?= $text['description-templates'] ?></span>
		<div id="template_list"></div>
		<div id="template_process">
			<div id="template_content"></div>
			<div class="modal_actions">
				<button type="button" id="templates_back_button"><?= $text['label-go_back'] ?></button>
				<button type="button" id="templates_send_button"><?= $text['label-send_message'] ?></button>
			</div>
		</div>
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