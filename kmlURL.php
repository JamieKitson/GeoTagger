<?php

include_once("common.php");
include("flickrCall.php");

$flickrId = $_POST['flickrId'];
if ($flickrId == "")
    errorExit('Please re-'.flickrAuthLink(''));

$photos = getPhotos($_POST);

$maxGap = $_POST['maxGap'] * 3600;

$max = min($photos[0][UTIME] + $maxGap, time()) * 1000;
$min = (end($photos)[UTIME] - $maxGap) * 1000;

$url = "https://maps.google.com/locationhistory/b/0/kml?startTime=$min&endTime=$max";

exit("<a href=\"$url\">$url</a>");

?>
