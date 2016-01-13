$(function() {
  if (typeof L === "object" && MetaCat.extents && MetaCat.extents.length > 0) {
    (function() {
      var extents = MetaCat.extents;
      var mqAttr = '<span>Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png"></span>';
      var osmAttr = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>';

      L.TileLayer.OSM = L.TileLayer.extend({
        initialize: function(options) {
          L.TileLayer.prototype.initialize.call(this, 'http://otile{s}.mqcdn.com/tiles/1.0.0/{type}/{z}/{x}/{y}.png', {
            subdomains: '1234',
            type: 'osm',
            attribution: 'Map data ' + osmAttr + ', ' + mqAttr
          });
        }
      });

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
          var name = props.featureName;
          var desc = props.description;

          if (name || desc) {
            var cnt = name ? name : '';

            cnt += name && desc ? ': ' : '';
            cnt += desc;
            layer.bindPopup(cnt);
          }
        }
      };

      var bboxToPoly = function(json, box) {
        var bbox = box ? box : json.bbox;
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


      var bboxLayers = [];
      var bboxCalc = [];

      extents.forEach(function(extent, idx) {
        var map = L.map('geo-' + idx);
        var geoArray = extent.geographicElement;
        var geojson = [];
        var geoLayer;


        geoArray.forEach(function(json, geoIdx, geoArr) {
          var bbox = json.bbox;

          //bbox doesn't cross the dateline
          if (!json.geometry && bbox && bbox[0] < 0 && bbox[2] < 0) {
            bboxToPoly(json);
          }

          if (json.geometry || json.features) {
            geojson.push(json);
            return;
          }
        });
        geoLayer = L.geoJson(geojson, {
          style: function(feature) {
            return feature.properties.style || {};
          },
          coordsToLatLng: function(coords) {
            var longitude = coords[0];
            var latitude = coords[1];
            var latlng = L.latLng(latitude, longitude);

            if (longitude < 0) {
              return latlng.wrap(360, 0);
            } else
              return latlng;
          },
          onEachFeature: onEachFeature
        });

        geoLayer.addTo(map);

        if (geojson.length) {
          var bnds = geoLayer.getBounds();
          var n = bnds.getNorth();
          var s = bnds.getSouth();
          var e = bnds.getEast();
          var w = bnds.getWest();

          //calculate bounds
          bboxCalc[0] = bboxCalc[0] ? Math.min(bboxCalc[0], w, e) : Math.min(w, e);
          bboxCalc[1] = bboxCalc[1] ? Math.min(bboxCalc[1], n, s) : Math.min(n, s);
          bboxCalc[2] = bboxCalc[2] ? Math.max(bboxCalc[2], w, e) : Math.max(w, e);
          bboxCalc[3] = bboxCalc[3] ? Math.max(bboxCalc[3], n, s) : Math.max(n, s);

          //geoLayer.addTo(map);
          map.fitBounds(bnds);
        } else {
          //bbox map crosses dateline
          geoLayer.options._map = map;
          bboxLayers.push(geoLayer);
        }

        $('#collapse-map a[data-toggle="pill"]').on('show.bs.tab', $.proxy(function() {
          var me = this;
          var i = 0;

          setTimeout(function() {
            check(i, me, geoLayer);
          }, 100);

        }, map));

        map.addLayer(new L.TileLayer.OSM());
      });
      //create 360 degree bbox
      //will combine multiple bboxes into single polygon
      //covers all features
      if (bboxLayers.length) {
        var jsonData = {
          "id": "boundingExtent",
          "crs": {
            "type": "name",
            "properties": {
              "name": "urn:ogc:def:crs:OGC:1.3:CRS84"
            }
          },
          "bbox": bboxCalc,
          "type": "Feature",
          "geometry": null,
          "properties": {
            "description": "bounding box"
          }
        };

        bboxLayers.forEach(function(layer) {
          var map = layer.options._map;

          bboxToPoly(jsonData);
          layer.addData(jsonData);
          layer.addTo(map);
          map.fitBounds(layer.getBounds());
        });
      }
    })();
  }
});
