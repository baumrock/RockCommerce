"use strict";

/**
 * RockCommerce Frontend Site JS
 *
 * This file is only loaded on project-specific fontend pages.
 * That are pages not like /rc/cart, but for example yoursite.com/product1
 *
 * The
 */

var RockCommerceSite;

(() => {
  function RCSite() {
    this.locale = "de-DE";
    this.currency = "EUR";
    this.priceType = "net";
    this.cartCount = 0;
    this.debug = false;
  }

  /** ########## Init ########## */

  /**
   * This method is triggerd from tag injected on Page::render
   */
  RCSite.prototype.init = function (config) {
    for (let prop in config) {
      this[prop] = config[prop];
    }
    this.cartCount = localStorage.getItem("rc-cart-count");
    this.updateMagicElements();
    console.log(this);
  };

  /** ########## Methods ########## */

  RCSite.prototype.getPricing = function (el) {
    el = el.closest("[rc-pricing]");
    return JSON.parse(el.getAttribute("rc-pricing"));
  };

  /**
   * Get variation key like "size:s|color:red"
   */
  RCSite.prototype.getSelectedVariationsKey = function (el) {
    var variations = el.querySelectorAll("[rc-variation]");
    var variationKey = "";
    variations.forEach(function (variation) {
      var name = variation.getAttribute("name");
      var value = variation.value;
      variationKey += name + ":" + value + "|";
    });
    return variationKey.slice(0, -1); // remove the last "|"
  };

  /**
   * Get selected accessories
   * Returns an array of ids of accessories, eg ["123", "456"]
   */
  RCSite.prototype.getSelectedAccessories = function (el) {
    var accessories = el.querySelectorAll("[rc-accessory]:checked");
    return Array.from(accessories).map(function (accessory) {
      return accessory.getAttribute("rc-accessory");
    });
  };

  /**
   * Money formatter for the frontend
   */
  RCSite.prototype.money = function (number) {
    return new Intl.NumberFormat(this.locale, {
      currency: this.currency,
      style: "currency",
    }).format(number);
  };

  RCSite.prototype.updateMagicElements = function () {
    var magicelements = {
      "rc-cart-count": this.cartCount,
    };
    for (var prop in magicelements) {
      var elements = document.querySelectorAll("[" + prop + "]");
      for (var i = 0; i < elements.length; i++) {
        elements[i].textContent = magicelements[prop];
        elements[i].removeAttribute("hidden");
      }
    }
  };

  /**
   * Update price of add-to-cart whenever a variation was changed
   */
  RCSite.prototype.updatePrice = function (el) {
    let root = el.closest("[rc-pricing]");
    let pricing = this.getPricing(root);
    let price = root.querySelector("[rc-price]");

    // get variations price
    let varKey = this.getSelectedVariationsKey(root);
    let varPrice = pricing.variations[varKey];
    let total = varPrice.net * 1;

    // add accessory prices
    let accessories = this.getSelectedAccessories(root);
    accessories.forEach(function (id) {
      let accPrice = pricing.accessories[id];
      console.log(accPrice);
      total += accPrice.net * 1;
    });

    price.textContent = this.money(total);
  };

  RockCommerceSite = new RCSite();

  /** ########## Event Listeners ########## */

  /**
   * Update price whenever a variation or accessory was changed
   */
  document.addEventListener("change", function (e) {
    let el = e.target;
    if (el.closest("[rc-variation]") || el.closest("[rc-accessory]")) {
      RockCommerceSite.updatePrice(el);
    }
  });
})();
