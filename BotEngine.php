<?php
    require_once("TwitterIOBroker.php");

    class BotEngine
    {
        public $verbose;
        public $io_broker;
        public $C;
        public $BAD;

        public function __construct($verbose)
        {
            $this->verbose = $verbose;
            $this->io_broker = new TwitterIOBroker(true);
            $this->C = $this->io_broker->C;
            $this->BAD = false;
        }

        public function run()
        {
            #initialize variables for the run loop
            $cold_start = true;
            $get_last_id = null;
            #$id = $empty;   ###defined in tweet info block below

            $last = null;
            $remaining_calls = null;
            $twitter_mentions = null;

            #tweet info (nice names)
            $text = null;
            $id = "";
            $the_tweet = null;
            $name = null;
            $tweeted_on = null;
            $tweeted_from = null;
            $user_id = null;

            $t = null;
            $sys_date = null;
            $out_success = null;

            #local aliases for readability
            $verbose = $this->verbose;
            $empty = $this->C->EMPTY;
            $io_broker = $this->io_broker;
            $mongo = $this->io_broker->mongo_instance;
            $nap = $this->C->SLEEP_TIME;
            $space = $this->C->SPACE;
            $tweet_generator = $this->io_broker->gen;

            $batch_salaries = array();


            #remember to sleep before any continue statement
            while (true)
            {
                system("date");


                try
                {
                    $io_broker->post_all_outgoing();
                }
                catch (IOBrokerTwitterError $e)
                {
                    #do nothing
                }

                #check rate

                try
                {
                    $remaining_calls =$this->io_broker->remaining_mentions();
                }
                catch (OAuthTransactionException $e)
                {
                    print $e->err('Caught in Bot engine.');
                }
                catch (IOBrokerTwitterError $e)
                {
                    if ($verbose) print $e->err();
                    $tec = $e->get_twitter_error_code();
                    $tec_translation = $this->C->twitter_error_hash[$tec];

                    if ($verbose) print "Twitter error code is: $tec [$tec_translation]".PHP_EOL;
                }


                if (!is_null($remaining_calls))
                {
                    print "Remaining calls: $remaining_calls".PHP_EOL;
                }
                else
                {
                    if ($verbose) print "NULL: returned for remaining calls".PHP_EOL;
                }

                #if app has remaining API calls then proceed
                if ($remaining_calls > 0)
                {
                    #if server is restarted tweets retrieved will be from the beginning of time
                    #look for last replied to tweet to use as "$since"

                    if ($verbose) print "yes remaining calls".PHP_EOL;

                    if ($cold_start || $id == $empty || is_null($id))
                    {

                        #do things to account for cold start
                        if ($mongo->get_count_myprof('incoming') > 0)
                        {
                            # $last will be set to null if there are no mentions
                            $last = $mongo->get_last_mentions_id();

                            if ($verbose) print "Last id is $last".PHP_EOL;

                            $cold_start = false;
                            if ($verbose) print "cold start now false".PHP_EOL;
                        }
                        else
                        {
                            if ($verbose) print "not cold. last now null".PHP_EOL;
                            $last = null;
                        }

                    }

                    #get mentions since from last tweet id
                    try
                    {
                        $twitter_mentions = $io_broker->mentions($last);

                        #print "Twitter Mentions:\n";
                        #print_r($twitter_mentions);


                    }
                    catch (IOBrokerTwitterError $e)
                    {
                        if ($verbose) print $e->err() . " | Twitter Code:" . $e->get_twitter_error_code();
                    }



                    #if false is returned, skip this loop iteration and publish warning
                    if (!$twitter_mentions)
                    {
                        if ($verbose)
                        {
                            #print "[false] No mentions returned".PHP_EOL;

                        }
                       else
                            error_log("TwitterIOBroker has returned false from method: mentions");

                        if ($verbose) print "sleep on false twitter_mentions".PHP_EOL;
                        sleep($nap);
                        continue;
                    }


                    #iterate over results
                    foreach ($twitter_mentions as $t)
                    {
                        if ($verbose) print "entered foreach Twitter_mentions".PHP_EOL;
                        $incoming_array = null;
                        $salaries_array = null;
                        $outgoing_array = null;
                        $text = $t->text;
                        $id = $t->id_str;
                        $name = $t->user->screen_name;
                        $user_id = $t->user->id_str;
                        

                        if( !mb_check_encoding($text, "UTF-8"))
                        {
                            continue;
                        }

                        #call method from tokenizer class to strip undesirables as some need it below
                        try
                        {
                            $text =$io_broker->S->strip_undesirables($text);
                        }
                        catch (InvalidStringException $e)
                        {
                            #$text is not a string, thus we must skip this iteration
                            continue;
                        }
                        catch (StringContainsURLException $e)
                        {
                            $outgoing_array = array("tweet" => null, "id" => $id, "name" => $name, "uid" => $user_id, "sent" => 0);
                            $incoming_array = array("text" => $text, "id" => $id, "name" => $name, "uid" => $user_id);
                            if ($verbose) print $e->getMessage();

                            $too_many_results_message = $tweet_generator->generate_tweet_contains_url_tweet($name);
                            $outgoing_array['tweet'] = $too_many_results_message;

                            $out_success = $mongo->write_outgoing(array($outgoing_array));
                            $r = $mongo->write_incoming($t, $incoming_array);
                            continue;
                        }

                        $incoming_array = array("text" => $text, "id" => $id, "name" => $name, "uid" => $user_id);

                        #TODO determine if there is  need to embed incoming Object _id
                        $outgoing_array = array("tweet" => null, "id" => $id, "name" => $name, "uid" => $user_id, "sent" => 0);

                        #print_r($incoming_array);

                        #TODO CHECK $name AGAINST $this->skip_users

                        if (strstr($id, $space) || strstr($user_id, $space) ||
                            strlen($id) != $this->C->INCOMING_ID_LENGTH || strstr($name, $space)
                            || is_null($name))
                        {
                            $this->BAD = true;
                        }

                        if (!$this->BAD && !is_null($id) && !is_null($user_id))
                        {
                            #performs both raw dump ($t) and incoming write ($incoming_array) and writes last mention id
                            $r = $mongo->write_incoming($t, $incoming_array);
                            if (!$r) print "error writing incoming".PHP_EOL;
                        }
                        else
                        {
                            if ($verbose)
                                print "Main bot loop has encountered a BAD screen name during mentions iteration".PHP_EOL;
                            else
                                error_log("Main bot loop has encountered a BAD screen name during mentions iteration");

                            sleep($nap);
                            continue;

                        }#end if BAD


                        #check against ignore list
                        #ignore list should have user name, and flag to indicate all tweets to be banned or just
                        #certain previously tweeted things
                        #has been constructed manually
                        #keys:
                        #   who: user
                        #   what: array of tweets to ignore
                        #   ban: true : 1  or false : 0

                        #check for totally banned user
                        $banned_user = $mongo->is_banned_user(strtolower($name));
                        if ($banned_user)
                        {
                            if ($verbose) print "Continue on banned user: $name".PHP_EOL;
                            continue;
                        }

                        #check for tweets to ignore from non banned user

                        $ignore_user = $mongo->ignore_user_tweets($name, $text);
                        if ($ignore_user)
                        {
                            if ($verbose) print "Continue on ignored tweet: $text from $name".PHP_EOL;
                            continue;
                        }

                        try
                        {
                            $salaries_array =$io_broker->toker->tokenize($text);
                        }
                        catch (EmptyTweetException $e)
                        {
                            if ($verbose) print $e->err();
                            $salary_tweet = $tweet_generator->generate_usage_tweet($name);
                            $outgoing_array['tweet'] = $salary_tweet;
                            $out_success = $mongo->write_outgoing(array($outgoing_array));

                            if (!$out_success)
                            {
                                #TODO raise exception;
                                print "TODO raise exception: error writing outgoing improper usage tweet".PHP_EOL;
                            }
                            if ($verbose) print "Continue on post tokenize".PHP_EOL;
                            continue;
                        }
                        catch (TweetTooShortException $e)
                        {
                            if ($verbose) print $e->err();

                            $vague_usage = $tweet_generator->generate_toovague_tweet($name);
                            $outgoing_array['tweet'] = $vague_usage;
                            $out_success = $mongo->write_outgoing(array($outgoing_array));

                            if (!$out_success)
                            {
                                print "TODO raise exception: error writing outgoing too vague tweet".PHP_EOL;
                            }
                            if ($verbose) print "Continue on vague tweet: $text from $name".PHP_EOL;
                            continue;
                        }
                        catch (TooManyTokensException $e)
                        {
                            if ($verbose) print $e->err();

                            $too_many_words_message = $tweet_generator->generate_too_many_words_tweet($name);
                            $outgoing_array['tweet'] = $too_many_words_message;
                            $out_success = $mongo->write_outgoing(array($outgoing_array));

                            continue;

                        }
                        catch (MongoException $e)
                        {
                            if ($verbose) print "Mongo: {$e->getMessage()} at Line: {$e->getLine()}".PHP_EOL;
                            continue;
                        }
                        catch (TooManyResultsException $e)
                        {
                            if ($verbose) print $e->getMessage();

                            $number_of_results = $e->get_number_of_results();

                            $too_many_results_message = $tweet_generator->generate_too_many_results_tweet($name, $number_of_results);
                            $outgoing_array['tweet'] = $too_many_results_message;
                            $out_success = $mongo->write_outgoing(array($outgoing_array));

                            continue;
                        }
                        catch (NullCursorException $e)
                        {
                            if ($verbose) print $e->err();
                            continue;
                        }
                        catch (LowRollerException $e)
                        {
                            #if ($verbose) print $e->err();

                            #remove own twitter handle from tweeted text. Assumption is that this is the professors name
                            $prof = str_replace(strtolower($this->C->MYPROFMAKES), $empty, strtolower($text));


                            if ($verbose) print "Prof: $prof".PHP_EOL;

                            #TODO scoop out any university ref

                            #$prof = "This professor or employee ";

                            $low_roller_tweet = $tweet_generator->generate_low_roller_tweet($name, $prof);

                            $outgoing_array['tweet'] = $low_roller_tweet;

                            $out_success = $mongo->write_outgoing(array($outgoing_array));

                            if (!$out_success)
                            {
                                #TODO raise exception;
                                print "TODO raise exception: error writing outgoing low roller tweet".PHP_EOL;
                            }
                            continue;
                        }



                        foreach($salaries_array as $salary)
                        {
                            #print "Salaries section---------------------------------------------".PHP_EOL;
                            #print_r($salary);

                            #formulate a tweet and save to outgoing db

                            #if tweet contains only 2 words then generate usage tweet
                            #TODO perform queries on one word as last name and ask if all results should be returned

                            if(!is_array($salary)) continue;

                            $salary_tweet = $tweet_generator->generate_tweet($name, $salary);

                            $outgoing_array['tweet'] = $salary_tweet;

                            if ($verbose) print "Salary tweet was: '$salary_tweet'".PHP_EOL;

                            array_push($batch_salaries, $outgoing_array);

                        }#foreach salaries_array

                        $out_success = $mongo->write_outgoing($batch_salaries);

                        #VERY IMPORTANT
                        $last = $id;

                    }#for ($twitter_mentions as $t)



                    #no tweets are actually posted until now

                    try
                    {
                        $out_success = $io_broker->post_all_outgoing();
                    }
                    catch (InvalidTextException $e)
                    {
                        if ($verbose) print $e->getMessage();
                    }
                    catch (InvalidParameterFormatException $e)
                    {
                        if ($verbose) print $e->getMessage();
                    }
                    catch (NoValidParametersException $e)
                    {
                        if ($verbose) print $e->getMessage();
                    }
                    catch (OAuthTransactionException $e)
                    {
                        #an error has occurred in the posting of the tweet
                        if ($verbose) print $e->getMessage();
                    }
                    catch (IOBrokerExceptions  $e)
                    {
                        #twitter returned an error
                        if ($verbose) print $e->getMessage();

                        #TODO based on error code do something
                        #$error = $e->get_twitter_error_code();
                    }
                    catch (Exception $e)
                    {
                        if ($verbose) print $e->getMessage();
                    }



                    if (!$out_success)
                        print "Error posting all tweets".PHP_EOL;



                } #if ($remaining_calls > 0)
                else
                {
                    #no remaining calls
                    if ($verbose) print "Sleep on no calls remaining".PHP_EOL;
                    sleep($nap);
                } #if ($remaining_calls > 0)

                #sleep between api calls
                #TODO reduce this time by the time it takes to post all tweets (if its a lot)
                if ($verbose) print "Sleep on naturally occurring end of loop".PHP_EOL;
                sleep($nap);

            }##main bot loop
        }#function run()
    }#class BotEngine