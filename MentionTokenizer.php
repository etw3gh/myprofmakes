<?php

        /**
         * Class MentionTokenizer
         *
         * Class that tokenizes a tweet, categorizes the tokens and
         * performs next best queries based on category
		 * 
		 * @todo decouple the functions so the returns are not daisy chained
		 *       so as to allow for unit testing
         * @todo deal with unicode characters
         *
         */
        class MentionTokenizer
            {
        public $H; #hashes
        public $C; #Constants
        public $S; #StringHelper Functions

        #oauth twitter client
        public $twitter;

        #database connections     
        public $ssdb;
        public $mpdb;

        public $TESTING;

        public function __construct($twitter_auth, $mongo_thin, $my_hashes, $my_constants, $string_helper, $testing=true)
        {
            $this->twitter = $twitter_auth;
            $this->H = $my_hashes;
            $this->C = $my_constants;
            $this->S = $string_helper;
            $this->ssdb = $mongo_thin->ss;
            $this->mpdb = $mongo_thin->myprof_db;
            $this->TESTING = $testing;
        }


            /**
             * function that takes a string representing a twitter mention
             * words are tokenized and matched against in memory hashed arrays
             *
             * once categorized, words are then pushed onto a stack and passed to
             * the resolve_names function
             *
             * @param $incoming_tweet
             * @throws EmptyTweetException
             * @throws TweetTooShortException
             * @throws TooManyTokensException
             * @return array
             */
        public function tokenize($incoming_tweet)
        {
            #remove extra spaces and undesirable characters from the tweet
            $t_stripped = $this->S->strip_undesirables($incoming_tweet);

			$delimiter = $this->C->SPACE;

			#stray punctuation is ignored. single characters are counted as words
			$words_in_tweet = str_word_count($t_stripped);	

            #short circuit on tweet containing a single word
            if ($words_in_tweet == 1)
            {
                throw new TweetTooShortException("Single Word Received: '$t_stripped''");
            }
            if (strlen($t_stripped) == 0)
            {
                throw new EmptyTweetException("Mention Tokenizer method has received an empty tweet");
            }
            if ($words_in_tweet > $this->C->TOO_MANY_WORDS)
            {
                throw new TooManyTokensException("Tweet rejected for having more than {$this->C->TOO_MANY_WORDS} words.");
            }

            #convert to lowercase
            $t_stripped = strtolower($t_stripped);

            $at_sign = $this->C->ATSIGN;
            $hash_sign = $this->C->HASH;
            $testing = $this->TESTING;
            $university_shortcuts_hash = $this->H->uni_short;

            $ignore_words = $this->H->ignore;

            $other_mentions = array();
            $university_names = array();

            $hopefully_names = array();

            $hash_tags = array();
            $university_names_from_hash_tags = array();

            $last_name = null;
            $first_name = null;
            $university = null;            
             
            $tok = strtok($t_stripped, $delimiter);  //return false on empty string

            #this should be taken care of by short circuit at the start of the method but just in case
            if (!$tok)
            { 
                throw new EmptyTweetException("Mention Tokenizer method tokenize has received an empty tweet");
            }

            if ($testing) print PHP_EOL.PHP_EOL."[original]: $t_stripped".PHP_EOL.PHP_EOL;


            while (!($tok === FALSE))
            {

                #consume tokens that should be ignored
                foreach($ignore_words as $ignore_this_word)
                {

                    if (preg_match("/^$tok/i", $ignore_this_word))
                    {
                        #consume next token for upcoming iteration
                        $tok = strtok($delimiter);

                        if ($testing) print "Ignored token: $tok" . PHP_EOL;

                        continue 2;
                    }
                }

                #test first character against the atsign
                if ($tok[0] == $at_sign)
                {

                    if ($testing) print PHP_EOL."@sign detected :: ";
                    #test for hashtag @myprofmakes
                    if ($tok == $this->C->MYPROFMAKES)
                    {
                        if ($testing) print "@myprofmakes handle: $tok".PHP_EOL;
                        #do nothing if mentioned
                    }
                    else
                    {
                        #TODO FIND A USE FOR THIS ARRAY OR DELETE IT
                        array_push($other_mentions, $tok);
                        if ($testing) print "$tok handle".PHP_EOL;
                    }
                }


                #test for university short cuts
                #todo: search sunshine list for conflicts between short cuts and names
                elseif (array_key_exists($tok, $university_shortcuts_hash))
                {
                    #return the full name corresponding to the shortcut 
                    #from the uni_short dictionary hash

                    #ONLY PUSH IF UNIQUE
                    $check_uni = $university_shortcuts_hash[$tok];

                    if ($testing) print PHP_EOL."\$uni_short check: $check_uni".PHP_EOL;

                    if (!in_array($check_uni, $university_names))
                    {
                        array_push($university_names, $check_uni);

                        if ($testing)
                        {
                            print PHP_EOL."Printing array \$university_names::".PHP_EOL;
                            print_r($university_names);
                        }
                    }
                }

                #test for hash tags matching university short cuts
                elseif ($tok[0] == $hash_sign)
                {
                    #push onto hash tag stack
                    array_push($hash_tags, $tok); #not used determine if there is a use for it

                    print "Tok: $tok\n";

                    $chomp_hash_tag = substr($tok, 1);



                    if ($testing)
                    {
                        print PHP_EOL."#sign detected :: $tok and extracted to $chomp_hash_tag........";
                        print PHP_EOL."Printing array \$hash_tags::".PHP_EOL;
                        print_r($hash_tags);
                    }

                    #compare hash tag minus pound sign (#) to university shortcuts
                    if (array_key_exists($chomp_hash_tag, $university_shortcuts_hash))
                    {
                        $check_uni = $university_shortcuts_hash[$chomp_hash_tag];

                        if (!in_array($check_uni, $university_names_from_hash_tags))
                        {
                            array_push($university_names_from_hash_tags, $check_uni);
                            if ($testing)
                            {
                                print PHP_EOL."Printing array \$university_names::".PHP_EOL;
                                print_r($university_names);
                            }
                        }
                    }
                }

                #anything else is hopefully a name so push onto the $hopefully_names stack
                else
                {
                    if ($testing)
                    {
                        print PHP_EOL."Found a name (hopefully): $tok".PHP_EOL;
                    }
                    array_push($hopefully_names, $tok);
                }

                #consume next token for upcoming iteration
                $tok = strtok($delimiter);
            }



            #set first occurring university name to the uni and put all others in other_universities
            if (count($university_names) == 1 )
            {
                $university = $university_names[0];
            }
            elseif (count($university_names) == 0 )
            {
                #if no shortcuts were detected, fall back to shortcuts obtained from hash tags
                if (count($university_names_from_hash_tags) == 0)
                {
                    $university = null;
                }
                else
                {
                    #take the first one
                    #todo determine best one
                    $university = $university_names_from_hash_tags[0];
                }
            }            
            else
            {
                $university = $university_names[0];
                unset($university_names[0]);
            }

            #TODO pass on hashtag and other university data
            return $this->resolve_names($hopefully_names, $university);
        }


        /**
         * function that takes at minimum a last name and possible a first name and university name as well
         * performs queries, assesses success and then either broadens or narrows the queries
         *
         * @todo swap first and last names upon zero result
         *
         * @todo establish alternative first name db: "The Joseph 'Carl' Kumarades case"
         * @todo figure out the proper way to do this time permitting
         *
         * @param $names_array
         * @param $university
         * @return array
         * @throws NoValidNamesException
         */
        public function resolve_names($names_array, $university)
        {
            #short circuit invalid name array length or type
            if  (is_null($names_array) || !is_array($names_array) || count($names_array) == 0)
            {
                print_r($university);
                print_r($names_array);
                throw new NoValidNamesException("No Valid Names Provided");
            }

            $testing = $this->TESTING;
            $uni_regex = null;
            $name_query = null;
            $nq_count = null;
            $number_of_names = count($names_array);


            print "#names: $number_of_names\n";


            #will be set to true if $university name is not null and is a string
            $university_provided = !is_null($university) && is_string($university);

            if ($university_provided)
            {
                $uni_truncated = substr($university, 0, strlen($university) -4);
                $uni_regex = new MongoRegex("/$uni_truncated/i");

                if ($testing) print "University (resolve_names): $university and truncated: $uni_truncated".PHP_EOL;
            }

            /*
             * if the size is one and university is null then we check on last name only
             */
            if ($number_of_names == 1 && !$university_provided)
            {
                $last_regex = new MongoRegex("/^$names_array[0]/i");
                $name_query = $this->ssdb->find(array("last_name" => $last_regex));
            }

            /*
             * if the size is one and university is not null then check on both
             */
            elseif ($number_of_names == 1 && $university_provided)
            {
                $last_regex = new MongoRegex("/^$names_array[0]/i");
                $name_query = $this->ssdb->find(array("university" => $uni_regex,"last_name" => $last_regex));
            }

            /*
             * both names have been provided and possibly the university name
             */
            else
            {
                $hopefully_first = $names_array[0];
                $hopefully_last  = $names_array[1];

                if ($testing) print "Name: $hopefully_first $hopefully_last".PHP_EOL;

                /*
                 * if only a single initial is provided, assume it is the first letter of first name
                 * otherwise look for the pattern anywhere in the first name
                 */
                if (strlen($hopefully_first) == 1)
                {

                    print "Trigger First: $hopefully_first\n";

                    $first_regex = new MongoRegex("/^$hopefully_first./i");
                }
                else
                {
                    $first_regex = new MongoRegex("/$hopefully_first/i");
                }

                /*
                 * assume the last name provided starts with the characters given
                 */
                $last_regex = new MongoRegex("/^$hopefully_last/i");

                if ($university_provided)
                {
                    if ($testing) print ">>>>>>>>>>>>>>>>>>>>uni: $university".PHP_EOL;

                    #perform query on all three parameters
                    $name_query = $this->ssdb->find(array("university" => $uni_regex,"first_name" => $first_regex ,"last_name" => $last_regex));

                    $nq_count = $name_query->count();

                    if ($testing) print "first query results: $nq_count".PHP_EOL;


                    $first_name_truncation = 2;

                    /*
                     * if no results are returned, the query will be broadened
                     */
                    if ($nq_count == 0)
                    {
                        #take first {$first_name_truncation} characters of the first name (this covers dave = david) and mike = micheal etc
                        $hopefully_first = substr($hopefully_first, 0, $first_name_truncation);

                        $first_regex = new MongoRegex("/^$hopefully_first./i");

                        $name_query = $this->ssdb->find(array("university" => $uni_regex,"first_name" => $first_regex ,"last_name" => $last_regex));
                        $nq_count = $name_query->count();

                        if ($testing) print "second query results: $nq_count".PHP_EOL;
                        /*
                         * still no results, drop the first name
                         */
                        if ($nq_count == 0)
                        {
                            $name_query = $this->ssdb->find(array("university" => $uni_regex,"last_name" => $last_regex));
                            $nq_count = $name_query->count();
                            if ($testing) print "third query results: $nq_count".PHP_EOL;



                            if ($nq_count !=1)
                            {
                                /*
                                 * still no results drop the university
                                 */
                                $name_query = $this->ssdb->find(array("last_name" => $last_regex));
                                $nq_count = $name_query->count();
                                if ($testing) print "fourth query results: $nq_count".PHP_EOL;
                            }
                        }
                        /*
                         * too many results add length to the truncation
                         */
                        elseif ($nq_count > 3)
                        {
                            $first_name_truncation += 1;
                            $hopefully_first = substr($hopefully_first, 0, $first_name_truncation);
                            $first_regex = new MongoRegex("/^$hopefully_first./i");

                            $name_query2 = $this->ssdb->find(array("university" => $uni_regex,"first_name" => $first_regex ,"last_name" => $last_regex));
                            $nq_count2 = $name_query2->count();
                            if ($testing) print "fifth query results: $nq_count2".PHP_EOL;
                            if ($nq_count2 < $nq_count && $nq_count2 > 0)
                            {
                                $name_query = $name_query2;
                            }
                            elseif ($nq_count2 > 3)
                            {
                                $first_name_truncation += 1;
                                $hopefully_first = substr($hopefully_first, 0, $first_name_truncation);
                                $first_regex = new MongoRegex("/^$hopefully_first./i");

                                $name_query3 = $this->ssdb->find(array("university" => $uni_regex,"first_name" => $first_regex ,"last_name" => $last_regex));
                                $nq_count3 = $name_query3->count();
                                if ($testing) print "sixth query results: $nq_count3".PHP_EOL;
                                if ($nq_count3 < $nq_count && $nq_count3 > 0)
                                {
                                    $name_query = $name_query3;
                                }

                            }
                        }
                    }
                }
                else
                {
                    /*
                     *  only 2 names are provided
                     */
                    $name_query = $this->ssdb->find(array("first_name" => $first_regex ,"last_name" => $last_regex));
                    $nq_count = $name_query->count();
                    if ($testing) print "7th query results: $nq_count".PHP_EOL;


                    if ($nq_count == 0)
                    {
                        $name_query = $this->ssdb->find(array("last_name" => $last_regex));
                        $nq_count = $name_query->count();
                        if ($testing) print "8th query results: $nq_count".PHP_EOL;
                    }
                }

            }
            return $this->dump_results($name_query);
        }


        /**
         * function that processes MongoCuror object (database query result)
         * exceptions are thrown as is necessary and valid data is pushed
         * onto a return stack for processing by the calling application
         *
         * @param $cursor
         * @return array
         * @throws TooManyResultsException
         * @throws NullCursorException
         * @throws LowRollerException
         */
        public function dump_results($cursor)
        {
            if (is_null($cursor))
            {
                throw new NullCursorException("Tokenizer Query Returned a Null Cursor");
            }

            $cursor_size = $cursor->count();

            if ($cursor_size > $this->C->MAX_RESULTS_WARNING)
            {
                throw new TooManyResultsException("User to be prompted via tweet that there are $cursor_size results\n", $cursor_size);
            }

            $testing = $this->TESTING;
            $result_array = array();

            #iterate over mongo cursor
            foreach($cursor as $result)
            {
                array_push($result_array, array('university' => $result['university'],
                                                'first_name' => $result['first_name'],
                                                'last_name'  => $result['last_name'],
                                                'title'      => $result['title'],
                                                'salary'     => $result['salary']

                ));

                if ($testing) print "Tokenizer Testing: {$result['university']}, {$result['first_name']} {$result['last_name']} ".PHP_EOL;
            }

            if (count($result_array) == 0)
                throw new LowRollerException("This person is not a high roller");
            else
                return $result_array;
        }

    }
