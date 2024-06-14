document.addEventListener("alpine:init", () => {
  Alpine.data("rockcommerce", () => ({
    init() {
      RockCommerce.addComponent(this);
      // RockCommerce.config = JSON.parse(this.$rcattr("data-config") || "{}");
      RockCommerce.config = JSON.parse("{}");
    },
  }));
});
