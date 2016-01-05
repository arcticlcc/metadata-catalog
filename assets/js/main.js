/**
 * MetaCat namespace.
 */
if ( typeof MetaCat === "undefined") {
  var MetaCat = {
    /**
     * Initializes this object.
     */
    init : function() {
      this.extents = (this.extents || []);

      return this;
    }
  };

  MetaCat.init();
}