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

function gzipCall($url)
{
  $opts = array(
      'http'=>array(
      'method'=>"GET",
      'header'=>"Accept-Encoding: gzip\r\n" .
                "User-Agent: my program (gzip)\r\n"
                                )
    );

  $context = stream_context_create($opts);

  $xmlresponse = @file_get_contents($url, false, $context);

  if ($xmlresponse !== false)
  {
    $xmlresponse = gzdecode($xmlresponse);
  }

  return $xmlresponse;
}

function writeStat($msg, $file)
{
  file_put_contents($file, /* date("d-H:i:s "). */ "$msg"); // \n", FILE_APPEND);
}

function formatDate($adate)
{
  return date('d M Y H:i', $adate);
}

function strong($s)
{
  return "<strong>$s</strong>";
}

?>
