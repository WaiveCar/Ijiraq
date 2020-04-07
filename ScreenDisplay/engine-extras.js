  Strategy.Oliver = (function( ) {
    var topicMap = {},
      // we can override this when we get the
      // default.
      topicList = [],
      current = false,
      jobIx = 0,
      activeList = [],
      doReplace = true,
      topicIx = 0;

    function render(forceOff) {
      if(forceOff || !topicList[topicIx].internal) {
        _res.container.classList.remove('hasTopicList');
      } else {
        _res.container.classList.add('hasTopicList');
        // make only the active topicList
        _box.topicList.forEach((row, ix) => row.classList[ix === topicIx ? 'add' : 'remove'](_key('active')));
      }
    }

    function nextTopic() {
      //
      // Essentially we gather all the active jobs, then we group
      // them by "topic" which is a field in the campaign.
      // Amongst each topic we arrange them by order of how
      // much of our "contract" we need to play out and then
      // just go through that list.
      //
      // The only real catch here is we don't change our idea
      // of what jobs are applicable to us until the current
      // topic is exhausted. 
      //
      // Even then because we want to commit to at least some 
      // form of continuity, if the new set does not contain 
      // jobs of the next topic then we go to it anyway and 
      // show some default campaign associated with that topic.
      //
      // This method *ONLY* looks not broken if we commit
      // ourselves to having a limited number of topics we
      // can choose from.  
      //

      activeList = Object.values(_res.db).filter(row => row.duration);

      //
      // We need to clear out our local copy of the ads
      // and repopulate.
      //
      topicMap = {};

      activeList.forEach(row => {
        // The null case is actually ok here.
        if (!topicMap[row.topic]) {
          topicMap[row.topic] = [];
        }
        //
        // This may be fairly inefficient since we are remaking
        // jobs that we may have previously made.
        //
        topicMap[row.topic].push(makeJob(row));
      });

      topicIx = (topicIx + 1) % topicList.length;
      jobIx = 0;

      // So we know our topic now, it's topicIx, which is an
      // integer offset in topicList
      //
      // This could be null or empty, fine ... but
      // it's kinda the server's responsibility to
      // make sure there's default campaigns for each
      // of these.
      current = topicMap[topicList[topicIx].internal];

      if(!current) {
        current = topicMap[null];
      }

      //console.log(_id, current, activeList, topicMap, _res.db);

      render();
      nextJob();
    }

    function nextJob() {
      if(!current) {
        // This means we've really fucked up somehow
        doReplace = true;
        if(!_.fallbackJob) {
          console.warn(_id, "I'm at a nextJob but have no assets or fallbacks");
          return _timeout(_res.NextJob, 1500, 'nextJob');
        }
        setNextJob(_.fallbackJob);

        // Force the topics off for now.
        render(true);

        // nextAsset is at the bottom
      } else {
        // console.log(topicMap, current, jobIx, topicList);
        
        if(jobIx === current.length) {
          nextTopic();
        }
        //
        // We are assuming a bunch here. essentially that we
        // have hit the nextTopic to assign a current pointer 
        // and that our sequential revisiting will handle our
        // mechanics correctly.
        //
        setNextJob( current[jobIx] );
        jobIx++;

        //
        // We'll go to the next topic at the end of showing
        // our ad. However, we need to make sure that we have
        // flagged our sow strategy to replace before we 
        // go into our timeout.
        // 
        if(jobIx === current.length) {
          doReplace = true;
        }
      }
      nextAsset();
    }

    function forgetAndReplaceWhenFlagged(list) {
      if(doReplace) {
        doReplace = false;
        forgetAndReplace(list);
      }
    }

    function newTopic() {
      var dom = document.createElement('div');
      dom.className = _key('topic');
      _box.topicContainer.appendChild(dom);
      return dom;
    }

    function enable() {
      // This enables the top category and swaps out the nextJob with us
      _res.NextJob = nextTopic;
      _box.topicList = [];
      setTopicList([
        {internal: 'event', display: 'Events'},
        {internal: 'help', display: 'Notices'},
        {internal: 'service', display: 'Services'}
      ]);
      sow.strategy = forgetAndReplaceWhenFlagged;
    }

    function setTopicList(list) {
      for(var ix = _box.topicList.length; ix < list.length; ix++) {
        _box.topicList.push(newTopic());
      }
      topicList = list;
      topicList.forEach((row, ix) => _box.topicList[ix].innerHTML = row.display);
      topicList.push( {internal: null, display: null} );
      render();
    }

    return { setTopicList, nextJob, enable };
  })();

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

