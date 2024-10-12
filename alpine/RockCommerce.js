// add the global rockcommerce object
var RockCommerce;

// define custom scope for this component
(() => {
  let componentID = 0;
  const components = {};

  /**
   * Setup the global RockCommerce object
   * This object will be loaded before any other Alpine components
   * and will be replaced by the RockCommerce Alpine component some
   * lines below in this file once it is loaded.
   */
  RockCommerce = {
    /**
     * Helper function that is called by all other alpine components
     * and creates a wired alpine component that supports Hooks!
     */
    addComponent(name, component) {
      document.addEventListener("alpine:init", () =>
        Alpine.data(name, () => {
          // if component has an init() method throw an error
          if (typeof component.init === "function") {
            throw new Error(
              "RockCommerce Alpine components must not have an init() method"
            );
          }

          // add a custom hookable init method for every added component
          // this custom init() method is called by alpine whenever it finds
          // a <div x-data=...> dom element. the init
          component.___init = function () {
            // add component to the list of added components
            components[++componentID] = this;
            this.$el.setAttribute("rc-cid", componentID);

            // if the RockCommerce alpine component was initialized
            // we replace the global RockCommerce object with this component
            if (name === "RockCommerce") {
              // wait for the next tick to prevent an alpine error
              setTimeout(() => {
                // replace rockcommerce with this alpine component
                RockCommerce = this;

                // read config from data-config attribute
                RockCommerce.config = JSON.parse(
                  this.$rcattr("data-config") || "{}"
                );

                // trigger .ready() method of every component if it exists
                RockCommerce.makeComponentsReady();
              }, 0);
            }
          };

          // return hookable alpine component
          return ProcessWire.wire(component, name, true);
        })
      );
    },
  };

  /**
   * RockCommerce Alpine Component
   *
   * Will be loaded when <div x-data="RockCommerce"> is found in the DOM
   * and then replace the global RockCommerce object.
   */
  RockCommerce.addComponent("RockCommerce", {
    // direct reference to the cart component
    // this is possible because we always only have one cart
    cart: false,

    config: {},
    pidcid: {},
    _taxrate: 0,
    timers: {},

    /**
     * Debounce callback for specified time
     * Usage:
     * RockCommerce.debounce('my-timer', () => {
     *   console.log('debounced');
     * }, 1000);
     */
    debounce(name, func, wait) {
      if (typeof wait === "undefined") wait = 10;
      clearTimeout(this.timers[name]);
      this.timers[name] = setTimeout(func, wait);
    },

    fetch(url, options = {}) {
      // make sure url has no leading slash
      url = url.replace(/^\/+/, "");
      url = RockCommerce.config.root + "rockcommerce/" + url;

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
     * Find alpine component from DOM element
     * Find closest component: RockCommerce.getComponent(myEl);
     * Find closest with type: RockCommerce.getComponent(myEl, 'RcProduct');
     */
    getComponent(el, type) {
      if (type) el = el.closest(`[x-data=${type}]`);
      else el = el.closest("[x-data]");
      if (!el) return false;
      const cid = el.getAttribute("rc-cid");
      if (!cid) return false;
      return components[cid];
    },

    /**
     * Find alpine component from pw page id (rc-pid attribute)
     */
    getComponentByPid(pid) {
      let comp = false;
      this.getComponents().forEach((c) => {
        if (c.$rcpid === pid) comp = c;
      });
      return comp;
    },

    getComponents(type) {
      if (!type) {
        return Object.values(components);
      }
      return Object.values(components).filter(
        (c) => c.$el.getAttribute("x-data") === type
      );
    },

    /**
     * Get taxrate
     * This ensures that the returned taxrate is numeric even if
     * this.taxrate is undefined for whatever reason
     */
    getTaxrate() {
      return parseFloat(this._taxrate) || 0;
    },

    /**
     * Show loading indicator
     *
     * Usage:
     * RockCommerce.loading(); // uses "dots" animation
     * RockCommerce.loading('my-animation');
     */
    loading(name = "dots") {
      document.body.setAttribute("rockloader", name);
    },

    loaded() {
      document.body.removeAttribute("rockloader");
    },

    ___makeComponentsReady() {
      Object.values(components).forEach((c) => {
        this.makeReady(c);
      });
    },

    ___makeReady(component) {
      if (typeof component.ready === "function") component.ready();
    },

    /**
     * Return a raw copy of data
     * This is useful to convert Alpine Proxy objects to a raw JS objects
     */
    raw(data) {
      return JSON.parse(JSON.stringify(data || this));
    },

    /**
     * Set the global taxrate
     */
    setTaxrate(taxrate) {
      this._taxrate = taxrate;
      localStorage.setItem("rc-taxrate", taxrate);
    },

    trigger(event, data) {
      console.error("refactor me");
    },
  });

  /**
   * Add global apine magic
   */
  document.addEventListener("alpine:init", () => {
    /**
     * Get page id of alpine component element
     * <div x-data=mycomponent rc-pid=123></div>
     * Usage c.$rcpid
     * Returns 123
     */
    Alpine.magic("rcpid", (el) => {
      const c = RockCommerce.getComponent(el);
      return c.$rcattr("rc-pid");
    });

    /**
     * Get attribute of alpine component element
     * <div x-data=mycomponent foo=bar></div>
     * Usage c.$rcattr('foo')
     * Returns "bar"
     */
    Alpine.magic("rcattr", (el) => {
      const c = RockCommerce.getComponent(el);
      return (attr) => c.$root.getAttribute(attr);
    });

    /**
     * Component type (eg RcCart, RcProduct)
     */
    Alpine.magic("rctype", (el) => {
      const c = RockCommerce.getComponent(el);
      return c.$el.getAttribute("x-data");
    });
  });
})();
