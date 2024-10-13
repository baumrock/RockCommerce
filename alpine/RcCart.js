/**
 * RockCommerce Alpine Cart Component
 * Access this component via RockCommerce.cart
 * See docs for details.
 */
(() => {
  // Cart Item Class
  class CartItem {
    constructor(data) {
      this.pid = parseInt(data.pid);
      this.amount = parseInt(data.amount) || 1;
      this.variation = data.variation;
    }
  }

  RockCommerce.addComponent("RcCart", {
    count: 0,
    id: false,
    items: [],

    ready() {
      // we allow only one cart
      if (RockCommerce.cart) return;
      RockCommerce.cart = this;
      this.$watch("items", this.update.bind(this));
      this.wakeup();
      if (this.$el.hasAttribute("rc-reload")) this.reload();
    },

    /**
     * Add item to the JS cart
     */
    ___addItem(data) {
      // send ajax request to add item to cart
      RockCommerce.fetch("/cart/add", {
        payload: {
          item: data,
          taxrate: RockCommerce.taxrate,
        },
      })
        .then((data) => {
          if (data.success) {
            this.setData(data.cart);
            this.itemAdded(data.itemAdded);
          } else throw new Error("Error adding item to cart");
        })
        .catch((error) => {
          alert(error.message);
        });
    },

    get count() {
      let count = 0;
      this.items.forEach((item) => {
        count += parseInt(item.amount || 1);
      });
      return count;
    },

    deleteItem(id) {
      RockCommerce.fetch("/cart/delete", {
        payload: { id },
      })
        .then((data) => {
          this.setData(data.cart);
        })
        .catch((error) => {
          alert(error.message);
        });
    },

    getFromLocalStorage() {
      return JSON.parse(localStorage.getItem("rc-cart") || "{}");
    },

    getSleepValue() {
      const data = {
        id: this.id,
        count: this.count,
        items: this.items,
      };
      return JSON.stringify(data);
    },

    /**
     * Hookable method executed after an item has been added to the cart
     */
    ___itemAdded(item) {},

    /**
     * Reload cart data from backend
     */
    reload() {
      const options = {
        payload: {
          taxrate: RockCommerce.getTaxrate(),
        },
        animation: this.$rcattr("rc-animation"),
      };
      console.log(options);
      RockCommerce.fetch("/cart/get", options)
        .then((response) => {
          this.setData(response);
        })
        .catch((error) => {
          console.error("Error loading cart from backend:", error);
          alert("Error loading cart from backend. Please try again later.");
        });
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
     * Set given data and save to localstorage
     */
    setData(data) {
      this.id = data.id || false;
      this.count = data.count || 0;
      this.items = data.items || [];
      this.save();
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
      this.setData(this.getFromLocalStorage());
    },
  });
})();
