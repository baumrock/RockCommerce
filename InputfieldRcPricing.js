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
      let taxrate = document.querySelector(
        'select[name="rc_product_taxrate"]'
      ).value;
      let grossValue = row.net * (1 + taxrate / 100);
      row.gross = parseFloat(grossValue.toFixed(2));
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
      let field = cell.getField();
      let taxrate = document.querySelector(
        'select[name="rc_product_taxrate"]'
      ).value;
      if (field == "net") {
        // update gross value
        let netValue = cell.getValue();
        let grossValue = netValue * (1 + taxrate / 100);
        grossValue = parseFloat(grossValue.toFixed(2));
        cell.getRow().update({ gross: grossValue });
      } else {
        // update net value
        let grossValue = cell.getValue();
        let netValue = grossValue / (1 + taxrate / 100);
        netValue = parseFloat(netValue.toFixed(2));
        cell.getRow().update({ net: netValue });
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
      let taxrate = document.querySelector(
        'select[name="rc_product_taxrate"]'
      ).value;
      if (inputName == "net") {
        row.net = price;
        let grossValue = row.net * (1 + taxrate / 100);
        row.gross = parseFloat(grossValue.toFixed(2));
      } else if (inputName == "gross") {
        row.gross = price;
        let netValue = row.gross / (1 + taxrate / 100);
        row.net = parseFloat(netValue.toFixed(2));
      }
      table.updateData([row]);
    });
    this.saveJson();
  };

  RcPricing = new Pricing();
})();
