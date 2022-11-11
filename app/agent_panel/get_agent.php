<?php

//set the include path
$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
require_once "resources/require.php";
require_once "resources/check_auth.php";

//get agent extensions
if(!isset($_SESSION['agent']['extension'])){

    $sql = "SELECT ";
    $sql .=     "ex.extension,";
    $sql .=     "es.extension_uuid, ";
    $sql .=     "es.extension_setting_value::json->> 'start' as start_epoch, ";
    $sql .=     "es.extension_setting_value::json->> 'end' as end_epoch ";
    $sql .= "FROM ";
    $sql .=     "v_extension_settings as es ";
    $sql .= "INNER JOIN ";
    $sql .=     "v_extensions as ex ";
    $sql .= "ON ";
    $sql .=     "es.domain_uuid = :domain_uuid ";
    $sql .= "AND ";
    $sql .=     "es.extension_uuid = ex.extension_uuid ";
    $sql .= "AND ";
    $sql .=     "es.extension_setting_type = 'param' ";
    $sql .= "AND ";
    $sql .=     "es.extension_setting_name ~ '^agent_\d+$' ";
    $sql .= "AND ";
    $sql .=     "es.extension_setting_value::json->> 'uuid' = :agent_uuid ";
    $sql .= "ORDER BY ";
    $sql .=     "start_epoch ";
    $sql .= "DESC ";

    $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
    $parameters['agent_uuid'] = $_SESSION['user']['user_uuid'];

    $database = new database;
    $result = $database->select($sql, $parameters, 'all');
    
    $x = 0;
    foreach ($result as $row) {
        $_SESSION['agent']['extension'][$x]['extension'] = $row['extension'];
        $_SESSION['agent']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
        $_SESSION['agent']['extension'][$x]['start_epoch'] = $row['start_epoch'];        
        $_SESSION['agent']['extension'][$x]['end_epoch'] = $row['end_epoch'];
        $x++;
    }
    unset($sql, $parameters, $result);

    if (!isset($_SESSION['agent']['extension']) || $_SESSION['agent']['extension'][0]['extension_uuid'] == '') {
        $x = 0;
        foreach ($_SESSION['user']['extension'] as $extension) {
            $_SESSION['agent']['extension'][$x]['extension'] = $extension['user'];
            $_SESSION['agent']['extension'][$x]['extension_uuid'] = $extension['extension_uuid'];
            $x++;
        }
    }
    unset($x);
    
}