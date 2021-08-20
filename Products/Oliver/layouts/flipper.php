<?php
$id = $_GET['id'];
$dur = 4;
?>
<!--
there's two ways

either place a fake grid on top of the picture and then do css animations for the boxes to change OR

make the boxes real by using background/offset and then I *think* you can do a css 3d transform to flip them around.

The real one is superior because I believe there is more flexibility maybe? I think the animation feature puts us into modern browser space regardless.
2020-08-13
-->
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@300;600;700&display=swap" rel="stylesheet">
<style>
body {
  margin: 0;
  padding: 0;
}
#ad {
  background: #303040;
  display: flex;
}
#lhs {
  margin: 2vh 2vmax;
  font-size: 6vh;
  color: white;
  height: 96vh;
}
#lhs > div {
  flex-direction: column;
  justify-content: center;
  display: flex;
  /* box-shadow: 0 0 4px pink; */
  height: 25%;
}
#top div {
  display: flex;
  align-items: center;
}
#name,#bigtext,#contact {
  font-family: Lora;
}
div#smalltext {
  font-family: Helvetica, Arial, sans-serif;
  font-size: 3vmax;
  font-weight: normal;
  justify-content: flex-start;
}
div#smalltext div {
  margin-top: 5vh;
}

div#bigtext small {
  opacity: .5;
  font-style: italic;
  float: right;
}
div#bigtext {
  font-size: 3.2vmax;
  font-weight: 700;
  margin: 0;
  line-height: 1.1;
  justify-content: flex-end;
}
div#bigtext div {
/*  margin-bottom: 5vh;*/
}
#image { 
  width: 100vh;
  height: 100vh;
  flex: 0 0 100vh;
  margin-left: auto;
}

.row {
  width: 100%;
  height: 25%;
  perspective: 100vh;
  perspective-origin: bottom center;
  font-size: 0;
}
.container {
  display: inline-block;
  width: 23vh;
  height: 23vh;
  margin: 1vh;
}
.card {
  width: 100%;
  height: 100%;
  font-size: 10vh;
  animation: <?=$dur?>s;
  animation-timing-function: linear;
animation-iteration-count: infinite;
  transform-style: preserve-3d;
  animation-name: flip; 
}
.card > div {
  position: absolute;
  height: 100%;
  width: 100%;
}
.front {
  background-size: 100vh 100vh;
}
.back {
  transform: translateZ(-.1px);
  background: #604050;
}
@keyframes flip {
  0% {transform: rotate3d(0.5, 0, 0, 0deg); }
  75% {transform: rotate3d(0.5, 0, 0, 0deg); }
  87% {transform: rotate3d(0.5, 0, 0, 180deg); }
  100% {transform: rotate3d(0.5, 0, 0, 360deg); }
}
.tpl-logo {
  border-radius: 50vw;
  height: 14vh;
}
#name { margin-left: 3vh; }
#lhs > div {
}
</style>
<style id=custom></style>
<div id=ad>
  <div id=lhs>
    <div id=top>
      <div>
        <div><img class=tpl-logo></div><div id=name class=tpl-name></div>
      </div>
    </div>

    <div id=bigtext><div class=tpl-bigtext>Please connect Yelp</div></div>
    <div id=smalltext><div class=tpl-description>Please connect Yelp</div></div>
    <div id=contact>
      <div>
        <span>@</span><span class=tpl-handle></span>
      </div>
    </div> 

  </div>

  <div id=image>
  <? for($ix = 0; $ix < 4; $ix ++) { ?>
    <div class=row>
      <? for($iy = 0; $iy < 4; $iy ++) { ?>
      <div class=container>
        <div style="animation-delay:<?= 20 * ($ix * 4 + $iy)?>ms" class="card">
          <div id=front-<?=$ix?>-<?=$iy?> class=front style="background-position:<?=$iy * 30?>% <?=$ix * 30?>%"></div>
          <div class=back style="background-position:<?=$iy * 30?>% <?=$ix * 30?>%"></div>
        </div>
      </div>
      <? } ?>
    </div>
  <? } ?>
  </div>
</div>

<script src="/js/jquery-3.0.0.min.js"></script>
<script src=template.js></script>
<script>
function randy(sz) {
  var
    s = [...Array(sz)].map((_, i) => i),
    ix = 0,
    swap,
    newp;

  for (; ix < sz; ix++) {
    newp = Math.floor(Math.random() * (sz - ix));
    if (newp != 0) {
      newp += ix;
      swap = s[newp];
      s[newp] = s[ix];
      s[ix] = swap;
    }
  }

  return s;
}

var _data, ix = 1;
let dur = <?= $dur ?>;
let ixMap = randy(4);
var start = new Date();
let tpl = template({
  id: <?= $id ?>,
  all: <?= json_encode($_GET); ?>,
  custom: {
    photoList: function (node, value, key, ix) {
      let custom = document.getElementById('custom');
      template.assign('bigtext', value[0].text);
      if(value.length) {
        custom.innerHTML = '.front { background-image: url("' + template.proxy(value[0].url) + '") }';
      }
    }
  }
});

function changer() {
  setTimeout(function() {
    $(`.front`).css('background-image', 'url(' + template.proxy(_data.photoList[ix % _data.photoList.length].url) + ')');
    let text = _data.photoList[ix % _data.photoList.length].text;
    if(text) {
      template.assign('bigtext', text.slice(0,100));
    }
    ix += 1;
  }, dur * 1000 * .87);
}

window.onload = function() {
  var el = document.querySelector('.card');
  ['animationiteration','animationstart'].forEach(row => {
    el.addEventListener(row, function() {
      changer();
    });
  });
}

function _cb(data) {
  _data = data;
  changer();
}
</script>
