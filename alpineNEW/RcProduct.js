// Product Component
RockCommerce.addComponent("RcProduct", {
  amount: 1,
  amountInput: false,
  maxamount: 1,
  minamount: 1,
  updateTimer: false,

  // dont use empty string to avoid FOUC on init
  price: 0,

  // this is the base price money object
  money: null,

  ready() {
    this.money = new RockMoney(this.$el.getAttribute("rc-price"));
    this.minamount = parseInt(
      this.$rcattr("rc-minamount") || RockCommerce.config.minamount
    );
    this.initAmount();
    this.$watch("amount", this.update.bind(this));
    this.maxamount = parseInt(
      this.$rcattr("rc-maxamount") || RockCommerce.config.maxamount
    );
    this.loadProductConfig();
    this.update();
  },

  ___addToCart() {
    RockCommerce.cart.addItem({
      pid: this.$rcpid,
      amount: this.amount,
      variation: this.variationString(false, false),
    });
  },

  getVariation(id) {
    const selector = `[x-data=RcVariation][rc-pid="${id}"]`;
    const varEl = this.$root.querySelector(selector);
    return RockCommerce.getComponent(varEl);
  },

  initAmount() {
    this.amountInput = this.$el.querySelector("[x-model=amount]");
    this.amount = parseInt(this.amountInput.value || this.minamount);
    if (!this.amountInput) return;
    this.amountInput.addEventListener("focus", (e) => {
      e.target.select();
    });
  },

  /**
   * Load product config from url hash
   */
  loadProductConfig() {
    const product = RockCommerce.products[0];
    if (!product) return;

    // get hash from current url
    let hash = window.location.hash.substring(1).replace(/-/g, "&");
    hash = hash.replace(/:/g, "=");
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

  minus() {
    if (this.amount - 1 < this.minamount) return (this.amount = this.minamount);
    if (this.amount - 1 > this.maxamount) return (this.amount = this.maxamount);
    this.amount--;
  },

  plus() {
    if (this.amount + 1 > this.maxamount) return (this.amount = this.maxamount);
    if (this.amount + 1 < this.minamount) return (this.amount = this.minamount);
    this.amount++;
  },

  ___priceInit(money) {
    return money;
  },

  ___priceVar(money) {
    this.variations.forEach((variation) => {
      if (typeof variation == "undefined") return;
      variation.options(true).forEach((option) => {
        money = money.plus(option.money.float());
      });
    });
    return money;
  },

  ___priceTotal(money) {
    return money.times(this.amount);
  },

  update() {
    RockCommerce.debounce("RcProduct-update", () => {
      // make sure amount is correct
      // this is because we use x-bind on amount input and cant know the input
      let amount = this.amount;
      if (amount > this.maxamount) amount = this.maxamount;
      else if (amount < this.minamount) amount = this.minamount;
      this.amount = amount;

      // calc price via hookable methods
      let money = this.money;
      money = this.priceInit(money);
      money = this.priceVar(money);
      money = this.priceTotal(money);

      // write formatted price to price property
      // so that the frontend can subscribe via x-text=price
      this.price = money.format();
      this.updateUrl();
    });
  },

  updateUrl() {
    if (!this.$rcattr("rc-updateUrl")) return;
    let str = this.variationString();
    let url = new URL(window.location);
    url.hash = str;
    history.replaceState(null, "", url);
  },

  /**
   * Get object with all variations and amounts
   * JavaScript will automatically sort the object by key
   */
  variationObject() {
    let obj = {};
    obj["amount"] = this.amount;

    // add querystring for all variations
    this.variations.forEach((variation) => {
      obj[variation.$rcpid] = variation.optionsString;
    });

    return obj;
  },

  get variations() {
    return Array.from(this.$root.querySelectorAll("[x-data=RcVariation]")).map(
      (el) => {
        return RockCommerce.getComponent(el);
      }
    );
  },

  /**
   * Returns a string that represents the selected variations
   * This is used for example to create a URL hash or to pass
   * variation info to products added to the cart.
   *
   * Example string for the url:
   * #1234567:6731,6732-amount:2
   *
   * Or for the cart:
   * 1234567:6731,6732
   *
   * @returns {string}
   */
  variationString(includeAmount = true, includeHash = true) {
    // we create an object with properties
    // this will make sure that variations will be sorted alphabetically
    let obj = this.variationObject();

    // include amount in string?
    if (!includeAmount) delete obj.amount;

    // convert to string
    let str = "";
    Object.keys(obj).forEach((key) => {
      const value = obj[key] + "";
      if (!value) return;
      str += `-${key}:${value}`;
    });

    // Add hash symbol if includeHash is true
    str = includeHash ? "#" + str.substring(1) : str.substring(1);

    // return final string
    return str;
  },
});
