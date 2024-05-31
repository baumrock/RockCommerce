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

    layout: "fitDataStretch",
    autoColumns: true, //create columns from data field names
    autoColumnsDefinitions: function (definitions) {
      //definitions - array of column definition objects

      definitions.push({
        field: "pricechange",
        title: "pricechange",
        editor: "number",
        editorParams: {
          selectContents: true,
          verticalNavigation: "table",
        },
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
        },
      });
      definitions.push({
        field: "fullprice",
        title: "fullprice",
        editor: "number",
        editorParams: {
          selectContents: true,
          verticalNavigation: "table",
        },
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
        },
      });

      let sort = ["Variation", "Option", "pricechange", "fullprice", "Comment"];
      // Sort definitions based on the sort array
      definitions.sort((a, b) => {
        let indexA = sort.indexOf(a.field);
        let indexB = sort.indexOf(b.field);
        if (indexA === -1) indexA = sort.length;
        if (indexB === -1) indexB = sort.length;
        return indexA - indexB;
      });

      definitions.forEach((col, i) => {
        if (col.field == "id" || col.field == "varID") {
          col.visible = false;
        } else if (col.field == "Variation") {
          col.headerFilter = true;
          col.formatter = "modal";
          col.formatterParams = {
            icon: true,
            tooltip: "bearbeiten",
            field: "varID",
          };
        } else if (col.field == "Option") {
          col.headerFilter = true;
        }
      });

      return definitions;
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

    if (field === "Option") {
      cell.getRow().toggleSelect();
    } else if (field === "Variation") {
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
    if (cell.getField() !== "pricechange") return;

    let val = cell.getValue();
    if (typeof val === "undefined") return;
    if (val !== "") {
      val = Math.round(val * 100) / 100;
      cell.setValue(val);
    }

    let pricechanges = grid.sleepValue.pricechanges || {};
    pricechanges[cell.getRow().getData().id] = val;
    grid.sleep("pricechanges", pricechanges);
  });
  table.on("dataProcessed", () => {
    let pricechanges = grid.sleepValue.pricechanges || {};
    for (let [id, price] of Object.entries(pricechanges)) {
      table.updateRow(id, { pricechange: price });
    }
  });
  // ##### END price input

  // ##### calc pricechange from fullprice
  table.on("cellEdited", (cell) => {
    if (cell.getField() !== "fullprice") return;

    let baseprice = document.getElementById(
      "Inputfield_rc_product_basepricenet"
    );
    if (!baseprice) return;

    let val = cell.getValue();
    if (typeof val === "undefined") return;
    if (val === "") return;

    val = Math.round(val * 100) / 100;
    cell.setValue(val);

    let basepriceValue = parseFloat(baseprice.value);
    if (!isNaN(basepriceValue)) {
      let newPriceChange = val - basepriceValue;
      let changeCell = cell.getRow().getCell("pricechange");
      changeCell.setValue(newPriceChange);
    }
  });
  // ##### END calc pricechange from fullprice
});
