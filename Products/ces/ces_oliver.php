<?
require 'vendor/autoload.php';
$Parsedown = new Parsedown();
$Parsedown->setSafeMode(true);
if(array_key_exists('message', $_GET)) {
  $message_md = $_GET['message'];
} else {
  $id = $_GET['id'];
  if(!$id) {
    exit;
  }
  $payload_raw = file_get_contents("http://waivescreen.com/api/ces?id=$id");
  $payload = json_decode($payload_raw, true);
  $message_md = $payload[0]['message'];
}

$message = $Parsedown->text($message_md);
?>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto+Mono:400,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700i&display=swap" rel="stylesheet"> 
<style>
* { font-family: 'Roboto', sans-serif; }
body {
margin: 0;
font-size: 6.45vw;
background: white;
color: #514aff;
display: flex;
align-items: center;
justify-content: center;
}
blockquote {
margin: 0 0 0 1.3vw;
border-left: 1.3vw solid #ff4d7d;
padding: 0 0 0 1.3vw;
}
ol,ul {
margin: 0;
padding: 0;
list-style-position: inside;
list-style-type: square;
}
li { margin: 0;padding: 0 }
hr { margin: 0 }
code { 
font-weight: 700;
background: rgba(0,0,0,0.9); color: #aef;
padding: 0 1.3vw;
font-family: 'Roboto Mono', monospace; }
#message {
text-align: center;
display: block;
font-weight: 400;
margin: 0;
height: 100%;
padding: 1.3vw 3.9vw;
}
#message p {
margin: 0;
padding: 0;
}
i,em{font-family: 'Lora', serif; font-weight: bold}
a { color: #248 }
img { display: none }
h1,h2,h3,h4,h5,h6 { margin: 0 }
h1 { font-weight: 900; font-size: 1.35em}
h2 { font-weight: 600; font-size: 1.20em}
h3 { font-weight: 400; font-size: 1.10em}
</style>
<div id=message>
<?= $message ?>
</div>
