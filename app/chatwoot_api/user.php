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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'POST') {

    $user_uuid = $_POST['user_uuid'];

    $is_chatwoot_user = chatwoot_user::is_chatwoot_user($user_uuid);

    if (!$is_chatwoot_user) {
        $sql = "SELECT \n";
        $sql .= "   username, \n";
        $sql .= "   user_email \n";
        $sql .= "FROM \n";
        $sql .= "   v_users \n";
        $sql .= "WHERE \n";
        $sql .= "   user_uuid = :user_uuid";

        $parameters['user_uuid'] = $user_uuid;
        $database = new database;
        $user = $database->select($sql, $parameters, 'row');

        $chatwoot_user = chatwoot_user::create($user_uuid, $_SESSION['chatwoot']['account']['id'], $user['username'], $user['user_email'], generate_special_password());

        if ($chatwoot_user === false) {
            message::add('erro ao criar', 'negative');
        } else {
            message::add('sucesso', 'positive');
        }
    }
    
    header('Location: /app/chatwoot_api');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'DELETE') {
    
    $error = false;
    foreach ($_POST['users'] as $id => $uuid) {
        echo $id."=>".$uuid;
        echo "<br>";
        $user = new chatwoot_user($id, $uuid);
        $success = $user->delete();
        if (!$success) {
            $error = true;
            break;
        }
    }
    if ($error) {
        message::add('erro', 'negative');
    } else {
        message::add('sucesso', 'positive');
    }

    header('Location: /app/chatwoot_api');
    exit;
    
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $user_uuid = $_GET['user_uuid'];

    if (!is_uuid($user_uuid)) {

        header('Location: /app/chatwoot_api');
        exit;
    }

    $user = chatwoot_user::get_user_by_uuid($user_uuid);
}



//add multi-lingual support
$language = new text;
$text = $language->get();

//show content
$document['title'] = $text['title-user'];

require_once "resources/header.php";
?>

<div class="action_bar">
	<div class="heading">
		<b><?= $user['username'] ?></b>
	</div>
	<div class="actions">
		
	</div>
</div>

<form>
    <div class="field">
        <label for="user_id">Id</label>
        <input id="user_id" value=<?= $user['user_id'] ?>>
    </div>
    <div class="field">
        <label for="access_token">Acess Token</label>
        <input id="access_token" value=<?= $user['access_token'] ?>>
    </div>
    <div class="field">
        <label for="pubsub_token">Pubsub Token</label>
        <input id="pubsub_token" value=<?= $user['pubsub_token'] ?>>
    </div>
</form>

<?php 
//include the footer
require_once "resources/footer.php";