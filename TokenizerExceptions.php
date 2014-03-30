<?php

    class TokenizerExceptions extends Exception
    {
        public function __construct($message, $code = 0, Exception $previous = null)
        {
            parent::__construct($message, $code, $previous);
        }

        protected function err($error_type)
        {
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()}".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }

    /**a
     * Class TokenizerException
     * general Tokenizer Exception
     */
    class TokenizerException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Tokenizer";
            return parent::err($error_type);
        }
    }


    class TweetTooShortException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Tweet Too Short";
            return parent::err($error_type);
        }
    }


    class EmptyTweetException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Empty Tweet";
            return parent::err($error_type);
        }
    }


    class LowRollerException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Low Roller";
            return parent::err($error_type);
        }
    }

    /**
     * Class TooManyResultsException
     * raises the case where a query returns more than
     * Constants::MAX_RESULTS_WARNING number of results
     *
     * User should notified of number of results and be
     * prompted to either refine the search or accept
     * all the resulting tweets to their account
     *
     */
    class TooManyResultsException extends TokenizerExceptions
    {
        private $number_of_results;

        public function __construct($message, $number_of_results)
        {
            parent::__construct($message);
            $this->number_of_results = $number_of_results;

        }

        public function err()
        {
            $error_type = "Too Many Results Returned";
            return parent::err($error_type);
        }

        public function get_number_of_results()
        {
            return $this->number_of_results;
        }
    }

    class NoValidNamesException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "No Valid Names Provided ";
            return parent::err($error_type);
        }
    }

    class NullCursorException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Mongo Query Has Null Cursor ";
            return parent::err($error_type);
        }
    }


    class TooManyTokensException extends TokenizerExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Too many tokens have been provided";
            return parent::err($error_type);
        }
    }