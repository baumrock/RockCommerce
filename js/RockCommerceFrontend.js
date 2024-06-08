/**
 * Frontend-specific extensions for RockCommerce
 */
RockCommerce.cid = 0;
RockCommerce.components = {};

_RockCommerce.prototype.addComponent = function (c) {
  const cid = ++RockCommerce.cid;
  RockCommerce.components[cid] = c;
  c.$el.setAttribute("rc-cid", cid);
};

_RockCommerce.prototype.component = function (el) {
  const cid = el.getAttribute("rc-cid");
  return RockCommerce.components[cid];
};

/**
 * AlpineJS Components for RockCommerce
 */
document.addEventListener("alpine:init", () => {
  /**
   * Product Info Component
   */
  Alpine.data("rcproductinfo", () => ({
    amount: 0,
    min: 0,
    price: 0,
    priceEl: false,

    init() {
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.priceEL = this.$el.querySelector("[rc-price]");
      this.setMin();
      this.setAmount();
      this.initUpdateUrl();
      this.updatePrice();
      this.$watch("amount", (amount) => {
        RockCommerce.trigger("setAmount", parseInt(amount));
      });
      this.$watch("amount", this.updatePrice.bind(this));

      RockCommerce.addComponent(this);
    },

    addToCart() {
      RockCommerce.trigger("addToCart", this);
    },

    getPrice() {
      // calculate the total price
      let price = this.price;

      // add prices of variations
      this.variations().forEach((variation) => {
        if (typeof variation == "undefined") return;
        variation.options(true).forEach((option) => {
          price = price.plus(option.price.float());
        });
      });

      // support hooks for price calculations (see pricing docs)
      // RockCommerce.on('getPrice', (price) => {
      //   return price.times(2);
      // });
      price = RockCommerce.trigger("getPrice", price);

      // multiply price with amount
      price = price.times(this.amount);

      return price;
    },

    initUpdateUrl() {
      this.updateUrl = !!this.$el.getAttribute("data-rc-updateurl");
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

    updatePrice() {
      RockCommerce.throttle(() => {
        if (this.amount < this.min) this.setAmount(this.min);
        if (!this.priceEL) return;
        this.priceEL.innerText = this.getPrice().format();
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
        return RockCommerce.component(variation);
      });
    },
  }));

  /**
   * Product Info Variation
   */
  Alpine.data("rcvariation", () => ({
    info: null,
    infoEl: null,
    min: 0,
    max: 0,
    selected: 0,

    init() {
      this.min = parseInt(this.$el.getAttribute("rc-min"));
      this.max = parseInt(this.$el.getAttribute("rc-max"));
      this.infoEl = this.$el.closest("[x-data=rcproductinfo]");
      this.info = RockCommerce.component(this.infoEl);
      RockCommerce.addComponent(this);
    },

    options(onlySelected = false) {
      let options = this.$el.querySelectorAll("[x-data=rcvariationoption]");
      return Array.from(options)
        .map((option) => {
          let c = RockCommerce.component(option);
          if (typeof c == "undefined") return;
          if (onlySelected && !c.selected) return;
          return c;
        })
        .filter((option) => option !== undefined);
    },

    unselectAll() {
      this.options().forEach((option) => (option.selected = false));
    },
  }));

  /**
   * Product Info Variation Option
   */
  Alpine.data("rcvariationoption", () => ({
    price: null,
    selected: false,
    variation: null,
    variationEl: null,

    init() {
      // setup properties
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.variationEl = this.$el.closest("[x-data=rcvariation]");
      this.variation = RockCommerce.component(this.variationEl);
      this.selected = this.$el.classList.contains("rc-active");
      if (this.selected) this.variation.selected++;

      // react
      this.$el.addEventListener("click", this.toggle.bind(this));
      this.$watch("selected", this.watchSelect.bind(this));

      // add component
      RockCommerce.addComponent(this);
      this.variation.info.updatePrice();
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
      this.variation.info.updatePrice();
    },
  }));
});
