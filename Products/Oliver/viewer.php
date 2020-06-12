<!doctype html5>
<html>
  <head>
    <title>WaiveScreen</title>
    <style>
    body {
      margin: 0;
      background: #000;
    }
    .info {
      position: absolute;
      bottom: 0;
      right: 0;
      font-size: 9px;
      color: rgba(100,240,100,0.3);
      font-family: "Bitstream Charter";
      z-index: 1000;
    }
    </style>
    <link rel="stylesheet" href="css/engine.css">
  </head>

  <body>
    <div class='info'></div>
    <div class='sms-wrap'>
      <div id='sms'></div>
    </div>
  </body>

  <script src="js/viewer.js"></script>
  <script src="js/engine.js"></script>
</html>
