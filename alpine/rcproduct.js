// Product Component
RockCommerce.component("rcproduct", {
  amount: 1,
  amountInput: false,
  maxamount: 1,
  minamount: 1,

  // dont use empty string to avoid FOUC on init
  price: 0,

  // this is the base price money object
  money: null,

  init() {
    RockCommerce.addComponent(this);
    this.money = new RockMoney(this.$el.getAttribute("rc-price"));
    this.minamount = parseInt(
      this.$rcattr("rc-minamount") || RockCommerce.config.minamount
    );
    this.initAmount();
    this.$watch("amount", this.update.bind(this));
    this.maxamount = parseInt(
      this.$rcattr("rc-maxamount") || RockCommerce.config.maxamount
    );
    this.update();
  },

  addToCart() {
    RockCommerce.trigger("addToCart", this, (scope) => {
      const product = RockCommerce.getComponent(scope.$root);
      const cart = RockCommerce.cart;
      cart.addItem(
        product.$rcpid,
        product.amount,
        product.variationStringCart()
      );
    });
  },

  get addToCartUrl() {
    return "tbd";
  },

  getVariation(id) {
    const selector = `[x-data=rcvariation][rc-pid="${id}"]`;
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

  update() {
    RockCommerce.throttle(() => {
      // make sure amount is correct
      // this is because we use x-bind on amount input and cant know the input
      let amount = this.amount;
      if (amount > this.maxamount) amount = this.maxamount;
      else if (amount < this.minamount) amount = this.minamount;
      this.amount = amount;

      // calc price
      let money = this.money;

      // add prices of variations
      money = RockCommerce.trigger(
        "rcproduct:priceAddVariations",
        money,
        (money) => {
          this.variations.forEach((variation) => {
            if (typeof variation == "undefined") return;
            variation.options(true).forEach((option) => {
              money = money.plus(option.money.float());
            });
          });
          return money;
        }
      );

      // add amount
      money = RockCommerce.trigger("rcproduct:priceTotal", money, (money) => {
        return money.times(this.amount);
      });

      // write formatted price to price property
      // so that the frontend can subscribe via x-text=price
      this.price = money.format();
      this.variationStringUrl();
    }, 0);
  },

  get variations() {
    return Array.from(this.$root.querySelectorAll("[x-data=rcvariation]")).map(
      (el) => {
        return RockCommerce.getComponent(el);
      }
    );
  },

  variationStringCart() {
    // we create an object with properties
    // this will make sure that variations will be sorted alphabetically
    let obj = {};

    // add querystring for all variations
    this.variations.forEach((variation) => {
      obj[variation.$rcpid] = variation.optionsString;
    });

    // convert to string
    let str = "";
    Object.keys(obj).forEach((key) => {
      str += `-${key}=${obj[key]}`;
    });
    return str.substring(1);
  },

  variationStringUrl() {
    // we create an object with properties
    // this will make sure that variations will be sorted alphabetically
    let obj = {};

    // add amount?
    obj["amount"] = this.amount;

    // add querystring for all variations
    this.variations.forEach((variation) => {
      obj[variation.$rcpid] = variation.optionsString;
    });

    // convert to string
    let str = "";
    Object.keys(obj).forEach((key) => {
      str += `-${key}=${obj[key]}`;
    });
    str = "#" + str.substring(1);

    // update url?
    if (this.$rcattr("rc-updateUrl")) {
      let url = new URL(window.location);
      url.hash = str;
      history.replaceState(null, "", url);
    }
    return str;
  },
});
