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
        } else if (name == "title") {
          rg.setColDef(col, "edit");
        } else if (name == "itemID") {
          col.visible = false;
        } else if (name == "item") {
          col.formatter = function (cell) {
            let title = cell.getValue();
            let id = cell.getRow().getData().itemID;
            return (
              '<a href="/rockgrid/view/' +
              id +
              '" target=_blank>' +
              title +
              "</a>"
            );
          };
        }
      });
      return definitions;
    },
  });
});
