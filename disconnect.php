<?PHP

include('common.php');

clearCookies(array(FLICKR_TOKEN, FLICKR_SECRET, GOOGLE_TOKEN));

header("Location: index.php#auth");

?>
