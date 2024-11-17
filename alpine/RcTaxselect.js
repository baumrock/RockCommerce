RockCommerce.addComponent("RcTaxselect", {
  id: false,
  first: true,

  ready() {
    this.$watch("id", this.watchTaxID.bind(this));
  },

  /**
   * Make percentage available via x-text='percentLong'
   */
  get percentLong() {
    if (!this.id) return;
    return this.taxrate.percentLong;
  },

  setCurrent() {
    if (!this.taxrate) return;
    this.id = this.taxrate.id;
  },

  /**
   * Make current taxrate available via this.taxrate
   */
  get taxrate() {
    return RockCommerce.taxrates.getCurrentTaxrate();
  },

  /**
   * Callback whenever taxID changes
   */
  watchTaxID() {
    // on the first change we don't do anything
    // this is when the id is initially set from false to 123
    if (this.first) return (this.first = false);

    // set taxID for JS
    RockCommerce.taxrates.setCurrentID(this.id);
  },
});
