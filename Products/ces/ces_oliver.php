<?
require 'vendor/autoload.php';
$Parsedown = new Parsedown();
$Parsedown->setSafeMode(true);
$message = $Parsedown->text("
# olvr.io 
 __The way__ to advertise for free during CES.");
?>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Roboto+Mono:400,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700i&display=swap" rel="stylesheet"> 
<style>
* { font-family: 'Roboto', sans-serif; }
body {
margin: 0;
font-size: 120px;
background: white;
}
blockquote {
margin: 0 0 0 25px;
border-left: 25px solid rgba(0,0,0,0.9);
padding: 0 0 0 25px;
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
padding: 0 25px;
font-family: 'Roboto Mono', monospace; }
#message {
font-weight: 400;
margin: 0;
padding: 25px 75px;
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
