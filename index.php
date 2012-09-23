<?php

// Might need to alter header

error_reporting(E_ALL);
ini_set('display_errors', '1');

include('flickrCall.php');
include('googleCall.php');

$flickrId = testFlickr();
$latitude = testLatitude();

?>
<!DOCTYPE html>
<html>
<head>
  <title>Geo-Tag Flickr photos using Google Latitude data</title>
  <meta charset="UTF-8">
  <meta name="description" content="Geo-tag your Flickr photos using your location history from Google Latitude">
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
  <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <style> 
    #lblCount { display: inline } 
    textarea { width: 40em }
    body { counter-reset: headings -1 }
    h1:before { content: counter(headings) ". "; counter-increment: headings; }
    #loading, #stat { display: none; }
    input#fakeFile { cursor: auto }
    .tab-content { border: 1px solid #DDDDDD; border-top: none; padding: 20px }
    #myTab { margin-bottom: 0 }
    #fileChoice .alert { margin-bottom: 10px }
  </style>
  <script src="bootstrap/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function(){

      var doStat = false;
      var latitude = <?php echo $latitude ? 'true' : 'false'; ?>;
      var flickrId = '<?php echo $flickrId; ?>';

      $("#region").val(getCookie('region', 'Europe'));
      $("textarea.criteria").val(getCookie('criteria', ''));

      loadTimezones();

      $("#region").change(function(event){
        setCookie('region', $("#region").val());
        loadTimezones();
      });

      $("#timezone").change(function(event){
        setCookie('timezone', $("#timezone").val());
      });

      $("textarea.criteria").focusout(function() {
        setCookie('criteria', $("textarea.criteria").val());
      });

      //clearFiles();
      $('#fakeFile').val($('#inputFile').val());

      var cho = getCookie('choice', '#latChoice');
      tabChange('#myTab a[href=' + cho + ']');
      $('#myTab a').click(function (e) {
        e.preventDefault();
        // clearFiles();
        tabChange(this);
        setCookie('choice', $(this).attr('href'));
        tabChange();
      });

      if (typeof FormData != 'undefined')
      //if (false)
      {
        $('#inputFile').change(function() { $('#fakeFile').val($(this).val()); goBtnEnable(); });
        $('#fakeFile').click(function() { $('input#inputFile').click(); });
        $('#browseInput').click(function() { $('input#inputFile').click(); });
        $('#inputText').change(function() { goBtnEnable(); });
        $('#fileChoice .alert').hide();
      }
      else
      {
        $('#browseInput').addClass('disabled');
      }

      function tabChange(aTab)
      {
        $(aTab).tab('show');
        goBtnEnable();
        $('.input').removeAttr('name');
        $('#myTab-content :visible .input').attr('name', 'input');
      }

      $('form').submit(function() {
        $('#gobtn').attr('disabled', 'disabled');
        $('#current').removeAttr("id");
        $('#loading').show();
        doStat = true;
        $('#stat').text('Starting...').show();
        setTimeout(readStat, 500);

        if (typeof FormData != 'undefined')
        {
          aData = new FormData($(this)[0]);
          contType = false;
          procData = false;
        }
        else
        {
          aData = $(this).serialize();
          contType = 'application/x-www-form-urlencoded; charset=UTF-8';
          procData = true;
        }

        $.ajax({
          url: "go.php", 
          data: aData,
          type: 'POST',
          cache: false,
          contentType: contType,
          processData: procData
        }).done(function( data ) { 
          $('#result').append(data);
          $('#gobtn').removeAttr('disabled');
          $('#loading').hide();
          doStat = false;
          if ($('#result table').length)
          {
            window.onbeforeunload = function(e) { 
              return 'Leaving this page will clear your results table.'; 
            };
          }
          $('#result > :last-child').attr("id", "current");
          document.getElementById('current').scrollIntoView();
        });

        return false;
      });

      function loadTimezones() {
        $("#timezone").load("timezones/" + $("#region").val(), function() { 
          $("#timezone").val(getCookie('timezone', 'London')); 
        });
      }

      function readStat()
      {
        $('#stat').load('stats/' + flickrId);
        if (doStat)
          setTimeout(readStat, 1000);
        //else
        //  $('#stat').hide();
      }
/*
      function clearFiles()
      {
        $('#inputFile').val('');
        $('#fakeFile').val('');
      }
*/
      function goBtnEnable()
      {
        if (validateInput() == '')
          $('#gobtn').removeAttr('disabled')
        else
          $('#gobtn').attr('disabled', 'disabled');
      }

      function validateInput()
      { 
        if (flickrId == '')
          return 'Please authorise Flickr.';
        if ($('#latChoice').is(':visible') && !latitude)
          return 'Please authorise Google Latitude.';
        if ($('#fileChoice').is(':visible') && ($('#inputFile').val() == ''))
          return 'Please choose a file.';
        if ($('#txtChoice').is(':visible') && ($('#inputText').val() == ''))
          return 'Please enter some input text.';
        return ''; 
      }

    });

    function setCookie(c_name, value)
    {
      var exdays = 100;
      var exdate = new Date();
      exdate.setDate(exdate.getDate() + exdays);
      var c_value = escape(value) + ((exdays == null) ? "" : "; expires=" + exdate.toUTCString());
      document.cookie = c_name + "=" + c_value;
    }

    function getCookie(c_name, def)
    {
      var i, x, y, ARRcookies = document.cookie.split(";");
      for (i = 0; i < ARRcookies.length; i++)
      {
        x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
        y = ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
        x = x.replace(/^\s+|\s+$/g,"");
        if (x == c_name)
        {
          return unescape(y);
        }
      }
      return def;
    }

 </script>

</head>
<body>
<form class="container" enctype="multipart/form-data">
<input type="hidden" name="flickrId" id="flickrId" value="<?php echo $flickrId; ?>">

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
<option>Europe</option>
<option>Indian</option>
<option>Pacific</option>
</select>

<select id="timezone" name="timezone">
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
  <input type="hidden" value="google" class="input" name="">
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
    Files must consist of lines of three space delimited fields containing a unix 
    time stamp, latitude and longitude. <strong>Note</strong> that the Flickr search 
    will be adjusted to search only within the data supplied, ie, no photos will be 
    returned which were taken outside the time stamps supplied in the geo-data file.
    </p>
    <input id="inputFile" type="file" style="display:none" name="" class="input">
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
    <textarea id="inputText" name="" class="input"></textarea>
  </div>
</div>
     
<h1 class="page-header">Flickr search criteria (optional)</h1>
<P>
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
<textarea rows=5 class="criteria" name="criteria"></textarea>

<h1 class="page-header" id="go">Go</h1>
<p>
This app will geo-tag a maximum of 250 photos at a time and will not write the
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

