<?php

    class Constants
    {
        #single character constants
        public $AMPERSAND;
        public $ATSIGN;
        public $BANG;
        public $COMMA;
        public $EMPTY;
        public $EQUAL;
        public $POUND;
        public $HASH;
        public $SPACE;
        public $QUESTION;
        public $SINGLE_Q;
        public $DOUBLE_Q;
        public $DASH;
        public $LEFT_PAR;
        public $RIGHT_PAR;

        public $MAX_TWEET_LENGTH;
        public $INCOMING_ID_LENGTH;
        public $USER_ID_LENGTH;
        public $VAGUE_MIN;
		public $SIX_FIGURE_SALARY;
        public $MYPROFMAKES;
        public $SLEEP_TIME;
        public $MAX_RESULTS_WARNING;
        public $TOO_MANY_WORDS;

        public $TWITTER_ERROR_BAD_AUTH; #YOUR CALL COULD NOT BE COMPLETED AS DIALED??? lol
        public $TWITTER_ERROR_SUSPENDED;
        public $TWITTER_ERROR_NO_PAGE;
        public $TWITTER_ERROR_LIMIT_EXCEEDED;
        public $TWITTER_ERROR_INVALID_TOKEN;
        public $TWITTER_ERROR_THE_WHALE; #OVER CAPACITY
        public $TWITTER_ERROR_INTERNAL;
        public $TWITTER_ERROR_BAD_AUTH_HTTP_401;
        public $TWITTER_ERROR_NOT_AUTHORIZED;
        public $TWITTER_ERROR_UNABLE_TO_FOLLOW;
        public $TWITTER_ERROR_OVER_DAILY_LIMIT;
        public $TWITTER_ERROR_DUPLICATE_TWEET;
        public $TWITTER_ERROR_BAD_ENDPOINT;

        public $twitter_error_hash;


        public function __construct($twitter_handle = "@myprofmakes")
        {
            $this->MAX_TWEET_LENGTH = 140;

            #probably a bad idea to count on these remaining the same forever
            $this->INCOMING_ID_LENGTH = 18;
            $this->USER_ID_LENGTH = 10;

            $this->AMPERSAND = chr(hexdec("26"));
            $this->ATSIGN    = chr(hexdec("40"));
            $this->BANG      = chr(hexdec("21"));
            $this->COMMA     = chr(hexdec("2C"));
            $this->EMPTY     = '';
            $this->EQUAL     = chr(hexdec("3D"));
            $this->POUND     = chr(hexdec("23"));
            $this->HASH      = $this->POUND;
            $this->SPACE     = chr(hexdec("20"));
            $this->QUESTION  = chr(hexdec("3F"));

            $this->DOUBLE_Q  = chr(hexdec("22"));

            #these are characters that have been found in names
            $this->SINGLE_Q  = chr(hexdec("27"));   #   '
            $this->DASH      = chr(hexdec("2D"));   #   -
            $this->LEFT_PAR  = chr(hexdec("28"));   #   (
            $this->RIGHT_PAR = chr(hexdec("29"));   #   )


            /** minimum number of words in mention to be considered too vague a query
            *   TODO reduce this to 1 (the mention itself)
            */
            $this->VAGUE_MIN = 2;

            /** if more than this many names have retrieved, prompt the user to see if they wish to
            *   narrow the search or get al the names
            */
            $this->MAX_RESULTS_WARNING = 4;

            /**
             * will trigger a TooManyTokensException if number of words tweeted is greater than this value
             */
            $this->TOO_MANY_WORDS = 5;

            $this->MYPROFMAKES = $twitter_handle;
            $this->SIX_FIGURE_SALARY = 6;

            /** the twitter api allows 15 calls to check mentions per 15 minutes = 60 calls
             *  per hour so this sleep time should allow the bot to run non stop
             *  TODO subtract processing time from sleep time to avoid lag
             */
            $this->SLEEP_TIME = 90;

            $this->TWITTER_ERROR_DUPLICATE_TWEET = 187;
            $this->TWITTER_ERROR_BAD_AUTH = 32;
            $this->TWITTER_ERROR_NO_PAGE = 34;              #HTTP 404
            $this->TWITTER_ERROR_SUSPENDED = 64;            #HTTP 403
            $this->TWITTER_ERROR_LIMIT_EXCEEDED = 88;
            $this->TWITTER_ERROR_INVALID_TOKEN = 89;
            $this->TWITTER_ERROR_THE_WHALE = 130;           #HTTP 503
            $this->TWITTER_ERROR_INTERNAL = 131;            #HTTP 500
            $this->TWITTER_ERROR_BAD_AUTH_HTTP_401 = 135;   #HTTP 401
            $this->TWITTER_ERROR_UNABLE_TO_FOLLOW = 161;    #HTTP 403
            $this->TWITTER_ERROR_NOT_AUTHORIZED = 179;      #HTTP 403
            $this->TWITTER_ERROR_OVER_DAILY_LIMIT = 185;    #HTTP 403
            $this->TWITTER_ERROR_BAD_ENDPOINT = 251;


            $this->twitter_error_hash = array(

                187 => "TWITTER_ERROR_DUPLICATE_TWEET",
                32 => "TWITTER_ERROR_BAD_AUTH",
                34 => "TWITTER_ERROR_NO_PAGE",
                64 => "TWITTER_ERROR_SUSPENDED",
                88 => "TWITTER_ERROR_SUSPENDED",
                89 => "TWITTER_ERROR_INVALID_TOKEN",
                130 => "TWITTER_ERROR_THE_WHALE (over capacity)",
                131 => "TWITTER_ERROR_INTERNAL",
                135 => "TWITTER_ERROR_BAD_AUTH_HTTP_401",
                161 => "TWITTER_ERROR_UNABLE_TO_FOLLOW",
                179 => "TWITTER_ERROR_NOT_AUTHORIZED",
                185 => "TWITTER_ERROR_OVER_DAILY_LIMIT",
                186 => "TWITTER_ERROR_STATUS_OVER_140_CHARACTERS",
                251 => "TWITTER_ERROR_BAD_ENDPOINT"
            );


        }
    }

/**ASCII CODES

  Dec Hex   Dec Hex    Dec Hex  Dec Hex  Dec Hex  Dec Hex   Dec Hex   Dec Hex
  0 00 NUL  16 10 DLE  32 20    48 30 0  64 40 @  80 50 P   96 60 `  112 70 p
  1 01 SOH  17 11 DC1  33 21 !  49 31 1  65 41 A  81 51 Q   97 61 a  113 71 q
  2 02 STX  18 12 DC2  34 22 "  50 32 2  66 42 B  82 52 R   98 62 b  114 72 r
  3 03 ETX  19 13 DC3  35 23 #  51 33 3  67 43 C  83 53 S   99 63 c  115 73 s
  4 04 EOT  20 14 DC4  36 24 $  52 34 4  68 44 D  84 54 T  100 64 d  116 74 t
  5 05 ENQ  21 15 NAK  37 25 %  53 35 5  69 45 E  85 55 U  101 65 e  117 75 u
  6 06 ACK  22 16 SYN  38 26 &  54 36 6  70 46 F  86 56 V  102 66 f  118 76 v
  7 07 BEL  23 17 ETB  39 27 '  55 37 7  71 47 G  87 57 W  103 67 g  119 77 w
  8 08 BS   24 18 CAN  40 28 (  56 38 8  72 48 H  88 58 X  104 68 h  120 78 x
  9 09 HT   25 19 EM   41 29 )  57 39 9  73 49 I  89 59 Y  105 69 i  121 79 y
 10 0A LF   26 1A SUB  42 2A *  58 3A :  74 4A J  90 5A Z  106 6A j  122 7A z
 11 0B VT   27 1B ESC  43 2B +  59 3B ;  75 4B K  91 5B [  107 6B k  123 7B {
 12 0C FF   28 1C FS   44 2C ,  60 3C <  76 4C L  92 5C \  108 6C l  124 7C |
 13 0D CR   29 1D GS   45 2D -  61 3D =  77 4D M  93 5D ]  109 6D m  125 7D }
 14 0E SO   30 1E RS   46 2E .  62 3E >  78 4E N  94 5E ^  110 6E n  126 7E ~
 15 0F SI   31 1F US   47 2F /  63 3F ?  79 4F O  95 5F _  111 6F o  127 7F DEL

*/

