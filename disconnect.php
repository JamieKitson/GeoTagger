<?PHP

include('common.php');

switch ($_GET['s']) {
  case 'flickr':
    clearCookies(array(FLICKR_TOKEN, FLICKR_SECRET));
    break;
  case 'google':
    clearCookies(array(GOOGLE_TOKEN));
    break;
  }

header("Location: index.php#".$_GET['s']);

?>
