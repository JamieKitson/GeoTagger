<?php

include('flickrCall.php');

function getRequestToken()
{
  clearCookies(array(FLICKR_SECRET, FLICKR_TOKEN));
  $params = Array();
  $params['oauth_callback'] = 'http://geo.kitten-x.com/gotFlickr.php';
  $rsp = flickrCall($params, 'oauth/request_token');
  echo $rsp;
  parse_str($rsp, $q);
  print_r($q);
  if ($q['oauth_callback_confirmed'] != true)
    exit("Flickr didn't return oauth_callback_confirmed true");
  $url = 'http://www.flickr.com/services/oauth/authorize?oauth_token='.$q['oauth_token'];
  echo "$url\n".$q['oauth_token_secret'];
  setcookie(FLICKR_SECRET, $q['oauth_token_secret']);
  header("Location: $url");
}

getRequestToken();

?>
