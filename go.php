<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');
include_once('common.php');

date_default_timezone_set($_POST['region'].'/'.$_POST['city']);

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

$photos = getPhotos($_POST['count'], $_POST['criteria']);

if (!isset($data))
{
  $data = getLatPoints($statFile, $_POST['latAccuracy'], $maxGap, $photos);
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
      echo "<td colspan=3>$msg</td><td>✗</td><td>✗</td></tr>\n";
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


?>
