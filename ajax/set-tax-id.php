<?php

namespace ProcessWire;

use RockCommerce\Item;

// access control
// nothing to do - anybody can set their taxrate

// sanitize input
$taxID = (int)$input->taxID;

// sleep(1);

// save taxID to cart
$cart = rockcommerce()->cart();
$cart->setTaxID($taxID);

return [
  'success' => 1,
  'cart' => $cart->getJsonArray(),
];
