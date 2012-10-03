<?php

include('flickrCall.php');

function getToken()
{
  $params = Array();
  $params['oauth_verifier'] = $_GET['oauth_verifier'];
  $params['oauth_token'] = $_GET['oauth_token'];
  $rsp = flickrCall($params, 'oauth/access_token');
  echo $rsp;
  parse_str($rsp, $q);
  print_r($q);
  $exp = time() + 60 * 60 * 24 * 100;
  setcookie(FLICKR_SECRET, $q['oauth_token_secret'], $exp);
  setcookie(FLICKR_TOKEN, $q['oauth_token'], $exp);
  header("Location: index.php#flickr");
}

getToken();


?>
