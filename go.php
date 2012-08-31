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
foreach ( $photos as $p)
{
echo $p['datetaken']."<br>\n";
*/ 

$first = (strtotime($photos[0]['datetaken']) + 24 * 60 * 60) * 1000;
$last = (strtotime(end($photos)['datetaken']) - 24 * 60 * 60) * 1000;

// print_r($fc);
/*
$count = 0;
while ($count < 2) 
{
*/ 

$opts = array(
      'http'=>array(
      'method'=>"GET",
      'header'=>"Accept-Encoding: gzip\r\n" .
                "User-Agent: my program (gzip)\r\n"
                                )
    );

$context = stream_context_create($opts);

//  echo date('c', $last / 1000)."<br>\n";
// echo date('c', $first / 1000)."<br>\n";

 $photo = 0;
 $xmlresponse = true;
 $count = 100;
 while (($count > 1) && ($xmlresponse !== FALSE))
{
$url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN]."&granularity=best&max-results=1000&max-time=$first&min-time=$last";

//echo $url."<br>\n";

$xmlresponse = gzdecode(file_get_contents($url, false, $context));  
//print_r($xmlresponse);
  $locks = json_decode($xmlresponse);

if ($xmlresponse !== FALSE)
{
  $locks = json_decode($xmlresponse);
  if (property_exists($locks->data, 'items'))
  {
    $count = count($locks->data->items);
    $first = end($locks->data->items)->timestampMs;
//    echo date('c', $first / 1000)." FIRST<br>\n";
  }
  else
    break;
//  print_r($locks);
//  $count = 0;
}
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


/*
elseif ($first - $last > 24 * 60 * 60 * 1000)
{
  break 2;
}

$first += 5 * 60 * 1000;
$last -= 5 * 60 * 1000;
}

if ($xmlresponse === FALSE)
  break;
*/
//echo $xmlresponse;
//echo "\n\n<br><p>ab";


//print_r($locks);
//print_r($locks->data->items);
/*
foreach($locks->data->items as $loc)
{
  echo date('c', $loc->timestampMs / 1000)." ".$loc->latitude." ".$loc->longitude." "."<br>\n";
} */
//echo "<p>\n";
}

function geoLine($loc)
{
  echo date('c', $loc->timestampMs / 1000)." ".$loc->latitude." ".$loc->longitude." "."<br>\n";
}

?>
