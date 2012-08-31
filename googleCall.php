<?PHP

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
