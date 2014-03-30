<?php

    require_once("MongoSetup.php");

    class MongoThinWrapper
    {
        public $sunshine_db;
        public $myprof_db;
        public $last_search;
        public $last_mention;
        public $mp;
        public $ss;
        public $in;
        public $tw;
        public $out;


        public function __construct()
        {
            $mongo_setup = new MongoSetup();



            $this->sunshine_db = $mongo_setup->get_sunshine_db_handle();
            $this->myprof_db = $mongo_setup->get_myprof_db_handle();

            $this->ss = $mongo_setup->get_sslist_collection('sslist');

            $this->in = $mongo_setup->get_myprof_collection('incoming');
            $this->tw = $mongo_setup->get_myprof_collection('tweets');

            $this->last_search = $mongo_setup->get_myprof_collection('last_search_id');
            $this->last_mention = $mongo_setup->get_myprof_collection('last_mention_id');

            $this->ignore = $mongo_setup->get_myprof_collection('ignore');
            $this->out = $mongo_setup->get_myprof_collection('outgoing');
        }



        public function get_count_myprof($collection)
        {
            return $this->myprof_db->selectCollection($collection)->count();
        }

        public function get_count_sslist($collection)
        {
            return $this->sunshine_db->selectCollection($collection)->count();
        }

        /**
         * Performs multiple database writes for the caller
         * 1) writes to the incoming database
         *
         * if 1) is a success
         *  2) writes last mention id to capped collection
         *  3) raw dumps tweet into tweets collection
         *
         * @param $raw_tweet
         * @param $incoming
         * @param bool $dump_raw if set to true will write entire tweet a db
         *
         * @return array|bool
         */
        public function write_incoming($raw_tweet, $incoming, $dump_raw=false)
        {
            $in_return_value = null;
            try
            {
                $in_return_value = $this->in->insert($incoming);
            }
            catch (MongoCursorException $e)
            {
                #print "Do nothing on dupe key [write incoming]".PHP_EOL;
                return true;
            }


            if (!array_key_exists('error', $in_return_value))
            {
                $this->insert_last_cursored_id($incoming['id'], 'last_mention_id');

                if ($dump_raw) $this->tw->insert($raw_tweet);

                return true;
            }
            else return false;
        }

        /**
         * inserts a batch of salaries
         *
         * @param $outgoing array of arrays
         *
         * @return array|bool
         */
        public function write_outgoing($outgoing)
        {
            $out_return_value = null;

            try
            {
                $this->out->batchInsert($outgoing, array("continueOnError" => 1));
            }
            catch (MongoCursorException $e)
            {
                print "MongoCursorException: ";

                print $e->getMessage() . PHP_EOL;
            }

        }

        /**
         * get last id returned in twitter search
         *
         * retrieves one document from a capped collection containing only one document
         *
         * @return string id_str string corresponding to the last cursored tweet_id in an ongoing search request
         * @todo raise exceptions
         */
        public function get_last_search_id()
        {
            $last_search_record = $this->last_search->findOne();

            if (is_null($last_search_record))
                return null;
            else
                return $last_search_record['id_str'];
        }


        /**
         * get last id returned in twitter mentions
         *
         * retrieves one document from a capped collection containing only one document
         *
         * @return string id_str string corresponding to the last cursored tweet_id in an ongoing mentions request
         * @todo raise exceptions
         */
        public function get_last_mentions_id()
        {
            $last_mention_record = $this->last_mention->findOne();

            if (is_null($last_mention_record))
                return null;
            else
                return $last_mention_record['id_str'];
        }


        /**
         * inserts an id string into a capped collection containing only one document
         * @param $id_str string is the tweet id in string form
         * @param $collection string is the target collection
         * @return bool true or false depending on success
         * @todo raise exception for invalid collection
         */
        public function insert_last_cursored_id($id_str, $collection)
        {
            $last_search = 'last_search_id';
            $last_mention = 'last_mention_id';

            if ($collection == $last_search)
            {
                $return_value = $this->last_search->insert(array('id_str' => $id_str));
            }
            elseif ($collection == $last_mention)
            {
                $return_value = $this->last_mention->insert(array('id_str' => $id_str));
            }
            else
            {
                return false;
            }

            if ($return_value['ok'] == 1)
                return true;
            else return false;
        }


        /**
         * determines if a user is banned and should be ignored
         *
         * @param $screen_name string corresponding to the users twitter handle
         *
         * @return bool true or false according to the ban status
         *
         * @todo programmaticacly block the user
         */
        public function is_banned_user($screen_name)
        {
            $screen_name_regex = new MongoRegex("/^$screen_name/i");

            $check_user = array ("who" => $screen_name_regex);

            $user_check = $this->ignore->findOne( $check_user );

            if (!is_null($user_check))
            {
                #print PHP_EOL.PHP_EOL."from is_banned_user: $screen_name is on the ignore list".PHP_EOL;

                $check = array("who" => $screen_name_regex ,"ban" => 1);

                $check_banned = $this->ignore->findOne( $check );

                if (is_null($check_banned))
                {
                    return false;
                }
                else
                {
                    #print "from is_banned_user: $screen_name is banned\n\n".PHP_EOL;
                    return true;
                }
            }
            else
            {
                return false;
            }
        }


        /**
         * determines if a user has tweets that need to be ignored
         *
         * @param $screen_name string corresponding to the users twitter handle
         * @param $the_tweet string corresponding to the text of the tweet in question
         *
         * @return bool true or false if the tweet in question needs to be ignored
         *
         * */
        public function ignore_user_tweets($screen_name, $the_tweet)
        {
            $the_tweet = strtolower($the_tweet);
            $screen_name = strtolower($screen_name);

            $screen_name_regex = new MongoRegex("/^$screen_name/i");
            $tweet_regex = new MongoRegex("/^$the_tweet/i");

            $check_user = $this->ignore->findOne( array ("who" => $screen_name_regex));

            if (!is_null($check_user))
            {
                $check = $this->ignore->findOne(array("who" => $screen_name_regex , "what" => array('$in' => array($tweet_regex))));

                if (is_null($check))
                {
                    return false;
                }
                else
                {
                    #print PHP_EOL."User $screen_name's tweet has been ignored successfully!!!!!!!!!!!!!!!!!!!!!!!!!!!!!'".PHP_EOL;
                    return true;
                }
            }
            else
            {
                #print "from ignore_user_tweets User: $screen_name not in ignore list\n\n".PHP_EOL;

                return false;
            }
        }
    }

