RockCommerce.component("rctaxselect", {
  taxrate: 0,
  init() {
    this.$watch("taxrate", (rate) => {
      RockCommerce.taxrate = rate;
      localStorage.setItem("rc-taxrate", rate);
      const product = RockCommerce.getComponent(this.$el, "rcproduct");
      if (product) product.update();
    });
    this.taxrate = localStorage.getItem("rc-taxrate") || 0;
  },
});
