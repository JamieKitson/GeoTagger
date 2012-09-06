<?php  
 
include('common.php');

  $f      = file(dirname(__FILE__).'/googlesecret.php');
  $secret = trim($f[1]);

$fields=array(  
  'code'          => $_GET["code"],  
  'client_id'     => '60961182481.apps.googleusercontent.com',  
  'client_secret' => $secret, 
  'redirect_uri'  => baseHttpPath().'gotLatitude.php',  
  'grant_type'    => 'authorization_code'  
);  
  
foreach($fields as $key => $value) 
{ 
  $encoded[] = $key.'='.urlencode($value); 
}  

$fields_string = implode('&', $encoded);

$ch = curl_init();  
curl_setopt($ch, CURLOPT_URL, 'https://accounts.google.com/o/oauth2/token');  
curl_setopt($ch, CURLOPT_POST, 5);  
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
$result = curl_exec($ch);  
curl_close($ch);  

$response = json_decode($result);

if (property_exists($response, 'access_token'))
{
  $accesstoken = $response->access_token;
  echo $accesstoken;
  setcookie(GOOGLE_TOKEN, $accesstoken);
  header("Location: index.php#auth");
}
else
{
  echo "Error getting Google token: ".$response->error;
}

?>
