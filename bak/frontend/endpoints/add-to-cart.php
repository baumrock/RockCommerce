<?php

namespace ProcessWire;

$rc = rockcommerce();

// add product to cart
$amount = $input->get('amount', 'int');
$product = $rc->getProduct($input->get('product', 'int'));

// get variation
$item = $rc->cart()->addItem(
  product: $product,
  amount: $amount,
  variation: $product->getVariationString($input),
);

// redirect
$session->redirect("/rc/item-added");
