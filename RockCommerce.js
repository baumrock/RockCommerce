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

  /**
   * Add alpine component to list of components
   * NOTE: Alpine components inherit methods and properties from parent
   * components! That means if you have nested components and a parent comp
   * implements foo() than this method will be available to all children
   * unless it is specified there as well.
   */
  addComponent(c) {
    const cid = ++this.cid;
    this.components[cid] = c;
    c.$el.setAttribute("rc-cid", cid);
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
   * Find alpine component from DOM element
   * Find closest component: RockCommerce.getComponent(myEl);
   * Find closest with type: RockCommerce.getComponent(myEl, 'rcproduct');
   */
  getComponent(el, type) {
    if (type) el = el.closest(`[x-data=${type}]`);
    if (!el) return false;
    const cid = el.closest("[rc-cid]").getAttribute("rc-cid");
    return this.components[cid];
  },

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
   * Get attribute of alpine component element
   * Usage c.$attr('foo')
   */
  Alpine.magic("rcattr", (el) => {
    const c = RockCommerce.getComponent(el);
    return (attr) => c.$el.getAttribute(attr);
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
document.dispatchEvent(
  new CustomEvent("RockCommerce:init", {
    detail: RockCommerce,
  })
);
