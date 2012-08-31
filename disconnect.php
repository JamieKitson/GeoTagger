<?PHP

include('cookies.php');

clearCookies(array(FLICKR_TOKEN, FLICKR_SECRET, GOOGLE_TOKEN));

header("Location: index.php");

?>
