<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();
$cart->setTaxrate($input->taxrate);

// sanitize input
$pid = (int)$input->item['pid'];
$amount = (int)$input->item['amount'];
$variation = rockcommerce()->sanitizeVariation($input->item['variation']);

// product to add
$product = rockcommerce()->getProduct($pid);

// check if the product is valid
if (!$product || !$product->isProduct) {
  return [
    'error' => 'Not a valid product',
  ];
}

// add the product to the cart
$item = $cart->addItem($product, $amount, $variation);

return [
  // implement the success message on the frontend
  'success' => 1,
  'cart' => $cart->getArray(),
  'itemAdded' => $item->getArray(),
];
