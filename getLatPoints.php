<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once('googleCall.php');

// get the initial max/min dates, +- 24 hours
// $first = $_GET['max'] * 1000;
// $last = $_GET['min'] * 1000;

// $tmp = tempnam(sys_get_temp_dir(), "jam");
// $f = fopen($tmp, "c");

function getLatPoints($first, $last)
{

  if (!testLatitude())
    return 'Please re-'.googleAuthLink('').'.';

  $first *= 1000;
  $last *= 1000;
  $rsp = "";

  // loop through pages of google latitude points
  while (true)
  {

    list($locks, $count) = googleCall("max-results=1000&max-time=$first&min-time=$last".
        "&fields=items(timestampMs,latitude,longitude)");

    if (($count > 0) && ($locks !== false))
    {
      // max-time for next page of latitude data
      $first = end($locks->data->items)->timestampMs - 1;

      // go through latitude points
      foreach ($locks->data->items as $item)
      {
          $line = ($item->timestampMs / 1000)." ".$item->latitude." ".$item->longitude."\n";
          //fwrite($f, $line);
          $rsp .= $line;
      }
    }
    else
    {
      //break;
      return $rsp;
    }
  }
}

//fclose($f);
//echo $tmp;

?>
