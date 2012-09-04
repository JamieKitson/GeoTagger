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

?>
