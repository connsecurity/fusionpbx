<?php
//includes
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require "resources/meta_api.php";
require "test_functions.php";

//check permissions
if (permission_exists('meta_view')) {
	//access granted
}
else {
	echo "access denied";
	exit;
}

//add multi-lingual support
$language = new text;
$text = $language->get();

if (empty($_GET['uuid']) || !is_uuid($_GET['uuid'])) {
    message::add($text['message-invalid_uuid'],'negative');
    header('Location: ./');
    exit;
}

$waba_id = waba::find_by_uuid($_GET['uuid']);

if (!$waba_id) {
    message::add($text['message-waba_not_found'],'negative');
    header('Location:./');
    exit;
}

$waba = get_waba_details($waba_id);
$templates = json_decode(get_waba_templates($waba_id), true)['data'];

//show content
$document['title'] = $waba['name'];
require_once "resources/header.php";
?>

<div class="action_bar">
    <div class="heading">
        <b><?= $waba_id ?> <?= $waba['name'] ?></b>
    </div>
    <div class="actions">
        <?= button::create(['type'=>'button','label'=>$text['label-templates'],'icon'=>'fas fa-th-list']) ?>
    </div>
</div>
<br/>

<!-- show templates -->
<?php foreach ($templates as $template): ?>
    <br/>
    <div class="waba">
        <div class="action_bar">
            <div class="heading">
                <b><?= $template['name'] ?></b>
                <span class="template_category"><?= title_case($template['category']) ?></span>
                <span class="template_status <?= strtolower($template['status']) ?>"><?= title_case($template['status']) ?></span>
            </div>
            <div class="actions">                
                <?= button::create(['type'=>'button','label'=>$text['button-edit'],'icon'=>$_SESSION['theme']['button_icon_edit']]) ?>
            </div>
        </div>
        <br/>
        <div class="waba_template">
            <?php foreach ($template['components'] as $component): ?>
                <div class="waba_template_component">
                    <span class="waba_template_component_type"><?= title_case($component['type']) ?></span>
                    <div class="waba_template_component_text"><?= $component['text'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>


<?php
require_once "resources/footer.php";