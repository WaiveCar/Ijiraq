// The cliff for the browser compatibility of this code is 7 years ago from now.
if (typeof Object.assign !== 'function') {
  // Must be writable: true, enumerable: false, configurable: true
  Object.defineProperty(Object, "assign", {
    value: function assign(target, varArgs) { // .length of function is 2
      'use strict';
      if (target === null || target === undefined) {
        throw new TypeError('Cannot convert undefined or null to object');
      }

      var to = Object(target);

      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];

        if (nextSource !== null && nextSource !== undefined) {
          for (var nextKey in nextSource) {
            // Avoid bugs when hasOwnProperty is shadowed
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }
      return to;
    },
    writable: true,
    configurable: true
  });
}

function template(opts) {
  var
    _res = Object.assign({
      server: "/api/provides",

      db: {},
      custom: {},
      all: {},

      duration: 7.5,
      id: false,

      target: { width: 1920, height: 675 },

    }, opts || {});

  var 
    exclude_list = ['created_at','id'], 
    orderList = false;

  let obj2kvargs = function(params){
    return Object.keys(params).map(function(key){
      return key + '=' + params[key];
    }).join('&');
  }

  if(_res.all.order) {
    orderList = _res.all.order.split(',');
    console.log(orderList);
  }

  function assign(node, value, key, ix) {
    if(key in _res.custom) {
      return _res.custom[key](node, value, key, ix);
    }

    if(!value) {
      return;
    }
    let is_url = value.match(/^https?:\/\//i);
    if(node.tagName === 'IMG') {
      if(is_url) {
        node.src = template.proxy(value);
      }
    } else {
      node.innerHTML = value;
    }
  }

  function reorder(data) {
    if(orderList) {
      var lookup = {};
      data.photoList.forEach(function(row) {
        return lookup[row.id] = row;
      });
      data.photoList = orderList.map(function(row) {  
        return lookup[row];
      });
    }
    return data;
  }

  function parser(data) {
    data = reorder(data);

    _res._last_data = data;
    let list = Object.keys(data).filter(function(x) {
      return !(x in exclude_list);
    });
    for (let key of list) {
      let matchList = document.querySelectorAll(".tpl-" + key);

      if(!matchList.length && key in _res.custom) { 
        _res.custom[key](null, data[key], key);

      } else if(Array.isArray(data[key])) {
        for(let node of matchList) {
          var which = parseInt(node.dataset.index, 10);
          if(data[key][which]) {
            assign(node, data[key][which].url, key, which);
          }
        }
      } else {
        for(let node of matchList) {
          assign(node, data[key], key);
        }
      }
    }
    if(self._cb) {
      _cb(data);
    }
  }

  function remote() {
    fetch([_res.server, obj2kvargs(_res.all)].join('?'))
      .then(function(response) {
        return response.json();
      })
      .then(parser)
  }
  
  function load() {
    if(_res.data){
      parser(_res.data);
    } else if((_res.id || _res.all.username) && _res.server) {
      remote();
    } else {
      console.log("woah partner, I need either data or id + server");
    }
  }

  load();
  _res.load = load;

  return _res;
}
template.assign = function(el, value) {
  let matchList = document.querySelector('.tpl-' + el);
  if(matchList) {
    matchList.innerHTML = value;
  }
}
template.proxy = function(url) {
  return '/api/proxy?url=' + encodeURIComponent(url);
}
