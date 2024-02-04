"use strict";

/**
 * RockCommerce Backend File
 * Loaded on ProcessWire Backend Pages
 */

var RockCommerce;
(() => {
  function RC() {}

  RC.prototype.taxrate = function () {
    return document.querySelector("[name=rc_product_taxrate]").value;
  };

  RC.prototype.getGross = function (net) {
    return (net * (1 + this.taxrate() / 100)).toFixed(2);
  };

  RC.prototype.getNet = function (gross) {
    return (gross / (1 + this.taxrate() / 100)).toFixed(2);
  };

  RockCommerce = new RC();
})();

/** ########## Event Listeners ########## */

// update gross if net was changed
$(document).on(
  "keyup change paste",
  "[data-field-name=rc_product_net]",
  (e) => {
    let repeater = e.target.closest(".InputfieldRepeaterItem");
    let gross = repeater.querySelector("[data-field-name=rc_product_gross]");
    gross.value = RockCommerce.getGross(e.target.value);
  }
);

// update net if gross was changed
$(document).on(
  "keyup change paste",
  "[data-field-name=rc_product_gross]",
  (e) => {
    let repeater = e.target.closest(".InputfieldRepeaterItem");
    let net = repeater.querySelector("[data-field-name=rc_product_net]");
    net.value = RockCommerce.getNet(e.target.value);
  }
);
