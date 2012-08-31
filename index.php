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
