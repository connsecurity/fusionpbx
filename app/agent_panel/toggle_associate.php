<?php


//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//check permissions
if (permission_exists('contact_relation_edit') || permission_exists('contact_relation_add')) {
    //access granted
}
else {
    echo "access denied";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //validate the token
    // $token = new token;
    // if (!$token->validate($_SERVER['PHP_SELF'])) {
    //     message::add($text['message-invalid_token'],'negative');   
    //     exit;
    // }


    $contact_uuid = $_SESSION['user']['contact_uuid'];
    $relation_contact_uuid = $_POST['contact_uuid'];

    $sql = "SELECT ";
    $sql .=     "contact_relation_uuid ";
    $sql .= "FROM ";
    $sql .=     "v_contact_relations ";
    $sql .= "WHERE ";
    $sql .=     "domain_uuid = :domain_uuid ";
    $sql .= "AND ";
    $sql .=     "(";
    $sql .=         "(";
    $sql .=             "contact_uuid = :contact_uuid ";
    $sql .=         "AND ";
    $sql .=             "relation_contact_uuid = :relation_contact_uuid ";
    $sql .=         "AND ";
    $sql .=             "relation_label = 'Customer'";
    $sql .=         ")";
    $sql .=     "OR ";
    $sql .=         "(";
    $sql .=             "contact_uuid = :relation_contact_uuid ";
    $sql .=         "AND ";
    $sql .=             "relation_contact_uuid = :contact_uuid ";
    $sql .=         "AND ";
    $sql .=             "relation_label = 'Agent'";
    $sql .=         ")";
    $sql .=     ")";

    $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    $parameters['contact_uuid'] = $contact_uuid;
    $parameters['relation_contact_uuid'] = $relation_contact_uuid;
    $database = new database;
    $result = $database->select($sql, $parameters, 'all');

    if (empty($result)) {
        $contact_relation_uuid = uuid();
		$array['contact_relations'][0]['contact_relation_uuid'] = $contact_relation_uuid;
        $array['contact_relations'][0]['contact_uuid'] = $contact_uuid;
        $array['contact_relations'][0]['domain_uuid'] = $_SESSION['domain_uuid'];
        $array['contact_relations'][0]['relation_label'] = "Customer";
        $array['contact_relations'][0]['relation_contact_uuid'] = $relation_contact_uuid;

        $contact_relation_uuid = uuid();
        $array['contact_relations'][1]['contact_relation_uuid'] = $contact_relation_uuid;
        $array['contact_relations'][1]['domain_uuid'] = $_SESSION['domain_uuid'];
        $array['contact_relations'][1]['contact_uuid'] = $relation_contact_uuid;
        $array['contact_relations'][1]['relation_label'] = "Agent";
        $array['contact_relations'][1]['relation_contact_uuid'] = $contact_uuid; 

        $database->app_name = 'contacts';
        $database->app_uuid = '04481e0e-a478-c559-adad-52bd4174574c';
        $database->save($array);
        if ($database->message['code'] == '200') {
            $message = 'added';
        }
        unset($array);



    } else {
        $x = 0;
        foreach ($result as $row) {
            $array['contact_relations'][$x]['checked'] = 'true';
            $array['contact_relations'][$x]['contact_relation_uuid'] = $row['contact_relation_uuid'];
            $x++;
        }
        $database->delete($array);
        unset($array);
        if ($database->message['code'] == '200') {
            $message = 'deleted';
        }
    }

    $json = [];
    $json['message'] = $message;
    $json['result'] = $result;
    echo json_encode($json, JSON_UNESCAPED_UNICODE);

}