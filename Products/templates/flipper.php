<!--
there's two ways

either place a fake grid on top of the picture and then do css animations for the boxes to change OR

make the boxes real by using background/offset and then I *think* you can do a css 3d transform to flip them around.

The real one is superior because I believe there is more flexibility maybe? I think the animation feature puts us into modern browser space regardless.
2020-08-13
-->
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&display=swap" rel="stylesheet">
<meta http-equiv="refresh" content="50">
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
  margin: 2vh 8vh;
  font-size: 8vh;
  color: white;
  height: 96vh;
}
#lhs > div {
  flex-direction: column;
  justify-content: center;
  display: flex;
  height: 25%;
}
#top div {
  display: inline-block;
}
#name,#bigtext,#contact {
  font-family: Lora;
}
div#smalltext {
  font-family: Helvetica, Arial, sans-serif;
  font-weight: normal;
  color: pink;
  justify-content: flex-start;
}
div#smalltext div {
  margin-top: 5vh;
}

div#bigtext {
  font-size: 12vh;
  font-weight: 700;
  margin: 0;
  line-height: 1;
  justify-content: flex-end;
}
div#bigtext div {
  margin-bottom: 5vh;
}
#image { 
  width: 100vh;
  height: 100vh;
  flex: 0 0 100vh;
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
  animation: .5s;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
  transform-style: preserve-3d;
  transform: rotate3d(0.5, 0, 0, 180deg);
  animation-name: flip; 
}
.card > div {
  position: absolute;
  height: 100%;
  width: 100%;
}
.front {
  background-image: url('square.jpg');
  background-size: 100vh 100vh;
}
.back {
  transform: translateZ(-.1px);
  background: #604050;
}
@keyframes flip {
  0% {transform: rotate3d(0.5, 0, 0, 180deg); }
  100% {transform: rotate3d(0.5, 0, 0, 0deg); }
}
#lhs > div {
}
</style>
<div id=ad>
  <div id=lhs>
    <div id=top>
      <div>
        <div id=logo>L</div><div id=name>COMPANY</div>
      </div>
    </div>

    <div id=bigtext><div>Big Text that is maybe 2 lines</div></div>
    <div id=smalltext><div>Small Text for ad</div></div>
    <div id=contact>@Contact</div> 

  </div>
  <div id=image>
  <? for($ix = 0; $ix < 4; $ix ++) { ?>
    <div class=row>
      <? for($iy = 0; $iy < 4; $iy ++) { ?>
      <div class=container>
        <div style="animation-delay:<?= 40 * ($ix * 4 + $iy)?>ms" class="card">
          <div class=front style="background-position:<?=$iy * 30?>% <?=$ix * 30?>%"></div>
          <div class=back></div>
        </div>
      </div>
      <? } ?>
    </div>
  <? } ?>
  </div>
</div>
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
let ixMap = randy(4);
</script>

