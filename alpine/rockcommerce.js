document.addEventListener("alpine:init", () => {
  Alpine.data("rockcommerce", () => ({
    init() {
      RockCommerce.addComponent(this);
      RockCommerce.config = JSON.parse(this.$rcattr("data-config") || "{}");
      this.loadProductConfig();
    },

    loadProductConfig() {
      const product = RockCommerce.products[0];
      if (!product) return;

      let hash = window.location.hash.substring(1).replace(/-/g, "&");
      const urlParams = new URLSearchParams(hash);

      // load amount
      if (urlParams.has("amount")) {
        const amount = parseInt(urlParams.get("amount"), 10);
        if (!isNaN(amount)) product.amount = amount;
      }

      // loop all product variations
      // try to find options in the url params object
      const variations = product.variations || [];
      variations.forEach((variation) => {
        const varID = variation.$rcpid;
        if (urlParams.has(varID)) {
          variation.disableAll();
          const options = urlParams.get(varID).split(",");
          options.forEach((pid) => {
            const option = RockCommerce.getComponentFromPid(pid);
            if (option) option.enable();
          });
        }
      });
    },
  }));
});
