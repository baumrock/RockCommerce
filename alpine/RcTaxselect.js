RockCommerceOLD.component("RcTaxselect", {
  taxrate: 0,
  init() {
    this.$watch("taxrate", (rate) => {
      RockCommerceOLD.setTaxrate(rate);
      const product = RockCommerceOLD.getComponent(this.$el, "RcProduct");
      if (product) product.update();
    });
    this.taxrate =
      localStorage.getItem("rc-taxrate") ||
      this.$el.getAttribute("rc-default-taxrate");
  },
});
