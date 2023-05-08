<?php

if (!class_exists('chatwoot_user')) {
    class chatwoot_user {

        private $user_id;
        private $user_uuid;
        private $account_id;
        private $domain_uuid;
        private $access_token;
        private $pubsub_token;
        private $app_name;
        private $app_uuid;

        public function __construct($user_id, $user_uuid, $account_id) {

            $this->user_id = $user_id;
            $this->user_uuid = $user_uuid;
            $this->account_id = $account_id;
            $this->domain_uuid = $_SESSION['domain_uuid'];
            $this->app_name = 'Chatwoot API';
            $this->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
        }

        public static function create($user_uuid, $account_id, $name, $email, $password, $custom_attributes = NULL) {

            $user_data = create_user($name, $email, $password, $custom_attributes);            
            $chatwoot_user = new static($user_data->id, $user_uuid, $account_id);

            if (!$chatwoot_user->user_id > 0) {
                // throw new RuntimeException("Error in chatwoot: ".$user_data->message);
                return false;
            }

            $account_user = create_account_user($account_id, $chatwoot_user->user_id);

            if (!$account_user->id > 0) {
                delete_user($chatwoot_user->user_id);
                return false;
            }

            
            $chatwoot_user->domain_uuid = $_SESSION['domain_uuid'];
            $chatwoot_user->access_token = $user_data->access_token;
            $chatwoot_user->pubsub_token = $user_data->pubsub_token;

            //prepare the array
            $array['chatwoot_user'][0]['user_id'] = $chatwoot_user->user_id;
            $array['chatwoot_user'][0]['user_uuid'] = $user_uuid;
            $array['chatwoot_user'][0]['account_id'] = $account_id;
            $array['chatwoot_user'][0]['domain_uuid'] = $chatwoot_user->domain_uuid;
            $array['chatwoot_user'][0]['access_token'] = $chatwoot_user->access_token;
            $array['chatwoot_user'][0]['pubsub_token'] = $chatwoot_user->pubsub_token;

            //add the temporary permission object
            $p = new permissions;
            $p->add('chatwoot_user_add', 'temp');

            //save the data
            $database = new database;
            $database->app_name = 'Chatwoot API';
            $database->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
            $success = $database->save($array);
            $message = $database->message;
            unset($array);

            $p->delete('chatwoot_user_add', 'temp');

            if (!$success) {
                // throw new RuntimeException("Error in databse: ".$message);
                return false;
            }

            return $chatwoot_user;
        }

        public function delete($platforms = "both") {

            if ($platforms === "both" || $platforms === "chatwoot") {
                //delete in chatwoot
                $success = delete_user($this->user_id);
                if (!$success) {
                    return false;
                }
            }

            if ($platforms === "both" || $platforms === "fusion") {
                //prepare the array
                $array['chatwoot_user'][0]['user_id'] = $this->user_id;

                //add the temporary permission object
                $p = new permissions;
                $p->add('chatwoot_user_delete', 'temp');

                //execute delete
                $database = new database;
                $database->app_name = $this->app_name;
                $database->app_uuid = $this->app_uuid;
                $success = $database->delete($array);
                $message = $database->message;
                unset($array);

                $p->delete('chatwoot_user_delete', 'temp');
            }

            return $success;
        }

        public static function get_user_list($account_id = NULL) {
            $sql = "SELECT \n";
            $sql .= "	user_id, \n";
            $sql .= "   user_uuid, \n";
            $sql .= "	account_id \n";
            $sql .= "FROM \n";
            $sql .= "	v_chatwoot_user \n";
            $sql .= "WHERE \n";
            $sql .= "	domain_uuid = :domain_uuid \n";
            if ($account_id)
            {
                $sql .= "AND \n";
                $sql .= "   account_id = :account_id \n";
                $parameters['account_id'] = $account_id;
            }

            $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
            $database = new database;
            $result = $database->select($sql, $parameters, 'all');

            return $result;
        }

        public static function get_user($user_id) {
            $sql = "SELECT \n";
            $sql .= "	user_id, \n";
            $sql .= "   user_uuid, \n";
            $sql .= "	account_id, \n";
            $sql .= "	domain_uuid, \n";
            $sql .= "	access_token, \n";
            $sql .= "	pubsub_token \n";
            $sql .= "FROM \n";
            $sql .= "	v_chatwoot_user \n";
            $sql .= "WHERE \n";
            $sql .= "	user_id = :user_id \n";

            $parameters['user_id'] = $user_id;
            $database = new database;
            $result = $database->select($sql, $parameters, 'row');

            return $result;
        }


    }
}


?>