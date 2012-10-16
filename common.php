<?PHP

// cookie names
define('GOOGLE_TOKEN', 'google_access_token');
define('FLICKR_TOKEN', 'flickr_access_token');
define('FLICKR_SECRET', 'flickr_access_secret');

// Field indexes
define("UTIME", 0);
define("LATITUDE", 1);
define("LONGITUDE", 2);

// mandatory field defaults
define("DEF_COUNT", 10);
define("DEF_ACCURACY", 100);
define("DEF_MAX_GAP", 24);

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
  $uri = explode('?', $_SERVER['REQUEST_URI']);
  $p = strrpos($uri[0], '/');
  return 'http://'.$_SERVER['HTTP_HOST'].substr($uri[0], 0, $p).'/';
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
//  file_put_contents($file, date("d-H:i:s "). "$msg\n", FILE_APPEND);
  file_put_contents($file, $msg);
}

function formatDate($adate)
{
  return date('d M Y H:i', $adate);
}

function strong($s)
{
  return "<strong>$s</strong>";
}

function sort_array_by_utime(&$anArray)
{
  usort($anArray, function($a, $b) {
    if ($a[UTIME] == $b[UTIME])
      return 0;
    return ($a[UTIME] < $b[UTIME]) ? 1 : -1;
  });
}

function errorExit($msg)
{
  echo "<div class=\"alert alert-error\">$msg</div>";
  exit;
}

function writeProgress($msg, $percent, $statFile)
{
  writeStat(progressBar($msg, $percent), $statFile);
}

function progressBar($msg, $percent)
{
  return "<p>$msg".'</p><div class="progress"><div class="bar" style="width: '.$percent.'%;"></div></div>';
}

if (!function_exists('gzdecode'))
{
  function gzdecode($data)
  {
    return gzinflate(substr($data, 10, -8));
  }
}

?>
