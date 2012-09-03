<?PHP

define('GOOGLE_TOKEN', 'google_access_token');
define('FLICKR_TOKEN', 'flickr_access_token');
define('FLICKR_SECRET', 'flickr_access_secret');

function clearCookies($cookies)
{
  foreach($cookies as $cookie)
  {
    setcookie($cookie, '', time() - 3600);
    if (array_key_exists($cookie, $_COOKIE))
      unset($_COOKIE[$cookie]);
  }
}

function cookiesSet($cookies)
{
  $res = true;
  foreach($cookies as $cookie)
  if (!(array_key_exists($cookie, $_COOKIE) && ($_COOKIE[$cookie] != ''))) 
  {
    $res = false;
    break;
  }
  if (!$res)
    clearCookies($cookies);
  return $res;
}

function baseHttpPath()
{
  $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
  $p = strrpos($uri, '/');
  return 'http://'.$_SERVER['HTTP_HOST'].substr($uri, 0, $p).'/';
}

?>
