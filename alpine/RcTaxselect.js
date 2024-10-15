RockCommerce.addComponent("RcTaxselect", {
  id: false,
  first: true,

  ready() {
    this.$watch("id", this.watchTaxID.bind(this));
  },

  get percentLong() {
    if (!this.id) return;
    return this.taxrate.percentLong;
  },

  setCurrent() {
    if (!this.taxrate) return;
    this.id = this.taxrate.id;
  },

  get taxrate() {
    return RockCommerce.taxrates.getCurrentTaxrate();
  },

  watchTaxID() {
    // on the first change we don't do anything
    // this is when the id is initially set from false to 123
    if (this.first) return (this.first = false);

    // set taxID for JS
    RockCommerce.taxrates.setCurrentID(this.id);

    // update all products
    RockCommerce.getComponents("RcProduct").forEach((product) => {
      product.update();
    });

    // send taxID to PHP
    RockCommerce.fetch("/set-tax-id", {
      payload: { taxID: this.id },
      // animation: false,
    }).then((data) => {
      if (data.cart) RockCommerce.cart.setData(data.cart);
    });
  },
});
