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

(() => {
  let alpineLoaded = false;
  document.addEventListener("alpine:init", () => (alpineLoaded = true));

  setTimeout(() => {
    if (!alpineLoaded) {
      alert(
        "Alpine.js not loaded - RockCommerce needs Alpine.js to work on the frontend of your site! Please see the quickstart guide in the docs!"
      );
    }
  }, 2000);
})();
