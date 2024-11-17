document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;

  // do everything below only for this specific grid
  if (grid.name !== "RockCommerce-ProductPricing") return;

  // build the tabulator table
  var table = grid.tabulator({
    layout: "fitDataStretch",
    ajaxURL: grid.ajaxURL,
    autoColumns: true,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        if (col.field == "foo") {
          col.visible = false;
        }
      });
      return definitions;
    },

    selectableRows: true,
  });
});
