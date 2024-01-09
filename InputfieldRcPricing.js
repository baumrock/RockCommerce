"use strict";

var RcPricing;

// temporary hack to focus shop tab for development
// $(document).ready(() => {
//   $("#_Inputfield_rc_product_rctab").click();
// });

(() => {
  function Pricing() {
    this.root;
    this.textarea;
    this.table;
  }

  Pricing.prototype.addPrices = function (data) {
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

  Pricing.prototype.getArray = function () {
    let arr = {};
    this.table.getData().forEach((row) => {
      delete row["id"];
      // the empty string makes sure we get a json with
      // all pricing rows even if they are not yet filled
      arr[RcPricing.getKey(row)] = row.net * 1 || "";
    });
    return arr;
  };

  /**
   * Get prices array-key from row object
   */
  Pricing.prototype.getKey = function (row) {
    let key = "";
    let sep = "";
    for (let col in row) {
      if (col == "net") continue;
      key += sep + col + ":" + row[col];
      sep = "|";
    }
    return key;
  };

  Pricing.prototype.getPrices = function () {
    return JSON.parse(this.textarea.value || "[]");
  };

  Pricing.prototype.init = function (id, variations, data) {
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
    let table = new Tabulator(id, {
      data: this.addPrices(data),
      layout: "fitColumns",
      columns: columns,
    });
    this.table = table;

    /** ##### events ##### */

    // show rockcalculator on cmd+click
    table.on("cellEditing", function (cell) {
      if (typeof RockCalculator == "undefined") return;
      if (!RockCalculator.metaClick) return;
      RockCalculator.show($(id), cell);
    });

    // update textarea whenever data changes
    table.on("dataChanged", RcPricing.saveJson.bind(this));

    // set prices on all visible rows
    $(document).on(
      "keyup",
      "#set-prices input",
      RcPricing.setPrices.bind(this)
    );
  };

  Pricing.prototype.saveJson = function () {
    this.textarea.value = JSON.stringify(this.getArray());
  };

  Pricing.prototype.setPrices = function (e) {
    e.preventDefault();
    let price = $("#set-prices input").val();
    let table = this.table;
    table.getData("active").forEach((row) => {
      row.net = price;
      table.updateData([row]);
    });
    this.saveJson();
  };

  RcPricing = new Pricing();
})();
