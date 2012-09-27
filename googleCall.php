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

function getLatPoints($first, $last, $statFile)
{
  
  writeStat("Confirming still connected to Google Latitude.", $statFile);

  if (!testLatitude())
    return 'Please re-'.googleAuthLink('').'.';

  $msg = '<p>Getting Google Latitude data from '.strong(formatDate($last)).' to '.strong(formatDate($first)).':</p><div class="progress"><div class="bar" style="width: %f%%;"></div></div>';
  writeStat(sprintf($msg, 0), $statFile);

  $first *= 1000;
  $last *= 1000;
  $rsp = array();
  $diff = $first - $last;

  // loop through pages of google latitude points
  while (true)
  {

    list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last".
        "&fields=items(timestampMs,latitude,longitude)");

    if (($count > 0) && ($locks !== false))
    {
      // max-time for next page of latitude data
      $first = end($locks->data->items)->timestampMs - 1;
      $p = 100 * ($last - $first + $diff) / $diff;

      $s = sprintf($msg, $p);
      writeStat($s, $statFile);

      // go through latitude points
      foreach ($locks->data->items as $item)
      {
          $rsp[] = array(($item->timestampMs / 1000), $item->latitude, $item->longitude);
      }
    }
    else
    {
      writeStat("Got all Google Latitude data.", $statFile);

      return $rsp;
    }
  }
}

?>
