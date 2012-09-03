<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

if (!(testFlickr() && testLatitude()))
  header("Location: index.php");

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

//echo date('c')." BEFORE FLICKR<br>\n";
$fc = unserialize(flickrCall($fp));
//echo date('c')." AFTER FLICKR<br>\n";

flush();

$photos = $fc['photos']['photo'];

foreach($photos as &$p)
{
  $p['udatetaken'] = strtotime($p['datetaken']);
}

//print_r($photos);
usort($photos, function($a, $b) { 
    if ($a['udatetaken'] == $b['udatetaken']) 
      return 0; 
    return ($a['udatetaken'] < $b['udatetaken']) ? 1 : -1; 
  }); 
//print_r($photos);

//echo date('c')." AFTER FLICKR<br>\n";

$first = ($photos[0]['udatetaken'] + 24 * 60 * 60) * 1000;
$last = (end($photos)['udatetaken'] - 24 * 60 * 60) * 1000;

echo '<table class="table">';
echo "<tr><th>#</th><th>Flickr Photo</th><th>Prior Point</th><th>Next Point</th><th>Best Guess</th></tr>";
 $photo = 0;
 $locks= true;
 $count = 100;
 while (($count > 1) && ($locks !== FALSE))
{

//echo date('c')." BEFORE GOOGLE<br>\n";
  list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last");
//echo date('c')." $count AFTER GOOGLE<br>\n";

flush();

if ($count > 0)
{
    $first = end($locks->data->items)->timestampMs;
  }
  else
    break;
//  print_r($locks);
//  $count = 0;

// go through photos
$geo = 0;
while (($d = $photos[$photo]['udatetaken']) > $first / 1000)
{
  while (($geo < count($locks->data->items)) && ($d < $locks->data->items[$geo]->timestampMs / 1000))
  {
    $geo++;
  }
  // all geo points are before photo
  if ($d < $locks->data->items[$geo]->timestampMs / 1000)
    break;
  // if we have a photo before any geo data we can't do anything about it
  if ($geo == 0)
  {
    echo "No data for photo ".$photos[$photo]['datetaken']." ".$photos[$photo]['id']."<br>\n";
    $photo++;
    continue;
  }
//  echo date('c', $locks->data->items[$geo - 1]->timestampMs / 1000)."<br>\n";

  $prior = $locks->data->items[$geo - 1];
  $next = $locks->data->items[$geo];

  $id = $photos[$photo]['id'];
  $title = /*utf8_decode(*/$photos[$photo]['title'];//);
  if ($title == "")
    $title = $id;
  
  echo "<tr><td>".($photo + 1)."</td><td><a href=\"http://flickr.com/photos/jamiekitson/$id\">$title</a></td>\n";
  geoLine($prior);
  
//  echo $photos[$photo]['datetaken']." <a href=\"http://flickr.com/photos/jamiekitson/$id\">$title</a> $photo<br>\n";
//    $photos[$photo]['latitude']." ".$photos[$photo]['longitude']."<br>\n";
//  echo date('c', $locks->data->items[$geo]->timestampMs / 1000)."<br>\n"."<br>\n";
  geoLine($next);

  $dTime = ($prior->timestampMs - $next->timestampMs) / 1000; 
  $dLat = $prior->latitude - $next->latitude;
  $dLong = $prior->longitude - $next->longitude;

  $multi = ($photos[$photo]['udatetaken'] - $prior->timestampMs / 1000) / $dTime; 
  $lat = $multi * $dLat + $prior->latitude;
  $long = $multi * $dLong + $prior->longitude;
  latLine($lat, $long, strtotime($photos[$photo]['datetaken']));

  echo "</tr>\n";
  $photo++;
  flush();
  if ($photo == count($photos))
  {
//echo date('c')." END <br>\n";
echo "</table>";
    exit;
  }
}


}

function geoLine($loc)
{
  $lat = $loc->latitude;
  $long = $loc->longitude;
//  echo date('c', $loc->timestampMs / 1000)." ";
  latLine($lat, $long, $loc->timestampMs / 1000);
}

function latLine($lat, $long, $desc)
{
  $desc = date('M d H:i', $desc);
  echo "<td><a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">$desc</a></td>\n";

}

?>
