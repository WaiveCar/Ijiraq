window.onload = function init() {
  let uid = window.location.href.split('/').pop();

  self.ads = Engine({
    doOliver: true,
    server: "/adserver/" + id + "/",
    meta: {sow: {uid: uid} }
  });

  ads.on('system', function(data) {
    var number = data.number ? data.number.slice(-7) : '??';
    document.getElementsByClassName('info')[0].innerHTML = [number, data.uuid.slice(0,5)].join(' ');
  });

  ads.on('jobEnded', function() {
    fetch(`${server}saveLocation`);
  });


  if(navigator.geolocation) {
    navigator.geolocation.watchPosition(
      function(pos) {
        ads.meta.sow.lat = pos.coords.latitude;
        ads.meta.sow.lng = pos.coords.longitude;
      }, function() {
      }, {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      });
  }

  ads.Start();
}
