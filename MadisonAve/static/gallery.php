<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  </head>
<style>
body { background: #000; margin: 0; padding: 0; }
img { width: 100%; }
</style>
<body>
<?
$id = $_GET['id'];
foreach(glob("snap/$id-*jpg") as $path) {
  echo "<img src=/$path>";
}
?>
</body>
<?  if ($_GET['type'] == 'live') { ?>
<script>
setInterval(function() {
  var imgList = document.getElementsByTagName('img');
  for (var ix = 0; ix < imgList.length; ix++) {
    imgList[ix].src = imgList[ix].src.split('?')[0] + '?' + (new Date());
  }
}, 1000);
</script>
<? } ?>
</html>