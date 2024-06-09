<?php

namespace ProcessWire;

use RockCommerce\ProductPage;

$rc = rockcommerce();

$id = $input->post('id', 'int');
$variation = $input->post('variation', 'string');

// check if it is a product
/** @var ProductPage $product */
$product = rockcommerce()->getProduct($id);
if (!$product) throw new WireException("Invalid Product");

// get new net price
$net = $input->post('net', 'float');
if ($input->post('field', 'string') === 'gross') {
  // gross price was updated!
  // calculate new net
  $gross = $input->post('gross', 'float');
  $net = $product->getNet($gross);
}

// sleep(1);

// save data
if (!$variation) $product->setSimplePrice($net);
else $product->setComplexPrice($net, $variation);

return $product->getPriceEditorRow();
