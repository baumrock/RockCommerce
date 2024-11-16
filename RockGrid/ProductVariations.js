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
      defs = grid.addAfter(defs, "option", {
        field: "price",
        title: "price",
        editor: "calc",
        width: 120,
        editorParams: {
          vars: {
            p: "#Inputfield_rockcommerce_net",
          },
        },
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
        },
        hozAlign: "right",
      });
      defs = grid.addAfter(defs, "price", {
        field: "total",
        title: "total",
        editor: "calc",
        width: 120,
        formatter: "money",
        formatterParams: {
          decimal: ",",
          thousand: ".",
        },
        hozAlign: "right",
      });
      defs = grid.addAfter(defs, "total", {
        field: "addNumbers",
        title: "",
        width: 40,
        headerSort: false,
        hozAlign: "center",
        formatter: function (cell) {
          return (
            '<a href class="rc-add-image-numbers">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="m4 15l3 3l3-3M7 6v12M17 3a2 2 0 0 1 2 2v3a2 2 0 1 1-4 0V5a2 2 0 0 1 2-2zm-2 13a2 2 0 1 0 4 0a2 2 0 1 0-4 0"/><path d="M19 16v3a2 2 0 0 1-2 2h-1.5"/></g></svg>' +
            "</a>"
          );
        },
      });
      defs = grid.addAfter(defs, "addNumbers", {
        field: "image",
        title: "image",
        // using input makes it possible to navigate via up and down keys
        editor: "number",
        editorParams: {
          verticalNavigation: "table",
        },
        width: 100,
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
          col.width = 50;
          col.hozAlign = "center";
          col.formatter = (cell) => {
            let value = cell.getValue();
            if (!value) return;
            return (
              "<i class='fa fa-comment-o' title='" + value + "' uk-tooltip></i>"
            );
          };
        }
      });

      return defs;
    },

    // pagination
    pagination: true,
    paginationSize: 20,
    // selectableRows: true,
  });

  // ### custom events ###

  // image numbers
  const sleepImages = () => {
    const numbers = {};
    table.getSelectedRows().forEach((row) => {
      const img = row.getData().image;
      if (!img) return;
      numbers[row.getData().id] = img;
    });
    grid.sleep("images", numbers);
  };
  table.on("cellClick", (e, cell) => {
    const a = e.target.closest(".rc-add-image-numbers");
    if (!a) return;
    e.preventDefault();
    // set all image cells above this to ""
    // set all image cells below this to 1++
    // Get all rows in the table
    let rows = table.getRows();

    // Find the index of the clicked row
    let clickedIndex = rows.findIndex((row) => row === cell.getRow());

    // reset image column
    for (let i = 0; i < rows.length; i++) {
      table.updateRow(rows[i].getData().id, { image: "" });
    }

    // set image numbers for all selected rows
    let imageNumber = 1;
    for (let i = clickedIndex; i < rows.length; i++) {
      if (!rows[i].isSelected()) continue;
      table.updateRow(rows[i].getData().id, { image: imageNumber++ });
    }

    // build sleep object for storing image numbers
    sleepImages();
  });
  table.on("cellEdited", (cell) => {
    if (cell.getField() !== "image") return;
    sleepImages();
  });

  // add filter to show only one single variation
  table.on("rowSelected", (row) => {
    let id = row.getData().varID;
    // table.clearFilter(true);
    table.setFilter("varID", "=", id);
  });
  table.on("rowSelectionChanged", () => {
    table.redraw(true);
    if (table.getSelectedRows().length) return;
    table.clearFilter(true);
    table.redraw(true);
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

  let updatePrices = (row) => {
    let cell = row.getCell("price");
    let val = cell.getValue();
    if (typeof val === "undefined") return;
    let prices = grid.sleepValue.prices || {};
    if (val !== "") prices[cell.getRow().getData().id] = val;
    grid.sleep("prices", prices);
  };

  // ### wakeup
  table.on("dataProcessed", () => {
    // prices
    let prices = grid.sleepValue.prices || {};
    let basePrice = 0;
    let priceEl = document.getElementById("Inputfield_rockcommerce_net");
    if (priceEl) basePrice = currency(priceEl.value);
    for (let [id, price] of Object.entries(prices)) {
      table.updateRow(id, {
        price: price,
        total: basePrice.add(price),
      });
    }

    // image numbers
    let images = grid.sleepValue.images || {};
    for (let [id, imageNumber] of Object.entries(images)) {
      table.updateRow(id, {
        image: imageNumber,
      });
    }
  });
  // ### END wakeup

  // ### calc/watch total input
  table.on("cellEdited", (cell) => {
    let basePrice = 0;
    let priceEl = document.getElementById("Inputfield_rockcommerce_net");
    if (priceEl) basePrice = currency(priceEl.value);
    let val = cell.getValue();
    let data = cell.getRow().getData();
    if (cell.getField() === "price") {
      // price has been updated --> calc total
      let total = basePrice.add(val);
      table.updateRow(data.id, { total: total.value });
    } else if (cell.getField() === "total") {
      // total has been updated --> calc price
      let price = currency(val).subtract(basePrice);
      table.updateRow(data.id, { price: price.value });
      updatePrices(cell.getRow());
    }
  });
  // ### END calc/watch total input

  table.on("cellEdited", (cell) => {
    updatePrices(cell.getRow());
  });
});
