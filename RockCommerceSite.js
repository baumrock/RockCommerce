"use strict";

/**
 * RockCommerce Frontend Site JS
 * This file is only loaded on project-specific fontend pages.
 * That are pages not like /rc/cart, but for example yoursite.com/product1
 */

/**
 * Magic Elements
 * This will automatically populate dom elements having a special
 * class with the related value stored in the localstorage.
 */
document.addEventListener("DOMContentLoaded", function () {
  var magicelements = ["rc-cart-count"];
  for (var i = 0; i < magicelements.length; i++) {
    var cls = magicelements[i];
    var val = localStorage.getItem(cls);
    var elements = document.getElementsByClassName(cls);
    for (var i = 0; i < elements.length; i++) {
      elements[i].textContent = val;
      elements[i].removeAttribute("hidden");
    }
  }
});
