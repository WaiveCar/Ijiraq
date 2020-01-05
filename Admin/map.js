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
    target: 'map',
    center: [-118.3, 34.02],
    select: false,
    selectFirst: false,
    zoom: 13,
    typeSelect: 'type',
    draw: false,
    resize: false,
    move: false,
  }, opts || {});

  var _draw, 
      _cb = { select: [] },
      _snap, 
      _featureList = [], 
      _id = 0,
      _select;
  var source = {};
  var dom = document.getElementById(opts.target);
	var styleCache = {};
  var isFirst = true;

  ['car','screen','bluedot'].forEach(row => {
    styleCache[row] = new Style({
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
          var style = styleCache[size];
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
            styleCache[size] = style;
          }
          return style;
        } else {
          return styleCache[features[0].getProperties().icon];
        }
      }
		});
	
    _layers.push(clusters);
    // } clustering

    //_layers.push(points);
  }
  // } points

  // drawlayer {
  function getShapes() {
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
    return list.map(shape => {
      var feature;
      // line is an array of points, [ [lat,lng], [lat,lng] ... ]
      // as the second argument.
      if(shape[0] === 'Point') {
        feature = new Feature({ geometry: new Point(fromLonLat(shape[1])) });
        feature.setStyle(styleCache.car);
      } else if(shape[0] === 'Location') {
        feature = new Feature({ geometry: new Point(fromLonLat(shape[1])) });
        feature.setStyle(styleCache.bluedot);
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

      if(opts.selectFirst && isFirst) {
        _select.getFeatures().push(feature);
        _select.on('select', function(evt) {
          if(evt.selected.length == 0) {
            _select.getFeatures().push(feature);
          }
        });
        isFirst = false;
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
      feature.setId(_id++);
      _featureList.push([feature, shape]);
      return feature;
      
    });
  }

  // this is the function with perhaps more accounting
  function move(index, lat, lng) {
    console.debug("Moving ", _featureList[index][1], "to", lat, lng);
    _featureList[index][0].getGeometry().setCoordinates(recurseFll([lat, lng]));
  }

  function clear() {
    for(var feature of draw.getSource().getFeatures()) {
      draw.getSource().removeFeature(feature);
    }
    _featureList = [];
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
    style: new Style({
      stroke: new Stroke({
        color: '#000000',
        width: 10
      }),
      image: new CircleStyle({
        radius: 2,
        fill: new Fill({
          color: '#ffcc33'
        })
      })
    })
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
  var map_params =  {
    layers: _layers,
    target: opts.target,
    view: new View({
      center: fromLonLat(opts.center),
      zoom: opts.zoom
    })
  };

  if(opts.move) {
    _select = new Select();

    map_params.interactions =  defaultInteractions().extend([
      _select, 
      new Translate({ features: _select.getFeatures() })
    ]);
  } else if (opts.select) {
    _select = new Select({
      style: styleCache.screen
    });
    map_params.interactions =  defaultInteractions().extend([
      _select
    ]);
  }


  if(_select) {
   _select.on('select', function(evt) {
     _cb.select.forEach(row => row(evt));
   });
  }

  var _map = new Map(map_params);

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
    _map,
    _layers,
    clear,
    removePoint,
    removeShape,
    move,
    fit: () => _map.getView().fit(_layers[1].getSource().getExtent()),
    ll: function(a) {
      return a.length ? recurseFll(a) : recurseFll(Array.from(arguments))
    },
    on: function(what, fn) {
      _cb[what].push(fn);
      return _cb;
    },
    save: getShapes,
    add,
    load
  };
}
