/**
 * RockCommerce Alpine Cart Component
 * Access this component via RockCommerce.cart
 * See docs for details.
 */
RockCommerce.addComponent("RcCart", {
  count: 0,
  id: false,
  items: [],
  doReload: false,
  totalNet: 0,
  totalGross: 0,
  totalVat: 0,

  ready() {
    // we allow only one cart
    if (RockCommerce.cart) return;
    RockCommerce.cart = this;
    this.$watch("items", this.update.bind(this));
    this.wakeup();
    if (this.$el.hasAttribute("rc-reload")) {
      this.doReload = true;
      this.reload();
    }
  },

  /**
   * Add item to the JS cart
   */
  ___addItem(data) {
    // send ajax request to add item to cart
    RockCommerce.fetch("/cart/add", {
      payload: {
        item: data,
        taxrate: RockCommerce.getTaxrate(),
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

  getItem(id) {
    return this.items.find((item) => item.id === id);
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

  itemAmountAdd(id) {
    const item = this.getItem(id);
    item.amount++;
    RockCommerce.debounce(
      "rc-cart-item-amount",
      () => this.setItemCount(id),
      500
    );
  },

  itemAmountSub(id) {
    const item = this.getItem(id);
    if (item.amount < 2) return;
    item.amount--;
    RockCommerce.debounce(
      "rc-cart-item-amount",
      () => this.setItemCount(id),
      500
    );
  },

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
    this.totalNet = data.totalNet || 0;
    this.totalGross = data.totalGross || 0;
    this.totalVat = data.totalVat || 0;
    this.save();
  },

  /**
   * Set item count via AJAX
   */
  setItemCount(id) {
    const item = this.getItem(id);
    if (!item) return;
    RockCommerce.fetch("/cart/set-item-amount", {
      payload: { item: id, amount: item.amount },
    }).then((data) => {
      this.setData(data.cart);
    });
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
