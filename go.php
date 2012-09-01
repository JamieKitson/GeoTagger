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
    'per_page' => 10,
    'method'   => 'flickr.photos.search',
    'extras'   => 'date_taken,geo'
    
    );

$fc = unserialize(flickrCall($fp));

$photos = $fc['photos']['photo'];

/*
print_r($photos);
usort($photos, function($a, $b) {$a['datetaken'] - $b['datetaken'];}); 
print_r($photos);
*/

$first = (strtotime($photos[0]['datetaken']) + 24 * 60 * 60) * 1000;
$last = (strtotime(end($photos)['datetaken']) - 24 * 60 * 60) * 1000;

 $photo = 0;
 $locks= true;
 $count = 100;
 while (($count > 1) && ($locks !== FALSE))
{

  list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last");

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
while (($d = strtotime($photos[$photo]['datetaken'])) > $first / 1000)
{
  while (($geo < count($locks->data->items)) && ($d < $locks->data->items[$geo]->timestampMs / 1000))
  {
    $geo++;
  }
  if ($d < $locks->data->items[$geo]->timestampMs / 1000)
    break;
//  echo date('c', $locks->data->items[$geo - 1]->timestampMs / 1000)."<br>\n";
  geoLine($locks->data->items[$geo - 1]);
  echo $photos[$photo]['datetaken']." ".$photos[$photo]['id']."<br>\n";
//  echo date('c', $locks->data->items[$geo]->timestampMs / 1000)."<br>\n"."<br>\n";
  geoLine($locks->data->items[$geo]);
  echo "<br>\n";
  $photo++;
  if ($photo == count($photos))
    exit;
}


}

function geoLine($loc)
{
  echo date('c', $loc->timestampMs / 1000)." ".$loc->latitude." ".$loc->longitude." "."<br>\n";
}

?>
