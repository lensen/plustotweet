<?php

/**
 * Run this script in the background and it will poll your Google+ feed for public posts every 5-10 minutes.
 * If it finds any new posts it will post them to Twitter.
 * 
 */

require_once('twitter-oauth/twitteroauth.php');

$google_userid = "<your_google_userid>";
$google_key = "<your_google_api_key>";

$twitter_consumerKey = "<your_twitter_consumerKey>";
$twitter_consumerSecret = "<your_twitter_consumerSecret>";
$twitter_oauthToken = "<your_twitter_oauthToken>";
$twitter_oauthSecret = "<your_twitter_oauthSecret>";

$tweet = new TwitterOAuth($twitter_consumerKey, $twitter_consumerSecret, $twitter_oauthToken, $twitter_oauthSecret);

$max_results = 5;
$plus_filter = "&fields=items(title,published,url,verb)";
$checktime = (int)(date('U', time()));

while (true) {
    $request = @file_get_contents("https://www.googleapis.com/plus/v1/people/$google_userid/activities/public?key=$google_key&maxResults=$max_results$plus_filter");
    $obj = json_decode($request);

    echo "Previous check: ".date('r', $checktime)."\n";
    foreach ($obj->{'items'} as $item) {
        $title = $item->{'title'};
        $verb = $item->{'verb'};
        $url = $item->{'url'};
        $published = $date = date('U', strtotime($item->{'published'}));

        if ($published < $checktime) {
            echo "Not a new post. Skipping.\n";
        }
        else {
            if ($title != "" and $verb != "share" ) {
                $shorturl = @file_get_contents("http://hyv.es/api/?url=$url");
                echo "Title: $title\nURL: $shorturl\n";
                echo "Posting to Twitter\n";
                $tweet->post('statuses/update', array('status' => "$title $shorturl"));
            }
        }
    }
    $checktime = (int)(date('U', time()));

    $sleeptime = rand ( 300, 600);
    echo "Sleeping for $sleeptime seconds\n\n";
    sleep($sleeptime);
}

?>
