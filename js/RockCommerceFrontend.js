// Frontend-specific extensions for RockCommerce

/**
 * Save reference to alpine component
 * @param info
 */
_RockCommerce.prototype.addProductInfo = function (info) {
  this.productinfos = this.productinfos || [];
  this.productinfos.push(info);
};

/**
 * Get ProductInfo instance from dom element
 * @param info
 */
_RockCommerce.prototype.getProductInfo = function (el) {
  let root = el.closest("[x-data=rcproductinfo]");
  if (!root) return false;
  let infos = this.productinfos || [];
  let foundInfo = false;
  infos.forEach((info) => {
    if (info.$el === el) {
      foundInfo = info;
      return;
    }
  });
  return foundInfo;
};

/**
 * Product Info Component
 */
document.addEventListener("alpine:init", () => {
  Alpine.data("rcproductinfo", () => ({
    count: 0,
    min: 1,
    price: 0,

    init() {
      this.priceEL = this.$el.querySelector("[rc-price]");

      this.setMin();
      this.setCount();
      this.initUpdateUrl();
      this.updatePrice();

      // watch properties
      this.$watch("count", this.updatePrice.bind(this));

      // watch elements
      this.watchVariations();

      RockCommerce.addProductInfo(this);
    },

    initUpdateUrl() {
      this.updateUrl = !!this.$el.getAttribute("data-rc-updateurl");
    },

    setCount(count) {
      if (!count) {
        let el = this.$el.querySelector("input[x-model=count]");
        el.addEventListener("focus", el.select);
        if (!el) return;
        count = el.value;
      }
      if (!count) return;
      // we dont check for correct min setting to avoid hard to find
      // bugs in ajax returns
      this.count = count;
    },

    setMin() {
      this.min = 1;
      let el = this.$el.querySelector("input[x-model=count]");
      if (!el) return;
      this.min = el.getAttribute("min") || this.min;
    },

    updatePrice() {
      if (this.count < this.min) this.setCount(this.min);
      if (!this.price) {
        this.price = new RockMoney(this.$el.getAttribute("data-price"));
      }
      if (!this.priceEL) return;
      this.priceEL.innerText = this.price.times(this.count).toLocale();
    },

    watchVariations() {
      document.addEventListener("click", (e) => {
        let option = e.target.closest("[rc-variation-option]");
        if (!option) return;
        option.classList.toggle("rc-active");
        e.preventDefault();
      });
    },
  }));
});
