// Product Component
console.log("Product.js");
document.addEventListener("alpine:init", () => {
  Alpine.data("rcproduct", () => ({
    amount: 0,
    min: 0,
    pid: 0,
    price: 0,
    priceFormatted: "",
    updateUrl: false,
    variationString: "",

    init() {
      RockCommerce.addComponent(this);
      this.pid = this.$el.getAttribute("rc-pid");
      this.price = new RockMoney(this.$el.getAttribute("rc-price"));
      this.updateUrl = !!this.$el.getAttribute("rc-updateUrl");
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

    get addToCartUrl() {
      return "/tbd/" + this.amount;
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

    getVariationString(noAmount) {
      let str = noAmount === true ? "" : "?amount=" + this.amount;

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
