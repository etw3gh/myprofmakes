<?php

    require_once("TokenizerExceptions.php");
    require_once("IOBrokerExceptions.php");
    require_once("TwitterURLExceptions.php");
    require_once("Constants.php");
    #app wide hashed arrays
    require_once("Hashes.php");
    #obtain var named $twitter which is authorized to use the Twitter API
    #DO NOT NAME THIS FILE 'OAUTH.PHP' in lower, upper, or any combination!
    require_once("Authorize.php");
    require_once("TwitterURL.php");
    require_once("MentionTokenizer.php");
    require_once("TweetGenerator.php");
    #includes MongoSetup.php
    require_once("MongoThinWrapper.php");

    require_once("StringHelpers.php");

    /**
     * Class TwitterIOBroker
     * Lean class: functionality added as required.
     * Broker for data exchanges between Twitter,
     * MongoDB and in memory Hashed Arrays
     * Results may be very specific or a large collection
     * of values depending on the method
     * @todo custom exception classes
     */
    class TwitterIOBroker
    {
        #TwitterURL.php
        public $api;
        #Authorize.php
        public $auth;
        public $twitter;
        #flag that turns on and off printing to stdout
        public $VERBOSE;
        public $C;
        public $H;
        public $S;
        public $mongo_instance;
        public $toker;
        public $gen;

        public function __construct($verbose)
        {
            #set to false for silence from this class, errors will go to error_log not stdout
            #no testing print statements will be executed
            $this->VERBOSE = $verbose;

            $tokenizer_verbosity = true;

            $this->C = new Constants();
            $this->H = new Hashes();
            $this->S = new StringHelpers($this->C);

            $this->mongo_instance = new MongoThinWrapper();

            try
            {
                $this->auth = new Authorize();
                $this->twitter = $this->auth->get_my_twitter_oauth();
            }
            catch (NewAuthorizationException $e)
            {
                if ($verbose) print $e->err("Fatal error occurred (from: TwitterIOBroker constructor) in obtaining authorization.");
                exit (1);
            }
            catch (Exception $e)
            {
                if ($verbose) print $e->getMessage();
            }

            $this->api = new TwitterURL($verbose, $this->C);
            $this->toker = new MentionTokenizer($this->twitter, $this->mongo_instance, $this->H, $this->C, $this->S, $tokenizer_verbosity);
            $this->gen = new TweetGenerator($this->H, $this->C, $this->S);
        }

        /**
         * Sends a tweet
         *
         * Obtains a valid parameterized url from the TwitterURL class
         * Posts the url via TwitterOAuth
         *
         * Tweeting is also known as status updating
         *
         * @param string $the_tweet
         * @param array $parameters an associative array of parameters
         * @param bool $return_tweet_url will cause the method to return
         *             the tweet if set to true (default value)
         *
         * @throws IOBrokerTwitterError
         *
         * @return bool true or valid twitter response (no error)
         */
        public function tweet($the_tweet, $parameters, $return_tweet_url=true)
        {
            $max =  $this->C->MAX_TWEET_LENGTH;

            #ensure only 140 characters
            $the_tweet = substr($the_tweet, 0, $max);

            $url = $this->api->post_tweet($the_tweet, $parameters);
            $response = $this->twitter->post($url);

            if (isset($response->errors))
            {
                print "Response Type is: " . gettype($response) . PHP_EOL;

                print_r($response);

                $error_code = $response->errors[0]->code;

                throw new IOBrokerTwitterError("CODE = $error_code", $error_code);
            }

            #either return true upon success or the twitter response set to true in method signature by default
            if ($return_tweet_url)
            {
                return $response;
            }
            else
            {
                return true;
            }
        }


        /**
         * batch posting of tweets from the outgoing db
         *
         * Selects all outgoing db documents with 'sent' flag set to false
         * and posts the tweet. 'sent' flag is then set to true.
         *
         * (the archive is available via the twitter api so no need to store it)
         *
         * @todo check twitter archive against posted tweets
         * @todo set flag to failed posts or write _id to a failed db
         *
         * @return bool true or exception will be raised from other methods called and no value will be returned
         * @result sent bool flag in outgoing db set to true for each post
         */
        public function post_all_outgoing()
        {
            $duplicate_tweet = $this->C->TWITTER_ERROR_DUPLICATE_TWEET;

            $outbox = $this->mongo_instance->out->find(array('sent' => 0));

            foreach ($outbox as $o)
            {
                #must be set to true for remainder of logic to work as is
                #otherwise a boolean is returned
                $return_url = true;


                print "TWEET: " . $o['tweet'] . PHP_EOL;
                $tweet_success = '';
                try
                {
                    $tweet_success = $this->tweet($o['tweet'], array('in_reply_to_status_id' => $o['id']), $return_url );
                }

                catch (IOBrokerTwitterError $e)
                {
                    $in_reply_to = $tweet_success->in_reply_to_status_id_str;
                    $this->mongo_instance->out->update(array('id' => $in_reply_to),
                        array('$set' => array('sent' => 1)));

                    print "$in_reply_to updated\n";
                }




                if (isset($tweet_success->in_reply_to_status_id_str))
                {
                    $in_reply_to = $tweet_success->in_reply_to_status_id_str;

                    print "\n\nIn reply to $in_reply_to\n\n";


                    if ($this->VERBOSE) print "TESTING: is array and no errors found";

                    try
                    {
                        $this->mongo_instance->out->update(array('id' => $in_reply_to),
                                                       array('$set' => array('sent' => 1)));
                        print "$in_reply_to updated\n";
                    }


                    catch (Exception $e)
                    {
                        print "Error is setting outgoing to sent:" . $e->getMessage();
                    }
                }

                elseif (isset($tweet_success->errors))
                {
                    if ($this->VERBOSE) print "Duplicate error detected from post_all_outgoing method".PHP_EOL;
                    #no exception will be raised for this event
                }

                else
                {
                    if (is_array($tweet_success) && !$tweet_success['errors']['code'] == $duplicate_tweet)
                    {
                        if ($this->VERBOSE) print "non Duplicate error detected from post_all_outgoing method".PHP_EOL;
                    }
                    #no exception is raised for this event
                }
            }
            return true;
        }

        /**
         * Conducts a twitter search
         */
         public function search($term, $parameters, $return_tweet_url=true)
         {
             $url = $this->api->get_search($term, $parameters);

             if (!$url) return false;

             if ($this->VERBOSE)
             {
                 print "URL:    ";
                 print $url."".PHP_EOL;
             }

             $response = $this->twitter->get($url);

             if (isset($response->errors))
             {
                 #TODO raise exception here
                 if ($this->VERBOSE)
                 {
                     print "ERRORS found in return [search method]".PHP_EOL;
                 }
                 return false;
             }
             else
             {
                 if ($return_tweet_url)
                 {
                     return $response;
                 }
                 else
                 {
                     return true;
                 }
             }
         }

        /**
         * Gets a collection of mentions since the specified id $since
         *
         * @todo goto database or API to obtain $since
         * @param $since
         * @throws IOBrokerExceptions
         * @return array of json objects each of which correspond to a mention of the authorized user
         */
        public function mentions($since)
        {
            $url = $this->api->get_mentions_timeline($since);

            if ($this->VERBOSE) print "$url ".PHP_EOL;

            $mentions = $this->twitter->get($url);

            if (!isset($mentions->errors))
            {
                return $mentions;
            }
            else
            {
                $error_code = $mentions->errors->code;
                throw new IOBrokerTwitterError('Raised from get_mentions_timeline method', $error_code);
            }
        }


        /**
         * Returns how many more times the authenticated user may retrieve
         * a list of their mentions. Resets every 15 minutes.
         *
         * Can be used to set sleep time for authenticated user's twitter bot
         *
         * @todo determine if this function should be generalized or just copied per method required
         * @todo return more meaningful errors or raise exceptions properly
         *
         * @throws IOBrokerTwitterError
         * @return integer corresponding to the number of calls a user may make to check their mentions
         * @return int false if one of many errors has occurred
         */
        public function remaining_mentions()
        {
            $r = "resources";
            $s = "statuses";
            $mt = "mentions_timeline";
            $remaining = "remaining";
            $query = '/'.$s.'/'.$mt;

            $response = null;

            $url = $this->api->get_rate_limit_status(array($s));

            if (!$url) return false;

            $response = $this->twitter->get($url);

            if (isset($response->errors))
            {
                $error_code = $response->errors->code;
                throw new IOBrokerTwitterError('error getting number of remaining calls to get_status_mentions', $error_code);
            }
            else
            {
                if (!is_null($response)) $calls_remaining = $response->{$r}->{$s}->{$query}->{$remaining};

                return $calls_remaining;
            }
        }


        /**
         * Returns how many more times the authenticated user may search
         * twitter for any given term. Resets every 15 minutes.
         *
         * Can be used to set sleep time for authenticated user's twitter bot
         *
         * @todo return more meaningful errors or raise exceptions properly
         *
         * @return integer corresponding to the number of searches a user may make
         * @return bool false if one of many errors has occurred
         */
        public function remaining_searches()
        {
            $r = "resources";
            $s = "search";
            $st = "tweets";
            $remaining = "remaining";
            $query = '/'.$s.'/'.$st;

            $response = null;

            $url = $this->api->get_rate_limit_status(array($s));

            if (!$url) return false;

            $response = $this->twitter->get($url);

            if (isset($response->errors)){
                return false;
            }
            else
            {
                #return an integer corresponding to the number of calls left
                $calls_remaining = $response->{$r}->{$s}->{$query}->{$remaining};
                return $calls_remaining;
            }
        }
    }