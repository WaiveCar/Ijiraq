function template(opts) {
  var
    _res = Object.assign({
      server: "/api/provides",

      db: {},
      custom: {},

      duration: 7.5,
      id: false,

      target: { width: 1920, height: 675 },

    }, opts || {});

  var exclude_list = new Set(['created_at','id']);

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
        node.src = value;
      }
    } else {
      node.innerHTML = value;
    }
  }

  function parser(data) {
    _res._last_data = data;
    let list = Object.keys(data).filter(x => !exclude_list.has(x));
    for (let key of list) {
      let matchList = document.querySelectorAll(`.tpl-${key}`);

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
  }

  function remote() {
    fetch(`${_res.server}?user_id=${_res.id}`)
      .then(response => response.json())
      .then(parser)
  }
  
  function load() {
    if(_res.data){
      parser(_res.data);
    } else if(_res.id && _res.server) {
      remote();
    } else {
      console.log("woah partner, I need either data or id + server");
    }
  }

  load();
  _res.load = load;

  return _res;
}
