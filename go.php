<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');
include_once('common.php');

date_default_timezone_set($_POST['region'].'/'.$_POST['city']);

$maxGap = ($_POST['maxGap'] ?: DEF_MAX_GAP) * 60 * 60;

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
  $lastPoint = end($data);
  $last = $lastPoint[UTIME];
  if ($_POST['max-date'] == "")
    $_POST['max-date'] = date('Y-m-d', $first);
  if ($_POST['min-date'] == "")
    $_POST['min-date'] = date('Y-m-d', $last);
}

if ($_POST['criteriaChoice'] == '#flickrSet')
{
  $photos = getSet($_POST['set'], $statFile);
}
else
{
  $photos = getPhotos($_POST['count'], $_POST['max-date'], $_POST['min-date'], $_POST['tags'], $statFile);
}

if (!isset($data))
{
  $data = getLatPoints($statFile, $maxGap, $photos);
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

    // start processing photo
    $id = $photo['id'];
    $title = $photo['title'] ?: $id;
 
    // start table row
    echo "<tr><td>".($pos + 1)."</td><td><a href=\"http://www.flickr.com/photo.gne?id=$id\">$title</a></td>\n";

    // we have a photo before any geo data or after all geo data so skip
    if (($geo == 0) || ($geo == count($data)))
    {
      echo geoDataFail("No geo-data for ".formatDate($pDate));
      continue;
    }

    $next = $data[$geo];
    $prior = $data[$geo - 1];
    $dTime = ($prior[UTIME] - $next[UTIME]); 

    // we have a photo in a gap of > 24 hours of geo data, so skip photo
    if ($dTime > $maxGap)
    {
      echo geoDataFail("No geo-data for ".formatDate($pDate));
      continue;
    }

    // double check that photo doesn't have geo-data, I *have* seen FLickr returned geo-teagged photos to has_geo=0 searches!
    // See: http://tech.groups.yahoo.com/group/yws-flickr/message/7777
    if (($photo['latitude'] != 0) || ($photo['longitude'] != 0))
    {
      echo geoDataFail("Photo already has geo-data!");
      continue;
    }

    latCell($next);
    latCell($prior);
    
    // calculate location from proportion of difference in time
    $multi = ($pDate - $prior[UTIME]) / $dTime; 
    foreach(array(LATITUDE, LONGITUDE) as $ll)
    {
      $dll = $prior[$ll] - $next[$ll];
      $point[$ll] = $multi * $dll + $prior[$ll];
    }
    $point[UTIME] = $pDate;
    
    latCell($point);

    // write data to flickr, if we've been told to
    if (array_key_exists('write', $_POST) && ($_POST['write'] == true))
    {
      $p = round(100 * $pos / count($photos));
      writeProgress("Writing back to Flickr: $title", $p, $statFile);
      $rsp = flickrAddTags($id, 'geotaggedfromlatitude');
      // if the tagging fails, don't geo-tag
      if (statToMessage($rsp))
      {
        $rsp = flickrSetGeo($id, $point[LATITUDE], $point[LONGITUDE]);
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
  echo "<td><a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">".formatDate($loc[UTIME])."</a></td>\n";
}

function geoDataFail($msg)
{
  return "<td colspan=3>$msg</td><td>✗</td><td>✗</td></tr>\n";
}

?>
