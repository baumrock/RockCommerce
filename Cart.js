"use strict";

/**
 * This file is loaded on RockCommerce Cart pages
 */

function _Cart() {}

_Cart.prototype.minus = function (el) {
  let parent = el.parentNode;
  let input = parent.querySelector("input");
  let newVal = input.value * 1 - 1;
  if (newVal < 0) newVal = 0;
  if (newVal < 2) {
    parent.querySelector(".trash").removeAttribute("hidden");
    parent.querySelector(".minus").setAttribute("hidden", true);
  }
  input.value = newVal;
  htmx.trigger(input, "keyup");
};

_Cart.prototype.plus = function (el) {
  let parent = el.parentNode;
  let input = parent.querySelector("input");
  input.value = input.value * 1 + 1;
  if (input.value > 1) {
    parent.querySelector(".trash").setAttribute("hidden", true);
    parent.querySelector(".minus").removeAttribute("hidden");
  }
  htmx.trigger(input, "keyup");
};

var Cart = new _Cart();
