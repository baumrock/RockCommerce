$(document).on("RockGrid", function () {
  let rg = RockGrid;

  rg.add("RockCommerce", {
    paginationSize: 15,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        let name = col.field;

        // example how to use the link() helper
        if (name == "id") {
          col.visible = false;
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
          col.title = "Order Subject";
        } else if (name == "rockcommerce_order_net") {
          col.title = "Net";
          RockGrid.setColDef(col, "money");
          col.width = 100;
        } else if (name == "rockcommerce_order_paymentstatus") {
          col.title = "Status";
          col.width = 100;
          col.hozAlign = "center";
        } else if (name == "Invoice") {
          col.width = 100;
        } else if (name == "rockcommerce_order_invoice") {
          col.formatter = (cell) => {
            console.log(cell.getValue());
          };
        } else if (name == "created") {
          col.title = "Date";
          col.hozAlign = "right";
          col.width = 180;
          col.formatter = (cell) => {
            return (
              "<span style='font-variant-numeric: tabular-nums;'>" +
              cell.getValue() +
              "</span>"
            );
          };
        }
      });
      return definitions;
    },
  });
});

// attach event listeners
$(document).on("rockgrid-init", (e, grid) => {
  if (grid.name !== "RockCommerce") return;
  let rg = RockGrid;
  let table = grid.table;

  // calc statistics
  table.on("dataFiltered", (filters, rows) => {
    let sum = 0;

    // calc total net revenue
    $.each(rows, (i, row) => {
      let net = row.getData().rockcommerce_order_net * 1;
      console.log(net);
      sum += net;
    });
    $("#net-rev").text(rg.money(sum, "de-DE", "EUR"));

    // calc num of orders
    $("#num-orders").text(rows.length);

    // calc avg net revenue per order
    $("#avg-net").text(rows.length ? rg.money(sum / rows.length) : 0);
  });
});
