import Map from 'ol/Map.js';
import View from 'ol/View.js';
import {GeoJSON} from 'ol/format';
import {defaults as defaultInteractions, Select, Translate, Draw, Modify, Snap} from 'ol/interaction.js';
import {Tile as TileLayer, Vector as VectorLayer} from 'ol/layer.js';
import {OSM, Cluster, Vector as VectorSource} from 'ol/source.js';
import {Circle as CircleStyle, Icon, Fill, Stroke, Style, Text} from 'ol/style.js';
import {fromLonLat, toLonLat} from 'ol/proj';
import Feature from 'ol/Feature';
import Point from 'ol/geom/Point';
import Polygon from 'ol/geom/Polygon';
import Circle from 'ol/geom/Circle';
import MultiLineString from 'ol/geom/MultiLineString';

window.map = function(opts) {
  //
  // opts:
  //  target: the dom id to draw the map to.
  //  center: the center of the map in lon/lat
  //
  // func:
  //  clear() - remove all the shapes
  //  

  opts = Object.assign({}, {
    // should objects be selectable?
    select: false,
    // should the first object added be automatically selected?
    selectFirst: false,
    // can we draw on the map?
    draw: false,
    // where we select the shapes from
    typeSelect: 'type',
    // can we resize the objects on the map?
    resize: false,
    // can we move objects on the map?
    move: false,
    // the dom id to attach the map to
    target: 'map',
    // the default center point
    center: [-118.3, 34.02],
    // the default zoom level
    zoom: 13,
  }, opts || {});

  var _draw, 
      _cb = { select: [] },
      _snap, 
      _featureMap = {},
      _id = 0,
      _select,
      _isFrst = true,
      _map,
      _map_params,
	    _styleCache = {};
  var source = {};
  var dom = document.getElementById(opts.target);

  ['car','screen','bluedot','carpink'].forEach(row => {
    _styleCache[row] = new Style({
      image: new Icon({
        src: `${row}.png`
      })
    });
  });

  var _layers = [ new TileLayer({ source: new OSM() }) ];
  var recurseFll = x => x[0].length ? x.map(y => y[0].length ? recurseFll(y) : fromLonLat(y) ) : fromLonLat(x);

  var css = document.createElement('style');
  css.innerHTML = `
  .ol-overlaycontainer-stopevent { display: none }
  `;
  dom.appendChild(css);

  // points {
  /*
  if(opts.points) {
    var featureMap = opts.points.filter(row => row.lng).map(row => {
      return {
        type: "Feature",
        properties: {
          icon: row.is_fake ? 'screen' : 'car'
        },
        geometry: {
          type: "Point",
          coordinates: fromLonLat([row.lng, row.lat])
        }
      };
    });
    featureMap = {type: "FeatureCollection", features: featureMap};

    source.screen = new VectorSource({
      format: new GeoJSON(),
      loader: function() {
        source.screen.addFeatures(
          source.screen.getFormat().readFeatures(JSON.stringify(featureMap))
        );
      }
    });

    // clustering {
    var clusterSource = new Cluster({
      distance: 55,
      source: source.screen
    });

    var clusters = new VectorLayer({
      source: clusterSource,
      style: function(obj) {
        var features = obj.get('features');
   			var size = features.length;
        if(size > 1) {
          var style = _styleCache[size];
          if (!style) {
            style = new Style({
              image: new CircleStyle({
                radius: 14,
                fill: new Fill({
                  color: '#000'
                })
              }),
              text: new Text({
                text: size.toString(),
                fill: new Fill({
                  color: '#fff'
                })
              })
            });
            _styleCache[size] = style;
          }
          return style;
        } else {
          return _styleCache[features[0].getProperties().icon];
        }
      }
		});
	
    _layers.push(clusters);
    // } clustering

    //_layers.push(points);
  }
  // } points
  */

  // drawlayer {
  function save() {
    let shapes = draw.getSource().getFeatures().map(row => {
      var kind = row.getGeometry();
      if (kind instanceof Polygon) {
        return ['Polygon', kind.getCoordinates()[0].map(coor => toLonLat(coor))];
      } else {
        return ['Circle', toLonLat(kind.getCenter()), kind.getRadius()];
      }
    });

    return shapes;
  }

  function load(list) {
    clear();
    return add(list);
  }

  function add(list) {
    return list.map(addOne);
  }

  function addOne(shape) {
    var feature, myid = false;
    // line is an array of points, [ [lat,lng], [lat,lng] ... ]
    // as the second argument.
    if(shape[0] === 'Point') {
      feature = new Feature({ 
        geometry: new Point(fromLonLat(shape[1])),
      });
      myid = shape[2];
      //feature.setStyle(_styleCache.car);
    } else if(shape[0] === 'Location') {
      feature = new Feature({ geometry: new Point(fromLonLat(shape[1])) });
      myid = shape[2];
      feature.setStyle(_styleCache.bluedot);
    } else if(shape[0] === 'Line') {
      feature = new Feature({
        geometry: new MultiLineString(recurseFll(shape.slice(1)))
      });
      feature.setStyle(
        new Style({
          stroke: new Stroke({
            color: '#7777ffff',
            width: 6
          })
        })
      );
    } else if(shape[0] === 'Circle') {
      feature = new Feature({
        geometry: new Circle(fromLonLat(shape[1]), shape[2]),
      });
      feature.setStyle(
        new Style({
          fill: new Fill({
            color: '#9999eebb', //getGradient()
          }),
          stroke: new Stroke({
            lineCap: 'butt',
            lineJoin: 'bevel',
            color: '#7777ff99',
            width: 2
          })
        })
      );
    } else if(shape[0] === 'Polygon') {
      feature = new Feature({
        geometry: new Polygon([shape[1].map(coor => fromLonLat(coor))]),
      });
    } else {
      console.error("What the fuck is a " + shape[0] + "?");
    }
    draw.getSource().addFeature(feature);

    if(opts.selectFirst && _isFirst) {
      _select.getFeatures().push(feature);
      _select.on('select', function(evt) {
        if(evt.selected.length == 0) {
          _select.getFeatures().push(feature);
        }
      });
      _isFirst = false;
    }
    // the most common thing we'll want to do is 
    // move the object. BUT WE CAN'T PASS LAT/LNG
    // The batshit crazy syntax is something like
    // mypoints[0].getGeometry().setCoordinates(_map.ll([-118.35,34.024]))
    // which can honestly go to hell. so we just have one that requires
    // more um ... accounting?
    //
    // V this is the features, followed by the actual shape
    //   definition that went in ... (this can be used for searching
    //   and debugging)
    //
    myid = myid || _id++;
    feature.setId(myid);
    _featureMap[myid] = [feature, shape];
    return { shape: feature, index: myid };
  }


  // this is the function with perhaps more accounting
  function move(index, lat, lng) {
    console.log("moving lat/lng", index, lat, lng);
    _featureMap[index][0].getGeometry().setCoordinates(recurseFll([lng, lat]));
  }
  function remove(index) {
    draw.getSource().removeFeature(_featureMap[index][0]);
    delete _featureMap[index];
  }

  function clear() {
    for(var feature of draw.getSource().getFeatures()) {
      draw.getSource().removeFeature(feature);
    }
    _featureMap = {};
  }

  function removeShape() {
    let shapeList = draw.getSource().getFeatures();
    if(shapeList) {
      draw.getSource().removeFeature(shapeList.slice(-1)[0]);
    }
  }

  function removePoint() {
    _draw.removeLastPoint();
  }

  source.draw = new VectorSource();
  var draw = new VectorLayer({
    source: source.draw,
    style: _styleCache.car
  });

  if(opts.draw) {
    var typeSelect = document.getElementById(opts.typeSelect);
    dom.onkeyup = function(e) {
      if(e.key === 'Delete') { removePoint(); }
      if(e.key === 'Backspace') { removeShape(); }
    }

    typeSelect.onchange = function() {
      _map.removeInteraction(_draw);
      _map.removeInteraction(_snap);
      addInteractions();
    };

  }
  // } drawlayer

  _layers.push(draw);

  // eventually use geoip
  _map_params =  {
    layers: _layers,
    target: opts.target,
    view: new View({
      center: fromLonLat(opts.center),
      zoom: opts.zoom
    })
  };

  if(opts.move) {
    _select = new Select();

    _map_params.interactions =  defaultInteractions().extend([
      _select, 
      new Translate({ features: _select.getFeatures() })
    ]);
  } else if (opts.select) {
    _select = new Select({
      style: _styleCache.carpink
    });
    _map_params.interactions =  defaultInteractions().extend([
      _select
    ]);
  }


  if(_select) {
   _select.on('select', function(evt) {
     _cb.select.forEach(row => row(evt));
   });
  }

  _map = new Map(_map_params);

  if(opts.draw) {
    _draw = new Draw({
      source: source.draw,
      type: typeSelect.value
    });
    _map.addInteraction(_draw);
    _snap = new Snap({source: source.draw});
    _map.addInteraction(_snap);
    if(opts.resize) {
      _map.addInteraction(new Modify({source: source.draw}));
    }
  }

  return {
    center: function(coor, zoom) {
      _map.getView().setCenter(fromLonLat(coor));
      if(zoom) {
        _map.getView().setZoom(zoom);
      }
    },
    fit: () => _map.getView().fit(_layers[1].getSource().getExtent()),
    ll: function(a) {
      return a.length ? recurseFll(a) : recurseFll(Array.from(arguments))
    },
    on: function(what, fn) {
      _cb[what].push(fn);
      return _cb;
    },
    _map,
    _layers,
    _featureMap,
    clear,
    removePoint,
    removeShape,
    remove,
    move,
    save,
    add,
    addOne,
    load
  };
}
