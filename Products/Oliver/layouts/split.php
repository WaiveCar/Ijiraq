<?php
$id = $_GET['id'];
?>
<!--
there's two ways

either place a fake grid on top of the picture and then do css animations for the boxes to change OR

make the boxes real by using background/offset and then I *think* you can do a css 3d transform to flip them around.

The real one is superior because I believe there is more flexibility maybe? I think the animation feature puts us into modern browser space regardless.
2020-08-13
-->
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  margin: 0;
  padding: 0;
  font-size: 6vh;
  text-align: center;
  color: white;
  font-family: Lora;
}
#ad {
  background: #303040;
  height: 100%;
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
}
#ad > div {
  position: relative;
  width: 32%;
}
#lhs, #rhs {
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  flex-direction: column;
}
#lhs {
  text-align: left;
  align-items: flex-start;
}
#lhs div{
  padding: 0 2vw;
}
#smalltext {
  font-weight: 300;
}
#contact { 
  font-size: 7vh;
}
#name {
  font-size: 8vh;
  margin-top: 3vh;
}
div#bigtext small {
  opacity: .5;
  font-style: italic;
  float: right;
}
div#bigtext {
  font-size: 3vmax;
  font-weight: 700;
  justify-content: flex-end;
}
#image img { 
  object-fit: cover;
  object-position: center;  
  height: 100%;
  width: 100%;
}

.tpl-logo {
  border-radius: 50vw;
  height: 33vh;
}
</style>
<div id=ad>
  <div id=lhs>
    <div id=bigtext class=tpl-bigtext>Big Text that is maybe 2 lines</div>
    <div id=smalltext class=tpl-description>Small Text for ad</div>
  </div><div id=image>
    <img data-index="0" class='tpl-photoList'>
  </div><div id=rhs>
    <div>
      <img class=tpl-logo>
      <div id=name class=tpl-name></div>
    </div>

    <div id=contact>
      <span>@</span><span class=tpl-handle></span>
    </div> 
  </div>
</div>

<script src=template.js></script>
<script>
let tpl = template({
  all: <?= json_encode($_GET); ?>,
  id: <?= $id ?>
});
</script>
