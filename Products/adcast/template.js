var id = 8;
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

window.onload = function() {
  fetch(`http://staging.waivescreen.com/api/provides?id=${id}`)
    .then(response => response.json())
    .then(res => {
      let list = Object.keys(res).filter(x => !exclude_list.has(x));
      for (let key of list) {
        if(Array.isArray(res[key])) {
          for(let node of document.querySelectorAll(`.tpl-${key}`)) {
            var which = parseInt(node.dataset.index,10);
            if(res[key][which]) {
              assign(node, res[key][which].url);
            }
          }
        } else {
          for(let node of document.querySelectorAll(`.tpl-${key}`)) {
            assign(node, res[key]);
          }
        } 
      }
    });
}
