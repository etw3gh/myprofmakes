<?php

    class MongoSetup
    {
        private $mongo;
        public $sunshinelist;
        public $myprof;

        public function __construct()
        {
            $this->sunshine_list = 'sunshinelist';
            $this->myprof = 'myprof';
            $this->mongo = new MongoClient();

            #collections that hold a single record:
            #ideal for cursored requests from twitter delineated by some id value
            #only stores the last inserted value thus the latest ID is easily obtained
            #myprof collections last_search_id & last_mentions id must be created as such:

                #from the mongo shell:
                #db.createCollection("last_search_id", { capped: true, size: 1024, max: 1})
                #db.createCollection("last_mention_id", { capped: true, size: 1024, max: 1})

            #see truncation script: truncate_myprof.js
            #run:
            #   mongo < truncate_myprof.js
            #
            #Make sure before:
            #   mongod --setParameter enableTestCommands=1

            ####################################################
            #To truncate a capped collection (for fresh start):
            #
            #Exit both mongo shell (if running) & mongod with control-c
            #
            #run mongod as such:
            #
            #   mongod --setParameter enableTestCommands=1
            #
            #
            #run command to truncate:
            #
            #   use myprof
            #   db.runCommand({emptycapped: "last_search_id"})
            #
            #Exit both mongo & mongod with control-c
            #Restart mongod without any parameters
            #Restart mongo shell (if required)
            ####################################################


            #setup indexes
            #index salary in descending order
            $this->get_sslist_collection("sslist")->ensureIndex(array("salary" => -1), array("name" => "salary"));
            #index last names in ascending (alphabetical) order
            $this->get_sslist_collection("sslist")->ensureIndex(array("last_name" => 1), array("name" => "last_name"));

            #ensure index on university name
            #$this->get_sslist_collection("sslist")->ensureIndex(array("university" => 1), array("name" => "university"));

            #index raw tweets by tweet id descending order and ensure no duplicates
            #$this->get_myprof_collection("tweets")->ensureIndex(array("id" => -1), array("unique" => true, "name" => "id"));
            $this->get_myprof_collection("tweets")->ensureIndex(array("id_str" => -1), array("name" => "id_str"));
            $this->get_myprof_collection("incoming")->ensureIndex(array("id" => -1), array("unique" => true, "name" => "id"));

        }

        public function get_sunshine_db_handle()
        {
            return $this->mongo->selectDB($this->sunshine_list);
        }

        public function get_sslist_collection($collection)
        {
            return $this->get_sunshine_db_handle()->selectCollection($collection);
        }

        public function get_myprof_db_handle()
        {
            return $this->mongo->selectDB($this->myprof);
        }

        public function get_myprof_collection($collection)
        {
            return $this->get_myprof_db_handle()->selectCollection($collection);
        }
    }

