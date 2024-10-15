RockCommerce.addComponent("RcTaxselect", {
  first: true,
  id: false,
  percentLong: "",

  ready() {
    this.$watch("id", this.setTaxID.bind(this));
    setTimeout(() => this.setCurrent(), 0);
  },

  setCurrent() {
    const current = RockCommerce.taxrates.getCurrentTaxrate();
    if (!current) return;
    this.id = current.id;
    this.percentLong = current.percentLong;
  },

  setTaxID(id) {
    RockCommerce.debounce(
      "setTaxID",
      () => {
        // set taxID for JS
        RockCommerce.taxrates.setCurrentID(id);

        // set current taxrate in this component
        this.setCurrent();

        // update all products
        RockCommerce.getComponents("RcProduct").forEach((product) => {
          product.update();
        });

        // are we on the cart page? then reload the cart
        const cart = RockCommerce.cart;
        if (cart && cart.doReload) cart.reload();

        // set taxID for PHP
        if (this.first) {
          this.first = false;
          return;
        } else {
          RockCommerce.fetch("/set-tax-id", {
            payload: { taxID: id },
            animation: false,
          });
        }
      },
      // cart.reload has 50ms debounce
      // so we need something smaller to not trigger another reload
      10
    );
  },
});
