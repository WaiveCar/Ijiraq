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
<link href="https://fonts.googleapis.com/css?family=Heebo:300,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Heebo+Mono:400,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700i&display=swap" rel="stylesheet"> 
<style>
* { font-family: 'Heebo', sans-serif; }
body {
transition: all 0.5s ease;
margin: 0;
font-size: 6.45vw;
background: #000;
color: #888;
opacity: 0.1;
display: flex;
align-items: center;
justify-content: center;
flex-direction: column;
}
blockquote {
margin: 0 0 0 1.3vw;
border-left: 1.3vw solid #ff4d7d;
padding: 0 0 0 1.3vw;
}
#bottom {
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Heebo', sans-serif; 
  font-size: 4vw;
  margin-top: 0;
  font-weight: 300;
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
font-family: 'Heebo Mono', monospace; }
#top {
height: 75vh;
display: flex;
align-items: center;
justify-content: center;
}
#message {
text-align: center;
display: block;
font-weight: 600;
margin: 0;
}
#message p {
margin: 0;
padding: 0;
}
i,em{font-family: 'Lora', serif; font-weight: bold}
a { color: #248 }
img { display: none }
img#logo { 
width: 13vw;
margin-bottom: 1vw;
display: inline-block;}
#bar{ margin: 0 3vw;height: 5vw;display:inline-block;border-right: 0.15vw solid  }
h1,h2,h3,h4,h5,h6 { margin: 0 }
h1 { font-weight: 900; font-size: 1.35em}
h2 { font-weight: 600; font-size: 1.20em;line-height:1em}
h3 { font-weight: 400; font-size: 1.10em;line-height:1em}
</style>
<div id=top>
<div id=message>
<?= $message ?>
</div>
</div>
<div id='bottom'>
  <img id=logo src=oliver_logo_thicker.svg><span id=bar></span><span id='user'>olvr.io</span>
</div>
<script>
window.onload = function() {
  var 
    _dark = Math.random() < 0.5,
    stack = [];

  var bg = (_dark ? 10 : 45) + "%";
  var fg = (_dark ? 85 : 100) + "%";
  var base = (Math.random() * 360);
  var text = (Math.random() * 360);

  document.body.style.background = 'hsl(' + [base, '20%', bg].join(',') + ')';
  document.body.style.color = 'hsl(' + [text, fg, fg].join(',') + ')';
  document.body.style.opacity = 1;
  let rgb = document.body.style.color.match(/\d+/g).map(x => (256 + parseInt(x, 10)).toString(16).slice(1)).join('');
  document.getElementById('logo').src= 'magic-logo.php?h=' + rgb;
  document.getElementById('bar').borderColor = document.body.style.color;
};
</script>
