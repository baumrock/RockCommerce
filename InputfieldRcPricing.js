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
    this._taxrate = false;
  }

  Pricing.prototype.addPrices = function (data) {
    let prices = this.getPrices();
    let notFound = Object.assign({}, prices);
    data.forEach((row) => {
      row.id = RcPricing.getKey(row);
      let price = prices[row.id];
      if (price !== undefined) delete notFound[row.id];
      row.net = price;
      row.gross = this.getGross(price);
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
      delete row["gross"];

      // the empty string makes sure we get a json with
      // all pricing rows even if they are not yet filled
      arr[RcPricing.getKey(row)] = row.net * 1 || "";
    });
    return arr;
  };

  Pricing.prototype.getGross = function (net) {
    if (net == "") return "";
    let grossValue = net * (1 + this.taxrate() / 100);
    return parseFloat(grossValue.toFixed(2));
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

  Pricing.prototype.getNet = function (gross) {
    let netValue = gross / (1 + this.taxrate() / 100);
    return parseFloat(netValue.toFixed(2));
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
    columns.push({
      field: "gross",
      title: "Gross Price",
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

    // update net or gross values
    table.on("cellEdited", function (cell) {
      let val = cell.getValue();
      let field = cell.getField();
      if (field == "net") {
        // update gross value
        cell.getRow().update({ gross: RcPricing.getGross(val) });
      } else {
        // update net value
        cell.getRow().update({ net: RcPricing.getNet(val) });
      }
    });

    // update textarea whenever data changes
    table.on("dataChanged", RcPricing.saveJson.bind(this));

    // set prices on all visible rows
    $(document).on(
      "keyup",
      "#set-prices input",
      RcPricing.setPrices.bind(this)
    );

    // reset inputfield when focus lost
    $(document).on("blur", "#set-prices input", function () {
      $(this).val("");
    });
  };

  Pricing.prototype.saveJson = function () {
    this.textarea.value = JSON.stringify(this.getArray());
  };

  Pricing.prototype.setPrices = function (e) {
    e.preventDefault();
    let inputName = $(e.target).attr("name");
    let price = $(e.target).val();
    if (price == "") return;
    let table = this.table;
    table.getData("active").forEach((row) => {
      if (inputName == "net") {
        row.net = price;
        row.gross = this.getGross(price);
      } else {
        row.gross = price;
        row.net = this.getNet(price);
      }
      table.updateData([row]);
    });
    this.saveJson();
  };

  Pricing.prototype.taxrate = function () {
    if (this._taxrate !== false) return this._taxrate;
    return (this._taxrate =
      document.querySelector('select[name="rc_product_taxrate"]').value * 1);
  };

  RcPricing = new Pricing();
})();
