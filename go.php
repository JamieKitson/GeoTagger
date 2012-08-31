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

foreach ( $photos as $p)
{
echo $p['datetaken']."<br>\n";
$first = (strtotime($p['datetaken']) + /* 24 */ 10 * 60) * 1000;
$last = (strtotime($p['datetaken']) - /* 24 */ 10 * 60) * 1000;

// print_r($fc);

$count = 0;
while ($count < 2) 
{

  echo date('c', $last / 1000)."<br>\n";
 echo date('c', $first / 1000)."<br>\n";

$url = 'https://www.googleapis.com/latitude/v1/location?oauth_token='.$_COOKIE[GOOGLE_TOKEN]."&granularity=best&max-results=100&max-time=$first&min-time=$last";

echo $url."<br>\n";

$xmlresponse = file_get_contents($url);  

if ($xmlresponse !== FALSE)
{
  $locks = json_decode($xmlresponse);
  $count = count($locks->data->items);
}
elseif ($first - $last > 24 * 60 * 60 * 1000)
{
  break 2;
}

$first += 5 * 60 * 1000;
$last -= 5 * 60 * 1000;
}

if ($xmlresponse === FALSE)
  break;

//echo $xmlresponse;
//echo "\n\n<br><p>ab";


//print_r($locks);
//print_r($locks->data->items);

foreach($locks->data->items as $loc)
{
  echo date('c', $loc->timestampMs / 1000)."<br>\n";
}
echo "<p>\n";
}
?>
