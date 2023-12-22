$(document).on("RockGrid", function () {
  let rg = RockGrid;

  rg.add("Cartitems", {
    paginationSize: 50,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        let name = col.field;

        // example how to use the link() helper
        if (name == "id") {
          col.width = 50;
          col.hozAlign = "center";
          col.formatter = function (cell) {
            let id = cell.getValue();
            return rg.link({
              icon: "edit",
              href: rg.editUrl(id),
              tooltip: "Edit this page",
              panel: true,
            });
          };
        }
        if (name == "title") {
          rg.setColDef(col, "edit");
        }
      });
      return definitions;
    },
  });
});
