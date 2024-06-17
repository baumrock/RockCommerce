// Cart Component
document.addEventListener("alpine:init", () => {
  Alpine.data("rccart", () => ({
    items: [],

    init() {
      RockCommerce.addComponent(this);
      RockCommerce.cart = this;
      this.$watch("items", this.update.bind(this));
      this.update();
    },

    // add item to the list of items
    // we add all items as single item and later build sums for rows
    addItem(pid, amount, variation) {
      const item = new CartItem(pid, variation);
      while (amount > 0) {
        this.items.push(item);
        amount--;
      }
    },

    get count() {
      return this.items.length;
    },

    /**
     * General update method
     */
    update() {
      this.updateCartCount();
    },

    /**
     * Update cart count on crontend
     */
    updateCartCount() {
      let elements = document.querySelectorAll("[rc-cart-count]");
      elements.forEach((el) => {
        el.textContent = this.count;
        if (this.count > 0) el.removeAttribute("hidden");
        else el.setAttribute("hidden", true);
      });
    },
  }));

  // Cart Item Class
  class CartItem {
    constructor(pid, variation) {
      this.pid = parseInt(pid);
      this.variation = variation;
    }
  }
});
