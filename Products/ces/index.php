<?
session_start();
if (!array_key_exists('state', $_SESSION)) {
  $_SESSION['state'] = 'create';
}
$state = $_SESSION['state'];
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
    text-align: center;
    height: 8vh;
    overflow: visible;
  }
  #white-box {
    margin: calc(-33.94vw/2) 4vw 0;
    height: 33.94vw;
    border: .5vw solid #514aff;
    border-radius: 3vh;
    overflow: hidden;
    box-shadow: 0 0 6px 1px rgba(0,0,0,0.2);
    font-size: 1.7rem;
    font-weight: 900;
    background: #fff;
    color: #514aff;
  }
  iframe { 
    width: 100%;
    height: 100%;
    border: 0; 
    overflow: hidden;
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
  #wait-pitch .footer { 
    position: absolute;
    bottom: 2vh;
    left: 0;
    text-align: center;
    width: 100%;
  }
  #wait-pitch { 
    margin: 0 2vh;
    text-align: center;
    height: 60vh;
  }
  #wait-pitch h2 { font-weight: 500 }
  #wait-pitch h2 big { font-weight: 900 }
  #wait-pitch h2 small { font-weight: 700;font-size: 1rem;margin-top:1rem;display:block;text-transform: uppercase }
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
    height: 20vh; 
  }
  /* There's 10vh margin on the header */
  .mode-dashboard #map { 
    border-radius: 2vh;
    margin: 0 1vh;
    height: 41vh; 
  }
  .mode-create #map { height: 42vh; }
  .mode-wait #map { display: none }

  @media all and (orientation:landscape) {
    #header { 
      display: none 
    }
    #white-box {
      margin-top: 0; 
      display: inline-block;
      width: calc(1920/675 * 0.6 * 33.94vw);
      height: calc(0.6 * 33.94vw);
    }
  }
</style>
</head>
<body>

  <div id="header">
    <img id="logo" src=oliver_logo_path.svg>
    <h1 class=dashboard id=header-message>your message just played!</h1>
  </div>

  <div id='white-box-parent'>
    <div id='white-box'>
      <iframe id=preview src="ces_oliver.php?id=1"></iframe>
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
    We'll send you a text when it plays
      <h2><big>Oliver</big> is the fastest and easiest way to get the word out.
<small>Free at CES</small>
</h2>
      <div class='footer'>
        Created by <a href=https://waive.com>Waive</a><br>
        Visit us at the Amazon Booth
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
var Dom = {};
var load = {
  create: getCars,
  dashboard: getPath,
  wait: function () {
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
  document.getElementById('message').focus();
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

  fetch("/api/ces", {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(toPost)
  })
  .then((response) => response.json())
  .then((data) => {
    console.log(data);
    setMode('wait');
    console.log('Success:', data);
  })
  .catch((error) => {
    console.error('Error:', error);
  });
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
/*
var ajaxInput = (function(){
  var tMap = {}, ix = 0;

  function handler(key) {
    var
      obj = tMap[key],
      dom = obj.dom,
      val = dom.value,
      ix = obj.ix ++;

    // if it's the same value that we sent before
    // then we bail
    if('last' in obj && obj.last === val) {
      return;
    }

    // record the last value
    obj.last = val;

    obj.cb.forEach(what => what(val, ix));
  }

  return function(dom, cb) {
    var key = ix++;

    if(!tMap[key]) {
      tMap[key] = {
        dom: dom,
        cb: [],
        ix: 0,
        last: '',
        timeout: 300
      };

      tMap[key].poll= setInterval(function(){
        if(!tMap[key].handler) {
          handler(key);
        }
      }, tMap[key].timeout);

      $(dom).on('keydown keyup', function(){
        var obj = tMap[key];

        if(obj.handler) {
          clearTimeout(obj.handler);
          delete obj.handler;
        }

        obj.handler = setTimeout(function(){
          handler(key);
          delete obj.handler;
        }, obj.timeout);
      });
    }

    tMap[key].cb.push(cb);
  }

})();
 */

function preview() {
  let last = '';
  setInterval(function() {
    if(Dom.message.value != last) {
      if(Dom.message.value.length == 0) {
        Dom.preview.src = "ces_oliver.php?id=1";
      } else {
        Dom.preview.src = "ces_oliver.php?message=" + encodeURIComponent(Dom.message.value);
      }
      last = Dom.message.value;
    }
  }, 400);
}

window.onload = function() {
  setMode('wait');
  self._map = map({
    selectFirst: false,
    draw: false,
    resize: false
  });
  //setMode('<?=$state?>');
  ['preview', 'message'].forEach(row => Dom[row] = document.getElementById(row));
  preview();
}
  </script>
</html>
