
var start = new Date(), ads;

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
    return (window.localStorage && key in localStorage) ? localStorage[key] : null;
  },
  incr: function(key) {
    return db.kv_set(key, parseInt(db.kv_get(key) || "0", 10) + 1);
  }
};


window.onload = function init() {
  var bootcount = db.incr('bootcount'), 
    uid = window.location.href.split('/').pop(),
    features = {
      location: {exists: !!navigator.geolocation, available: null},
      localstorage: {exists: !!window.localStorage, available: db.kv_get('ping_count') > 0},
      panels: window.screen
    },

    ping_payload = {
      uid: uid,
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

  ads = Engine({
    doOliver: true,
    server: "/adserver/" + uid + "/",
    meta: {sow: {uid: uid} },
    debug: true,
    cb: {
      getDefault: function(success, fail) {
        function ondisk() {
          try{
            var myDefault = JSON.parse(db.kv_get('campaign') || "");
            if(myDefault) {
              success(myDefault);
            }
            return myDefault;
          } catch(ex) {
            return false;
          }
        }

        if(!ondisk()) {
          return ping(ondisk);
        }
      }
    }
  });

  ads.on('system', function(data) {
    var number = data.number ? data.number.slice(-7) : '??';
    document.getElementsByClassName('info')[0].innerHTML = [number, data.uuid.slice(0,5)].join(' ');
  });

  ads.on('jobEnded', function() {
    fetch(`${server}saveLocation`);
  });

  function ping(cb) {
    if(ping.lock) {
      return false;
    }

    ping.lock = true;
    ads.post('ping', ping_payload, function(data) {
      var screen = data.screen,
          campaign = data.default;

      ['port','model','project','serial'].map(function(key) {
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
        cb();
      }
    });
  }

  if(navigator.geolocation) {
    navigator.geolocation.watchPosition(
      function(pos) {
        features.location.available = true;
        ads.meta.sow.lat = pos.coords.latitude;
        ads.meta.sow.lng = pos.coords.longitude;
        ping_payload.location = {
          lat: ads.meta.sow.lat,
          lng: ads.meta.sow.lng
        };
      }, function() {
        features.location.available = false;
      }, {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      });
  }

  setInterval(ping, 3 * 60 * 1000);

  ads.Start();
}

