var 
  uploadInput,
  _preview,
  _proto = 'https',
  _server_url = 'olvr.io',
  _layout_base = `${_proto}://${_server_url}/layouts`,
  _galleryMap = {},
  _provides = {},
  _layout = {name: 'aviv', duration: 15},
  _valMap = {},
  _map = null,
  _loc = [-118.32, 34.09],
  _assetList = [];

function tplUrl(tpl) {
  return `${_layout_base}/${tpl}.php?id=${_provides.user_id}&order=${_layout.order}`;
}

function create_campaign(obj) {
  // Before the payment is processed by paypal, a user's purchase is sent to the server with 
  // the information that has so far been obtained including the picture.
  let formData = new FormData();

  for(var key in _valMap) {
    formData.append(key, _valMap[key]);
  }

  ['email','phone','startdate'].forEach(row => formData.append(row, $(`#${row}`).val()));

  formData.append('geofence', _map.save());
  formData.append('asset[0][url]', tplUrl(_layout.name));
  formData.append('asset[0][duration]', _layout.duration);

  return axios({
    method: 'post',
    url: `${_proto}://${_server_url}/api/campaign`,
    data: formData,
    config: {
      headers: { 'Content-Type': 'multipart/form-data' },
    },
  }).then(function(resp) {
    window.location = _proto + '://' + window.location.hostname + '/campaigns#' + resp.data;
  });
}
function resize(asset, width, height) {
  if( height * (1920/756) > width) {
    asset.style.width = '100%';
  } else {
    asset.style.height = '100%';
  }
}
function setRatio(container, what) {
  if(what == 'car') {
    container.style.height = (.351 * container.clientWidth) + "px";
  }
}

function loadMap() {
  var mymap = document.querySelector('#map-summary');
  mymap.style.height = mymap.clientWidth * 675/1920 + 'px';

  function showMap(loc) {
    _map = map({
      target: 'map-summary',

      selectFirst: true,
      opacity: 0.6,
      tiles: 'stamen.toner',
      move: true,
      zoom: 12.5,

      center: loc,
    });
    _map.load([['Circle', loc, 2256]]);
  }

  navigator.geolocation.getCurrentPosition(function(pos) {
    _loc = [
      pos.coords.longitude,
      pos.coords.latitude
    ];
    showMap(_loc);
  }, function(err) {
    showMap(_loc);
    console.warn("geolocation issue:", err);
  });
}

function get(ep, cb) {
  fetch(new Request(`${_proto}://${_server_url}/api/${ep}`))
    .then(res => {
      if (res.status === 200) {
        return res.json();
      }
    }).then(cb);
}

var _shown = false;
function show(what) {
  if(_shown && _shown != what) {
    $(`.${_shown}-wrapper`).slideUp();
  }
  $(`.${what}-wrapper`).slideDown(function() {
    if(what == 'creatives') {
      setRatio(_container, 'car'); 
    }
  });
  _shown = what;
}

function yelpchoose(el) {
  $(el).addClass('selected').siblings().hide();
  $('.btn', el).slideUp();
  $('.chosen', el).slideDown();
  get('yelp_save?id=' + el.dataset.id, (res) => {
    instaregen();
  });
}

function yelpsearch(){
  // TODO: REMOVE AFTER DEMO
  var loc = [-118.32, 34.09];

  get("yelp_search?" +
    $.param({
      query: $("#yelp-search-input").val(),
      longitude: loc[0],
      latitude: loc[1]
    }), (res) => {
      $(".yelp .search .results").html('')
      res.businesses.forEach(row => {
        $(".yelp .search .results").append(
          `<div onclick=yelpchoose(this) data-id="${row['id']}" class="card mb-3" >
          <div class="card-body row">
            <div class="col-3">
             <img src="${row['image_url']}">
            </div>
            <div class="col-9">
              <h3>${row['name']}</h3>
              <h4>${row['phone'].slice(1)}</h4>
              <div class=bothstates>
                <button class="btn" type="button">Use this business</button>
                <div class=chosen>&check; Selected</div>
              </div>
            </div>
          </div>
         </div>
        `);
      });
    } 
  );
}

function yelpshow(){
  $(".socnet-wrapper").removeClass('unselected');
  $(".login.yelp").addClass('selected').siblings().removeClass('selected');
  $(".customize .yelp").show().siblings().hide();
  $("#yelp-search-input").focus();
}

function proxy (url) {
  return '/api/proxy?url=' + encodeURIComponent(url);
}

function instaGet() {
  var Gen = self.instaregen = function() {
    $(".insta .selector").remove();
    var ix = 1;
    var selected = [];
    selector.forEach(function(row) {
      row.innerHTML += `<div class=selector>${ix}</div>`;
      ix++;
      selected.push(row.dataset.standard);
    })

    // this is a hack. absolutely a hack.
    _layout.order = selected.map(row => row.replace(/\?/,'%3F').replace(/\&/g, '%26')).join(',');

    _preview.FAP({ url: tplUrl(_layout.name) });

    for(var engine_ix = 0; engine_ix < Engine._length; engine_ix++) {
      let engine = Engine[engine_ix];
      if(engine.name) {
        console.log("Loading " + engine.name);
        engine.FAP({ url: tplUrl(engine.name) });
        engine.Start();
      }
    }

    _preview.Start();
  }
  $(".customize .insta").show().siblings().hide();

  var selector = [];
  self.s = selector;
  get('instagram?info=1', function(res) {
    var row, content = [];

    $(".login.instagram .tab-title").html("Instagram");
    if(!res.res) {
      $(".insta .login").css("display","flex");
      return;
    }
    res = res.data;
    _provides = res;
    var user = res.data.user;
    // todo: fix the data format post-demo
    let posts = res.data.posts;
    if (user) {

      $('.insta .profile img').attr('src', proxy(user.profile_pic));
      $('.insta .info .name').html( user.username );
      $('.insta .info .description').html( user.full_name );
      let ix = 0;

      posts.data.forEach((post) => {
        if(ix > 17) { return }
        if(!(ix % 3)) {
          if(row) {
            content.push("<div class=row>" + row.join('') + "</div>");
          }
          row = [];
        }
        let id = post.id, img = post.url;

        row.push(`<div class='box' data-standard='${id}'><img src=${img}></div>`);

        ix++;
      });
    }
    if(row) {
      content.push("<div class=row>" + row.join('') + "</div>");
    }
    $('.insta .content').html( content.join('') );
    setTimeout(function(){
      $(".insta .content .box").each(function() {
        console.log(this);
        if(selector.length < 6) {
          selector.push(this);
        }
      });
      Gen();
    }, 10);
    $(".insta .content .box").click(function() {
      var exists = selector.filter(row => row.dataset.standard == this.dataset.standard);
      if(exists.length) {
        selector = selector.filter(row => row.dataset.standard != this.dataset.standard);
      } else {
        if(selector.length < 6) {
          selector.push(this);
        } else {
          // don't gen.
          return;
        }
      }
      Gen();
    });
    $(".insta .mock").fadeIn(1000);

  });
}

function receipt_update() {
  $("#receipt-date").html(document.getElementById('startdate').value)
}

function logout() {
  get('logout', function(){
    location.reload();
  });
}

window.onload = function(){
  //
  // Layout selector
  //
  $(".adchoice .card").click(function() {
    let which = this.querySelector('.engine-container');
    _layout.name = which.dataset.name; 
    _layout.duration = which.dataset.duration;

    _preview.FAP({ url: tplUrl(_layout.name) });

    $(".adchoice .card").removeClass('selected');
    $(this).addClass('selected')
  });

  $(".engine-container").each(function() {
    let template = this.dataset.name;
    this.style.height = .351 * this.clientWidth + "px";
    this.parentNode.style.height = 1.5 * .351 * this.clientWidth + "px";

    _galleryMap[template] = Engine({
      container: this,
      dynamicSize: true,
      _debug: true
    });

    _galleryMap[template].name = template;
  });

  
  $("#yelp-search-input").keypress(function (e) {
    if (e.which == 13) {
      yelpsearch();
    }
  });


  var promo_ison = false;
  $("#promo").on('keyup', function(){
    if($("#promo").val().toUpperCase() == 'FREEME') {
      promo_ison = true;

      $(".price").html('FREE');
      $("#cc-card").fadeOut();
    } else if(promo_ison) {
      promo_ison = false;
      $(".price").html('$4.00');
      $("#cc-card").fadeIn();
    }
  });
  //
  // Preview engine
  //
  self._container =  document.getElementById('engine');
  if(_container) {
    setRatio(_container, 'car'); 
    self._preview = Engine({ 
      container: _container,
      dynamicSize: true,
      _debug: true });
    self._job = _preview.AddJob();

    $(".ratios button").click(function(){
      $(this).siblings().removeClass('active');
      $(this).addClass('active');
      var ratio = this.innerHTML.replace(':', '-').toLowerCase();
      if(this.innerHTML == "16:9") {
        _container.style.width = _container.clientHeight * 16/9 + "px";
      } else if(this.innerHTML == "3:2") {
        _container.style.width = _container.clientHeight * 3/2 + "px";
      } else {
        _container.style.width = "100%";
      }
        $(`.preview-holder-${ratio}`).siblings().removeClass('selector');
        $(`.preview-holder-${ratio}`).addClass('selector');
    });

    //
    // Date Selector
    //
    
    let tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);

    document.getElementById('startdate').value = [
      tomorrow.getFullYear(),
      (100 + tomorrow.getMonth() + 1).toString().slice(1),
      (100 + tomorrow.getDate()).toString().slice(1),
    ].join('-');

    //
    // Content loading
    //
    if(_me.instagram && self._preview) {
      $(".socnet-wrapper").removeClass('unselected');
      $(".login.instagram").addClass('selected');
      instaGet();
    }

    receipt_update();
    loadMap();
  }
}

