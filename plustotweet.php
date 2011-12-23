<?php

/**
 * Run this script in the background and it will poll your Google+ feed for public posts every 5-10 minutes.
 * If it finds any new posts it will post them to Twitter.
 * 
 */

require_once('tmhOAuth/tmhOAuth.php');

$google_userid = "<your_google_userid>";
$google_key = "<your_google_api_key>";

$twitter_consumerKey = "<your_twitter_consumerKey>";
$twitter_consumerSecret = "<your_twitter_consumerSecret>";
$twitter_oauthToken = "<your_twitter_oauthToken>";
$twitter_oauthSecret = "<your_twitter_oauthSecret>";

$tmhOAuth = new tmhOAuth(array(
  'consumer_key'    => $twitter_consumerKey,
  'consumer_secret' => $twitter_consumerSecret,
  'user_token'      => $twitter_oauthToken,
  'user_secret'     => $twitter_oauthSecret,
));

$max_results = 5;
$plus_filter = "items(title,verb,url,published,object/attachments(objectType,content,fullImage/url))";
$checktime = (int)(date('U', time()));
$imagename = "upload.jpg";

while (true) {
    $request = @file_get_contents("https://www.googleapis.com/plus/v1/people/$google_userid/activities/public?key=$google_key&maxResults=$max_results&fields=$plus_filter");
    $obj = json_decode($request);

    echo "Previous check: ".date('r', $checktime)."\n";
    foreach ($obj->{'items'} as $item) {
        @unlink($imagename);
        $image = False;
        $title = $item->{'title'};
        $verb = $item->{'verb'};
        $url = $item->{'url'};
        $attachments = (empty($item->{'object'}->{'attachments'})) ? array() : $item->{'object'}->{'attachments'};
        $published = $date = date('U', strtotime($item->{'published'}));

        if ($published < $checktime) {
            echo "Not a new post. Skipping.\n";
        }
        else {
            if ($title != "" and $verb != "share" ) {
                $shorturl = @file_get_contents("http://hyv.es/api/?url=$url");
                foreach ($attachments as $attachment) {
                    $a_type = $attachment->{'objectType'};
                    $a_content = $attachment->{'content'};
                    $a_url = $attachment->{'fullImage'}->{'url'};
                    if ( $a_type == "photo" and $a_content != "" ) {
                        $image = True;
                        file_put_contents($imagename, file_get_contents($a_url));
                        break;
                    }
                }
                $status = "$title $shorturl";
                echo "Posting to Twitter: $status\n";
                if ($image) {
                    $tmhOAuth->request('POST', 'https://upload.twitter.com/1/statuses/update_with_media.json',
                        array(
                            'media[]'  => "@{$imagename}",
                            'status'   => $status
                        ),
                        true, // use auth
                        true  // multipart
                    );
                }
                else {
                     $tmhOAuth->request('POST', $tmhOAuth->url('1/statuses/update'), array(
                        'status' => $status
                    ));
                }
            }
        }
    }
    $checktime = (int)(date('U', time()));

    $sleeptime = rand ( 300, 600);
    echo "Sleeping for $sleeptime seconds\n\n";
    sleep($sleeptime);
}

?>
