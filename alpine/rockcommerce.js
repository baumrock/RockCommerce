/**
 * RockCommerce Alpine component
 *
 * This is by design very minimal. Put your logic in
 * RockCommerce.js or other alpine components!
 */
document.addEventListener("alpine:init", () => {
  Alpine.data("rockcommerce", () => ({
    init() {
      RockCommerce.addComponent(this);
      RockCommerce.config = JSON.parse(this.$rcattr("data-config") || "{}");
      document.dispatchEvent(new CustomEvent("RockCommerce:ready"));
    },
  }));
});
