<?php

// Might need to alter header

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

$flickr = testFlickr();
$latitude = testLatitude();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Google Latitude to Flickr Geo-Tagger</title>
  <meta charset="UTF-8">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <style> #lblCount { display: inline } </style>
  <script src="bootstrap/js/bootstrap.min.js"></script>
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

      $('form').submit(function() {

        $('#gobtn').attr('disabled', 'disabled');

        $.ajax({
          url: "go.php?" + $(this).serialize(),
          context: document.body
        }).done(function( data ) { 
          $('#result').append(data);
          $('#gobtn').removeAttr('disabled');
          if ($('#result').has('table').is('table'))
            window.onbeforeunload = function(e) { 
              return 'Leaving this page will clear your results table.'; 
          };
        });

        return false;
      });


      /*
      function saveValue(id) {
        setCookie(id, $("#" + id).val(), 100);
      }

      function getValue(id, def) {
        getCookie(id, $("#" + id).val(), def);
      } 
      */

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
<form class="container">

<h1 class="page-header">0. About</h1>
<p>
This app will attempt to automatically geo-tag your Flickr photos using your
Google Latitude data. The exact location will be estimated by using the
proportion of time passed between the two points either side of the time that
the photo was taken. This app will never overwrite existing location data and
will add a tag to any photos that it geo-tags, this might see like spam, but is
there to help you in case anything should go wrong and hence cannot be disabled. You
can add search criteria in step 3 such as tags, sets, etc. By default this app
will not write data back to Flickr but will show you what it will try to do and
why.
</p>

<h1 class="page-header" id="settz">1. Set your time zone</h1>
<p>
<a href="http://www.flickr.com/services/api/misc.dates.html">Flickr does not
record the time zone</a> that your camera is set to, so to maximise accuracy it
is very important to get the time zone right. Choosing your exact location
allows us to hopefully handle daylight saving correctly. Note that this should match you
camera setting, not just where you happen to be right now. Yes, I could attempt
to get the time zone from the EXIF data, but from a brief and unscientific 
survey it seems that this doesn't show up in Flickr for many cameras, my D7000
being one of the few that does.
</p>
<select id="region" name="region">
<option>Africa</option>
<option>America</option>
<option>Antarctica</option>
<option>Arctic</option>
<option>Asia</option>
<option>Atlantic</option>
<option>Australia</option>
<option>Europe</option>
<option>Indian</option>
<option>Pacific</option>
</select>

<select id="timezone" name="timezone">
</select>

<h1 class="page-header" id="auth">2. Authenticate Google and Flickr</h1>
<p>
The respective tokens will be held only as cookies in your browser and can be
removed at any time by clicking the Disconnect button. The Goolge token has a
fairly short life, so don't be surprised if you need to re-authenticate
regularly.
</p>
<p>
<?php

if (!$flickr)
  echo '<a class="btn btn-primary" href="getFlickr.php">Authorise Flickr</a>'."\n";

if (!$latitude)
  echo '<a class="btn btn-primary" href="https://accounts.google.com/o/oauth2/auth?client_id=60961182481.apps.googleusercontent.com&amp;redirect_uri='.baseHttpPath().'gotLatitude.php&amp;scope=https://www.googleapis.com/auth/latitude.all.best&amp;response_type=code">Authorise Google Latitude</a>'."\n";

if ($flickr || $latitude)
  echo '<a class="btn" href="disconnect.php">Disconnect</a>'."\n";

?>
</p>

<h1 class="page-header">3. Flickr search criteria (optional)</h1>
<P>
Here you can add any criteria that exist for 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html">flickr.photos.search</a></code>
to restrict the photos that will be geo-tagged. This will probably be
by <code>tags</code>. Put each criteria on its own line and separate name and value with an
equals sign as in the example below. You can also change the 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html#yui_3_5_1_1_1346613172850_475">sort</a></code>
order, for example if you wanted to start by tagging your oldest photos first, rather than your newest photos, ie, defaults to <code>date-taken-desc</code>. 
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
<textarea rows=5 cols=40 name="criteria"></textarea>

<h1 class="page-header" id="go">4. Go</h1>
<p>
This app will geo-tag a maximum 500 photos at a time and will not write the
data back to Flickr unless you check the checkbox below. This app can be quite
slow, especially when writing back to Flickr. The biggest factor is the
duration that the pictures were taken over, so for example 100 photos taken
over an hour may be processed quicker than 10 photos taken over a week.
</p>
<p>
<label class="control-label" for="count" id="lblCount">Number of pictures to tag: </label>
<input type="text" name="count" id="count" value="10">
<label class="checkbox">Write data to Flickr<input type="checkbox" name="write" value="true"></label>
</p>

<p>
<input type="submit" value="Go" class="btn btn-primary btn-large" id="gobtn"<?php

if (!($flickr && $latitude))
  echo ' disabled="disabled" ';

?>>
</p>

<h1 class="page-header">5. Results</h1>
<p>
The results will be presented here as a table, mainly of links. The Prior and
Next links display the times closest to the time that the photo was taken and
link to those two points on Google Maps. The Best Guess column shows the time
at which the photo was taken and links to the point on Google Maps where the
photo will be geo-tagged.
</p>
<div id="result"></div>

<h1 class="page-header" id="alt">6. Alternatives</h1>
<ul>
<li><a href="http://latitude2flickr.be-q.com/">Latitude2Flickr</a> has a very nice
interface, but I don't think does anything automatically, or even in batches.
It also dynamically loads all of your photos so can be quite slow if you have a
lot of photos and is a little buggy (currently it's returning an error on
Google authentication). 
<li>A few desktop non-Flickr apps are discussed in 
<a href="http://www.quora.com/Is-there-a-way-to-geotag-photos-using-Google-Latitude-history">this thread</a>.
</ul>

<h1 class="page-header" id="source">7. Source code</h1>
<a href="https://github.com/JamieKitson/GeoTagger">On GitHub</a>. This is also the place to 
<a href="https://github.com/JamieKitson/GeoTagger/issues">report bugs</a>.
</form>
</body>
</html>

