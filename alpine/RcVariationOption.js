RockCommerce.addComponent("RcVariationOption", {
  money: null,
  variation: null,

  ready() {
    this.product = RockCommerce.getComponent(this.$el, "RcProduct");
    this.variation = RockCommerce.getComponent(this.$el, "RcVariation");
    if (!this.variation) alert("options must be placed inside a variation");
    this.$el.addEventListener("click", this.handleClick.bind(this));
    this.money = new RockMoney(this.$rcattr("rc-price"));
  },

  get active() {
    return this.$el.classList.contains("rc-active");
  },

  disable(force) {
    let selected = this.variation.numSelected();
    if (!force && selected - 1 < this.variation.minoptions) return;
    this.$el.classList.remove("rc-active");
    this.variation.optionsChanged();
  },

  ___enable() {
    this.$el.classList.add("rc-active");
    this.variation.optionsChanged();
  },

  handleClick(event) {
    event.preventDefault();
    this.toggle();
  },

  toggle() {
    if (this.active) this.disable();
    else {
      let selected = this.variation.numSelected();

      // no early exit if max options are reached!
      // we need to run the code to update the optionsChanged()

      // Single selection mode
      if (this.variation.maxoptions === 1) {
        this.variation.disableAll();
        this.enable();
      }
      // Multi-selection mode, within limit
      else if (selected < this.variation.maxoptions) {
        this.enable();
      }
      // Max options reached
      // do not enable this option when clicked
    }
  },
});
