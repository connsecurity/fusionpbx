<?php

//includes
include "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require "resources/chatwoot_api.php";

//check permissions
if (permission_exists('chatwoot_api_view')) {
	//access granted
} else {
	echo "access denied";
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	if ($_POST['agent_ids'] === "") {
		$agent_ids = array();
	} else {
		$agent_ids = explode(",", $_POST['agent_ids']);
		$agent_ids = array_map('intval', $agent_ids);
	}
	
	$inbox_id = $_GET['id'];

	$response = update_inbox_agents($_SESSION['chatwoot']['account']['id'], $inbox_id, $agent_ids);

	if (is_array($response['payload'])) {
		message::add('sucesso', 'positive');
	} else {		
		message::add('erro', 'negative');
	}

	header('Location: /app/chatwoot_api/inbox.php?id='.$inbox_id);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

	if (ctype_digit($_GET['id'])) {
		$inbox_id = $_GET['id'];
		$inbox = get_inbox($_SESSION['chatwoot']['account']['id'], $inbox_id);
		$agents = chatwoot_inbox::get_inbox($inbox_id)->get_all_agents();
		
	}
}

//add multi-lingual support
$language = new text;
$text = $language->get();

//show content
$document['title'] = $text['title-inbox'];
require_once "resources/header.php";
?>

<div class="action_bar">
	<div class="heading">
		<b><?= $inbox->name ?></b>
	</div>
	<div class="actions">
		<?= button::create(['type'=>'button','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'button_save','style'=>'margin-left: 15px;']) ?>
	</div>
</div>

<form>
	<div>
		<label for="channel_type">Channel</label>
		<input id="channel_type" value=<?= $inbox->channel_type ?> disabled>
	</div>
	<div>
		<label for="phone_number">Phone Number</label>
		<input id="phone_number" value=<?= $inbox->phone_number ?> disabled>
	</div>
	<div>
		<label for="phone_number_id">Phone Number ID</label>
		<input id="phone_number_id" value=<?= $inbox->provider_config->phone_number_id ?> disabled>
	</div>
	<div>
		<label for="business_account_id">Business Account ID</label>
		<input id="business_account_id" value=<?= $inbox->provider_config->business_account_id ?> disabled>
	</div>
	<div>
		<label for="api_key">Whatsapp API Token</label>
		<input id="api_key" value=<?= $inbox->provider_config->api_key ?> disabled>
	</div>
	<div>
		<label for="webhook_verify_token">Webhook Token</label>
		<input id="webhook_verify_token" value=<?= $inbox->provider_config->webhook_verify_token ?> disabled>
	</div>
</form>

<div id="agent_listbox">
	<span><b><?= $text['label-agents'] ?></b></span>
	<select id="available_agents" multiple>
		<?php foreach ($agents['unassigned'] as $id => $name) : ?>
			<option value="<?= $id ?>">
				<?= $name ?>
			</option>
		<?php endforeach; ?>
	</select>

	<button id="button_add">></button>
	<button id="button_remove"><</button>
	<button id="button_add_all">>></button>
	<button id="button_remove_all"><<</button>

	<select id="assigned_agents" multiple>
		<?php foreach ($agents['assigned'] as $id => $name) : ?>
			<option value="<?= $id ?>">
				<?= $name ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>

<script>

const button_save_elem = document.getElementById('button_save');
const available_agents_elem = document.getElementById('available_agents');
const assigned_agents_elem = document.getElementById('assigned_agents');
const button_add_elem = document.getElementById('button_add');
const button_remove_elem = document.getElementById('button_remove');
const button_add_all_elem = document.getElementById('button_add_all');
const button_remove_all_elem = document.getElementById('button_remove_all');

button_save_elem.addEventListener('click', () => {submit_form()});

button_add_elem.addEventListener('click', () => {
	const selected_agents = Array.from(available_agents_elem.selectedOptions);
	selected_agents.forEach(add_agent);
});

button_remove_elem.addEventListener('click', () => {
	const selected_agents = Array.from(assigned_agents_elem.selectedOptions);
	selected_agents.forEach(remove_agent);
});

button_add_all_elem.addEventListener('click', () => {
	const available_agents = Array.from(available_agents_elem.options);
	available_agents.forEach(add_agent);
});

button_remove_all_elem.addEventListener('click', () => {
	const assigned_agents = Array.from(assigned_agents_elem.options);
	assigned_agents.forEach(remove_agent);
});

function add_agent(agent) {
	available_agents_elem.removeChild(agent);
	agent.selected = false;
	assigned_agents_elem.appendChild(agent);
}
function remove_agent(agent) {
	assigned_agents_elem.removeChild(agent);
	agent.selected = false;
	available_agents_elem.appendChild(agent);
}

function submit_form() {
	const assigned_agents = Array.from(assigned_agents_elem.options);
	const agent_ids = assigned_agents.map(agent => agent.value);
	console.log(agent_ids);

	const form = document.createElement("form");
	const input = document.createElement("input");

	form.method = "POST";
	form.action = "";

	input.name = "agent_ids";
	input.value = agent_ids;
	input.type = "hidden";

	form.appendChild(input);
	document.body.appendChild(form);
	form.submit();
}

</script>

<?php

//include the footer
require_once "resources/footer.php";