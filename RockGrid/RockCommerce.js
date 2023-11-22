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
        } else if (name == "net") {
          col.title = "Net";
          RockGrid.setColDef(col, "money");
          col.width = 100;
        } else if (name == "status") {
          col.title = "Status";
          col.width = 100;
          col.hozAlign = "center";
          col.formatter = (cell) => {
            let val = cell.getValue();
            return "<span class='badge " + val + "'>" + val + "</span>";
          };
        } else if (name == "recipient") {
          col.title = "Recipient";
        } else if (name == "invoice") {
          col.title = "Invoice";
          col.width = 100;
        } else if (name == "date") {
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
        } else if (name == "country") {
          col.title = "Country";
          col.width = 100;
          col.hozAlign = "center";
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
      let net = row.getData().net * 1;
      sum += net;
    });
    $("#net-rev").text(rg.money(sum, "de-DE", "EUR"));

    // calc num of orders
    $("#num-orders").text(rows.length);

    // calc avg net revenue per order
    $("#avg-net").text(rows.length ? rg.money(sum / rows.length) : 0);
  });
});

// chart
$(document).on("rockgrid-init", (e, grid) => {
  if (grid.name !== "RockCommerce") return;
  let table = grid.table;
  var chart = document.getElementById("chart");
  var myChart = echarts.init(chart);

  // prepare options
  let options = {
    title: {
      // text: 'ECharts Sample'
    },
    tooltip: {
      trigger: "axis",
    },
    legend: {
      data: ["Sales", "Net Revenue"],
    },
    xAxis: {
      type: "time",
    },
    yAxis: [
      {
        type: "value",
        name: "Sales",
      },
      {
        type: "value",
        name: "Net Revenue",
      },
    ],
  };

  // listen to table filtered event
  table.on("dataFiltered", (filters, rows) => {
    let sales = {};
    let revenue = {};

    // calc total net revenue
    $.each(rows, (i, row) => {
      let data = row.getData();
      let date = data.date;
      let day = date.split(" ")[0];
      sales[day] = (sales[day] || 0) + 1;
      revenue[day] = (revenue[day] || 0) + data.net;
    });

    // update chart
    options.series = [
      {
        name: "Sales",
        type: "bar",
        data: Object.entries(sales),
        barGap: "0%",
        barCategoryGap: "20%",
      },
      {
        name: "Net Revenue",
        type: "bar",
        yAxisIndex: 1,
        data: Object.entries(revenue),
      },
    ];
    myChart.setOption(options);
  });
});
