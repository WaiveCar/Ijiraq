var tableIt = (function() {
  var _map = {}, wordMap = {org: 'organization'};

  function id2name(what){
    if(_map[what]) {
      return;
    }
    _map[what] = {}

    $.getJSON(`/api/${what}s`, function(res) {
      res.forEach(row => _map[row.id] = row);
      $(`td.${what}_id`).each(function(ix, el) {
        var myid = el.innerHTML;
        el.innerHTML = `<a href='/${what}/show/?id=${myid}'>${_map[myid].name}</a>`;
      });
    });
  }

  return function (table, opts) {
    opts = Object.assign({
      filter: (opts && opts.filter || []).concat(['id','password','created_at','image'])
    }, opts || {});

    $.getJSON(`/api/${table}`, function(res) {
      console.log(res);
      
      if(res.length === 0) {
        let singular = table.slice(0,-1);
        $("#dataTable").parent().html(`<h2>Welcome to your ${table} dashboard!</h2><h5> Adding your first ${singular} is easy. Just click the button in the upper right labeled "New ${singular}" to get started.</h5>`);
        return;
      }
      let fields = Object.keys(res[0]).filter(row => !opts.filter.includes(row)) 
      $("#table-head").html(
        "<tr>" +
        fields.map(field => `<th class="${field}" scope="col">${field}</th>`) +
        "</tr>"
      );
      $("#table-body").html(
        res.map(function(row) {
          return '<tr>' +
            fields.map(function(field) {
              return '<td class=' + field + '>' + (
                field === 'name' ?
                  `<a href="/${table}/show/?id=${row.id}">${row[field]}</a>` :
                  row[field]
               ) + '</td>'
            }) + '</tr>'
        })
      )
      fields.forEach(function(row) {
        var xref = row.match(/(\w*)_id/);

        if (xref) {
          let field = xref[1];
          id2name(field);
          $(`th.${row}`).html(field);
        }
      });

    });
  }
})();