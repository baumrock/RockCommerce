<?php

namespace ProcessWire;

use RockCommerce\Item;

// access control
// nothing to do - anybody can set the item count of their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// sanitize input
$itemID = (int)$input->item;
$amount = (int)$input->amount;

$item = $cart->getItem($itemID);
if ($item instanceof Item) $item->setAmount($amount);

return [
  'success' => 1,
  'cart' => $cart->getJsonArray(),
];
