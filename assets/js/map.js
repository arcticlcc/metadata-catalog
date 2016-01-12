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

      var check = function(i, me, bnds) {
        if (i < 3) {
          var resize = me.getSize().x === 0 && me.getContainer().offsetWidth > 0;

          me.invalidateSize();
          if (resize) {
            me.fitBounds(bnds);
          } else {
            i++;
            setTimeout(function() {
              check(i, me, bnds);
            }, 100);
          }
        }
      };

      extents.forEach(function(extent, idx, arr) {
        var map = L.map('geo-' + idx);
        var geoArray = extent.geographicElement;
        var geojson = [];

        geoArray.forEach(function(json, geoIdx, geoArr) {
          var bbox = json.bbox;
          if (json.geometry === null && bbox) {
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
          }

          geojson.push(json);
        });

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

        var geoLayer = L.geoJson(geojson, {
          style: function(feature) {
            return feature.properties.style || {};
          },
          coordsToLatLng: function(coords) {
            longitude = coords[0];
            latitude = coords[1];

            var latlng = L.latLng(latitude, longitude);

            if (longitude < 0) {
              return latlng.wrap(360, 0);
            } else
              return latlng;
          },
          onEachFeature: onEachFeature
        }).addTo(map);

        var bnds = geoLayer.getBounds();

        $('#collapse-map a[data-toggle="pill"]').on('show.bs.tab', $.proxy(function() {
          var me = this;
          var i = 0;

          setTimeout(function() {
            check(i, me, bnds);
          }, 100);

        }, map));

        map.fitBounds(bnds);
        map.addLayer(new L.TileLayer.OSM());
      });
    })();
  }
});
