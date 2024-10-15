RockCommerce.addComponent("RcTaxrates", {
  defaultID: false,
  currentID: false,
  taxrates: {},

  ready() {
    RockCommerce.taxrates = this;
    this.defaultID = RockCommerce.config.defaultTaxID;
    this.initCurrentID();
    this.setTaxrates();

    // notify all taxselects about the current taxrate
    RockCommerce.getComponents("RcTaxselect").forEach((taxselect) => {
      taxselect.setCurrent();
    });
  },

  getCurrentTaxrate() {
    for (const id in this.taxrates) {
      if (this.taxrates[id].id === this.currentID) {
        return this.taxrates[id];
      }
    }
    return false;
  },

  initCurrentID() {
    this.currentID = this.defaultID;

    // if a taxrate is saved in localstorage, use that
    const taxID = parseInt(localStorage.getItem("rc-taxID"));
    if (!isNaN(taxID)) this.currentID = taxID;
  },

  setCurrentID(id) {
    this.currentID = parseInt(id);
    localStorage.setItem("rc-taxID", id);
  },

  setTaxrates() {
    // loop all items of taxrates and set the default taxrate
    for (const id in RockCommerce.config.taxrates) {
      const taxrate = RockCommerce.config.taxrates[id];
      taxrate.percent = parseFloat(taxrate.percent.toFixed(2));
      taxrate.taxrate = parseFloat(taxrate.taxrate.toFixed(5));
      this.taxrates[id] = taxrate;
    }
  },
});
