<?PHP

include_once('common.php');

function googleCall($params)
{
  $url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN].
        "&granularity=best&$params";

  $xmlresponse = gzipCall($url); 

  $count = 0;
  $locks = false;
  if ($xmlresponse !== false)
  {
    $locks = json_decode($xmlresponse);
    if (property_exists($locks->data, 'items'))
    {
      $count = count($locks->data->items);
    }
  }

  return array($locks, $count);
}

function testLatitude()
{
  if (cookiesSet(array(GOOGLE_TOKEN)))
  {
    list ( $rsp, $count ) = googleCall('max-results=1');
    return $count == 1;
  }
  return false;
}

function googleAuthLink($class)
{
  return '<a class="'.$class.'" href="https://accounts.google.com/o/oauth2/auth?client_id=60961182481.apps.googleusercontent.com&amp;redirect_uri='.baseHttpPath().'gotLatitude.php&amp;scope=https://www.googleapis.com/auth/latitude.all.best&amp;response_type=code">Authorise Google Latitude</a>';
}

?>
