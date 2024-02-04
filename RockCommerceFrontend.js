"use strict";

/**
 * RockCommerce Frontend JS
 * This file is only loaded on frontend facing pages under /rc
 */

/**
 * Hook the htmx after swap event to place markup on multiple
 * targets, which is not possible with regular htmx oob swaps
 */
document.body.addEventListener("htmx:after-swap", function (evt) {
  let el = evt.target;
  if (el.id != "rc-swap") return;

  // loop all childnodes and place them on target
  el.childNodes.forEach(function (child) {
    let targets;
    let selector = child.getAttribute("rc-swap");
    if (!selector) {
      // no selector specified
      // if we also have no id we exit
      if (!child.id) {
        console.error(
          "Swap-Element must have either rc-swap attribute or an id!",
          child
        );
        return;
      }

      // we have an id so we replace this element
      targets = document.querySelectorAll("#" + child.id);
    } else {
      // we have a selector to replace multiple targets
      targets = document.querySelectorAll("[rc-swap='" + selector + "']");
    }

    // replace all targets
    targets.forEach((oldEl) => {
      if (oldEl === child) return;
      oldEl.replaceWith(child.cloneNode(true));
    });
  });

  // Remove the original el from the DOM
  el.remove();
});
