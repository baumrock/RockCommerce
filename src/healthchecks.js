// check if script was loaded with defer
if (!document.currentScript?.defer) {
  console.warn("RockCommerce scripts should be loaded with defer attribute!");
}

// check if RockJavaScriptHooks are available
if (typeof ProcessWire.addHookAfter !== "function") {
  alert("Please load RockJavaScriptHooks before loading RockCommerce!");
}

// check if RockMoney is loaded
if (typeof RockMoney === "undefined") {
  alert("Please load RockMoney before loading RockCommerce!");
}
