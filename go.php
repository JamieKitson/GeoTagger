<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

// test we're still authenticated with flickr and google
if (!(testFlickr() && testLatitude()))
  exit('<div class="alert alert-error">Please re-'.googleAuthLink('').'.</div>');

// set user adjustable flickr settings
$fp = array(
    'sort' => 'date-taken-desc',
    'per_page' => $_GET['count'],
    );

// get user settings for flickr
foreach(explode("\n", $_GET['criteria']) as $a)
{
  $q = explode('=', $a);
  if (count($q) == 2)
    $fp[$q[0]] = $q[1];
}

// set non user adjustable flickr settings
$fp['user_id'] = 'me';
$fp['has_geo'] = 0;
$fp['method'] = 'flickr.photos.search';
$fp['extras'] = 'date_taken,geo';

$fc = unserialize(flickrCall($fp));

// The flickrCall above will use the local time zone, 
// but from now on we should use the user's time zone
date_default_timezone_set($_GET['region'].'/'.$_GET['timezone']);

$photos = $fc['photos']['photo'];

// bail if we've got no photos
if (count($photos) == 0)
{
  exit('<div class="alert alert-error">No Flickr photos found.</div>');
}
else
{
  // else record flickr user for prosperity
  touch('stats/'.str_replace('@', '_', $photos[0]['owner']));
}

// do expensive str date processing just once
foreach($photos as &$p)
{
  $p['udatetaken'] = strtotime($p['datetaken']);
}

// sort photos by date taken in case flickr has fucked up or the user has overridden sort
usort($photos, function($a, $b) { 
    if ($a['udatetaken'] == $b['udatetaken']) 
      return 0; 
    return ($a['udatetaken'] < $b['udatetaken']) ? 1 : -1; 
  }); 

// get the initial max/min dates
$first = ($photos[0]['udatetaken'] + 24 * 60 * 60) * 1000;
$last = (end($photos)['udatetaken'] - 24 * 60 * 60) * 1000;

// start result table
echo '<table class="table">';
echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th>".
  "<th>Tag</th><th>Geo</th></tr>";

$photo = 0;   // number of current photo 
$locks= true; // location points returned by google
$count = 100; // number of returned latitude points

while (($count > 0) && ($locks !== false))
{

  list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last".
      "&fields=items(timestampMs,latitude,longitude)");

  if ($count > 0)
  {
    // max-time for next page of latitude data
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
  while (($photo < count($photos)) && (($pDate = $photos[$photo]['udatetaken']) > $first / 1000))
  {
    // go through latitude points
    while (($geo < count($locks->data->items)) && ($pDate < $locks->data->items[$geo]->timestampMs / 1000))
    {
      $geo++;
    }

    // all geo points are before photo, get next points
    if ($pDate < $locks->data->items[$geo]->timestampMs / 1000)
      break;

    // start processing photo
    $id = $photos[$photo]['id'];
    $title = /*utf8_decode(*/$photos[$photo]['title'];//);
    if ($title == "")
      $title = $id;
    
    echo "<tr><td>".($photo + 1)."</td><td><a href=\"http://flickr.com/photos/".$photos[$photo]['owner']."/$id\">$title</a></td>\n";

    if ($geo > 0)
    {
      $prior = $locks->data->items[$geo - 1];
      $next = $locks->data->items[$geo];
      $dTime = ($prior->timestampMs - $next->timestampMs) / 1000; 
    }

    // either we have a photo before any geo data or we have a photo in a gap of > 24 hours of geo data, so skip photo
    if (($geo == 0) || ($dTime > 24 * 60 * 60))
    {
      echo '<td colspan=5>No Latitude data for '.formatDate($pDate)."</td></tr>\n";
      $photo++;
      continue;
    }

    latCell($next);
    latCell($prior);
    
    $dLat = $prior->latitude - $next->latitude;
    $dLong = $prior->longitude - $next->longitude;

    // calculate location from proportion of difference in time
    $multi = ($pDate - $prior->timestampMs / 1000) / $dTime; 
    $lat = $multi * $dLat + $prior->latitude;
    $long = $multi * $dLong + $prior->longitude;
    geoCell($lat, $long, $pDate);

    // write data to flickr, if we've been told to
    if (array_key_exists('write', $_GET) && ($_GET['write'] == true))
    {
      $rsp = flickrCall(array(
            'method' => 'flickr.photos.addTags', 
            'photo_id' => $id, 
            'tags' => 'geotaggedfromlatitude'));
      // if the tagging fails, don't geo-tag
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

    // complete row
    echo "</tr>\n";
    $photo++;
    flush();

    // don't bother continuing when we reach the end of the photos
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

function latCell($loc)
{
  $lat = $loc->latitude;
  $long = $loc->longitude;
  geoCell($lat, $long, $loc->timestampMs / 1000);
}

function formatDate($adate)
{
  return date('d M Y H:i', $adate);
}

function geoCell($lat, $long, $desc)
{
  echo "<td><a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">".formatDate($desc)."</a></td>\n";
}

?>
