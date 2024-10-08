console.log("RockCommerceNEW");

/**
 * Setup the global RockCommerce object
 * This object will be loaded before any other Alpine components
 * and will be replaced by the RockCommerce Alpine component some
 * lines below in this file once it is loaded.
 */
var RockCommerce = {
  /**
   * Helper function that is called by all other alpine components
   * and creates a wired alpine component that supports Hooks!
   */
  addComponent(name, comp) {
    document.addEventListener("alpine:init", () =>
      Alpine.data(name, () => ProcessWire.wire(comp, name, true))
    );
  },
};

/**
 * RockCommerce Alpine Component
 *
 * Will be loaded when <div x-data="RockCommerce"> is found in the DOM
 * and then replace the global RockCommerce object.
 */
document.addEventListener("alpine:init", () => {
  Alpine.data("RockCommerceNEW", () => ({
    init() {
      console.log("RockCommerceNEW init");
    },
  }));
});
