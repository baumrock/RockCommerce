"use strict";

/**
 * RockCommerce Frontend Site JS
 * This file is only loaded on project-specific fontend pages.
 * That are pages not like /rc/cart, but for example yoursite.com/product1
 */

var RockCommerceSite;

(() => {
  function RCSite() {
    this.cartCount = 0;
  }

  RCSite.prototype.init = function () {
    this.cartCount = localStorage.getItem("rc-cart-count");
    this.setMagicElements();
  };

  RCSite.prototype.setMagicElements = function () {
    var magicelements = {
      "cart-count": this.cartCount,
    };
    for (var prop in magicelements) {
      var elements = document.querySelectorAll("[data-" + prop + "]");
      for (var i = 0; i < elements.length; i++) {
        elements[i].textContent = magicelements[prop];
        elements[i].removeAttribute("hidden");
      }
    }
  };

  RockCommerceSite = new RCSite();
  RockCommerceSite.init();
})();
