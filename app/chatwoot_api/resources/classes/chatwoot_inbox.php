<?php

require_once "resources/chatwoot_api.php";

if (!class_exists('chatwoot_inbox')) {
	class chatwoot_inbox {

        private $inbox_id;
        private $account_id;
        private $domain_uuid;
        private $app_name;
        private $app_uuid;

        protected function __construct() {

            $this->account_id = $_SESSION['chatwoot']['account']['id'];
            $this->domain_uuid = $_SESSION['domain_uuid'];
            $this->app_name = 'Chatwoot API';
            $this->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
        }


        /**
         * Creates chatwoot account
         * @param int $account_id The account id where it will create an inbox
         * @param String $name Name of the new inbox
         * @param array $channel Attributes of the channel
         * @param String $platforms Determines from where it creates: 'both', 'chatwoot' or 'fusion'
         * @param int $inbox_id Only needed if skipping chatwoot
         * @return chatwoot_inbox|bool Returns the object on successfull creation or false if encounters any errors
         */
        public static function create($name, $channel, $platforms = 'both', $inbox_id = NULL) {
            if ($platforms === "both" || $platforms === "chatwoot") {
                //create in chatwoot
                $inbox = create_inbox($_SESSION['chatwoot']['account']['id'], $name, $channel);
                $inbox_id = $inbox->id;

                if ($inbox === false) {
                    return false;
                }
                if ($platforms === "chatwoot") {
                    $chatwoot_inbox = new static();
                    $chatwoot_inbox->set_inbox_id($inbox_id);
                    return $chatwoot_inbox;
                }
            }

            if ($platforms === "both" || $platforms === "fusion") {

                //prepare the array
                $array['chatwoot_inbox'][0]['inbox_id'] = $inbox_id;
                $array['chatwoot_inbox'][0]['account_id'] = $_SESSION['chatwoot']['account']['id'];
                $array['chatwoot_inbox'][0]['domain_uuid'] = $_SESSION['domain_uuid'];

                //add the temporary permission object
                $p = new permissions;
                $p->add('chatwoot_inbox_add', 'temp');

                //save the data
                $database = new database;
                $database->app_name = 'Chatwoot API';
                $database->app_uuid = 'ea5150fb-8722-45a7-bea7-361d4dd54092';
                $success = $database->save($array);
                $message = $database->message;
                unset($array);

                $p->delete('chatwoot_inbox_add', 'temp');

                if ($success) {
                    $chatwoot_inbox = new static();
                    $chatwoot_inbox->set_inbox_id($inbox_id);
                    return $chatwoot_inbox;
                } else {
                    return false;
                }
            }
            return false;
        }


        /**
         * Deletes chatwoot inbox
         * @param String $platforms Determines from where it deletes: 'both', 'chatwoot' or 'fusion'
         * @return bool Returns true for successfull deletion or false if encounters any errors
         */
        public function delete($platforms = "both") {

            if ($platforms === "both" || $platforms === "chatwoot") {
                //delete in chatwoot
                $success = delete_inbox($this->account_id, $this->inbox_id);
                if (!$success) {
                    return false;
                }
            }

            if ($platforms === "both" || $platforms === "fusion") {
                //prepare the array
                $array['chatwoot_inbox'][0]['inbox_id'] = $this->inbox_id;

                //add the temporary permission object
                $p = new permissions;
                $p->add('chatwoot_inbox_delete', 'temp');

                //execute delete
                $database = new database;
                $database->app_name = $this->app_name;
                $database->app_uuid = $this->app_uuid;
                $success = $database->delete($array);
                $message = $database->message;
                unset($array);

                $p->delete('chatwoot_inbox_delete', 'temp');
            }

            return $success;
        }

        public function get_inbox_id() {
            return $this->inbox_id;
        }

        protected function set_inbox_id($inbox_id) {
            $this->inbox_id = $inbox_id;
        }

        public static function get_all_inbox() {
            $sql = "SELECT \n";
            $sql .= "	inbox_id \n";
            $sql .= "FROM \n";
            $sql .= "	v_chatwoot_inbox \n";
            $sql .= "WHERE \n";
            $sql .= "	domain_uuid = :domain_uuid \n";
            $sql .= "AND \n";
            $sql .= "   account_id = :account_id";

            $parameters['domain_uuid'] = $_SESSION['domain_uuid'];
            $parameters['account_id'] = $_SESSION['chatwoot']['account']['id'];

            $database = new database;
            $result = $database->select($sql, $parameters, 'all');

            if ($result === false) {
                return false;
            }
            
            return $result;
        }

        public static function get_inbox($inbox_id) {
            $chatwoot_inbox = new static();
            $chatwoot_inbox->set_inbox_id($inbox_id);
            return $chatwoot_inbox;
        }

    }
}


?>