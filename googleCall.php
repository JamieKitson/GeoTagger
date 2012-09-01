<?PHP

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

  $xmlresponse = gzdecode(file_get_contents($url, false, $context));
  $locks = json_decode($xmlresponse);

  $count = 0;
  $lcoks = false;
  if ($xmlresponse !== FALSE)
  {
    $locks = json_decode($xmlresponse);
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

    $url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN]."&granularity=best&max-results=1";

//echo $url;

    $xmlresponse = @file_get_contents($url);

//echo $xmlresponse;
    if ($xmlresponse === FALSE)
    {   
      clearCookies(array(GOOGLE_TOKEN));
      return false;
    }
//echo "\n\n<br><p>ab";

//    print_r(json_decode($xmlresponse));

    return true;

  }
  return false;
}

?>
