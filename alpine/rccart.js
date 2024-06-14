// Cart Component
document.addEventListener("alpine:init", () => {
  Alpine.data("rccart", () => ({
    items: [],

    init() {
      RockCommerce.addComponent(this);
      RockCommerce.cart = this;
    },

    // add item to the list of items
    // we add all items as single item and later build sums for rows
    addItem(product) {
      while (product.amount > 0) {
        this.items.push(
          new CartItem(product.pid, 1, product.getVariationString(true))
        );
        product.amount--;
      }
    },

    get count() {
      return this.items.length;
    },
  }));

  // Cart Item Class
  class CartItem {
    constructor(pid, amount, variation) {
      this.pid = pid;
      this.amount = amount;
      this.variation = variation;
    }
  }
});
