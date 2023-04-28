<?php

require_once "resources/chatwoot_api.php";

if (!class_exists('chatwoot_account')) {
	class chatwoot_account {

        private $account_id;
        private $domain_uuid;
        private $app_name;
        private $app_uuid;

        public function __construct() {

            $this->domain_uuid = $_SESSION['domain_uuid'];
            $this->app_name = 'Chatwoot API';
            $this->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
        }

        /**
         * Creates chatwoot account
         * @param String $platforms Determines from where it creates: 'both', 'chatwoot' or 'fusion'
         * @param int $account_id Only needed if skipping chatwoot
         * @return chatwoot_account|bool Returns the object on successfull creation or false if encounters any errors
         */
        public static function create($platforms = 'both', $account_id = NULL) {

            if ($platforms === "both" || $platforms === "chatwoot") {
                //create in chatwoot
                $account_id = create_account($_SESSION['domain_name']);

                if ($account_id === false) {
                    return false;
                }
                if ($platforms === "chatwoot") {
                    $chatwoot_account = new static();
                    $chatwoot_account->set_account_id($account_id);
                    return $chatwoot_account;
                }
            }

            if ($platforms === "both" || $platforms === "fusion") {
                //prepare the array
                $array['chatwoot_account'][0]['account_id'] = $account_id;
                $array['chatwoot_account'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

                //add the temporary permission object
                $p = new permissions;
                $p->add('chatwoot_account_add', 'temp');

                //save the data
                $database = new database;
                $database->app_name = 'Chatwoot API';
                $database->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
                $success = $database->save($array);
                $message = $database->message;
                unset($array);

                $p->delete('chatwoot_account_add', 'temp');

                if ($success) {
                    $chatwoot_account = new static();
                    $chatwoot_account->set_account_id($account_id);
                    return $chatwoot_account;
                } else {
                    return false;
                }
            }
            return false;
        }

        /**
         * Deletes chatwoot account
         * @param String $platforms Determines from where it deletes: 'both', 'chatwoot' or 'fusion'
         * @return bool Returns true for successfull deletion or false if encounters any errors
         */
        public function delete($platforms = "both") {

            if ($platforms === "both" || $platforms === "chatwoot") {
                //delete in chatwoot
                $success = delete_account($this->account_id);
                if (!$success) {
                    return false;
                }
            }

            if ($platforms === "both" || $platforms === "fusion") {
                //prepare the array
                $array['chatwoot_account'][0]['account_id'] = $this->account_id;

                //add the temporary permission object
                $p = new permissions;
                $p->add('chatwoot_account_delete', 'temp');

                //execute delete
                $database = new database;
                $database->app_name = $this->app_name;
                $database->app_uuid = $this->app_uuid;
                $success = $database->delete($array);
                $message = $database->message;
                unset($array);

                $p->delete('chatwoot_account_delete', 'temp');
            }

            return $success;
        }

        protected function set_account_id($account_id){
            $this->account_id = $account_id;
        }

        public function get_account_id() {            
            return $this->account_id;
        }

        public static function get_domain_account() {
            $sql = "SELECT \n";
            $sql .= "	account_id \n";
            $sql .= "FROM \n";
            $sql .= "	v_chatwoot_account \n";
            $sql .= "WHERE \n";
            $sql .= "	domain_uuid = :domain_uuid";

            $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
            $database = new database;
            $account_id = $database->select($sql, $parameters, 'column');

            if ($account_id === false) {
                return false;
            }

            $chatwoot_account = new static();
            $chatwoot_account->set_account_id($account_id);
            return $chatwoot_account;
        }

    }
}


?>