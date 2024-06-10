// RockCommerce Frontend JS

// RockCommerce Base Component
// This is immediately available so that we can attach hooks before alpine fires
// and all other components can be added to the list even before RC itself calls
// init.
var RockCommerce = {
  cid: 0,
  components: {},
  hooksAfter: {},
  hooksBefore: {},

  /**
   * Add alpine component to list of components
   */
  addComponent(c) {
    const cid = ++this.cid;
    this.components[cid] = c;
    c.$el.setAttribute("rc-cid", cid);
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

  loadConfig(component) {
    const json = component.$el.getAttribute("data-config");
    try {
      const config = JSON.parse(json);
      for (const [prop, val] of Object.entries(config)) {
        component[prop] = val;
      }
    } catch (error) {
      console.error("Invalid JSON config", component.$el);
    }
  },

  /**
   * Get raw object for better logging/debugging
   */
  raw(data) {
    return JSON.parse(JSON.stringify(data || this));
  },

  /**
   * Throttle function to limit the rate at which a function can fire.
   */
  throttle(func, delay = 50) {
    clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      func();
    }, delay);
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
    if (this.hooksBefore[event]) {
      this.hooksBefore[event].forEach((hook) => hook(hookEvent));
    }

    // if any of the before hooks set "event.replace = true"
    // we don't execute the callback and all after hooks
    if (hookEvent.replace) return;

    // execute callback
    if (typeof callback === "function") {
      hookEvent.return = callback(hookEvent.return);
    }

    // call after hook
    if (this.hooksAfter[event]) {
      this.hooksAfter[event].forEach((hook) => hook(hookEvent));
    }

    return hookEvent.return;
  },
};

// RockCommerce Alpine Magic
document.addEventListener("alpine:init", () => {
  Alpine.data("rockcommerce", () =>
    Object.assign(RockCommerce, {
      init() {
        // expose RockCommerce globally
        RockCommerce = this;
        RockCommerce.loadConfig(this);

        // finally trigger rockcommerce ready event
        RockCommerce.trigger("RockCommerceReady");
      },

      // ##### regular methods #####
    })
  );
});

// Product Component
document.addEventListener("alpine:init", () => {
  Alpine.data("rcproduct", () => ({
    addToCartUrl: "",
    amount: 0,
    min: 0,
    price: 0,
    priceFormatted: "",
    variationString: "",

    init() {
      RockCommerce.addComponent(this);
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.updateUrl = !!this.$el.getAttribute("data-rc-updateurl");
      this.setMin();
      this.setAmount();
      this.update();
      this.$watch("amount", (amount) => {
        RockCommerce.trigger("setAmount", parseInt(amount));
      });
      this.$watch("amount", this.update.bind(this));
    },

    addToCart() {
      RockCommerce.trigger("addToCart", this);
    },

    getAddToCartUrl() {
      return "/tbd/cart/" + this.variationString;
    },

    getPrice() {
      // calculate the total price
      let price = RockCommerce.trigger("ProductPriceInit", null, () => {
        return this.price;
      });

      // add prices of variations
      this.variations().forEach((variation) => {
        if (typeof variation == "undefined") return;
        variation.options(true).forEach((option) => {
          price = price.plus(option.price.float());
        });
      });

      // add amount
      price = RockCommerce.trigger("ProductPriceTotal", price, (price) => {
        return price.times(this.amount);
      });

      // update formatted price
      this.priceFormatted = price.format();

      return price;
    },

    getVariationString() {
      let str = "?amount=" + this.amount;

      this.variations().forEach((variation) => {
        if (typeof variation == "undefined") return;
        str += variation.urlString();
      });

      return str;
    },

    setAmount(amount) {
      if (!amount) {
        let el = this.$el.querySelector("input[x-model=amount]");
        el.addEventListener("focus", el.select);
        if (!el) return;
        amount = el.value;
      }
      if (!amount) return;
      // we dont check for correct min setting to avoid hard to find
      // bugs in ajax returns
      this.amount = amount;
    },

    setMin() {
      let el = this.$el.querySelector("input[x-model=amount]");
      if (!el) return;
      this.min = el.getAttribute("min") || this.min;
    },

    update() {
      RockCommerce.throttle(() => {
        // update min amount
        if (this.amount < this.min) this.setAmount(this.min);
        this.getPrice();

        // update properties
        this.variationString = this.getVariationString();
        this.addToCartUrl = this.getAddToCartUrl();

        // trigger event
        RockCommerce.trigger("infoUpdate", this);
      }, 20);
    },

    /**
     * Get all variations of this info object
     * We don't cache the result to make sure we always get the latest elements
     * @returns
     */
    variations() {
      let variations = this.$el.querySelectorAll("[x-data=rcvariation]");
      return Array.from(variations).map((variation) => {
        return RockCommerce.getComponent(variation);
      });
    },
  }));
});

// Product Variation Component
document.addEventListener("alpine:init", () => {
  /**
   * Product Info Variation
   */
  Alpine.data("rcvariation", () => ({
    pid: 0,
    product: null,
    min: 0,
    max: 0,
    selected: 0,

    init() {
      RockCommerce.addComponent(this);
      this.pid = parseInt(this.$el.getAttribute("rc-pid"));
      this.min = parseInt(this.$el.getAttribute("rc-min"));
      this.max = parseInt(this.$el.getAttribute("rc-max"));
      this.product = RockCommerce.getComponent(this.$el, "product");
    },

    options(onlySelected = false) {
      let options = this.$el.querySelectorAll("[x-data=rcvariationoption]");
      return Array.from(options)
        .map((option) => {
          let c = RockCommerce.getComponent(option);
          if (typeof c == "undefined") return;
          if (onlySelected && !c.selected) return;
          return c;
        })
        .filter((option) => option !== undefined);
    },

    unselectAll() {
      this.options().forEach((option) => (option.selected = false));
    },

    urlString() {
      return (
        "&" +
        this.pid +
        "=" +
        this.options(true)
          .map((option) => option.pid)
          .join(",")
      );
    },
  }));
});

// Product Variation Option Component
document.addEventListener("alpine:init", () => {
  /**
   * Product Info Variation Option
   */
  Alpine.data("rcvariationoption", () => ({
    pid: 0,
    price: null,
    product: null,
    selected: false,
    variation: null,

    init() {
      RockCommerce.addComponent(this);

      // setup properties
      this.pid = parseInt(this.$el.getAttribute("rc-pid"));
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.variation = RockCommerce.getComponent(this.$el, "rcvariation");
      this.product = RockCommerce.getComponent(this.$el, "rcproduct");
      this.selected = this.$el.classList.contains("rc-active");
      if (this.selected) this.variation.selected++;

      // react
      this.$el.addEventListener("click", this.toggle.bind(this));
      this.$watch("selected", this.watchSelect.bind(this));

      // trigger product update
      this.product.update();
    },

    select() {
      if (this.variation.max === 1) this.variation.unselectAll();
      setTimeout(() => {
        if (this.variation.selected + 1 > this.variation.max) return;
        this.selected = true;
      }, 0);
    },

    toggle(e) {
      e.preventDefault();
      if (this.selected) this.unselect();
      else this.select();
    },

    unselect() {
      if (this.variation.selected - 1 < this.variation.min) return;
      this.selected = false;
    },

    watchSelect(val) {
      if (val) {
        this.$el.classList.add("rc-active");
        this.variation.selected++;
      } else {
        this.$el.classList.remove("rc-active");
        this.variation.selected--;
      }
      this.product.update();
    },
  }));
});

// Cart Component
document.addEventListener("alpine:init", () => {
  /**
   * Product Info Variation Option
   */
  Alpine.data("rccart", () => ({
    init() {
      RockCommerce.cart = this;
    },
  }));
});
