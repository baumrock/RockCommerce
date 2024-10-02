/**
 * RockCommerce Alpine Cart Component
 * Access this component via RockCommerce.cart
 * See docs for details.
 */

document.addEventListener("alpine:init", () => {
  // Cart Item Class
  class CartItem {
    constructor(data) {
      this.pid = parseInt(data.pid);
      this.amount = parseInt(data.amount) || 1;
      this.variation = data.variation;
    }
  }

  // Alpine component
  Alpine.data("rccart", () => ({
    items: [],

    init() {
      // we allow only one cart
      if (RockCommerce.cart) return;

      RockCommerce.addComponent(this);
      RockCommerce.cart = this;

      // load items from localstorage or via ajax from the backend?
      this.ajax = !this.$el.hasAttribute("data-noajax");

      this.wakeup();
      this.$watch("items", this.update.bind(this));
      this.update();
    },

    /**
     * Add item to the JS cart
     */
    add(data) {
      RockCommerce.trigger("addToCart", data, (data) => {
        const item = new CartItem(data);

        // if cart already has an item with the same pid and variation
        // then we increase the amount of that item
        // otherwise we add the new item to the cart
        const existingItem = this.items.find(
          (i) => i.pid === item.pid && i.variation === item.variation
        );
        if (existingItem) {
          existingItem.amount += item.amount;
        } else {
          this.items.push(item);
        }

        this.save();
      });
    },

    get count() {
      let count = 0;
      this.items.forEach((item) => {
        count += parseInt(item.amount || 1);
      });
      return count;
    },

    getFromLocalStorage() {
      return JSON.parse(localStorage.getItem("rc-cart") || "{}");
    },

    getSleepValue() {
      const data = {
        _items: this.items,
      };
      return JSON.stringify(data);
    },

    /**
     * Load cart from backend
     */
    loadFromBackend() {
      RockCommerce.loading();
    },

    /**
     * Save cart
     */
    save() {
      this.update();
      json = this.getSleepValue();
      localStorage.setItem("rc-cart", json);
    },

    /**
     * General update method triggered on every change
     */
    update() {
      RockCommerce.debounce("rc-cart-update", () => {
        this.updateCartCount();
      });
    },

    /**
     * Update cart count on frontend
     *
     * This method populates all elements with rc-cart-count attribute
     * so all you have to do to show the cart count is to add rc-cart-count="true" to an element
     * Example: <div rc-cart-count>0</div>
     */
    updateCartCount() {
      let elements = document.querySelectorAll("[rc-cart-count]");
      elements.forEach((el) => {
        el.textContent = this.count;
        if (this.count > 0) el.removeAttribute("hidden");
        else el.setAttribute("hidden", true);
      });
    },

    /**
     * Wakeup: Read data from localstorage
     */
    wakeup() {
      const data = this.getFromLocalStorage();
      // loop through all items from the localstorage
      for (const key in data) {
        if (key === "_items") {
          if (this.ajax) this.loadFromBackend();
          else this.items = data._items;
        }
      }
    },
  }));
});
