document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;
  var table = new Tabulator(grid.div, {
    layout: "fitDataStretch",
    ajaxURL: grid.ajaxURL,
    autoColumns: true,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        col.headerFilter = true;
        if (col.field == "id") col.visible = false;
        if (col.field == "varid") col.visible = false;
      });
      return definitions;
    },

    // row selection + tickboxes
    selectableRows: true,
    rowHeader: {
      headerSort: false,
      resizable: false,
      frozen: true,
      headerHozAlign: "center",
      hozAlign: "center",
      formatter: "rowSelection",
      titleFormatter: "rowSelection",
      titleFormatterParams: {
        rowRange: "active", //only toggle the values of the active filtered rows
      },
    },

    // pagination
    pagination: true,
    paginationSize: 10,
  });

  // monitor selection changes
  // if only one row is selected then use the varid of that row and filter all
  // other rows so that only rows with the same varid are shown in the table
  // if no row is selected reset the filter and show all data again
  table.on("rowSelectionChanged", function (data, rows) {
    if (rows.length > 0) {
      let selectedVarid = rows[0].getData().varid;
      table.setFilter("varid", "=", selectedVarid);
    } else {
      table.clearFilter();
    }
  });
});
