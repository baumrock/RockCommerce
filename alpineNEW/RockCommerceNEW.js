console.log("RockCommerceNEW");

/**
 * Setup the global RockCommerce object
 * This object will be loaded before any other Alpine components
 * and will be replaced by the RockCommerce Alpine component some
 * lines below in this file once it is loaded.
 */
var RockCommerce = {
  components: [],

  /**
   * Helper function that is called by all other alpine components
   * and creates a wired alpine component that supports Hooks!
   */
  addComponent(name, comp) {
    document.addEventListener("alpine:init", () =>
      Alpine.data(name, () => {
        // add a custom hookable init method for every added component
        // this custom init() method is called by alpine whenever it finds
        // a <div x-data=...> dom element. the init
        comp.___init = function () {
          console.log("my init for " + name);
        };
        const wired = ProcessWire.wire(comp, name, true);
        this.components.push(wired);
        return wired;
      })
    );
  },
};

/**
 * RockCommerce Alpine Component
 *
 * Will be loaded when <div x-data="RockCommerce"> is found in the DOM
 * and then replace the global RockCommerce object.
 */
RockCommerce.addComponent("RockCommerceNEW", {});

/**
 * Add global apine magic
 */
document.addEventListener("alpine:init", () => {
  /**
   * Get page id of alpine component element
   * <div x-data=mycomponent rc-pid=123></div>
   * Usage c.$rcpid()
   */
  Alpine.magic("rcpid", (el) => {
    const c = RockCommerceOLD.getComponent(el);
    return c.$rcattr("rc-pid");
  });

  /**
   * Get attribute of alpine component element
   * <div x-data=mycomponent foo=foo></div>
   * Usage c.$rcattr('foo')
   */
  Alpine.magic("rcattr", (el) => {
    const c = RockCommerceOLD.getComponent(el);
    return (attr) => c.$root.getAttribute(attr);
  });

  /**
   * Component type (eg RcCart, RcProduct)
   */
  Alpine.magic("rctype", (el) => {
    const c = RockCommerceOLD.getComponent(el);
    return c.$el.getAttribute("x-data");
  });
});
