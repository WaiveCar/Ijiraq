var 
  uploadInput,
  _preview,
  _proto = 'https',
  _server_url = '9ol.es',
  _galleryMap = {},
  _provides = {},
  _assetList = [];

function calcItems() {
  requestAnimationFrame(() => {
    let schedule = JSON.parse($('#schedule').jqs('export'));
    let minutesPerWeek = schedule.reduce((acc, item) => {
      return (
        acc +
        item.periods.reduce((acc, period) => {
          return (
            acc +
            moment(period.end, 'hh:mm').diff(
              moment(period.start, 'hh:mm'),
              'minutes',
            )
          );
        }, 0)
      );
    }, 0);
    let budget = document.querySelector('#budget').value;
    let fakeNumImpressionsPerWeek = budget * 14.32;
    let fakeCPM = (fakeNumImpressionsPerWeek / budget / 100).toFixed(2);
    if (budget) {
      document.querySelector('#budget').textContent = `$${budget}`;
      document.querySelector('#cpm').textContent = `$${fakeCPM}`;
      document.querySelector(
        '#impressions',
      ).textContent = `${fakeNumImpressionsPerWeek}`;
      document.querySelector('#price').textContent = `$${budget}`;
    }
  });
}

function create_campaign(obj) {
  // Before the payment is processed by paypal, a user's purchase is sent to the server with 
  // the information that has so far been obtained including the picture.
  let formData = new FormData();
  let valMap = myform.getValues();
  for(var key in valMap) {
    formData.append(key, valMap[key]);
  }
  formData.append('geofence', _map.save());

  /*
  for(var ix = 0; ix < _job.assetList.length; ix++) {
    formData.append('file' + ix, _job.assetList[ix].url);
  }
  */
  for(var ix = 0; ix < uploadInput.files.length; ix++) {
    formData.append('file' + ix, uploadInput.files[ix]);
  }

  return axios({
    method: 'post',
    url: `${_proto}://${_server_url}/api/campaign`,
    data: formData,
    config: {
      headers: { 'Content-Type': 'multipart/form-data' },
    },
  }).then(function(resp) {
    window.location = '${_proto}://' + window.location.hostname + '/campaigns';
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

function get(ep, cb) {
  fetch(new Request(`${_proto}://${_server_url}/api/${ep}`))
    .then(res => {
      if (res.status === 200) {
        return res.json();
      }
    }).then(cb);
}

function post(ep, body, cb) {
  fetch(new Request(`${_proto}://${_server_url}/api/${ep}`, {
    method: 'POST', 
    body: JSON.stringify(body)
  })).then(res => {
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


function instaGet() {
  function Gen() {
    $(".insta .selector").remove();
    var ix = 1;
    var selected = [];
    selector.forEach(function(row) {
      row.innerHTML += `<div class=selector>${ix}</div>`;
      ix++;
      selected.push(row.dataset.standard);
    })
    var param = selected.map(row => `images[]=` + row.replace(/\?/,'%3F').replace(/\&/g, '%26')).join('&');

    _preview.AddJob({
      url: `/templates/aviv.php?id=${_provides.id}`
    });

    for(var engine_ix = 0; engine_ix < Engine._length; engine_ix++) {
      let engine = Engine[engine_ix];
      if(engine.name) {
        console.log("Loading " + engine.name);
        engine.AddJob({ 
          url: `/templates/${engine.name}.php?id=${_provides.id}`
        });
        engine.Start();
      }
    }

    _preview.Start();
  }

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

      $('.insta .profile img').attr('src', user.profile_pic);
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
        let id = post.id, img = post.media_url;

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
function loadMap() {
  var mymap = document.querySelector('#map-summary');
  mymap.style.height = mymap.clientWidth * 675/1920 + 'px';
  navigator.geolocation.getCurrentPosition(function(pos) {
    let loc = [
      pos.coords.longitude,
      pos.coords.latitude
    ];
    let mymap = map({
      target: 'map-summary',

      selectFirst: true,
      opacity: 0.6,
      tiles: 'stamen.toner',
      move: true,
      zoom: 12.5,

      center: loc,
    });
    mymap.load([['Circle', loc, 2256]]);
  });
}

window.onload = function(){
  self._container =  document.getElementById('engine');
  var isFirst = true;
  var ratio = 'car';
  $(".adchoice .card").click(function() {
    $(".adchoice .card").removeClass('selected');
    $(this).addClass('selected')
  });

  setRatio(_container, 'car'); 
  self._preview = Engine({ 
    container: _container,
    dynamicSize: true,
    _debug: true });
  self._job = _preview.AddJob();

  if(_me.instagram) {
    instaGet();
  }
  $(".controls .rewind").click(function() {
    // this is a lovely trick to force the current job
    // which effectively resets itself
      _preview.PlayNow(_job, true);
    });

  let tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
  document.getElementById('startdate').value = [
    tomorrow.getFullYear(),
    (100 + tomorrow.getMonth()).toString().slice(1),
    (100 + tomorrow.getDay()).toString().slice(1),
  ].join('-');


  $(".engine-container").each(function() {
    let template = this.dataset.template;
    this.style.height = .351 * this.clientWidth + "px";
    this.parentNode.style.height = 1.5 * .351 * this.clientWidth + "px";

    _galleryMap[template] = Engine({
      container: this,
      dynamicSize: true,
      _debug: true
    });

    _galleryMap[template].name = template;
  });

  loadMap();

  $(".ratios button").click(function(){
    $(this).siblings().removeClass('active');
    $(this).addClass('active');
    ratio = this.innerHTML.replace(':', '-').toLowerCase();
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


}

