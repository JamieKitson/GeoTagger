<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

if (!(testFlickr() && testLatitude()))
  header("Location: index.php");

$fp = array(
    
    'sort' => 'date-taken-desc',
    'has_geo' => 0,
    'user_id' => 'me',
    'per_page' => 50,
    'method'   => 'flickr.photos.search',
    'extras'   => 'date_taken,geo'
    
    );
echo date('c')." BEFORE FLICKR<br>\n";
$fc = unserialize(flickrCall($fp));
echo date('c')." AFTER FLICKR<br>\n";

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

echo date('c')." AFTER FLICKR<br>\n";

$first = ($photos[0]['udatetaken'] + 24 * 60 * 60) * 1000;
$last = (end($photos)['udatetaken'] - 24 * 60 * 60) * 1000;

 $photo = 0;
 $locks= true;
 $count = 100;
 while (($count > 1) && ($locks !== FALSE))
{

echo date('c')." BEFORE GOOGLE<br>\n";
  list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last");
echo date('c')." $count AFTER GOOGLE<br>\n";

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
  geoLine($locks->data->items[$geo - 1]);
  $id = $photos[$photo]['id'];
  echo $photos[$photo]['datetaken']." <a href=\"http://flickr.com/photos/jamiekitson/$id\">$id</a><br>\n";
//    $photos[$photo]['latitude']." ".$photos[$photo]['longitude']."<br>\n";
//  echo date('c', $locks->data->items[$geo]->timestampMs / 1000)."<br>\n"."<br>\n";
  geoLine($locks->data->items[$geo]);
  echo "<br>\n";
  $photo++;
  flush();
  if ($photo == count($photos))
  {
echo date('c')." END <br>\n";
    exit;
  }
}


}

function geoLine($loc)
{
  $lat = $loc->latitude;
  $long = $loc->longitude;
  echo date('c', $loc->timestampMs / 1000)." <a href=\"http://maps.google.co.uk/maps?q=$lat,$long\">$lat $long</a><br>\n";
}

?>
