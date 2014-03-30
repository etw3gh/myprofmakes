<?php
    /**
     * Class StringHelpers
     *
     * added to encourage decoupling of classes
     *
     * @todo incorporate ideas from ruby NameCase class (included in comment at bottom of this file)
     * @todo run capitalize_names on entire database via utility script instead of calling it over and over again
     *
     */
    class StringHelpers
    {

        public $C;

        public function __construct($constants)
        {
            $this->C = $constants;
        }
        /**
         * function that takes a name and returns a properly capitalized name
         *
         * @param $first
         * @param $last string optional last name (the name may come all in one string)
         * @throws InvalidStringException
         * @return string
         */
        public function capitalize_name($first, $last = null)
        {
            if (!is_string($first))
            {
                throw new InvalidStringException("Raised from capitalize_name method");
            }

            $space = $this->C->SPACE;
            $first = $this->capitalize_words($first);

            if (is_null($last) || !is_string($last))
            {
                return $first;
            }
            else
            {
                $last = $this->capitalize_words($last);
                return $first.$space.$last;
            }
        }


        /**
         * function that properly capitalizes every word in a sentence
         *
         * treats a single name as a sentence as its possible to have many space separated names
         *
         * mainly helper for capitalize_names
         *
         * Yes, its O(n^3) for special cases but n is very small, being the number of words and/or word-components in a first or last name
         *      and O(n^2) for names that do not contain special characters which is in most cases
         *
         * works for 99.5% of names and 100% of names on the sunshine list
         *
         * @todo fix for hyphenated mac-mc names by refactoring (very rare)
         * @todo replace everything with a set of regular expressions
         *
         * @param $sentence
         * @throws EmptyStringException
         * @throws InvalidStringException
         * @return string
         */
        public function capitalize_words($sentence)
        {
            if (!is_string($sentence))
            {
                throw new InvalidStringException("Raised from capitalize_words method");
            }

            if (strlen($sentence) == 0)
            {
                throw new EmptyStringException("Raised from capitalize_words method.");
            }

            if (str_word_count($sentence) == 0)
            {
                return $sentence;
            }

            $sentence = strtolower($sentence);

            $separator = null;
            $space = $this->C->SPACE;
            $dash = $this->C->DASH;
            $left = $this->C->LEFT_PAR;
            $right = $this->C->RIGHT_PAR;

            $luck_o_the_irish = $this->C->SINGLE_Q;

            $special_capitializations = array('Mc', 'Mac');
            #$special_non_capitalizations = array("von", "de" );

            $proper_capitalization = array();

            #TODO determine if more characters exist
            $funny_characters = array($dash, $luck_o_the_irish, $left, $right);

            $words = explode($space, $sentence);
            foreach ($words as $a_word)
            {
                $is_separated_name = false;

                foreach ($funny_characters as $separator)
                {
                    if (strstr($a_word, $separator))
                    {
                        #if $separator is present then capitalize all portions
                        $separate_name = explode($separator, $a_word);

                        $put_it_back_together_again = array();

                        #there can be more than 1 hyphen in a name eg: Nicholas Ng-A-Fook, esteemed Ottawa U professor.

                        #capitalize each piece and stack it up
                        foreach ($separate_name as $name_piece)
                        {
                            array_push($put_it_back_together_again, ucfirst($name_piece));
                        }

                        #implode the stack
                        $a_word = implode($separator, $put_it_back_together_again);
                        $is_separated_name = true;
                    }
                }
                if (!$is_separated_name)
                {
                    array_push ($proper_capitalization, ucfirst($a_word));
                }
                else
                {
                    array_push ($proper_capitalization, $a_word);
                }
            }
            #$proceed_to_last_check = implode($space, $proper_capitalization);

            $final_array = array();

            foreach ($proper_capitalization as $proceed_to_last_check)
            {
                $word_pushed = false;

                foreach ($special_capitializations as $special_cap)
                {
                    $prefix_length = strlen($special_cap);

                    $test_prefix = substr($proceed_to_last_check, 0, $prefix_length);

                    #if the first n characters are equal then capitalize the suffix
                    if ($test_prefix == $special_cap)
                    {
                        $suffix = substr($proceed_to_last_check, $prefix_length);
                        array_push($final_array, ucfirst($test_prefix) . ucfirst($suffix));
                        $word_pushed = true;
                    }
                }
                if (!$word_pushed)
                {
                    array_push($final_array, $proceed_to_last_check);
                }
            }
            return implode($space, $final_array);
        }


        /**
         * function that strips undesirable characters from a string and then
         * normalizes spaces to 1 space between words
         *
         * non utf-8 are stripped, which is a temporary measure (see @todo)
         *
         * @todo DEAL WITH UNICODE
         * @param $tweet
         * @throws StringContainsURLException
         * @throws InvalidStringException
         * @return string
         */
        public function strip_undesirables($tweet)
        {
            if (!is_string($tweet)) throw new InvalidStringException("Invalid string from strip_undesirables method");

            #strip url
            if (!strpos($tweet, 'http'))
            {
                #strips all non-utf-8 to prevent system crash (MongoCrash)
                #TODO must handle unicode!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                $tweet = preg_replace('/[^(\x20-\x7F)]*/','', $tweet );
                return $this->normalize_spaces($tweet);
            }
            else
            {
                throw new StringContainsURLException("url detected");
            }

        }


        /**
         * function that strips extra whitespace, ensuring only 1 space between words
         * and none at either ends
         *
         * @param $a_string
         * @throws InvalidStringException
         * @return string
         */
        public function normalize_spaces($a_string)
        {
            if (!is_string($a_string)) throw new InvalidStringException("Invalid string from normalize_spaces method");

            $space = $this->C->SPACE;
            $match_multiple_spaces_regex = '/\s+/';

            return preg_replace($match_multiple_spaces_regex, $space, trim($a_string, $space));
        }
    }

    class InvalidStringException extends Exception
    {
        public function __construct($message, $code = 0, Exception $previous = null)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Not a string";
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()}".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }

    class EmptyStringException extends Exception
    {
        public function __construct($message, $code = 0, Exception $previous = null)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Zero length string";
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()}".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }

    class StringContainsURLException extends Exception
    {
        public function __construct($message, $code = 0, Exception $previous = null)
        {
            parent::__construct($message);
        }

        public function err()
        {
            $error_type = "Zero length string";
            $e = "$error_type error on line {$this->getLine()} in file {$this->getFile()}".PHP_EOL."ERR: {$this->getMessage()}".PHP_EOL;
            return $e;
        }
    }

/*
class NameCase < String
  VERSION = '1.1.0'

  class << self
    def nc string
      new(string).nc
    end
  end

  # Returns a new +String+ with the contents properly namecased
  def nc
    localstring = downcase
    localstring.gsub!(/\b\w/) { |first| first.upcase }
    localstring.gsub!(/\'\w\b/) { |c| c.downcase } # Lowercase 's

    if localstring =~ /\bMac[A-Za-z]{2,}[^aciozj]\b/ or localstring =~ /\bMc/
      localstring.gsub!(/\b(Ma?c)([A-Za-z]+)/) { |match| $1 + $2.capitalize }

      # Now fix "Mac" exceptions
      localstring.gsub!(/\bMacEvicius/, 'Macevicius')
      localstring.gsub!(/\bMacHado/, 'Machado')
      localstring.gsub!(/\bMacHar/, 'Machar')
      localstring.gsub!(/\bMacHin/, 'Machin')
      localstring.gsub!(/\bMacHlin/, 'Machlin')
      localstring.gsub!(/\bMacIas/, 'Macias')
      localstring.gsub!(/\bMacIulis/, 'Maciulis')
      localstring.gsub!(/\bMacKie/, 'Mackie')
      localstring.gsub!(/\bMacKle/, 'Mackle')
      localstring.gsub!(/\bMacKlin/, 'Macklin')
      localstring.gsub!(/\bMacQuarie/, 'Macquarie')
    end
    localstring.gsub!('Macmurdo','MacMurdo')

    # Fixes for "son (daughter) of" etc
    localstring.gsub!(/\bAl(?=\s+\w)/, 'al')  # al Arabic or forename Al.
    localstring.gsub!(/\bAp\b/, 'ap')         # ap Welsh.
    localstring.gsub!(/\bBen(?=\s+\w)/,'ben') # ben Hebrew or forename Ben.
    localstring.gsub!(/\bDell([ae])\b/,'dell\1')  # della and delle Italian.
    localstring.gsub!(/\bD([aeiu])\b/,'d\1')   # da, de, di Italian; du French.
    localstring.gsub!(/\bDe([lr])\b/,'de\1')   # del Italian; der Dutch/Flemish.
    localstring.gsub!(/\bEl\b/,'el')   # el Greek or El Spanish.
    localstring.gsub!(/\bLa\b/,'la')   # la French or La Spanish.
    localstring.gsub!(/\bL([eo])\b/,'l\1')      # lo Italian; le French.
    localstring.gsub!(/\bVan(?=\s+\w)/,'van')  # van German or forename Van.
    localstring.gsub!(/\bVon\b/,'von')  # von Dutch/Flemish

    # Fix roman numeral names
    localstring.gsub!(
      / \b ( (?: [Xx]{1,3} | [Xx][Ll]   | [Ll][Xx]{0,3} )?
             (?: [Ii]{1,3} | [Ii][VvXx] | [Vv][Ii]{0,3} )? ) \b /x
    ) { |match| match.upcase }

    localstring
  end

  # Modifies _str_ in place and properly namecases the string.
  def nc!
    self.gsub!(self, self.nc)
  end
end

def NameCase string
  NameCase.new(string).nc
end






 */
