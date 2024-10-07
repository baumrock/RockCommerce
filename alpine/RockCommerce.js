/**
 * RockCommerce Alpine component
 *
 * This is by design very minimal. Put your logic in
 * RockCommerce.js or other alpine components!
 */
document.addEventListener("alpine:init", () => {
  Alpine.data("RockCommerce", () => ({
    init() {
      RockCommerce.addComponent(this);
      RockCommerce.config = JSON.parse(this.$rcattr("data-config") || "{}");
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
    const c = RockCommerce.getComponent(el);
    return c.$rcattr("rc-pid");
  });

  /**
   * Get attribute of alpine component element
   * <div x-data=mycomponent foo=foo></div>
   * Usage c.$rcattr('foo')
   */
  Alpine.magic("rcattr", (el) => {
    const c = RockCommerce.getComponent(el);
    return (attr) => c.$root.getAttribute(attr);
  });

  /**
   * Component type (eg RcCart, RcProduct)
   */
  Alpine.magic("rctype", (el) => {
    const c = RockCommerce.getComponent(el);
    return c.$el.getAttribute("x-data");
  });
});
