<html>
<head>

  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script>
    $(document).ready(function(){

      $("#region").val(getCookie('region', 'Europe'));

      function loadTimezones() {
        $("#timezone").load("timezones/" + $("#region").val(), function() { 
          $("#timezone").val(getCookie('timezone', 'London')); 
        });
      }

      loadTimezones();

      $("#region").change(function(event){
        setCookie('region', $("#region").val(), 100);
        loadTimezones();
      });

      $("#timezone").change(function(event){
        setCookie('timezone', $("#timezone").val(), 100);
      });

    });

function setCookie(c_name,value,exdays)
{
  var exdate=new Date();
  exdate.setDate(exdate.getDate() + exdays);
  var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
  document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name, def)
{
  var i,x,y,ARRcookies=document.cookie.split(";");
  for (i=0;i<ARRcookies.length;i++)
  {
    x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
    y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
    x=x.replace(/^\s+|\s+$/g,"");
    if (x==c_name)
      {
      return unescape(y);
      }
    }
  return def;
}

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
<option>Europe</option>
<option>Indian</option>
<option>Pacific</option>
</select>

<select id="timezone">
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

