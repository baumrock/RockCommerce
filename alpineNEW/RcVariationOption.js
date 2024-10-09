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
    this.product.update();
  },

  enable() {
    let selected = this.variation.numSelected();
    if (this.variation.maxoptions === 1) {
      this.variation.disableAll();
      this.$el.classList.add("rc-active");
      this.product.update();
    } else if (selected < this.variation.maxoptions) {
      this.$el.classList.add("rc-active");
      this.product.update();
    } else {
      RockCommerce.trigger("RcVariationOption:maxoptions", this);
    }
  },

  handleClick(event) {
    event.preventDefault();
    this.toggle();
  },

  toggle() {
    if (this.active) this.disable();
    else this.enable();
  },
});
