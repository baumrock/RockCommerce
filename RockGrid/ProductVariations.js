document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;
  if (grid.hash !== "RockCommerce-ProductVariations") return;

  // initialize table
  var table = new Tabulator(grid.div, {
    // ajax
    ajaxURL: grid.ajaxURL,
    ajaxConfig: {
      method: "GET", //set request type to Position
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-type": "application/json; charset=utf-8",
      },
    },

    layout: "fitDataFill",
    autoColumns: true, //create columns from data field names
    autoColumnsDefinitions: function (definitions) {
      //definitions - array of column definition objects

      definitions.forEach((col) => {
        // global settings
        col.headerFilter = true;

        // custom settings
        if (col.field == "id" || col.field == "var:id") {
          col.visible = false;
        } else if (col.field == "name") {
          col.headerFilter = true; // add header filter to every column
        }
      });

      return definitions;
    },

    // pagination
    pagination: true,
    paginationSize: 10,

    // enable row selection
    selectableRows: true,
  });

  table.on("tableBuilt", () => grid.tableBuilt(table));
});
