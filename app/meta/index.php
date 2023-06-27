<?php
//includes
require_once dirname(__DIR__, 2) . "/resources/require.php";
require_once "resources/check_auth.php";
require "resources/meta_api.php";

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

//get the domain WABA's
$wabas = load_domain_wabas();

//show content
$document['title'] = $text['title-meta'];
require_once "resources/header.php";

?>
<div class="action_bar">
    <div class="heading">
            <b><?= $text['label-waba_full'] ?></b>
    </div>
    <div class="actions">
        <?php if(permission_exists('meta_edit')): ?>
            <?= button::create(['type'=>'button','label'=>$text['label-manually_add'],'icon'=>$_SESSION['theme']['button_icon_add'], 'link'=>'waba_add.php']) ?> OR 
        <?php endif; ?>
        <button onclick="launchWhatsAppSignup()" class="facebook"><?= $text['label-facebook_login'] ?></button>
    </div>
</div>
<br/>
<?php foreach($wabas as $waba): ?>
    <br/>
    <div class="waba">
        <?php if(isset($waba['error'])): ?>
            <div class="heading">
                <b><?= $waba['waba_id'] ?> <?= $text['label-error_getting_waba'] ?></b>
            </div>
        <?php else: ?>
            <div class="action_bar">
                <div class="heading">
                    <a href="waba.php?uuid=<?= $waba['waba_uuid'] ?>"><b><?= $waba['waba_id'] ?> <?= $waba['name'] ?></b></a>
                </div>
                <div class="actions">
                    <?= button::create(['type'=>'button','label'=>$text['label-templates'],'icon'=>'fas fa-th-list']) ?>
                </div>
            </div>
            <br>
            <div class="waba_phone_number">           
                <table>
                    <tr>
                        <th><?= $text['label-id'] ?></th>
                        <th><?= $text['label-verified_name'] ?></th>
                        <th><?= $text['label-display_phone_number'] ?></th>
                        <th><?= $text['label-quality_rating'] ?></th>
                    </tr>
                    <?php foreach($waba['phone_numbers'] as $phone_number): ?>
                        <tr>
                            <td><?= $phone_number['id'] ?></td>
                            <td><?= $phone_number['verified_name'] ?></td>
                            <td><?= $phone_number['display_phone_number'] ?></td>
                            <td><?= $phone_number['quality_rating'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<script>
window.fbAsyncInit = function() {
    // JavaScript SDK configuration and setup
    FB.init({
        appId: '<?= $_SESSION['meta']['app_id']['text'] ?>', // Facebook App ID
        cookie: true, // enable cookies
        xfbml: true, // parse social plugins on this page
        version: 'v17.0' //Graph API version
    });
};

// Load the JavaScript SDK asynchronously
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s);
    js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

// Facebook Login with JavaScript SDK
function launchWhatsAppSignup() {
    // Conversion tracking code
    // fbq && fbq('trackCustom', 'WhatsAppOnboardingStart', {
    //     appId: '<?= $_SESSION['meta']['app_id']['text'] ?>',
    //     feature: 'whatsapp_embedded_signup'
    // });

    // Launch Facebook login
    FB.login(function(response) {
        if (response.authResponse) {
            const accessToken = response.authResponse.accessToken;
            //Use this token to call the debug_token API and get the shared WABA's ID
        } else {
            console.log('User cancelled login or did not fully authorize.');
        }
    }, {
        scope: 'whatsapp_business_management',
        extras: {
            feature: 'whatsapp_embedded_signup',
            version: 2,
            setup: {
                // Prefilled data can go here
            }
        }
    });
}

const sessionInfoListener = (event) => {
  if (event.origin !== "https://www.facebook.com") return;
  try {
    const data = JSON.parse(event.data);
    if (data.type === 'WA_EMBEDDED_SIGNUP') {
     // if user finishes the embedded sign up flow
     if (data.event === 'FINISH') {
       const {phoneID, wabaID} = data.data;
     }
     // if user cancels the embedded sign up flow
     else {
      const{currentStep} = data.data;
  }
    }
  } catch {
    // Don’t parse info that’s not a JSON
    console.log('Non JSON Response', event.data);
  }
};

window.addEventListener('message', sessionInfoListener);  
</script>
<?php
require_once "resources/footer.php";