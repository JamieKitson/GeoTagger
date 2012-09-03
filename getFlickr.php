<?php

include('flickrCall.php');

function getRequestToken()
{
  clearCookies(array(FLICKR_SECRET, FLICKR_TOKEN));
  $params = Array();
  $params['oauth_callback'] = baseHttpPath().'gotFlickr.php';
  $rsp = flickrCall($params, 'oauth/request_token');
  parse_str($rsp, $q);
  if (!array_key_exists('oauth_callback_confirmed', $q) || $q['oauth_callback_confirmed'] != true)
    exit("Flickr didn't return oauth_callback_confirmed true: $rsp");
  $url = 'http://www.flickr.com/services/oauth/authorize?oauth_token='.$q['oauth_token'];
  setcookie(FLICKR_SECRET, $q['oauth_token_secret']);
  header("Location: $url");
}

getRequestToken();

?>
