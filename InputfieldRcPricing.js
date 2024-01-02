"use strict";

// temporary hack to focus shop tab for development
// $(document).ready(() => {
//   $("#_Inputfield_rc_product_rctab").click();
// });

function _RcPricing() {
  this.root;
  this.textarea;
  this.table;
}

_RcPricing.prototype.getArray = function () {
  let arr = {};
  this.table.getData().forEach((row) => {
    delete row["id"];
    arr[RcPricing.getKey(row)] = row.net;
  });
  return arr;
};

/**
 * Get prices array-key from row object
 */
_RcPricing.prototype.getKey = function (row) {
  let key = "";
  let sep = "";
  for (let col in row) {
    if (col == "net") continue;
    key += sep + col + ":" + row[col];
    sep = "|";
  }
  return key;
};

_RcPricing.prototype.getPrices = function () {
  return JSON.parse(this.textarea.value || "[]");
};

_RcPricing.prototype.init = function (id, variations, data) {
  this.root = document.querySelector(id).closest(".Inputfield");
  this.textarea = this.root.querySelector("textarea.FieldtypeTextarea");

  // setup columns
  let columns = [];
  for (let prop in variations) {
    columns.push({
      field: prop,
      title: prop,
      headerFilter: true,
    });
  }
  columns.push({
    field: "net",
    title: "Net Price",
    editor: "number",
    headerFilter: "input",
    editorParams: {
      selectContents: true,
      verticalNavigation: "table",
      step: 0.01,
    },
  });

  // create Tabulator
  this.table = new Tabulator(id, {
    data: this.setPrices(data),
    layout: "fitColumns", //fit columns to width of table (optional)
    columns: columns,
  });

  /** ##### events ##### */

  // save data when cell has been edited
  this.table.on("cellEdited", function (cell) {
    RcPricing.saveJson();
  });

  // update prices on button click
  let setPrices = (e) => {
    e.preventDefault();
    let price = $("#set-prices input").val();
    let table = this.table;
    table.getData("active").forEach((row) => {
      row.net = price;
      table.updateData([row]);
    });
    RcPricing.saveJson();
  };
  $(document).on("keyup", "#set-prices input", setPrices);
};

_RcPricing.prototype.saveJson = function () {
  this.textarea.value = JSON.stringify(this.getArray());
};

_RcPricing.prototype.setPrices = function (data) {
  let prices = this.getPrices();
  let notFound = Object.assign({}, prices);
  data.forEach((row) => {
    row.id = RcPricing.getKey(row);
    let price = prices[row.id];
    if (price !== undefined) delete notFound[row.id];
    row.net = price;
  });

  // populate not found warning
  for (let prop in notFound) {
    $("#notFound").attr("hidden", false);
    $("#notFoundWarning").attr("hidden", false);
    $("#notFound > div").append(
      "<div>" + prop.replace("|", " | ") + " = " + notFound[prop] + "</div>"
    );
  }

  return data;
};

var RcPricing = new _RcPricing();
