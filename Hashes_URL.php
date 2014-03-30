<?php
    $T = "https://api.twitter.com/1.1/";

    /**
     * An associative array with a description of the method or method name as keys & the API endpoint as values
     * HTTP method 'post' or 'get' is prepended for clarity
     */
    $this->urls = array(
        #POST STATUSES
        "post_tweet" => $T."statuses/update.json",
        "post_tweet_with_media" => $T."statuses/update_with_media.json?",
        "post_delete_tweet" => $T."statuses/destroy/",

        #POST ACCOUNT
        #even bots care about their appearance
        "post_update_profile" =>$T."account/update_profile.json",
        "post_update_profile_image" => $T."account/update_profile_image.json",
        "post_update_profile_background_image" => $T."account/update_profile_background_image.json",
        "post_update_profile_colors" => $T."account/update_profile.colors.json",
        "post_remove_profile_banner"  => $T."account/remove_profile_banner.json",
        "post_update_profile_banner"  => $T."account/update_profile_banner.json",

        #GET
        "get_mentions_timeline" => $T."statuses/mentions_timeline.json",
        "get_rate_limit_status" => $T."application/rate_limit_status.json",
        "get_help_configuration" => $T."help/configuration.json",
        "get_profile_banner" => $T."users/profile_banner.json",

        "get_search" => $T."search/tweets.json?q="
    );

    $this->family = array("post_tweet" => array('in_reply_to_status_id', 'lat', 'long', 'place_id', 'display_coordinates', 'trim_user'),

        #requires POST Content-Type=multipart/form-data
        #TODO check TwitterOAuth source code for this. Add it if not avalable.
        #upload with ssl (recommended)
        "post_tweet_with_media" => array('media[]','possibly_sensitive','in_reply_to_status_id', 'lat',
            'long', 'place_id', 'display_coordinates'),

        "post_update_profile_background_image" => array('image', 'tile', 'include_entities', 'skip_status', 'use'),
        "post_update_profile_colors" =>  array('profile_background_color', 'profile_link_color', 'profile_sidebar_border_color',
            'profile_sidebar_fill_color' ,'profile_text_color', 'include_entities' ,'skip_status'),
        "post_update_profile_banner"  => array(/*required send as parameter by itself 'banner',*/ 'width', 'height', 'offset_left',
            'offset_top'),

        "get_rate_limit_status" => array('account', 'application', 'blocks', 'geo', 'help', 'lists', 'users',
            'saved_searches', 'search', 'statuses', 'trends', 'users'),

        "get_search" => array('geocode', 'lang', 'locale', 'result_type' ,'count', 'until',
                              'since_id', 'max_id', 'include_entities')
    );
