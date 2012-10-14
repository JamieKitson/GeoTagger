<?php

include('flickrCall.php');

function listFlickrSets()
{
  $res = "";
  $params = array('method' => 'flickr.photosets.getList');
  $rsp = flickrCall($params);
  $fc = unserialize($rsp);
  if ($fc['stat'] == 'ok')
  {
    foreach($fc['photosets']['photoset'] as $set)
    {
      $res .= '<option value="'.$set['id'].'">'.htmlentities($set['title']['_content'], 0, 'UTF-8').'</option>';
    }
  }
  return $res;
}

echo listFlickrSets();

?>
