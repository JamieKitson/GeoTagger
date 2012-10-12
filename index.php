<?php

// Might need to alter header

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');
include_once('common.php');

$flickrId = testFlickr();
$latitude = testLatitude();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Geo-Tag Flickr photos using Google Latitude data</title>
  <meta charset="UTF-8">
  <meta name="description" content="Geo-tag your Flickr photos using your location history from Google Latitude">
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="http://code.jquery.com/ui/1.9.0/themes/base/jquery-ui.css" />
  <script src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
  <script src="http://code.jquery.com/ui/1.9.0/jquery-ui.js"></script>
  <style>
    form.form-horizontal label.control-label { width: auto; margin-right: 0.5em; }
    input.number {text-align: right }
    textarea { width: 40em }
    body { counter-reset: headings -1 }
    h1:before { content: counter(headings) ". "; counter-increment: headings; }
    #loading, #stat { display: none; }
    input#fakeFile { cursor: auto }
    .tab-content { border: 1px solid #DDDDDD; border-top: none; padding: 20px }
    #myTab { margin-bottom: 0 }
    #fileChoice .alert { margin-bottom: 10px }
    .tab-pane { margin-bottom: 1em; }
  </style>
  <script src="bootstrap/js/bootstrap.min.js"></script>
  <script src="geo.js"></script>
  <script>
    var latitude = <?php echo $latitude ? 'true' : 'false'; ?>;
    var flickrId = '<?php echo $flickrId; ?>';
    function progressBar(msg, p) {
      return '<?php echo progressBar("' + msg + '", "' + p + '"); ?>';
    }
  </script>
</head>
<body>

<form class="container form-horizontal" enctype="multipart/form-data">
<input type="hidden" name="flickrId" id="flickrId" value="<?php echo $flickrId; ?>">

<div class="alert alert-block alert-error" id="apiWarning" style="display: none">
  <button class="close" type="button">×</button>
  <strong>Please note</strong> that the Flickr API can be unreliable. Fewer photos than 
  requested may be returned, occasionally none at all. If this happens please try again.
  See the <a href="http://tech.groups.yahoo.com/group/yws-flickr/">Yahoo Flickr API Group</a> 
  and <a href="http://www.flickr.com/groups/api/">Flickr API Group</a> for 
  <a href="http://tech.groups.yahoo.com/group/yws-flickr/message/7809">various</a> 
  <a href="http://www.flickr.com/groups/api/discuss/72157631638748470/">discussions</a>.
</div>

<h1 class="page-header">About</h1>
<p>
This app will attempt to automatically geo-tag your Flickr photos using your
Google Latitude or text data. The exact location will be estimated by using the
proportion of time passed between the two points either side of the time that
the photo was taken, to a maximum of 24 hours. This app will never overwrite existing location data and
will tag any photos that it geo-tags with <code>geotaggedfromlatitude</code>, this might seem like spam, but is
there to help you in case anything should go wrong, and hence cannot be disabled. You
can add search criteria in step 3 such as tags, dates, etc. By default this app
will not write data back to Flickr but will show you what it will try to do and
why.
</p>

<h1 class="page-header" id="settz">Set your time zone</h1>
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
<option selected="selected">Europe</option>
<option>Indian</option>
<option>Pacific</option>
</select>

<select id="city" name="city">
</select>

<h1 class="page-header" id="flickr">Authenticate Flickr</h1>
<p>
Flickr tokens will be held only as cookies in your browser and can be
removed at any time by clicking the Disconnect button. 
</p>
<p>
<?php

if ($flickrId === false)
  echo flickrAuthLink("btn btn-primary")."\n";
else
  echo '<a class="btn" href="disconnect.php?s=flickr">Disconnect</a>'."\n";

?>
</p>

<h1 class="page-header" id="google">Choose your input method</h1>

<ul class="nav nav-tabs" id="myTab">
<li class="active"><a href="#latChoice">Google Latitude</a></li>
<li><a href="#fileChoice">Text File</a></li>
<li><a href="#txtChoice">Text</a></li>
</ul>
   
<div class="tab-content" id="myTab-content">
  <div class="tab-pane active" id="latChoice">
  <p>
  Google tokens will be held only as cookies in your browser and can be
  removed at any time by clicking the Disconnect button. The Goolge token has a
  fairly short life, so don't be surprised if you need to re-authenticate
  regularly.
  <input type="hidden" value="google" class="geoinput" name="">
  </p>
  <p>
  <label class="control-label" for="latAccuracy">Ignore Latitude points less than accurate than:</label>
  <span class="input-append"><input type="text" name="latAccuracy" id="latAccuracy" class="input-mini number" value="100"><span class="add-on">metres</span></span>
  </p>
  <?php

  if (!$latitude)
    echo googleAuthLink("btn btn-primary")."\n";
  else
    echo '<a class="btn" href="disconnect.php?s=google">Disconnect</a>'."\n";

  ?>
  </div>
  <div class="tab-pane" id="fileChoice">
    <div class="alert alert-error">
      <strong>Error:</strong> Your browser is not compatible with uploading files as 
      it does not support the FormData type. See <a href="http://caniuse.com/xhr2">this page</a>
      for a list of supported browsers. Please try the text input on the next tab.
    </div>
    <p>
    Files must consist of lines of three space delimited fields containing a  
    time stamp, latitude and longitude. <strong>Note</strong> that the Flickr search 
    will be adjusted to search only within the data supplied, ie, no photos will be 
    returned which were taken outside the time stamps supplied in the geo-data file.
    </p>
    <input id="inputFile" type="file" style="display:none" name="" class="geoinput">
    <div class="input-append">
       <input id="fakeFile" class="input-large" type="text" readonly="readonly">
       <a class="btn" id="browseInput">Browse</a>
    </div>
  </div>
  <div class="tab-pane" id="txtChoice">
    <p>
    Text must consist of lines of three space delimited fields containing a unix 
    time stamp, latitude and longitude. <strong>Note</strong> that the Flickr search 
    will be adjusted to search only within the data supplied, ie, no photos will be 
    returned which were taken outside the time stamps supplied in the geo-data file.
    </p>
    <textarea id="inputText" name="" class="geoinput"></textarea>
  </div>

  <label class="control-label" for="maxGap">Ignore photos in gaps of more than:</label>
  <span class="input-append"><input type="text" name="maxGap" id="maxGap" class="input-mini number" value="24"><span class="add-on">hours</span></span>

</div>
    
<h1 class="page-header" id="criteria">Flickr search criteria (optional)</h1>
<div class="control-group">
<label class="control-label" for="min-date" >Geo-tag photos taken after:</label>
<input type="text" name="min-date" id="min-date" class="input-small date">
</div>
<div class="control-group">
<label class="control-label" for="max-date" >Geo-tag photos taken before:</label>
<input type="text" name="max-date" id="max-date" class="input-small date">
</div>
<div class="control-group">
<label class="control-label" for="tags" >Geo-tag photos tagged:</label>
<input type="text" name="tags" id="tags" class="input">
</div>

<!--
<p>
Here you can add any criteria that exist for 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html">flickr.photos.search</a></code>
to restrict the photos that will be geo-tagged. This will probably be
by <code>tags</code>. Put each criteria on its own line and separate name and value with an
equals sign as in the example below. You can also change the 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html#yui_3_5_1_1_1346613172850_475">sort</a></code>
order, for example if you wanted to start by tagging your oldest photos first, rather than 
your newest photos, ie, defaults to <code>date-taken-desc</code>. 
<code><a href="http://www.flickr.com/services/api/flickr.photos.search.html#yui_3_5_1_1_1346613172850_465">privacy_filter</a></code>
could also be useful if you don't want to geo-tag public photos, see the
previous link for possible values. Note that you cannot override <code>has_geo</code>, this
app will never overwrite existing location data. Unfortunately
<code>flickr.photos.search</code> does not seem to have a <code>sets</code> parameter.
<code>min_taken_date</code> and <code>max_taken_date</code> will be useful if
you have gaps in your Latitude data.
<pre class="criteria">
tags=mongolia,mongolrally
privacy_filter=4
sort=date-taken-asc
min_taken_date=2012-05-28
</pre>
<textarea rows=5 class="criteria" name="criteria" id="criteria"></textarea>
-->

<h1 class="page-header" id="go">Go</h1>
<p>
This app will will not write
data back to Flickr unless you check the checkbox below. This app can be quite
slow, especially when writing back to Flickr. The biggest factor can be the
amount of data returned from Google Latitude, so for example 100 photos taken
over an hour may be processed more quickly than 10 photos taken over a week.
</p>
<p>
<label class="control-label" for="count" id="lblCount">Maximum number of photos to tag:</label>
<input type="text" name="count" id="count" value="10" class="input-small number">
<label class="checkbox">Write data to Flickr<input type="checkbox" name="write" value="true"></label>
</p>

<p>
<input type="submit" value="Go" class="btn btn-primary btn-large" id="gobtn">
<img id="loading" src="loading.gif" alt="loading">
<div id="stat" class="alert alert-info"></div>

<h1 class="page-header">Results</h1>
<p>
The results will be presented here as a table, mainly of links. The Prior and
Next links display the times closest to the time that the photo was taken and
link to those two points on Google Maps. The Best Guess column shows the time
at which the photo was taken and links to the point on Google Maps where the
photo will be geo-tagged. The final two columns show whether writing to Flickr
has been successful and any error messages if not. If the tag fails to write
the location will not be attempted.
</p>
<div id="result"></div>

<h1 class="page-header" id="tips">Tips</h1>
<p>
Obviously the more readings that Google Latitude saves the more accurate this
tool will be. I believe that you can deliberately get Google to start logging
your location by specifically starting Google Maps on your phone. So do this
for example before and after shooting, or leave it running for the duration.
Note that
<a href="http://www.flickr.com/photos/jamiekitson/7822494392/">Google Maps does
not need a data connection</a> to remember locations, it should upload 
remembered locations when it can. 
</p><p>
I've personally found that the biggest
discrepancies come from situations where location (a) is logged in the evening,
I take a photo in the morning in the same location, and then only after travelling some distance 
another location (b) is logged. The large discrepancy is due to the photo
time being a lot closer to point (b) than point (a). Again, if you can it's
best to log more locations.
</p><p>
Remember that you can delete spurious locations using Google's 
<a href="https://maps.google.com/locationhistory/b/0">Location history manager</a>. 
If it appears that the delete function is not working, it may just be that there
are many points in exactly the same location.
</p><p>
Due to Flickr's nasty habit of ignoring time zone data it might actually be better
to not change the time or time zone on your camera when you go abroad. Remember
though that it is quite easy to change taken times, using Flickr's Organiser you
can shift a selection of photos by a certain number of hours.
</p>

<h1 class="page-header" id="alt">Alternatives</h1>
<ul>
<li><a href="http://latitude2flickr.be-q.com/">Latitude2Flickr</a> has a very nice
interface, but I don't think does anything automatically, or even in batches.
It also dynamically loads all of your non-geo-tagged photos every time you go back, 
so can be quite slow if you have a lot of them.
<li>A few desktop non-Flickr apps are discussed in 
<a href="http://www.quora.com/Is-there-a-way-to-geotag-photos-using-Google-Latitude-history">this thread</a>.
</ul>

<h1 class="page-header" id="source">Source code</h1>
<a href="https://github.com/JamieKitson/GeoTagger">On GitHub</a>. This is also the place to 
<a href="https://github.com/JamieKitson/GeoTagger/issues">report</a> bugs, corrections and feature requests.
In the <a href="http://www.flickr.com/services/apps/72157631292787722/?action=screenshots_added">App Garden</a>.
</form>
</body>
</html>

