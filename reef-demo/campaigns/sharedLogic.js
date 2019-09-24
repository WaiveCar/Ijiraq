var 
  _preview,
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
    }
  });
}

(() => {
 let campaignBudget = document.getElementById('campaign-budget');
  if (campaignBudget) {
    $('#schedule').jqs();
    document
      .getElementById('campaign-budget')
      .addEventListener('change', calcItems);
    document
      .getElementById('campaign-budget')
      .addEventListener('keyup', calcItems);
    document
      .querySelector('.jqs-table tbody')
      .addEventListener('mouseup', calcItems);
    }
})();

function create_campaign(obj) {
  // Before the payment is processed by paypal, a user's purchase is sent to the server with 
  // the information that has so far been obtained including the picture.
  let formData = new FormData();
  $(document.forms[0]).serializeArray().forEach(function(row) {
    state[row.name] = row.value;
    formData.append(row.name, row.value);
  });
  state.total = dealMap[state.option].price;

  /*
  for(var ix = 0; ix < uploadInput.files.length; ix++) {
    formData.append('file' + ix, uploadInput.files[ix]);
  }
  */
  for(var ix = 0; ix < _job.assetList.length; ix++) {
    formData.append('file' + ix, _job.assetList[ix]);
  }

  return axios({
    method: 'post',
    url: 'http://waivescreen.com/api/campaign',
    data: formData,
    config: {
      headers: { 'Content-Type': 'multipart/form-data' },
    },
  }).then(function(resp) {
    if(resp.res) {
      state.campaign_id = res.data;
    }
    if(!obj) {
      return true;
    }
    return obj.payment.create({
      payment: {
        transactions: [
          {
            amount: {
              total: (state.total / 100).toFixed(2),
              currency: 'USD',
            }
          }
        ]
      }
    });
  });
}
function resize(asset, width, height) {
  if( height * (1920/756) > width) {
    asset.style.height = '100%';
  } else {
    asset.style.width = '100%';
  }
}
function addtime(n) {
  if(n === false) {
    duration = 0;
    $("#runtime").hide();
  } else {
    duration += n;
    if(duration == 0) {
      $("#runtime").hide();
    } else {
      $("#runtime").html("Runtime: " + duration.toFixed(2) + " sec").show();
    }
  }
}
function setRatio(container, what) {
  if(what == 'car') {
    container.style.height = (.351 * container.clientWidth) + "px";
  }
}

function post(ep, body, cb) {
  fetch(new Request(`http://waivescreen.com/api/${ep}`, {
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

function get(id) {
  var res = Data.filter(row => row.id == id);
  return res ? res[0] : null;
}
function doMap() {
  $.getJSON("http://waivescreen.com/api/screens?active=1&removed=0", function(Screens) {
    self._map = map({points:Screens});
    let success = false;

    if(success) {
      _map.load(_campaign.shape_list);
    } else {
      _map.center([-118.34,34.06], 11);
    }
  });
}

function clearmap() {
  _map.clear();
}

function removeShape() {
  _map.removeShape();
}

function geosave() {
  var coords = _map.save();
  // If we click on the map again we should show the updated coords
  _campaign.shape_list = coords;
  post('campaign_update', {id: _id, geofence: coords}, res => {
    show({data: 'Updated Campaign'}, 1000);
  });
}

window.onload = function(){
  self._container =  document.getElementById('engine');
  let map = document.getElementById('map');
  if (map) {
    doMap();
  }
  var isFirst = true;
  if (self._container) {
    setRatio(_container, 'car'); 
    self._preview = Engine({ 
    container: _container,
    dynamicSize: true,
    _debug: true });
    self._job = _preview.AddJob();
    $(".controls .rewind").click(function() {
      // this is a lovely trick to force the current job
    // which effectively resets itself
      _preview.PlayNow(_job, true);
    });
  }



  // The event handler below handles the user uploading new files
  uploadInput = document.getElementById('image-upload');
    if (uploadInput) {
    uploadInput.addEventListener('change', function() {
      var container = $(".preview-holder");

      addtime(false);
      Array.prototype.slice.call(uploadInput.files).forEach(function(file) {

        let reader = new FileReader();

        reader.onload = function(e) {
          var asset, reference;

          let row = $(
            ['<div class="screen">',
               '<img src="/assets/screen-black.png" class="bg">',
               '<button type="button" class="remove-asset btn btn-sm btn-dark">',
               '<i class="fas fa-times"></i>',
               '</button>',
               '<div class="asset-container"></div>',
            '</div>'].join(''));

          reference = _job.append(e.target.result);

          if(file.type.split('/')[0] === 'image') {
            asset = document.createElement('img');
            asset.onload = function() {
              resize(asset, asset.width, asset.height);
              container.append(row);
              addtime( 7.5 );
            }

            asset.src = e.target.result;
            asset.className = 'asset';
          } else {
            asset = document.createElement('video');
            var src = document.createElement('source');

            asset.setAttribute('preload', 'auto');
            asset.setAttribute('loop', 'true');
            asset.appendChild(src);

            src.src = e.target.result;

            asset.ondurationchange = function(e) {
              asset.currentTime = 0;
              asset.play();
              resize(asset, asset.videoWidth, asset.videoHeight);
              container.append(row);
              addtime( e.target.duration );
            }
          }

          $(".remove-asset", row).click(function() {
            _job.remove(reference);
            row.remove();
          });

          $(".asset-container", row).append(asset);
        };
        reader.readAsDataURL(file);
      });

      if(isFirst) {
        _preview.Play();
        isFirst = false;
      }
    });
  }
}

