<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

// Field indexes
define("UTIME", 0);
define("LATITUDE", 1);
define("LONGITUDE", 2);

date_default_timezone_set($_GET['region'].'/'.$_GET['timezone']);

$flickrId = $_GET['flickrId'];
if ($flickrId == "")
  errorExit('Please re-'.flickrAuthLink(''));
$statFile = "stats/$flickrId";
writeStat("Getting photos from Flickr.", $statFile);

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

if ($fc['stat'] != 'ok')
  errorExit('Please re-'.flickrAuthLink(''));

$photos = $fc['photos']['photo'];

// bail if we've got no photos
if (count($photos) == 0)
{
  errorExit('No Flickr photos found.');
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

  $data = getLatPoints($first, $last, $statFile);

  // returned a warning if not an array
  if (!is_array($data))
    errorExit($data);

  writeStat("Processing geo-data.", $statFile);

  // start result table
  echo "<table class=\"table\">\n";
  echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th><th>Tag</th><th>Geo</th></tr>\n";

  $geo = 0; // latitude point index

  // loop through photos
  foreach ($photos as $pos => $photo)
  {
    $pDate = $photo['udatetaken'];

    // go through latitude points
    while (($geo < count($data)) && ($pDate < $data[$geo][UTIME]))
    {
      $geo++;
    }
    if ($geo < count($data))
      $next = $data[$geo];

    // start processing photo
    $id = $photo['id'];
    $title = $photo['title'] ?: $id;
    
    echo "<tr><td>".($pos + 1)."</td><td><a href=\"http://www.flickr.com/photo.gne?id=$id\">$title</a></td>\n";

    if ($geo > 0)
    {
      $prior = $data[$geo - 1];
      $dTime = ($prior[UTIME] - $next[UTIME]); 
    }

    $msg = "";

    // either we have a photo before any geo data or we have a photo in a gap of > 24 hours of geo data, so skip photo
    if (($geo == 0) || ($dTime > 24 * 60 * 60) || ($geo == count($data)))
      $msg = "No geo-data for ".formatDate($pDate);

    // double check that photo doesn't have geo-data, this can happen if a search is done very soon after it's set
    if (($photo['latitude'] != 0) || ($photo['longitude'] != 0))
      $msg = "Photo already has geo-data!";

    if ($msg > "")
    {
      echo "<td colspan=5>$msg</td></tr>\n";
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
      writeStat("Writing back to Flickr photo <strong>".($pos + 1)."</strong>.", $statFile);
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

echo "</table>";
writeStat("Finished.", $statFile);

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

function geoCell($lat, $long, $desc)
{
  echo "<td><a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">".formatDate($desc)."</a></td>\n";
}

function errorExit($msg)
{
  echo "<div class=\"alert alert-error\">$msg</div>";
  exit;
}

?>
