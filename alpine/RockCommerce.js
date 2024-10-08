/**
 * RockCommerce Alpine component
 *
 * This is the first file that is loaded by RockCommerce.
 * All other component files are loaded after this one.
 *
 * The RockCommerce DOM element is the very last on every page,
 * so that the RockCommerce Alpine component is initialized last.
 */
console.log("RockCommerceAlpine");

document.addEventListener("alpine:init", () => {
  Alpine.data("RockCommerceAlpine", () => ({
    init() {
      RockCommerceOLD.addComponent(this);
      RockCommerceOLD.config = JSON.parse(this.$rcattr("data-config") || "{}");
      document.dispatchEvent(new CustomEvent("RockCommerce:ready"));
    },
  }));
});

/**
 * Add global magic to alpine components
 * Magic properties are accessible via component.$foo
 * Magic functions are accessible via component.$foo()
 * See https://alpinejs.dev/advanced/extending#custom-magics
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
