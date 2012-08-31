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

?>
