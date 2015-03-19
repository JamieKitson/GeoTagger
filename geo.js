
    $(document).ready(function(){

      var doStat = false;

      $('input[type!=file], textarea, select').each(function() {
        var id = $(this).attr('id');
        $(this).val(getCookie(id, $(this).val()));
        $(this).change(function() {
          setCookie(id, $(this).val());
        });
      });

      loadTimezones();

      $("#region").change(function(event){
        setCookie('region', $("#region").val());
        loadTimezones();
      });

      $('#fakeFile').val($('#inputFile').val());

      var cho = getCookie('choice', '#kmlChoice');
      inputTabChange('#inputTab a[href=' + cho + ']');
      $('#inputTab a').click(function (e) {
        e.preventDefault();
        inputTabChange(this);
        setCookie('choice', $(this).attr('href'));
      });

      var crit = getCookie('criteriaTab', '#flickrSearch');
      criteriaTabChange('#criteriaTab a[href=' + crit + ']');
      $('#criteriaTab a').click(function(e) {
          e.preventDefault();
          criteriaTabChange(this);
          setCookie('criteriaTab', $(this).attr('href'));
      });

      if (getCookie('apiWarn', 'no') == 'no')
      {
        $('#apiWarning').show();
        $('#apiWarning button.close').click(function() {
            $('#apiWarning').slideUp();
            setCookie('apiWarn', 'yes');
        });
      }

      if (typeof FormData != 'undefined')
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
        $('#fakeFile').attr('disabled', 'disabled');
      }

      $('input.date').datepicker({ dateFormat: 'yy-mm-dd', showButtonPanel: true });
      $('input.date').change(function() {
          var id = $(this).attr('id');
          setCookie(id, $(this).val());
          if (!validateDates())
          {
            $('#max-date').val($('#min-date').val());
//            $('input.date').parent().addClass('error');
          }
      });

      function validateDates()
      {
        if (($('#max-date').val() == '') || ($('#min-date').val() == ''))
          return true;
        if (new Date($('#max-date').val()) >= new Date($('#min-date').val()))
          return true;
        alert('"After" date must be equal or prior to "before" date.');
        return false;
      }

      function inputTabChange(aTab)
      {
        $(aTab).tab('show');
        goBtnEnable();
        $('.geoinput').removeAttr('name');
        if ($('.filecontainer').is(':visible'))
        {
            $('.filecontainer:visible').append($('#fileUpload'));
            $('#fileUpload').show();
        }
        $('#inputTab-content :visible .geoinput').attr('name', 'input');
      }

      function criteriaTabChange(aTab)
      {
        $(aTab).tab('show');
        var id = $(aTab).attr('href');
        $('#criteriaChoice').val(id);
        if ((id == '#flickrSet') && ($('#set').html().length == 0) && (flickrId != ''))
        {
          $('#setLoading').show();
          $('#set').load('getSets.php', function() { 
            $('#setLoading').hide();
            if ($('#set').html().length != 0)
            {
              $(this).width('auto');
              $(this).removeAttr('disabled');
              $(this).val(getCookie('set', 0));
            }
          });
        }
      }

      $('form').submit(function() {
        if (!validateDates())
        {
          document.getElementById('criteria').scrollIntoView();
          return false;
        }
        $('#gobtn').attr('disabled', 'disabled');
        $('#current').removeAttr("id");
        doStat = true;
        $('#stat').text('Starting...').show();
        setTimeout(readStat, 1000);
        return doCall('go.php', '#goLoading', goDone);
      });

      $('#btnKml').click(function() {
        if (!validateDates())
        {
          document.getElementById('criteria').scrollIntoView();
          return;
        }
        doCall('kmlURL.php', '#kmlLoading', function(data) { $('#kmlLink').html(data); } );
      });

      function doCall(aURL, aLoad, aDone) {

        $(aLoad).show();
        if (typeof FormData != 'undefined')
        {
          aData = new FormData($('form')[0]);
          contType = false;
          procData = false;
        }
        else
        {
          aData = $('form').serialize();
          contType = 'application/x-www-form-urlencoded; charset=UTF-8';
          procData = true;
        }

        $.ajax({
          url: aURL,
          data: aData,
          type: 'POST',
          cache: false,
          contentType: contType,
          processData: procData,
          xhr: getXhr
        }).done(function(data){
            $(aLoad).hide();
            aDone(data);
          });

        return false;
      }

    function goDone( data ) {
          $('#result').append(data);
          $('#gobtn').removeAttr('disabled');
          readStat();
          doStat = false;
          if ($('#result table').length)
          {
            window.onbeforeunload = function(e) { 
              return 'Leaving this page will clear your results table.'; 
            };
          }
          $('#result > :last-child').attr("id", "current");
          document.getElementById('current').scrollIntoView();
        }

      function loadTimezones() {
        $("#city").load("timezones/" + $("#region").val() + '.html', function() { 
          $("#city").val(getCookie('city', 'London')); 
        });
      }

      function readStat()
      {
        if (doStat)
        {
          $('#stat').load('stats/' + flickrId);
          setTimeout(readStat, 1000);
        }
      }

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
        if (($('#fileChoice').is(':visible') || $('#kmlChoice').is(':visible'))&& ($('#inputFile').val() == ''))
          return 'Please choose a file.';
        if ($('#txtChoice').is(':visible') && ($('#inputText').val() == ''))
          return 'Please enter some input text.';
        return ''; 
      }

      function getXhr() 
      {
          myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload){
            myXhr.upload.addEventListener('progress',progressHandlerFunction, false);
            myXhr.upload.addEventListener('load',loadEndHandlerFunction, false);
          }
          return myXhr;
      }

      function progressHandlerFunction(evt)
      {  
        if (evt.lengthComputable) 
        {  
          doStat = false;
          var p = Math.round(evt.loaded / evt.total * 100);
            $('#stat').html(progressBar('Uploading data:', p));
            // firefox fix
            if (p > 75)
              loadEndHandlerFunction(evt);
        }  
      }  

      function loadEndHandlerFunction(evt)
      {  
        if (!doStat)
        {
          $('#stat').html(progressBar('Uploading data:', 100));
          doStat = true;
          setTimeout(readStat, 1000);
        }
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


