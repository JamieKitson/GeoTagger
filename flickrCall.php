<?php

include_once('common.php');

function flickrCall($params, $uri = "rest")
{

        $f      = file(dirname(__FILE__).'/flickrsecret.php');
        $secret = trim($f[1]);
        $params['oauth_consumer_key']     = '3b72b2a6e62cc3a06de7eef62646d81c';
        $params['oauth_nonce']            = rand(0, 99999999);
        $params['oauth_timestamp']        = date('U');
        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_version']          = '1.0';
        $params['format']      = 'php_serial';
        if (array_key_exists(FLICKR_TOKEN, $_COOKIE) && ($_COOKIE[FLICKR_TOKEN] != ''))
          $params['oauth_token'] = $_COOKIE[FLICKR_TOKEN];

        $encoded_params = array();
        foreach ($params as $k => $v)
        {
                $encoded_params[] = urlencode($k).'='.urlencode($v); // "$k=$v"; //  
        }

        sort($encoded_params);
        $p = implode('&', $encoded_params);

        $url = "http://api.flickr.com/services/$uri";

        $base = "GET&".urlencode($url)."&".urlencode($p);

        if (array_key_exists(FLICKR_SECRET, $_COOKIE))
          $tokensecret = $_COOKIE[FLICKR_SECRET];
        else
          $tokensecret = "";

        $sig = urlencode(base64_encode(hash_hmac('sha1', $base, "$secret&$tokensecret", true)));

        $url .= "?$p&oauth_signature=$sig";

        $rsp = gzipCall($url);
  
        return $rsp;

}

function testFlickr()
{
  if (cookiesSet(array(FLICKR_TOKEN, FLICKR_SECRET)))
  {
    $rsp = flickrCall(Array('method' => 'flickr.test.login'));
    $p = unserialize($rsp);
    if (array_key_exists('stat', $p) && ($p['stat'] == 'ok'))
      return str_replace("@", "_", $p['user']['id']);
  }
  return false; 
}

function flickrAuthLink($class)
{
  return "<a class=\"$class\" href=\"getFlickr.php\">Authorise Flickr</a>";
}

function pagePhotos($total, $params, $statFile)
{
  $params['page'] = 1;
  $photos = array(1);
  $allPhotos = array();
  $tries = 0;
  while((count($allPhotos) < $total) && (count($photos) > 0))
  {
    $params['per_page'] = $total - count($allPhotos);
    $rsp = flickrCall($params);
    $fc = unserialize($rsp);

    if ($fc['stat'] != 'ok')
    {
      if ($tries >= 3)
      {
        if (is_array($fc) && array_key_exists('message', $fc)) {
          $msg = $fc['message'];
        } else {
          $msg = $rsp;
        }
        errorExit('Flickr call failed with: '.$msg); 
      }
      else
      {
        $tries++;
        continue;
      }
    }
    $photos = $fc['photos']['photo'];
    $allPhotos = array_merge($allPhotos, $photos);
    $tries = 0;
    writeProgress("Getting photos from Flickr.", (100 * count($allPhotos) / $total), $statFile);
    $params['page'] += 1;
  }
  return $allPhotos;
}

function getPhotos($count, $criteria, $statFile)
{
  writeProgress("Getting photos from Flickr.", 0, $statFile);

  // set user adjustable flickr parameters
  $params = array('sort' => 'date-taken-desc');

  // get user set flickr parameters
  foreach(explode("\n", $criteria) as $line)
  {
    $q = explode('=', $line);
    if (count($q) == 2)
      $params[$q[0]] = $q[1];
  }

  // set non user adjustable flickr parameters
  $params['user_id'] = 'me';
  $params['has_geo'] = 0;
  $params['method'] = 'flickr.photos.search';
  $params['extras'] = 'date_taken,geo';

  $photos = pagePhotos($count, $params, $statFile);

  // bail if we've got no photos
  if (count($photos) == 0)
  {
    errorExit('No Flickr photos found.');
  }

  // do expensive str date processing just once
  foreach($photos as &$p)
  {
    $p[UTIME] = strtotime($p['datetaken']);
  }

  // sort photos by date taken in case flickr has fucked up or the user has overridden sort
  sort_array_by_utime($photos);

  return $photos;

}

function flickrAddTags($photoId, $tags)
{
  return flickrCall(array(
        'method' => 'flickr.photos.addTags',
        'photo_id' => $photoId,
        'tags' => $tags));
}

function flickrSetGeo($photoId, $lat, $long)
{
  return flickrCall(array(
        'method' => 'flickr.photos.geo.setLocation',
        'photo_id' => $photoId,
        'lat' => $lat,
        'lon' => $long));
}

?>
