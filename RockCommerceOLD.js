/**
 * RockCommerce Frontend Javascript
 */
console.log("RockCommerceOLD");
var RockCommerceOLD = ProcessWire.wire(
  {
    cart: false,
    cid: 0,
    components: {},
    config: {},
    pidcid: {},
    products: [],

    // use getTaxrate() and setTaxrate()!
    _taxrate: 0,

    /**
     * Add alpine component to list of components
     *
     * NOTE: Alpine components inherit methods and properties from parent
     * components! That means if you have nested components and a parent comp
     * implements foo() than this method will be available to all children
     * unless it is specified there as well.
     *
     * This also means that you must not set global component properties here,
     * for example c.foo = 'foo' will mess up and set properties of parent comps.
     */
    ___addComponent(c) {
      const cid = ++this.cid;
      this.components[cid] = c;
      c.$el.setAttribute("rc-cid", cid);
      if (c.$rctype === "RcProduct") this.products.push(c);
      const pid = c.$rcattr("rc-pid");
      if (pid) this.pidcid[pid] = cid;
    },

    /**
     * Register Alpine Component
     */
    component(name, comp) {
      document.addEventListener("alpine:init", () =>
        Alpine.data(name, () => ProcessWire.wire(comp, name, true))
      );
    },

    fetch(url, options = {}) {
      // make sure url has no leading slash
      url = url.replace(/^\/+/, "");
      url = RockCommerceOLD.config.root + "rockcommerce/" + url;

      // Set default headers
      const defaultHeaders = {
        "X-Requested-With": "XMLHttpRequest",
        "Content-Type": "application/json",
        Accept: "application/json",
      };

      // Merge default options with provided options
      const mergedOptions = {
        ...options,
        headers: {
          ...defaultHeaders,
          ...(options && options.headers),
        },
      };

      // Show loading indicator
      if (options.animation !== false) {
        this.loading(options.animation || "dots");
      }

      return fetch(url, mergedOptions)
        .then((response) => {
          // Hide loading indicator when the request is complete
          this.loaded();
          return response;
        })
        .catch((error) => {
          // Hide loading indicator if there's an error
          this.loaded();
          // Re-throw the error to be caught by the caller
          throw error;
        });
    },

    /**
     * Find alpine component from pw page id (rc-pid attribute)
     */
    getComponentFromPid(pid) {
      const cid = this.pidcid[pid];
      if (cid) return this.components[cid];
    },

    /**
     * Get taxrate
     * This ensures that the returned taxrate is numeric even if
     * this.taxrate is undefined for whatever reason
     */
    getTaxrate() {
      return parseFloat(this._taxrate) || 0;
    },
  },
  "RockCommerce"
);
