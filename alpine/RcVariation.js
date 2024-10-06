// Product Variation Component
RockCommerce.component("RcVariation", {
  minoptions: 0,
  maxoptions: 1,

  init() {
    RockCommerce.addComponent(this);
    this.minoptions = parseInt(this.$rcattr("rc-minoptions"));
    this.maxoptions = parseInt(this.$rcattr("rc-maxoptions")) || 1;
  },

  disableAll() {
    this.options(true).forEach((option) => {
      option.disable(true);
    });
  },

  getSelected() {
    return this.$el.querySelectorAll("[x-data=RcVariationOption].rc-active");
  },

  options(onlySelected) {
    let selector = onlySelected
      ? "[x-data=RcVariationOption].rc-active"
      : "[x-data=RcVariationOption]";
    return Array.from(this.$el.querySelectorAll(selector)).map((el) =>
      RockCommerce.getComponent(el)
    );
  },

  /**
   * Returns a string that represents the selected options
   * Usually this is one option, but if there are multiple options
   * then they are comma separated, eg "1010,1011,1012"
   *
   * Options are always sorted ASC for quick and easy comparisons as string.
   */
  get optionsString() {
    return Array.from(this.getSelected())
      .map((el) => parseInt(el.getAttribute("rc-pid")))
      .sort((a, b) => a - b)
      .join(",");
  },

  numSelected() {
    return this.getSelected().length;
  },
});
