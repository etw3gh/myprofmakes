<?php
    require_once('BotEngine.php');
    $verbose = false;
    $run = new BotEngine($verbose);

    try
    {
        $run->run();
    }
    catch (Exception $e)
    {
        print "Failed to Run man Bot Engine. This is a major problem." . PHP_EOL;

        print $e->getMessage() . PHP_EOL;
        print $e->getLine() . PHP_EOL;
        print $e->getFile() . PHP_EOL;

    }




