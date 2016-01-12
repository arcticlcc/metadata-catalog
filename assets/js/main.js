/**
 * MetaCat namespace.
 */
if (typeof MetaCat === "undefined") {
  var MetaCat = {
    /**
     * Initializes this object.
     * @returns {Object}
     */
    init: function() {
      this.extents = (this.extents || []);

      //jQuery listeners
      $(function() {
        $('.mc-list-header select').change(function() {
          var _this = $(this);
          var val = _this.val();
          var params = MetaCat.getParams();
          var col = _this.data('param');

          params[col] = val;
          window.location.search = '?' + $.param(params);
        });
      });

      return this;
    },
    /**
     * Gets the querystring as an object
     * @returns {Object}
     */
    getParams: function() {
      var match;
      var pl = /\+/g; // Regex for replacing addition symbol with a space
      var search = /([^&=]+)=?([^&]*)/g;
      var decode = function(s) {
        return decodeURIComponent(s.replace(pl, " "));
      };
      var query = window.location.search.substring(1);
      var urlParams = {};

      match = search.exec(query);

      while (match) {
        urlParams[decode(match[1])] = decode(match[2]);
        match = search.exec(query);
      }

      return urlParams;
    }
  };

  MetaCat.init();
}
