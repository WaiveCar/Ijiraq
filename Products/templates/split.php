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
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&display=swap" rel="stylesheet">
<style>
body {
  margin: 0;
  padding: 0;
  font-size: 6vh;
  text-align: center;
  color: white;
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
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  flex-direction: column;
}
#lhs > div {
  flex-direction: column;
  justify-content: center;
  display: flex;
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
  font-weight: normal;
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
#image img { 
  width: 100%;
}

.tpl-logo {
  border-radius: 50vw;
  height: 33vh;
}
</style>
<style id=custom></style>
<div id=ad>
  <div id=lhs>

    <div id=bigtext><div>Big Text that is maybe 2 lines</div></div>
    <div id=smalltext><div class=tpl-description>Small Text for ad</div></div>

  </div><div id=image>
    <img data-index="0" class='tpl-photoList'>
  </div><div id=rhs>
    <div>
      <img class=tpl-logo>
      <div id=name class=tpl-name></div>
    </div>

    <div id=contact>
      <div>
        <span>@</span><span class=tpl-handle></span>
      </div>
    </div> 
  </div>
</div>

<script src=template.js></script>
<script>
let tpl = template({
  id: <?= $id ?>
});
</script>
