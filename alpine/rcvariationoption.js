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

  handleClick(event) {
    event.preventDefault();
    this.toggle();
    console.log(this.variation.minoptions);
  },

  toggle() {
    this.$el.classList.toggle("rc-active");
  },
});
