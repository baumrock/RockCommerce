<?php

namespace ProcessWire;

use RockCommerce\Item;

// access control
// nothing to do - anybody can set their taxrate

// sanitize input
$taxID = (int)$input->taxID;

// save taxID to cart
rockcommerce()->cart()->setTaxID($taxID);

return [
  'success' => 1,
];
