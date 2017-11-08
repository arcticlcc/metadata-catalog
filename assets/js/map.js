$(function() {
  if (typeof L === "object" && MetaCat.extents && MetaCat.extents.length > 0) {
    (function() {
      var extents = MetaCat.extents;
      //var mqAttr = '<span>Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png"></span>';
      //var osmAttr = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';

      /*L.TileLayer.OSM = L.TileLayer.extend({
        initialize: function(options) {
          L.TileLayer.prototype.initialize.call(this, 'http://otile{s}.mqcdn.com/tiles/1.0.0/{type}/{z}/{x}/{y}.png', {
            subdomains: '1234',
            type: 'osm',
            attribution: 'Map data ' + osmAttr + ', ' + mqAttr
          });
        }
      });*/



      var check = function(i, me, lyr) {
        if (i < 3) {
          var resize = me.getSize().x === 0 && me.getContainer().offsetWidth > 0;

          me.invalidateSize();
          if (resize) {
            me.fitBounds(lyr.getBounds());
          } else {
            i++;
            setTimeout(function() {
              check(i, me, lyr);
            }, 100);
          }
        }
      };

      var onEachFeature = function(feature, layer) {
        var props = feature.properties;

        if (props) {
          var name = props.name;
          var desc = props.description;

          if (name || desc) {
            var cnt = name ? name : '';

            cnt += name && desc ? ': ' : '';
            cnt += desc;
            layer.bindPopup(cnt);
          }
        }
      };

      var bboxToPoly = function(geoJson, box) {
        var bbox = box ? box : geoJson.bbox;
        var json = geoJson ? geoJson : {
          "id": "boundingExtent",
          "crs": {
            "type": "name",
            "properties": {
              "name": "urn:ogc:def:crs:OGC:1.3:CRS84"
            }
          },
          "bbox": bbox,
          "type": "Feature",
          "geometry": null,
          "properties": {
            "description": "bounding box"
          }
        };

        json.geometry = {
          "type": "Polygon",
          "coordinates": [
            [
              [bbox[2], bbox[3]],
              [bbox[0], bbox[3]],
              [bbox[0], bbox[1]],
              [bbox[2], bbox[1]],
              [bbox[2], bbox[3]]
            ]
          ]
        };
        if (!json.properties) {
          json.properties = {};
        }
        json.properties.style = {
          color: '#f00',
          fill: false
        };

        return json;
      };

      var bboxToLayer = function(bbox) {
        //create 360 degree bbox
        if (bbox) {
          var data = bboxToPoly(null, bbox);

          var layer = L.geoJson(data, {
            style: function(feature) {
              return feature.properties.style || {};
            },
            onEachFeature: onEachFeature
          });

          return layer;
        }
      };

      var coordsToLatLng = function(coords) {
        var longitude = coords[0];
        var latitude = coords[1];
        var latlng = L.latLng(latitude, longitude);

        if (longitude < 0) {
          return latlng.wrap(360, 0);
        } else
          return latlng;
      };

      var compareBounds = function(bnds, toCompare) {
        var n = bnds[3];
        var s = bnds[1];
        var e = bnds[2];
        var w = bnds[0];
        var calc = toCompare ? toCompare : [];

        //calculate bounds
        calc[0] = calc[0] ? Math.min(calc[0], w, e) : Math.min(w, e);
        calc[1] = calc[1] ? Math.min(calc[1], n, s) : Math.min(n, s);
        calc[2] = calc[2] ? Math.max(calc[2], w, e) : Math.max(w, e);
        calc[3] = calc[3] ? Math.max(calc[3], n, s) : Math.max(n, s);

        return calc;
      };


      var bboxMaps = [];
      var bboxCalcAll;

      extents.forEach(function(extent, idx) {
        var map = L.map('geo-' + idx);
        var geoArray = extent.geographicExtent[0].geographicElement;
        var geojson = [];
        var geoLayer;
        var bboxCalc;
        var overlays = {};

        //map.addLayer(new L.TileLayer.OSM());
        var stamen = new L.StamenTileLayer("terrain");
        map.addLayer(stamen);



        extent.geographicExtent.forEach(function(json, geoIdx, geoArr) {
          //var bbox = json.geometry === null && json.type === "Feature" ? json.bbox : false;
          var raw = json.boundingBox;
          var bbox = [raw.westLongitude, raw.southLatitude, raw.eastLongitude,
            raw.northLatitude
          ];

          //bbox doesn't cross the dateline
          if (bbox && ((bbox[0] <= 0 && bbox[2] <= 0) || (bbox && bbox[0] > 0 && bbox[2] > 0))) {
            //json.properties.isBox = true;
            geojson.push(bboxToPoly(null, bbox));
          }

          //add valid object to layer data
          json.geographicElement.forEach(function (json) {
            if(json.geometry || json.features || json.coordinates || json.geometries) {
              geojson.push(json);
              return;
            }
          });
        });

        //create feature layer
        geoLayer = L.geoJson(geojson, {
          style: function(feature) {
            return feature.properties.style || {};
          },
          coordsToLatLng: coordsToLatLng,
          onEachFeature: onEachFeature
        });

        geoLayer.addTo(map);

        if (geojson.length === 0) {
          //bbox map crosses dateline
          bboxMaps.push([map, geoLayer]);
        } else {
          var bnds = geoLayer.getBounds();

          if (!(geojson.length === 1 && geojson[0].properties && geojson[0].properties.isBox)) {
            //actual features, calculate bounds
            bboxCalc = compareBounds(bnds.toBBoxString().split(','));
            overlays.Features = geoLayer;
          }

          map.fitBounds(bnds);
        }
        //re-calculate bounds on tab toggle
        $('#collapse-map a[data-toggle="pill"]').on('show.bs.tab', $.proxy(function() {
          var me = this;
          var i = 0;

          setTimeout(function() {
            check(i, me, geoLayer);
          }, 100);

        }, map));

        //calculate bounds for all features in this extent
        if (bboxCalc) {
          overlays.BBOX = bboxToLayer(bboxCalc);
          L.control.layers(null, overlays).addTo(map);
        }

        //update bboxCalcAll
        bboxCalcAll = bboxCalcAll ? compareBounds(bboxCalc, bboxCalcAll) : bboxCalc;
      });

      //generate overall bbox map layers for ALL features
      if (bboxMaps.length) {
        bboxMaps.forEach(function(map) {
          map[1].addData(bboxToPoly(null, bboxCalcAll));
          map[0].addLayer(map[1]);
          map[0].fitBounds(map[1].getBounds());
          $('#collapse-map a[data-toggle="pill"][href="#' + map[0].getContainer().id + '"]')
            .text('bounding box for ALL features');
        });
      }
    })();
  }
});
