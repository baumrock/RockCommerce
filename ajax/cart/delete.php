<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// sanitize input
$id = (int)$input->id;

// trash page id with given id
$item = $cart->items()->get(['id' => $id]);
if ($item->id) {
  $item->trash();
  $cart->refreshItems();
}

return [
  'success' => 1,
  'cart' => $cart->getJsonArray(),
];
