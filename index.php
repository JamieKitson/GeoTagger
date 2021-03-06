<?php

// Might need to alter header

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');
include_once('common.php');

$flickrId = testFlickr();
$latitude = testLatitude();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Geo-Tag Flickr photos using Google Location History (Latitude) or other GPS data</title>
  <meta charset="UTF-8">
  <meta name="description" content="Geo-tag your Flickr photos using your location history from Google Latitude or other GPS data.">
  <meta name="keywords" content="flickr, geotag, geotagging, google, latitude, location history, location, geo-tag, geo-tagging, gps">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.1/css/bootstrap-combined.min.css" rel="stylesheet">
  <link href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" rel="stylesheet">
  <link href="geo.css" rel="stylesheet">
</head>
<body>

<form class="container form-horizontal" enctype="multipart/form-data">
<input type="hidden" name="flickrId" id="flickrId" value="<?php echo $flickrId; ?>">

<div class="alert alert-block alert-error" id="apiWarning" style="display: none">
  <p>
  <button class="close" type="button">×</button>
  <strong>Please note</strong> that the Flickr API can be unreliable. Fewer photos than 
  requested may be returned, occasionally none at all. If this happens please try again.
  See the <a href="http://tech.groups.yahoo.com/group/yws-flickr/">Yahoo Flickr API Group</a> 
  and <a href="http://www.flickr.com/groups/api/">Flickr API Group</a> for 
  <a href="http://tech.groups.yahoo.com/group/yws-flickr/message/7809">various</a> 
  <a href="http://www.flickr.com/groups/api/discuss/72157631638748470/">discussions</a>.
  </p>
  <p>
  <strong>Additionally</strong> the Flickr API has been acting irratically when used with min and max dates,
  so it might be best to just tag you latest x photos.
  </p>
</div>

<h1 class="page-header">About</h1>
<p>
This app will attempt to automatically geo-tag your Flickr photos using 
Google Latitude or textual data. The exact location will be estimated by using the
proportion of time passed between the two points either side of the time that
the photo was taken. This app will never overwrite existing location data and
will tag any photos that it geo-tags with <code>geotaggedfromlatitude</code>, this might seem like spam, but is
there to help you in case anything should go wrong, and hence cannot be disabled. You
can choose photos by tags, dates, or set in step 3. By default this app
will not write data back to Flickr but will show you what it will try to do and
why.
</p>

<h1 class="page-header" id="settz">Set your time zone</h1>
<p>
<a href="http://www.flickr.com/services/api/misc.dates.html">Flickr does not
record the time zone</a> that your camera is set to, so to maximise accuracy it
is very important to get the time zone right. Choosing your exact location
allows us to hopefully handle daylight saving correctly. Note that this should match you
camera setting, not just where you happen to be right now. <!-- Yes, I could attempt
to get the time zone from the EXIF data, but from a brief and unscientific 
survey it seems that this doesn't show up in Flickr for many cameras, my D7000
being one of the few that does. -->
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

<h1 class="page-header" id="criteria">Flickr search criteria (optional)</h1>
<input type="hidden" value="#flickrSearch" name="criteriaChoice" id="criteriaChoice">
<ul class="nav nav-tabs" id="criteriaTab">
  <li class="active"><a href="#flickrSearch">Search</a></li>
  <li><a href="#flickrSet">Sets</a></li>
</ul>

<div class="tab-content" id="criteriaTab-content">
  <div class="tab-pane active" id="flickrSearch">
    <div class="control-group">
      <label class="control-label" for="min-date" >Geo-tag photos taken on or after:</label>
      <input type="text" name="min-date" id="min-date" class="input-small date">
    </div>
    <div class="control-group">
      <label class="control-label" for="max-date" >Geo-tag photos taken on or before:</label>
      <input type="text" name="max-date" id="max-date" class="input-small date">
    </div>
    <div class="control-group">
      <label class="control-label" for="tags" >Geo-tag photos tagged:</label>
      <input type="text" name="tags" id="tags" class="input">
    </div>
    <div class="control-group">
      <label class="control-label" for="count" id="lblCount">Maximum number of photos to tag:</label>
      <input type="number" name="count" id="count" placeholder="<?php echo DEF_COUNT; ?>" value="<?php echo DEF_COUNT; ?>" class="input-small">
    </div>
  </div>

  <div class="tab-pane " id="flickrSet">
    <div class="control-group" id="setGroup">
      <label class="control-label" for="set">Geo-tag set:</label>
      <select name="set" id="set" disabled="disabled"></select>
      <img id="setLoading" src="loading.gif" alt="loading" class="loading">
    </div>
    <p>
      Note that this will return all photos from the chosen set, but only photos that are not already geo-tagged will be written to.
    </p>
  </div>

</div>

<h1 class="page-header" id="google">Choose your input method</h1>

<ul class="nav nav-tabs" id="inputTab">
<li class="active"><a href="#kmlChoice">Google Location History</a></li>
<li><a href="#latChoice">Google Latitude</a></li>
<li><a href="#fileChoice">File Upload</a></li>
<li><a href="#txtChoice">Text</a></li>
</ul>
   
<div class="tab-content" id="inputTab-content">
  <div class="tab-pane active" id="kmlChoice">
    <div class="alert">
        <strong>Warning:</strong>
        Google seems to be limiting the amount of data that can be downloaded in one file.
        If you see lots of "No geo-data for..." in your results regenerate the URL once you
        have written the data back to Flickr and reupload the data. This should decrease the
        timespan of the file and bring back the more specific, relevant data.
    </div>
    <ol>
        <li>
            <p><button id="btnKml" class="btn btn-primary" type="button" <?php echo $flickrId === false ? 'disabled="disalbed"' : '' ?>>Generate URL</button>
            <img id="kmlLoading" src="loading.gif" alt="loading" class="loading"></p>
        </li>
        <li><p>Download File: <span id="kmlLink"></span></p></li>
        <li><p class="filecontainer">Upload File:</p></li>
    </ol>
  </div>

  <div class="tab-pane" id="latChoice">
    <div class="alert alert-error">
        <strong>Warning:</strong> The Google Latitude API <a href="https://developers.google.com/latitude/">has been retired</a>, 
        so this input method will not work. Use the two step Location History download and upload process on the prior tab.
    </div>
  <p>
  Google tokens will be held only as cookies in your browser and can be
  removed at any time by clicking the Disconnect button. The Google token has a
  fairly short life, so don't be surprised if you need to re-authenticate
  regularly. Latitude points less accurate than <?php echo DEF_ACCURACY; ?> 
  metres will be ignored.
  <input type="hidden" value="google" class="geoinput" name="">
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
    kml files and text files consisting of lines of three space delimited fields containing a  
    time stamp, longitude and latitude, in that order are accepted. <strong>Note</strong> that the Flickr search
    will be adjusted to search only within the data supplied, ie, no photos will be 
    returned which were taken outside the time stamps supplied in the geo-data file.
    </p>
    <div id="textFile" class="filecontainer"></div>
  </div>
  <div class="tab-pane" id="txtChoice">
    <p>
    Text must consist of lines of three space delimited fields containing a unix 
    time stamp, longitude and latitude, in that order. <strong>Note</strong> that the Flickr search
    will be adjusted to search only within the data supplied, ie, no photos will be 
    returned which were taken outside the time stamps supplied in the geo-data file.
    </p>
    <textarea id="inputText" name="" class="geoinput"></textarea>
  </div>

  <span id="fileUpload" style="display:none">
    <input id="inputFile" type="file" style="display:none" name="" class="geoinput">
    <div class="input-append">
       <input id="fakeFile" class="input-large" type="text" readonly="readonly">
       <a class="btn" id="browseInput">Browse</a>
    </div>
  </span>

  <label class="control-label" for="maxGap">Skip photos in gaps of more than:</label>
  <span class="input-append">
    <input type="number" name="maxGap" id="maxGap" class="input-mini" placeholder="<?php echo DEF_MAX_GAP; ?>" value="<?php echo DEF_MAX_GAP; ?>">
    <span class="add-on">hours</span>
  </span>

</div>
    
<h1 class="page-header" id="go">Go</h1>
<p>
If you just want to do a test run to see what this app would do uncheck the checkbox below.
This app can be quite
slow, especially when writing back to Flickr. The biggest factor can be the
amount of data returned from Google Latitude, so for example 100 photos taken
over an hour may be processed more quickly than 10 photos taken over a week.
</p>
<div class="write">
<label class="checkbox"><strong>Write data to Flickr</strong><input type="checkbox" checked="checked" name="write" value="true"></label>
</div>

<p>
<input type="submit" value="Go" class="btn btn-primary btn-large" id="gobtn">
<img id="goLoading" src="loading.gif" alt="loading" class="loading">
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
<p>
If you want to geo-tag photos which already have geo-data then you will need to
remove that geo-data first using Flickr's Organiser. To remove all the
<code>geotaggedfromlatitude</code> tags from your photos you can do that 
<a href="http://www.flickr.com/photos/me/tags/geotaggedfromlatitude/delete/">here</a>.
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

  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
  <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/jquery-ui.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.1/js/bootstrap.min.js"></script>
  <script src="geo.js"></script>
  <script>
    var latitude = <?php echo $latitude ? 'true' : 'false'; ?>;
    var flickrId = '<?php echo $flickrId; ?>';
    function progressBar(msg, p) {
      return '<?php echo progressBar("' + msg + '", "' + p + '"); ?>';
    }
  </script>

</body>
</html>

