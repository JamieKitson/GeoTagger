<?php

include_once('common.php');

function flickrCall($params, $uri = "rest")
{

        $f      = file(dirname(__FILE__).'/flickrsecret.php');
        $secret = trim($f[1]);
        $params['oauth_consumer_key']     = '3b72b2a6e62cc3a06de7eef62646d81c';
        $params['oauth_nonce']            = rand(0, 99999999);
        $params['oauth_timestamp']        = date('U');
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_version']          = '1.0';
        $params['format']      = 'php_serial';
        if (array_key_exists(FLICKR_TOKEN, $_COOKIE) && ($_COOKIE[FLICKR_TOKEN] != ''))
          $params['oauth_token'] = $_COOKIE[FLICKR_TOKEN];

        $encoded_params = array();
        foreach ($params as $k => $v)
        {
                $encoded_params[] = urlencode($k).'='.urlencode($v); // "$k=$v"; //  
        }

        sort($encoded_params);
        $p = implode('&', $encoded_params);

        $url = "http://api.flickr.com/services/$uri";

        $base = "GET&".urlencode($url)."&".urlencode($p);

        if (array_key_exists(FLICKR_SECRET, $_COOKIE))
          $tokensecret = $_COOKIE[FLICKR_SECRET];
        else
          $tokensecret = "";

        $sig = urlencode(base64_encode(hash_hmac('sha1', $base, "$secret&$tokensecret", true)));

        $url .= "?$p&oauth_signature=$sig";

        $rsp = gzipCall($url);
  
        return $rsp;

}

function testFlickr()
{
  if (cookiesSet(array(FLICKR_TOKEN, FLICKR_SECRET)))
  {
    $rsp = flickrCall(Array('method' => 'flickr.test.login'));
    $p = unserialize($rsp);
    if (array_key_exists('stat', $p) && ($p['stat'] == 'ok'))
      return str_replace("@", "_", $p['user']['id']);
  }
  return false; 
}

function flickrAuthLink($class)
{
  return "<a class=\"$class\" href=\"getFlickr.php\">Authorise Flickr</a>";
}

?>
