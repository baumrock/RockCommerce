RockCommerce.addComponent("RcTaxselect", {
  taxrate: 0,

  ready() {
    // whenever the taxrate changes we save it and update all products
    this.$watch("taxrate", (rate) => {
      // save global taxrate
      RockCommerce.setTaxrate(rate);

      // update all products
      RockCommerce.getComponents("RcProduct").forEach((product) => {
        product.update();
      });

      // reload cart?
      const cart = RockCommerce.cart;
      if (cart && cart.doReload) cart.reload();
    });
    this.taxrate =
      localStorage.getItem("rc-taxrate") ||
      this.$el.getAttribute("rc-default-taxrate");
  },
});
