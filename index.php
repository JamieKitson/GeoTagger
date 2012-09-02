<!DOCTYPE html>
<html>
<head>
  <title>Google Latitude to Flickr Geo-Tagger</title>
  <meta charset="UTF-8">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script>
    $(document).ready(function(){

      $("#region").val(getCookie('region', 'Europe'));
      $("#criteria").val(getCookie('criteria', ''));

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

<h1>0. About</h1>
This app will attempt to automatically geo-tag your Flickr photos using your
Google Latitude data. The exact location will be estimated by using the
proportion of time passed between the two points either side of the time that
the photo was taken. This app will never overwrite existing location data and
will add a tag to any photos that it geo-tags, this might see like spam, but is
there to help you in case anything should go wrong and cannot be disabled. You
can add search criteria in step 3 such as tags, sets, etc. By default this app
will not write data back to Flickr but will show you what it will try to do and
why.
<form action="go.php">
<h1>1. Set your timezone</h1>
<a href="http://www.flickr.com/services/api/misc.dates.html">Flickr does not
record the timezone</a> that your camera is set to, so to maximise accuracy it
is very important to get the timezone right. Choosing your exact location
allows us to hopefully handle daylight saving. Note that this should match you
camera setting, not just where you happen to be right now.  <br>

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

<h1>2. Authenticate Google and Flickr</h1>
The respective tokens will be held only as cookies in your browser and can be
removed at any time by clicking the Disconnect button. The Goolge token has a
fairly short life, so don't be suprised if you need to reauthenticate
regularly.  <br>
<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

$flickr = testFlickr();
$latitude = testLatitude();

if (!$flickr)
  echo '<a href="getFlickr.php">Authorise Flickr</a>';

if (!$latitude)
  echo '<a href="https://accounts.google.com/o/oauth2/auth?client_id=60961182481.apps.googleusercontent.com&amp;redirect_uri=http://geo.kitten-x.com/gotLatitude.php&amp;scope=https://www.googleapis.com/auth/latitude.all.best&amp;response_type=code">Authorise Google Latitude</a>';

if ($flickr || $latitude)
  echo '<a href="disconnect.php">Disconnect</a>';

?>
<h1>3. Flickr search criteria (optional)</h1>
Here you can add any criteria that exist for 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html">flickr.photos.search</a></code>
to restrict the photos that will be geo-tagged. This will probably be
by <code>tags</code>. Put each criteria on its own line and separate name and value with an
equals sign as in the example below. You can also change the 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html#yui_3_5_1_1_1346613172850_475">sort</a></code>
order, for example if you wanted to start by tagging your oldest photos first, rather than your newest photos. 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html#yui_3_5_1_1_1346613172850_465">privacy_filter</a></code>
could also be useful if you don't want to geo-tag public photos, see the
previous link for possible values. Note that you cannot override <code>has_geo</code>, this
app will never overwrite existing location data. Unfortunately
<code>flickr.photos.search</code> does not seem to have a sets setting.
<pre>
tags=mongolia,mongolrally
privacy_filter=4
sort=date-posted-asc
</pre>
<textarea rows=5 cols=40 id="criteria"></textarea>

<h1>4. Go</h1>
This app will tag a maximum 500 photos at a time.
<label>Number of pictures to tag<input type="text" name="count" value="10"></label><br>
<label><input type="checkbox" name="write" value="true">Write data to Flickr</label><br>

<?php

if ($flickr && $latitude)
  echo '<input type="submit" value="Go">';


?>
</form>
</body>
</html>

