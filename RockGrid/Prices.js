$(document).on("RockGrid", function () {
  let rg = RockGrid;

  rg.add("Prices", {
    dataTree: true,
    dataTreeStartExpanded: true,
    dataTreeElementColumn: "title",
    dataTreeFilter: false,
    paginationSize: 15,
    selectable: false,
    autoColumnsDefinitions: function (definitions) {
      definitions.forEach((col) => {
        let name = col.field;
        if (name == "id") {
          col.width = 50;
          col.hozAlign = "center";
          col.mutator = function (val) {
            let isNum = !isNaN(Number(val));
            if (!isNum) return false;
            return val;
          };
          col.formatter = function (cell) {
            let id = cell.getValue();
            if (!id) return;
            return rg.link({
              icon: "edit",
              href: rg.editUrl(id),
              tooltip: "Edit this page",
              panel: true,
            });
          };
        } else if (name == "available") {
          col.title = "";
          col.width = 50;
          col.editor = "tickCross";
          col.editable = function (cell) {
            return !cell.getRow().getData().variation;
          };
          col.editorParams = {
            trueValue: 1,
            falseValue: 0,
          };
          col.formatter = (cell) => {
            if (cell.getRow().getData().variation) return;
            if (cell.getValue()) return "✅";
            return "❌";
          };
          col.hozAlign = "center";
        } else if (name == "net" || name == "gross") {
          col.formatter = "money";
          col.width = 100;
          col.hozAlign = "right";
          col.editor = "number";
          col.headerFilter = "input";
          col.editorParams = {
            selectContents: true,
            verticalNavigation: "table",
            step: 0.01,
          };
        } else if (name == "taxrate") {
          col.width = 90;
          col.hozAlign = "center";
          col.editor = "number";
          col.headerFilter = "input";
          col.editorParams = {
            selectContents: true,
            verticalNavigation: "table",
            step: 0.01,
          };
          col.formatter = (cell) => {
            let val = cell.getValue();
            if (val != "") return cell.getValue() + "%";
          };
        } else if (name == "pricing") {
          col.visible = false;
        } else if (name == "_children") {
          col.visible = false;
        } else if (name == "key") {
          col.visible = false;
        }
      });
      return definitions;
    },
  });
});

// attach event listeners
$(document).on("rockgrid-init", (e, grid) => {
  if (grid.name !== "Prices") return;
  let table = grid.table;

  let key = "down";
  $(document).on("click", function (e) {
    key = "down";
  });
  $(document).on("keydown", function (e) {
    if (e.key == "ArrowUp") key = "up";
    else key = "down";
  });

  function PriceEditor() {
    this.isUpdating = false;
    this.editCell = false;
    this.editValue = false;
  }

  /**
   * Find next editable taxrate (skip variations)
   */
  PriceEditor.prototype.editNextTaxrate = function (cell) {
    let data = cell.getData();
    if (!data.variation) return;
    if (key == "down") cell.navigateDown();
    else if (key == "up") cell.navigateUp();
  };

  /**
   * Find next editable price
   */
  PriceEditor.prototype.editNextPrice = function (cell) {
    let data = cell.getData();
    if (data.variation) return;
    if (!cell.getData()._children) return;
    if (key == "down") cell.navigateDown();
    else if (key == "up") cell.navigateUp();
  };

  var Editor = new PriceEditor();

  /**
   * Send new data via AJAX
   */
  table.on("cellEdited", function (cell) {
    let data = cell.getRow().getData();
    let field = cell.getColumn().getField();

    // no change, no edit
    if (Editor.editValue * 1 == cell.getValue() * 1) return;

    Editor.isUpdating = true;
    $("#loading").fadeIn();

    if (field == "taxrate") {
      $.ajax({
        type: "POST",
        dataType: "json",
        url: "/rockcommerce/update-taxrate",
        data: {
          id: data.id,
          taxrate: data.taxrate,
        },
        success: (data) => {
          table.updateData([data]);
          Editor.isUpdating = false;
          $("#loading").fadeOut();
          Editor.editCell.edit();
        },
      });
    } else if (field == "available") {
      $.ajax({
        type: "POST",
        dataType: "json",
        url: "/rockcommerce/update-available",
        data: {
          id: data.id,
          available: data.available,
        },
        success: (data) => {
          table.updateData([data]);
          Editor.isUpdating = false;
          $("#loading").fadeOut();
        },
      });
    } else {
      Editor.editId = data.id;
      Editor.editVariation = data.variation;

      $.ajax({
        type: "POST",
        dataType: "json",
        url: "/rockcommerce/update-price",
        data: {
          id: data.id || data.pageid,
          net: data.net,
          gross: data.gross,
          variation: data.variation,
          field,
        },
        success: (data) => {
          table.updateData([data]);
          Editor.isUpdating = false;
          $("#loading").fadeOut();
          Editor.editCell.edit();
        },
      });
    }
  });

  /**
   * Skip non-editable cells
   */
  table.on("cellEditing", function (cell) {
    // trigger cellEditing after the keydown has been recognized
    setTimeout(() => {
      let field = cell.getField();

      // save edited cell to storage for later use
      Editor.editCell = cell;
      Editor.editValue = cell.getValue();

      // skip non-editable rows
      if (field == "available") return;
      if (field == "taxrate") Editor.editNextTaxrate(cell);
      else Editor.editNextPrice(cell);

      // cancel edit if ajax is active
      // this needs to happen after non-editable rows have been skipped
      if (Editor.isUpdating) {
        cell.cancelEdit();
        return;
      }
    }, 0);
  });

  // show rockcalculator on cmd+click
  // table.on("cellEditing", function (cell) {
  //   if (typeof RockCalculator == "undefined") return;
  //   if (!RockCalculator.metaClick) return;
  //   RockCalculator.show(false, cell);
  //   // TODO: trigger update of cell value
  // });
});
