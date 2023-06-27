<?php

if (!class_exists('waba')) {
    class waba {
        private $id;
        private $domain_uuid;
        private $app_name;
        private $app_uuid;

        public function __construct($id) {

            $this->id = $id;
            $this->domain_uuid = $_SESSION['domain_uuid'];
            $this->app_name = 'Meta';
            $this->app_uuid = '78240306-98db-4445-a4b4-a6a22682ac58';
        }

        public function save() {

            //check if the waba already exists
            if (self::exist($this->id)) {
                return false;
            }

            //prepare the array
            $array['waba'][0]['waba_id'] = $this->id;
            $array['waba'][0]['domain_uuid'] = $this->domain_uuid;

            //add the temporary permission object
            $p = new permissions;
            $p->add('waba_add', 'temp');

            //save the data
            $database = new database;
            $database->app_name = $this->app_name;
            $database->app_uuid = $this->app_uuid;
            $success = $database->save($array);

            $p->delete('waba_add', 'temp');
            return $success;
        }

        public static function get_domain_wabas() {

            $sql = "SELECT
                        waba_uuid,
                        waba_id
                    FROM
                        v_waba
                    WHERE
                        domain_uuid = :domain_uuid";

            $parameters['domain_uuid'] = $_SESSION['domain_uuid'];

            $database = new database;
            $wabas = $database->select($sql, $parameters, 'all');
            return $wabas;
        }

        public static function exist($id) {                
            $sql = "SELECT
                        waba_id
                    FROM
                        v_waba
                    WHERE
                        waba_id = :waba_id
                    AND
                        domain_uuid = :domain_uuid";

            $parameters['waba_id'] = $id;
            $parameters['domain_uuid'] = $_SESSION['domain_uuid'];

            $database = new database;
            return $database->select($sql, $parameters, 'row');
        }

        public static function find_by_uuid($uuid) {
            $sql = "SELECT 
                        waba_id
                    FROM
                        v_waba
                    WHERE
                        waba_uuid = :waba_uuid";

            $parameters['waba_uuid'] = $uuid;

            $database = new database;
            return $database->select($sql, $parameters, 'column');
        }
    }
}