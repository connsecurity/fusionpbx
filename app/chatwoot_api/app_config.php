<?php

	//application details
		$apps[$x]['name'] = "Chatwoot API";
		$apps[$x]['uuid'] = "ea5150fb-8722-45a7-bea7-361d4dd54092";
		$apps[$x]['category'] = "";
		$apps[$x]['subcategory'] = "";
		$apps[$x]['version'] = "0.1";
		$apps[$x]['license'] = "";
		$apps[$x]['url'] = "https://www.chatwoot.com/";
		$apps[$x]['description']['en-us'] = "Chatwoot API";
		$apps[$x]['description']['en-gb'] = "Chatwoot API";
		$apps[$x]['description']['ar-eg'] = "Chatwoot API";
		$apps[$x]['description']['de-at'] = "Chatwoot API";
		$apps[$x]['description']['de-ch'] = "Chatwoot API";
		$apps[$x]['description']['de-de'] = "Chatwoot API";
		$apps[$x]['description']['es-cl'] = "Chatwoot API";
		$apps[$x]['description']['es-mx'] = "Chatwoot API";
		$apps[$x]['description']['fr-ca'] = "Chatwoot API";
		$apps[$x]['description']['fr-fr'] = "Chatwoot API";
		$apps[$x]['description']['he-il'] = "Chatwoot API";
		$apps[$x]['description']['it-it'] = "Chatwoot API";
		$apps[$x]['description']['nl-nl'] = "Chatwoot API";
		$apps[$x]['description']['pl-pl'] = "Chatwoot API";
		$apps[$x]['description']['pt-br'] = "Chatwoot API";
		$apps[$x]['description']['pt-pt'] = "Chatwoot API";
		$apps[$x]['description']['ro-ro'] = "Chatwoot API";
		$apps[$x]['description']['ru-ru'] = "Chatwoot API";
		$apps[$x]['description']['sv-se'] = "Chatwoot API";
		$apps[$x]['description']['uk-ua'] = "Chatwoot API";
        
	//default settings
		$y=0;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "d28b319b-f96c-4d2c-b0a0-b1a36b5defbb";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "chat";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "chatwoot_url";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Base URL for chatwoot API calls";
		$y++;
		$apps[$x]['default_settings'][$y]['default_setting_uuid'] = "0632e7ec-3940-4b6a-b89e-83935cbcbbbd";
		$apps[$x]['default_settings'][$y]['default_setting_category'] = "chat";
		$apps[$x]['default_settings'][$y]['default_setting_subcategory'] = "platform_access_token";
		$apps[$x]['default_settings'][$y]['default_setting_name'] = "text";
		$apps[$x]['default_settings'][$y]['default_setting_value'] = "";
		$apps[$x]['default_settings'][$y]['default_setting_enabled'] = "false";
		$apps[$x]['default_settings'][$y]['default_setting_description'] = "Chatwoot Platform Access Token";

	//schema details
		$y=0;
		$apps[$x]['db'][$y]['table']['name'] = "v_chatwoot";
		$apps[$x]['db'][$y]['table']['parent'] = "";
		$z=0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "account_id";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "integer";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "integer";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "int";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "primary";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "uuid";
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "char(36)";
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = "foreign";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = "v_domains";
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = "domain_uuid";
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "";

	//permission details
		$y=0;
		$apps[$x]['permissions'][$y]['name'] = "chatwoot_api_view";
		$apps[$x]['permissions'][$y]['menu']['uuid'] = "3fd0e3fc-ae45-41e0-a365-82c52efaadab";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
		$y++;
		$apps[$x]['permissions'][$y]['name'] = "chatwoot_api_edit";
		$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
?>