<?php

    #available at https://github.com/abraham/twitteroauth
    #unzip at root of project or adjust path accordingly
    require_once('twitteroauth-master/twitteroauth/twitteroauth.php');

    #WARNING: Its a good idea to read the above classes in order not to duplicate class names

    class Authorize
    {
        private $twitter;
        private $verbose;
        public function __construct($verbose=true)
        {
            $this->verbose = $verbose;

            #obtain these 4 from dev.twitter.com
            $consumer_key = ''; 
            $consumer_secret = ''; 
            $access_token = ''; 
            $access_token_secret = '';

            try
            {
                $this->twitter = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
            }
            catch (OAuthException $e)
            {
                #TODO globalize verbose flag
                if ($this->verbose) print $e->getMessage();
                throw new NewAuthorizationException("Error in obtaining a new authorization. Check api keys.");
            }
            catch (Exception $e)
            {
                #TODO globalize verbose flag
                if ($this->verbose) print $e->getMessage();
                throw new NewAuthorizationException("Generic error caught in obtaining a new authorization. Check api keys.");
            }
        }

        public function get_my_twitter_oauth()
        {
            return $this->twitter;
        }
    }

    class NewAuthorizationException extends Exception
    {
        public function __construct($message, $code=0, $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }
        public function err($error_type)
        {
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()} ".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }

    class OAuthTransactionException extends Exception
    {
        public function __construct($message, $code=0, $previous = null)
        {
            parent::__construct($message, $code, $previous);
            }
        public function err($error_type)
        {
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()} ".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }
