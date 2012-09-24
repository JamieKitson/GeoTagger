
    $(document).ready(function(){

      var doStat = false;

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
        $('#fakeFile').attr('disabled', 'disabled');
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
        setTimeout(readStat, 1000);

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
          processData: procData,
          xhr: getXhr
        }).done(function( data ) { 
          $('#result').append(data);
          $('#gobtn').removeAttr('disabled');
          $('#loading').hide();
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
        if (doStat)
        {
          $('#stat').load('stats/' + flickrId);
          setTimeout(readStat, 1000);
        }
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

      function getXhr() 
      {
          myXhr = $.ajaxSettings.xhr();
          if(myXhr.upload){
            myXhr.upload.addEventListener('progress',progressHandlerFunction, false);
          }
          return myXhr;
      }

      function progressHandlerFunction(evt)
      {  
          doStat = false;
          if (evt.lengthComputable) 
          {  
            var p = Math.round(evt.loaded / evt.total * 100);
            if (p < 100)
              $('#stat').html('<p>Uploading data:</p><div class="progress"><div class="bar" style="width: ' + p + '%;"></div></div>')
            else
            {
              doStat = true;
              setTimeout(readStat, 1000);
            }
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


