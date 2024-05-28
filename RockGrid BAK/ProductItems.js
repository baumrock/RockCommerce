document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;
  if (grid.hash !== "RockCommerce-ProductItems") return;

  var table = new Tabulator(grid.div, {
    layout: "fitDataStretch",
    ajaxURL: grid.ajaxURL,

    pagination: true,
    paginationSize: 10,

    autoColumns: true,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        if (col.field == "foo") {
          col.visible = false;
        }
      });
      return definitions;
    },
  });

  table.on("tableBuilt", () => grid.tableBuilt(table));
});
