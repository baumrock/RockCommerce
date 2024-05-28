document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;
  var table = new Tabulator(grid.div, {
    ajaxURL: grid.ajaxURL,
    ajaxParams: function () {
      return { var: 2176 };
    },
    autoColumns: true, //create columns from data field names
    autoColumnsDefinitions: function (definitions) {
      //definitions - array of column definition objects

      definitions.forEach((col) => {
        if (col.field == "id" || col.field == "var:id") {
          col.visible = false;
        }
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
    },
  });
});
