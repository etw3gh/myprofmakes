<?php

    class Hashes
    {
        #from Hashes_Uni.php
        public $response_short;
        public $uni_short;
        public $affiliates;
        public $ignore;

        #from Hashes_Twitter.php
        public $uni_twitter;

        public function __construct()
        {
            #$this-> declarations found in below files for above variables
            #(long files)
            require_once("Hashes_Uni.php");
            require_once("Hashes_Twitter.php");
            require_once("Hashes_Tokenizer_Ignore.php");
        }
    }