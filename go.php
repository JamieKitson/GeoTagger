<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('getLatPoints.php');

// Field indexes
define("UTIME", 0);
define("LATITUDE", 1);
define("LONGITUDE", 2);

// test we're still authenticated with flickr 
if (!testFlickr())
  exit('<div class="alert alert-error">Please re-'.flickrAuthLink('').'.</div>');

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
  // record flickr user for prosperity
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

// get the initial max/min dates, +- 24 hours
$first = ($photos[0]['udatetaken'] + 24 * 60 * 60);
$last = (end($photos)['udatetaken'] - 24 * 60 * 60);

// start result table
echo '<table class="table">';
echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th>".
  "<th>Tag</th><th>Geo</th></tr>";

$photo = 0;   // number of current photo 

  $rsp = trim(getLatPoints($first, $last));
//  echo "rsp: $rsp <br>\n";
  $data = explode("\n", $rsp);

  if ($rsp == "")
  {
    $msg = 'No data for <strong>'.formatDate($last).'</strong> to <strong>'.formatDate($first).'</strong>.';
  }
  if (count($data) == 1)
  {
    // assuming this is a warning
    $msg = $data[0];
  }
  if ($msg != "")
  {
    echo "</table><div class=\"alert alert-error\">$msg</div>";
    exit;
  }

//echo "count: ".count($data)." <br>\n";

  $geo = 0; // latitude point index
  $next = explode(" ", $data[$geo]);

  // loop through photos
  foreach ($photos as $photo)
  {
    $pDate = $photo['udatetaken'];

    // go through latitude points
    while ($next[UTIME] < $pDate)
    {
      $geo++;
      if ($geo == count($data))
        exit("Ran out of geo points.");
      $next = explode(" ", $data[$geo]);
    }

    // start processing photo
    $id = $photo['id'];
    $title = $photo['title'] ?: $id;
    
    echo "<tr><td class=\"ids\"></td><td><a href=\"http://www.flickr.com/photo.gne?id=$id\">$title</a></td>\n";

    if ($geo > 0)
    {
      $prior = explode(" ", $data[$geo - 1]);
      $dTime = ($prior[UTIME] - $next[UTIME]); 
    }

    $msg = "";

    // either we have a photo before any geo data or we have a photo in a gap of > 24 hours of geo data, so skip photo
    if (($geo == 0) || ($dTime > 24 * 60 * 60))
      $msg = "No Latitude data for ".formatDate($pDate);

    // double check that photo doesn't have geo-data
    if (($photo['latitude'] != 0) || ($photo['longitude'] != 0))
      $msg = "Photo already has geo data!";

    if ($msg > "")
    {
      echo "<td colspan=5>$msg</td></tr>\n";
      $photo++;
      continue;
    }

    latCell($next);
    latCell($prior);
    
    $dLat = $prior[LATITUDE] - $next[LATITUDE];
    $dLong = $prior[LONGITUDE] - $next[LONGITUDE];

    // calculate location from proportion of difference in time
    $multi = ($pDate - $prior[UTIME]) / $dTime; 
    $lat = $multi * $dLat + $prior[LATITUDE];
    $long = $multi * $dLong + $prior[LONGITUDE];
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
    flush();

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
  $lat = $loc[LATITUDE];
  $long = $loc[LONGITUDE];
  geoCell($lat, $long, $loc[UTIME]);
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
