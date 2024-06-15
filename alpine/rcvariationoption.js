RockCommerce.component("rcvariationoption", {
  variation: null,

  init() {
    RockCommerce.addComponent(this);
    this.variation = RockCommerce.getComponent(this.$el, "rcvariation");
    if (!this.variation) alert("options must be placed inside a variation");
    this.$el.addEventListener("click", this.handleClick.bind(this));
  },

  get active() {
    return this.$el.classList.contains("rc-active");
  },

  disable(force) {
    let selected = this.variation.numSelected();
    if (!force && selected - 1 < this.variation.minoptions) return;
    this.$el.classList.remove("rc-active");
  },

  enable() {
    let selected = this.variation.numSelected();
    if (this.variation.maxoptions === 1) {
      this.variation.disableAll();
      this.$el.classList.add("rc-active");
    } else if (selected < this.variation.maxoptions) {
      this.$el.classList.add("rc-active");
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
