<?php

    /**
     * Class TweetGenerator
     *
     * A class to generate the text for tweets sent by a twitter bot
     *
     * No transactional logic provided here
     *
     */
    class TweetGenerator
    {
        public $usage;
        public $C;
        public $H;
        public $too_vague;
        public $dear;
        public $low_roller;
        public $S;

        public function __construct($my_hashes, $my_constants, $string_helper)
        {
            $this->usage =
                " correct usage: [at]myprofmakes university first last. You can abbreviate names and use uni acronyms";

            $this->too_vague = "[Too Vague] ";
            $this->dear = "Dear ";

            $this->C = $my_constants;
            $this->H = $my_hashes;
            $this->S = $string_helper;
        }

        public function generate_tweet($name, $salaries)
        {
            $space = $this->C->SPACE;
            $u_temp = $salaries['university'];
            $hash_tag = $this->H->uni_twitter["$u_temp"]['hashtags']['official'];

            $s = $salaries['salary'];
            if (strlen($s) == 6)
            {
                $last3  = substr($s,-3);
                $first3 = substr($s,0,3);
                $salary_2012 = "$$first3,$last3";
            }
            else
            {
                $salary_2012 = "\$error";
            }

            $response_start = "Dear @$name, ";

            $full_name = $this->S->capitalize_name($salaries["first_name"], $salaries["last_name"]);

            #essential part : "Joe Blow made $100,000 in 2012 (Sponsor: myprofmakes.ca)"
            $response_end = trim($full_name) . " made " . $salary_2012 . " in 2012 (Sponsor: myprofmakes.ca)";

            $response_tweet = "$response_start$response_end";

            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($response_tweet);

            if ($max_head_room > strlen($space . $hash_tag))
            {
                return $response_tweet . $space . $hash_tag;
            }
            else
            {
                return $response_tweet;
            }

        }

        public function generate_tweet_contains_url_tweet($name)
        {
            $usage = " your query has been rejected as possibly containing a URL";

            #$usage =" please tweet only university, first & last names";
            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($usage);

            $at = $this->C->ATSIGN;
            $comma = $this->C->COMMA;
            $dear = $this->dear;

            $prefix2 = $dear . $at . $name;
            $prefix = $prefix2 . $comma;

            if (($max_head_room) > strlen($prefix))
            {
                return $prefix . $usage;
            }
            elseif (($max_head_room) > strlen($prefix2))
            {
                return $prefix2 . $usage;
            }
            else
            {
                return $at . $name . ucfirst($usage);
            }
        }

        public function generate_toovague_tweet($name)
        {
            $usage = $this->usage;

            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($usage);

            $at = $this->C->ATSIGN;
            $comma = $this->C->COMMA;
            $dear = $this->dear;

            $vague = $this->too_vague;

            $prefix2 = $dear . $at . $name;
            $prefix = $vague . $prefix2 . $comma;

            if (($max_head_room) > strlen($prefix))
            {
                return $prefix . $usage;
            }
            elseif (($max_head_room) > strlen($prefix2))
            {
                return $prefix2 . $usage;
            }
            else
            {
                return $at . $name . $usage;
            }
        }
        public function generate_too_many_words_tweet($name)
        {
            $usage = " your query contains more than {$this->C->TOO_MANY_WORDS} words. Please forward any comments or complaints to @softcodedotca";

            #$usage =" please tweet only university, first & last names";
            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($usage);

            $at = $this->C->ATSIGN;
            $comma = $this->C->COMMA;
            $dear = $this->dear;



            $prefix2 = $dear . $at . $name;
            $prefix = $prefix2 . $comma;

            if (($max_head_room) > strlen($prefix))
            {
                return $prefix . $usage;
            }
            elseif (($max_head_room) > strlen($prefix2))
            {
                return $prefix2 . $usage;
            }
            else
            {
                return $at . $name . ucfirst($usage);
            }
        }


        public function generate_usage_tweet($name)
        {
            $usage = $this->usage;

            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($usage);

            $at = $this->C->ATSIGN;
            $comma = $this->C->COMMA;
            $dear = $this->dear;

            $prefix = $dear . $at . $name . $comma;

            if ($max_head_room > strlen($prefix))
            {
                return  $prefix . $usage;
            }
            else
            {
                return $at . $name . $usage;
            }
        }

        /**
         * Originally method to generate low roller tweets.
         *
         * Now just gives a temporary message for 0 result queries
         *
         * @param $name
         * @param $person
         * @return string
         *
         * @todo rename if no low roller tweets will be generated
         */
        public function generate_low_roller_tweet($name, $person)
        {
            #$low_roller = " is not on the high rollers list. #myprofmakes";

            $we_are_testing = "we are still testing this app. A human will double check your query. Please be patient. Meanwhile, try someone else.";

            $at = $this->C->ATSIGN;
            $comma = $this->C->COMMA;
            $space = $this->C->SPACE;
            $dear = $this->dear;

            $hash_tag = $space . $this->C->MYPROFMAKES;

            $hash_tag_length = strlen($hash_tag);

            $tweet = $dear . $at . $name . $comma . $space . $we_are_testing;

            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($tweet);

            if ($max_head_room >= ($hash_tag_length))
            {
                return $tweet . $hash_tag;
            }
            else
            {
                return $tweet;
            }
        }

        /**
         * method to generate the text for cases where a user's query has generated more than Constants::MAX_RESULTS_WARNING tweets.
         *
         * text gen only. handling the transaction is not provided here.
         *
         * @param $name
         * @param $number_of_results
         * @return string
         */
        public function generate_too_many_results_tweet($name, $number_of_results)
        {
            $at = $this->C->ATSIGN;
            $space = $this->C->SPACE;

            #$too_many_message = "Dear $at$name, we have $number_of_results tweets to send. Reply 'y' to receive them all or try a more specific query";

            $too_many_message = "[Query Too Broad] Dear $at$name, we found $number_of_results names matching your query, please retry with a first name";

            $hash_tag = $space . '#myprofmakes';
            $hash_tag_length = strlen($hash_tag);

            #calculate how much more character space is left for the remainder of the tweet
            $max_head_room = $this->C->MAX_TWEET_LENGTH - strlen($too_many_message);

            if ($max_head_room >= ($hash_tag_length))
            {
                return $too_many_message . $hash_tag;
            }
            else
            {
                return $too_many_message;
            }
        }

        public function tweet_jigger($t)
        {
                #refactor from above code once its working
        }

    }