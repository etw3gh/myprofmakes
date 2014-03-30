<?php
    #import authorized var $twitter
    require_once("Authorize.php");

    $auth = new Authorize();
    $twitter = $auth->get_my_twitter_oauth();



    $api_destry_url = "https://api.twitter.com/1.1/statuses/destroy/";
    $api_get_timeline_url = "https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=myprofmakes&trim_user=true&count=200";

    $timeline = $twitter->get($api_get_timeline_url);

    #var_dump ($timeline);
    #exit;
    foreach ($timeline as $status)
    {
        $id = $status->id_str;
        print $id."\n";
        $twitter->post($api_destry_url."$id.json");
    }
?>
