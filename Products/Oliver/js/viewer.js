
var start = new Date(), 
    features,
    ads;

function get_uptime() {
  return new Date() - start;
}
var db = {
  kv_set: function(key, value) {
    if(window.localStorage) {
      localStorage[key] = value;
    }
    return value;
  },
  kv_get: function(key) {
    if (window.localStorage) {
      if (key) {
        return (key in localStorage) ? localStorage[key] : null
      }
      return window.localStorage;
    }
  },
  incr: function(key) {
    return db.kv_set(key, parseInt(db.kv_get(key) || "0", 10) + 1);
  }
};


function middleware(verb, url, what, onsuccess, onfail) {
  var args = Array.prototype.slice.call(arguments);

  if(args[1] == 'sow') {

    var payload = { uid: db.kv_get('uid') };
    db.kv_set('last_sow', +new Date());

    if (features.location.available) {
      payload.lat = db.kv_get('lat');
      payload.lng = db.kv_get('lng');
    }
    payload.jobs = what;
    args[2] = payload;
  }

  return args;
}



window.onload = function init() {
  features = {
    location: {exists: !!navigator.geolocation, available: null},
    localstorage: {exists: !!window.localStorage, available: db.kv_get('ping_count') > 0},
    panels: window.screen
  };

  var bootcount = db.incr('bootcount'), 
    uid = db.kv_get('uid') || Math.floor(Math.random() * 1e16).toString(36),
    hoard_id = window.location.href.split('/').pop(),

    ping_payload = {
      // I'm certainly permitted to assert my uid, but the
      // server is the one that decides here.
      uid: uid,
      hoard_id: hoard_id,
      uptime: get_uptime(),
      bootcount: bootcount,
      ping_count: db.incr('ping_count'),
      // this is filled in by the navigator geoLocation watcher
      location: null,
      last_uptime: null, // for now
      //version: get_version(),
      last_task: db.kv_get('last_task') || 0,
      last_task_result: null,
      features: features,
      modem: null,
    };

  document.title = hoard_id.slice(-6) + "@" + uid.slice(-6);
  ads = Engine({
    doOliver: true,
    server: "/adserver/" + uid + "/",
    middleware: middleware,
    // The hoard gets appended to the SOW requests and 
    // that's how the accounting works.
    meta: {
      sow: {
        hoard_id: hoard_id,
        uid: uid
      } 
    },
    debug: true,
    cb: {
      getDefault: function(success, fail) {
        function ondisk() {
          var myCampaign = db.kv_get('campaign');
          var myDefault = JSON.parse(myCampaign || "{}");
          if(myDefault) {
            success({
              data: {
                system: {},
                campaign: myDefault
              } 
            });
          }
          return myDefault;
        }

        if(!ondisk()) {
          return ping(ondisk);
        }
      }
    }
  });

  ads.on('system', function(data) {
    console.log(data);
    var number, id;
    if(data.uuid) {
      number = data.number ? data.number.slice(-7) : '??';
      id = [number, data.uuid.slice(0,5)].join(' ');
    } else {
      id = "unknown";
    }
    document.getElementsByClassName('info')[0].innerHTML = id;
  });

  ads.on('jobEnded', function() {
    fetch(ads.server + "saveLocation");
  });
  if(navigator.geolocation) {
    try {
      navigator.geolocation.watchPosition(
        function(pos) {
          features.location.available = true;
          db.kv_set('lat', pos.coords.latitude);
          db.kv_set('lng', pos.coords.longitude);

          ping_payload.location = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude
          };
          if(ads && ads.meta) {
            ads.meta.sow.lat = pos.coords.latitude;
            ads.meta.sow.lng = pos.coords.longitude;
          }
        }, function() {
          features.location.available = false;
        }, {
          enableHighAccuracy: true,
          timeout: 5000,
          maximumAge: 0
        });
    } catch (ex) {
      console.log('navigator.geolocation', ex)
    }
  }

  function ping(cb) {
    if(ping.lock) {
      return false;
    }

    ping.lock = true;

    ads.post('ping', ping_payload, function(data) {
      var screen = data.screen,
          campaign = data.default;

      ['port','model','project','serial','uid'].map(function(key) {
        if (key in screen) {
          db.kv_set(key, screen[key]);
        }
      });

      ['bootcount','ping_count'].map(function(key) {
        if (key in screen) {
          var server_value = parseInt(screen[key], 10);
          my_value = parseInt(db.kv_get(key)) || 0;
          if (server_value > (3 + my_value)) {
            db.kv_set(key, server_value);
          }
        }
      });

      db.kv_set('campaign', JSON.stringify(campaign));
      db.kv_set('lastping', db.kv_get('runcount')) 

      // task_ingest(data)

      ping.lock = false;
      if(cb) { 
        cb(data);
      }
    });
  }

  ping(function(res) {
    ads.meta.sow.uid = db.kv_get('uid');
    ads.Start();
  });
  setInterval(ping, 3 * 60 * 1000);
}

