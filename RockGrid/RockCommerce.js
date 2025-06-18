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
        } else if (name == "vat") {
          col.title = "VAT";
          RockGrid.setColDef(col, "money");
          col.width = 100;
        } else if (name == "total") {
          col.title = "Total";
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
    rows = filterRows(rows);

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

  // chart
  var chart = document.getElementById("chart");
  var myChart = echarts.init(chart);

  var today = new Date();
  var formattedToday =
    today.getFullYear() +
    "-" +
    (today.getMonth() + 1).toString().padStart(2, "0") +
    "-" +
    today.getDate().toString().padStart(2, "0");
  var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

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
      // min: firstDayOfMonth,
      // max: formattedToday,
    },
    yAxis: [
      {
        type: "value",
        name: "Sales",
      },
      {
        type: "value",
        name: "Net Revenue",
        alignTicks: true,
        axisLabel: {
          formatter: "{value}€",
        },
      },
    ],
  };

  // cumulate data function
  let cumulate = function (values) {
    const dataArray = Object.entries(values);

    // sort by date
    dataArray.sort((a, b) => new Date(a[0]) - new Date(b[0]));

    // now that the data is sorted we can return early
    // if the cumulate checkbox is not checked
    if (!$("#cumulate").is(":checked")) return dataArray;

    // cumulate data
    let cumulativeSum = 0;
    const cumulativeData = dataArray.map(([date, value]) => {
      cumulativeSum += value;
      return [date, cumulativeSum];
    });

    return cumulativeData;
  };

  let getRows = function (table) {
    let rows;
    if ($("#selected").is(":checked")) rows = table.getSelectedRows();
    else rows = table.getRows();
    return filterRows(rows);
  };

  let filterRows = function (rows) {
    return rows.filter((row) => {
      let data = row.getData();
      return data.status === "paid" || data.status === "open";
    });
  };

  // draw chart function
  let drawChart = function (rows) {
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
        data: cumulate(sales), // Object.entries(sales),
        barGap: "0%",
        barCategoryGap: "20%",
      },
      {
        name: "Net Revenue",
        type: "bar",
        yAxisIndex: 1,
        data: cumulate(revenue),
      },
    ];
    myChart.setOption(options);
  };

  // trigger the filtered event whenever the checkbox changes
  $("#cumulate").change(function () {
    drawChart(getRows(table));
  });

  $("#selected").change(function () {
    drawChart(getRows(table));
  });

  // listen to table filtered event
  table.on("dataFiltered", (filters, rows) => {
    drawChart(filterRows(rows));
  });

  table.on("rowSelectionChanged", (filters, rows) => {
    if (!$("#selected").is(":checked")) return;
    drawChart(filterRows(rows));
  });

  // resize chart on window resize
  window.addEventListener("resize", function () {
    myChart.resize();
  });
});
