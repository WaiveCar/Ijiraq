  var Timeline = {
    _data: [], 
    // This goes forward and loops around ... *almost*
    // it depends on what happens, see below for more
    // excitement.
    position: 0,

    // This returns if thre is a next slot without looping.
    hasNext: function() {
      return Timeline._data.length > Timeline.position;
    },

    // This is different, this will loop.
    move: function(amount) {
      // the classic trick for negatives
      Timeline.position = (Timeline._data.length + Timeline.position + amount) % Timeline._data.length
      return Timeline._data[Timeline.position];
    },

    bath: function() {
      // scrub scrub, clean up time.
      // This is actually only 3 hours of 7 second ads
      // which we cut back to 2. This should be fine 
      if(Timeline._data.length > 1500 && Timeline.position > 500) {
        // this moves our pointer
        Timeline._data = Timeline._data.slice(500);
        // so we move our pointer.
        Timeline.position -= 500;
      }
    },

    add: function(job) {
      // Just adds it to the current place.
      Timeline._data.splice(Timeline.position, 0, job);
      Timeline.bath();
    },

    mostRecent: function() {
      if(Timeline._data.length > 1) {
        var last = (Timeline.position + Timeline._data.length - 1) % Timeline._data.length;
        return Timeline._data[last];
      }
    },

    addAtEnd: function(job) {
      Timeline._data.push(job);
      Timeline.bath();
    },
  };

  var Widget = {
    doTime: function() {
      var now = new Date();
      _box.time.innerHTML = [
          (now.getHours() + 100).toString().slice(1),
          (now.getMinutes() + 100).toString().slice(1)
        ].join(':')
    },
    feedMap: {},
    active: {},
    show: {
      weather: function(cloud, temp) {
        _box.widget.innerHTML = [
          "<div class='app weather-xA8tAY4YSBmn2RTQqnnXXw cloudy'>", 
          "<img src=/cloudy_" + cloud + ".svg>",
          temp + "&deg;",
          "</div>"
        ].join('');
      }
    },
    updateView: function(what, where) {
      Widget.active[what] = where;
      var hasBottom = Widget.active.time || Widget.active.ticker;
      var hasWidget = hasBottom || Widget.active.app;
      _res.container.classList[hasWidget ? 'add' : 'remove']('addon');
      _res.container.classList[hasBottom ? 'add' : 'remove']('hasBottom');
    },

    time: function(onoff) {
      Widget.updateView('time', onoff);
      if(onoff) {
        _box.time.style.display = 'block';
        if(!Widget._time) {
          Widget._time = setInterval(Widget.doTime, 1000);
          Widget.doTime();
        }
      } else {
        _box.time.style.display = 'none';
        clearInterval(Widget._time);
        Widget._time = false;
      }
    },
    app: function(feed) {
      if(arguments.length > 0) {
        Widget.updateView('app', feed);
        if(feed) {
          _box.widget.style.display = 'block';
          return Widget.show.weather(
            feed.summary.match(/partly/i) ? 50 : 0,
            Math.round(feed.temperature)
          );
        } 
        _box.widget.style.display = 'none';
      }
    },
    ticker: function(feed) {
      var amount = 1.4,
          delay =  30;
      if(arguments.length === 0) {
        return;
      }
      function scroll() {
        _box.ticker.style.opacity = 1;
        _box.ticker.scrollLeft = 1;
        clearInterval(Widget._ticker);
        Widget._ticker = setInterval(function(){
          var before = _box.ticker.scrollLeft;
          _box.ticker.scrollLeft += amount;
          if (_box.ticker.scrollLeft === before) {
            clearInterval(Widget._ticker);
            scroll();
          }
        }, delay);
      }
      Widget.updateView('ticker', feed);
      if(feed) {
        _box.ticker.style.display = 'block';
        if(feed.map) {
          _box.ticker.innerHTML = "<div class=ticker-content-xA8tAY4YSBmn2RTQqnnXXw>" + 
            shuffle(feed).map(row => "<span>" + row + "</span>") + "</div>";
        }

        scroll();
      } else {
        _box.ticker.style.display = 'none';
        clearInterval(Widget._ticker);
        Widget._ticker = false;
      }
    },
  };

