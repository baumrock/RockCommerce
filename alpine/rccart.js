// Cart Component
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

        this.update();
      });
    },

    get count() {
      let count = 0;
      this.items.forEach((item) => {
        count += parseInt(item.amount || 1);
      });
      return count;
    },

    /**
     * Save cart to the backend
     */
    save() {
      let json = this.sleep();
      console.log(json, "save to backend");
    },

    /**
     * Get JSON representation of the cart and save it to localStorage
     */
    sleep() {
      const data = {
        items: this.items,
      };
      let json = JSON.stringify(data);
      localStorage.setItem("rc-cart", json);
      return json;
    },

    /**
     * General update method
     */
    update() {
      RockCommerce.debounce("rc-cart-update", () => {
        this.updateCartCount();
        this.sleep();
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
     * Load data from localstorage
     */
    wakeup() {
      const data = localStorage.getItem("rc-cart") || "{}";
      const parsedData = JSON.parse(data);
      for (const key in parsedData) {
        if (parsedData.hasOwnProperty(key)) {
          this[key] = parsedData[key];
        }
      }
    },
  }));
});
