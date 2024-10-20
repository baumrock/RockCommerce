<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// sanitize input
$pid = (int)$input->pid;
$amount = (int)$input->amount;
$variation = rockcommerce()->sanitizeVariation($input->variation);

// product to add
$product = rockcommerce()->getProduct($pid);
bd($product, 'product');

// check if the product is valid
if (!$product || !$product->isProduct) {
  return [
    'error' => 'Not a valid product',
  ];
}

// add the product to the cart
$item = $cart->addItem($product, $amount, $variation);
bd($item, 'item');
return [
  'success' => 1,
  'cart' => $cart->getJsonArray(),
  'itemAdded' => $item->getJsonArray(),
];
