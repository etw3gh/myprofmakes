<?php
    /**
     * Class TwitterURL
     *
     * Limited to frequently used endpoints and is by no means an exhaustive Twitter API library
     *
     * Class to produce parameterized urls for various Twitter API calls
     * Making the calls is delegated to the TwitterOAuth library
     * Parsing the calls is delegated to the application or an Extractor class (see MentionsTokenizer.php)
     *
     * Assumes use of TwitterOAuth or other library
     * Percent encoding is taken care of by TwitterOAuth (see TwitterIOBroker.php) or other library for now
     *
     * @var array urls      key => value pairs
     *                      endpoint => URL
     *
     * @var array family    key => value pairs
     *                      endpoint => array (method family)
     *
     * @todo incude optional function parameters for percent encoded urls
    */
    class TwitterURL
    {

        /**
         * @var array is an associative array with keys corresponding to the method names/API endpoints
         *      and values corresponding to the url for the API call
         */
        public $urls;

        /**
         * @var array is an associative array with keys corresponding to the method names/API endpoints
         *      and values corresponding to the family of resources valid for that endpoint
         */
        public $family;

        /**
         * @var various frequently used constants including single characters
         */
        public $C;

        #flag that when true turns ON printing to STDOUT and OFF to STDERR (and vice versa)
        public $VERBOSE;

        /**
         * Class constructor
         * Simply initializes $urls associative array
         *
         */
        public function __construct($verbose, $my_constants)
        {
            $this->C = $my_constants; #new Constants();

            require_once("Hashes_URL.php");
        }

        /**
         * Returns the url necessary to post a tweet
         * $the_tweet (status) is the only mandatory parameter
         *
         * Method will provide a parameterized url corresponding to the input
         * ie: https://api.twitter.com/1.1/statuses/update.json?status="this is the tweet"&in_reply_to_status_id="12234115484"
         *
         * Percent encoding is taken care of by TwitterOAuth
         *
         * @param $the_tweet string which is the text that is to be posted (AKA status)
         * @param $parameters array An associative array that contains parameters and their method names
         *
         * @throws InvalidParameterFormatException
         * @throws InvalidTextException
         * @throws NoValidParametersException
         *
         * @return string An ampersand separated parameterized url that can be posted to twitter with oauth library
         * @return string false will be returned if:@todo determine more invalid tweets
         * @todo include percent encode option or just do it automatically
         */
        public function post_tweet($the_tweet, $parameters)
        {
            $method_name = 'post_tweet';
            $status = "&status=";
            $empty = $this->C->EMPTY;
            $question_mark = $this->C->QUESTION;

            $url = $this->urls[$method_name];
            $family = $this->family[$method_name];

            #ampersand separated values
            $asv = null;

            if (is_null($the_tweet) || !is_string($the_tweet) || $the_tweet == $empty)
            {
                throw new InvalidTextException("Valid text not received by $method_name method");
            }

            if (is_null($parameters) || count($parameters) == 0) return $url . $the_tweet;

            if (!$this->is_associative_array($parameters))
            {
                throw new InvalidParameterFormatException("Parameters to $method_name method not sent as an associative array");
            }

            $asv = $this->generate_asv($parameters, $family);

            return $url . $question_mark . $asv . $status . urlencode($the_tweet);
        }


        /**
         * Returns the url necessary to search for a term with optional parameters
         *
         * @param $term string is the mandatory search term
         * @param $parameters is the optional parmeter list
         *
         * @throws InvalidParameterFormatException
         * @throws InvalidTextException
         * @throws NoValidParametersException
         *
         * @return string An ampersand separated parameterized url that can be used to search twitter
         * @return boolean false will be returned if:
         *                  only invalid parameters are sent in the non empty array $parameters
         *                  $the_tweet is null or not a string or an empty string,
         *                  or some error has occurred.
         *
         * @todo raise exceptions instead of returning false
         *
         */
        public function get_search($term, $parameters)
        {
            $method_name = 'get_search';
            $empty = $this->C->EMPTY;
            $url = $this->urls[$method_name];
            $family = $this->family[$method_name];
            #ampersand separated values
            $asv = null;

            if (is_null($term) || !is_string($term) || $term == $empty)
            {
                throw new InvalidTextException("Valid text not received by $method_name method");
            }

            if (is_null($parameters) || count($parameters) == 0) return $url . $term;

            if (!$this->is_associative_array($parameters))
            {
                throw new InvalidParameterFormatException("Parameters to $method_name method not sent as an associative array");
            }
            $asv = $this->generate_asv($parameters, $family);

            return $url . $term . $asv;
        }


        /**
         * Returns the url necessary to delete a tweet for a given tweet id ($id)
         * that was authored by the authenticated user
         *
         * Caller must test for array_key_exists('errors', $some_twitter_response_array)
         * in order to determine success or failure of the deletion
         *
         * @param $id 'id_str' of the tweet
         * @param bool $trim_user Default is true. Set to falso to obtain more info
         *
         * @return string The paramaterized url required to delete a tweet by id_str
         */
        public function post_delete_tweet($id, $trim_user=true)
        {
            $method_name = "post_delete_tweet";
            $format = '.json';
            $url = $this->urls[$method_name];

            #append trim user to the end of the url only if true
            if ($trim_user)
            {
                $format .= '&trim_user=' . var_export($trim_user);
            }

            return $url . $id . $format;
        }

        /**
         * Returns the url necessary to retrieve mentions by other users on twitter
         * Limited to just indicating the last id retrieved
         * Including no id retrieves all mentions
         *
         * @param string $since is the id string (id_str) of the last retrieved tweet
         *
         *
         *
         * @return string url or parameterized url depending on the input
         */
        public function get_mentions_timeline($since)
        {
            $method_name = "get_mentions_timeline";
            $url = $this->urls[$method_name];
            $empty = $this->C->EMPTY;
            $equal_sign = $this->C->EQUAL;
            $question_mark = $this->C->QUESTION;
            $method_string = $empty;

            #$option_string = '&count=200&include_rts=1';

            if (!is_null($since) && is_string($since) && $since != $empty)
            {
                $method_string .= $question_mark . "since_id" . $equal_sign . $since;
            }
            return $url . $method_string;
        }

        /**
         * Returns the url necessary to determine how many more api calls an authorized
         * application has left for any given method indicated by the caller
         *
         * Useful for twitter bots or bot engines
         *
         * Constructs parameterized rate_limit_status url
         *
         * Takes a string array of resources, and constructs a csv string
         * of only the valid parameters according to Twitter API 1.1.
         *
         * @param array $resources
         *
         * @throws NoValidParametersException
         *
         * @return string a parameterized url constructed from valid members of $resources
         * @return string containing url if no resources are specified either by sending null or an empty array
         *
         */
        public function get_rate_limit_status($resources)
        {
            $csv = null;

            #define case insensitive constants
            $resource_string = '?resources=';
            $method_name = 'get_rate_limit_status';

            $url = $this->urls[$method_name];
            $family = $this->family[$method_name];

            #if no parameters have been sent, then a the url alone will be returned
            #if called this naked url will return a map of all rate limited GET methods

            if (is_null($resources) || count($resources) == 0 )
            {
                return $url;
            }

            $csv = $this->generate_csv($resources, $family);

            if ($this->VERBOSE) print "CSV: $csv" . PHP_EOL;

            return  $url . $resource_string . $csv;
        }

        #########################################################################
        #                       helpers                                         #
        #########################################################################

        /**
         * Method to determine if an array is flat or an associative array
         * @param array $arr
         *
         * @todo test this method
         *
         * @return boolean true if $arr is an associative array or else returns false
         */
        public function is_associative_array($arr)
        {
            return array_keys($arr) !== range(0, count($arr) - 1);
        }

        /**
         * Generates an ampersand separated (asv) list of parameter value pairs
         * denoted by 'parameter=value' if and only if they are checked against
         * a reference list of valid parameters
         *
         * @param $check_this_array
         * @param $against_this_array
         *
         * @throws InvalidParameterFormatException
         * @throws NoValidParametersException
         *
         * @return ampersand separated string intended to be appended to an api url
         */
        public function generate_asv($check_this_array, $against_this_array)
        {
            $good_parameters_to_implode = array();
            $parameter_string = null;
            $equal_sign = $this->C->EQUAL;
            $ampersand = $this->C->AMPERSAND;

            # base cases
            # 1)
            if (is_null($check_this_array) || count($check_this_array) == 0)
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_asv method [null or zero count]");
            }

            # 2)
            if (!$this->is_associative_array($check_this_array))
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_asv method [not associative array]");
            }

            # 3)
            if (!is_array($against_this_array))
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_asv method [bad application hash])");
            }

            foreach ($check_this_array as $parameter => $value)
            {
                if (in_array($parameter, $against_this_array))
                {
                    #construct parameter string 'foo_parameter='foovalue'

                    $parameter_string = $parameter . $equal_sign . $value;

                    array_push($good_parameters_to_implode, $parameter_string);
                }
            }

            if (count($good_parameters_to_implode) == 0)
            {
                throw new NoValidParametersException("No valid parameters sent to generate_asv method");
            }

            return implode($ampersand, $good_parameters_to_implode);
        }


        /**
         * Generates a csv list of api methods if and only if they
         * are checked against a reference list
         *
         * Non parameterized ie: 'a,b,c,e' not 'a=6,b=6,'...
         *
         * @param $check_this_array
         * @param $against_this_array
         *
         * @throws InvalidParameterFormatException
         * @throws NoValidParametersException
         *
         * @return string in csv format to be appended to an api url
         */
        public function generate_csv($check_this_array, $against_this_array)
        {
            $good_parameters_to_implode = array();
            $comma = $this->C->COMMA;

            # base cases
            # 1)
            if (is_null($check_this_array) || count($check_this_array) == 0)
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_csv method [null or zero count]");
            }

            # 2)
            if (!is_string($check_this_array) && !is_array($check_this_array))
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_csv method [not associative array]");
            }

            # 3)
            if (!is_array($against_this_array))
            {
                throw new InvalidParameterFormatException("Invalid array sent to generate_csv method [bad application hash])");
            }

            #4) single string
            if (is_string($check_this_array) && in_array($check_this_array, $against_this_array))
            {
                return  strtolower($check_this_array);
            }

            foreach ($check_this_array as $parameter)
            {
                if (in_array($parameter, $against_this_array))
                {
                    array_push($good_parameters_to_implode, $parameter);
                }
            }

            if (count($good_parameters_to_implode) == 0)
            {
                throw new NoValidParametersException("No valid parameters sent to generate_asv method");
            }

            return implode($comma, $good_parameters_to_implode);
        }
    }


