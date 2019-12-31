<?
session_start();
if (!array_key_exists('state', $_SESSION)) {
  $_SESSION['state'] = 'create';
}
$state = 'dashboard'; //$_SESSION['state'];
?>
<!doctype html5>
<head>
  <title>Oliver, by Waive, Free for CES</title>
  <meta name=viewport content="width=device-width,initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,600,900&display=swap" rel="stylesheet">
<style>
  * { font-family: 'Roboto', sans-serif; }
  a, :visited { color: #fff; }
  body { 
    background: #514aff;
    color: #fff;
    height: 100%;
    margin: 0;
  }
  button { 
    padding: 1.75vh 7vh;
    color: #fff;
    background: #514aff;
    font-weight: 800;
    border: 4px;
    border-radius: 4vh;
    text-transform: uppercase;
  }
  button.white { 
    background: #fff;
    color: #514aff;
  }
  button.primary { 
    background: #ff4d7d;
    font-size: 1.05rem;
  }
  input[type="text"],textarea {
    padding: .5rem;
    border-radius: 2.5vh;
    background: #edecff;
    border: 0;
    font-size: 1.2rem;
    color: #514aff;
    width: 100%;
  }
  input[type="text"]::placeholder,textarea::placeholder{
    color: #514aff;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  textarea::placeholder{
    font-weight: 800;
    line-height: 13vh;
  }
  textarea {
    height: 15vh;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    font-weight: 800;
    justify-content: center;
  }
  .form {
    border-radius: 3vh 3vh 0 0;
    padding: 2vh;
    margin: 0;
  }
  .form.light {
    background: #fff;
    color: #514aff;
    box-shadow: 0 0 6px 1px rgba(0,0,0,0.2);
  } 
  #map {
    height: 50vw;
    overflow: hidden;
    background: #ddd;
  }
  .full {
    width: 100%;
  }
  #header {
    text-align: center;
    background: #514aff;
    padding-bottom: 10vh;
  }
  #header-message {
    margin: 0;
    font-weight: 900;
    padding: 3vh 2vh 3vh;
  }
  #logo {
    margin: 3.5vh 0 2vh;
    width: 33%;
  }
  #white-box-parent {
    position: relative;
    z-index: 10;
    height: 8vh;
    overflow: visible;
  }
  #white-box {
    text-align: center;
    margin: -8vh 2vh 0;
    background: #fff;
    color: #514aff;
    height: 15vh;
    display: flex;
    border: .5vh solid #514aff;
    border-radius: 3vh;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    font-weight: 900;
    box-shadow: 0 0 6px 1px rgba(0,0,0,0.2);
  }
  .form.light input, .form.light label {
    margin: 1.5vh 0;
    text-align: center;
  }
  .form.light label {
    margin-top:0;
    display:block
  }
  #bottom {
    position: absolute;
    bottom: 0;
    width: 100%;
    left: 0;
  }
  #wait-pitch .footer { position: absolute;
    bottom: 2vh;
    text-align: center;
    width: 80vw;
  }
  #wait-pitch { 
    margin: 0 2vh;
    text-align: center;
    height: 60vh;
  }
  #wait-pitch h2 { font-weight: 500;margin:20vh 0 }
  #wait-pitch h2 big { font-weight: 900 }
  #wait-pitch h3 small { font-weight: 500 }
  #wait-pitch h4 { font-weight: 500 }

  .wait, .create, .dashboard {
    display: none;
  }

  .mode-create .create { display: block; }
  .mode-wait .wait { display: block; }
  .mode-dashboard .dashboard { display: block; }
  #go-again {
    width: 100%;
    background: #514aff;
    text-align: center;
  }
  #go-again button {
    margin: 1vh 0;
  }
  .mode-dashboard #header { height: 28vh; }
  .mode-dashboard #go-again { 
    padding-top: 1vh;
    height: 20vh; }
  /* There's 10vh margin on the header */
  .mode-dashboard #map { 
    border-radius: 2vh;
    margin: 0 1vh;
    height: 41vh; }
  .mode-create #map { height: 42vh; }
  .mode-wait #map { display: none }
</style>
</head>
<body>

  <div id="header">
    <img id="logo" src=oliver_logo_path.svg>
    <h1 class=dashboard id=header-message>your message just played!</h1>
  </div>

  <div id='white-box-parent'>
    <div id='white-box'>
      this is my message
    </div>
  </div>

  <div id="map"></div>
  <div id="bottom">
    <div id="input" class="create">
      <div class='light form'>
        <textarea class='message' name="message" id="message" placeholder="Enter message"></textarea>
        <input type="text" placeholder="Enter phone number" name="phone" id="phone">
        <label>
          <input checked type=checkbox required>
          I agree with terms & conditions
        </label>
        <button onclick='addMessage()' class='full'>Continue</button>
      </div>
    </div>

    <div id=wait-pitch class='wait'>
      <h2><big>Oliver</big> is the fastest and easiest way to get the word out.</h2>
      <h3>
        Free at CES<br>
        <small>Advertising for Everyone</small>
      </h3>
      <h3>Come see us at the Amazon booth</h3>
      <div class='footer'>
        Created by <a href=https://waive.com>Waive</a>
      </div>
    </div>

    <div id="go-again" class='dashboard'>
      <div>
      <button class='white'>share</button>
      </div>
      <button onclick="another()" class='primary'>make another</button>
    </div>
  </div>
</body>
  <script src=map.js></script>
  <script>
var id = 56;
var load = {
  create: getCars,
  dashboard: getPath,
  wait: function () {
    whiteit("Thanks, we'll send you a text when it plays.");
  }
}
function another() {
  setMode('create');
}
function whiteit(what) {
  document.querySelector('#white-box').innerHTML = what;
}
// either create wait pitch or dashboard
function setMode(what) {
  document.body.className = 'mode-' + what;
  if(load[what]) {
    load[what]();
  }
}

function api(what) {
  return fetch(`/api/${what}`)
    .then(response => response.json())
}

function getCars() {
  api("screens?project=dev&fields=lat,lng")
    .then(carList => {
      carList = carList.filter(x => x.lat).map(r => ['Point', [r.lng, r.lat]]);
      _map.load(carList);
      _map.fit();
    });
}

function addMessage() {
  var toPost = {};
  ['message','phone'].forEach(w => toPost[w] = document.getElementById(w).value);
  console.log(toPost);
  setMode('wait');
}

function getPath() {
  fetch(`/api/path?id=${id}`)
    .then(response => response.json())
    .then(points => {
      _map.clear();
      _map.load([["Line", points]]);
      _map.fit();
    });
}

window.onload = function() {
  setMode('create');
  self._map = map({
    selectFirst: false,
    draw: false,
    resize: false
  });
  setMode('<?=$state?>');
}
  </script>
</html>
