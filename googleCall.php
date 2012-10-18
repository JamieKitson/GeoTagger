<?PHP

include_once('common.php');

function googleCall($params)
{
  $url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN]."&granularity=best&$params";

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

function getLatPoints($statFile, $maxGap, $photos)
{
  writeStat("Confirming still connected to Google Latitude.", $statFile);

  if (!testLatitude())
    errorExit('Please re-'.googleAuthLink('').'.');

  $first = $photos[0][UTIME] + $maxGap;
  $lastPhoto = end($photos);
  $last = $lastPhoto[UTIME] - $maxGap;

  $msg = 'Getting Google Latitude data from '.strong(formatDate($last)).' to '.strong(formatDate($first));
  writeProgress($msg, 0, $statFile);

  $realLast = $last * 1000;
  $diff = $first * 1000 - $realLast;

  $allPoints = array();
  for ($i = 0; $i < count($photos); $i++)
  {
    // get the initial date, +- 24 hours
    $first = ($photos[$i][UTIME] + $maxGap) * 1000;
    // while the gap between photos is less than 2 * maxGap
    while (($i < count($photos) - 1) && ($photos[$i][UTIME] - $photos[$i + 1][UTIME] < $maxGap * 2))
      $i ++;

    // get the last date before the gap
    $last = ($photos[$i][UTIME] - $maxGap) * 1000;

    $points = getWholeDuration($first, $last, $statFile, $realLast, $diff, $msg);

    $allPoints = array_merge($allPoints, $points);
  }
  writeStat("Got all Google Latitude data.", $statFile);
  return $allPoints;
}

function getWholeDuration($first, $last, $statFile, $realLast, $diff, $msg) 
{
  $rsp = array();
  // loop through pages of google latitude points
  while (true)
  {

    list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last&fields=items(timestampMs,latitude,longitude,accuracy)");

    if (($count > 0) && ($locks !== false))
    {
      // max-time for next page of latitude data
      $first = end($locks->data->items)->timestampMs - 1;

      $p = round(100 * ($realLast - $first + $diff) / $diff);
      writeProgress($msg, $p, $statFile);

      // go through latitude points
      foreach ($locks->data->items as $item)
      {
        if ($item->accuracy < DEF_ACCURACY)
          $rsp[] = array(($item->timestampMs / 1000), $item->latitude, $item->longitude);
      }
    }
    else
    {
      return $rsp;
    }
  }
}

?>
