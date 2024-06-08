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
    min: 1,
    price: 0,

    init() {
      this.priceEL = this.$el.querySelector("[rc-price]");

      this.setMin();
      this.setAmount();
      this.initUpdateUrl();
      this.updatePrice();
      this.$watch("amount", this.updatePrice.bind(this));

      RockCommerce.addComponent(this);
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
      this.min = 1;
      let el = this.$el.querySelector("input[x-model=amount]");
      if (!el) return;
      this.min = el.getAttribute("min") || this.min;
    },

    updatePrice() {
      if (this.amount < this.min) this.setAmount(this.min);
      if (!this.price) {
        this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      }
      if (!this.priceEL) return;

      // calculate the total price
      let price = this.price;

      // multiply price with amount
      price = price.times(this.amount);

      // write final price to dom
      this.priceEL.innerText = price.format();
    },
  }));

  /**
   * Product Info Variation
   */
  Alpine.data("rcvariation", () => ({
    min: 0,
    max: 0,
    selected: 0,
    _options: null,

    init() {
      this.min = parseInt(this.$el.getAttribute("rc-min"));
      this.max = parseInt(this.$el.getAttribute("rc-max"));
      RockCommerce.addComponent(this);
    },

    options() {
      if (this._options) return this._options;
      let options = this.$el.querySelectorAll("[x-data=rcvariationoption]");
      this._options = Array.from(options).map((option) => {
        return RockCommerce.component(option);
      });
      return this._options;
    },

    unselectAll() {
      this.options().forEach((option) => (option.selected = false));
    },
  }));

  /**
   * Product Info Variation Option
   */
  Alpine.data("rcvariationoption", () => ({
    price: 0,
    selected: false,
    variation: null,
    variationEl: null,

    init() {
      // setup properties
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.variationEl = this.$el.closest("[x-data=rcvariation]");
      this.variation = RockCommerce.component(this.variationEl);
      this.selected = this.$el.classList.contains("rc-active");

      // react
      this.$el.addEventListener("click", this.toggle.bind(this));
      this.$watch("selected", this.watchSelect.bind(this));

      // add component
      RockCommerce.addComponent(this);
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
    },
  }));
});
