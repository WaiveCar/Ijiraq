<?
session_start();
function getces($id) {
  $list = json_decode(file_get_contents('http://waivescreen.com/api/ces?id=' . $id), true);
  if(count($list) > 0) {
    return $list[0];
  }
}

$state = 'create';
$campaign_id = 0;
$ces_id = 1;
if(!empty($_GET['id'])) {
  $ces_id = $_GET['id'];
  $obj = getces($ces_id);
  if($obj) {
    $campaign_id = $obj['campaign_id'];
    $state = 'dashboard';
  }
} else if (array_key_exists('campaign_id', $_SESSION)) {
  $campaign_id = $_SESSION['campaign_id'];
  $ces_id = $_SESSION['ces_id'];
  $state = 'dashboard';
} 
?>
<!doctype html5>
<head>
  <title>Oliver, by Waive, Free for CES</title>
  <meta name=viewport content="width=device-width,initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,600,900&display=swap" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>

  <div id="header">
    <img id="logo" src=oliver_logo_thicker.svg>
    <h1 class=dashboard id=header-message>Here's where it played</h1>
  </div>

  <div id='white-box-parent'>
    <div id='white-box'>
      <iframe id=preview src="ces_oliver.php?id=<?=$ces_id?>"></iframe>
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
        <button onclick='addMessage()' class='full'>Go!</button>
      </div>
    </div>

    <div id=wait-pitch class='wait'>
      We'll send you a text when it plays
      <h2>
        <big>Oliver</big> is the fastest and easiest way to get the word out.
        <small>Free at CES</small>
      </h2>
      <div class='footer'>
        Created by <a href=https://waive.com>Waive</a><br>
        Visit us at the Amazon Booth
      </div>
    </div>

    <div id="go-again" class='dashboard'>
  <!--    <div>
      <button class='white'>share</button>
      </div>-->
      <button onclick="another()" class='primary'>make another</button>
    </div>
  </div>
</body>
  <script src=map.js></script>
  <script>
var id = <?= $campaign_id ?>;
var colorList = [
  ["fff", "504aff"],
  ["fff", "8fffed"],
  ["fff", "ffdd4d"],
  ["fff", "ff4d7d"],
  ["fff", "000"],
  ["000", "fff"]
];
var ival = [];
var Dom = {};
var load = {
  create: getCars,
  dashboard: getPath,
  wait: function () {
  }
}
function another() {
  Dom.preview.src = "ces_oliver.php?id=1";
  setMode('create');
}
function whiteit(what) {
  document.querySelector('#white-box').innerHTML = what;
}
// either create wait pitch or dashboard
function setMode(what) {
  document.body.className = 'mode-' + what;
  while(ival.length) {
    clearInterval(ival.pop());
  }
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
  api("screens?project=CES&fields=lat,lng")
    .then(carList => {
      carList = carList.filter(x => x.lat).map(r => ['Point', [r.lng, r.lat]]);
      _map.load(carList);
      _map.fit();
    });
}

function addMessage() {
  var toPost = {};
  ['message','phone'].forEach(w => toPost[w] = document.getElementById(w).value);

  fetch("/api/campaign_ces_create", {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(toPost)
  })
  .then((response) => response.json())
  .then((data) => {
    window.location = "/?id=" + data.ces_id;
    console.log('Success:', data);
  })
  .catch((error) => {
    console.error('Error:', error);
  });
}

function getPath() {
  let isFirst = true;
  function getMap() {
    fetch(`/api/campaigns?id=${id}`)
      .then(response => response.json())
      .then(stats => {
        let playtimes = stats[0].completed_seconds / 7.5;
        if(stats[0].completed_seconds < 10) {
          Dom['header-message'].innerHTML = `The screens are getting your message...`; 
        } else {
          Dom['header-message'].innerHTML = `It's played ${playtimes} times.`; 
        }
      });

    fetch(`/api/path?id=${id}`)
      .then(response => response.json())
      .then(points => {
        _map.clear();
        _map.load(points.map(row => ["Line", row]));
        if(isFirst) {
          _map.fit();
          isFirst = false;
        }
      });
  }
  var iv = setInterval(getMap, 7.5 * 1000);
  ival.push(iv);
  getMap();
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
  setMode('<?=$state?>');
  self._map = map({
    selectFirst: false,
    draw: false,
    resize: false
  });
  ['header-message', 'preview', 'message'].forEach(row => Dom[row] = document.getElementById(row));
  preview();
}
  </script>
</html>
<!-- <?  var_dump($_SESSION); ?> -->
