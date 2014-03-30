<?php

    class URLExceptions extends Exception
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

    /**
     * Class URLException
     * general exception
     */
    class URLException extends URLExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Invalid Parameters Sent";
            return parent::err($error_type);
        }
    }


    class InvalidTextException extends URLExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Invalid Tweet Sent";
            return parent::err($error_type);
        }
    }

    class InvalidParameterFormatException extends URLExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Invalid Parameters Sent";
            return parent::err($error_type);
        }
    }

    class NoValidParametersException extends URLExceptions
    {
        public function __construct($message)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Invalid Parameters Sent";
            return parent::err($error_type);
        }
    }
