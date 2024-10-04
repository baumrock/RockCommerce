/**
 * RockCommerce Frontend Javascript
 */
var RockCommerce = {
  cart: false,
  cid: 0,
  components: {},
  config: {},
  hooksAfter: {},
  hooksBefore: {},
  pidcid: {},
  products: [],
  taxrate: 0,
  timers: {},

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
  addComponent(c) {
    const cid = ++this.cid;
    this.components[cid] = c;
    c.$el.setAttribute("rc-cid", cid);
    if (c.$rctype === "rcproduct") this.products.push(c);
    const pid = c.$rcattr("rc-pid");
    if (pid) this.pidcid[pid] = cid;
    this.trigger("addComponent", c);
  },

  /**
   * Add AFTER hook, eg RockCommerce.after('event', callback);
   */
  after(event, handler) {
    if (!this.hooksAfter[event]) {
      this.hooksAfter[event] = [];
    }
    this.hooksAfter[event].push(handler);
  },

  /**
   * Add BEFORE hook, eg RockCommerce.before('event', callback);
   */
  before(event, handler) {
    if (!this.hooksBefore[event]) {
      this.hooksBefore[event] = [];
    }
    this.hooksBefore[event].push(handler);
  },

  /**
   * Register Alpine Component
   */
  component(name, config) {
    document.addEventListener("alpine:init", () => {
      // https://github.com/alpinejs/alpine/discussions/1940#discussioncomment-6586765
      Alpine.data(name, () =>
        Object.create(
          Object.getPrototypeOf(config),
          Object.getOwnPropertyDescriptors(config)
        )
      );
    });
  },

  /**
   * Debounce callback for specified time
   * See rcproduct.js update() for how to use
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
      this.loading(options.animation || "rc-dots");
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
   * Find closest with type: RockCommerce.getComponent(myEl, 'rcproduct');
   */
  getComponent(el, type) {
    if (type) el = el.closest(`[x-data=${type}]`);
    if (!el) return false;
    const cid = el.closest("[x-data]").getAttribute("rc-cid");
    if (!cid) console.error("Component is not initialized");
    return this.components[cid];
  },

  /**
   * Find alpine component from pw page id (rc-pid attribute)
   */
  getComponentFromPid(pid) {
    const cid = this.pidcid[pid];
    if (cid) return this.components[cid];
  },

  /**
   * Show loading indicator
   */
  loading(name) {
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

  /**
   * Trigger hook
   */
  trigger(event, data, callback) {
    let hookEvent = {
      return: data,
      replace: false,
    };

    // call before hook
    // hookEvent is passed as reference so all hooks can modify it directly
    if (this.hooksBefore[event]) {
      this.hooksBefore[event].forEach((hook) => hook(hookEvent));
    }

    // if any of the before hooks set "event.replace = true"
    // we don't execute the callback and all after hooks
    if (hookEvent.replace) return;

    // execute callback
    // the return value of the callback is written to event.return
    if (typeof callback === "function") {
      hookEvent.return = callback(hookEvent.return);
    }

    // call after hook
    // hookEvent is passed as reference so all hooks can modify it directly
    if (this.hooksAfter[event]) {
      this.hooksAfter[event].forEach((hook) => hook(hookEvent));
    }

    // finally return the return value
    return hookEvent.return;
  },
};

/**
 * Add global magic to alpine components
 * Magic properties are accessible via component.$foo
 * Magic functions are accessible via component.$foo()
 * See https://alpinejs.dev/advanced/extending#custom-magics
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
   * Component type (eg rccart, rcproduct)
   */
  Alpine.magic("rctype", (el) => {
    const c = RockCommerce.getComponent(el);
    return c.$el.getAttribute("x-data");
  });
});

// let everyone know that RockCommerce is ready
const init = new CustomEvent("RockCommerce:init", { detail: RockCommerce });
document.dispatchEvent(init);
