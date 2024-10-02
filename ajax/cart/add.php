<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// product to add
$product = rockcommerce()->getProduct(1555);

// check if the product is valid
if (!$product->isProduct) {
  return [
    'error' => 'Not a valid product',
  ];
}

// add the product to the cart
// tbd

return [
  'success' => 'Item has been added to the cart',
];
