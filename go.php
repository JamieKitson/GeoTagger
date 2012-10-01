<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

// Field indexes
define("UTIME", 0);
define("LATITUDE", 1);
define("LONGITUDE", 2);

date_default_timezone_set($_POST['region'].'/'.$_POST['timezone']);

$maxGap = $_POST['maxGap'];

$flickrId = $_POST['flickrId'];
if ($flickrId == "")
  errorExit('Please re-'.flickrAuthLink(''));
$statFile = "stats/$flickrId";

writeStat("Starting...", $statFile);

if (array_key_exists('input', $_FILES))
  $sData = file($_FILES['input']['tmp_name']);
elseif (!array_key_exists('input', $_POST))
  errorExit("No input data found.");
elseif ($_POST['input'] != 'google')
  $sData = explode("\n", $_POST['input']);

if (isset($sData))
{
  writeStat("Sorting data.", $statFile);
  $data = array();
  foreach($sData as $line)
  {
    $aline = explode(" ", $line);
    if ((count($aline) >= 3) && (($aline[UTIME] = strtotime($aline[UTIME])) !== false))
      $data[] = $aline;
  }
  sort_array_by_utime($data);
  $first = $data[0][UTIME];
  $last = end($data)[UTIME];
  $_POST['criteria'] .= "\nmax_taken_date=$first\nmin_taken_date=$last";
}

writeStat("Getting photos from Flickr.", $statFile);

// set user adjustable flickr parameters
$fp = array(
    'sort' => 'date-taken-desc',
    'per_page' => $_POST['count'],
    );

// get user set flickr parameters
foreach(explode("\n", $_POST['criteria']) as $line)
{
  $q = explode('=', $line);
  if (count($q) == 2)
    $fp[$q[0]] = $q[1];
}

// set non user adjustable flickr parameters
$fp['user_id'] = 'me';
$fp['has_geo'] = 0;
$fp['method'] = 'flickr.photos.search';
$fp['extras'] = 'date_taken,geo';

$rsp = flickrCall($fp);
$fc = unserialize($rsp);

if ($fc['stat'] != 'ok')
{
  if (is_array($fc) && array_key_exists('message', $fc))
  {
    $msg = $fc['message'];
  }
  else
  {
    $msg = $rsp;
  }
  errorExit('Flickr call failed with: '.$msg); // , you may need to re-'.flickrAuthLink(''));
}

$photos = $fc['photos']['photo'];

// bail if we've got no photos
if (count($photos) == 0)
{
  errorExit('No Flickr photos found.');
}

// do expensive str date processing just once
foreach($photos as &$p)
{
  $p[UTIME] = strtotime($p['datetaken']);
}

// sort photos by date taken in case flickr has fucked up or the user has overridden sort
sort_array_by_utime($photos);

if (!isset($data))
{
  $data = getLatPoints($statFile, $_POST['latAccuracy'], $maxGap, $photos);

  // returned a warning if not an array
  if (!is_array($data))
    errorExit($data);

}

  writeStat("Processing geo-data.", $statFile);

  // start result table
  echo "<table class=\"table\">\n";
  echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th><th>Tag</th><th>Geo</th></tr>\n";

  $geo = 0; // latitude point index

  // loop through photos
  foreach ($photos as $pos => $photo)
  {
    $pDate = $photo[UTIME];

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
    if (($geo == 0) || ($dTime > $maxGap * 60 * 60) || ($geo == count($data)))
      $msg = "No geo-data for ".formatDate($pDate);

    // double check that photo doesn't have geo-data, I *have* seen FLickr returned geo-teagged photos to has_geo=0 searches!
    // See: http://tech.groups.yahoo.com/group/yws-flickr/message/7777
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
    if (array_key_exists('write', $_POST) && ($_POST['write'] == true))
    {
      writeStat("<p>Writing back to Flickr: $title".'</p><div class="progress"><div class="bar" style="width: '.round(100 * $pos / count($photos)).'%;"></div></div>', $statFile);
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

function sort_array_by_utime(&$anArray)
{
  usort($anArray, function($a, $b) { 
    if ($a[UTIME] == $b[UTIME]) 
      return 0; 
    return ($a[UTIME] < $b[UTIME]) ? 1 : -1; 
  });
}

?>
