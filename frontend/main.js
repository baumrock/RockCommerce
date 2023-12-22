"use strict";

/**
 * Hook the htmx after swap event to place markup on multiple
 * targets, which is not possible with regular htmx oob swaps
 */
document.body.addEventListener("htmx:after-swap", function (evt) {
  let el = evt.target;
  if (el.id != "rc-swap") return;

  // loop all childnodes and place them on target
  el.childNodes.forEach(function (child) {
    let selector = child.getAttribute("rc-swap");
    if (!selector) return;

    let targets = document.querySelectorAll("[rc-swap='" + selector + "']");
    targets.forEach((oldEl) => {
      if (oldEl === child) return;
      console.log(oldEl);
      oldEl.replaceWith(child.cloneNode(true));
    });
  });

  // Remove the original el from the DOM
  el.remove();
});
