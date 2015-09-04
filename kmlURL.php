<?php

include_once("common.php");
include("flickrCall.php");

function URLDate($adate)
{
    return 
        "!1i".date("Y", $adate).
        "!2i".(date("n", $adate) - 1).
        "!3i".date("j", $adate);
}


$flickrId = $_POST['flickrId'];
if ($flickrId == "")
    errorExit('Please re-'.flickrAuthLink(''));

$photos = getPhotos($_POST);

$maxGap = $_POST['maxGap'] * 3600;

$max = min($photos[0][UTIME] + $maxGap, time());
$min = (end($photos)[UTIME] - $maxGap);

$url = "https://www.google.com/maps/timeline/kml?authuser=0&pb=!1m8!1m3".URLDate($min)."!2m3".URLDate($max);

exit("<a href=\"$url\">$url</a>");

?>
