<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

if (!(testFlickr() && testLatitude()))
  exit('<div class="alert alert-error">Please re-'.googleAuthLink('').'.</div>');

$fp = array(
    
    'sort' => 'date-taken-desc',
    'per_page' => $_GET['count'],
    
    );

foreach(explode("\n", $_GET['criteria']) as $a)
{
  $q = explode('=', $a);
  if (count($q) == 2)
    $fp[$q[0]] = $q[1];
}

$fp['user_id'] = 'me';
$fp['has_geo'] = 0;
$fp['method'] = 'flickr.photos.search';
$fp['extras'] = 'date_taken,geo';

$fc = unserialize(flickrCall($fp));

// The flickrCall above will use the local time zone, 
// but from now on we should use the user's time zone
date_default_timezone_set($_GET['region'].'/'.$_GET['timezone']);

$photos = $fc['photos']['photo'];

if (count($photos) == 0)
{
  exit('<div class="alert alert-error">No Flickr photos found.</div>');
}
else
{
  touch('stats/'.str_replace('@', '_', $photos[0]['owner']));
}

foreach($photos as &$p)
{
  $p['udatetaken'] = strtotime($p['datetaken']);
}

usort($photos, function($a, $b) { 
    if ($a['udatetaken'] == $b['udatetaken']) 
      return 0; 
    return ($a['udatetaken'] < $b['udatetaken']) ? 1 : -1; 
  }); 

$first = ($photos[0]['udatetaken'] + 24 * 60 * 60) * 1000;
$last = (end($photos)['udatetaken'] - 24 * 60 * 60) * 1000;

echo '<table class="table">';
echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th>".
  "<th>Tag</th><th>Geo</th></tr>";
$photo = 0;
$locks= true;
$count = 100;
while (($count > 0) && ($locks !== FALSE))
{

  list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last".
      "&fields=items(timestampMs,latitude,longitude)");

  if ($count > 0)
  {
    $first = end($locks->data->items)->timestampMs - 1;
  }
  else
  {
    echo '</table><div class="alert alert-error">Google Latitude returned no data for <strong>'.
      formatDate($last / 1000).'</strong> to <strong>'.formatDate($first / 1000).'</strong>.</div>';
    break;
  }

  // go through photos
  $geo = 0;
  while (($pDate = $photos[$photo]['udatetaken']) > $first / 1000)
  {
    while (($geo < count($locks->data->items)) && ($pDate < $locks->data->items[$geo]->timestampMs / 1000))
    {
      $geo++;
    }
    // all geo points are before photo
    if ($pDate < $locks->data->items[$geo]->timestampMs / 1000)
      break;

    $id = $photos[$photo]['id'];
    $title = /*utf8_decode(*/$photos[$photo]['title'];//);
    if ($title == "")
      $title = $id;
    
    echo "<tr><td>".($photo + 1)."</td><td><a href=\"http://flickr.com/photos/".$photos[$photo]['owner']."/$id\">$title</a></td>\n";

    $prior = $locks->data->items[$geo - 1];
    $next = $locks->data->items[$geo];
    $dTime = ($prior->timestampMs - $next->timestampMs) / 1000; 

    // if we have a photo before any geo data we can't do anything about it
    if ($geo == 0 || $dTime > 24 * 60 * 60)
    {
      echo '<td colspan=5>No Latitude data for '.formatDate($pDate)."</td></tr>\n";
      $photo++;
      continue;
    }

    geoLine($next);
    geoLine($prior);
    
    $dLat = $prior->latitude - $next->latitude;
    $dLong = $prior->longitude - $next->longitude;

    $multi = ($pDate - $prior->timestampMs / 1000) / $dTime; 
    $lat = $multi * $dLat + $prior->latitude;
    $long = $multi * $dLong + $prior->longitude;
    latLine($lat, $long, $pDate);

    if (array_key_exists('write', $_GET) && ($_GET['write'] == true))
    {
      $rsp = flickrCall(array(
            'method' => 'flickr.photos.addTags', 
            'photo_id' => $id, 
            'tags' => 'geotaggedfromlatitude'));
      if (statToMessage($rsp))
      {
        $rsp = flickrCall(array(
              'method' => 'flickr.photos.geo.setLocation', 
              'photo_id' => $id, 
              'lat' => $lat, 
              'lon' => $long));
        statToMessage($rsp);
      }
      else
        echo "<td>✗</td>";
    }
    else
      echo "<td>✗</td><td>✗</td>";

    echo "</tr>\n";
    $photo++;
    flush();
    if ($photo == count($photos))
    {
      exit("</table>");
    }
  }

}

function statToMessage($rsp)
{
  $p = unserialize($rsp);
  if ($p['stat'] == 'ok')
  {
    echo "<td>✓</td>";
    return true;
  }
  echo "<td>".$p['message']."</td>";
  return false;

}

function geoLine($loc)
{
  $lat = $loc->latitude;
  $long = $loc->longitude;
//  echo date('c', $loc->timestampMs / 1000)." ";
  latLine($lat, $long, $loc->timestampMs / 1000);
}

function formatDate($adate)
{
  return date('d M Y H:i', $adate);
}

function latLine($lat, $long, $desc)
{
  echo "<td><a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">".formatDate($desc)."</a></td>\n";
}

?>
