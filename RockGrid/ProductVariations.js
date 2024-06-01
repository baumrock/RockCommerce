document.addEventListener("RockGrid:init", (e) => {
  let grid = e.detail;

  // do everything below only for this specific grid
  if (grid.name !== "RockCommerce-ProductVariations") return;

  // initialize table
  let table = grid.tabulator({
    // ajax
    ajaxURL: grid.ajaxURL,
    ajaxConfig: {
      method: "GET", //set request type to Position
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Content-type": "application/json; charset=utf-8",
      },
    },

    layout: "fitData",
    autoColumns: true, //create columns from data field names
    autoColumnsDefinitions: function (defs) {
      defs.push({
        field: "price",
        title: "price",
        editor: "calc",
        width: 120,
        editorParams: {
          vars: {
            p: "#Inputfield_rc_product_price",
          },
        },
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
        },
        hozAlign: "right",
      });

      defs.forEach((col, i) => {
        if (col.field == "id" || col.field == "varID") {
          col.visible = false;
        } else if (col.field == "variation") {
          col.headerFilter = true;
          col.formatter = "modal";
          col.formatterParams = {
            icon: true,
            tooltip: "bearbeiten",
            field: "varID",
          };
        } else if (col.field == "option") {
          col.headerFilter = true;
        } else if (col.field == "comment") {
          col.title = "";
          col.headerSort = false;
        }
      });

      return defs;
    },

    // pagination
    pagination: true,
    paginationSize: 10,
    // selectableRows: true,
  });

  // ##### custom events #####

  // add filter to show only one single variation
  table.on("rowSelected", (row) => {
    let id = row.getData().varID;
    // table.clearFilter(true);
    table.setFilter("varID", "=", id);
  });
  table.on("rowSelectionChanged", () => {
    if (table.getSelectedRows().length) return;
    table.clearFilter(true);
  });

  // select/unselect all on click in variation row
  table.on("cellClick", (e, cell) => {
    let field = cell.getField();

    if (field === "option") {
      cell.getRow().toggleSelect();
    } else if (field === "variation") {
      let varID = cell.getRow().getData().varID;
      let selected = cell.getRow().isSelected();
      let rows = table.getRows().filter((row) => row.getData().varID === varID);
      rows.forEach((row) => {
        if (selected) table.deselectRow(row);
        else table.selectRow(row);
      });
    }
  });

  // ##### price input
  table.on("cellEdited", (cell) => {
    if (cell.getField() !== "price") return;

    let val = cell.getValue();
    if (typeof val === "undefined") return;

    let prices = grid.sleepValue.prices || {};
    if (val !== "") prices[cell.getRow().getData().id] = val;
    grid.sleep("prices", prices);
  });
  table.on("dataProcessed", () => {
    let prices = grid.sleepValue.prices || {};
    for (let [id, price] of Object.entries(prices)) {
      table.updateRow(id, { price: price });
    }
  });
  // ##### END price input
});
