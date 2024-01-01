"use strict";

// temporary hack to focus shop tab for development
$(document).ready(() => {
  $("#_Inputfield_rc_product_rctab").click();
});

function _RcVariations() {}

_RcVariations.prototype.init = function (id, variations, data) {
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

  console.log(data);

  //create Tabulator on DOM element with id "example-table"
  var table = new Tabulator(id, {
    data: data, //assign data to table
    layout: "fitColumns", //fit columns to width of table (optional)
    columns: columns,
  });
};

var RcVariations = new _RcVariations();
