<?php

namespace ProcessWire;

use RockCommerce\Item;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// sanitize input
$id = (int)$input->id;

// trash page id with given id
$item = $cart->getItem($id);
if ($item instanceof Item && $item->id) {
  $item->trash();
  $cart->refreshItems();
}

return [
  'success' => 1,
  'cart' => $cart->getJsonArray(),
];
