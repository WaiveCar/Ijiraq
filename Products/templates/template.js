function template(opts) {
  var
    _res = Object.assign({
      server: "http://staging.waivescreen.com/api/provides",

      db: {},

      duration: 7.5,
      id: false,

      target: { width: 1920, height: 675 },

    }, opts || {});

  var exclude_list = new Set(['created_at','id']);

  function assign(node, value) {
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
    let list = Object.keys(data).filter(x => !exclude_list.has(x));
    for (let key of list) {
      if(Array.isArray(data[key])) {
        for(let node of document.querySelectorAll(`.tpl-${key}`)) {
          var which = parseInt(node.dataset.index, 10);
          if(data[key][which]) {
            assign(node, data[key][which].url);
          }
        }
      } else {
        for(let node of document.querySelectorAll(`.tpl-${key}`)) {
          assign(node, data[key]);
        }
      } 
    }
  }

  function remote() {
    fetch(`${_res.server}?id=${_res.id}`)
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
