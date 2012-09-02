<html>
<head>

   <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
   <script>
     $(document).ready(function(){
       $("#region").change(function(event){
         loadTimezones();
       });

function loadTimezones()
{
$("#timezone").load("timezones/" + $("#region").val());
}
         loadTimezones();
     });


   </script>

</head>
<body>
<h1>1. Set your timezone</h1>
<a href="http://www.flickr.com/services/api/misc.dates.html">Flickr does not record the timezone</a> that your camera is set to, so to maximise accuracy it is very important to get the timezone right. Choosing your exact location allows us to hopefully handle daylight saving. Note that this should match you camera setting, not just where you happen to be right now.
<br>

<select id="region">
<option>Africa</option>
<option>America</option>
<option>Antarctica</option>
<option>Asia</option>
<option>Atlantic</option>
<option selected="selected">Europe</option>
<option>Indian</option>
<option>Pacific</option>
</select>

<select id="timezone">
</select>

<?php

static $regions = array(
    'Africa' => DateTimeZone::AFRICA,
    'America' => DateTimeZone::AMERICA,
    'Antarctica' => DateTimeZone::ANTARCTICA,
    'Asia' => DateTimeZone::ASIA,
    'Atlantic' => DateTimeZone::ATLANTIC,
    'Europe' => DateTimeZone::EUROPE,
    'Indian' => DateTimeZone::INDIAN,
    'Pacific' => DateTimeZone::PACIFIC
);
/*
echo '<select id="region">';
$region_cookie = array_key_exists('region', $_COOKIE) ? $_COOKIE['region'] : 'Europe';
foreach ($regions as $name => $mask) {
echo "<option";
if ($name == $region_cookie)
        echo ' selected="selected" ';
echo ">$name</option>\n";
}
echo "<select>";
/* /
foreach ($regions as $name => $mask) {
    $s = "";
    foreach( DateTimeZone::listIdentifiers($mask) as $bah)
        $s .= '<option value="'.$bah.'">'.str_replace("_", " ", substr($bah, strpos($bah, '/') + 1))."</option>\n";
    file_put_contents("timezones/$name", $s);
}
// */
//print_r($tzlist);

?>

<select id="second-choice">
        <option>Please choose from above</option>
</select>

<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

$flickr = testFlickr();
$latitude = testLatitude();

if (!$flickr)
  echo '<a href="getFlickr.php">Authorise Flickr</a>';

echo "<br>";

if (!$latitude)
  echo '<a href="https://accounts.google.com/o/oauth2/auth?client_id=60961182481.apps.googleusercontent.com&redirect_uri=http://geo.kitten-x.com/gotLatitude.php&scope=https://www.googleapis.com/auth/latitude.all.best&response_type=code">Authorise Google Latitude</a>';

echo "<br>";

if ($flickr && $latitude)
  echo '<a href="go.php">Go</a>';

echo "<br>";

if ($flickr || $latitude)
  echo '<a href="disconnect.php">Disconnect</a>';


?>

</body>
</html>

