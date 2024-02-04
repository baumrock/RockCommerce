<?php

namespace ProcessWire;

use RockCommerce\ProductPage;

$rc = rockcommerce();

$id = $input->post('id', 'int');
$available = $input->post('available', 'bool');

// check if it is a product
/** @var ProductPage $product */
$product = rockcommerce()->getProduct($id);
if (!$product) throw new WireException("Invalid Product");

$product->setAvailable($available);
return $product->getPriceEditorRow();
