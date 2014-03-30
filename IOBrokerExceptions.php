<?php

    class IOBrokerExceptions extends Exception
    {
        public function __construct($message, $code = 0, $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }

        protected function err($error_type)
        {
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()} ".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }


    class IOBrokerTwitterError extends IOBrokerExceptions
    {
        private $twitter_error_code;
        public function __construct($message, $twitter_error_code=null)
        {
            $this->twitter_error_code = $twitter_error_code;
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Twitter Returned an Error";

            return parent::err($error_type);
        }

        public function get_twitter_error_code()
        {
            return $this->twitter_error_code;
        }
    }