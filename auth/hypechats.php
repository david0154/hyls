$app_id = '1d34da71819348f21e4a';
$app_secret = '0bc5994b7910169d0f113724a7b5056d1766bd4';

if (!isset($_GET['code'])) {
 header("Location:https://hypechats.com/oauth?app_id=$app_id");
 exit;
}

$code = $_GET['code'];
$get = file_get_contents("https://hypechats.com/authorize?app_id=$app_id&app_secret=$app_secret&code=$code");
$json = json_decode($get,true);

if (!empty($json['access_token'])) {
 $token = $json['access_token'];
 $user = json_decode(file_get_contents(
 "https://hypechats.com/app_api?access_token=$token&type=get_user_data"
 ), true);

 // login or register user
}
