// Product Component
document.addEventListener("alpine:init", () => {
  Alpine.data("rcproduct", () => ({
    amount: 1,
    amountInput: false,
    maxamount: 1,
    minamount: 1,

    // this is the base price money object
    money: null,

    init() {
      RockCommerce.addComponent(this);
      this.money = new RockMoney(this.$el.getAttribute("rc-price"));
      this.minamount = parseInt(
        this.$rcattr("rc-minamount") || RockCommerce.config.minamount
      );
      this.initAmountInput();
      this.amount = parseInt(this.amountInput.value || this.minamount);
      this.maxamount = parseInt(
        this.$rcattr("rc-maxamount") || RockCommerce.config.maxamount
      );
    },

    get addToCartUrl() {
      return "tbd";
    },

    initAmountInput() {
      this.amountInput = this.$el.querySelector("[x-model=amount]");
      if (!this.amountInput) return;
      this.amountInput.addEventListener("focus", (e) => {
        e.target.select();
      });
    },

    minus() {
      if (this.amount - 1 < this.minamount)
        return (this.amount = this.minamount);
      if (this.amount - 1 > this.maxamount)
        return (this.amount = this.maxamount);
      this.amount--;
    },

    plus() {
      if (this.amount + 1 > this.maxamount)
        return (this.amount = this.maxamount);
      if (this.amount + 1 < this.minamount)
        return (this.amount = this.minamount);
      this.amount++;
    },

    /**
     * Get the calculated dynamic price
     * This is dependent on the amount and on tax etc.;
     */
    get price() {
      let money = this.money;
      if (money === null) return;

      // calculate the total price
      // let price = RockCommerce.trigger("ProductPriceInit", null, () => {
      //   return this.price;
      // });

      // add prices of variations
      // this.variations().forEach((variation) => {
      //   if (typeof variation == "undefined") return;
      //   variation.options(true).forEach((option) => {
      //     price = price.plus(option.price.float());
      //   });
      // });

      // add amount
      money = RockCommerce.trigger("rcproduct:priceTotal", money, (money) => {
        return money.times(this.amount);
      });

      return money.format();
    },

    get variationString() {
      return this.$rctype + "-" + this.$root.getAttribute("rc-cid");
      // return "varStr " + this.$component.$type;
    },
  }));
});
