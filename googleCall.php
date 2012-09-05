<?PHP

include_once('cookies.php');

function googleCall($params)
{
  $opts = array(
      'http'=>array(
      'method'=>"GET",
      'header'=>"Accept-Encoding: gzip\r\n" .
                "User-Agent: my program (gzip)\r\n"
                                )
    );

  $context = stream_context_create($opts);

  $url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN].
        "&granularity=best&$params"; // max-results=1000&max-time=$first&min-time=$last";

  $xmlresponse = @file_get_contents($url, false, $context);

  $count = 0;
  $locks = false;
  if ($xmlresponse !== FALSE)
  {
    $unzipped = gzdecode($xmlresponse);
    $locks = json_decode($unzipped);
    if (property_exists($locks->data, 'items'))
    {
      $count = count($locks->data->items);
  //    $first = end($locks->data->items)->timestampMs;
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
