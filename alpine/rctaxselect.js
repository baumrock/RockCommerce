RockCommerce.component("rctaxselect", {
  taxrate: 0,
  init() {
    this.$watch("taxrate", (rate) => {
      RockCommerce.setTaxrate(rate);
      const product = RockCommerce.getComponent(this.$el, "rcproduct");
      if (product) product.update();
    });
    this.taxrate =
      localStorage.getItem("rc-taxrate") ||
      this.$el.getAttribute("rc-default-taxrate");
  },
});
