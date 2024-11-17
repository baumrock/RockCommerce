<?php

namespace ProcessWire;

// access control
// nothing to do - anybody may add items to their cart

// get the current user's cart
$cart = rockcommerce()->cart();

// sanitize input
$pid       = (int)$input->pid;
$amount    = (int)$input->amount;
$variation = (string)rockcommerce()->sanitizeVariation($input->variation);
$taxID     = (int)$input->taxID;

// update taxID in cart
$cart->setTaxID($taxID);

// product to add
$product = rockcommerce()->getProduct($pid);

// check if the product is valid
$error = ['error' => 'Not a valid product'];
if (!$product) return $error;
if (!$product->isProduct) return $error;

// add the product to the cart
$item = $cart->addItem($product, $amount, $variation);
return [
  'success'   => 1,
  'cart'      => $cart->getJsonArray(),
  'itemAdded' => $item->getJsonArray(),
];
