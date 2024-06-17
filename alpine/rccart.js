// Cart Component
document.addEventListener("alpine:init", () => {
  // Cart Item Class
  class CartItem {
    constructor(data) {
      this.pid = parseInt(data.pid);
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

    add(data) {
      RockCommerce.trigger("addToCart", data, (data) => {
        const item = new CartItem(data);
        while (data.amount > 0) {
          this.items.push(item);
          data.amount--;
        }
      });
    },

    get count() {
      return this.items.length;
    },

    /**
     * Save data to localStorage
     */
    sleep() {
      const data = {
        items: this.items,
      };
      localStorage.setItem("rc-cart", JSON.stringify(data));
      // TODO: Save to database
    },

    /**
     * General update method
     */
    update() {
      RockCommerce.debounce("rc-cart-update", () => {
        this.updateCartCountElements();
        this.sleep();
      });
    },

    /**
     * Update cart count on crontend
     */
    updateCartCountElements() {
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
