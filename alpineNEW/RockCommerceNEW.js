console.log("RockCommerceNEW");

var RockCommerce;
(() => {
  const components = [];

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
    addComponent(name, comp) {
      document.addEventListener("alpine:init", () =>
        Alpine.data(name, () => {
          // if component has an init() method throw an error
          if (typeof comp.init === "function") {
            throw new Error(
              "RockCommerce Alpine components must not have an init() method"
            );
          }

          // add a custom hookable init method for every added component
          // this custom init() method is called by alpine whenever it finds
          // a <div x-data=...> dom element. the init
          comp.___init = function () {
            // add component to the list of added components
            components.push(this);

            // if the RockCommerce alpine component was initialized
            // we replace the global RockCommerce object with this component
            if (name === "RockCommerceNEW") {
              setTimeout(() => {
                RockCommerce = this;
                RockCommerce.readyComponents();
              }, 0);
            }
          };

          // make the alpine component hookable
          const wired = ProcessWire.wire(comp, name, true);

          return wired;
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
  RockCommerce.addComponent("RockCommerceNEW", {
    // direct reference to the cart component
    // this is possible because we always only have one cart
    cart: false,

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

    /**
     * Find alpine component from DOM element
     * Find closest component: RockCommerce.getComponent(myEl);
     * Find closest with type: RockCommerce.getComponent(myEl, 'RcProduct');
     */
    getComponent(el, type) {
      if (type) el = el.closest(`[x-data=${type}]`);
      else el = el.closest("[x-data]");
      if (!el) return false;
      components.forEach((c) => {
        if (c.$el === el) return c;
      });
      return false;
    },

    getComponents(type) {
      return components.filter((c) => c.$el.getAttribute("x-data") === type);
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

    /**
     * Return a raw copy of data
     * This is useful to convert Alpine Proxy objects to a raw JS objects
     */
    raw(data) {
      return JSON.parse(JSON.stringify(data || this));
    },

    ___readyComponents() {
      components.forEach((c) => {
        // call .ready() method of every component if it exists
        if (typeof c.ready === "function") c.ready();
      });
    },

    /**
     * Set the global taxrate
     */
    setTaxrate(taxrate) {
      this._taxrate = taxrate;
      localStorage.setItem("rc-taxrate", taxrate);
    },
  });

  /**
   * Add global apine magic
   */
  document.addEventListener("alpine:init", () => {
    /**
     * Get page id of alpine component element
     * <div x-data=mycomponent rc-pid=123></div>
     * Usage c.$rcpid()
     */
    Alpine.magic("rcpid", (el) => {
      const c = RockCommerce.getComponent(el);
      return c.$rcattr("rc-pid");
    });

    /**
     * Get attribute of alpine component element
     * <div x-data=mycomponent foo=foo></div>
     * Usage c.$rcattr('foo')
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
