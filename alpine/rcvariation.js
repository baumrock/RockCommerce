// Product Variation Component
RockCommerce.component("rcvariation", {
  minoptions: 0,
  maxoptions: 1,

  init() {
    RockCommerce.addComponent(this);
    this.minoptions = parseInt(this.$rcattr("rc-minoptions"));
    this.maxoptions = parseInt(this.$rcattr("rc-maxoptions"));
  },

  disableAll() {
    this.options(true).forEach((option) => {
      option.disable(true);
    });
  },

  getSelected() {
    return this.$el.querySelectorAll("[x-data=rcvariationoption].rc-active");
  },

  options(onlySelected) {
    let selector = onlySelected
      ? "[x-data=rcvariationoption].rc-active"
      : "[x-data=rcvariationoption]";
    return Array.from(this.$el.querySelectorAll(selector)).map((el) =>
      RockCommerce.getComponent(el)
    );
  },

  get optionsString() {
    return Array.from(this.getSelected())
      .map((el) => el.getAttribute("rc-pid"))
      .join(",");
  },

  numSelected() {
    return this.getSelected().length;
  },
});
