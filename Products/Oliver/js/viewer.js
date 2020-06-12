window.onload = function init() {
  let id = window.location.href.split('/').pop();

  self.ads = Engine({
    server: "adserver/" + id + "/"
  });

  ads.on('system', function(data) {
    var number = data.number ? data.number.slice(-7) : '??';
    document.getElementsByClassName('info')[0].innerHTML = [number, data.uuid.slice(0,5)].join(' ');
  });

  ads.on('jobEnded', function() {
    fetch(`${server}saveLocation`);
  });

  ads.Start();
}
